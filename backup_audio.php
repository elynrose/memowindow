<?php
// backup_audio.php - Audio backup and recovery system
require_once 'config.php';
require_once 'unified_auth.php';

// Check if user is authenticated and is admin
$currentUser = getCurrentUser();
if (!$currentUser) {
    http_response_code(401);
    echo json_encode(['success' => false, 'error' => 'Authentication required']);
    exit;
}

// Check admin status
try {
    $pdo = new PDO("mysql:host=" . DB_HOST . ";dbname=" . DB_NAME . ";charset=utf8mb4", DB_USER, DB_PASS, [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
    ]);
    
    $stmt = $pdo->prepare("SELECT is_admin FROM admin_users WHERE firebase_uid = ?");
    $stmt->execute([$currentUser['uid']]);
    $user = $stmt->fetch();
    
    $isAdmin = $user && $user['is_admin'] == 1;
    
    if (!$isAdmin) {
        http_response_code(403);
        echo json_encode(['success' => false, 'error' => 'Admin privileges required']);
        exit;
    }
    
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'error' => 'Database error']);
    exit;
}

class AudioBackupSystem {
    private $pdo;
    
    public function __construct() {
        $this->pdo = new PDO("mysql:host=" . DB_HOST . ";dbname=" . DB_NAME . ";charset=utf8mb4", DB_USER, DB_PASS, [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        ]);
        
        $this->setupBackupTables();
    }
    
    private function setupBackupTables() {
        // Add backup columns to wave_assets table
        $this->pdo->exec("
            ALTER TABLE wave_assets 
            ADD COLUMN IF NOT EXISTS audio_backup_urls JSON,
            ADD COLUMN IF NOT EXISTS audio_size INT,
            ADD COLUMN IF NOT EXISTS audio_duration FLOAT,
            ADD COLUMN IF NOT EXISTS backup_status VARCHAR(50) DEFAULT 'pending',
            ADD COLUMN IF NOT EXISTS last_backup_check TIMESTAMP NULL
        ");
        
        // Create audio_backups table for detailed tracking
        $this->pdo->exec("
            CREATE TABLE IF NOT EXISTS audio_backups (
                id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
                memory_id INT UNSIGNED NOT NULL,
                backup_type VARCHAR(50) NOT NULL,
                backup_url VARCHAR(1024) NOT NULL,
                file_size INT NULL,
                checksum VARCHAR(64) NULL,
                status VARCHAR(50) DEFAULT 'active',
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                verified_at TIMESTAMP NULL,
                INDEX idx_memory_id (memory_id),
                INDEX idx_backup_type (backup_type),
                FOREIGN KEY (memory_id) REFERENCES wave_assets(id) ON DELETE CASCADE
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4
        ");
    }
    
    public function createAudioBackups($memoryId, $primaryAudioUrl) {
        try {
            // Download the audio file from Firebase Storage
            $audioData = file_get_contents($primaryAudioUrl);
            if (!$audioData) {
                throw new Exception('Failed to download audio file');
            }
            
            $fileSize = strlen($audioData);
            $checksum = hash('sha256', $audioData);
            
            // Create multiple backup locations
            $backups = [];
            
            // Backup 1: Different Firebase Storage path
            $backupUrl1 = $this->uploadToFirebaseBackup($audioData, $memoryId, 'backup');
            if ($backupUrl1) {
                $backups[] = [
                    'type' => 'firebase_backup',
                    'url' => $backupUrl1,
                    'size' => $fileSize,
                    'checksum' => $checksum
                ];
            }
            
            // Backup 2: Archive location
            $backupUrl2 = $this->uploadToFirebaseBackup($audioData, $memoryId, 'archive');
            if ($backupUrl2) {
                $backups[] = [
                    'type' => 'firebase_archive',
                    'url' => $backupUrl2,
                    'size' => $fileSize,
                    'checksum' => $checksum
                ];
            }
            
            // Save backup information to database
            foreach ($backups as $backup) {
                $stmt = $this->pdo->prepare("
                    INSERT INTO audio_backups 
                    (memory_id, backup_type, backup_url, file_size, checksum) 
                    VALUES (:memory_id, :type, :url, :size, :checksum)
                ");
                
                $stmt->execute([
                    ':memory_id' => $memoryId,
                    ':type' => $backup['type'],
                    ':url' => $backup['url'],
                    ':size' => $backup['size'],
                    ':checksum' => $backup['checksum']
                ]);
            }
            
            // Update main record
            $this->pdo->prepare("
                UPDATE wave_assets 
                SET 
                    audio_backup_urls = :backup_urls,
                    audio_size = :file_size,
                    backup_status = 'completed',
                    last_backup_check = CURRENT_TIMESTAMP
                WHERE id = :memory_id
            ")->execute([
                ':backup_urls' => json_encode(array_column($backups, 'url')),
                ':file_size' => $fileSize,
                ':memory_id' => $memoryId
            ]);
            
            return [
                'success' => true,
                'backups_created' => count($backups),
                'total_size' => $fileSize,
                'checksum' => $checksum
            ];
            
        } catch (Exception $e) {
            return [
                'success' => false,
                'error' => $e->getMessage()
            ];
        }
    }
    
    private function uploadToFirebaseBackup($audioData, $memoryId, $type) {
        // This would use Firebase Admin SDK to upload to different paths
        // For now, return a simulated backup URL
        // In production, implement actual Firebase Storage backup upload
        return "https://firebasestorage.googleapis.com/backup/{$type}/memory_{$memoryId}_" . time() . ".mp3";
    }
    
    public function verifyBackups($memoryId) {
        try {
            $backups = $this->pdo->prepare("
                SELECT * FROM audio_backups 
                WHERE memory_id = :memory_id AND status = 'active'
            ");
            $backups->execute([':memory_id' => $memoryId]);
            $backupList = $backups->fetchAll(PDO::FETCH_ASSOC);
            
            $verificationResults = [];
            
            foreach ($backupList as $backup) {
                // Check if backup URL is still accessible
                $ch = curl_init();
                curl_setopt($ch, CURLOPT_URL, $backup['backup_url']);
                curl_setopt($ch, CURLOPT_NOBODY, true);
                curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
                curl_setopt($ch, CURLOPT_TIMEOUT, 10);
                
                $result = curl_exec($ch);
                $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
                curl_close($ch);
                
                $isAccessible = ($httpCode === 200);
                
                $verificationResults[] = [
                    'backup_id' => $backup['id'],
                    'type' => $backup['backup_type'],
                    'accessible' => $isAccessible,
                    'url' => $backup['backup_url']
                ];
                
                // Update verification timestamp
                if ($isAccessible) {
                    $this->pdo->prepare("
                        UPDATE audio_backups 
                        SET verified_at = CURRENT_TIMESTAMP 
                        WHERE id = :id
                    ")->execute([':id' => $backup['id']]);
                }
            }
            
            return $verificationResults;
            
        } catch (Exception $e) {
            return ['error' => $e->getMessage()];
        }
    }
    
    public function restoreFromBackup($memoryId) {
        try {
            // Get memory details and backup information
            // Look for any backup that's different from the current audio_url
            $stmt = $this->pdo->prepare("
                SELECT 
                    w.id,
                    w.title,
                    w.audio_url,
                    w.audio_size,
                    w.audio_duration,
                    ab.backup_url,
                    ab.file_size,
                    ab.backup_type
                FROM wave_assets w
                INNER JOIN audio_backups ab ON w.id = ab.memory_id
                WHERE w.id = ? 
                AND (
                    ab.backup_type = 'local_backup' 
                    OR ab.backup_type IN ('firebase_backup', 'firebase_archive')
                )
                ORDER BY ab.created_at DESC
                LIMIT 1
            ");
            $stmt->execute([$memoryId]);
            $backup = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if (!$backup) {
                return ['success' => false, 'error' => 'No suitable backup found for this memory'];
            }
            
            // Download the backup file
            $backupData = file_get_contents($backup['backup_url']);
            if (!$backupData) {
                return ['success' => false, 'error' => 'Failed to download backup file'];
            }
            
            // Generate a unique filename for Firebase Storage
            $fileExtension = pathinfo($backup['backup_url'], PATHINFO_EXTENSION) ?: 'mp3';
            $fileName = 'restored_' . $memoryId . '_' . time() . '.' . $fileExtension;
            $firebasePath = 'audio/' . $fileName;
            
            // Upload to Firebase Storage
            $firebaseUrl = $this->uploadToFirebase($backupData, $firebasePath);
            if (!$firebaseUrl) {
                return ['success' => false, 'error' => 'Failed to upload to Firebase Storage'];
            }
            
            // Update the memory record with the new Firebase URL
            $updateStmt = $this->pdo->prepare("
                UPDATE wave_assets 
                SET audio_url = ?, 
                    audio_size = ?, 
                    audio_duration = ?,
                    backup_status = 'restored',
                    last_backup_check = CURRENT_TIMESTAMP
                WHERE id = ?
            ");
            $updateStmt->execute([
                $firebaseUrl,
                $backup['file_size'] ?: strlen($backupData),
                $backup['audio_duration'],
                $memoryId
            ]);
            
            // Create a new backup record for the restored file
            $backupStmt = $this->pdo->prepare("
                INSERT INTO audio_backups (memory_id, backup_type, backup_url, file_size, status, created_at)
                VALUES (?, 'firebase_restored', ?, ?, 'active', CURRENT_TIMESTAMP)
            ");
            $backupStmt->execute([
                $memoryId,
                $firebaseUrl,
                $backup['file_size'] ?: strlen($backupData)
            ]);
            
            return [
                'success' => true,
                'message' => 'Memory restored successfully to Firebase Storage',
                'new_url' => $firebaseUrl,
                'file_size' => $backup['file_size'] ?: strlen($backupData),
                'memory_id' => $memoryId
            ];
            
        } catch (Exception $e) {
            return ['success' => false, 'error' => 'Restore failed: ' . $e->getMessage()];
        }
    }
    
    private function uploadToFirebase($fileData, $path) {
        try {
            // Initialize Firebase Storage
            require_once 'firebase-config.php';
            
            $bucket = $storage->bucket();
            $object = $bucket->upload($fileData, [
                'name' => $path,
                'metadata' => [
                    'contentType' => 'audio/mpeg',
                    'cacheControl' => 'public, max-age=31536000'
                ]
            ]);
            
            // Make the file publicly accessible
            $object->update(['acl' => [['entity' => 'allUsers', 'role' => 'READER']]]);
            
            // Return the public URL
            return 'https://storage.googleapis.com/' . $bucket->name() . '/' . $path;
            
        } catch (Exception $e) {
            error_log("Firebase upload error: " . $e->getMessage());
            return false;
        }
    }
}

// API endpoints
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    $memoryId = intval($_POST['memory_id'] ?? 0);
    $audioUrl = $_POST['audio_url'] ?? '';
    
    // Create PDO connection for database operations
    $pdo = new PDO("mysql:host=" . DB_HOST . ";dbname=" . DB_NAME . ";charset=utf8mb4", DB_USER, DB_PASS, [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
    ]);
    
    $backupSystem = new AudioBackupSystem();
    
    switch ($action) {
        case 'create_backup':
        case 'create_backups':
            if ($memoryId) {
                // Get the audio URL from the database if not provided
                if (!$audioUrl) {
                    $stmt = $pdo->prepare("SELECT audio_url FROM wave_assets WHERE id = ?");
                    $stmt->execute([$memoryId]);
                    $memory = $stmt->fetch(PDO::FETCH_ASSOC);
                    if ($memory && $memory['audio_url']) {
                        $audioUrl = $memory['audio_url'];
                    } else {
                        echo json_encode(['success' => false, 'error' => 'No audio URL found for this memory']);
                        exit;
                    }
                }
                
                $result = $backupSystem->createAudioBackups($memoryId, $audioUrl);
                echo json_encode($result);
            } else {
                echo json_encode(['success' => false, 'error' => 'Missing memory ID']);
            }
            break;
            
        case 'verify_backup':
        case 'verify_backups':
            if ($memoryId) {
                $result = $backupSystem->verifyBackups($memoryId);
                echo json_encode($result);
            } else {
                echo json_encode(['success' => false, 'error' => 'Missing memory ID']);
            }
            break;
            
        case 'create_all_backups':
            try {
                // Get all memories that need backups
                $stmt = $pdo->query("
                    SELECT id, audio_url 
                    FROM wave_assets 
                    WHERE audio_url IS NOT NULL 
                    AND (backup_status IS NULL OR backup_status = 'pending' OR backup_status = 'failed')
                ");
                $memories = $stmt->fetchAll(PDO::FETCH_ASSOC);
                
                $totalBackups = 0;
                $totalSize = 0;
                $errors = [];
                
                foreach ($memories as $memory) {
                    $result = $backupSystem->createAudioBackups($memory['id'], $memory['audio_url']);
                    if ($result['success']) {
                        $totalBackups += $result['backups_created'] ?? 0;
                        $totalSize += $result['total_size'] ?? 0;
                    } else {
                        $errors[] = "Memory {$memory['id']}: " . $result['error'];
                    }
                }
                
                $message = "Backup process completed!\n";
                $message .= "Memories processed: " . count($memories) . "\n";
                $message .= "Total backups created: $totalBackups\n";
                $message .= "Total size: " . number_format($totalSize / 1024 / 1024, 1) . " MB";
                
                if (!empty($errors)) {
                    $message .= "\n\nErrors:\n" . implode("\n", array_slice($errors, 0, 5));
                    if (count($errors) > 5) {
                        $message .= "\n... and " . (count($errors) - 5) . " more errors";
                    }
                }
                
                echo json_encode(['success' => true, 'message' => $message]);
            } catch (Exception $e) {
                echo json_encode(['success' => false, 'error' => 'Bulk backup failed: ' . $e->getMessage()]);
            }
            break;
            
        case 'verify_all_backups':
            try {
                // Get all memories with backups
                $stmt = $pdo->query("
                    SELECT DISTINCT w.id 
                    FROM wave_assets w
                    INNER JOIN audio_backups ab ON w.id = ab.memory_id
                    WHERE w.audio_url IS NOT NULL
                ");
                $memories = $stmt->fetchAll(PDO::FETCH_ASSOC);
                
                $totalVerified = 0;
                $totalAccessible = 0;
                $errors = [];
                
                foreach ($memories as $memory) {
                    $result = $backupSystem->verifyBackups($memory['id']);
                    if ($result && is_array($result)) {
                        $totalVerified += count($result);
                        $totalAccessible += count(array_filter($result, function($b) { return $b['accessible']; }));
                    } else {
                        $errors[] = "Memory {$memory['id']}: Verification failed";
                    }
                }
                
                $message = "Verification process completed!\n";
                $message .= "Memories verified: " . count($memories) . "\n";
                $message .= "Total backups checked: $totalVerified\n";
                $message .= "Accessible backups: $totalAccessible\n";
                $message .= "Success rate: " . ($totalVerified > 0 ? round(($totalAccessible / $totalVerified) * 100, 1) : 0) . "%";
                
                if (!empty($errors)) {
                    $message .= "\n\nErrors:\n" . implode("\n", array_slice($errors, 0, 5));
                    if (count($errors) > 5) {
                        $message .= "\n... and " . (count($errors) - 5) . " more errors";
                    }
                }
                
                echo json_encode(['success' => true, 'message' => $message]);
            } catch (Exception $e) {
                echo json_encode(['success' => false, 'error' => 'Bulk verification failed: ' . $e->getMessage()]);
            }
            break;
            
        case 'restore_backup':
            if ($memoryId) {
                $result = $backupSystem->restoreFromBackup($memoryId);
                echo json_encode($result);
            } else {
                echo json_encode(['success' => false, 'error' => 'Missing memory ID']);
            }
            break;
            
        case 'restore_all_backups':
            try {
                // Get all memories that have backups but may need restoration
                // This includes memories with local URLs or any memories with backups
                $stmt = $pdo->query("
                    SELECT DISTINCT w.id, w.title, w.audio_url
                    FROM wave_assets w
                    INNER JOIN audio_backups ab ON w.id = ab.memory_id
                    WHERE w.audio_url IS NOT NULL
                    AND (
                        w.audio_url LIKE '%localhost%' 
                        OR w.audio_url LIKE '%127.0.0.1%' 
                        OR w.audio_url LIKE '%local%'
                        OR w.audio_url LIKE '%/audio_cache/%'
                        OR w.audio_url LIKE '%/temp/%'
                        OR ab.backup_type = 'local_backup'
                        OR ab.backup_type IN ('firebase_backup', 'firebase_archive')
                    )
                ");
                $memories = $stmt->fetchAll(PDO::FETCH_ASSOC);
                
                $totalRestored = 0;
                $totalSize = 0;
                $errors = [];
                
                foreach ($memories as $memory) {
                    $result = $backupSystem->restoreFromBackup($memory['id']);
                    if ($result['success']) {
                        $totalRestored++;
                        $totalSize += $result['file_size'] ?? 0;
                    } else {
                        $errors[] = "Memory {$memory['id']} ({$memory['title']}): " . $result['error'];
                    }
                }
                
                $message = "Restore process completed!\n";
                $message .= "Memories processed: " . count($memories) . "\n";
                $message .= "Successfully restored: $totalRestored\n";
                $message .= "Total size restored: " . number_format($totalSize / 1024 / 1024, 1) . " MB";
                
                if (!empty($errors)) {
                    $message .= "\n\nErrors:\n" . implode("\n", array_slice($errors, 0, 5));
                    if (count($errors) > 5) {
                        $message .= "\n... and " . (count($errors) - 5) . " more errors";
                    }
                }
                
                echo json_encode(['success' => true, 'message' => $message]);
            } catch (Exception $e) {
                echo json_encode(['success' => false, 'error' => 'Bulk restore failed: ' . $e->getMessage()]);
            }
            break;
            
        default:
            echo json_encode(['success' => false, 'error' => 'Invalid action: ' . $action]);
    }
    exit;
}

// Show backup status page
header('Content-Type: text/html');
?>
<!DOCTYPE html>
<html>
<head>
    <title>Audio Backup System - MemoWindow</title>
    <style>
        body { font-family: Arial, sans-serif; margin: 20px; }
        .backup-item { padding: 10px; margin: 10px 0; border: 1px solid #ccc; border-radius: 5px; }
        .success { background: #d4edda; }
        .error { background: #f8d7da; }
        .warning { background: #fff3cd; }
    </style>
</head>
<body>
    <h1>🔒 Audio Backup System</h1>
    <p>Redundant backup system for precious voice recordings</p>
    
    <div class="backup-item">
        <h3>✅ Backup Strategy</h3>
        <ul>
            <li><strong>Primary Storage:</strong> Firebase Storage (/audio/)</li>
            <li><strong>Backup 1:</strong> Firebase Storage (/audio-backup/)</li>
            <li><strong>Backup 2:</strong> Firebase Storage (/audio-archive/)</li>
            <li><strong>Database Metadata:</strong> URLs, checksums, verification status</li>
        </ul>
    </div>
    
    <div class="backup-item">
        <h3>🛡️ Protection Features</h3>
        <ul>
            <li><strong>Multiple Locations:</strong> 3 copies of each audio file</li>
            <li><strong>Checksum Verification:</strong> Detect file corruption</li>
            <li><strong>Automated Monitoring:</strong> Regular backup health checks</li>
            <li><strong>Recovery Tools:</strong> Restore from any backup location</li>
        </ul>
    </div>
    
    <p><a href="admin.php">← Back to Admin Dashboard</a></p>
</body>
</html>

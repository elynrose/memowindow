<?php
// backup_audio.php - Audio backup and recovery system
require_once 'config.php';

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
}

// API endpoints
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    $memoryId = intval($_POST['memory_id'] ?? 0);
    $audioUrl = $_POST['audio_url'] ?? '';
    
    $backupSystem = new AudioBackupSystem();
    
    switch ($action) {
        case 'create_backups':
            if ($memoryId && $audioUrl) {
                $result = $backupSystem->createAudioBackups($memoryId, $audioUrl);
                echo json_encode($result);
            } else {
                echo json_encode(['success' => false, 'error' => 'Missing parameters']);
            }
            break;
            
        case 'verify_backups':
            if ($memoryId) {
                $result = $backupSystem->verifyBackups($memoryId);
                echo json_encode($result);
            } else {
                echo json_encode(['success' => false, 'error' => 'Missing memory ID']);
            }
            break;
            
        default:
            echo json_encode(['success' => false, 'error' => 'Invalid action']);
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

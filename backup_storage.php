<?php
/**
 * Backup Storage Handler
 * Handles local server backup storage for audio files and images
 */

class BackupStorage {
    private static $basePath = __DIR__ . '/backups/';
    
    /**
     * Save file to local backup storage
     * @param string $filePath Path to the file to save
     * @param string $folder Folder name (waveforms, audio, qr-codes)
     * @param string $fileName Desired filename
     * @return array Result with success status and local path
     */
    public static function saveFile($filePath, $folder, $fileName) {
        try {
            // Ensure backup directory exists
            $backupDir = self::$basePath . $folder;
            if (!is_dir($backupDir)) {
                if (!mkdir($backupDir, 0755, true)) {
                    throw new Exception("Failed to create backup directory: $backupDir");
                }
            }
            
            $localPath = $backupDir . '/' . $fileName;
            
            // Copy file to backup location
            if (is_file($filePath)) {
                if (!copy($filePath, $localPath)) {
                    throw new Exception("Failed to copy file to backup location");
                }
            } else {
                throw new Exception("Source file does not exist: $filePath");
            }
            
            return [
                'success' => true,
                'local_path' => $localPath,
                'relative_path' => "backups/$folder/$fileName"
            ];
            
        } catch (Exception $e) {
            error_log("Backup storage error: " . $e->getMessage());
            return [
                'success' => false,
                'error' => $e->getMessage()
            ];
        }
    }
    
    /**
     * Save blob data to local backup storage
     * @param string $blobData Base64 encoded blob data
     * @param string $folder Folder name
     * @param string $fileName Desired filename
     * @return array Result with success status and local path
     */
    public static function saveBlob($blobData, $folder, $fileName) {
        try {
            // Ensure backup directory exists
            $backupDir = self::$basePath . $folder;
            if (!is_dir($backupDir)) {
                if (!mkdir($backupDir, 0755, true)) {
                    throw new Exception("Failed to create backup directory: $backupDir");
                }
            }
            
            $localPath = $backupDir . '/' . $fileName;
            
            // Decode and save blob data
            $decodedData = base64_decode($blobData);
            if ($decodedData === false) {
                throw new Exception("Failed to decode blob data");
            }
            
            if (file_put_contents($localPath, $decodedData) === false) {
                throw new Exception("Failed to write blob data to backup location");
            }
            
            return [
                'success' => true,
                'local_path' => $localPath,
                'relative_path' => "backups/$folder/$fileName"
            ];
            
        } catch (Exception $e) {
            error_log("Backup storage error: " . $e->getMessage());
            return [
                'success' => false,
                'error' => $e->getMessage()
            ];
        }
    }
    
    /**
     * Delete file from local backup storage
     * @param string $relativePath Relative path from backups directory
     * @return bool Success status
     */
    public static function deleteFile($relativePath) {
        try {
            $fullPath = self::$basePath . $relativePath;
            
            if (file_exists($fullPath)) {
                return unlink($fullPath);
            }
            
            return true; // File doesn't exist, consider it deleted
            
        } catch (Exception $e) {
            error_log("Backup delete error: " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Get backup file info
     * @param string $relativePath Relative path from backups directory
     * @return array File info or null if not found
     */
    public static function getFileInfo($relativePath) {
        try {
            $fullPath = self::$basePath . $relativePath;
            
            if (file_exists($fullPath)) {
                return [
                    'exists' => true,
                    'size' => filesize($fullPath),
                    'modified' => filemtime($fullPath),
                    'path' => $fullPath
                ];
            }
            
            return ['exists' => false];
            
        } catch (Exception $e) {
            error_log("Backup file info error: " . $e->getMessage());
            return ['exists' => false, 'error' => $e->getMessage()];
        }
    }
    
    /**
     * List files in backup directory
     * @param string $folder Folder name
     * @return array List of files
     */
    public static function listFiles($folder) {
        try {
            $backupDir = self::$basePath . $folder;
            
            if (!is_dir($backupDir)) {
                return [];
            }
            
            $files = [];
            $iterator = new DirectoryIterator($backupDir);
            
            foreach ($iterator as $file) {
                if ($file->isFile()) {
                    $files[] = [
                        'name' => $file->getFilename(),
                        'size' => $file->getSize(),
                        'modified' => $file->getMTime(),
                        'path' => "backups/$folder/" . $file->getFilename()
                    ];
                }
            }
            
            return $files;
            
        } catch (Exception $e) {
            error_log("Backup list files error: " . $e->getMessage());
            return [];
        }
    }
    
    /**
     * Get backup storage statistics
     * @return array Storage statistics
     */
    public static function getStats() {
        try {
            $stats = [
                'total_files' => 0,
                'total_size' => 0,
                'folders' => []
            ];
            
            $folders = ['waveforms', 'audio', 'qr-codes'];
            
            foreach ($folders as $folder) {
                $folderStats = [
                    'files' => 0,
                    'size' => 0
                ];
                
                $backupDir = self::$basePath . $folder;
                if (is_dir($backupDir)) {
                    $iterator = new DirectoryIterator($backupDir);
                    
                    foreach ($iterator as $file) {
                        if ($file->isFile()) {
                            $folderStats['files']++;
                            $folderStats['size'] += $file->getSize();
                        }
                    }
                }
                
                $stats['folders'][$folder] = $folderStats;
                $stats['total_files'] += $folderStats['files'];
                $stats['total_size'] += $folderStats['size'];
            }
            
            return $stats;
            
        } catch (Exception $e) {
            error_log("Backup stats error: " . $e->getMessage());
            return [
                'total_files' => 0,
                'total_size' => 0,
                'folders' => [],
                'error' => $e->getMessage()
            ];
        }
    }
}

// API endpoint for backup operations
if (php_sapi_name() !== 'cli' && isset($_SERVER['REQUEST_METHOD'])) {
    header('Content-Type: application/json');
    
    $method = $_SERVER['REQUEST_METHOD'];
    $action = $_GET['action'] ?? '';
    
    try {
        switch ($action) {
            case 'save_blob':
                if ($method !== 'POST') {
                    throw new Exception('POST method required');
                }
                
                $input = json_decode(file_get_contents('php://input'), true);
                if (!$input || !isset($input['blob_data']) || !isset($input['folder']) || !isset($input['filename'])) {
                    throw new Exception('Missing required parameters');
                }
                
                $result = BackupStorage::saveBlob($input['blob_data'], $input['folder'], $input['filename']);
                echo json_encode($result);
                break;
                
            case 'delete_file':
                if ($method !== 'DELETE') {
                    throw new Exception('DELETE method required');
                }
                
                $relativePath = $_GET['path'] ?? '';
                if (empty($relativePath)) {
                    throw new Exception('Path parameter required');
                }
                
                $success = BackupStorage::deleteFile($relativePath);
                echo json_encode(['success' => $success]);
                break;
                
            case 'list_files':
                $folder = $_GET['folder'] ?? '';
                if (empty($folder)) {
                    throw new Exception('Folder parameter required');
                }
                
                $files = BackupStorage::listFiles($folder);
                echo json_encode(['files' => $files]);
                break;
                
            case 'stats':
                $stats = BackupStorage::getStats();
                echo json_encode($stats);
                break;
                
            default:
                throw new Exception('Invalid action');
        }
        
    } catch (Exception $e) {
        http_response_code(400);
        echo json_encode(['error' => $e->getMessage()]);
    }
}
?>

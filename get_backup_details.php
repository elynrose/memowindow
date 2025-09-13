<?php
/**
 * get_backup_details.php - Get detailed backup information for a specific memory
 */

require_once 'config.php';
require_once 'unified_auth.php';

// Check if user is admin
$currentUser = getCurrentUser();
if (!$currentUser) {
    http_response_code(401);
    echo "Authentication required";
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
        echo "Admin privileges required";
        exit;
    }
    
} catch (Exception $e) {
    http_response_code(500);
    echo "Database error";
    exit;
}

$memoryId = $_GET['memory_id'] ?? null;

if (!$memoryId) {
    echo "<div style='color: #dc2626; padding: 20px; text-align: center;'>❌ Memory ID required</div>";
    exit;
}

try {
    // Get memory details
    $stmt = $pdo->prepare("
        SELECT 
            w.id,
            w.title,
            w.original_name,
            w.audio_url,
            w.audio_backup_urls,
            w.audio_size,
            w.audio_duration,
            w.backup_status,
            w.last_backup_check,
            w.created_at
        FROM wave_assets w
        WHERE w.id = ?
    ");
    $stmt->execute([$memoryId]);
    $memory = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$memory) {
        echo "<div style='color: #dc2626; padding: 20px; text-align: center;'>❌ Memory not found</div>";
        exit;
    }
    
    // Get backup details
    $stmt = $pdo->prepare("
        SELECT 
            ab.id,
            ab.backup_type,
            ab.backup_url,
            ab.file_size,
            ab.checksum,
            ab.status,
            ab.created_at,
            ab.verified_at
        FROM audio_backups ab
        WHERE ab.memory_id = ?
        ORDER BY ab.created_at DESC
    ");
    $stmt->execute([$memoryId]);
    $backups = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Format file size
    function formatBytes($bytes, $precision = 2) {
        $units = array('B', 'KB', 'MB', 'GB', 'TB');
        
        for ($i = 0; $bytes > 1024 && $i < count($units) - 1; $i++) {
            $bytes /= 1024;
        }
        
        return round($bytes, $precision) . ' ' . $units[$i];
    }
    
    // Format duration
    function formatDuration($seconds) {
        if (!$seconds) return 'Unknown';
        
        $minutes = floor($seconds / 60);
        $seconds = $seconds % 60;
        
        if ($minutes > 0) {
            return sprintf('%d:%02d', $minutes, $seconds);
        } else {
            return sprintf('0:%02d', $seconds);
        }
    }
    
    ?>
    <div style="font-family: Arial, sans-serif;">
        <!-- Memory Information -->
        <div style="background: #f8f9fa; padding: 15px; border-radius: 8px; margin-bottom: 20px;">
            <h4 style="margin: 0 0 10px 0; color: #333;">📱 Memory Information</h4>
            <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 10px; font-size: 14px;">
                <div><strong>Title:</strong> <?php echo htmlspecialchars($memory['title'] ?: 'Untitled'); ?></div>
                <div><strong>Original Name:</strong> <?php echo htmlspecialchars($memory['original_name']); ?></div>
                <div><strong>File Size:</strong> <?php echo $memory['audio_size'] ? formatBytes($memory['audio_size']) : 'Unknown'; ?></div>
                <div><strong>Duration:</strong> <?php echo formatDuration($memory['audio_duration']); ?></div>
                <div><strong>Status:</strong> 
                    <span style="padding: 2px 8px; border-radius: 12px; font-size: 12px; font-weight: 500; text-transform: uppercase; 
                        background: <?php echo $memory['backup_status'] === 'completed' ? '#d1fae5; color: #065f46' : 
                                   ($memory['backup_status'] === 'pending' ? '#fef3c7; color: #92400e' : '#fee2e2; color: #991b1b'); ?>">
                        <?php echo ucfirst($memory['backup_status']); ?>
                    </span>
                </div>
                <div><strong>Created:</strong> <?php echo date('M j, Y g:i A', strtotime($memory['created_at'])); ?></div>
            </div>
        </div>
        
        <!-- Primary Audio URL -->
        <div style="background: #e7f3ff; padding: 15px; border-radius: 8px; margin-bottom: 20px;">
            <h4 style="margin: 0 0 10px 0; color: #0066cc;">🎵 Primary Audio</h4>
            <div style="font-size: 14px;">
                <div style="margin-bottom: 8px;"><strong>URL:</strong></div>
                <div style="background: white; padding: 8px; border-radius: 4px; border: 1px solid #ddd; word-break: break-all; font-family: monospace; font-size: 12px;">
                    <?php echo htmlspecialchars($memory['audio_url']); ?>
                </div>
                <?php if ($memory['audio_url']): ?>
                <div style="margin-top: 8px;">
                    <a href="<?php echo htmlspecialchars($memory['audio_url']); ?>" target="_blank" 
                       style="color: #0066cc; text-decoration: none; font-size: 12px;">🔗 Open in new tab</a>
                </div>
                <?php endif; ?>
            </div>
        </div>
        
        <!-- Backup URLs -->
        <?php if ($memory['audio_backup_urls']): ?>
        <div style="background: #f0f8f0; padding: 15px; border-radius: 8px; margin-bottom: 20px;">
            <h4 style="margin: 0 0 10px 0; color: #28a745;">💾 Backup URLs</h4>
            <div style="font-size: 14px;">
                <?php 
                $backupUrls = json_decode($memory['audio_backup_urls'], true);
                if (is_array($backupUrls)): 
                    foreach ($backupUrls as $index => $url): 
                ?>
                <div style="margin-bottom: 8px;">
                    <div style="font-weight: 500;">Backup <?php echo $index + 1; ?>:</div>
                    <div style="background: white; padding: 8px; border-radius: 4px; border: 1px solid #ddd; word-break: break-all; font-family: monospace; font-size: 12px;">
                        <?php echo htmlspecialchars($url); ?>
                    </div>
                    <div style="margin-top: 4px;">
                        <a href="<?php echo htmlspecialchars($url); ?>" target="_blank" 
                           style="color: #28a745; text-decoration: none; font-size: 12px;">🔗 Open in new tab</a>
                    </div>
                </div>
                <?php 
                    endforeach;
                else: 
                ?>
                <div style="color: #666;">No backup URLs available</div>
                <?php endif; ?>
            </div>
        </div>
        <?php endif; ?>
        
        <!-- Detailed Backup Records -->
        <div style="background: #fff8e1; padding: 15px; border-radius: 8px; margin-bottom: 20px;">
            <h4 style="margin: 0 0 15px 0; color: #f57c00;">📋 Detailed Backup Records</h4>
            <?php if (empty($backups)): ?>
                <div style="color: #666; text-align: center; padding: 20px;">No detailed backup records found</div>
            <?php else: ?>
                <div style="overflow-x: auto;">
                    <table style="width: 100%; border-collapse: collapse; font-size: 14px;">
                        <thead>
                            <tr style="background: #f5f5f5;">
                                <th style="padding: 8px; text-align: left; border-bottom: 1px solid #ddd;">Type</th>
                                <th style="padding: 8px; text-align: left; border-bottom: 1px solid #ddd;">Size</th>
                                <th style="padding: 8px; text-align: left; border-bottom: 1px solid #ddd;">Status</th>
                                <th style="padding: 8px; text-align: left; border-bottom: 1px solid #ddd;">Created</th>
                                <th style="padding: 8px; text-align: left; border-bottom: 1px solid #ddd;">Verified</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($backups as $backup): ?>
                            <tr>
                                <td style="padding: 8px; border-bottom: 1px solid #eee;"><?php echo htmlspecialchars($backup['backup_type']); ?></td>
                                <td style="padding: 8px; border-bottom: 1px solid #eee;"><?php echo $backup['file_size'] ? formatBytes($backup['file_size']) : 'Unknown'; ?></td>
                                <td style="padding: 8px; border-bottom: 1px solid #eee;">
                                    <span style="padding: 2px 6px; border-radius: 10px; font-size: 11px; font-weight: 500; text-transform: uppercase;
                                        background: <?php echo $backup['status'] === 'active' ? '#d1fae5; color: #065f46' : '#fee2e2; color: #991b1b'; ?>">
                                        <?php echo ucfirst($backup['status']); ?>
                                    </span>
                                </td>
                                <td style="padding: 8px; border-bottom: 1px solid #eee;"><?php echo date('M j, g:i A', strtotime($backup['created_at'])); ?></td>
                                <td style="padding: 8px; border-bottom: 1px solid #eee;">
                                    <?php echo $backup['verified_at'] ? date('M j, g:i A', strtotime($backup['verified_at'])) : 'Never'; ?>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php endif; ?>
        </div>
        
        <!-- Last Backup Check -->
        <?php if ($memory['last_backup_check']): ?>
        <div style="background: #f8f9fa; padding: 15px; border-radius: 8px; text-align: center; font-size: 14px; color: #666;">
            <strong>Last Backup Check:</strong> <?php echo date('M j, Y g:i A', strtotime($memory['last_backup_check'])); ?>
        </div>
        <?php endif; ?>
    </div>
    <?php
    
} catch (Exception $e) {
    echo "<div style='color: #dc2626; padding: 20px; text-align: center;'>❌ Error: " . htmlspecialchars($e->getMessage()) . "</div>";
}
?>

<?php
// backup_admin.php - Admin interface for backup storage management
require_once 'auth_check.php';
require_once 'backup_storage.php';

// Require admin authentication
$userFirebaseUID = requireAdmin();

$stats = BackupStorage::getStats();
$folders = ['waveforms', 'audio', 'qr-codes'];
$folderFiles = [];

foreach ($folders as $folder) {
    $folderFiles[$folder] = BackupStorage::listFiles($folder);
}

function formatBytes($bytes, $precision = 2) {
    $units = array('B', 'KB', 'MB', 'GB', 'TB');
    
    for ($i = 0; $bytes > 1024 && $i < count($units) - 1; $i++) {
        $bytes /= 1024;
    }
    
    return round($bytes, $precision) . ' ' . $units[$i];
}

function formatDate($timestamp) {
    return date('Y-m-d H:i:s', $timestamp);
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Backup Storage Admin - MemoWindow</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        
        body {
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
            background: #f8fafc;
            color: #1a202c;
            line-height: 1.6;
        }
        
        .container {
            max-width: 1200px;
            margin: 0 auto;
            padding: 20px;
        }
        
        .header {
            background: white;
            border-radius: 12px;
            padding: 24px;
            margin-bottom: 24px;
            box-shadow: 0 1px 3px rgba(0,0,0,0.1);
        }
        
        .header h1 {
            color: #2d3748;
            margin-bottom: 8px;
        }
        
        .header p {
            color: #718096;
        }
        
        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 20px;
            margin-bottom: 24px;
        }
        
        .stat-card {
            background: white;
            border-radius: 12px;
            padding: 20px;
            box-shadow: 0 1px 3px rgba(0,0,0,0.1);
        }
        
        .stat-card h3 {
            color: #4a5568;
            margin-bottom: 12px;
            font-size: 14px;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }
        
        .stat-value {
            font-size: 24px;
            font-weight: 600;
            color: #2d3748;
            margin-bottom: 4px;
        }
        
        .stat-label {
            color: #718096;
            font-size: 14px;
        }
        
        .folder-section {
            background: white;
            border-radius: 12px;
            padding: 24px;
            margin-bottom: 24px;
            box-shadow: 0 1px 3px rgba(0,0,0,0.1);
        }
        
        .folder-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 16px;
        }
        
        .folder-title {
            font-size: 18px;
            font-weight: 600;
            color: #2d3748;
        }
        
        .folder-count {
            background: #e2e8f0;
            color: #4a5568;
            padding: 4px 12px;
            border-radius: 20px;
            font-size: 14px;
        }
        
        .files-table {
            width: 100%;
            border-collapse: collapse;
        }
        
        .files-table th,
        .files-table td {
            text-align: left;
            padding: 12px;
            border-bottom: 1px solid #e2e8f0;
        }
        
        .files-table th {
            background: #f7fafc;
            color: #4a5568;
            font-weight: 600;
            font-size: 14px;
        }
        
        .files-table td {
            color: #2d3748;
        }
        
        .file-name {
            font-family: 'Monaco', 'Menlo', monospace;
            font-size: 13px;
        }
        
        .file-size {
            color: #718096;
        }
        
        .file-date {
            color: #718096;
            font-size: 14px;
        }
        
        .empty-state {
            text-align: center;
            padding: 40px;
            color: #718096;
        }
        
        .empty-state-icon {
            font-size: 48px;
            margin-bottom: 16px;
        }
        
        .nav-links {
            display: flex;
            gap: 16px;
            margin-bottom: 20px;
        }
        
        .nav-link {
            color: #4a5568;
            text-decoration: none;
            padding: 8px 16px;
            border-radius: 6px;
            transition: all 0.2s;
        }
        
        .nav-link:hover {
            background: #e2e8f0;
            color: #2d3748;
        }
        
        .nav-link.active {
            background: #667eea;
            color: white;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1>🔄 Backup Storage Admin</h1>
            <p>Local backup storage management for MemoWindow files</p>
            
            <div class="nav-links">
                <a href="admin.php" class="nav-link">← Back to Admin</a>
                <a href="backup_admin.php" class="nav-link active">Backup Storage</a>
            </div>
        </div>
        
        <!-- Overall Statistics -->
        <div class="stats-grid">
            <div class="stat-card">
                <h3>Total Files</h3>
                <div class="stat-value"><?php echo number_format($stats['total_files']); ?></div>
                <div class="stat-label">Backed up files</div>
            </div>
            
            <div class="stat-card">
                <h3>Total Size</h3>
                <div class="stat-value"><?php echo formatBytes($stats['total_size']); ?></div>
                <div class="stat-label">Storage used</div>
            </div>
            
            <div class="stat-card">
                <h3>Backup Status</h3>
                <div class="stat-value"><?php echo $stats['total_files'] > 0 ? '✅ Active' : '⚠️ Empty'; ?></div>
                <div class="stat-label">System status</div>
            </div>
        </div>
        
        <!-- Folder Details -->
        <?php foreach ($folders as $folder): ?>
        <div class="folder-section">
            <div class="folder-header">
                <h2 class="folder-title">
                    <?php 
                    $icons = [
                        'waveforms' => '🎵',
                        'audio' => '🎧',
                        'qr-codes' => '📱'
                    ];
                    echo $icons[$folder] ?? '📁';
                    ?>
                    <?php echo ucfirst($folder); ?>
                </h2>
                <div class="folder-count">
                    <?php echo count($folderFiles[$folder]); ?> files
                    (<?php echo formatBytes($stats['folders'][$folder]['size']); ?>)
                </div>
            </div>
            
            <?php if (empty($folderFiles[$folder])): ?>
                <div class="empty-state">
                    <div class="empty-state-icon">📂</div>
                    <p>No files in this folder yet</p>
                </div>
            <?php else: ?>
                <table class="files-table">
                    <thead>
                        <tr>
                            <th>Filename</th>
                            <th>Size</th>
                            <th>Modified</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($folderFiles[$folder] as $file): ?>
                        <tr>
                            <td class="file-name"><?php echo htmlspecialchars($file['name']); ?></td>
                            <td class="file-size"><?php echo formatBytes($file['size']); ?></td>
                            <td class="file-date"><?php echo formatDate($file['modified']); ?></td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            <?php endif; ?>
        </div>
        <?php endforeach; ?>
        
        <div class="folder-section">
            <h2 class="folder-title">ℹ️ Backup Information</h2>
            <div style="color: #4a5568; line-height: 1.8;">
                <p><strong>Purpose:</strong> Local backup storage acts as a secondary copy of all uploaded files.</p>
                <p><strong>Primary Storage:</strong> Firebase Storage (used for serving files to users)</p>
                <p><strong>Backup Storage:</strong> Local server filesystem (backup only)</p>
                <p><strong>Automatic:</strong> Files are automatically backed up when uploaded and deleted when removed.</p>
                <p><strong>Non-blocking:</strong> Backup failures don't affect the main upload/delete operations.</p>
            </div>
        </div>
    </div>
</body>
</html>

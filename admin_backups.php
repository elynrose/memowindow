<?php
// admin_backups.php - Audio backup management interface
require_once 'unified_auth.php';
require_once 'config.php';

// Check if user is authenticated
$currentUser = getCurrentUser();
if (!$currentUser) {
    header('Location: login.php');
    exit;
}

// Check if user is admin
try {
    $pdo = new PDO("mysql:host=" . DB_HOST . ";dbname=" . DB_NAME . ";charset=utf8mb4", DB_USER, DB_PASS, [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
    ]);
    
    $stmt = $pdo->prepare("SELECT is_admin FROM admin_users WHERE firebase_uid = ?");
    $stmt->execute([$currentUser['uid']]);
    $user = $stmt->fetch();
    
    $isAdmin = $user && $user['is_admin'] == 1;
    
    if (!$isAdmin) {
        header('Location: app.php');
        exit;
    }
    
    // Setup backup tables if they don't exist
    $pdo->exec("
        ALTER TABLE wave_assets 
        ADD COLUMN IF NOT EXISTS audio_backup_urls JSON,
        ADD COLUMN IF NOT EXISTS audio_size INT,
        ADD COLUMN IF NOT EXISTS backup_status VARCHAR(50) DEFAULT 'pending',
        ADD COLUMN IF NOT EXISTS last_backup_check TIMESTAMP NULL
    ");
    
    $pdo->exec("
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
            INDEX idx_backup_type (backup_type)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4
    ");
    
} catch (PDOException $e) {
    http_response_code(500);
    echo "Database error";
    exit;
}

// Get backup statistics
try {
    // Backup status overview
    $backupStats = $pdo->query("
        SELECT 
            backup_status,
            COUNT(*) as count,
            SUM(audio_size) as total_size
        FROM wave_assets 
        WHERE audio_url IS NOT NULL
        GROUP BY backup_status
    ")->fetchAll(PDO::FETCH_ASSOC);
    
    // Recent backup activity
    $recentBackups = $pdo->query("
        SELECT 
            w.id,
            w.title,
            w.original_name,
            w.backup_status,
            w.audio_size,
            w.last_backup_check,
            w.created_at,
            COUNT(ab.id) as backup_count
        FROM wave_assets w
        LEFT JOIN audio_backups ab ON w.id = ab.memory_id
        WHERE w.audio_url IS NOT NULL
        GROUP BY w.id
        ORDER BY w.created_at DESC
        LIMIT 20
    ")->fetchAll(PDO::FETCH_ASSOC);
    
    // Backup health summary
    $healthStats = $pdo->query("
        SELECT 
            COUNT(*) as total_memories,
            SUM(CASE WHEN backup_status = 'completed' THEN 1 ELSE 0 END) as backed_up,
            SUM(CASE WHEN backup_status = 'pending' THEN 1 ELSE 0 END) as pending,
            SUM(CASE WHEN backup_status = 'failed' THEN 1 ELSE 0 END) as failed,
            SUM(CASE WHEN last_backup_check < DATE_SUB(NOW(), INTERVAL 7 DAY) THEN 1 ELSE 0 END) as needs_check
        FROM wave_assets 
        WHERE audio_url IS NOT NULL
    ")->fetch(PDO::FETCH_ASSOC);
    
} catch (PDOException $e) {
    $backupStats = [];
    $recentBackups = [];
    $healthStats = [];
    $error = $e->getMessage();
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Audio Backup Management - MemoWindow Admin</title>
    <link rel="stylesheet" href="includes/admin_styles.css">
    <style>
        /* Page-specific styles */
        }
        .stat-card {
            background: white;
            border-radius: 12px;
            padding: 20px;
            border: 1px solid #e2e8f0;
            text-align: center;
        }
        .stat-number {
            font-size: 24px;
            font-weight: 700;
            margin-bottom: 8px;
        }
        .stat-number.success { color: #059669; }
        .stat-number.warning { color: #d97706; }
        .stat-number.danger { color: #dc2626; }
        .stat-label {
            color: #64748b;
            font-size: 14px;
            font-weight: 500;
        }
        .section {
            background: white;
            border-radius: 12px;
            padding: 24px;
            margin-bottom: 24px;
            border: 1px solid #e2e8f0;
        }
        .backup-item {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 16px;
            border: 1px solid #e2e8f0;
            border-radius: 8px;
            margin-bottom: 12px;
            background: #fafbfc;
        }
        .backup-status {
            padding: 4px 12px;
            border-radius: 20px;
            font-size: 12px;
            font-weight: 500;
            text-transform: uppercase;
        }
        .status-completed { background: #d1fae5; color: #065f46; }
        .status-pending { background: #fef3c7; color: #92400e; }
        .status-failed { background: #fee2e2; color: #991b1b; }
        .btn {
            padding: 8px 16px;
            border: none;
            border-radius: 6px;
            cursor: pointer;
            font-size: 12px;
            font-weight: 500;
            text-decoration: none;
            display: inline-block;
        }
        .btn-primary { background: #2563eb; color: white; }
        .btn-success { background: #059669; color: white; }
        .btn-warning { background: #d97706; color: white; }
        .btn-danger { background: #dc2626; color: white; }
        .actions-bar {
            display: flex;
            gap: 12px;
            margin-bottom: 24px;
            flex-wrap: wrap;
        }
    </style>
</head>
<body>
    <div class="admin-container">
        <div class="admin-header">
            <h1>🔒 Audio Backup Management</h1>
            <p>Protect precious voice recordings with redundant backup system</p>
        </div>
        
        <?php include 'includes/admin_navigation.php'; ?>

        <div class="admin-content">
            <!-- Backup Health Overview -->
            <div class="admin-stats-grid">
                <div class="admin-stat-card">
                    <div class="number" style="color: #28a745;"><?php echo $healthStats['backed_up'] ?? 0; ?></div>
                    <p>Backed Up</p>
                </div>
                <div class="admin-stat-card">
                    <div class="number" style="color: #ffc107;"><?php echo $healthStats['pending'] ?? 0; ?></div>
                    <p>Pending Backup</p>
                </div>
                <div class="admin-stat-card">
                    <div class="number" style="color: #dc3545;"><?php echo $healthStats['failed'] ?? 0; ?></div>
                    <p>Failed Backups</p>
                </div>
                <div class="admin-stat-card">
                    <div class="number" style="color: #ffc107;"><?php echo $healthStats['needs_check'] ?? 0; ?></div>
                    <p>Needs Verification</p>
                </div>
            </div>

        <!-- Action Buttons -->
        <div class="actions-bar">
            <button onclick="createAllBackups()" class="btn btn-success">
                🔄 Create Missing Backups
            </button>
            <button onclick="verifyAllBackups()" class="btn btn-primary">
                ✅ Verify All Backups
            </button>
            <button onclick="restoreAllBackups()" class="btn btn-warning">
                🔄 Restore All to Firebase
            </button>
            <button onclick="exportBackupReport()" class="btn btn-warning">
                📊 Export Backup Report
            </button>
            <button onclick="showBackupSettings()" class="btn btn-primary">
                ⚙️ Backup Settings
            </button>
        </div>

        <!-- Recent Backup Activity -->
        <div class="section">
            <h2 style="margin-bottom: 20px;">📋 Memory Backup Status</h2>
            <?php if (isset($error)): ?>
                <div style="color: #dc2626; text-align: center; padding: 20px;">
                    Error: <?php echo htmlspecialchars($error); ?>
                </div>
            <?php elseif (empty($recentBackups)): ?>
                <div style="color: #64748b; text-align: center; padding: 20px;">
                    No audio files found. Backups will appear here after users create memories with audio.
                </div>
            <?php else: ?>
                <?php foreach ($recentBackups as $memory): ?>
                <div class="backup-item">
                    <div>
                        <strong><?php echo htmlspecialchars($memory['title'] ?: 'Untitled'); ?></strong>
                        <div style="font-size: 12px; color: #64748b; margin-top: 4px;">
                            <?php echo htmlspecialchars($memory['original_name']); ?> • 
                            <?php echo $memory['audio_size'] ? number_format($memory['audio_size'] / 1024 / 1024, 1) . ' MB' : 'Unknown size'; ?> •
                            <?php echo $memory['backup_count']; ?> backup(s) •
                            Created: <?php echo date('M j, Y', strtotime($memory['created_at'])); ?>
                        </div>
                        <?php if ($memory['last_backup_check']): ?>
                        <div style="font-size: 11px; color: #64748b; margin-top: 2px;">
                            Last checked: <?php echo date('M j, g:i A', strtotime($memory['last_backup_check'])); ?>
                        </div>
                        <?php endif; ?>
                    </div>
                    <div style="display: flex; align-items: center; gap: 12px;">
                        <span class="backup-status status-<?php echo strtolower($memory['backup_status']); ?>">
                            <?php echo ucfirst($memory['backup_status']); ?>
                        </span>
                        <div style="display: flex; gap: 4px;">
                            <button onclick="createBackup(<?php echo $memory['id']; ?>)" class="btn btn-success" title="Create Backup">🔄</button>
                            <button onclick="verifyBackup(<?php echo $memory['id']; ?>)" class="btn btn-primary" title="Verify Backup">✅</button>
                            <button onclick="restoreBackup(<?php echo $memory['id']; ?>)" class="btn btn-warning" title="Restore to Firebase">🔄</button>
                            <button onclick="viewBackupDetails(<?php echo $memory['id']; ?>)" class="btn btn-warning" title="View Details">📋</button>
                        </div>
                    </div>
                </div>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>
    </div>

    <!-- Backup Details Modal -->
    <div id="backupModal" style="display: none; position: fixed; top: 0; left: 0; width: 100%; height: 100%; background: rgba(0,0,0,0.6); z-index: 1000; align-items: center; justify-content: center;">
        <div style="background: white; border-radius: 12px; padding: 24px; max-width: 600px; width: 90%; max-height: 80vh; overflow-y: auto;">
            <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px;">
                <h3 style="margin: 0;">🔒 Backup Details</h3>
                <button onclick="closeBackupModal()" style="background: none; border: none; font-size: 24px; cursor: pointer;">&times;</button>
            </div>
            <div id="backupDetails">Loading...</div>
        </div>
    </div>

    <script>
        async function createBackup(memoryId) {
            try {
                const response = await fetch('backup_audio.php', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                    body: `action=create_backup&memory_id=${memoryId}`
                });
                
                const result = await response.json();
                
                if (result.success) {
                    alert(`✅ Backup created successfully!\nBackups: ${result.backups_created}\nSize: ${(result.total_size / 1024 / 1024).toFixed(1)} MB`);
                    location.reload();
                } else {
                    alert('❌ Backup failed: ' + result.error);
                }
            } catch (error) {
                alert('❌ Error creating backup: ' + error.message);
            }
        }

        async function verifyBackup(memoryId) {
            try {
                const response = await fetch('backup_audio.php', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                    body: `action=verify_backup&memory_id=${memoryId}`
                });
                
                const result = await response.json();
                
                if (result.success) {
                    const accessible = result.backups.filter(b => b.accessible).length;
                    const total = result.backups.length;
                    alert(`✅ Backup verification complete!\n${accessible}/${total} backups accessible`);
                    location.reload();
                } else {
                    alert('❌ Verification failed: ' + result.error);
                }
            } catch (error) {
                alert('❌ Error verifying backup: ' + error.message);
            }
        }

        async function viewBackupDetails(memoryId) {
            try {
                const response = await fetch(`get_backup_details.php?memory_id=${memoryId}`);
                const details = await response.text();
                
                document.getElementById('backupDetails').innerHTML = details;
                document.getElementById('backupModal').style.display = 'flex';
            } catch (error) {
                alert('❌ Error loading details: ' + error.message);
            }
        }

        function closeBackupModal() {
            document.getElementById('backupModal').style.display = 'none';
        }

        async function createAllBackups() {
            if (!confirm('Create backups for all memories without backups?\n\nThis may take several minutes for large numbers of files.')) {
                return;
            }
            
            try {
                const response = await fetch('backup_audio.php', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                    body: 'action=create_all_backups'
                });
                
                const result = await response.json();
                alert(result.message || 'Backup process completed');
                location.reload();
            } catch (error) {
                alert('❌ Error: ' + error.message);
            }
        }

        async function verifyAllBackups() {
            if (!confirm('Verify all backup files?\n\nThis will check if all backup URLs are accessible.')) {
                return;
            }
            
            try {
                const response = await fetch('backup_audio.php', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                    body: 'action=verify_all_backups'
                });
                
                const result = await response.json();
                alert(result.message || 'Verification completed');
                location.reload();
            } catch (error) {
                alert('❌ Error: ' + error.message);
            }
        }

        function exportBackupReport() {
            window.open(`export_data.php?type=backups`, '_blank');
        }

        async function restoreBackup(memoryId) {
            if (!confirm('Restore this memory from local backup to Firebase Storage?\n\nThis will upload the backup file to Firebase and update the memory record.')) {
                return;
            }
            
            try {
                const response = await fetch('backup_audio.php', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                    body: `action=restore_backup&memory_id=${memoryId}`
                });
                
                const result = await response.json();
                
                if (result.success) {
                    alert(`✅ Memory restored successfully!\nNew Firebase URL: ${result.new_url}\nFile size: ${(result.file_size / 1024 / 1024).toFixed(1)} MB`);
                    location.reload();
                } else {
                    alert('❌ Restore failed: ' + result.error);
                }
            } catch (error) {
                alert('❌ Error restoring backup: ' + error.message);
            }
        }

        async function restoreAllBackups() {
            if (!confirm('Restore all local backups to Firebase Storage?\n\nThis will process all memories with local backups and upload them to Firebase. This may take several minutes.')) {
                return;
            }
            
            try {
                const response = await fetch('backup_audio.php', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                    body: 'action=restore_all_backups'
                });
                
                const result = await response.json();
                alert(result.message || 'Restore process completed');
                location.reload();
            } catch (error) {
                alert('❌ Error: ' + error.message);
            }
        }

        function showBackupSettings() {
            alert('Backup settings:\n\n✅ Automatic backup creation\n✅ Multiple Firebase Storage locations\n✅ Checksum verification\n✅ Weekly health checks\n✅ Local to Firebase restore\n\nSettings are configured in backup_audio.php');
        }

        // Auto-refresh every 2 minutes
        setInterval(() => {
            location.reload();
        }, 120000);
    </script>
        </div>
    </div>
</body>
</html>

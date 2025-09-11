<?php
require_once 'config.php';
require_once 'VoiceCloneSettings.php';
require_once 'auth_check.php';

// Require admin authentication using Firebase
$userFirebaseUID = requireAdmin();

$settings = new VoiceCloneSettings();
$message = '';
$error = '';

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        $newSettings = [
            'voice_clone_enabled' => isset($_POST['voice_clone_enabled']) ? '1' : '0',
            'voice_clone_monthly_limit' => intval($_POST['voice_clone_monthly_limit']),
            'voice_clone_requires_subscription' => isset($_POST['voice_clone_requires_subscription']) ? '1' : '0'
        ];
        
        $settings->updateSettings($newSettings);
        $message = 'Voice clone settings updated successfully!';
    } catch (Exception $e) {
        $error = 'Error updating settings: ' . $e->getMessage();
    }
}

$currentSettings = $settings->getAllSettings();
$usageStats = $settings->getUsageStats();
$topUsers = $settings->getTopUsers();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Voice Clone Settings - Admin</title>
    <link rel="stylesheet" href="includes/admin_styles.css">
    <style>
        /* Page-specific styles */
        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 20px;
            margin-bottom: 20px;
        }
        
        .stat-card {
            background: #f8f9fa;
            padding: 15px;
            border-radius: 8px;
            text-align: center;
        }
        
        .stat-number {
            font-size: 24px;
            font-weight: bold;
            color: #667eea;
            margin-bottom: 5px;
        }
        
        .stat-label {
            font-size: 12px;
            color: #666;
        }
        
        .status-enabled {
            color: #28a745;
            font-weight: bold;
        }
        
        .status-disabled {
            color: #dc3545;
            font-weight: bold;
        }
    </style>
</head>
<body>
    <div class="admin-container">
        <div class="admin-header">
            <h1>🎤 Voice Clone Settings</h1>
            <p>Manage voice cloning feature settings and monitor usage</p>
        </div>
        
        <?php include 'includes/admin_navigation.php'; ?>

        <div class="admin-content">
            <?php if ($message): ?>
                <div class="admin-alert admin-alert-success"><?php echo htmlspecialchars($message); ?></div>
            <?php endif; ?>

            <?php if ($error): ?>
                <div class="admin-alert admin-alert-error"><?php echo htmlspecialchars($error); ?></div>
            <?php endif; ?>

            <div class="admin-card">
            <h2>Feature Settings</h2>
            <form method="POST">
                <div class="form-group">
                    <label>
                        <input type="checkbox" name="voice_clone_enabled" <?php echo $currentSettings['voice_clone_enabled'] === '1' ? 'checked' : ''; ?>>
                        Enable Voice Cloning
                    </label>
                    <small>When disabled, users cannot create voice clones</small>
                </div>

                <div class="form-group">
                    <label for="voice_clone_monthly_limit">Monthly Limit per User:</label>
                    <input type="number" id="voice_clone_monthly_limit" name="voice_clone_monthly_limit" 
                           value="<?php echo htmlspecialchars($currentSettings['voice_clone_monthly_limit']); ?>" 
                           min="1" max="100">
                    <small>Maximum number of voice clones a user can create per month</small>
                </div>

                <div class="form-group">
                    <label>
                        <input type="checkbox" name="voice_clone_requires_subscription" <?php echo $currentSettings['voice_clone_requires_subscription'] === '1' ? 'checked' : ''; ?>>
                        Requires Subscription
                    </label>
                    <small>Only users with active subscriptions can use voice cloning</small>
                </div>

                <button type="submit" class="btn">Save Settings</button>
            </form>
        </div>

        <div class="card">
            <h2>Current Status</h2>
            <div class="stats-grid">
                <div class="stat-card">
                    <div class="stat-number">
                        <?php echo $currentSettings['voice_clone_enabled'] === '1' ? 'ENABLED' : 'DISABLED'; ?>
                    </div>
                    <div class="stat-label">Feature Status</div>
                </div>
                <div class="stat-card">
                    <div class="stat-number"><?php echo htmlspecialchars($currentSettings['voice_clone_monthly_limit']); ?></div>
                    <div class="stat-label">Monthly Limit</div>
                </div>
                <div class="stat-card">
                    <div class="stat-number"><?php echo $currentSettings['voice_clone_requires_subscription'] === '1' ? 'YES' : 'NO'; ?></div>
                    <div class="stat-label">Requires Subscription</div>
                </div>
            </div>
        </div>

        <div class="card">
            <h2>Usage Statistics (<?php echo date('F Y'); ?>)</h2>
            <div class="stats-grid">
                <div class="stat-card">
                    <div class="stat-number"><?php echo $usageStats['total_users'] ?? 0; ?></div>
                    <div class="stat-label">Active Users</div>
                </div>
                <div class="stat-card">
                    <div class="stat-number"><?php echo $usageStats['total_clones'] ?? 0; ?></div>
                    <div class="stat-label">Total Clones</div>
                </div>
                <div class="stat-card">
                    <div class="stat-number"><?php echo round($usageStats['avg_clones_per_user'] ?? 0, 1); ?></div>
                    <div class="stat-label">Avg per User</div>
                </div>
            </div>
        </div>

        <?php if (!empty($topUsers)): ?>
        <div class="card">
            <h2>Top Users This Month</h2>
            <table>
                <thead>
                    <tr>
                        <th>User ID</th>
                        <th>Clones Created</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($topUsers as $user): ?>
                    <tr>
                        <td><?php echo htmlspecialchars($user['user_id']); ?></td>
                        <td><?php echo $user['clone_count']; ?></td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
        <?php endif; ?>

        <div class="card">
            <h2>Quick Actions</h2>
            <p>
                <a href="admin.php?user_id=<?php echo urlencode($userFirebaseUID); ?>" class="btn">← Back to Admin Dashboard</a>
            </p>
        </div>
    </div>
</body>
</html>

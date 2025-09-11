<?php
require_once 'config.php';
require_once 'VoiceCloneSettings.php';
require_once 'auth_check.php';

// Require admin authentication
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
            margin-bottom: 30px;
        }
        
        .stat-card {
            background: linear-gradient(135deg, #f8f9fa 0%, #e9ecef 100%);
            padding: 20px;
            border-radius: 12px;
            text-align: center;
            border: 1px solid #dee2e6;
            transition: transform 0.2s ease, box-shadow 0.2s ease;
        }
        
        .stat-card:hover {
            transform: translateY(-2px);
            box-shadow: 0 8px 25px rgba(0,0,0,0.1);
        }
        
        .stat-number {
            font-size: 28px;
            font-weight: 700;
            color: #667eea;
            margin-bottom: 8px;
            text-shadow: 0 1px 2px rgba(0,0,0,0.1);
        }
        
        .stat-label {
            font-size: 14px;
            color: #6c757d;
            font-weight: 500;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }
        
        .status-enabled {
            color: #28a745;
            font-weight: bold;
            background: linear-gradient(135deg, #d4edda 0%, #c3e6cb 100%);
            padding: 8px 16px;
            border-radius: 20px;
            display: inline-block;
        }
        
        .status-disabled {
            color: #dc3545;
            font-weight: bold;
            background: linear-gradient(135deg, #f8d7da 0%, #f5c6cb 100%);
            padding: 8px 16px;
            border-radius: 20px;
            display: inline-block;
        }

        /* Enhanced form styling */
        .form-group {
            margin-bottom: 25px;
            padding: 20px;
            background: #f8f9fa;
            border-radius: 12px;
            border: 1px solid #e9ecef;
            transition: all 0.2s ease;
        }

        .form-group:hover {
            background: #ffffff;
            border-color: #667eea;
            box-shadow: 0 4px 12px rgba(102, 126, 234, 0.1);
        }

        .form-group label {
            display: flex;
            align-items: center;
            font-weight: 600;
            color: #495057;
            margin-bottom: 8px;
            cursor: pointer;
        }

        .form-group input[type="checkbox"] {
            margin-right: 12px;
            width: 18px;
            height: 18px;
            accent-color: #667eea;
        }

        .form-group input[type="number"] {
            width: 100%;
            padding: 12px 16px;
            border: 2px solid #e9ecef;
            border-radius: 8px;
            font-size: 16px;
            transition: border-color 0.2s ease;
            background: white;
        }

        .form-group input[type="number"]:focus {
            outline: none;
            border-color: #667eea;
            box-shadow: 0 0 0 3px rgba(102, 126, 234, 0.1);
        }

        .form-group small {
            display: block;
            color: #6c757d;
            font-size: 14px;
            margin-top: 8px;
            font-style: italic;
        }

        .btn {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            border: none;
            padding: 14px 28px;
            border-radius: 8px;
            font-size: 16px;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.2s ease;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }

        .btn:hover {
            transform: translateY(-2px);
            box-shadow: 0 8px 25px rgba(102, 126, 234, 0.3);
        }

        .btn:active {
            transform: translateY(0);
        }

        /* Card styling */
        .card {
            background: white;
            border-radius: 12px;
            padding: 25px;
            margin-bottom: 25px;
            border: 1px solid #e9ecef;
            box-shadow: 0 4px 12px rgba(0,0,0,0.05);
        }

        .card h2 {
            color: #495057;
            margin-bottom: 20px;
            font-size: 24px;
            font-weight: 700;
            border-bottom: 2px solid #e9ecef;
            padding-bottom: 10px;
        }

        /* Usage stats table */
        .usage-table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 20px;
        }

        .usage-table th,
        .usage-table td {
            padding: 12px;
            text-align: left;
            border-bottom: 1px solid #e9ecef;
        }

        .usage-table th {
            background: #f8f9fa;
            font-weight: 600;
            color: #495057;
        }

        .usage-table tr:hover {
            background: #f8f9fa;
        }

        /* Responsive design */
        @media (max-width: 768px) {
            .stats-grid {
                grid-template-columns: 1fr;
            }
            
            .form-group {
                padding: 15px;
            }
            
            .stat-number {
                font-size: 24px;
            }
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
            <h2 style="margin-bottom: 20px;">Feature Settings</h2>
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
            <h2 style="margin-bottom: 20px;">Current Status</h2>
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
            <h2 style="margin-bottom: 20px;">Usage Statistics (<?php echo date('F Y'); ?>)</h2>
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
            <h2 style="margin-bottom: 20px;">Top Users This Month</h2>
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

       
    </div>
</body>
</html>

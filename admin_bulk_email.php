<?php
/**
 * Admin Bulk Email Sender
 * Allows admins to send bulk emails to filtered users
 */

require_once 'config.php';
require_once 'unified_auth.php';
require_once 'EmailNotification.php';

// Check if user is admin
$currentUser = getCurrentUser();
if (!$currentUser) {
    header('Location: login.php');
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
        header('Location: app.php');
        exit;
    }
    
} catch (Exception $e) {
    error_log("Admin check error: " . $e->getMessage());
    header('Location: app.php');
    exit;
}

// Handle form submissions
$success_message = '';
$error_message = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        $action = $_POST['action'] ?? '';
        
        if ($action === 'send_bulk_email') {
            $email_type = $_POST['email_type'] ?? '';
            $user_filter = $_POST['user_filter'] ?? 'all';
            $custom_subject = $_POST['custom_subject'] ?? '';
            $custom_message = $_POST['custom_message'] ?? '';
            
            // Get users based on filter
            $users = getUsersByFilter($user_filter);
            
            if (empty($users)) {
                $error_message = "No users found matching the selected filter.";
            } else {
                // Send emails
                $emailNotification = new EmailNotification();
                $sent_count = 0;
                $failed_count = 0;
                
                foreach ($users as $user) {
                    $result = sendBulkEmail($emailNotification, $email_type, $user, [
                        'custom_subject' => $custom_subject,
                        'custom_message' => $custom_message
                    ]);
                    
                    if ($result['success']) {
                        $sent_count++;
                    } else {
                        $failed_count++;
                    }
                }
                
                $success_message = "Bulk email sent successfully! Sent: {$sent_count}, Failed: {$failed_count}";
            }
        }
        
    } catch (Exception $e) {
        $error_message = "Error: " . $e->getMessage();
    }
}

// Get user statistics
try {
    $user_stats = getUserStatistics();
} catch (Exception $e) {
    $user_stats = [
        'total_users' => 0,
        'active_users' => 0,
        'subscription_users' => 0,
        'recent_users' => 0
    ];
}

// Get email templates
try {
    $stmt = $pdo->prepare("SELECT template_key, template_name FROM email_templates WHERE is_active = 1 ORDER BY template_name");
    $stmt->execute();
    $email_templates = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (Exception $e) {
    $email_templates = [];
}

/**
 * Get users based on filter criteria
 */
function getUsersByFilter($filter) {
    global $pdo;
    
    $sql = "SELECT DISTINCT u.firebase_uid, u.email, u.display_name, u.created_at, u.last_login_at 
            FROM users u";
    
    switch ($filter) {
        case 'all':
            // All users
            break;
            
        case 'active':
            // Users who logged in within last 30 days
            $sql .= " WHERE u.last_login_at >= DATE_SUB(NOW(), INTERVAL 30 DAY)";
            break;
            
        case 'subscription':
            // Users with active subscriptions
            $sql .= " INNER JOIN user_subscriptions us ON u.firebase_uid = us.user_id 
                     WHERE us.status = 'active'";
            break;
            
        case 'recent':
            // Users who joined within last 7 days
            $sql .= " WHERE u.created_at >= DATE_SUB(NOW(), INTERVAL 7 DAY)";
            break;
            
        case 'inactive':
            // Users who haven't logged in for 30+ days
            $sql .= " WHERE u.last_login_at < DATE_SUB(NOW(), INTERVAL 30 DAY) OR u.last_login_at IS NULL";
            break;
            
        case 'premium':
            // Users with premium subscriptions
            $sql .= " INNER JOIN user_subscriptions us ON u.firebase_uid = us.user_id 
                     INNER JOIN subscription_packages sp ON us.package_id = sp.id
                     WHERE us.status = 'active' AND sp.name LIKE '%Premium%'";
            break;
    }
    
    $sql .= " ORDER BY u.created_at DESC";
    
    $stmt = $pdo->prepare($sql);
    $stmt->execute();
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

/**
 * Get user statistics
 */
function getUserStatistics() {
    global $pdo;
    
    $stats = [];
    
    // Total users
    $stmt = $pdo->prepare("SELECT COUNT(*) as count FROM users");
    $stmt->execute();
    $stats['total_users'] = $stmt->fetch()['count'];
    
    // Active users (logged in within 30 days)
    $stmt = $pdo->prepare("SELECT COUNT(*) as count FROM users WHERE last_login_at >= DATE_SUB(NOW(), INTERVAL 30 DAY)");
    $stmt->execute();
    $stats['active_users'] = $stmt->fetch()['count'];
    
    // Subscription users
    $stmt = $pdo->prepare("SELECT COUNT(DISTINCT user_id) as count FROM user_subscriptions WHERE status = 'active'");
    $stmt->execute();
    $stats['subscription_users'] = $stmt->fetch()['count'];
    
    // Recent users (joined within 7 days)
    $stmt = $pdo->prepare("SELECT COUNT(*) as count FROM users WHERE created_at >= DATE_SUB(NOW(), INTERVAL 7 DAY)");
    $stmt->execute();
    $stats['recent_users'] = $stmt->fetch()['count'];
    
    return $stats;
}

/**
 * Send bulk email based on type
 */
function sendBulkEmail($emailNotification, $email_type, $user, $custom_data = []) {
    $user_email = $user['email'];
    $user_name = $user['display_name'] ?: 'User';
    
    switch ($email_type) {
        case 'maintenance_notice':
            return $emailNotification->sendMaintenanceNotice($user_email, $user_name, [
                'date' => $custom_data['maintenance_date'] ?? date('F j, Y', strtotime('+1 day')),
                'time' => $custom_data['maintenance_time'] ?? '2:00 AM - 4:00 AM EST',
                'duration' => $custom_data['maintenance_duration'] ?? '2 hours',
                'reason' => $custom_data['maintenance_reason'] ?? 'System maintenance and updates',
                'services' => $custom_data['affected_services'] ?? 'All services may be temporarily unavailable'
            ]);
            
        case 'feature_announcement':
            return $emailNotification->sendFeatureAnnouncement($user_email, $user_name, [
                'feature_name' => $custom_data['feature_name'] ?? 'New Feature',
                'description' => $custom_data['feature_description'] ?? 'Check out this exciting new feature!',
                'benefits' => $custom_data['feature_benefits'] ?? 'Enhanced user experience',
                'how_to_access' => $custom_data['how_to_access'] ?? 'Available in your dashboard',
                'launch_date' => $custom_data['launch_date'] ?? date('F j, Y')
            ]);
            
        case 'custom':
            // Send custom email
            $subject = $custom_data['custom_subject'] ?: 'MemoWindow Update';
            $html_body = $this->getBaseTemplate();
            $html_body = str_replace('{{content}}', nl2br(htmlspecialchars($custom_data['custom_message'])), $html_body);
            $text_body = $custom_data['custom_message'];
            
            return $emailNotification->sendEmail($user_email, $subject, $html_body, $text_body);
            
        default:
            return ['success' => false, 'message' => 'Unknown email type'];
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Bulk Email - MemoWindow Admin</title>
    <link rel="stylesheet" href="includes/unified.css?v=<?php echo time(); ?>">
    <link rel="stylesheet" href="includes/admin_styles.css?v=<?php echo time(); ?>">
    <style>
        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 20px;
            margin-bottom: 30px;
        }
        
        .stat-card {
            background: white;
            padding: 20px;
            border-radius: 8px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
            text-align: center;
        }
        
        .stat-number {
            font-size: 2em;
            font-weight: bold;
            color: #667eea;
            margin-bottom: 5px;
        }
        
        .stat-label {
            color: #666;
            font-size: 14px;
        }
        
        .form-section {
            background: white;
            padding: 25px;
            border-radius: 8px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
            margin-bottom: 20px;
        }
        
        .form-section h3 {
            margin-top: 0;
            color: #333;
            border-bottom: 2px solid #667eea;
            padding-bottom: 10px;
        }
        
        .form-row {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 20px;
            margin-bottom: 20px;
        }
        
        .form-group {
            margin-bottom: 20px;
        }
        
        .form-group label {
            display: block;
            margin-bottom: 8px;
            font-weight: 500;
            color: #333;
        }
        
        .form-group select,
        .form-group input,
        .form-group textarea {
            width: 100%;
            padding: 12px;
            border: 1px solid #ddd;
            border-radius: 6px;
            font-size: 14px;
        }
        
        .form-group textarea {
            min-height: 120px;
            resize: vertical;
        }
        
        .filter-options {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 15px;
            margin-top: 15px;
        }
        
        .filter-option {
            background: #f8f9fa;
            padding: 15px;
            border-radius: 6px;
            border: 2px solid transparent;
            cursor: pointer;
            transition: all 0.3s ease;
        }
        
        .filter-option:hover {
            border-color: #667eea;
            background: #e7f3ff;
        }
        
        .filter-option.selected {
            border-color: #667eea;
            background: #e7f3ff;
        }
        
        .filter-option input[type="radio"] {
            margin-right: 10px;
        }
        
        .filter-option h4 {
            margin: 0 0 5px 0;
            color: #333;
        }
        
        .filter-option p {
            margin: 0;
            color: #666;
            font-size: 12px;
        }
        
        .btn-send {
            background: #28a745;
            color: white;
            padding: 15px 30px;
            border: none;
            border-radius: 6px;
            font-size: 16px;
            font-weight: bold;
            cursor: pointer;
            transition: background 0.3s ease;
        }
        
        .btn-send:hover {
            background: #218838;
        }
        
        .btn-send:disabled {
            background: #6c757d;
            cursor: not-allowed;
        }
        
        .preview-section {
            background: #f8f9fa;
            padding: 20px;
            border-radius: 6px;
            margin-top: 20px;
        }
        
        .preview-section h4 {
            margin-top: 0;
            color: #333;
        }
        
        .preview-content {
            background: white;
            padding: 15px;
            border-radius: 4px;
            border: 1px solid #ddd;
            max-height: 300px;
            overflow-y: auto;
        }
    </style>
</head>
<body>
    <div class="admin-container">
        <div class="admin-header">
            <h1>📧 Bulk Email Sender</h1>
            <p>Send emails to filtered users or all users</p>
        </div>
        
        <?php include 'includes/admin_navigation.php'; ?>

        <div class="admin-content">
            <?php if ($success_message): ?>
                <div class="alert alert-success">
                    <?php echo htmlspecialchars($success_message); ?>
                </div>
            <?php endif; ?>
            
            <?php if ($error_message): ?>
                <div class="alert alert-danger">
                    <?php echo htmlspecialchars($error_message); ?>
                </div>
            <?php endif; ?>
            
            <!-- User Statistics -->
            <div class="stats-grid">
                <div class="stat-card">
                    <div class="stat-number"><?php echo number_format($user_stats['total_users']); ?></div>
                    <div class="stat-label">Total Users</div>
                </div>
                <div class="stat-card">
                    <div class="stat-number"><?php echo number_format($user_stats['active_users']); ?></div>
                    <div class="stat-label">Active Users (30 days)</div>
                </div>
                <div class="stat-card">
                    <div class="stat-number"><?php echo number_format($user_stats['subscription_users']); ?></div>
                    <div class="stat-label">Subscription Users</div>
                </div>
                <div class="stat-card">
                    <div class="stat-number"><?php echo number_format($user_stats['recent_users']); ?></div>
                    <div class="stat-label">Recent Users (7 days)</div>
                </div>
            </div>
            
            <!-- Bulk Email Form -->
            <form method="POST" id="bulkEmailForm">
                <input type="hidden" name="action" value="send_bulk_email">
                
                <div class="form-section">
                    <h3>📧 Email Type</h3>
                    <div class="form-group">
                        <label for="email_type">Select Email Type:</label>
                        <select name="email_type" id="email_type" required>
                            <option value="">Choose an email type...</option>
                            <?php foreach ($email_templates as $template): ?>
                                <option value="<?php echo htmlspecialchars($template['template_key']); ?>">
                                    <?php echo htmlspecialchars($template['template_name']); ?>
                                </option>
                            <?php endforeach; ?>
                            <option value="custom">Custom Email</option>
                        </select>
                    </div>
                </div>
                
                <div class="form-section">
                    <h3>👥 Target Users</h3>
                    <div class="filter-options">
                        <label class="filter-option">
                            <input type="radio" name="user_filter" value="all" checked>
                            <h4>All Users</h4>
                            <p><?php echo number_format($user_stats['total_users']); ?> total users</p>
                        </label>
                        
                        <label class="filter-option">
                            <input type="radio" name="user_filter" value="active">
                            <h4>Active Users</h4>
                            <p><?php echo number_format($user_stats['active_users']); ?> users (logged in 30 days)</p>
                        </label>
                        
                        <label class="filter-option">
                            <input type="radio" name="user_filter" value="subscription">
                            <h4>Subscription Users</h4>
                            <p><?php echo number_format($user_stats['subscription_users']); ?> active subscribers</p>
                        </label>
                        
                        <label class="filter-option">
                            <input type="radio" name="user_filter" value="recent">
                            <h4>Recent Users</h4>
                            <p><?php echo number_format($user_stats['recent_users']); ?> new users (7 days)</p>
                        </label>
                        
                        <label class="filter-option">
                            <input type="radio" name="user_filter" value="inactive">
                            <h4>Inactive Users</h4>
                            <p>Users who haven't logged in for 30+ days</p>
                        </label>
                        
                        <label class="filter-option">
                            <input type="radio" name="user_filter" value="premium">
                            <h4>Premium Users</h4>
                            <p>Users with premium subscriptions</p>
                        </label>
                    </div>
                </div>
                
                <div class="form-section" id="customEmailSection" style="display: none;">
                    <h3>✏️ Custom Email Content</h3>
                    <div class="form-group">
                        <label for="custom_subject">Email Subject:</label>
                        <input type="text" name="custom_subject" id="custom_subject" placeholder="Enter email subject...">
                    </div>
                    <div class="form-group">
                        <label for="custom_message">Email Message:</label>
                        <textarea name="custom_message" id="custom_message" placeholder="Enter your email message here..."></textarea>
                    </div>
                </div>
                
                <div class="form-section">
                    <h3>🚀 Send Email</h3>
                    <div class="preview-section">
                        <h4>Preview</h4>
                        <div class="preview-content" id="previewContent">
                            <p>Select an email type and user filter to see a preview...</p>
                        </div>
                    </div>
                    
                    <button type="submit" class="btn-send" id="sendButton" disabled>
                        📧 Send Bulk Email
                    </button>
                </div>
            </form>
        </div>
    </div>
    
    <script>
        // Handle email type change
        document.getElementById('email_type').addEventListener('change', function() {
            const customSection = document.getElementById('customEmailSection');
            const sendButton = document.getElementById('sendButton');
            
            if (this.value === 'custom') {
                customSection.style.display = 'block';
            } else {
                customSection.style.display = 'none';
            }
            
            updatePreview();
            updateSendButton();
        });
        
        // Handle user filter change
        document.querySelectorAll('input[name="user_filter"]').forEach(radio => {
            radio.addEventListener('change', function() {
                updatePreview();
                updateSendButton();
            });
        });
        
        // Handle custom content changes
        document.getElementById('custom_subject').addEventListener('input', updatePreview);
        document.getElementById('custom_message').addEventListener('input', updatePreview);
        
        function updatePreview() {
            const emailType = document.getElementById('email_type').value;
            const userFilter = document.querySelector('input[name="user_filter"]:checked').value;
            const previewContent = document.getElementById('previewContent');
            
            if (!emailType) {
                previewContent.innerHTML = '<p>Select an email type and user filter to see a preview...</p>';
                return;
            }
            
            let preview = '<h4>Email Preview</h4>';
            preview += '<p><strong>Email Type:</strong> ' + getEmailTypeName(emailType) + '</p>';
            preview += '<p><strong>Target Users:</strong> ' + getUserFilterName(userFilter) + '</p>';
            
            if (emailType === 'custom') {
                const subject = document.getElementById('custom_subject').value || 'Custom Email';
                const message = document.getElementById('custom_message').value || 'Your custom message here...';
                preview += '<p><strong>Subject:</strong> ' + subject + '</p>';
                preview += '<p><strong>Message:</strong></p>';
                preview += '<div style="background: #f8f9fa; padding: 10px; border-radius: 4px; margin-top: 10px;">' + 
                          message.replace(/\n/g, '<br>') + '</div>';
            } else {
                preview += '<p><strong>Template:</strong> Professional email template with MemoWindow branding</p>';
            }
            
            previewContent.innerHTML = preview;
        }
        
        function updateSendButton() {
            const emailType = document.getElementById('email_type').value;
            const sendButton = document.getElementById('sendButton');
            
            if (emailType) {
                sendButton.disabled = false;
                sendButton.textContent = '📧 Send Bulk Email';
            } else {
                sendButton.disabled = true;
                sendButton.textContent = '📧 Select Email Type First';
            }
        }
        
        function getEmailTypeName(type) {
            const names = {
                'maintenance_notice': 'Maintenance Notice',
                'feature_announcement': 'Feature Announcement',
                'memory_scanned': 'Memory Scanned',
                'payment_confirmation': 'Payment Confirmation',
                'subscription_confirmation': 'Subscription Confirmation',
                'subscription_cancellation': 'Subscription Cancellation',
                'order_confirmation': 'Order Confirmation',
                'custom': 'Custom Email'
            };
            return names[type] || type;
        }
        
        function getUserFilterName(filter) {
            const names = {
                'all': 'All Users',
                'active': 'Active Users (logged in 30 days)',
                'subscription': 'Subscription Users',
                'recent': 'Recent Users (joined 7 days)',
                'inactive': 'Inactive Users (30+ days)',
                'premium': 'Premium Users'
            };
            return names[filter] || filter;
        }
        
        // Form submission confirmation
        document.getElementById('bulkEmailForm').addEventListener('submit', function(e) {
            const emailType = document.getElementById('email_type').value;
            const userFilter = document.querySelector('input[name="user_filter"]:checked').value;
            
            if (!confirm('Are you sure you want to send this bulk email? This action cannot be undone.')) {
                e.preventDefault();
                return;
            }
            
            // Show loading state
            const sendButton = document.getElementById('sendButton');
            sendButton.disabled = true;
            sendButton.textContent = '📧 Sending...';
        });
    </script>
</body>
</html>

<?php
/**
 * Admin Email Templates Editor
 * Allows admins to edit email templates from the admin panel
 */

require_once 'config.php';
require_once 'unified_auth.php';

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
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        $pdo = new PDO("mysql:host=" . DB_HOST . ";dbname=" . DB_NAME . ";charset=utf8mb4", DB_USER, DB_PASS, [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        ]);
        
        if (isset($_POST['action'])) {
            switch ($_POST['action']) {
                case 'update_template':
                    $stmt = $pdo->prepare("
                        UPDATE email_templates 
                        SET template_name = :template_name, 
                            subject = :subject, 
                            html_body = :html_body, 
                            text_body = :text_body,
                            updated_at = CURRENT_TIMESTAMP
                        WHERE id = :id
                    ");
                    
                    $stmt->execute([
                        ':id' => $_POST['template_id'],
                        ':template_name' => $_POST['template_name'],
                        ':subject' => $_POST['subject'],
                        ':html_body' => $_POST['html_body'],
                        ':text_body' => $_POST['text_body']
                    ]);
                    
                    $success_message = "Email template updated successfully!";
                    break;
                    
                case 'toggle_active':
                    $stmt = $pdo->prepare("
                        UPDATE email_templates 
                        SET is_active = :is_active,
                            updated_at = CURRENT_TIMESTAMP
                        WHERE id = :id
                    ");
                    
                    $stmt->execute([
                        ':id' => $_POST['template_id'],
                        ':is_active' => $_POST['is_active'] ? 1 : 0
                    ]);
                    
                    $success_message = "Template status updated successfully!";
                    break;
                    
                case 'reset_template':
                    // Get the template key to reset to default
                    $stmt = $pdo->prepare("SELECT template_key FROM email_templates WHERE id = ?");
                    $stmt->execute([$_POST['template_id']]);
                    $template = $stmt->fetch(PDO::FETCH_ASSOC);
                    
                    if ($template) {
                        // Reset to default template based on template_key
                        $defaultTemplates = getDefaultTemplates();
                        $templateKey = $template['template_key'];
                        
                        if (isset($defaultTemplates[$templateKey])) {
                            $defaultTemplate = $defaultTemplates[$templateKey];
                            
                            $stmt = $pdo->prepare("
                                UPDATE email_templates 
                                SET template_name = :template_name,
                                    subject = :subject,
                                    html_body = :html_body,
                                    text_body = :text_body,
                                    updated_at = CURRENT_TIMESTAMP
                                WHERE id = :id
                            ");
                            
                            $stmt->execute([
                                ':id' => $_POST['template_id'],
                                ':template_name' => $defaultTemplate['template_name'],
                                ':subject' => $defaultTemplate['subject'],
                                ':html_body' => $defaultTemplate['html_body'],
                                ':text_body' => $defaultTemplate['text_body']
                            ]);
                            
                            $success_message = "Template reset to default successfully!";
                        } else {
                            $error_message = "Default template not found for this template type.";
                        }
                    } else {
                        $error_message = "Template not found.";
                    }
                    break;
            }
        }
    } catch (PDOException $e) {
        $error_message = "Database error: " . $e->getMessage();
    }
}

// Get all email templates
try {
    $pdo = new PDO("mysql:host=" . DB_HOST . ";dbname=" . DB_NAME . ";charset=utf8mb4", DB_USER, DB_PASS, [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
    ]);
    
    $stmt = $pdo->prepare("SELECT * FROM email_templates ORDER BY template_name");
    $stmt->execute();
    $templates = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
} catch (PDOException $e) {
    $error_message = "Failed to load email templates: " . $e->getMessage();
    $templates = [];
}

// Get template variables for help
$templateVariables = [
    'payment_confirmation' => ['user_name', 'amount', 'date', 'transaction_id', 'payment_method', 'site_url'],
    'subscription_confirmation' => ['user_name', 'package_name', 'amount', 'billing_cycle', 'next_billing', 'stripe_subscription_id', 'site_url'],
    'subscription_cancellation' => ['user_name', 'package_name', 'end_date', 'stripe_subscription_id', 'site_url'],
    'order_confirmation' => ['user_name', 'order_id', 'product_name', 'amount', 'date', 'site_url']
];

// Function to get default templates for reset functionality
function getDefaultTemplates() {
    return [
        'payment_confirmation' => [
            'template_name' => 'Payment Confirmation',
            'subject' => 'Payment Confirmation - MemoWindow',
            'html_body' => '<!DOCTYPE html><html><head><meta charset="UTF-8"><title>Payment Confirmation</title></head><body><h1>Payment Confirmed!</h1><p>Hi {{user_name}},</p><p>Thank you for your payment of {{amount}} on {{date}}.</p><p>Transaction ID: {{transaction_id}}</p><p>Payment Method: {{payment_method}}</p><p><a href="{{site_url}}">Visit MemoWindow</a></p></body></html>',
            'text_body' => 'Payment Confirmed!\n\nHi {{user_name}},\n\nThank you for your payment of {{amount}} on {{date}}.\n\nTransaction ID: {{transaction_id}}\nPayment Method: {{payment_method}}\n\nVisit: {{site_url}}'
        ],
        'subscription_confirmation' => [
            'template_name' => 'Subscription Confirmation',
            'subject' => 'Subscription Confirmed - MemoWindow',
            'html_body' => '<!DOCTYPE html><html><head><meta charset="UTF-8"><title>Subscription Confirmed</title></head><body><h1>Subscription Confirmed!</h1><p>Hi {{user_name}},</p><p>Welcome to {{package_name}}! Your subscription is now active.</p><p>Amount: {{amount}} per {{billing_cycle}}</p><p>Next billing: {{next_billing}}</p><p><a href="{{site_url}}">Manage Subscription</a></p></body></html>',
            'text_body' => 'Subscription Confirmed!\n\nHi {{user_name}},\n\nWelcome to {{package_name}}! Your subscription is now active.\n\nAmount: {{amount}} per {{billing_cycle}}\nNext billing: {{next_billing}}\n\nManage: {{site_url}}'
        ],
        'subscription_cancellation' => [
            'template_name' => 'Subscription Cancellation',
            'subject' => 'Subscription Cancelled - MemoWindow',
            'html_body' => '<!DOCTYPE html><html><head><meta charset="UTF-8"><title>Subscription Cancelled</title></head><body><h1>Subscription Cancelled</h1><p>Hi {{user_name}},</p><p>Your {{package_name}} subscription has been cancelled.</p><p>Access until: {{end_date}}</p><p><a href="{{site_url}}">Reactivate Subscription</a></p></body></html>',
            'text_body' => 'Subscription Cancelled\n\nHi {{user_name}},\n\nYour {{package_name}} subscription has been cancelled.\n\nAccess until: {{end_date}}\n\nReactivate: {{site_url}}'
        ],
        'order_confirmation' => [
            'template_name' => 'Order Confirmation',
            'subject' => 'Order Confirmation - MemoWindow',
            'html_body' => '<!DOCTYPE html><html><head><meta charset="UTF-8"><title>Order Confirmation</title></head><body><h1>Order Confirmed!</h1><p>Hi {{user_name}},</p><p>Thank you for your order!</p><p>Order ID: {{order_id}}</p><p>Product: {{product_name}}</p><p>Amount: {{amount}}</p><p>Date: {{date}}</p><p><a href="{{site_url}}">Track Order</a></p></body></html>',
            'text_body' => 'Order Confirmed!\n\nHi {{user_name}},\n\nThank you for your order!\n\nOrder ID: {{order_id}}\nProduct: {{product_name}}\nAmount: {{amount}}\nDate: {{date}}\n\nTrack: {{site_url}}'
        ]
    ];
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Email Templates - MemoWindow Admin</title>
    <link rel="stylesheet" href="includes/unified.css?v=<?php echo time(); ?>">
    <link rel="stylesheet" href="includes/admin_styles.css?v=<?php echo time(); ?>">
    <style>
        .admin-container {
            max-width: 1200px;
            margin: 0 auto;
            padding: 20px;
        }
        
        .template-card {
            background: white;
            border-radius: 8px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
            margin-bottom: 20px;
            overflow: hidden;
        }
        
        .template-header {
            background: #f8f9fa;
            padding: 15px 20px;
            border-bottom: 1px solid #e9ecef;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        
        .template-content {
            padding: 20px;
        }
        
        .form-group {
            margin-bottom: 20px;
        }
        
        .form-group label {
            display: block;
            margin-bottom: 5px;
            font-weight: 500;
            color: #333;
        }
        
        .form-group input,
        .form-group textarea {
            width: 100%;
            padding: 10px;
            border: 1px solid #ddd;
            border-radius: 4px;
            font-size: 14px;
        }
        
        .form-group textarea {
            min-height: 100px;
            resize: vertical;
        }
        
        .form-group textarea.html-editor {
            min-height: 300px;
            font-family: 'Courier New', monospace;
        }
        
        .btn {
            padding: 10px 20px;
            border: none;
            border-radius: 4px;
            cursor: pointer;
            font-size: 14px;
            font-weight: 500;
            text-decoration: none;
            display: inline-block;
            margin-right: 10px;
        }
        
        .btn-primary {
            background: #667eea;
            color: white;
        }
        
        .btn-primary:hover {
            background: #5a6fd8;
        }
        
        .btn-success {
            background: #28a745;
            color: white;
        }
        
        .btn-success:hover {
            background: #218838;
        }
        
        .btn-warning {
            background: #ffc107;
            color: #212529;
        }
        
        .btn-warning:hover {
            background: #e0a800;
        }
        
        .btn-danger {
            background: #dc3545;
            color: white;
        }
        
        .btn-danger:hover {
            background: #c82333;
        }
        
        .status-badge {
            padding: 4px 8px;
            border-radius: 12px;
            font-size: 12px;
            font-weight: 500;
        }
        
        .status-active {
            background: #d4edda;
            color: #155724;
        }
        
        .status-inactive {
            background: #f8d7da;
            color: #721c24;
        }
        
        .variables-help {
            background: #e7f3ff;
            border: 1px solid #b3d9ff;
            border-radius: 4px;
            padding: 15px;
            margin-bottom: 20px;
        }
        
        .variables-help h4 {
            margin: 0 0 10px 0;
            color: #0066cc;
        }
        
        .variables-list {
            display: flex;
            flex-wrap: wrap;
            gap: 8px;
        }
        
        .variable-tag {
            background: #667eea;
            color: white;
            padding: 2px 8px;
            border-radius: 12px;
            font-size: 12px;
            font-family: monospace;
        }
        
        .alert {
            padding: 15px;
            margin-bottom: 20px;
            border-radius: 4px;
        }
        
        .alert-success {
            background: #d4edda;
            color: #155724;
            border: 1px solid #c3e6cb;
        }
        
        .alert-danger {
            background: #f8d7da;
            color: #721c24;
            border: 1px solid #f5c6cb;
        }
        
        .template-preview {
            background: #f8f9fa;
            border: 1px solid #e9ecef;
            border-radius: 4px;
            padding: 15px;
            margin-top: 10px;
        }
        
        .template-preview h5 {
            margin: 0 0 10px 0;
            color: #666;
        }
        
        .preview-content {
            max-height: 400px;
            overflow-y: auto;
            background: white;
            padding: 10px;
            border-radius: 4px;
            border: 1px solid #ddd;
        }
        
        .template-stats {
            background: #e7f3ff;
            border: 1px solid #b3d9ff;
            border-radius: 4px;
            padding: 10px;
            margin-bottom: 15px;
            font-size: 14px;
        }
        
        .template-stats h4 {
            margin: 0 0 8px 0;
            color: #0066cc;
            font-size: 14px;
        }
        
        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(150px, 1fr));
            gap: 10px;
        }
        
        .stat-item {
            background: white;
            padding: 8px;
            border-radius: 4px;
            border: 1px solid #ddd;
        }
        
        .stat-label {
            font-weight: 500;
            color: #666;
            font-size: 12px;
        }
        
        .stat-value {
            color: #333;
            font-weight: 600;
        }
        
        .template-actions {
            display: flex;
            gap: 10px;
            margin-top: 15px;
            flex-wrap: wrap;
        }
        
        .btn-small {
            padding: 6px 12px;
            font-size: 12px;
        }
        
        .btn-info {
            background: #17a2b8;
            color: white;
        }
        
        .btn-info:hover {
            background: #138496;
        }
        
        .btn-warning {
            background: #ffc107;
            color: #212529;
        }
        
        .btn-warning:hover {
            background: #e0a800;
        }
        
        .template-history {
            background: #f8f9fa;
            border: 1px solid #e9ecef;
            border-radius: 4px;
            padding: 15px;
            margin-top: 15px;
        }
        
        .history-item {
            background: white;
            border: 1px solid #ddd;
            border-radius: 4px;
            padding: 10px;
            margin-bottom: 10px;
        }
        
        .history-date {
            font-size: 12px;
            color: #666;
        }
        
        .history-changes {
            font-size: 14px;
            color: #333;
        }
    </style>
</head>
<body>
    <?php include 'includes/admin_navigation.php'; ?>
    
    <div class="admin-container">
        <h1>📧 Email Templates Management</h1>
        
        <?php if (isset($success_message)): ?>
            <div class="alert alert-success">
                <?php echo htmlspecialchars($success_message); ?>
            </div>
        <?php endif; ?>
        
        <?php if (isset($error_message)): ?>
            <div class="alert alert-danger">
                <?php echo htmlspecialchars($error_message); ?>
            </div>
        <?php endif; ?>
        
        <div class="variables-help">
            <h4>📝 Available Variables</h4>
            <p>Use these variables in your templates (they will be replaced with actual values):</p>
            <div class="variables-list">
                <?php 
                $allVariables = array_unique(array_merge(...array_values($templateVariables)));
                foreach ($allVariables as $variable): 
                ?>
                    <span class="variable-tag">{{<?php echo $variable; ?>}}</span>
                <?php endforeach; ?>
            </div>
        </div>
        
        <?php foreach ($templates as $template): ?>
            <div class="template-card">
                <div class="template-header">
                    <div>
                        <h3><?php echo htmlspecialchars($template['template_name']); ?></h3>
                        <span class="status-badge <?php echo $template['is_active'] ? 'status-active' : 'status-inactive'; ?>">
                            <?php echo $template['is_active'] ? 'Active' : 'Inactive'; ?>
                        </span>
                    </div>
                    <div>
                        <form method="POST" style="display: inline;">
                            <input type="hidden" name="action" value="toggle_active">
                            <input type="hidden" name="template_id" value="<?php echo $template['id']; ?>">
                            <input type="hidden" name="is_active" value="<?php echo $template['is_active'] ? 0 : 1; ?>">
                            <button type="submit" class="btn <?php echo $template['is_active'] ? 'btn-warning' : 'btn-success'; ?>">
                                <?php echo $template['is_active'] ? 'Deactivate' : 'Activate'; ?>
                            </button>
                        </form>
                    </div>
                </div>
                
                <div class="template-content">
                    <!-- Template Statistics -->
                    <div class="template-stats">
                        <h4>📊 Template Statistics</h4>
                        <div class="stats-grid">
                            <div class="stat-item">
                                <div class="stat-label">Subject Length</div>
                                <div class="stat-value"><?php echo strlen($template['subject']); ?> chars</div>
                            </div>
                            <div class="stat-item">
                                <div class="stat-label">HTML Body Length</div>
                                <div class="stat-value"><?php echo strlen($template['html_body']); ?> chars</div>
                            </div>
                            <div class="stat-item">
                                <div class="stat-label">Text Body Length</div>
                                <div class="stat-value"><?php echo strlen($template['text_body'] ?: ''); ?> chars</div>
                            </div>
                            <div class="stat-item">
                                <div class="stat-label">Variables Used</div>
                                <div class="stat-value"><?php 
                                    $variables = json_decode($template['variables'] ?: '[]', true);
                                    echo count($variables);
                                ?></div>
                            </div>
                            <div class="stat-item">
                                <div class="stat-label">Last Updated</div>
                                <div class="stat-value"><?php echo date('M j, Y', strtotime($template['updated_at'])); ?></div>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Template Actions -->
                    <div class="template-actions">
                        <button type="button" class="btn btn-info btn-small" onclick="previewTemplate(<?php echo $template['id']; ?>)">
                            👁️ Preview
                        </button>
                        <button type="button" class="btn btn-warning btn-small" onclick="testTemplate(<?php echo $template['id']; ?>)">
                            🧪 Test Email
                        </button>
                        <button type="button" class="btn btn-secondary btn-small" onclick="resetTemplate(<?php echo $template['id']; ?>)">
                            🔄 Reset to Default
                        </button>
                    </div>
                    
                    <form method="POST">
                        <input type="hidden" name="action" value="update_template">
                        <input type="hidden" name="template_id" value="<?php echo $template['id']; ?>">
                        
                        <div class="form-group">
                            <label for="template_name_<?php echo $template['id']; ?>">Template Name:</label>
                            <input type="text" id="template_name_<?php echo $template['id']; ?>" name="template_name" 
                                   value="<?php echo htmlspecialchars($template['template_name']); ?>" required>
                        </div>
                        
                        <div class="form-group">
                            <label for="subject_<?php echo $template['id']; ?>">Email Subject:</label>
                            <input type="text" id="subject_<?php echo $template['id']; ?>" name="subject" 
                                   value="<?php echo htmlspecialchars($template['subject']); ?>" required>
                        </div>
                        
                        <div class="form-group">
                            <label for="html_body_<?php echo $template['id']; ?>">HTML Body:</label>
                            <textarea id="html_body_<?php echo $template['id']; ?>" name="html_body" 
                                      class="html-editor" required><?php echo htmlspecialchars($template['html_body']); ?></textarea>
                        </div>
                        
                        <div class="form-group">
                            <label for="text_body_<?php echo $template['id']; ?>">Plain Text Body:</label>
                            <textarea id="text_body_<?php echo $template['id']; ?>" name="text_body"><?php echo htmlspecialchars($template['text_body']); ?></textarea>
                        </div>
                        
                        <div class="form-group">
                            <button type="submit" class="btn btn-primary">Update Template</button>
                            <button type="button" class="btn btn-success" onclick="previewTemplate(<?php echo $template['id']; ?>)">Preview</button>
                        </div>
                    </form>
                    
                    <div id="preview_<?php echo $template['id']; ?>" class="template-preview" style="display: none;">
                        <h5>Template Preview:</h5>
                        <div class="preview-content" id="preview_content_<?php echo $template['id']; ?>"></div>
                    </div>
                </div>
            </div>
        <?php endforeach; ?>
        
        <?php if (empty($templates)): ?>
            <div class="alert alert-danger">
                <h4>No email templates found!</h4>
                <p>Please run the setup script to create the email templates table and default templates.</p>
                <a href="setup_email_templates_table.php" class="btn btn-primary">Setup Email Templates</a>
            </div>
        <?php endif; ?>
    </div>
    
    <script>
        function previewTemplate(templateId) {
            const htmlBody = document.getElementById('html_body_' + templateId).value;
            const previewDiv = document.getElementById('preview_' + templateId);
            const previewContent = document.getElementById('preview_content_' + templateId);
            
            // Replace variables with sample data
            let previewHtml = htmlBody
                .replace(/\{\{user_name\}\}/g, 'John Doe')
                .replace(/\{\{amount\}\}/g, '$29.99')
                .replace(/\{\{date\}\}/g, new Date().toLocaleDateString())
                .replace(/\{\{transaction_id\}\}/g, 'txn_123456789')
                .replace(/\{\{payment_method\}\}/g, 'Credit Card')
                .replace(/\{\{package_name\}\}/g, 'Premium Plan')
                .replace(/\{\{billing_cycle\}\}/g, 'monthly')
                .replace(/\{\{next_billing\}\}/g, new Date(Date.now() + 30*24*60*60*1000).toLocaleDateString())
                .replace(/\{\{stripe_subscription_id\}\}/g, 'sub_123456789')
                .replace(/\{\{end_date\}\}/g, new Date(Date.now() + 30*24*60*60*1000).toLocaleDateString())
                .replace(/\{\{order_id\}\}/g, 'ORD-123456')
                .replace(/\{\{product_name\}\}/g, 'Premium Canvas Print')
                .replace(/\{\{site_url\}\}/g, 'https://memowindow.com');
            
            previewContent.innerHTML = previewHtml;
            previewDiv.style.display = previewDiv.style.display === 'none' ? 'block' : 'none';
        }
        
        function testTemplate(templateId) {
            const templateName = document.getElementById('template_name_' + templateId).value;
            const testEmail = prompt('Enter test email address:', 'test@example.com');
            
            if (testEmail && testEmail.includes('@')) {
                // Create a test form and submit it
                const form = document.createElement('form');
                form.method = 'POST';
                form.action = 'test_email_notifications.php';
                form.target = '_blank';
                
                const templateInput = document.createElement('input');
                templateInput.type = 'hidden';
                templateInput.name = 'template_id';
                templateInput.value = templateId;
                
                const emailInput = document.createElement('input');
                emailInput.type = 'hidden';
                emailInput.name = 'test_email';
                emailInput.value = testEmail;
                
                form.appendChild(templateInput);
                form.appendChild(emailInput);
                document.body.appendChild(form);
                form.submit();
                document.body.removeChild(form);
                
                showToast(`Test email sent to ${testEmail}`, 'success');
            } else if (testEmail) {
                showToast('Please enter a valid email address', 'error');
            }
        }
        
        function resetTemplate(templateId) {
            if (confirm('Are you sure you want to reset this template to default? This will overwrite all your changes.')) {
                // Create a reset form and submit it
                const form = document.createElement('form');
                form.method = 'POST';
                form.action = window.location.href;
                
                const actionInput = document.createElement('input');
                actionInput.type = 'hidden';
                actionInput.name = 'action';
                actionInput.value = 'reset_template';
                
                const templateInput = document.createElement('input');
                templateInput.type = 'hidden';
                templateInput.name = 'template_id';
                templateInput.value = templateId;
                
                form.appendChild(actionInput);
                form.appendChild(templateInput);
                document.body.appendChild(form);
                form.submit();
            }
        }
        
        function showToast(message, type = 'info') {
            const toast = document.createElement('div');
            toast.style.cssText = `
                position: fixed;
                top: 20px;
                right: 20px;
                background: ${type === 'success' ? '#28a745' : type === 'error' ? '#dc3545' : '#17a2b8'};
                color: white;
                padding: 15px 20px;
                border-radius: 8px;
                box-shadow: 0 4px 12px rgba(0,0,0,0.15);
                z-index: 10000;
                font-weight: 500;
                max-width: 300px;
                animation: slideIn 0.3s ease;
            `;
            toast.textContent = message;
            
            // Add animation styles
            const style = document.createElement('style');
            style.textContent = `
                @keyframes slideIn {
                    from { transform: translateX(100%); opacity: 0; }
                    to { transform: translateX(0); opacity: 1; }
                }
            `;
            document.head.appendChild(style);
            
            document.body.appendChild(toast);
            
            // Remove after 3 seconds
            setTimeout(() => {
                if (toast.parentNode) {
                    toast.parentNode.removeChild(toast);
                }
            }, 3000);
        }
        
        // Auto-save functionality (optional)
        let saveTimeout;
        document.querySelectorAll('textarea, input[type="text"]').forEach(element => {
            element.addEventListener('input', function() {
                clearTimeout(saveTimeout);
                saveTimeout = setTimeout(() => {
                    // Auto-save could be implemented here
                }, 2000);
            });
        });
        
        // Add keyboard shortcuts
        document.addEventListener('keydown', function(e) {
            // Ctrl+S to save
            if (e.ctrlKey && e.key === 's') {
                e.preventDefault();
                const activeForm = document.querySelector('form[method="POST"]');
                if (activeForm) {
                    activeForm.submit();
                }
            }
            
            // Ctrl+P to preview
            if (e.ctrlKey && e.key === 'p') {
                e.preventDefault();
                const firstPreviewBtn = document.querySelector('button[onclick*="previewTemplate"]');
                if (firstPreviewBtn) {
                    firstPreviewBtn.click();
                }
            }
        });
    </script>
</body>
</html>

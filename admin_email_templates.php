<?php
/**
 * Admin Email Templates Editor
 * Allows admins to edit email templates from the admin panel
 */

require_once 'config.php';
require_once 'check_admin.php';

// Check if user is admin
if (!isAdmin()) {
    header('Location: login.php');
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
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Email Templates - MemoWindow Admin</title>
    <link rel="stylesheet" href="includes/unified.css?v=<?php echo time(); ?>">
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
            max-height: 200px;
            overflow-y: auto;
            background: white;
            padding: 10px;
            border-radius: 4px;
            border: 1px solid #ddd;
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
    </script>
</body>
</html>

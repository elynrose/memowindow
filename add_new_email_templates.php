<?php
/**
 * Add new email templates to the database
 * Maintenance Notice, Feature Announcement, and Memory Scanned
 */

require_once 'config.php';

try {
    $pdo = new PDO("mysql:host=" . DB_HOST . ";dbname=" . DB_NAME . ";charset=utf8mb4", DB_USER, DB_PASS, [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
    ]);
    
    echo "<h1>Adding New Email Templates</h1>";
    
    // New email templates
    $newTemplates = [
        [
            'template_key' => 'maintenance_notice',
            'template_name' => 'Maintenance Notice',
            'subject' => 'Scheduled Maintenance - MemoWindow',
            'html_body' => '<!DOCTYPE html><html><head><meta charset="UTF-8"><title>Maintenance Notice</title></head><body style="font-family: Arial, sans-serif; line-height: 1.6; color: #333; max-width: 600px; margin: 0 auto; padding: 20px;"><div style="background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); color: white; padding: 30px; text-align: center; border-radius: 10px 10px 0 0;"><h1 style="margin: 0; font-size: 28px;">🔧 Scheduled Maintenance</h1><p style="margin: 10px 0 0 0; font-size: 16px;">MemoWindow System Update</p></div><div style="background: white; padding: 30px; border: 1px solid #e0e0e0; border-top: none; border-radius: 0 0 10px 10px;"><p>Hi {{user_name}},</p><p>We wanted to let you know about an upcoming scheduled maintenance window for MemoWindow.</p><div style="background: #f8f9fa; padding: 20px; border-radius: 8px; margin: 20px 0;"><h3 style="margin-top: 0; color: #667eea;">📅 Maintenance Details</h3><p><strong>Date:</strong> {{maintenance_date}}</p><p><strong>Time:</strong> {{maintenance_time}}</p><p><strong>Duration:</strong> {{maintenance_duration}}</p><p><strong>Reason:</strong> {{maintenance_reason}}</p></div><div style="background: #fff3cd; border: 1px solid #ffeaa7; padding: 15px; border-radius: 8px; margin: 20px 0;"><p style="margin: 0; color: #856404;"><strong>⚠️ Important:</strong> {{affected_services}}</p></div><p>We apologize for any inconvenience this may cause. We\'re working to improve your MemoWindow experience!</p><p>Thank you for your patience and understanding.</p><div style="text-align: center; margin: 30px 0;"><a href="{{site_url}}" style="background: #667eea; color: white; padding: 12px 30px; text-decoration: none; border-radius: 25px; display: inline-block; font-weight: bold;">Visit MemoWindow</a></div><p style="text-align: center; color: #666; font-size: 14px; margin-top: 30px;">Best regards,<br>The MemoWindow Team</p></div></body></html>',
            'text_body' => 'Scheduled Maintenance - MemoWindow\n\nHi {{user_name}},\n\nWe wanted to let you know about an upcoming scheduled maintenance window for MemoWindow.\n\nMaintenance Details:\n- Date: {{maintenance_date}}\n- Time: {{maintenance_time}}\n- Duration: {{maintenance_duration}}\n- Reason: {{maintenance_reason}}\n\nImportant: {{affected_services}}\n\nWe apologize for any inconvenience this may cause. We\'re working to improve your MemoWindow experience!\n\nThank you for your patience and understanding.\n\nVisit: {{site_url}}\n\nBest regards,\nThe MemoWindow Team',
            'variables' => json_encode(['user_name', 'maintenance_date', 'maintenance_time', 'maintenance_duration', 'maintenance_reason', 'affected_services', 'site_url']),
            'is_active' => 1
        ],
        [
            'template_key' => 'feature_announcement',
            'template_name' => 'Feature Announcement',
            'subject' => '🎉 New Feature: {{feature_name}} - MemoWindow',
            'html_body' => '<!DOCTYPE html><html><head><meta charset="UTF-8"><title>Feature Announcement</title></head><body style="font-family: Arial, sans-serif; line-height: 1.6; color: #333; max-width: 600px; margin: 0 auto; padding: 20px;"><div style="background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); color: white; padding: 30px; text-align: center; border-radius: 10px 10px 0 0;"><h1 style="margin: 0; font-size: 28px;">🎉 New Feature!</h1><p style="margin: 10px 0 0 0; font-size: 16px;">{{feature_name}} is now available</p></div><div style="background: white; padding: 30px; border: 1px solid #e0e0e0; border-top: none; border-radius: 0 0 10px 10px;"><p>Hi {{user_name}},</p><p>We\'re excited to announce that <strong>{{feature_name}}</strong> is now live on MemoWindow!</p><div style="background: #e7f3ff; padding: 20px; border-radius: 8px; margin: 20px 0; border-left: 4px solid #667eea;"><h3 style="margin-top: 0; color: #667eea;">✨ What\'s New</h3><p>{{feature_description}}</p></div><div style="background: #f0f8f0; padding: 20px; border-radius: 8px; margin: 20px 0; border-left: 4px solid #28a745;"><h3 style="margin-top: 0; color: #28a745;">🚀 Benefits</h3><p>{{feature_benefits}}</p></div><div style="background: #fff8e1; padding: 20px; border-radius: 8px; margin: 20px 0; border-left: 4px solid #ffc107;"><h3 style="margin-top: 0; color: #f57c00;">📱 How to Access</h3><p>{{how_to_access}}</p></div><p><strong>Launch Date:</strong> {{launch_date}}</p><p>We hope you enjoy this new feature! As always, we\'re here to help if you have any questions.</p><div style="text-align: center; margin: 30px 0;"><a href="{{site_url}}" style="background: #667eea; color: white; padding: 12px 30px; text-decoration: none; border-radius: 25px; display: inline-block; font-weight: bold;">Try It Now</a></div><p style="text-align: center; color: #666; font-size: 14px; margin-top: 30px;">Happy creating!<br>The MemoWindow Team</p></div></body></html>',
            'text_body' => 'New Feature: {{feature_name}} - MemoWindow\n\nHi {{user_name}},\n\nWe\'re excited to announce that {{feature_name}} is now live on MemoWindow!\n\nWhat\'s New:\n{{feature_description}}\n\nBenefits:\n{{feature_benefits}}\n\nHow to Access:\n{{how_to_access}}\n\nLaunch Date: {{launch_date}}\n\nWe hope you enjoy this new feature! As always, we\'re here to help if you have any questions.\n\nTry it now: {{site_url}}\n\nHappy creating!\nThe MemoWindow Team',
            'variables' => json_encode(['user_name', 'feature_name', 'feature_description', 'feature_benefits', 'how_to_access', 'launch_date', 'site_url']),
            'is_active' => 1
        ],
        [
            'template_key' => 'memory_scanned',
            'template_name' => 'Memory Scanned',
            'subject' => '✅ Your Memory "{{memory_title}}" is Ready - MemoWindow',
            'html_body' => '<!DOCTYPE html><html><head><meta charset="UTF-8"><title>Memory Scanned</title></head><body style="font-family: Arial, sans-serif; line-height: 1.6; color: #333; max-width: 600px; margin: 0 auto; padding: 20px;"><div style="background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); color: white; padding: 30px; text-align: center; border-radius: 10px 10px 0 0;"><h1 style="margin: 0; font-size: 28px;">✅ Memory Ready!</h1><p style="margin: 10px 0 0 0; font-size: 16px;">Your memory has been processed</p></div><div style="background: white; padding: 30px; border: 1px solid #e0e0e0; border-top: none; border-radius: 0 0 10px 10px;"><p>Hi {{user_name}},</p><p>Great news! Your memory <strong>"{{memory_title}}"</strong> has been successfully scanned and processed.</p><div style="background: #e7f3ff; padding: 20px; border-radius: 8px; margin: 20px 0; border-left: 4px solid #667eea;"><h3 style="margin-top: 0; color: #667eea;">📊 Processing Details</h3><p><strong>Memory ID:</strong> {{memory_id}}</p><p><strong>Scan Date:</strong> {{scan_date}}</p><p><strong>Status:</strong> {{scan_status}}</p></div><div style="background: #f0f8f0; padding: 20px; border-radius: 8px; margin: 20px 0; border-left: 4px solid #28a745;"><h3 style="margin-top: 0; color: #28a745;">🎵 What\'s Next?</h3><p>Your memory is now ready to:</p><ul><li>Play and share with others</li><li>Create voice clones</li><li>Generate audio versions</li><li>Order physical prints</li></ul></div><div style="text-align: center; margin: 30px 0;"><a href="{{memory_url}}" style="background: #667eea; color: white; padding: 12px 30px; text-decoration: none; border-radius: 25px; display: inline-block; font-weight: bold; margin: 5px;">View All Memories</a><a href="{{play_url}}" style="background: #28a745; color: white; padding: 12px 30px; text-decoration: none; border-radius: 25px; display: inline-block; font-weight: bold; margin: 5px;">Play Memory</a></div><p>Thank you for using MemoWindow to preserve your precious memories!</p><p style="text-align: center; color: #666; font-size: 14px; margin-top: 30px;">Keep creating!<br>The MemoWindow Team</p></div></body></html>',
            'text_body' => 'Your Memory "{{memory_title}}" is Ready - MemoWindow\n\nHi {{user_name}},\n\nGreat news! Your memory "{{memory_title}}" has been successfully scanned and processed.\n\nProcessing Details:\n- Memory ID: {{memory_id}}\n- Scan Date: {{scan_date}}\n- Status: {{scan_status}}\n\nWhat\'s Next?\nYour memory is now ready to:\n- Play and share with others\n- Create voice clones\n- Generate audio versions\n- Order physical prints\n\nView all memories: {{memory_url}}\nPlay this memory: {{play_url}}\n\nThank you for using MemoWindow to preserve your precious memories!\n\nKeep creating!\nThe MemoWindow Team',
            'variables' => json_encode(['user_name', 'memory_title', 'memory_id', 'scan_date', 'scan_status', 'memory_url', 'play_url', 'site_url']),
            'is_active' => 1
        ]
    ];
    
    $addedCount = 0;
    $updatedCount = 0;
    
    foreach ($newTemplates as $template) {
        // Check if template already exists
        $stmt = $pdo->prepare("SELECT id FROM email_templates WHERE template_key = ?");
        $stmt->execute([$template['template_key']]);
        $existing = $stmt->fetch();
        
        if ($existing) {
            // Update existing template
            $stmt = $pdo->prepare("
                UPDATE email_templates 
                SET template_name = :template_name,
                    subject = :subject,
                    html_body = :html_body,
                    text_body = :text_body,
                    variables = :variables,
                    is_active = :is_active,
                    updated_at = CURRENT_TIMESTAMP
                WHERE template_key = :template_key
            ");
            
            $stmt->execute([
                ':template_key' => $template['template_key'],
                ':template_name' => $template['template_name'],
                ':subject' => $template['subject'],
                ':html_body' => $template['html_body'],
                ':text_body' => $template['text_body'],
                ':variables' => $template['variables'],
                ':is_active' => $template['is_active']
            ]);
            
            echo "<p>✅ Updated template: <strong>{$template['template_name']}</strong></p>";
            $updatedCount++;
        } else {
            // Insert new template
            $stmt = $pdo->prepare("
                INSERT INTO email_templates (template_key, template_name, subject, html_body, text_body, variables, is_active, created_at, updated_at)
                VALUES (:template_key, :template_name, :subject, :html_body, :text_body, :variables, :is_active, CURRENT_TIMESTAMP, CURRENT_TIMESTAMP)
            ");
            
            $stmt->execute([
                ':template_key' => $template['template_key'],
                ':template_name' => $template['template_name'],
                ':subject' => $template['subject'],
                ':html_body' => $template['html_body'],
                ':text_body' => $template['text_body'],
                ':variables' => $template['variables'],
                ':is_active' => $template['is_active']
            ]);
            
            echo "<p>✅ Added new template: <strong>{$template['template_name']}</strong></p>";
            $addedCount++;
        }
    }
    
    echo "<h2>Summary</h2>";
    echo "<p><strong>Added:</strong> {$addedCount} new templates</p>";
    echo "<p><strong>Updated:</strong> {$updatedCount} existing templates</p>";
    echo "<p><strong>Total processed:</strong> " . ($addedCount + $updatedCount) . " templates</p>";
    
    echo "<h2>New Email Templates Available:</h2>";
    echo "<ul>";
    echo "<li><strong>Maintenance Notice</strong> - For scheduled maintenance notifications</li>";
    echo "<li><strong>Feature Announcement</strong> - For new feature announcements</li>";
    echo "<li><strong>Memory Scanned</strong> - For memory processing completion</li>";
    echo "</ul>";
    
    echo "<p><a href='admin_email_templates.php'>View Email Templates Admin</a></p>";
    
} catch (PDOException $e) {
    echo "<h2>❌ Error</h2>";
    echo "<p>Database error: " . $e->getMessage() . "</p>";
} catch (Exception $e) {
    echo "<h2>❌ Error</h2>";
    echo "<p>Error: " . $e->getMessage() . "</p>";
}
?>

<style>
body { font-family: Arial, sans-serif; margin: 40px; }
h1 { color: #333; }
h2 { color: #667eea; }
p { margin: 10px 0; }
ul { margin: 10px 0; padding-left: 20px; }
a { color: #667eea; text-decoration: none; }
a:hover { text-decoration: underline; }
</style>

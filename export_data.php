<?php
// export_data.php - Export data for admin users
require_once 'config.php';
require_once 'unified_auth.php';

$exportType = $_GET['type'] ?? '';

if (!$exportType) {
    http_response_code(400);
    echo "Missing parameters";
    exit;
}

// Check if user is authenticated and is admin
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

// Set CSV headers
header('Content-Type: text/csv');
header('Content-Disposition: attachment; filename="memowindow_' . $exportType . '_' . date('Y-m-d') . '.csv"');

$output = fopen('php://output', 'w');

try {
    if ($exportType === 'memories') {
        // Export memories data
        fputcsv($output, ['ID', 'Title', 'Original File', 'User ID', 'Image URL', 'Audio URL', 'Created At']);
        
        $stmt = $pdo->query("
            SELECT 
                id,
                title,
                original_name,
                user_id,
                image_url,
                audio_url,
                created_at
            FROM wave_assets 
            ORDER BY created_at DESC
        ");
        
        while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
            fputcsv($output, [
                $row['id'],
                $row['title'],
                $row['original_name'],
                $row['user_id'],
                $row['image_url'],
                $row['audio_url'],
                $row['created_at']
            ]);
        }
        
    } elseif ($exportType === 'orders') {
        // Export orders data
        fputcsv($output, ['ID', 'Stripe Session', 'Customer Name', 'Customer Email', 'Product', 'Amount', 'Status', 'Printful Order ID', 'Created At']);
        
        $stmt = $pdo->query("
            SELECT 
                o.*,
                p.name as product_name
            FROM orders o
            LEFT JOIN print_products p ON o.product_variant_id = p.product_key
            ORDER BY o.created_at DESC
        ");
        
        while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
            fputcsv($output, [
                $row['id'],
                $row['stripe_session_id'],
                $row['customer_name'],
                $row['customer_email'],
                $row['product_name'] ?? 'Unknown Product',
                '$' . number_format($row['amount_paid'] / 100, 2),
                $row['status'],
                $row['printful_order_id'],
                $row['created_at']
            ]);
        }
        
    } elseif ($exportType === 'backups') {
        // Export backup data
        fputcsv($output, [
            'Memory ID', 
            'Title', 
            'Original Name', 
            'Audio URL', 
            'Audio Size (bytes)', 
            'Audio Duration (seconds)', 
            'Backup Status', 
            'Backup URLs', 
            'Last Backup Check', 
            'Created At',
            'Backup Count',
            'Total Backup Size (bytes)'
        ]);
        
        $stmt = $pdo->query("
            SELECT 
                w.id,
                w.title,
                w.original_name,
                w.audio_url,
                w.audio_size,
                w.audio_duration,
                w.backup_status,
                w.audio_backup_urls,
                w.last_backup_check,
                w.created_at,
                COUNT(ab.id) as backup_count,
                SUM(ab.file_size) as total_backup_size
            FROM wave_assets w
            LEFT JOIN audio_backups ab ON w.id = ab.memory_id
            WHERE w.audio_url IS NOT NULL
            GROUP BY w.id
            ORDER BY w.created_at DESC
        ");
        
        while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
            $backupUrls = '';
            if ($row['audio_backup_urls']) {
                $urls = json_decode($row['audio_backup_urls'], true);
                if (is_array($urls)) {
                    $backupUrls = implode('; ', $urls);
                }
            }
            
            fputcsv($output, [
                $row['id'],
                $row['title'] ?: 'Untitled',
                $row['original_name'],
                $row['audio_url'],
                $row['audio_size'] ?: 0,
                $row['audio_duration'] ?: 0,
                $row['backup_status'] ?: 'pending',
                $backupUrls,
                $row['last_backup_check'] ?: 'Never',
                $row['created_at'],
                $row['backup_count'] ?: 0,
                $row['total_backup_size'] ?: 0
            ]);
        }
        
    } else {
        http_response_code(400);
        echo "Invalid export type";
        exit;
    }
    
} catch (PDOException $e) {
    http_response_code(500);
    echo "Export error: " . $e->getMessage();
}

fclose($output);
?>

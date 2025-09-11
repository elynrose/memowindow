<?php
// export_data.php - Export data for admin users
require_once 'config.php';
require_once 'secure_auth.php';

$exportType = $_GET['type'] ?? '';

// Get user ID from session or URL parameter (for backward compatibility)
$userFirebaseUID = null;

// Check session first
if (isLoggedIn()) {
    $userFirebaseUID = getCurrentUser()['user_id'];
} else {
    // Fallback to URL parameter for backward compatibility
    $userFirebaseUID = $_GET['user_id'] ?? null;
}

if (!$userFirebaseUID || !$exportType) {
    http_response_code(401);
    echo "Authentication required or missing parameters";
    exit;
}

// Check if user is admin
try {
    $pdo = new PDO("mysql:host=" . DB_HOST . ";dbname=" . DB_NAME . ";charset=utf8mb4", DB_USER, DB_PASS, [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
    ]);
    
    $adminCheck = $pdo->prepare("SELECT is_admin FROM admin_users WHERE firebase_uid = :uid AND is_admin = 1");
    $adminCheck->execute([':uid' => $userFirebaseUID]);
    
    if (!$adminCheck->fetch()) {
        http_response_code(403);
        echo "Access denied";
        exit;
    }
    
} catch (PDOException $e) {
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

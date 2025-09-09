<?php
// check_admin.php - Check if user has admin privileges
header('Content-Type: application/json');
require_once 'config.php';

$userFirebaseUID = $_GET['user_id'] ?? '';

if (!$userFirebaseUID) {
    echo json_encode(['is_admin' => false, 'error' => 'Missing user ID']);
    exit;
}

try {
    $pdo = new PDO("mysql:host=" . DB_HOST . ";dbname=" . DB_NAME . ";charset=utf8mb4", DB_USER, DB_PASS, [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
    ]);
    
    $stmt = $pdo->prepare("SELECT is_admin, permissions FROM admin_users WHERE firebase_uid = :uid");
    $stmt->execute([':uid' => $userFirebaseUID]);
    $admin = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if ($admin) {
        echo json_encode([
            'is_admin' => (bool)$admin['is_admin'],
            'permissions' => json_decode($admin['permissions'], true)
        ]);
    } else {
        echo json_encode(['is_admin' => false]);
    }
    
} catch (PDOException $e) {
    echo json_encode(['is_admin' => false, 'error' => 'Database error']);
}
?>

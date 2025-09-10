<?php
require_once 'auth_check.php';
require_once 'config.php';

// Check if user is admin
requireAdmin();

try {
    $pdo = new PDO("mysql:host=" . DB_HOST . ";dbname=" . DB_NAME, DB_USER, DB_PASS);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    // Handle form submissions
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        $action = $_POST['action'] ?? '';
        
        if ($action === 'update_package') {
            $packageId = $_POST['package_id'];
            $name = $_POST['name'];
            $description = $_POST['description'];
            $priceMonthly = $_POST['price_monthly'];
            $priceYearly = $_POST['price_yearly'];
            $memoryLimit = $_POST['memory_limit'];
            $memoryExpiryDays = $_POST['memory_expiry_days'];
            $voiceCloneLimit = $_POST['voice_clone_limit'];
            $stripePriceIdMonthly = $_POST['stripe_price_id_monthly'];
            $stripePriceIdYearly = $_POST['stripe_price_id_yearly'];
            $isActive = isset($_POST['is_active']) ? 1 : 0;
            
            $stmt = $pdo->prepare("
                UPDATE subscription_packages 
                SET name = ?, description = ?, price_monthly = ?, price_yearly = ?,
                    memory_limit = ?, memory_expiry_days = ?, voice_clone_limit = ?,
                    stripe_price_id_monthly = ?, stripe_price_id_yearly = ?, is_active = ?
                WHERE id = ?
            ");
            $stmt->execute([
                $name, $description, $priceMonthly, $priceYearly,
                $memoryLimit, $memoryExpiryDays, $voiceCloneLimit,
                $stripePriceIdMonthly, $stripePriceIdYearly, $isActive, $packageId
            ]);
            
            $success = "Package updated successfully!";
        }
    }
    
    // Get all packages
    $stmt = $pdo->query("SELECT * FROM subscription_packages ORDER BY sort_order");
    $packages = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Get subscription statistics
    $statsStmt = $pdo->query("
        SELECT 
            sp.name,
            COUNT(us.id) as subscriber_count,
            SUM(CASE WHEN us.status = 'active' THEN 1 ELSE 0 END) as active_count
        FROM subscription_packages sp
        LEFT JOIN user_subscriptions us ON sp.id = us.package_id
        GROUP BY sp.id, sp.name
        ORDER BY sp.sort_order
    ");
    $stats = $statsStmt->fetchAll(PDO::FETCH_ASSOC);
    
} catch (PDOException $e) {
    $error = "Database error: " . $e->getMessage();
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Subscription Management - MemoWindow Admin</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        
        body {
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            padding: 20px;
        }
        
        .container {
            max-width: 1200px;
            margin: 0 auto;
            background: white;
            border-radius: 20px;
            box-shadow: 0 20px 40px rgba(0,0,0,0.1);
            overflow: hidden;
        }
        
        .header {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 30px;
            text-align: center;
        }
        
        .header h1 {
            font-size: 2.5rem;
            margin-bottom: 10px;
        }
        
        .header p {
            opacity: 0.9;
            font-size: 1.1rem;
        }
        
        .nav {
            background: #f8f9fa;
            padding: 20px 30px;
            border-bottom: 1px solid #e9ecef;
        }
        
        .nav a {
            color: #667eea;
            text-decoration: none;
            margin-right: 20px;
            font-weight: 500;
        }
        
        .content {
            padding: 30px;
        }
        
        .alert {
            padding: 15px;
            border-radius: 8px;
            margin-bottom: 20px;
        }
        
        .alert-success {
            background: #d4edda;
            color: #155724;
            border: 1px solid #c3e6cb;
        }
        
        .alert-error {
            background: #f8d7da;
            color: #721c24;
            border: 1px solid #f5c6cb;
        }
        
        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 20px;
            margin-bottom: 30px;
        }
        
        .stat-card {
            background: #f8f9fa;
            padding: 20px;
            border-radius: 12px;
            text-align: center;
        }
        
        .stat-card h3 {
            color: #667eea;
            margin-bottom: 10px;
        }
        
        .stat-card .number {
            font-size: 2rem;
            font-weight: bold;
            color: #333;
        }
        
        .packages-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(350px, 1fr));
            gap: 30px;
        }
        
        .package-card {
            border: 2px solid #e9ecef;
            border-radius: 16px;
            padding: 25px;
            background: white;
            transition: all 0.3s ease;
        }
        
        .package-card:hover {
            border-color: #667eea;
            box-shadow: 0 10px 30px rgba(102, 126, 234, 0.1);
        }
        
        .package-card.premium {
            border-color: #ffd700;
            background: linear-gradient(135deg, #fff9e6 0%, #ffffff 100%);
        }
        
        .package-header {
            text-align: center;
            margin-bottom: 20px;
        }
        
        .package-name {
            font-size: 1.5rem;
            font-weight: bold;
            color: #333;
            margin-bottom: 5px;
        }
        
        .package-price {
            font-size: 2rem;
            font-weight: bold;
            color: #667eea;
            margin-bottom: 10px;
        }
        
        .package-price .period {
            font-size: 1rem;
            color: #666;
            font-weight: normal;
        }
        
        .package-description {
            color: #666;
            margin-bottom: 20px;
            text-align: center;
        }
        
        .package-features {
            margin-bottom: 20px;
        }
        
        .package-features h4 {
            color: #333;
            margin-bottom: 10px;
        }
        
        .package-features ul {
            list-style: none;
            padding: 0;
        }
        
        .package-features li {
            padding: 5px 0;
            color: #666;
        }
        
        .package-features li:before {
            content: "✓";
            color: #28a745;
            font-weight: bold;
            margin-right: 8px;
        }
        
        .package-form {
            margin-top: 20px;
        }
        
        .form-group {
            margin-bottom: 15px;
        }
        
        .form-group label {
            display: block;
            margin-bottom: 5px;
            font-weight: 500;
            color: #333;
        }
        
        .form-group input, .form-group textarea {
            width: 100%;
            padding: 10px;
            border: 1px solid #ddd;
            border-radius: 6px;
            font-size: 14px;
        }
        
        .form-group textarea {
            height: 80px;
            resize: vertical;
        }
        
        .form-row {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 15px;
        }
        
        .checkbox-group {
            display: flex;
            align-items: center;
            gap: 8px;
        }
        
        .btn {
            background: #667eea;
            color: white;
            border: none;
            padding: 12px 24px;
            border-radius: 8px;
            cursor: pointer;
            font-size: 14px;
            font-weight: 500;
            transition: all 0.3s ease;
        }
        
        .btn:hover {
            background: #5a6fd8;
            transform: translateY(-2px);
        }
        
        .btn-secondary {
            background: #6c757d;
        }
        
        .btn-secondary:hover {
            background: #5a6268;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1>Subscription Management</h1>
            <p>Manage subscription packages and pricing</p>
        </div>
        
        <div class="nav">
            <a href="admin.php">← Back to Admin</a>
            <a href="admin_voice_clone.php">Voice Clone Settings</a>
        </div>
        
        <div class="content">
            <?php if (isset($success)): ?>
                <div class="alert alert-success"><?= htmlspecialchars($success) ?></div>
            <?php endif; ?>
            
            <?php if (isset($error)): ?>
                <div class="alert alert-error"><?= htmlspecialchars($error) ?></div>
            <?php endif; ?>
            
            <div class="stats-grid">
                <?php foreach ($stats as $stat): ?>
                    <div class="stat-card">
                        <h3><?= htmlspecialchars($stat['name']) ?></h3>
                        <div class="number"><?= $stat['active_count'] ?></div>
                        <p>Active Subscribers</p>
                        <small><?= $stat['subscriber_count'] ?> Total</small>
                    </div>
                <?php endforeach; ?>
            </div>
            
            <div class="packages-grid">
                <?php foreach ($packages as $package): ?>
                    <div class="package-card <?= $package['slug'] === 'premium' ? 'premium' : '' ?>">
                        <div class="package-header">
                            <div class="package-name"><?= htmlspecialchars($package['name']) ?></div>
                            <div class="package-price">
                                $<?= number_format($package['price_monthly'], 2) ?>
                                <span class="period">/month</span>
                            </div>
                            <div class="package-description">
                                <?= htmlspecialchars($package['description']) ?>
                            </div>
                        </div>
                        
                        <div class="package-features">
                            <h4>Features:</h4>
                            <?php 
                            $features = json_decode($package['features'], true);
                            if ($features):
                            ?>
                                <ul>
                                    <?php foreach ($features as $feature): ?>
                                        <li><?= htmlspecialchars($feature) ?></li>
                                    <?php endforeach; ?>
                                </ul>
                            <?php endif; ?>
                        </div>
                        
                        <form method="POST" class="package-form">
                            <input type="hidden" name="action" value="update_package">
                            <input type="hidden" name="package_id" value="<?= $package['id'] ?>">
                            
                            <div class="form-group">
                                <label>Package Name</label>
                                <input type="text" name="name" value="<?= htmlspecialchars($package['name']) ?>" required>
                            </div>
                            
                            <div class="form-group">
                                <label>Description</label>
                                <textarea name="description"><?= htmlspecialchars($package['description']) ?></textarea>
                            </div>
                            
                            <div class="form-row">
                                <div class="form-group">
                                    <label>Monthly Price ($)</label>
                                    <input type="number" name="price_monthly" value="<?= $package['price_monthly'] ?>" step="0.01" min="0">
                                </div>
                                <div class="form-group">
                                    <label>Yearly Price ($)</label>
                                    <input type="number" name="price_yearly" value="<?= $package['price_yearly'] ?>" step="0.01" min="0">
                                </div>
                            </div>
                            
                            <div class="form-row">
                                <div class="form-group">
                                    <label>Memory Limit (0 = unlimited)</label>
                                    <input type="number" name="memory_limit" value="<?= $package['memory_limit'] ?>" min="0">
                                </div>
                                <div class="form-group">
                                    <label>Memory Expiry Days (0 = never)</label>
                                    <input type="number" name="memory_expiry_days" value="<?= $package['memory_expiry_days'] ?>" min="0">
                                </div>
                            </div>
                            
                            <div class="form-group">
                                <label>Voice Clone Limit</label>
                                <input type="number" name="voice_clone_limit" value="<?= $package['voice_clone_limit'] ?>" min="0">
                            </div>
                            
                            <div class="form-row">
                                <div class="form-group">
                                    <label>Stripe Monthly Price ID</label>
                                    <input type="text" name="stripe_price_id_monthly" value="<?= htmlspecialchars($package['stripe_price_id_monthly']) ?>" placeholder="price_xxxxx">
                                </div>
                                <div class="form-group">
                                    <label>Stripe Yearly Price ID</label>
                                    <input type="text" name="stripe_price_id_yearly" value="<?= htmlspecialchars($package['stripe_price_id_yearly']) ?>" placeholder="price_xxxxx">
                                </div>
                            </div>
                            
                            <div class="form-group">
                                <div class="checkbox-group">
                                    <input type="checkbox" name="is_active" <?= $package['is_active'] ? 'checked' : '' ?>>
                                    <label>Package is active</label>
                                </div>
                            </div>
                            
                            <button type="submit" class="btn">Update Package</button>
                        </form>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>
    </div>
</body>
</html>

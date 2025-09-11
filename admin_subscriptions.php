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
            $maxAudioLength = $_POST['max_audio_length_seconds'];
            $stripePriceIdMonthly = $_POST['stripe_price_id_monthly'];
            $stripePriceIdYearly = $_POST['stripe_price_id_yearly'];
            $isActive = isset($_POST['is_active']) ? 1 : 0;
            
            $stmt = $pdo->prepare("
                UPDATE subscription_packages 
                SET name = ?, description = ?, price_monthly = ?, price_yearly = ?,
                    memory_limit = ?, memory_expiry_days = ?, voice_clone_limit = ?,
                    max_audio_length_seconds = ?, stripe_price_id_monthly = ?, stripe_price_id_yearly = ?, is_active = ?
                WHERE id = ?
            ");
            $stmt->execute([
                $name, $description, $priceMonthly, $priceYearly,
                $memoryLimit, $memoryExpiryDays, $voiceCloneLimit, $maxAudioLength,
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
    <link rel="stylesheet" href="includes/admin_styles.css">
    <style>
        /* Page-specific styles */
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
    </style>
</head>
<body>
    <div class="admin-container">
        <div class="admin-header">
            <h1>💳 Subscription Management</h1>
            <p>Manage subscription packages and pricing</p>
        </div>
        
        <?php include 'includes/admin_navigation.php'; ?>
        
        <div class="admin-content">
            <?php if (isset($success)): ?>
                <div class="admin-alert admin-alert-success"><?= htmlspecialchars($success) ?></div>
            <?php endif; ?>
            
            <?php if (isset($error)): ?>
                <div class="admin-alert admin-alert-error"><?= htmlspecialchars($error) ?></div>
            <?php endif; ?>
            
            <div class="admin-stats-grid">
                <?php foreach ($stats as $stat): ?>
                    <div class="admin-stat-card">
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
                            
                            <div style="margin-top: 15px; padding: 10px; background: #f3f4f6; border-radius: 6px;">
                                <h5 style="margin: 0 0 8px 0; color: #374151;">Package Limits:</h5>
                                <div style="font-size: 14px; color: #6b7280;">
                                    <div>🎵 Max Audio Length: <strong><?php 
                                        $minutes = floor($package['max_audio_length_seconds'] / 60);
                                        $seconds = $package['max_audio_length_seconds'] % 60;
                                        echo $minutes > 0 ? "{$minutes}m {$seconds}s" : "{$seconds}s";
                                    ?></strong></div>
                                    <div>💾 Memory Limit: <strong><?= $package['memory_limit'] == 0 ? 'Unlimited' : $package['memory_limit'] ?></strong></div>
                                    <div>🎤 Voice Clone Limit: <strong><?= $package['voice_clone_limit'] == 0 ? 'Unlimited' : $package['voice_clone_limit'] ?></strong></div>
                                </div>
                            </div>
                        </div>
                        
                        <form method="POST" class="package-form">
                            <input type="hidden" name="action" value="update_package">
                            <input type="hidden" name="package_id" value="<?= $package['id'] ?>">
                            
                            <div class="admin-form-group">
                                <label>Package Name</label>
                                <input type="text" name="name" value="<?= htmlspecialchars($package['name']) ?>" required>
                            </div>
                            
                            <div class="admin-form-group">
                                <label>Description</label>
                                <textarea name="description"><?= htmlspecialchars($package['description']) ?></textarea>
                            </div>
                            
                            <div class="admin-form-row">
                                <div class="admin-form-group">
                                    <label>Monthly Price ($)</label>
                                    <input type="number" name="price_monthly" value="<?= $package['price_monthly'] ?>" step="0.01" min="0">
                                </div>
                                <div class="admin-form-group">
                                    <label>Yearly Price ($)</label>
                                    <input type="number" name="price_yearly" value="<?= $package['price_yearly'] ?>" step="0.01" min="0">
                                </div>
                            </div>
                            
                            <div class="admin-form-row">
                                <div class="admin-form-group">
                                    <label>Memory Limit (0 = unlimited)</label>
                                    <input type="number" name="memory_limit" value="<?= $package['memory_limit'] ?>" min="0">
                                </div>
                                <div class="admin-form-group">
                                    <label>Memory Expiry Days (0 = never)</label>
                                    <input type="number" name="memory_expiry_days" value="<?= $package['memory_expiry_days'] ?>" min="0">
                                </div>
                            </div>
                            
                            <div class="admin-form-row">
                                <div class="admin-form-group">
                                    <label>Voice Clone Limit</label>
                                    <input type="number" name="voice_clone_limit" value="<?= $package['voice_clone_limit'] ?>" min="0">
                                </div>
                                <div class="admin-form-group">
                                    <label>Max Audio Length (seconds)</label>
                                    <input type="number" name="max_audio_length_seconds" value="<?= $package['max_audio_length_seconds'] ?>" min="1" max="3600">
                                    <small style="color: #6b7280; font-size: 12px;">
                                        <?php 
                                        $minutes = floor($package['max_audio_length_seconds'] / 60);
                                        $seconds = $package['max_audio_length_seconds'] % 60;
                                        echo "({$minutes}m {$seconds}s)";
                                        ?>
                                    </small>
                                </div>
                            </div>
                            
                            <div class="admin-form-row">
                                <div class="admin-form-group">
                                    <label>Stripe Monthly Price ID</label>
                                    <input type="text" name="stripe_price_id_monthly" value="<?= htmlspecialchars($package['stripe_price_id_monthly']) ?>" placeholder="price_xxxxx">
                                </div>
                                <div class="admin-form-group">
                                    <label>Stripe Yearly Price ID</label>
                                    <input type="text" name="stripe_price_id_yearly" value="<?= htmlspecialchars($package['stripe_price_id_yearly']) ?>" placeholder="price_xxxxx">
                                </div>
                            </div>
                            
                            <div class="admin-form-group">
                                <div class="admin-checkbox-group">
                                    <input type="checkbox" name="is_active" <?= $package['is_active'] ? 'checked' : '' ?>>
                                    <label>Package is active</label>
                                </div>
                            </div>
                            
                            <button type="submit" class="admin-btn">Update Package</button>
                        </form>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>
    </div>
</body>
</html>

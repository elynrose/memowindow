<?php
require_once 'unified_auth.php';
require_once 'config.php';
require_once 'SubscriptionManager.php';

// Get current authenticated user
$currentUser = getCurrentUser();
if (!$currentUser) {
    // Redirect to login if not authenticated
    header('Location: login.php?error=login_required');
    exit;
}

$userId = $currentUser['uid'];
$subscriptionManager = new SubscriptionManager();
$packages = $subscriptionManager->getAvailablePackages();
$currentLimits = $subscriptionManager->getUserLimits($userId);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Choose Your Plan - MemoWindow</title>
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
            max-width: 1000px;
            margin: 0 auto;
        }
        
        .header {
            text-align: center;
            color: white;
            margin-bottom: 40px;
        }
        
        .header h1 {
            font-size: 3rem;
            margin-bottom: 10px;
        }
        
        .header p {
            font-size: 1.2rem;
            opacity: 0.9;
        }
        
        .current-plan {
            background: white;
            border-radius: 16px;
            padding: 20px;
            margin-bottom: 30px;
            box-shadow: 0 10px 30px rgba(0,0,0,0.1);
        }
        
        .current-plan h3 {
            color: #667eea;
            margin-bottom: 15px;
        }
        
        .usage-stats {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 15px;
        }
        
        .usage-stat {
            background: #f8f9fa;
            padding: 15px;
            border-radius: 8px;
            text-align: center;
        }
        
        .usage-stat .label {
            color: #666;
            font-size: 0.9rem;
            margin-bottom: 5px;
        }
        
        .usage-stat .value {
            font-size: 1.5rem;
            font-weight: bold;
            color: #333;
        }
        
        .packages-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
            gap: 30px;
        }
        
        .package-card {
            background: white;
            border-radius: 20px;
            padding: 30px;
            text-align: center;
            box-shadow: 0 10px 30px rgba(0,0,0,0.1);
            transition: all 0.3s ease;
            position: relative;
        }
        
        .package-card:hover {
            transform: translateY(-10px);
            box-shadow: 0 20px 40px rgba(0,0,0,0.15);
        }
        
        .package-card.popular {
            border: 3px solid #667eea;
            transform: scale(1.05);
        }
        
        .package-card.popular::before {
            content: "Most Popular";
            position: absolute;
            top: -15px;
            left: 50%;
            transform: translateX(-50%);
            background: #667eea;
            color: white;
            padding: 8px 20px;
            border-radius: 20px;
            font-size: 0.8rem;
            font-weight: bold;
        }
        
        .package-name {
            font-size: 1.8rem;
            font-weight: bold;
            color: #333;
            margin-bottom: 10px;
        }
        
        .package-price {
            font-size: 3rem;
            font-weight: bold;
            color: #667eea;
            margin-bottom: 5px;
        }
        
        .package-price .period {
            font-size: 1rem;
            color: #666;
            font-weight: normal;
        }
        
        .package-description {
            color: #666;
            margin-bottom: 25px;
            line-height: 1.5;
        }
        
        .package-features {
            text-align: left;
            margin-bottom: 30px;
        }
        
        .package-features ul {
            list-style: none;
            padding: 0;
        }
        
        .package-features li {
            padding: 8px 0;
            color: #333;
            position: relative;
            padding-left: 25px;
        }
        
        .package-features li:before {
            content: "✓";
            position: absolute;
            left: 0;
            color: #28a745;
            font-weight: bold;
        }
        
        .package-features li.unlimited:before {
            content: "∞";
            color: #667eea;
        }
        
        .btn {
            background: #667eea;
            color: white;
            border: none;
            padding: 15px 30px;
            border-radius: 50px;
            cursor: pointer;
            font-size: 1.1rem;
            font-weight: 600;
            transition: all 0.3s ease;
            text-decoration: none;
            display: inline-block;
            width: 100%;
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
        
        .btn-free {
            background: #28a745;
        }
        
        .btn-free:hover {
            background: #218838;
        }
        
        .savings {
            background: #d4edda;
            color: #155724;
            padding: 8px 15px;
            border-radius: 20px;
            font-size: 0.9rem;
            margin-bottom: 15px;
            display: inline-block;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1>Choose Your Plan</h1>
            <p>Unlock the full potential of MemoryWindow</p>
        </div>
        
        <div class="current-plan">
            <h3>Current Plan: <?= htmlspecialchars($currentLimits['package_name']) ?></h3>
            <div class="usage-stats">
                <div class="usage-stat">
                    <div class="label">Memories Used</div>
                    <div class="value"><?= $currentLimits['memory_used'] ?><?= $currentLimits['memory_limit'] > 0 ? '/' . $currentLimits['memory_limit'] : '' ?></div>
                </div>
                <div class="usage-stat">
                    <div class="label">Voice Clones Used</div>
                    <div class="value"><?= $currentLimits['voice_clone_used'] ?><?= $currentLimits['voice_clone_limit'] > 0 ? '/' . $currentLimits['voice_clone_limit'] : '' ?></div>
                </div>
            </div>
        </div>
        
        <div class="packages-grid">
            <?php foreach ($packages as $package): ?>
                <div class="package-card <?= $package['slug'] === 'standard' ? 'popular' : '' ?>">
                    <div class="package-name"><?= htmlspecialchars($package['name']) ?></div>
                    
                    <?php if ($package['price_monthly'] > 0): ?>
                        <div class="package-price">
                            $<?= number_format($package['price_monthly'], 0) ?>
                            <span class="period">/month</span>
                        </div>
                        <?php if ($package['price_yearly'] > 0): ?>
                            <div class="savings">
                                Save $<?= number_format(($package['price_monthly'] * 12) - $package['price_yearly'], 0) ?> with yearly billing
                            </div>
                        <?php endif; ?>
                    <?php else: ?>
                        <div class="package-price">
                            Free
                        </div>
                    <?php endif; ?>
                    
                    <div class="package-description">
                        <?= htmlspecialchars($package['description']) ?>
                    </div>
                    
                    <div class="package-features">
                        <ul>
                            <?php 
                            $features = json_decode($package['features'], true);
                            if ($features):
                                foreach ($features as $feature):
                            ?>
                                <li class="<?= strpos($feature, 'Unlimited') !== false ? 'unlimited' : '' ?>">
                                    <?= htmlspecialchars($feature) ?>
                                </li>
                            <?php 
                                endforeach;
                            endif;
                            ?>
                        </ul>
                    </div>
                    
                    <?php if ($package['price_monthly'] > 0): ?>
                        <a href="create_subscription_checkout.php?package_id=<?= $package['id'] ?>&billing=monthly" class="btn">
                            Start Monthly Plan
                        </a>
                        <?php if ($package['price_yearly'] > 0): ?>
                            <a href="create_subscription_checkout.php?package_id=<?= $package['id'] ?>&billing=yearly" class="btn btn-secondary" style="margin-top: 10px;">
                                Start Yearly Plan
                            </a>
                        <?php endif; ?>
                    <?php else: ?>
                        <a href="app.php" class="btn btn-free">
                            Continue with Free Plan
                        </a>
                    <?php endif; ?>
                </div>
            <?php endforeach; ?>
        </div>
    </div>
</body>
</html>

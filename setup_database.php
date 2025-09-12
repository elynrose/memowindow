<?php
// Database setup script - creates missing tables for MemoWindow
require_once 'config.php';

try {
    echo "<h1>MemoWindow Database Setup</h1>\n";
    echo "<pre>\n";
    
    $pdo = new PDO("mysql:host=" . DB_HOST . ";dbname=" . DB_NAME . ";charset=utf8mb4", DB_USER, DB_PASS, [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
    ]);
    
    echo "✅ Connected to database: " . DB_NAME . "\n\n";
    
    // 1. Create wave_assets table
    echo "Creating wave_assets table...\n";
    $pdo->exec("
        CREATE TABLE IF NOT EXISTS `wave_assets` (
            `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
            `user_id` VARCHAR(255) NOT NULL,
            `unique_id` VARCHAR(255) NULL,
            `title` VARCHAR(255) NULL,
            `original_name` VARCHAR(255) NULL,
            `image_url` VARCHAR(1024) NOT NULL,
            `qr_url` VARCHAR(1024) NULL,
            `audio_url` VARCHAR(1024) NULL,
            `play_url` VARCHAR(1024) NULL,
            `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            INDEX `idx_user_id` (`user_id`),
            INDEX `idx_unique_id` (`unique_id`),
            INDEX `idx_created_at` (`created_at`)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
    ");
    echo "✅ wave_assets table created\n";
    
    // 2. Create subscription_packages table
    echo "Creating subscription_packages table...\n";
    $pdo->exec("
        CREATE TABLE IF NOT EXISTS `subscription_packages` (
            `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
            `name` VARCHAR(255) NOT NULL,
            `slug` VARCHAR(100) NOT NULL UNIQUE,
            `description` TEXT NULL,
            `price_monthly` INT UNSIGNED DEFAULT 0 COMMENT 'Price in cents',
            `price_yearly` INT UNSIGNED DEFAULT 0 COMMENT 'Price in cents', 
            `memory_limit` INT UNSIGNED DEFAULT 10,
            `memory_expiry_days` INT UNSIGNED DEFAULT 365,
            `voice_clone_limit` INT UNSIGNED DEFAULT 0,
            `max_audio_length_seconds` INT UNSIGNED DEFAULT 60,
            `is_active` BOOLEAN DEFAULT TRUE,
            `sort_order` INT DEFAULT 0,
            `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            INDEX `idx_slug` (`slug`),
            INDEX `idx_active` (`is_active`)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
    ");
    echo "✅ subscription_packages table created\n";
    
    // 3. Insert basic package
    echo "Adding basic package...\n";
    $pdo->exec("
        INSERT INTO subscription_packages (name, slug, description, memory_limit, max_audio_length_seconds) 
        VALUES ('Basic (Free)', 'basic', 'Free tier with basic features', 10, 100)
        ON DUPLICATE KEY UPDATE name = VALUES(name);
    ");
    echo "✅ Basic package added\n";
    
    // 4. Create user_subscriptions table  
    echo "Creating user_subscriptions table...\n";
    $pdo->exec("
        CREATE TABLE IF NOT EXISTS `user_subscriptions` (
            `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
            `user_id` VARCHAR(255) NOT NULL,
            `package_id` INT UNSIGNED NOT NULL,
            `stripe_subscription_id` VARCHAR(255) NULL,
            `stripe_customer_id` VARCHAR(255) NULL,
            `status` ENUM('active', 'canceled', 'expired', 'past_due') DEFAULT 'active',
            `current_period_start` TIMESTAMP NULL,
            `current_period_end` TIMESTAMP NULL,
            `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            INDEX `idx_user_id` (`user_id`),
            INDEX `idx_status` (`status`),
            FOREIGN KEY (`package_id`) REFERENCES `subscription_packages`(`id`) ON DELETE CASCADE
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
    ");
    echo "✅ user_subscriptions table created\n";
    
    // 5. Create admin_users table (if needed)
    echo "Creating admin_users table...\n"; 
    $pdo->exec("
        CREATE TABLE IF NOT EXISTS `admin_users` (
            `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
            `firebase_uid` VARCHAR(255) NOT NULL UNIQUE,
            `email` VARCHAR(255) NOT NULL,
            `name` VARCHAR(255) NULL,
            `is_admin` BOOLEAN DEFAULT FALSE,
            `last_login_at` TIMESTAMP NULL,
            `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            INDEX `idx_firebase_uid` (`firebase_uid`),
            INDEX `idx_admin` (`is_admin`)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
    ");
    echo "✅ admin_users table created\n";
    
    // 6. Check existing data
    $count = $pdo->query("SELECT COUNT(*) FROM wave_assets")->fetchColumn();
    echo "\n📊 Current wave_assets records: $count\n";
    
    $packages = $pdo->query("SELECT COUNT(*) FROM subscription_packages")->fetchColumn();
    echo "📊 Subscription packages: $packages\n";
    
    echo "\n🎉 Database setup completed successfully!\n";
    echo "You can now test the upload functionality.\n";
    echo "</pre>\n";
    
} catch (Exception $e) {
    echo "<pre style='color: red;'>\n";
    echo "❌ Database setup failed: " . $e->getMessage() . "\n";
    echo "\nPlease check:\n";
    echo "1. MySQL server is running\n";
    echo "2. Database credentials in config.php are correct\n";
    echo "3. Database '" . DB_NAME . "' exists\n";
    echo "</pre>\n";
}
?>

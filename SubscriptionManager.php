<?php
require_once 'config.php';

class SubscriptionManager {
    private $pdo;
    
    public function __construct() {
        $this->pdo = new PDO("mysql:host=" . DB_HOST . ";dbname=" . DB_NAME, DB_USER, DB_PASS, [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        ]);
    }
    
    /**
     * Get PDO connection for external use
     */
    public function getPdo() {
        return $this->pdo;
    }
    
    /**
     * Get user's current subscription
     */
    public function getUserSubscription($userId) {
        $stmt = $this->pdo->prepare("
            SELECT us.*, sp.name as package_name, sp.slug as package_slug,
                   sp.memory_limit, sp.memory_expiry_days, sp.voice_clone_limit,
                   sp.price_monthly, sp.price_yearly
            FROM user_subscriptions us
            JOIN subscription_packages sp ON us.package_id = sp.id
            WHERE us.user_id = ? AND us.status = 'active'
            ORDER BY us.created_at DESC
            LIMIT 1
        ");
        $stmt->execute([$userId]);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }
    
    /**
     * Get user's memory count
     */
    public function getUserMemoryCount($userId) {
        $stmt = $this->pdo->prepare("SELECT COUNT(*) as count FROM wave_assets WHERE user_id = ?");
        $stmt->execute([$userId]);
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        return (int)$result['count'];
    }
    
    /**
     * Get user's voice clone count
     */
    public function getUserVoiceCloneCount($userId) {
        $stmt = $this->pdo->prepare("SELECT COUNT(*) as count FROM voice_clones WHERE user_id = ?");
        $stmt->execute([$userId]);
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        return (int)$result['count'];
    }
    
    /**
     * Check if user can create a new memory
     */
    public function canCreateMemory($userId) {
        $subscription = $this->getUserSubscription($userId);
        $memoryCount = $this->getUserMemoryCount($userId);
        
        if (!$subscription) {
            // No subscription - check if they can use free tier
            $freePackage = $this->getPackageBySlug('basic');
            if ($freePackage && $memoryCount < $freePackage['memory_limit']) {
                return ['allowed' => true, 'reason' => 'Free tier available'];
            }
            return ['allowed' => false, 'reason' => 'Memory limit reached. Please upgrade your subscription.'];
        }
        
        // Check memory limit
        if ($subscription['memory_limit'] > 0 && $memoryCount >= $subscription['memory_limit']) {
            return ['allowed' => false, 'reason' => 'Memory limit reached for your current plan.'];
        }
        
        return ['allowed' => true, 'reason' => 'Memory creation allowed'];
    }
    
    /**
     * Check if user can create a voice clone
     */
    public function canCreateVoiceClone($userId) {
        $subscription = $this->getUserSubscription($userId);
        $voiceCloneCount = $this->getUserVoiceCloneCount($userId);
        
        if (!$subscription) {
            return ['allowed' => false, 'reason' => 'Voice cloning requires a paid subscription.'];
        }
        
        // Check voice clone limit
        if ($subscription['voice_clone_limit'] > 0 && $voiceCloneCount >= $subscription['voice_clone_limit']) {
            return ['allowed' => false, 'reason' => 'Voice clone limit reached for your current plan.'];
        }
        
        return ['allowed' => true, 'reason' => 'Voice clone creation allowed'];
    }
    
    /**
     * Get all available packages
     */
    public function getAvailablePackages() {
        $stmt = $this->pdo->query("SELECT * FROM subscription_packages WHERE is_active = 1 ORDER BY sort_order");
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
    
    /**
     * Get package by slug
     */
    public function getPackageBySlug($slug) {
        $stmt = $this->pdo->prepare("SELECT * FROM subscription_packages WHERE slug = ? AND is_active = 1");
        $stmt->execute([$slug]);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }
    
    /**
     * Create or update user subscription
     */
    public function createOrUpdateSubscription($userId, $packageId, $stripeSubscriptionId = null, $stripeCustomerId = null, $status = 'active') {
        // Check if user already has an active subscription
        $existing = $this->getUserSubscription($userId);
        
        if ($existing) {
            // Update existing subscription
            $stmt = $this->pdo->prepare("
                UPDATE user_subscriptions 
                SET package_id = ?, stripe_subscription_id = ?, stripe_customer_id = ?, 
                    status = ?, updated_at = CURRENT_TIMESTAMP
                WHERE user_id = ? AND status = 'active'
            ");
            $stmt->execute([$packageId, $stripeSubscriptionId, $stripeCustomerId, $status, $userId]);
        } else {
            // Create new subscription
            $stmt = $this->pdo->prepare("
                INSERT INTO user_subscriptions (user_id, package_id, stripe_subscription_id, stripe_customer_id, status)
                VALUES (?, ?, ?, ?, ?)
            ");
            $stmt->execute([$userId, $packageId, $stripeSubscriptionId, $stripeCustomerId, $status]);
        }
    }
    
    /**
     * Cancel user subscription
     */
    public function cancelSubscription($userId) {
        $stmt = $this->pdo->prepare("
            UPDATE user_subscriptions 
            SET status = 'canceled', updated_at = CURRENT_TIMESTAMP
            WHERE user_id = ? AND status = 'active'
        ");
        $stmt->execute([$userId]);
    }
    
    /**
     * Get user's subscription limits and usage
     */
    public function getUserLimits($userId) {
        $subscription = $this->getUserSubscription($userId);
        $memoryCount = $this->getUserMemoryCount($userId);
        $voiceCloneCount = $this->getUserVoiceCloneCount($userId);
        
        if (!$subscription) {
            // Return free tier limits
            $freePackage = $this->getPackageBySlug('basic');
            return [
                'package_name' => 'Basic (Free)',
                'memory_limit' => $freePackage['memory_limit'],
                'memory_used' => $memoryCount,
                'voice_clone_limit' => $freePackage['voice_clone_limit'],
                'voice_clone_used' => $voiceCloneCount,
                'can_create_memory' => $this->canCreateMemory($userId),
                'can_create_voice_clone' => $this->canCreateVoiceClone($userId)
            ];
        }
        
        return [
            'package_name' => $subscription['package_name'],
            'memory_limit' => $subscription['memory_limit'],
            'memory_used' => $memoryCount,
            'voice_clone_limit' => $subscription['voice_clone_limit'],
            'voice_clone_used' => $voiceCloneCount,
            'can_create_memory' => $this->canCreateMemory($userId),
            'can_create_voice_clone' => $this->canCreateVoiceClone($userId),
            'subscription_status' => $subscription['status'],
            'current_period_end' => $subscription['current_period_end']
        ];
    }
    
    /**
     * Check if user's memories should expire
     */
    public function shouldExpireMemories($userId) {
        $subscription = $this->getUserSubscription($userId);
        
        if (!$subscription) {
            // Free tier - memories expire after 1 year
            return true;
        }
        
        // Paid subscription - check if memories never expire
        return $subscription['memory_expiry_days'] > 0;
    }
    
    /**
     * Get memory expiry date for user
     */
    public function getMemoryExpiryDate($userId) {
        $subscription = $this->getUserSubscription($userId);
        
        if (!$subscription) {
            // Free tier - 1 year from creation
            return date('Y-m-d H:i:s', strtotime('+1 year'));
        }
        
        if ($subscription['memory_expiry_days'] == 0) {
            // Never expire
            return null;
        }
        
        // Custom expiry period
        return date('Y-m-d H:i:s', strtotime('+' . $subscription['memory_expiry_days'] . ' days'));
    }
}
?>

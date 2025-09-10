<?php
/**
 * Voice Clone Settings Management
 * Handles admin settings and user usage tracking for voice cloning
 */

require_once 'config.php';

class VoiceCloneSettings {
    private $pdo;
    
    public function __construct() {
        $this->pdo = new PDO("mysql:host=" . DB_HOST . ";dbname=" . DB_NAME . ";charset=utf8mb4", DB_USER, DB_PASS, [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        ]);
    }
    
    /**
     * Get a setting value
     */
    public function getSetting($key, $default = null) {
        $stmt = $this->pdo->prepare("SELECT setting_value FROM voice_clone_settings WHERE setting_key = ?");
        $stmt->execute([$key]);
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        return $result ? $result['setting_value'] : $default;
    }
    
    /**
     * Set a setting value
     */
    public function setSetting($key, $value) {
        $stmt = $this->pdo->prepare("
            INSERT INTO voice_clone_settings (setting_key, setting_value) 
            VALUES (?, ?) 
            ON DUPLICATE KEY UPDATE setting_value = VALUES(setting_value)
        ");
        return $stmt->execute([$key, $value]);
    }
    
    /**
     * Check if voice cloning is enabled
     */
    public function isEnabled() {
        return $this->getSetting('voice_clone_enabled', '0') === '1';
    }
    
    /**
     * Get monthly limit
     */
    public function getMonthlyLimit() {
        return intval($this->getSetting('voice_clone_monthly_limit', '3'));
    }
    
    /**
     * Check if subscription is required
     */
    public function requiresSubscription() {
        return $this->getSetting('voice_clone_requires_subscription', '1') === '1';
    }
    
    /**
     * Get user's current month usage
     */
    public function getUserUsage($userId) {
        $monthYear = date('Y-m');
        $stmt = $this->pdo->prepare("
            SELECT clone_count FROM voice_clone_usage 
            WHERE user_id = ? AND month_year = ?
        ");
        $stmt->execute([$userId, $monthYear]);
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        return $result ? intval($result['clone_count']) : 0;
    }
    
    /**
     * Check if user can create a voice clone
     */
    public function canCreateClone($userId, $hasSubscription = false) {
        // Check if feature is enabled
        if (!$this->isEnabled()) {
            return ['allowed' => false, 'reason' => 'Voice cloning is currently disabled'];
        }
        
        // Check subscription requirement
        if ($this->requiresSubscription() && !$hasSubscription) {
            return ['allowed' => false, 'reason' => 'Voice cloning requires an active subscription'];
        }
        
        // Check monthly limit
        $currentUsage = $this->getUserUsage($userId);
        $monthlyLimit = $this->getMonthlyLimit();
        
        if ($currentUsage >= $monthlyLimit) {
            return ['allowed' => false, 'reason' => "Monthly limit reached ({$currentUsage}/{$monthlyLimit})"];
        }
        
        return ['allowed' => true, 'usage' => $currentUsage, 'limit' => $monthlyLimit];
    }
    
    /**
     * Increment user's usage count
     */
    public function incrementUsage($userId) {
        $monthYear = date('Y-m');
        $stmt = $this->pdo->prepare("
            INSERT INTO voice_clone_usage (user_id, month_year, clone_count) 
            VALUES (?, ?, 1) 
            ON DUPLICATE KEY UPDATE clone_count = clone_count + 1
        ");
        return $stmt->execute([$userId, $monthYear]);
    }
    
    /**
     * Get all settings for admin interface
     */
    public function getAllSettings() {
        $stmt = $this->pdo->query("SELECT setting_key, setting_value FROM voice_clone_settings");
        $settings = [];
        while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
            $settings[$row['setting_key']] = $row['setting_value'];
        }
        return $settings;
    }
    
    /**
     * Update multiple settings
     */
    public function updateSettings($settings) {
        $this->pdo->beginTransaction();
        try {
            foreach ($settings as $key => $value) {
                $this->setSetting($key, $value);
            }
            $this->pdo->commit();
            return true;
        } catch (Exception $e) {
            $this->pdo->rollBack();
            throw $e;
        }
    }
    
    /**
     * Get usage statistics for admin
     */
    public function getUsageStats($monthYear = null) {
        if (!$monthYear) {
            $monthYear = date('Y-m');
        }
        
        $stmt = $this->pdo->prepare("
            SELECT 
                COUNT(*) as total_users,
                SUM(clone_count) as total_clones,
                AVG(clone_count) as avg_clones_per_user
            FROM voice_clone_usage 
            WHERE month_year = ?
        ");
        $stmt->execute([$monthYear]);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }
    
    /**
     * Get top users by usage
     */
    public function getTopUsers($monthYear = null, $limit = 10) {
        if (!$monthYear) {
            $monthYear = date('Y-m');
        }
        
        $stmt = $this->pdo->prepare("
            SELECT user_id, clone_count 
            FROM voice_clone_usage 
            WHERE month_year = ? 
            ORDER BY clone_count DESC 
            LIMIT " . intval($limit)
        );
        $stmt->execute([$monthYear]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
}
?>

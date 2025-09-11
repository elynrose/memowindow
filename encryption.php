<?php
/**
 * Encryption Helper for MemoWindow
 * Provides encryption/decryption for sensitive data
 */

class DataEncryption {
    private $key;
    private $cipher = 'AES-256-GCM';
    
    public function __construct($key = null) {
        // Use provided key or generate one from config
        if ($key) {
            $this->key = $key;
        } else {
            // Generate key from config or use default
            $this->key = $this->generateKey();
        }
    }
    
    /**
     * Generate encryption key
     */
    private function generateKey() {
        // In production, this should be stored securely
        $configKey = defined('ENCRYPTION_KEY') ? ENCRYPTION_KEY : 'default_key_change_in_production';
        return hash('sha256', $configKey, true);
    }
    
    /**
     * Encrypt data
     */
    public function encrypt($data) {
        if (empty($data)) {
            return $data;
        }
        
        $iv = random_bytes(12); // 96-bit IV for GCM
        $tag = '';
        
        $encrypted = openssl_encrypt(
            $data,
            $this->cipher,
            $this->key,
            OPENSSL_RAW_DATA,
            $iv,
            $tag
        );
        
        if ($encrypted === false) {
            throw new Exception('Encryption failed');
        }
        
        // Combine IV, tag, and encrypted data
        return base64_encode($iv . $tag . $encrypted);
    }
    
    /**
     * Decrypt data
     */
    public function decrypt($encryptedData) {
        if (empty($encryptedData)) {
            return $encryptedData;
        }
        
        $data = base64_decode($encryptedData);
        if ($data === false) {
            throw new Exception('Invalid encrypted data');
        }
        
        // Extract IV, tag, and encrypted data
        $iv = substr($data, 0, 12);
        $tag = substr($data, 12, 16);
        $encrypted = substr($data, 28);
        
        $decrypted = openssl_decrypt(
            $encrypted,
            $this->cipher,
            $this->key,
            OPENSSL_RAW_DATA,
            $iv,
            $tag
        );
        
        if ($decrypted === false) {
            throw new Exception('Decryption failed');
        }
        
        return $decrypted;
    }
    
    /**
     * Hash password securely
     */
    public function hashPassword($password) {
        return password_hash($password, PASSWORD_ARGON2ID, [
            'memory_cost' => 65536, // 64 MB
            'time_cost' => 4,       // 4 iterations
            'threads' => 3          // 3 threads
        ]);
    }
    
    /**
     * Verify password
     */
    public function verifyPassword($password, $hash) {
        return password_verify($password, $hash);
    }
    
    /**
     * Generate secure random token
     */
    public function generateToken($length = 32) {
        return bin2hex(random_bytes($length));
    }
    
    /**
     * Generate secure random string
     */
    public function generateSecureString($length = 16) {
        $characters = '0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ';
        $string = '';
        
        for ($i = 0; $i < $length; $i++) {
            $string .= $characters[random_int(0, strlen($characters) - 1)];
        }
        
        return $string;
    }
}

/**
 * Sensitive data encryption for database storage
 */
class SensitiveDataEncryption {
    private $encryption;
    
    public function __construct() {
        $this->encryption = new DataEncryption();
    }
    
    /**
     * Encrypt user email
     */
    public function encryptEmail($email) {
        return $this->encryption->encrypt($email);
    }
    
    /**
     * Decrypt user email
     */
    public function decryptEmail($encryptedEmail) {
        return $this->encryption->decrypt($encryptedEmail);
    }
    
    /**
     * Encrypt user name
     */
    public function encryptName($name) {
        return $this->encryption->encrypt($name);
    }
    
    /**
     * Decrypt user name
     */
    public function decryptName($encryptedName) {
        return $this->encryption->decrypt($encryptedName);
    }
    
    /**
     * Encrypt API keys
     */
    public function encryptAPIKey($apiKey) {
        return $this->encryption->encrypt($apiKey);
    }
    
    /**
     * Decrypt API keys
     */
    public function decryptAPIKey($encryptedAPIKey) {
        return $this->encryption->decrypt($encryptedAPIKey);
    }
    
    /**
     * Encrypt payment information
     */
    public function encryptPaymentInfo($paymentInfo) {
        return $this->encryption->encrypt(json_encode($paymentInfo));
    }
    
    /**
     * Decrypt payment information
     */
    public function decryptPaymentInfo($encryptedPaymentInfo) {
        $decrypted = $this->encryption->decrypt($encryptedPaymentInfo);
        return json_decode($decrypted, true);
    }
}

/**
 * Helper functions for encryption
 */

/**
 * Encrypt sensitive data before database storage
 */
function encryptSensitiveData($data, $type = 'general') {
    $encryption = new DataEncryption();
    
    switch ($type) {
        case 'email':
            return $encryption->encrypt($data);
        case 'name':
            return $encryption->encrypt($data);
        case 'api_key':
            return $encryption->encrypt($data);
        case 'payment':
            return $encryption->encrypt(json_encode($data));
        default:
            return $encryption->encrypt($data);
    }
}

/**
 * Decrypt sensitive data after database retrieval
 */
function decryptSensitiveData($encryptedData, $type = 'general') {
    if (empty($encryptedData)) {
        return $encryptedData;
    }
    
    $encryption = new DataEncryption();
    
    try {
        switch ($type) {
            case 'email':
                return $encryption->decrypt($encryptedData);
            case 'name':
                return $encryption->decrypt($encryptedData);
            case 'api_key':
                return $encryption->decrypt($encryptedData);
            case 'payment':
                $decrypted = $encryption->decrypt($encryptedData);
                return json_decode($decrypted, true);
            default:
                return $encryption->decrypt($encryptedData);
        }
    } catch (Exception $e) {
        // Log error but return original data
        error_log("Decryption failed: " . $e->getMessage());
        return $encryptedData;
    }
}

/**
 * Generate secure CSRF token
 */
function generateSecureCSRFToken() {
    $encryption = new DataEncryption();
    return $encryption->generateToken(32);
}

/**
 * Generate secure session ID
 */
function generateSecureSessionID() {
    $encryption = new DataEncryption();
    return $encryption->generateSecureString(32);
}
?>

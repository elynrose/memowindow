<?php
/**
 * Rate Limiter for MemoWindow
 * Provides rate limiting functionality to prevent API abuse
 */

class RateLimiter {
    public $maxRequests;
    public $timeWindow;
    private $storageDir;
    
    public function __construct($maxRequests = 100, $timeWindow = 3600) {
        $this->maxRequests = $maxRequests;
        $this->timeWindow = $timeWindow;
        $this->storageDir = __DIR__ . '/rate_limit_storage';
        
        // Ensure storage directory exists
        if (!is_dir($this->storageDir)) {
            mkdir($this->storageDir, 0755, true);
        }
    }
    
    /**
     * Check if request is allowed for given identifier
     */
    public function isAllowed($identifier) {
        $key = $this->getKey($identifier);
        $file = $this->storageDir . '/' . $key;
        
        $now = time();
        $requests = [];
        
        // Load existing requests
        if (file_exists($file)) {
            $data = file_get_contents($file);
            $requests = json_decode($data, true) ?: [];
        }
        
        // Remove old requests outside time window
        $requests = array_filter($requests, function($timestamp) use ($now) {
            return ($now - $timestamp) < $this->timeWindow;
        });
        
        // Check if limit exceeded
        if (count($requests) >= $this->maxRequests) {
            return false;
        }
        
        // Add current request
        $requests[] = $now;
        
        // Save updated requests
        file_put_contents($file, json_encode($requests));
        
        return true;
    }
    
    /**
     * Get remaining requests for identifier
     */
    public function getRemainingRequests($identifier) {
        $key = $this->getKey($identifier);
        $file = $this->storageDir . '/' . $key;
        
        $now = time();
        $requests = [];
        
        if (file_exists($file)) {
            $data = file_get_contents($file);
            $requests = json_decode($data, true) ?: [];
        }
        
        // Remove old requests
        $requests = array_filter($requests, function($timestamp) use ($now) {
            return ($now - $timestamp) < $this->timeWindow;
        });
        
        return max(0, $this->maxRequests - count($requests));
    }
    
    /**
     * Get reset time for identifier
     */
    public function getResetTime($identifier) {
        $key = $this->getKey($identifier);
        $file = $this->storageDir . '/' . $key;
        
        if (!file_exists($file)) {
            return time() + $this->timeWindow;
        }
        
        $data = file_get_contents($file);
        $requests = json_decode($data, true) ?: [];
        
        if (empty($requests)) {
            return time() + $this->timeWindow;
        }
        
        $oldestRequest = min($requests);
        return $oldestRequest + $this->timeWindow;
    }
    
    /**
     * Clean up old rate limit files
     */
    public function cleanup() {
        $files = glob($this->storageDir . '/*');
        $now = time();
        
        foreach ($files as $file) {
            if (is_file($file) && ($now - filemtime($file)) > $this->timeWindow * 2) {
                unlink($file);
            }
        }
    }
    
    /**
     * Generate key for identifier
     */
    private function getKey($identifier) {
        return hash('sha256', $identifier);
    }
}

/**
 * API Rate Limiter - More restrictive for API endpoints
 */
class APIRateLimiter extends RateLimiter {
    public function __construct() {
        parent::__construct(60, 3600); // 60 requests per hour
    }
}

/**
 * Upload Rate Limiter - Very restrictive for uploads
 */
class UploadRateLimiter extends RateLimiter {
    public function __construct() {
        parent::__construct(10, 3600); // 10 uploads per hour
    }
}

/**
 * Login Rate Limiter - Prevents brute force attacks
 */
class LoginRateLimiter extends RateLimiter {
    public function __construct() {
        parent::__construct(5, 900); // 5 login attempts per 15 minutes
    }
}

/**
 * Helper function to get client identifier
 */
function getClientIdentifier() {
    // Use IP address as primary identifier
    $ip = $_SERVER['REMOTE_ADDR'] ?? 'unknown';
    
    // Add user agent hash for additional uniqueness
    $userAgent = $_SERVER['HTTP_USER_AGENT'] ?? '';
    $userAgentHash = substr(hash('sha256', $userAgent), 0, 8);
    
    return $ip . '_' . $userAgentHash;
}

/**
 * Helper function to check rate limit and return appropriate response
 */
function checkRateLimit($rateLimiter, $identifier = null) {
    if (!$identifier) {
        $identifier = getClientIdentifier();
    }
    
    if (!$rateLimiter->isAllowed($identifier)) {
        $resetTime = $rateLimiter->getResetTime($identifier);
        $retryAfter = $resetTime - time();
        
        http_response_code(429);
        header('Retry-After: ' . $retryAfter);
        header('X-RateLimit-Limit: ' . $rateLimiter->maxRequests);
        header('X-RateLimit-Remaining: 0');
        header('X-RateLimit-Reset: ' . $resetTime);
        
        echo json_encode([
            'error' => 'Rate limit exceeded',
            'retry_after' => $retryAfter,
            'limit' => $rateLimiter->maxRequests,
            'reset_time' => $resetTime
        ]);
        
        exit;
    }
    
    // Add rate limit headers to response
    $remaining = $rateLimiter->getRemainingRequests($identifier);
    $resetTime = $rateLimiter->getResetTime($identifier);
    
    header('X-RateLimit-Limit: ' . $rateLimiter->maxRequests);
    header('X-RateLimit-Remaining: ' . $remaining);
    header('X-RateLimit-Reset: ' . $resetTime);
}
?>

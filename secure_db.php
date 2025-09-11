<?php
/**
 * Secure Database Helper for MemoWindow
 * Provides secure database operations with prepared statements
 */

require_once 'config.php';

class SecureDB {
    private $pdo;
    private static $instance = null;
    
    private function __construct() {
        try {
            $this->pdo = new PDO(
                "mysql:host=" . DB_HOST . ";dbname=" . DB_NAME . ";charset=utf8mb4", 
                DB_USER, 
                DB_PASS, 
                [
                    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                    PDO::ATTR_EMULATE_PREPARES => false,
                ]
            );
        } catch (PDOException $e) {
            error_log("Database connection failed: " . $e->getMessage());
            throw new Exception("Database connection failed");
        }
    }
    
    public static function getInstance() {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }
    
    /**
     * Execute a prepared statement safely
     */
    public function query($sql, $params = []) {
        try {
            $stmt = $this->pdo->prepare($sql);
            $stmt->execute($params);
            return $stmt;
        } catch (PDOException $e) {
            error_log("Database query failed: " . $e->getMessage() . " SQL: " . $sql);
            throw new Exception("Database query failed");
        }
    }
    
    /**
     * Get single row
     */
    public function fetchOne($sql, $params = []) {
        $stmt = $this->query($sql, $params);
        return $stmt->fetch();
    }
    
    /**
     * Get all rows
     */
    public function fetchAll($sql, $params = []) {
        $stmt = $this->query($sql, $params);
        return $stmt->fetchAll();
    }
    
    /**
     * Insert and return last insert ID
     */
    public function insert($sql, $params = []) {
        $this->query($sql, $params);
        return $this->pdo->lastInsertId();
    }
    
    /**
     * Update/Delete and return affected rows
     */
    public function execute($sql, $params = []) {
        $stmt = $this->query($sql, $params);
        return $stmt->rowCount();
    }
    
    /**
     * Get count safely
     */
    public function getCount($table, $where = '', $params = []) {
        $sql = "SELECT COUNT(*) as count FROM `" . $this->sanitizeTableName($table) . "`";
        if ($where) {
            $sql .= " WHERE " . $where;
        }
        
        $result = $this->fetchOne($sql, $params);
        return (int)$result['count'];
    }
    
    /**
     * Sanitize table name to prevent injection
     */
    private function sanitizeTableName($table) {
        // Only allow alphanumeric characters and underscores
        if (!preg_match('/^[a-zA-Z0-9_]+$/', $table)) {
            throw new Exception("Invalid table name: $table");
        }
        
        // Whitelist allowed tables
        $allowedTables = [
            'users', 'admin_users', 'wave_assets', 'orders', 'print_products',
            'subscription_packages', 'user_subscriptions', 'voice_clone_settings',
            'audio_backups', 'waveforms'
        ];
        
        if (!in_array($table, $allowedTables)) {
            throw new Exception("Table not allowed: $table");
        }
        
        return $table;
    }
    
    /**
     * Begin transaction
     */
    public function beginTransaction() {
        return $this->pdo->beginTransaction();
    }
    
    /**
     * Commit transaction
     */
    public function commit() {
        return $this->pdo->commit();
    }
    
    /**
     * Rollback transaction
     */
    public function rollback() {
        return $this->pdo->rollback();
    }
    
    /**
     * Get PDO instance for complex operations
     */
    public function getPDO() {
        return $this->pdo;
    }
}

/**
 * Helper functions for common database operations
 */

/**
 * Get user by Firebase UID
 */
function getUserByFirebaseUID($firebaseUID) {
    $db = SecureDB::getInstance();
    return $db->fetchOne("SELECT * FROM users WHERE firebase_uid = ?", [$firebaseUID]);
}

/**
 * Get admin user by Firebase UID
 */
function getAdminByFirebaseUID($firebaseUID) {
    $db = SecureDB::getInstance();
    return $db->fetchOne("SELECT * FROM admin_users WHERE firebase_uid = ?", [$firebaseUID]);
}

/**
 * Get user's memories
 */
function getUserMemories($userId, $limit = 10, $offset = 0) {
    $db = SecureDB::getInstance();
    return $db->fetchAll(
        "SELECT * FROM wave_assets WHERE user_id = ? ORDER BY created_at DESC LIMIT ? OFFSET ?",
        [$userId, $limit, $offset]
    );
}

/**
 * Get user's orders
 */
function getUserOrders($userId, $limit = 10, $offset = 0) {
    $db = SecureDB::getInstance();
    return $db->fetchAll(
        "SELECT * FROM orders WHERE user_id = ? ORDER BY created_at DESC LIMIT ? OFFSET ?",
        [$userId, $limit, $offset]
    );
}

/**
 * Get dashboard statistics safely
 */
function getDashboardStats() {
    $db = SecureDB::getInstance();
    
    return [
        'total_users' => $db->getCount('users'),
        'total_memories' => $db->getCount('wave_assets'),
        'total_orders' => $db->getCount('orders'),
        'total_revenue' => $db->fetchOne("SELECT SUM(amount_paid) as total FROM orders WHERE amount_paid > 0")['total'] ?? 0
    ];
}

/**
 * Create user safely
 */
function createUser($firebaseUID, $email = null, $name = null) {
    $db = SecureDB::getInstance();
    
    // Check if user already exists
    $existing = getUserByFirebaseUID($firebaseUID);
    if ($existing) {
        return $existing['id'];
    }
    
    // Use display_name as fallback for name
    $displayName = $name ?: 'User';
    
    return $db->insert(
        "INSERT INTO users (firebase_uid, email, name, password, display_name, created_at, updated_at) VALUES (?, ?, ?, ?, ?, NOW(), NOW())",
        [$firebaseUID, $email, $name, 'temp_password', $displayName]
    );
}

/**
 * Update user last login
 */
function updateUserLastLogin($firebaseUID) {
    $db = SecureDB::getInstance();
    return $db->execute(
        "UPDATE users SET last_login_at = NOW() WHERE firebase_uid = ?",
        [$firebaseUID]
    );
}

/**
 * Update admin last login
 */
function updateAdminLastLogin($firebaseUID) {
    $db = SecureDB::getInstance();
    return $db->execute(
        "UPDATE admin_users SET last_login_at = NOW() WHERE firebase_uid = ?",
        [$firebaseUID]
    );
}
?>

<?php
// admin.php - MemoWindow Admin Dashboard
require_once 'unified_auth.php';
require_once 'secure_db.php';

// Require admin authentication
$currentUser = requireAdmin();
$userFirebaseUID = $currentUser['uid'];

// User is already verified as admin by requireAdmin()
// Update last login using secure database helper
try {
    updateAdminLastLogin($userFirebaseUID);
} catch (Exception $e) {
    // Continue even if update fails
    error_log("Failed to update admin last login: " . $e->getMessage());
}

// Get dashboard data using secure database helper
try {
    $db = SecureDB::getInstance();
    
    // Get statistics using secure methods
    $stats = getDashboardStats();
    
    // Recent memories using secure database helper
    $recentMemories = $db->fetchAll("
        SELECT 
            w.id,
            w.title,
            w.original_name,
            w.image_url,
            w.created_at,
            SUBSTRING(w.user_id, 1, 10) as user_short
        FROM wave_assets w 
        ORDER BY w.created_at DESC 
        LIMIT 10
    ");
    
    // Recent orders using secure database helper
    $recentOrders = $db->fetchAll("
        SELECT 
            o.*,
            p.name as product_name
        FROM orders o
        LEFT JOIN print_products p ON o.product_variant_id = p.product_key
        ORDER BY o.created_at DESC 
        LIMIT 10
    ");
    
    // Daily stats for last 7 days using secure database helper
    $dailyStats = $db->fetchAll("
        SELECT 
            DATE(created_at) as date,
            COUNT(*) as memories_created,
            COUNT(DISTINCT user_id) as active_users
        FROM wave_assets 
        WHERE created_at >= DATE_SUB(NOW(), INTERVAL 7 DAY)
        GROUP BY DATE(created_at)
        ORDER BY date DESC
    ");
    
} catch (Exception $e) {
    // Log error but don't expose details to user
    error_log("Admin dashboard error: " . $e->getMessage());
    $stats = ['error' => 'Failed to load dashboard data'];
    $recentMemories = [];
    $recentOrders = [];
    $dailyStats = [];
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Dashboard - MemoWindow</title>
    <link rel="stylesheet" href="includes/admin_styles.css">
    <style>
        /* Page-specific styles */
        .admin-actions-grid {
            display: grid;
            grid-template-columns: repeat(3, 1fr);
            gap: 16px;
            margin-top: 24px;
        }
        
        .memory-thumb {
            width: 60px;
            height: 40px;
            object-fit: cover;
            border-radius: 4px;
            border: 1px solid #e2e8f0;
        }
        
        .user-id {
            font-family: monospace;
            background: #f1f5f9;
            padding: 2px 6px;
            border-radius: 4px;
            font-size: 12px;
        }
        
        @media (max-width: 768px) {
            .admin-actions-grid {
                grid-template-columns: repeat(2, 1fr);
            }
        }
        
        @media (max-width: 480px) {
            .admin-actions-grid {
                grid-template-columns: 1fr;
            }
        }
    </style>
</head>
<body>
    <div class="admin-container">
        <div class="admin-header">
            <h1>📊 MemoWindow Admin Dashboard</h1>
            <p>Manage users, orders, and analytics</p>
        </div>
        
        <?php include 'includes/admin_navigation.php'; ?>

        <div class="admin-content">
            <!-- Statistics Cards -->
            <div class="admin-stats-grid">
                <div class="admin-stat-card">
                    <div class="number"><?php echo number_format($stats['total_users']); ?></div>
                    <p>Total Users</p>
                </div>
                <div class="admin-stat-card">
                    <div class="number"><?php echo number_format($stats['total_memories']); ?></div>
                    <p>Memories Created</p>
                </div>
                <div class="admin-stat-card">
                    <div class="number"><?php echo number_format($stats['total_orders']); ?></div>
                    <p>Total Orders</p>
                </div>
                <div class="admin-stat-card">
                    <div class="number">$<?php echo number_format($stats['total_revenue'] / 100, 2); ?></div>
                    <p>Total Revenue</p>
                </div>
            </div>

            <!-- Admin Actions -->
            <div class="admin-card">
                <div class="admin-card-header">
                    <h2>⚙️ Admin Actions</h2>
                    <p>Quick access to all admin functions</p>
                </div>
                <div class="admin-actions-grid">
                    <button onclick="exportData('memories')" class="admin-btn">
                        📊 Export Memories
                    </button>
                    <button onclick="exportData('orders')" class="admin-btn">
                        📈 Export Orders
                    </button>
                    <a href="analytics.php?user_id=<?php echo urlencode($userFirebaseUID); ?>" class="admin-btn">
                        📈 Analytics
                    </a>
                    <a href="admin_users.php?user_id=<?php echo urlencode($userFirebaseUID); ?>" class="admin-btn">
                        👥 Manage Users
                    </a>
                    <a href="admin_backups.php?user_id=<?php echo urlencode($userFirebaseUID); ?>" class="admin-btn">
                        🔒 Audio Backups
                    </a>
                    <a href="admin_products.php?user_id=<?php echo urlencode($userFirebaseUID); ?>" class="admin-btn">
                        🛒 Manage Products
                    </a>
                    <a href="admin_orders.php?user_id=<?php echo urlencode($userFirebaseUID); ?>" class="admin-btn">
                        📦 Manage Orders
                    </a>
                    <a href="admin_voice_clone.php?user_id=<?php echo urlencode($userFirebaseUID); ?>" class="admin-btn">
                        🎤 Voice Clone Settings
                    </a>
                    <a href="admin_subscriptions.php?user_id=<?php echo urlencode($userFirebaseUID); ?>" class="admin-btn">
                        💳 Subscription Management
                    </a>
                </div>
            </div>

            <!-- Daily Activity -->
            <?php if (!empty($dailyStats)): ?>
            <div class="admin-card">
                <div class="admin-card-header">
                    <h2>📊 Daily Activity (Last 7 Days)</h2>
                    <p>Recent user activity and memory creation</p>
                </div>
                <div class="admin-table-container">
                    <table class="admin-table">
                        <thead>
                            <tr>
                                <th>Date</th>
                                <th>Memories Created</th>
                                <th>Active Users</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($dailyStats as $day): ?>
                            <tr>
                                <td><?php echo date('M j, Y', strtotime($day['date'])); ?></td>
                                <td><?php echo $day['memories_created']; ?></td>
                                <td><?php echo $day['active_users']; ?></td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
            <?php endif; ?>

            <!-- Recent Orders -->
            <?php if (!empty($recentOrders)): ?>
            <div class="admin-card">
                <div class="admin-card-header">
                    <h2>🛒 Recent Orders</h2>
                    <p>Latest customer orders and their status</p>
                </div>
                <div class="admin-table-container">
                    <table class="admin-table">
                        <thead>
                            <tr>
                                <th>Order ID</th>
                                <th>Customer</th>
                                <th>Product</th>
                                <th>Amount</th>
                                <th>Status</th>
                                <th>Date</th>
                                <th>Printful ID</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($recentOrders as $order): ?>
                            <tr>
                                <td class="user-id"><?php echo substr($order['stripe_session_id'], -8); ?></td>
                                <td>
                                    <?php echo htmlspecialchars($order['customer_name']); ?><br>
                                    <small style="color: #64748b;"><?php echo htmlspecialchars($order['customer_email']); ?></small>
                                </td>
                                <td><?php echo htmlspecialchars($order['product_name'] ?: 'Unknown Product'); ?></td>
                                <td>$<?php echo number_format($order['amount_paid'] / 100, 2); ?></td>
                                <td>
                                    <span class="admin-status admin-status-<?php echo strtolower($order['status']); ?>">
                                        <?php echo ucfirst($order['status']); ?>
                                    </span>
                                </td>
                                <td><?php echo date('M j, g:i A', strtotime($order['created_at'])); ?></td>
                                <td class="user-id"><?php echo $order['printful_order_id'] ?: 'Pending'; ?></td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
            <?php endif; ?>

            <!-- Recent Memories -->
            <?php if (!empty($recentMemories)): ?>
            <div class="admin-card">
                <div class="admin-card-header">
                    <h2>💕 Recent Memories</h2>
                    <p>Latest memories created by users</p>
                </div>
                <div class="admin-table-container">
                    <table class="admin-table">
                        <thead>
                            <tr>
                                <th>Preview</th>
                                <th>Title</th>
                                <th>Original File</th>
                                <th>User</th>
                                <th>Created</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($recentMemories as $memory): ?>
                            <tr>
                                <td>
                                    <img src="<?php echo htmlspecialchars($memory['image_url']); ?>" 
                                         alt="Memory" class="memory-thumb"
                                         onclick="showImageModal('<?php echo htmlspecialchars($memory['image_url']); ?>', '<?php echo htmlspecialchars($memory['title']); ?>')">
                                </td>
                                <td>
                                    <strong><?php echo htmlspecialchars($memory['title'] ?: 'Untitled'); ?></strong>
                                </td>
                                <td><?php echo htmlspecialchars($memory['original_name']); ?></td>
                                <td>
                                    <span class="user-id"><?php echo $memory['user_short']; ?>...</span>
                                </td>
                                <td><?php echo date('M j, g:i A', strtotime($memory['created_at'])); ?></td>
                                <td>
                                    <a href="<?php echo htmlspecialchars($memory['image_url']); ?>" target="_blank" 
                                       style="color: #667eea; text-decoration: none; font-size: 12px;">View</a>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
            <?php endif; ?>

        </div>
    </div>

    <!-- Image Modal -->
    <div id="imageModal" style="display: none; position: fixed; top: 0; left: 0; width: 100%; height: 100%; background: rgba(0,0,0,0.9); z-index: 1000; align-items: center; justify-content: center;">
        <div style="position: relative; max-width: 90vw; max-height: 90vh;">
            <button onclick="closeImageModal()" style="position: absolute; top: -40px; right: 0; background: white; border: none; width: 32px; height: 32px; border-radius: 50%; cursor: pointer;">×</button>
            <img id="modalImage" src="" alt="Memory" style="max-width: 100%; max-height: 100%; border-radius: 8px;">
            <div id="modalTitle" style="text-align: center; color: white; margin-top: 16px; font-size: 18px;"></div>
        </div>
    </div>

    <!-- SweetAlert2 -->
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

    <script>
        function showImageModal(imageUrl, title) {
            document.getElementById('modalImage').src = imageUrl;
            document.getElementById('modalTitle').textContent = title;
            document.getElementById('imageModal').style.display = 'flex';
        }

        function closeImageModal() {
            document.getElementById('imageModal').style.display = 'none';
        }

        function exportData(type) {
            window.open(`export_data.php?type=${type}&user_id=<?php echo urlencode($userFirebaseUID); ?>`, '_blank');
        }

        // Analytics now has dedicated page

        // User management now has dedicated page

        // Auto-refresh every 30 seconds
        setInterval(() => {
            const url = new URL(window.location);
            url.searchParams.set('t', Date.now());
            window.location.href = url.toString();
        }, 30000);

        // Close modal on escape key
        document.addEventListener('keydown', (e) => {
            if (e.key === 'Escape') {
                closeImageModal();
            }
        });
    </script>
</body>
</html>

<?php
// admin.php - MemoWindow Admin Dashboard
require_once 'auth_check.php';

// Require admin authentication
$userFirebaseUID = requireAdmin();

// User is already verified as admin by requireAdmin()
// Update last login
try {
    $pdo = new PDO("mysql:host=" . DB_HOST . ";dbname=" . DB_NAME . ";charset=utf8mb4", DB_USER, DB_PASS, [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
    ]);
    
    $pdo->prepare("UPDATE admin_users SET last_login = CURRENT_TIMESTAMP WHERE firebase_uid = :uid")
        ->execute([':uid' => $userFirebaseUID]);
    
} catch (PDOException $e) {
    // Continue even if update fails
}

// Get dashboard data
try {
    // Get statistics
    $stats = [];
    
    // Total users (from users table)
    $stats['total_users'] = $pdo->query("SELECT COUNT(*) as count FROM users")->fetch()['count'];
    
    // Total memories
    $stats['total_memories'] = $pdo->query("SELECT COUNT(*) as count FROM wave_assets")->fetch()['count'];
    
    // Total orders
    $stats['total_orders'] = $pdo->query("SELECT COUNT(*) as count FROM orders")->fetch()['count'];
    
    // Total revenue (all orders with amount_paid > 0)
    $revenueResult = $pdo->query("SELECT SUM(amount_paid) as total FROM orders WHERE amount_paid > 0");
    $stats['total_revenue'] = $revenueResult->fetch()['total'] ?? 0;
    
    // Recent memories
    $recentMemories = $pdo->query("
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
    ")->fetchAll(PDO::FETCH_ASSOC);
    
    // Recent orders
    $recentOrders = $pdo->query("
        SELECT 
            o.*,
            p.name as product_name
        FROM orders o
        LEFT JOIN print_products p ON o.product_variant_id = p.product_key
        ORDER BY o.created_at DESC 
        LIMIT 10
    ")->fetchAll(PDO::FETCH_ASSOC);
    
    // Daily stats for last 7 days
    $dailyStats = $pdo->query("
        SELECT 
            DATE(created_at) as date,
            COUNT(*) as memories_created,
            COUNT(DISTINCT user_id) as active_users
        FROM wave_assets 
        WHERE created_at >= DATE_SUB(NOW(), INTERVAL 7 DAY)
        GROUP BY DATE(created_at)
        ORDER BY date DESC
    ")->fetchAll(PDO::FETCH_ASSOC);
    
} catch (PDOException $e) {
    $stats = ['error' => $e->getMessage()];
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
    <style>
        body {
            font-family: system-ui, -apple-system, Segoe UI, Roboto, Ubuntu, Helvetica, Arial, sans-serif;
            background: #f8fafc;
            color: #0f172a;
            margin: 0;
            padding: 20px;
        }
        .container {
            max-width: 1200px;
            margin: 0 auto;
        }
        .header {
            background: linear-gradient(135deg, #2563eb 0%, #1d4ed8 100%);
            color: white;
            padding: 24px;
            border-radius: 12px;
            margin-bottom: 24px;
            text-align: center;
        }
        .header h1 {
            margin: 0 0 8px 0;
            font-size: 28px;
            font-weight: 600;
        }
        .header p {
            margin: 0;
            opacity: 0.9;
            font-size: 16px;
        }
        .nav-links {
            text-align: center;
            margin: 16px 0;
        }
        .nav-link {
            display: inline-block;
            background: rgba(255,255,255,0.2);
            color: white;
            padding: 8px 16px;
            border-radius: 8px;
            text-decoration: none;
            margin: 0 8px;
            font-size: 14px;
        }
        .nav-link:hover {
            background: rgba(255,255,255,0.3);
        }
        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 20px;
            margin-bottom: 24px;
        }
        .stat-card {
            background: white;
            border-radius: 12px;
            padding: 20px;
            border: 1px solid #e2e8f0;
            text-align: center;
        }
        .stat-number {
            font-size: 32px;
            font-weight: 700;
            color: #2563eb;
            margin-bottom: 8px;
        }
        .stat-label {
            color: #64748b;
            font-size: 14px;
            font-weight: 500;
        }
        .section {
            background: white;
            border-radius: 12px;
            padding: 24px;
            margin-bottom: 24px;
            border: 1px solid #e2e8f0;
        }
        .section h2 {
            margin: 0 0 20px 0;
            color: #1e293b;
            font-size: 20px;
            font-weight: 600;
        }
        .table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 16px;
        }
        .table th, .table td {
            padding: 12px;
            text-align: left;
            border-bottom: 1px solid #e2e8f0;
            font-size: 14px;
        }
        .table th {
            background: #f8fafc;
            font-weight: 600;
            color: #374151;
        }
        .table tbody tr:hover {
            background: #f8fafc;
        }
        .status-badge {
            padding: 4px 8px;
            border-radius: 4px;
            font-size: 12px;
            font-weight: 500;
        }
        
        .admin-actions-grid {
            display: grid;
            grid-template-columns: repeat(3, 1fr);
            gap: 16px;
            margin-top: 24px;
        }
        
        .admin-btn {
            background: #3b82f6;
            color: white;
            border: none;
            padding: 16px;
            border-radius: 8px;
            cursor: pointer;
            text-decoration: none;
            display: block;
            text-align: center;
            font-weight: 500;
            transition: all 0.2s ease;
        }
        
        .admin-btn:hover {
            background: #2563eb;
            transform: translateY(-2px);
            box-shadow: 0 4px 12px rgba(59, 130, 246, 0.3);
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
        .status-paid {
            background: #d1fae5;
            color: #065f46;
        }
        .status-pending {
            background: #fef3c7;
            color: #92400e;
        }
        .status-draft {
            background: #e0e7ff;
            color: #3730a3;
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
            .stats-grid {
                grid-template-columns: repeat(2, 1fr);
            }
            .table {
                font-size: 12px;
            }
            .table th, .table td {
                padding: 8px;
            }
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1>MemoWindow Admin Dashboard</h1>
            <p>Manage users, orders, and analytics</p>
            <div class="nav-links">
                <a href="login.html" class="nav-link">← Back to MemoWindow</a>
                <a href="orders.html" class="nav-link">My Orders</a>
                <a href="#" onclick="location.reload()" class="nav-link">Refresh Data</a>
            </div>
        </div>

        <!-- Statistics Cards -->
        <div class="stats-grid">
            <div class="stat-card">
                <div class="stat-number"><?php echo number_format($stats['total_users']); ?></div>
                <div class="stat-label">Total Users</div>
            </div>
            <div class="stat-card">
                <div class="stat-number"><?php echo number_format($stats['total_memories']); ?></div>
                <div class="stat-label">Memories Created</div>
            </div>
            <div class="stat-card">
                <div class="stat-number"><?php echo number_format($stats['total_orders']); ?></div>
                <div class="stat-label">Total Orders</div>
            </div>
            <div class="stat-card">
                <div class="stat-number">$<?php echo number_format($stats['total_revenue'] / 100, 2); ?></div>
                <div class="stat-label">Total Revenue</div>
            </div>
        </div>

        <!-- Admin Actions -->
        <div class="section">
            <h2>⚙️ Admin Actions</h2>
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
        <div class="section">
            <h2>📊 Daily Activity (Last 7 Days)</h2>
            <table class="table">
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
        <?php endif; ?>

        <!-- Recent Orders -->
        <?php if (!empty($recentOrders)): ?>
        <div class="section">
            <h2>🛒 Recent Orders</h2>
            <table class="table">
                <thead>
                    <tr>
                        <th>Order ID</th>
                        <th>Customer</th>
                        <th>Memory</th>
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
                        <td><?php echo htmlspecialchars($order['product_variant_id'] ?: 'N/A'); ?></td>
                        <td>$<?php echo number_format($order['amount_paid'] / 100, 2); ?></td>
                        <td>
                            <span class="status-badge status-<?php echo strtolower($order['status']); ?>">
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
        <?php endif; ?>

        <!-- Recent Memories -->
        <?php if (!empty($recentMemories)): ?>
        <div class="section">
            <h2>💕 Recent Memories</h2>
            <table class="table">
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
                               style="color: #2563eb; text-decoration: none; font-size: 12px;">View</a>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
        <?php endif; ?>

    </div>

    <!-- Image Modal -->
    <div id="imageModal" style="display: none; position: fixed; top: 0; left: 0; width: 100%; height: 100%; background: rgba(0,0,0,0.9); z-index: 1000; align-items: center; justify-content: center;">
        <div style="position: relative; max-width: 90vw; max-height: 90vh;">
            <button onclick="closeImageModal()" style="position: absolute; top: -40px; right: 0; background: white; border: none; width: 32px; height: 32px; border-radius: 50%; cursor: pointer;">×</button>
            <img id="modalImage" src="" alt="Memory" style="max-width: 100%; max-height: 100%; border-radius: 8px;">
            <div id="modalTitle" style="text-align: center; color: white; margin-top: 16px; font-size: 18px;"></div>
        </div>
    </div>

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

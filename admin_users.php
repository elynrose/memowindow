<?php
// admin_users.php - User management interface
require_once 'auth_check.php';
require_once 'config.php';

// Require admin authentication
$userFirebaseUID = requireAdmin();

// Check if user is admin
try {
    $pdo = new PDO("mysql:host=" . DB_HOST . ";dbname=" . DB_NAME . ";charset=utf8mb4", DB_USER, DB_PASS, [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
    ]);
    
    $adminCheck = $pdo->prepare("SELECT * FROM admin_users WHERE firebase_uid = :uid AND is_admin = 1");
    $adminCheck->execute([':uid' => $userFirebaseUID]);
    $adminUser = $adminCheck->fetch(PDO::FETCH_ASSOC);
    
    if (!$adminUser) {
        http_response_code(403);
        echo "Access denied. Admin privileges required.";
        exit;
    }
    
} catch (PDOException $e) {
    http_response_code(500);
    echo "Database error";
    exit;
}

// Get user statistics and data
try {
    // Get all users with their activity
    $users = $pdo->query("
        SELECT 
            user_id,
            COUNT(*) as memory_count,
            MAX(created_at) as last_activity,
            MIN(created_at) as first_activity,
            SUM(CASE WHEN created_at >= DATE_SUB(NOW(), INTERVAL 7 DAY) THEN 1 ELSE 0 END) as recent_memories,
            GROUP_CONCAT(DISTINCT SUBSTRING(title, 1, 30) ORDER BY created_at DESC SEPARATOR ', ') as recent_titles
        FROM wave_assets 
        GROUP BY user_id 
        ORDER BY last_activity DESC
    ")->fetchAll(PDO::FETCH_ASSOC);
    
    // Get order statistics per user
    $orderStats = $pdo->query("
        SELECT 
            user_id,
            COUNT(*) as order_count,
            SUM(amount_paid) as total_spent,
            MAX(created_at) as last_order
        FROM orders 
        GROUP BY user_id
    ")->fetchAll(PDO::FETCH_ASSOC);
    
    // Merge order stats with user data
    $ordersByUser = [];
    foreach ($orderStats as $stat) {
        $ordersByUser[$stat['user_id']] = $stat;
    }
    
} catch (PDOException $e) {
    $users = [];
    $ordersByUser = [];
    $error = $e->getMessage();
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>User Management - MemoWindow Admin</title>
    <style>
        body {
            font-family: system-ui, -apple-system, Segoe UI, Roboto, Ubuntu, Helvetica, Arial, sans-serif;
            background: #f8fafc;
            color: #0f172a;
            margin: 0;
            padding: 20px;
        }
        .container {
            max-width: 1400px;
            margin: 0 auto;
        }
        .header {
            background: linear-gradient(135deg, #dc2626 0%, #b91c1c 100%);
            color: white;
            padding: 24px;
            border-radius: 12px;
            margin-bottom: 24px;
        }
        .header h1 {
            margin: 0 0 8px 0;
            font-size: 28px;
            font-weight: 600;
        }
        .header p {
            margin: 0 0 16px 0;
            opacity: 0.9;
        }
        .nav-links {
            display: flex;
            gap: 12px;
            flex-wrap: wrap;
        }
        .nav-link {
            background: rgba(255,255,255,0.2);
            color: white;
            padding: 8px 16px;
            border-radius: 8px;
            text-decoration: none;
            font-size: 14px;
            transition: background 0.2s;
        }
        .nav-link:hover {
            background: rgba(255,255,255,0.3);
        }
        .search-section {
            background: white;
            border-radius: 12px;
            padding: 20px;
            margin-bottom: 24px;
            border: 1px solid #e2e8f0;
        }
        .search-box {
            width: 100%;
            max-width: 400px;
            padding: 12px;
            border: 1px solid #d1d5db;
            border-radius: 8px;
            font-size: 14px;
        }
        .filter-buttons {
            display: flex;
            gap: 8px;
            margin-top: 12px;
            flex-wrap: wrap;
        }
        .filter-btn {
            padding: 6px 12px;
            border: 1px solid #d1d5db;
            background: white;
            border-radius: 6px;
            cursor: pointer;
            font-size: 12px;
        }
        .filter-btn.active {
            background: #2563eb;
            color: white;
            border-color: #2563eb;
        }
        .users-grid {
            display: grid;
            gap: 20px;
        }
        .user-card {
            background: white;
            border-radius: 12px;
            padding: 20px;
            border: 1px solid #e2e8f0;
            transition: box-shadow 0.2s;
        }
        .user-card:hover {
            box-shadow: 0 4px 12px rgba(0,0,0,0.1);
        }
        .user-header {
            display: flex;
            justify-content: space-between;
            align-items: start;
            margin-bottom: 16px;
        }
        .user-id {
            font-family: monospace;
            background: #f1f5f9;
            padding: 4px 8px;
            border-radius: 4px;
            font-size: 12px;
            color: #475569;
        }
        .user-stats {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(120px, 1fr));
            gap: 16px;
            margin: 16px 0;
        }
        .user-stat {
            text-align: center;
            padding: 12px;
            background: #f8fafc;
            border-radius: 8px;
        }
        .user-stat-number {
            font-size: 20px;
            font-weight: 600;
            color: #2563eb;
            margin-bottom: 4px;
        }
        .user-stat-label {
            font-size: 12px;
            color: #64748b;
        }
        .recent-titles {
            font-size: 12px;
            color: #64748b;
            line-height: 1.4;
            margin-top: 12px;
            padding: 8px;
            background: #f8fafc;
            border-radius: 6px;
            max-height: 60px;
            overflow: hidden;
        }
        .user-actions {
            display: flex;
            gap: 8px;
            margin-top: 16px;
        }
        .btn {
            padding: 6px 12px;
            border: none;
            border-radius: 6px;
            cursor: pointer;
            font-size: 12px;
            text-decoration: none;
            display: inline-block;
        }
        .btn-primary {
            background: #2563eb;
            color: white;
        }
        .btn-secondary {
            background: #6b7280;
            color: white;
        }
        .btn-danger {
            background: #dc2626;
            color: white;
        }
        @media (max-width: 768px) {
            .user-header {
                flex-direction: column;
                gap: 12px;
            }
            .user-stats {
                grid-template-columns: repeat(2, 1fr);
            }
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1>👥 User Management</h1>
            <p>Manage MemoWindow users and their activity</p>
            <div class="nav-links">
                <a href="admin.php?user_id=<?php echo urlencode($userFirebaseUID); ?>" class="nav-link">← Dashboard</a>
                <a href="login.php" class="nav-link">MemoWindow</a>
                <a href="#" onclick="location.reload()" class="nav-link">Refresh</a>
            </div>
        </div>

        <!-- Search and Filters -->
        <div class="search-section">
            <h3 style="margin: 0 0 16px 0;">🔍 Search Users</h3>
            <input type="text" class="search-box" placeholder="Search by user ID, memory title, or activity..." 
                   onkeyup="filterUsers(this.value)">
            <div class="filter-buttons">
                <button class="filter-btn active" onclick="filterByActivity('all')">All Users</button>
                <button class="filter-btn" onclick="filterByActivity('active')">Active (7 days)</button>
                <button class="filter-btn" onclick="filterByActivity('customers')">Customers</button>
                <button class="filter-btn" onclick="filterByActivity('inactive')">Inactive (30+ days)</button>
            </div>
        </div>

        <!-- Users Grid -->
        <div class="users-grid" id="usersGrid">
            <?php if (isset($error)): ?>
                <div style="text-align: center; color: #dc2626; padding: 40px;">
                    <h3>Error Loading Users</h3>
                    <p><?php echo htmlspecialchars($error); ?></p>
                </div>
            <?php elseif (empty($users)): ?>
                <div style="text-align: center; color: #64748b; padding: 40px;">
                    <h3>No Users Yet</h3>
                    <p>No MemoWindow users have created memories yet.</p>
                </div>
            <?php else: ?>
                <?php foreach ($users as $user): 
                    $orders = $ordersByUser[$user['user_id']] ?? null;
                    $isActive = strtotime($user['last_activity']) > strtotime('-7 days');
                    $isCustomer = $orders && $orders['order_count'] > 0;
                ?>
                <div class="user-card" data-user-id="<?php echo $user['user_id']; ?>" 
                     data-active="<?php echo $isActive ? 'true' : 'false'; ?>"
                     data-customer="<?php echo $isCustomer ? 'true' : 'false'; ?>">
                    
                    <div class="user-header">
                        <div>
                            <div class="user-id"><?php echo substr($user['user_id'], 0, 20); ?>...</div>
                            <?php if ($isActive): ?>
                                <span style="color: #059669; font-size: 12px; font-weight: 500;">● Active</span>
                            <?php endif; ?>
                            <?php if ($isCustomer): ?>
                                <span style="color: #2563eb; font-size: 12px; font-weight: 500;">💳 Customer</span>
                            <?php endif; ?>
                        </div>
                        <div style="text-align: right; font-size: 12px; color: #64748b;">
                            <div>Joined: <?php echo date('M j, Y', strtotime($user['first_activity'])); ?></div>
                            <div>Last seen: <?php echo date('M j, g:i A', strtotime($user['last_activity'])); ?></div>
                        </div>
                    </div>

                    <div class="user-stats">
                        <div class="user-stat">
                            <div class="user-stat-number"><?php echo $user['memory_count']; ?></div>
                            <div class="user-stat-label">Memories</div>
                        </div>
                        <div class="user-stat">
                            <div class="user-stat-number"><?php echo $user['recent_memories']; ?></div>
                            <div class="user-stat-label">This Week</div>
                        </div>
                        <?php if ($orders): ?>
                        <div class="user-stat">
                            <div class="user-stat-number"><?php echo $orders['order_count']; ?></div>
                            <div class="user-stat-label">Orders</div>
                        </div>
                        <div class="user-stat">
                            <div class="user-stat-number">$<?php echo number_format($orders['total_spent'] / 100, 0); ?></div>
                            <div class="user-stat-label">Spent</div>
                        </div>
                        <?php endif; ?>
                    </div>

                    <?php if ($user['recent_titles']): ?>
                    <div class="recent-titles">
                        <strong>Recent memories:</strong> <?php echo htmlspecialchars($user['recent_titles']); ?>
                    </div>
                    <?php endif; ?>

                    <div class="user-actions">
                        <a href="user_details.php?user_id=<?php echo urlencode($userFirebaseUID); ?>&target_user=<?php echo urlencode($user['user_id']); ?>" 
                           class="btn btn-primary">View Details</a>
                        <a href="orders.php" 
                           class="btn btn-secondary">View Orders</a>
                        <?php if ($orders): ?>
                        <button onclick="exportUserData('<?php echo $user['user_id']; ?>')" 
                                class="btn btn-secondary">Export Data</button>
                        <?php endif; ?>
                    </div>
                </div>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>
    </div>

    <script>
        function filterUsers(searchTerm) {
            const cards = document.querySelectorAll('.user-card');
            const term = searchTerm.toLowerCase();
            
            cards.forEach(card => {
                const text = card.textContent.toLowerCase();
                const userId = card.dataset.userId.toLowerCase();
                
                if (text.includes(term) || userId.includes(term)) {
                    card.style.display = 'block';
                } else {
                    card.style.display = 'none';
                }
            });
        }

        function filterByActivity(filter) {
            // Update button states
            document.querySelectorAll('.filter-btn').forEach(btn => {
                btn.classList.remove('active');
            });
            event.target.classList.add('active');
            
            const cards = document.querySelectorAll('.user-card');
            
            cards.forEach(card => {
                let show = false;
                
                switch(filter) {
                    case 'all':
                        show = true;
                        break;
                    case 'active':
                        show = card.dataset.active === 'true';
                        break;
                    case 'customers':
                        show = card.dataset.customer === 'true';
                        break;
                    case 'inactive':
                        show = card.dataset.active === 'false';
                        break;
                }
                
                card.style.display = show ? 'block' : 'none';
            });
        }

        function exportUserData(userId) {
            window.open(`export_user_data.php?user_id=<?php echo urlencode($userFirebaseUID); ?>&target_user=${encodeURIComponent(userId)}`, '_blank');
        }

        // Auto-refresh every 60 seconds
        setInterval(() => {
            location.reload();
        }, 60000);
    </script>
</body>
</html>

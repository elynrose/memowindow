<?php
// user_details.php - Detailed user information
require_once 'config.php';

$adminUID = $_GET['user_id'] ?? '';
$targetUserID = $_GET['target_user'] ?? '';

if (!$adminUID || !$targetUserID) {
    header('Location: admin_users.php');
    exit;
}

// Check admin privileges
try {
    $pdo = new PDO("mysql:host=" . DB_HOST . ";dbname=" . DB_NAME . ";charset=utf8mb4", DB_USER, DB_PASS, [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
    ]);
    
    $adminCheck = $pdo->prepare("SELECT * FROM admin_users WHERE firebase_uid = :uid AND is_admin = 1");
    $adminCheck->execute([':uid' => $adminUID]);
    
    if (!$adminCheck->fetch()) {
        http_response_code(403);
        echo "Access denied";
        exit;
    }
    
    // Get user's memories
    $memories = $pdo->prepare("
        SELECT * FROM wave_assets 
        WHERE user_id = :user_id 
        ORDER BY created_at DESC
    ");
    $memories->execute([':user_id' => $targetUserID]);
    $userMemories = $memories->fetchAll(PDO::FETCH_ASSOC);
    
    // Get user's orders
    $orders = $pdo->prepare("
        SELECT 
            o.*,
            w.title as memory_title
        FROM orders o
        LEFT JOIN wave_assets w ON o.memory_id = w.id
        WHERE o.user_id = :user_id 
        ORDER BY o.created_at DESC
    ");
    $orders->execute([':user_id' => $targetUserID]);
    $userOrders = $orders->fetchAll(PDO::FETCH_ASSOC);
    
    // Calculate user statistics
    $totalSpent = array_sum(array_column($userOrders, 'amount_paid'));
    $firstActivity = !empty($userMemories) ? min(array_column($userMemories, 'created_at')) : null;
    $lastActivity = !empty($userMemories) ? max(array_column($userMemories, 'created_at')) : null;
    
} catch (PDOException $e) {
    $error = $e->getMessage();
    $userMemories = [];
    $userOrders = [];
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>User Details - <?php echo substr($targetUserID, 0, 20); ?>... - MemoWindow Admin</title>
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
            background: linear-gradient(135deg, #7c3aed 0%, #6d28d9 100%);
            color: white;
            padding: 24px;
            border-radius: 12px;
            margin-bottom: 24px;
        }
        .user-id {
            font-family: monospace;
            background: rgba(255,255,255,0.2);
            padding: 4px 8px;
            border-radius: 4px;
            font-size: 14px;
        }
        .section {
            background: white;
            border-radius: 12px;
            padding: 24px;
            margin-bottom: 24px;
            border: 1px solid #e2e8f0;
        }
        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
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
            font-size: 24px;
            font-weight: 700;
            color: #2563eb;
            margin-bottom: 8px;
        }
        .stat-label {
            color: #64748b;
            font-size: 14px;
        }
        .memory-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(300px, 1fr));
            gap: 16px;
        }
        .memory-card {
            border: 1px solid #e2e8f0;
            border-radius: 8px;
            padding: 16px;
            background: #fafbfc;
        }
        .memory-thumb {
            width: 100%;
            height: 120px;
            object-fit: cover;
            border-radius: 6px;
            border: 1px solid #e2e8f0;
            margin-bottom: 12px;
            cursor: pointer;
        }
        .table {
            width: 100%;
            border-collapse: collapse;
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
        }
        .nav-link {
            background: rgba(255,255,255,0.2);
            color: white;
            padding: 8px 16px;
            border-radius: 8px;
            text-decoration: none;
            font-size: 14px;
            margin-right: 8px;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1>👤 User Details</h1>
            <p>User ID: <span class="user-id"><?php echo htmlspecialchars($targetUserID); ?></span></p>
            <div>
                <a href="admin_users.php?user_id=<?php echo urlencode($adminUID); ?>" class="nav-link">← Back to Users</a>
                <a href="admin.php?user_id=<?php echo urlencode($adminUID); ?>" class="nav-link">Dashboard</a>
            </div>
        </div>

        <!-- User Statistics -->
        <div class="stats-grid">
            <div class="stat-card">
                <div class="stat-number"><?php echo count($userMemories); ?></div>
                <div class="stat-label">Total Memories</div>
            </div>
            <div class="stat-card">
                <div class="stat-number"><?php echo count($userOrders); ?></div>
                <div class="stat-label">Total Orders</div>
            </div>
            <div class="stat-card">
                <div class="stat-number">$<?php echo number_format($totalSpent / 100, 2); ?></div>
                <div class="stat-label">Total Spent</div>
            </div>
            <div class="stat-card">
                <div class="stat-number"><?php echo $firstActivity ? date('M j, Y', strtotime($firstActivity)) : 'N/A'; ?></div>
                <div class="stat-label">First Activity</div>
            </div>
        </div>

        <!-- User's Memories -->
        <?php if (!empty($userMemories)): ?>
        <div class="section">
            <h2>💕 User's Memories (<?php echo count($userMemories); ?>)</h2>
            <div class="memory-grid">
                <?php foreach ($userMemories as $memory): ?>
                <div class="memory-card">
                    <img src="<?php echo htmlspecialchars($memory['image_url']); ?>" 
                         alt="Memory" class="memory-thumb"
                         onclick="showImageModal('<?php echo htmlspecialchars($memory['image_url']); ?>', '<?php echo htmlspecialchars($memory['title']); ?>')">
                    <h4 style="margin: 0 0 8px 0;"><?php echo htmlspecialchars($memory['title'] ?: 'Untitled'); ?></h4>
                    <p style="margin: 0; font-size: 12px; color: #64748b;">
                        <?php echo htmlspecialchars($memory['original_name']); ?><br>
                        <?php echo date('M j, Y g:i A', strtotime($memory['created_at'])); ?>
                    </p>
                    <div style="margin-top: 12px; display: flex; gap: 8px;">
                        <a href="<?php echo htmlspecialchars($memory['image_url']); ?>" target="_blank" 
                           style="font-size: 12px; color: #2563eb; text-decoration: none;">View Image</a>
                        <a href="<?php echo htmlspecialchars($memory['qr_url']); ?>" target="_blank" 
                           style="font-size: 12px; color: #2563eb; text-decoration: none;">QR Code</a>
                        <?php if ($memory['audio_url']): ?>
                        <a href="<?php echo htmlspecialchars($memory['audio_url']); ?>" target="_blank" 
                           style="font-size: 12px; color: #2563eb; text-decoration: none;">Audio</a>
                        <?php endif; ?>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>
        </div>
        <?php endif; ?>

        <!-- User's Orders -->
        <?php if (!empty($userOrders)): ?>
        <div class="section">
            <h2>🛒 User's Orders (<?php echo count($userOrders); ?>)</h2>
            <table class="table">
                <thead>
                    <tr>
                        <th>Order ID</th>
                        <th>Memory</th>
                        <th>Product</th>
                        <th>Amount</th>
                        <th>Status</th>
                        <th>Printful ID</th>
                        <th>Date</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($userOrders as $order): ?>
                    <tr>
                        <td style="font-family: monospace; font-size: 12px;"><?php echo substr($order['stripe_session_id'], -8); ?></td>
                        <td><?php echo htmlspecialchars($order['memory_title'] ?: 'Untitled'); ?></td>
                        <td><?php echo htmlspecialchars($order['product_id']); ?></td>
                        <td>$<?php echo number_format($order['amount_paid'] / 100, 2); ?></td>
                        <td>
                            <span style="padding: 4px 8px; border-radius: 4px; font-size: 12px; background: #d1fae5; color: #065f46;">
                                <?php echo ucfirst($order['status']); ?>
                            </span>
                        </td>
                        <td style="font-family: monospace; font-size: 12px;"><?php echo $order['printful_order_id'] ?: 'Pending'; ?></td>
                        <td><?php echo date('M j, g:i A', strtotime($order['created_at'])); ?></td>
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

        document.addEventListener('keydown', (e) => {
            if (e.key === 'Escape') {
                closeImageModal();
            }
        });
    </script>
</body>
</html>

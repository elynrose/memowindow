<?php
// orders.php - User's order tracking page
require_once 'auth_check.php';
require_once 'config.php';

// Require authentication
$userId = requireAuth();

try {
    // Connect to database
    $pdo = new PDO("mysql:host=" . DB_HOST . ";dbname=" . DB_NAME . ";charset=utf8mb4", DB_USER, DB_PASS, [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
    ]);

    // Get user's orders with memory details
    $stmt = $pdo->prepare("
        SELECT 
            o.id,
            o.stripe_session_id,
            o.memory_id,
            o.printful_order_id,
            o.memory_title,
            o.memory_image_url,
            o.product_name,
            o.product_variant_id,
            o.quantity,
            o.unit_price,
            o.total_price,
            o.status,
            o.shipping_address,
            o.tracking_number,
            o.created_at,
            o.updated_at,
            o.product_id,
            o.customer_name,
            o.customer_email,
            o.amount_paid
        FROM orders o
        WHERE o.user_id = :user_id
        ORDER BY o.created_at DESC
    ");
    
    $stmt->execute([':user_id' => $userId]);
    $orders = $stmt->fetchAll(PDO::FETCH_ASSOC);

} catch (PDOException $e) {
    $orders = [];
    $error = 'Database error: ' . $e->getMessage();
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>My Orders - MemoWindow</title>
    <style>
        body {
            font-family: system-ui, -apple-system, Segoe UI, Roboto, Ubuntu, Helvetica, Arial, sans-serif;
            background: #f5f7fb;
            color: #0b0d12;
            margin: 0;
            padding: 20px;
        }
        .container {
            max-width: 900px;
            margin: auto;
        }
        .header {
            background: white;
            border-radius: 16px;
            padding: 24px;
            margin-bottom: 24px;
            text-align: center;
            border: 1px solid #e6e9f2;
        }
        .header h1 {
            margin: 0 0 8px 0;
            color: #0b0d12;
            font-size: 28px;
        }
        .header p {
            margin: 0;
            color: #6b7280;
            font-size: 16px;
        }
        .nav-link {
            display: inline-block;
            background: #2a4df5;
            color: white;
            padding: 10px 20px;
            border-radius: 10px;
            text-decoration: none;
            font-weight: 500;
            margin-top: 16px;
        }
        .order-card {
            background: white;
            border: 1px solid #e6e9f2;
            border-radius: 16px;
            padding: 20px;
            margin-bottom: 16px;
        }
        .order-header {
            display: flex;
            justify-content: space-between;
            align-items: start;
            margin-bottom: 16px;
            flex-wrap: wrap;
            gap: 12px;
        }
        .order-title {
            font-size: 18px;
            font-weight: 600;
            color: #0b0d12;
            margin: 0;
        }
        .order-status {
            padding: 4px 12px;
            border-radius: 20px;
            font-size: 12px;
            font-weight: 500;
            text-transform: uppercase;
        }
        .status-paid {
            background: #d1fae5;
            color: #065f46;
        }
        .status-processing {
            background: #dbeafe;
            color: #1e40af;
        }
        .status-shipped {
            background: #e0e7ff;
            color: #3730a3;
        }
        .order-details {
            display: grid;
            grid-template-columns: auto 1fr auto;
            gap: 16px;
            align-items: center;
        }
        .order-image {
            width: 80px;
            height: 50px;
            border-radius: 8px;
            border: 1px solid #e6e9f2;
            object-fit: cover;
            background: white;
        }
        .order-info {
            flex: 1;
        }
        .order-info h4 {
            margin: 0 0 4px 0;
            color: #0b0d12;
            font-size: 14px;
        }
        .order-info p {
            margin: 0;
            color: #6b7280;
            font-size: 12px;
        }
        .order-price {
            font-size: 16px;
            font-weight: 600;
            color: #0b0d12;
            text-align: right;
        }
        .order-meta {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-top: 16px;
            padding-top: 16px;
            border-top: 1px solid #f3f4f6;
            font-size: 12px;
            color: #6b7280;
        }
        .empty-state {
            text-align: center;
            padding: 40px;
            color: #6b7280;
        }
        .empty-state h3 {
            color: #0b0d12;
            margin-bottom: 8px;
        }
        @media (max-width: 640px) {
            .order-details {
                grid-template-columns: 1fr;
                text-align: center;
            }
            .order-header {
                flex-direction: column;
                align-items: center;
                text-align: center;
            }
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1>My Orders</h1>
            <p>Track your MemoryWave print orders</p>
            <a href="index.html" class="nav-link">← Back to MemoWindow</a>
        </div>

        <?php if (isset($error)): ?>
            <div class="order-card" style="text-align: center; color: #dc2626;">
                <h3>Error Loading Orders</h3>
                <p><?php echo htmlspecialchars($error); ?></p>
            </div>
        <?php elseif (empty($orders)): ?>
            <div class="order-card empty-state">
                <h3>No Orders Yet</h3>
                <p>You haven't placed any print orders yet.</p>
                <p>Create a MemoryWave and order a beautiful print to get started!</p>
                <a href="index.html" class="nav-link">Create MemoryWave</a>
            </div>
        <?php else: ?>
            <?php foreach ($orders as $order): 
                $product = getProduct($order['product_id']);
                $statusClass = 'status-' . strtolower($order['status']);
            ?>
            <div class="order-card">
                <div class="order-header">
                    <div>
                        <h2 class="order-title"><?php echo htmlspecialchars($order['memory_title'] ?: 'Untitled Memory'); ?></h2>
                        <p style="margin: 4px 0 0 0; color: #6b7280; font-size: 14px;">
                            Order #<?php echo substr($order['stripe_session_id'], -8); ?>
                        </p>
                    </div>
                    <span class="order-status <?php echo $statusClass; ?>">
                        <?php echo ucfirst($order['status']); ?>
                    </span>
                </div>
                
                <div class="order-details">
                    <img src="<?php echo htmlspecialchars($order['memory_image_url']); ?>" 
                         alt="MemoryWave" class="order-image">
                    
                    <div class="order-info">
                        <h4><?php echo htmlspecialchars($order['product_name']); ?></h4>
                        <p>Quantity: <?php echo htmlspecialchars($order['quantity']); ?></p>
                        <p>Unit Price: $<?php echo number_format($order['unit_price'], 2); ?></p>
                        <?php if ($order['printful_order_id']): ?>
                            <p>Printful Order: <?php echo htmlspecialchars($order['printful_order_id']); ?></p>
                        <?php endif; ?>
                    </div>
                    
                    <div class="order-price">
                        $<?php echo number_format($order['total_price'], 2); ?>
                    </div>
                </div>
                
                <div class="order-meta">
                    <span>Ordered: <?php echo date('M j, Y \a\t g:i A', strtotime($order['created_at'])); ?></span>
                    <span>Product: <?php echo htmlspecialchars($order['product_name']); ?></span>
                    <?php if (in_array($order['status'], ['pending', 'paid'])): ?>
                        <button onclick="cancelOrder(<?php echo $order['id']; ?>)" 
                                class="cancel-btn" 
                                style="background: #dc2626; color: white; border: none; padding: 4px 8px; border-radius: 4px; font-size: 12px; cursor: pointer; margin-left: 10px;">
                            Cancel Order
                        </button>
                    <?php endif; ?>
                </div>
            </div>
            <?php endforeach; ?>
        <?php endif; ?>
    </div>

    <script>
        function cancelOrder(orderId) {
            if (!confirm('Are you sure you want to cancel this order? This action cannot be undone.')) {
                return;
            }
            
            const userId = '<?php echo htmlspecialchars($userId); ?>';
            
            fetch('cancel_order.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded',
                },
                body: `order_id=${orderId}&user_id=${userId}&reason=User requested cancellation`
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    alert('Order cancelled successfully!');
                    location.reload(); // Refresh the page to show updated order list
                } else {
                    alert('Error cancelling order: ' + (data.error || 'Unknown error'));
                }
            })
            .catch(error => {
                console.error('Error:', error);
                alert('Error cancelling order. Please try again.');
            });
        }
    </script>
</body>
</html>

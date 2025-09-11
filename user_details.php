<?php
// user_details.php - Detailed user information
require_once 'secure_auth.php';
require_once 'config.php';

// Check session timeout
if (!checkSessionTimeout()) {
    header('Location: ' . BASE_URL . '/login.php?error=session_expired');
    exit;
}

// Require admin authentication
$adminUID = requireSecureAdmin();
$targetUserID = $_GET['target_user'] ?? '';

if (!$targetUserID) {
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
    
    // Get user's orders with Stripe payment information
    $orders = $pdo->prepare("
        SELECT 
            o.*,
            w.title as memory_title,
            w.image_url as memory_image
        FROM orders o
        LEFT JOIN wave_assets w ON o.memory_id = w.id
        WHERE o.user_id = :user_id 
        ORDER BY o.created_at DESC
    ");
    $orders->execute([':user_id' => $targetUserID]);
    $userOrders = $orders->fetchAll(PDO::FETCH_ASSOC);
    
    // Get Stripe payment information for each order
    $stripePayments = [];
    if (!empty($userOrders)) {
        require_once 'vendor/autoload.php';
        \Stripe\Stripe::setApiKey(STRIPE_SECRET_KEY);
        
        foreach ($userOrders as $order) {
            if ($order['stripe_session_id']) {
                try {
                    // Get payment intent from session
                    $session = \Stripe\Checkout\Session::retrieve($order['stripe_session_id']);
                    if ($session->payment_intent) {
                        $paymentIntent = \Stripe\PaymentIntent::retrieve($session->payment_intent);
                        $stripePayments[$order['id']] = [
                            'payment_intent_id' => $paymentIntent->id,
                            'status' => $paymentIntent->status,
                            'amount' => $paymentIntent->amount,
                            'currency' => $paymentIntent->currency,
                            'charges' => $paymentIntent->charges->data,
                            'refunded' => $paymentIntent->amount_refunded > 0,
                            'refund_amount' => $paymentIntent->amount_refunded,
                            'session_id' => $order['stripe_session_id']
                        ];
                    }
                } catch (Exception $e) {
                    error_log("Error fetching Stripe payment for order {$order['id']}: " . $e->getMessage());
                    $stripePayments[$order['id']] = null;
                }
            }
        }
    }
    
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
    <link rel="stylesheet" href="includes/admin_styles.css">
    <!-- SweetAlert2 -->
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <style>
        /* Page-specific styles */
        .user-id {
            font-family: monospace;
            background: rgba(255,255,255,0.2);
            padding: 4px 8px;
            border-radius: 4px;
            font-size: 14px;
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
    <div class="admin-container">
        <div class="admin-header">
            <h1>👤 User Details</h1>
            <p>User ID: <span class="user-id"><?php echo htmlspecialchars($targetUserID); ?></span></p>
        </div>
        
        <?php include 'includes/admin_navigation.php'; ?>

        <!-- Custom Back Button for User Details -->
        <div class="admin-content" style="margin-top: 20px;">
            <a href="admin_users.php" class="admin-btn admin-btn-secondary" style="margin-bottom: 20px;">
                ← Back to Users
            </a>
        </div>

        <div class="admin-content">
            <!-- User Statistics -->
            <div class="admin-stats-grid">
                <div class="admin-stat-card">
                    <div class="number"><?php echo count($userMemories); ?></div>
                    <p>Total Memories</p>
                </div>
                <div class="admin-stat-card">
                    <div class="number"><?php echo count($userOrders); ?></div>
                    <p>Total Orders</p>
                </div>
                <div class="admin-stat-card">
                    <div class="number">$<?php echo number_format($totalSpent / 100, 2); ?></div>
                    <p>Total Spent</p>
                </div>
                <div class="admin-stat-card">
                    <div class="number"><?php echo $firstActivity ? date('M j, Y', strtotime($firstActivity)) : 'N/A'; ?></div>
                    <p>First Activity</p>
                </div>
            </div>

            <!-- User's Memories -->
            <?php if (!empty($userMemories)): ?>
            <div class="admin-card">
                <div class="admin-card-header">
                    <h2>💕 User's Memories (<?php echo count($userMemories); ?>)</h2>
                </div>
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
            <div class="admin-card">
                <div class="admin-card-header">
                    <h2>🛒 User's Orders (<?php echo count($userOrders); ?>)</h2>
                </div>
                <div class="admin-table-container">
                    <table class="admin-table">
                <thead>
                    <tr>
                        <th>Order ID</th>
                        <th>Memory</th>
                        <th>Product</th>
                        <th>Amount</th>
                        <th>Status</th>
                        <th>Stripe Payment</th>
                        <th>Printful ID</th>
                        <th>Date</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($userOrders as $order): 
                        $stripePayment = $stripePayments[$order['id']] ?? null;
                    ?>
                    <tr>
                        <td style="font-family: monospace; font-size: 12px;"><?php echo substr($order['stripe_session_id'], -8); ?></td>
                        <td>
                            <?php if ($order['memory_image']): ?>
                                <img src="<?php echo htmlspecialchars($order['memory_image']); ?>" 
                                     alt="Memory" style="width: 40px; height: 40px; object-fit: cover; border-radius: 4px; margin-right: 8px; vertical-align: middle;">
                            <?php endif; ?>
                            <?php echo htmlspecialchars($order['memory_title'] ?: 'Untitled'); ?>
                        </td>
                        <td><?php echo htmlspecialchars($order['product_id']); ?></td>
                        <td>$<?php echo number_format($order['amount_paid'] / 100, 2); ?></td>
                        <td>
                            <span style="padding: 4px 8px; border-radius: 4px; font-size: 12px; background: #d1fae5; color: #065f46;">
                                <?php echo ucfirst($order['status']); ?>
                            </span>
                        </td>
                        <td>
                            <?php if ($stripePayment): ?>
                                <div style="font-size: 12px;">
                                    <div style="font-family: monospace; color: #2563eb;">
                                        <?php echo substr($stripePayment['payment_intent_id'], -8); ?>
                                    </div>
                                    <div style="color: <?php echo $stripePayment['status'] === 'succeeded' ? '#059669' : '#dc2626'; ?>;">
                                        <?php echo ucfirst($stripePayment['status']); ?>
                                    </div>
                                    <?php if ($stripePayment['refunded']): ?>
                                        <div style="color: #dc2626; font-weight: bold;">
                                            Refunded: $<?php echo number_format($stripePayment['refund_amount'] / 100, 2); ?>
                                        </div>
                                    <?php endif; ?>
                                </div>
                            <?php else: ?>
                                <span style="color: #64748b; font-size: 12px;">No payment data</span>
                            <?php endif; ?>
                        </td>
                        <td style="font-family: monospace; font-size: 12px;"><?php echo $order['printful_order_id'] ?: 'Pending'; ?></td>
                        <td><?php echo date('M j, g:i A', strtotime($order['created_at'])); ?></td>
                        <td>
                            <div style="display: flex; gap: 4px; flex-wrap: wrap;">
                                <?php if ($stripePayment && $stripePayment['status'] === 'succeeded' && !$stripePayment['refunded']): ?>
                                    <button onclick="processRefund('<?php echo $order['id']; ?>', '<?php echo $stripePayment['payment_intent_id']; ?>', <?php echo $order['amount_paid']; ?>)" 
                                            class="refund-btn" style="background: #dc2626; color: white; border: none; padding: 4px 8px; border-radius: 4px; font-size: 11px; cursor: pointer;">
                                        Refund
                                    </button>
                                <?php endif; ?>
                                <button onclick="viewOrderDetails('<?php echo $order['id']; ?>')" 
                                        class="view-btn" style="background: #2563eb; color: white; border: none; padding: 4px 8px; border-radius: 4px; font-size: 11px; cursor: pointer;">
                                    View
                                </button>
                            </div>
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

        document.addEventListener('keydown', (e) => {
            if (e.key === 'Escape') {
                closeImageModal();
            }
        });

        function processRefund(orderId, paymentIntentId, amount) {
            const amountFormatted = (amount / 100).toFixed(2);
            
            Swal.fire({
                title: 'Process Refund',
                html: `
                    <p>Order ID: ${orderId}</p>
                    <p>Payment Intent: ${paymentIntentId}</p>
                    <p>Amount: $${amountFormatted}</p>
                    <div style="margin-top: 20px;">
                        <label for="refundReason">Refund Reason:</label>
                        <select id="refundReason" style="width: 100%; padding: 8px; margin-top: 5px; border: 1px solid #ddd; border-radius: 4px;">
                            <option value="requested_by_customer">Requested by Customer</option>
                            <option value="duplicate">Duplicate Payment</option>
                            <option value="fraudulent">Fraudulent</option>
                            <option value="product_defective">Product Defective</option>
                            <option value="order_cancelled">Order Cancelled</option>
                            <option value="other">Other</option>
                        </select>
                    </div>
                    <div style="margin-top: 15px;">
                        <label for="refundAmount">Refund Amount (leave empty for full refund):</label>
                        <input type="number" id="refundAmount" step="0.01" min="0" max="${amountFormatted}" 
                               style="width: 100%; padding: 8px; margin-top: 5px; border: 1px solid #ddd; border-radius: 4px;"
                               placeholder="$${amountFormatted}">
                    </div>
                `,
                showCancelButton: true,
                confirmButtonText: 'Process Refund',
                cancelButtonText: 'Cancel',
                confirmButtonColor: '#dc2626',
                preConfirm: () => {
                    const reason = document.getElementById('refundReason').value;
                    const refundAmount = document.getElementById('refundAmount').value;
                    return { reason, refundAmount };
                }
            }).then((result) => {
                if (result.isConfirmed) {
                    const { reason, refundAmount } = result.value;
                    const refundAmountCents = refundAmount ? Math.round(parseFloat(refundAmount) * 100) : amount;
                    
                    // Show loading
                    Swal.fire({
                        title: 'Processing Refund...',
                        text: 'Please wait while we process the refund',
                        allowOutsideClick: false,
                        showConfirmButton: false,
                        willOpen: () => {
                            Swal.showLoading();
                        }
                    });
                    
                    // Process refund
                    fetch('process_refund.php', {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json',
                        },
                        body: JSON.stringify({
                            order_id: orderId,
                            payment_intent_id: paymentIntentId,
                            amount: refundAmountCents,
                            reason: reason
                        })
                    })
                    .then(response => response.json())
                    .then(data => {
                        if (data.success) {
                            Swal.fire({
                                title: 'Refund Processed',
                                text: data.message,
                                icon: 'success',
                                confirmButtonText: 'OK'
                            }).then(() => {
                                location.reload();
                            });
                        } else {
                            Swal.fire({
                                title: 'Refund Failed',
                                text: data.error,
                                icon: 'error',
                                confirmButtonText: 'OK'
                            });
                        }
                    })
                    .catch(error => {
                        Swal.fire({
                            title: 'Error',
                            text: 'An error occurred while processing the refund',
                            icon: 'error',
                            confirmButtonText: 'OK'
                        });
                    });
                }
            });
        }

        function viewOrderDetails(orderId) {
            Swal.fire({
                title: 'Order Details',
                text: `Order ID: ${orderId}`,
                icon: 'info',
                confirmButtonText: 'OK'
            });
        }
    </script>
        </div>
    </div>
</body>
</html>

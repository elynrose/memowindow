<?php
// admin_orders.php - Orders management interface
require_once 'auth_check.php';
require_once 'config.php';
require_once 'PriceManager.php';

// Require admin authentication
$userFirebaseUID = requireAdmin();

$success = '';
$error = '';

// Handle POST requests
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        $pdo = new PDO("mysql:host=" . DB_HOST . ";dbname=" . DB_NAME . ";charset=utf8mb4", DB_USER, DB_PASS, [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        ]);
        
        $action = $_POST['action'] ?? '';
        
        switch ($action) {
            case 'sync_orders':
                $syncedCount = syncOrdersFromPrintful($pdo);
                $success = "✅ Successfully synced $syncedCount orders from Printful";
                break;
                
            case 'update_status':
                $orderId = intval($_POST['order_id']);
                $newStatus = $_POST['status'];
                
                $stmt = $pdo->prepare("UPDATE orders SET status = :status WHERE id = :id");
                $stmt->execute([':status' => $newStatus, ':id' => $orderId]);
                $success = "Order status updated to: $newStatus";
                break;
                
            case 'bulk_update':
                $orderIds = $_POST['order_ids'] ?? [];
                $newStatus = $_POST['bulk_status'];
                
                if (!empty($orderIds)) {
                    $placeholders = str_repeat('?,', count($orderIds) - 1) . '?';
                    $stmt = $pdo->prepare("UPDATE orders SET status = ? WHERE id IN ($placeholders)");
                    $stmt->execute(array_merge([$newStatus], $orderIds));
                    $success = "Updated " . count($orderIds) . " orders to status: $newStatus";
                }
                break;
        }
        
    } catch (Exception $e) {
        $error = "❌ Error: " . $e->getMessage();
    }
}

// Function to sync orders from Printful
function syncOrdersFromPrintful($pdo) {
    $syncedCount = 0;
    
    // Get all orders that have Printful order IDs
    $stmt = $pdo->query("SELECT id, printful_order_id FROM orders WHERE printful_order_id IS NOT NULL AND printful_order_id != ''");
    $orders = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    foreach ($orders as $order) {
        try {
            // Get order status from Printful
            $ch = curl_init();
            curl_setopt($ch, CURLOPT_URL, PRINTFUL_API_URL . 'orders/' . $order['printful_order_id']);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch, CURLOPT_HTTPHEADER, [
                'Authorization: Bearer ' . PRINTFUL_API_KEY,
                'Content-Type: application/json'
            ]);
            curl_setopt($ch, CURLOPT_TIMEOUT, 30);
            
            $response = curl_exec($ch);
            $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
            curl_close($ch);
            
            if ($httpCode === 200) {
                $data = json_decode($response, true);
                if (isset($data['result']['status'])) {
                    $printfulStatus = $data['result']['status'];
                    
                    // Map Printful status to our status
                    $mappedStatus = mapPrintfulStatus($printfulStatus);
                    
                    // Update our database
                    $updateStmt = $pdo->prepare("UPDATE orders SET status = :status WHERE id = :id");
                    $updateStmt->execute([':status' => $mappedStatus, ':id' => $order['id']]);
                    
                    $syncedCount++;
                }
            }
            
        } catch (Exception $e) {
            error_log("Failed to sync order {$order['id']}: " . $e->getMessage());
        }
    }
    
    return $syncedCount;
}

// Function to map Printful status to our status
function mapPrintfulStatus($printfulStatus) {
    $statusMap = [
        'draft' => 'pending',
        'pending' => 'pending',
        'failed' => 'failed',
        'canceled' => 'cancelled',
        'onhold' => 'on_hold',
        'inprocess' => 'processing',
        'fulfilled' => 'fulfilled',
        'returned' => 'returned'
    ];
    
    return $statusMap[$printfulStatus] ?? 'pending';
}

// Get orders with pagination
try {
    $pdo = new PDO("mysql:host=" . DB_HOST . ";dbname=" . DB_NAME . ";charset=utf8mb4", DB_USER, DB_PASS, [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
    ]);
    
    $itemsPerPage = 20;
    $currentPage = isset($_GET['page']) ? max(1, intval($_GET['page'])) : 1;
    $offset = ($currentPage - 1) * $itemsPerPage;
    
    // Get total count
    $totalCount = $pdo->query("SELECT COUNT(*) as count FROM orders")->fetch()['count'];
    $totalPages = ceil($totalCount / $itemsPerPage);
    
    // Get orders with product info
    $stmt = $pdo->prepare("
        SELECT 
            o.*,
            p.name as product_name,
            p.size as product_size,
            p.material as product_material
        FROM orders o
        LEFT JOIN print_products p ON o.product_id = p.product_key
        ORDER BY o.created_at DESC
        LIMIT $itemsPerPage OFFSET $offset
    ");
    $stmt->execute();
    $orders = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
} catch (Exception $e) {
    $error = "❌ Database error: " . $e->getMessage();
    $orders = [];
    $totalPages = 0;
}

// Status options
$statusOptions = [
    'pending' => 'Pending',
    'processing' => 'Processing', 
    'on_hold' => 'On Hold',
    'fulfilled' => 'Fulfilled',
    'cancelled' => 'Cancelled',
    'failed' => 'Failed',
    'returned' => 'Returned'
];
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Orders - MemoWindow</title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif; background: #f8fafc; color: #1e293b; }
        .container { max-width: 1400px; margin: 0 auto; padding: 20px; }
        .header { background: white; border-radius: 12px; padding: 24px; margin-bottom: 24px; box-shadow: 0 1px 3px rgba(0,0,0,0.1); }
        .header h1 { font-size: 28px; font-weight: 700; color: #0f172a; margin-bottom: 8px; }
        .header p { color: #64748b; font-size: 16px; }
        
        .actions { background: white; border-radius: 12px; padding: 20px; margin-bottom: 24px; box-shadow: 0 1px 3px rgba(0,0,0,0.1); }
        .actions h3 { margin-bottom: 16px; color: #0f172a; }
        .action-buttons { display: flex; gap: 12px; flex-wrap: wrap; }
        .btn { padding: 10px 20px; border: none; border-radius: 8px; cursor: pointer; font-weight: 500; text-decoration: none; display: inline-flex; align-items: center; gap: 8px; }
        .btn-primary { background: #3b82f6; color: white; }
        .btn-success { background: #10b981; color: white; }
        .btn-warning { background: #f59e0b; color: white; }
        .btn-danger { background: #ef4444; color: white; }
        .btn-secondary { background: #6b7280; color: white; }
        .btn:hover { opacity: 0.9; transform: translateY(-1px); }
        
        .bulk-actions { background: #f1f5f9; border-radius: 8px; padding: 16px; margin-bottom: 20px; display: none; }
        .bulk-actions.show { display: block; }
        .bulk-actions select { padding: 8px 12px; border: 1px solid #d1d5db; border-radius: 6px; margin-right: 12px; }
        
        .orders-table { background: white; border-radius: 12px; overflow: hidden; box-shadow: 0 1px 3px rgba(0,0,0,0.1); }
        .table { width: 100%; border-collapse: collapse; table-layout: fixed; }
        .table th { background: #f8fafc; padding: 16px; text-align: left; font-weight: 600; color: #374151; border-bottom: 1px solid #e5e7eb; }
        .table td { padding: 16px; border-bottom: 1px solid #f1f5f9; overflow: hidden; text-overflow: ellipsis; }
        .table th:nth-child(1), .table td:nth-child(1) { width: 50px; } /* Checkbox */
        .table th:nth-child(2), .table td:nth-child(2) { width: 200px; } /* Order ID */
        .table th:nth-child(3), .table td:nth-child(3) { width: 200px; } /* Customer */
        .table th:nth-child(4), .table td:nth-child(4) { width: 180px; } /* Product */
        .table th:nth-child(5), .table td:nth-child(5) { width: 100px; } /* Amount */
        .table th:nth-child(6), .table td:nth-child(6) { width: 120px; } /* Status */
        .table th:nth-child(7), .table td:nth-child(7) { width: 140px; } /* Created */
        .table th:nth-child(8), .table td:nth-child(8) { width: 120px; } /* Printful ID */
        .table th:nth-child(9), .table td:nth-child(9) { width: 150px; } /* Actions */
        .table tr:hover { background: #f8fafc; }
        
        .status { padding: 4px 12px; border-radius: 20px; font-size: 12px; font-weight: 500; text-transform: uppercase; }
        .status-pending { background: #fef3c7; color: #92400e; }
        .status-processing { background: #dbeafe; color: #1e40af; }
        .status-on_hold { background: #f3e8ff; color: #7c3aed; }
        .status-fulfilled { background: #d1fae5; color: #065f46; }
        .status-cancelled { background: #fee2e2; color: #991b1b; }
        .status-failed { background: #fecaca; color: #dc2626; }
        .status-returned { background: #fde68a; color: #b45309; }
        
        .order-actions { display: flex; gap: 8px; }
        .order-actions select { padding: 4px 8px; border: 1px solid #d1d5db; border-radius: 4px; font-size: 12px; }
        .order-actions button { padding: 4px 8px; border: none; border-radius: 4px; cursor: pointer; font-size: 12px; }
        
        .pagination { display: flex; justify-content: center; align-items: center; gap: 8px; margin-top: 24px; }
        .pagination a, .pagination span { padding: 8px 12px; border: 1px solid #d1d5db; border-radius: 6px; text-decoration: none; color: #374151; }
        .pagination .current { background: #3b82f6; color: white; border-color: #3b82f6; }
        .pagination a:hover { background: #f3f4f6; }
        
        .alert { padding: 12px 16px; border-radius: 8px; margin-bottom: 20px; }
        .alert-success { background: #d1fae5; color: #065f46; border: 1px solid #a7f3d0; }
        .alert-error { background: #fee2e2; color: #991b1b; border: 1px solid #fca5a5; }
        
        .order-details { font-size: 12px; color: #64748b; }
        .order-id { font-weight: 600; color: #374151; }
        .customer-info { margin-top: 4px; }
        
        .checkbox { margin-right: 8px; }
        
        @media (max-width: 768px) {
            .table { font-size: 14px; }
            .table th, .table td { padding: 12px 8px; }
            .action-buttons { flex-direction: column; }
            .btn { justify-content: center; }
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1>📦 Order Management</h1>
            <p>Manage orders, sync with Printful, and update order status</p>
        </div>
        
        <?php if ($success): ?>
            <div class="alert alert-success"><?php echo htmlspecialchars($success); ?></div>
        <?php endif; ?>
        
        <?php if ($error): ?>
            <div class="alert alert-error"><?php echo htmlspecialchars($error); ?></div>
        <?php endif; ?>
        
        <div class="actions">
            <h3>Order Actions</h3>
            <div class="action-buttons">
                <button onclick="syncOrders()" class="btn btn-primary">
                    🔄 Sync Orders from Printful
                </button>
                <button onclick="selectAllOrders()" class="btn btn-secondary">
                    ☑️ Select All
                </button>
                <button onclick="clearSelection()" class="btn btn-secondary">
                    ☐ Clear Selection
                </button>
            </div>
        </div>
        
        <div class="bulk-actions" id="bulkActions">
            <strong>Bulk Actions:</strong>
            <select id="bulkStatus">
                <?php foreach ($statusOptions as $value => $label): ?>
                    <option value="<?php echo $value; ?>"><?php echo $label; ?></option>
                <?php endforeach; ?>
            </select>
            <button onclick="bulkUpdateStatus()" class="btn btn-warning">Update Selected</button>
            <span id="selectedCount">0 orders selected</span>
        </div>
        
        <div class="orders-table">
            <table class="table">
                <thead>
                    <tr>
                        <th><input type="checkbox" id="selectAll" onchange="toggleSelectAll()"></th>
                        <th>Order ID</th>
                        <th>Customer</th>
                        <th>Product</th>
                        <th>Amount</th>
                        <th>Status</th>
                        <th>Created</th>
                        <th>Printful ID</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($orders)): ?>
                        <tr>
                            <td colspan="9" style="text-align: center; padding: 40px; color: #64748b;">
                                No orders found
                            </td>
                        </tr>
                    <?php else: ?>
                        <?php foreach ($orders as $order): ?>
                            <tr>
                                <td>
                                    <input type="checkbox" class="checkbox order-checkbox" value="<?php echo $order['id']; ?>" onchange="updateBulkActions()">
                                </td>
                                <td>
                                    <div class="order-id">#<?php echo $order['id']; ?></div>
                                    <div class="order-details" title="<?php echo htmlspecialchars($order['stripe_session_id']); ?>">
                                        <?php 
                                        $sessionId = $order['stripe_session_id'];
                                        echo htmlspecialchars(strlen($sessionId) > 20 ? substr($sessionId, 0, 20) . '...' : $sessionId);
                                        ?>
                                    </div>
                                </td>
                                <td>
                                    <div><?php echo htmlspecialchars($order['customer_name']); ?></div>
                                    <div class="customer-info"><?php echo htmlspecialchars($order['customer_email']); ?></div>
                                </td>
                                <td>
                                    <div><?php echo htmlspecialchars($order['product_name'] ?? 'Unknown Product'); ?></div>
                                    <div class="order-details">
                                        <?php if ($order['product_size']): ?>
                                            <?php echo htmlspecialchars($order['product_size']); ?>
                                        <?php endif; ?>
                                    </div>
                                </td>
                                <td><?php echo PriceManager::formatPrice($order['amount_paid']); ?></td>
                                <td>
                                    <span class="status status-<?php echo $order['status']; ?>">
                                        <?php echo $statusOptions[$order['status']] ?? $order['status']; ?>
                                    </span>
                                </td>
                                <td><?php echo date('M j, Y g:i A', strtotime($order['created_at'])); ?></td>
                                <td>
                                    <?php if ($order['printful_order_id']): ?>
                                        <a href="https://www.printful.com/dashboard/orders/<?php echo $order['printful_order_id']; ?>" target="_blank" style="color: #3b82f6;">
                                            <?php echo htmlspecialchars($order['printful_order_id']); ?>
                                        </a>
                                    <?php else: ?>
                                        <span style="color: #9ca3af;">Not created</span>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <div class="order-actions">
                                        <select onchange="updateOrderStatus(<?php echo $order['id']; ?>, this.value)">
                                            <?php foreach ($statusOptions as $value => $label): ?>
                                                <option value="<?php echo $value; ?>" <?php echo $order['status'] === $value ? 'selected' : ''; ?>>
                                                    <?php echo $label; ?>
                                                </option>
                                            <?php endforeach; ?>
                                        </select>
                                    </div>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
        
        <?php if ($totalPages > 1): ?>
            <div class="pagination">
                <?php if ($currentPage > 1): ?>
                    <a href="?user_id=<?php echo $userFirebaseUID; ?>&page=<?php echo $currentPage - 1; ?>">← Previous</a>
                <?php endif; ?>
                
                <?php for ($i = max(1, $currentPage - 2); $i <= min($totalPages, $currentPage + 2); $i++): ?>
                    <?php if ($i === $currentPage): ?>
                        <span class="current"><?php echo $i; ?></span>
                    <?php else: ?>
                        <a href="?user_id=<?php echo $userFirebaseUID; ?>&page=<?php echo $i; ?>"><?php echo $i; ?></a>
                    <?php endif; ?>
                <?php endfor; ?>
                
                <?php if ($currentPage < $totalPages): ?>
                    <a href="?user_id=<?php echo $userFirebaseUID; ?>&page=<?php echo $currentPage + 1; ?>">Next →</a>
                <?php endif; ?>
            </div>
        <?php endif; ?>
    </div>
    
    <script>
        function syncOrders() {
            if (confirm('Sync all orders with Printful? This will update order statuses based on Printful data.')) {
                const form = document.createElement('form');
                form.method = 'POST';
                form.innerHTML = '<input type="hidden" name="action" value="sync_orders">';
                document.body.appendChild(form);
                form.submit();
            }
        }
        
        function updateOrderStatus(orderId, newStatus) {
            if (confirm(`Update order #${orderId} status to ${newStatus}?`)) {
                const form = document.createElement('form');
                form.method = 'POST';
                form.innerHTML = `
                    <input type="hidden" name="action" value="update_status">
                    <input type="hidden" name="order_id" value="${orderId}">
                    <input type="hidden" name="status" value="${newStatus}">
                `;
                document.body.appendChild(form);
                form.submit();
            }
        }
        
        function toggleSelectAll() {
            const selectAll = document.getElementById('selectAll');
            const checkboxes = document.querySelectorAll('.order-checkbox');
            checkboxes.forEach(checkbox => {
                checkbox.checked = selectAll.checked;
            });
            updateBulkActions();
        }
        
        function selectAllOrders() {
            const checkboxes = document.querySelectorAll('.order-checkbox');
            checkboxes.forEach(checkbox => {
                checkbox.checked = true;
            });
            document.getElementById('selectAll').checked = true;
            updateBulkActions();
        }
        
        function clearSelection() {
            const checkboxes = document.querySelectorAll('.order-checkbox');
            checkboxes.forEach(checkbox => {
                checkbox.checked = false;
            });
            document.getElementById('selectAll').checked = false;
            updateBulkActions();
        }
        
        function updateBulkActions() {
            const checkboxes = document.querySelectorAll('.order-checkbox:checked');
            const bulkActions = document.getElementById('bulkActions');
            const selectedCount = document.getElementById('selectedCount');
            
            if (checkboxes.length > 0) {
                bulkActions.classList.add('show');
                selectedCount.textContent = `${checkboxes.length} orders selected`;
            } else {
                bulkActions.classList.remove('show');
            }
        }
        
        function bulkUpdateStatus() {
            const checkboxes = document.querySelectorAll('.order-checkbox:checked');
            const newStatus = document.getElementById('bulkStatus').value;
            
            if (checkboxes.length === 0) {
                alert('Please select at least one order');
                return;
            }
            
            if (confirm(`Update ${checkboxes.length} orders to status: ${newStatus}?`)) {
                const form = document.createElement('form');
                form.method = 'POST';
                form.innerHTML = `
                    <input type="hidden" name="action" value="bulk_update">
                    <input type="hidden" name="bulk_status" value="${newStatus}">
                `;
                
                checkboxes.forEach(checkbox => {
                    const input = document.createElement('input');
                    input.type = 'hidden';
                    input.name = 'order_ids[]';
                    input.value = checkbox.value;
                    form.appendChild(input);
                });
                
                document.body.appendChild(form);
                form.submit();
            }
        }
        
        // Update bulk actions on page load
        document.addEventListener('DOMContentLoaded', function() {
            updateBulkActions();
        });
    </script>
</body>
</html>
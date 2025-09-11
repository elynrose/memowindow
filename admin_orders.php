<?php
// admin_orders.php - Orders management interface
require_once 'secure_auth.php';
require_once 'config.php';
require_once 'PriceManager.php';

// Check session timeout
if (!checkSessionTimeout()) {
    header('Location: ' . BASE_URL . '/login.php?error=session_expired');
    exit;
}

// Require admin authentication
$userFirebaseUID = requireSecureAdmin();

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
        LEFT JOIN print_products p ON o.product_variant_id = p.product_key
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
    <link rel="stylesheet" href="includes/admin_styles.css">
    <style>
        /* Page-specific styles */
        .bulk-actions { background: #f1f5f9; border-radius: 8px; padding: 16px; margin-bottom: 20px; display: none; }
        .bulk-actions.show { display: block; }
        .bulk-actions select { padding: 8px 12px; border: 1px solid #d1d5db; border-radius: 6px; margin-right: 12px; }
        
        .order-actions { display: flex; gap: 8px; }
        .order-actions select { padding: 4px 8px; border: 1px solid #d1d5db; border-radius: 4px; font-size: 12px; }
        .order-actions button { padding: 4px 8px; border: none; border-radius: 4px; cursor: pointer; font-size: 12px; }
        
        .order-details { font-size: 12px; color: #64748b; }
        .order-id { font-weight: 600; color: #374151; }
        .customer-info { margin-top: 4px; }
        
        .checkbox { margin-right: 8px; }
        
        /* Table column widths */
        .admin-table th:nth-child(1), .admin-table td:nth-child(1) { width: 50px; } /* Checkbox */
        .admin-table th:nth-child(2), .admin-table td:nth-child(2) { width: 200px; } /* Order ID */
        .admin-table th:nth-child(3), .admin-table td:nth-child(3) { width: 200px; } /* Customer */
        .admin-table th:nth-child(4), .admin-table td:nth-child(4) { width: 180px; } /* Product */
        .admin-table th:nth-child(5), .admin-table td:nth-child(5) { width: 100px; } /* Amount */
        .admin-table th:nth-child(6), .admin-table td:nth-child(6) { width: 120px; } /* Status */
        .admin-table th:nth-child(7), .admin-table td:nth-child(7) { width: 140px; } /* Created */
        .admin-table th:nth-child(8), .admin-table td:nth-child(8) { width: 120px; } /* Printful ID */
        .admin-table th:nth-child(9), .admin-table td:nth-child(9) { width: 150px; } /* Actions */
    </style>
</head>
<body>
    <div class="admin-container">
        <div class="admin-header">
            <h1>📦 Order Management</h1>
            <p>Manage orders, sync with Printful, and update order status</p>
        </div>
        
        <?php include 'includes/admin_navigation.php'; ?>
        
        <div class="admin-content">
            <?php if ($success): ?>
                <div class="admin-alert admin-alert-success"><?php echo htmlspecialchars($success); ?></div>
            <?php endif; ?>
            
            <?php if ($error): ?>
                <div class="admin-alert admin-alert-error"><?php echo htmlspecialchars($error); ?></div>
            <?php endif; ?>
            
            <div class="admin-card">
                <div class="admin-card-header">
                    <h2>Order Actions</h2>
                    <p>Manage and sync orders with Printful</p>
                </div>
                <div class="admin-btn-group">
                    <button onclick="syncOrders()" class="admin-btn admin-btn-primary">
                        🔄 Sync Orders from Printful
                    </button>
                    <button onclick="selectAllOrders()" class="admin-btn admin-btn-secondary">
                        ☑️ Select All
                    </button>
                    <button onclick="clearSelection()" class="admin-btn admin-btn-secondary">
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
            <button onclick="bulkUpdateStatus()" class="admin-btn admin-btn-warning">Update Selected</button>
            <span id="selectedCount">0 orders selected</span>
        </div>
        
            <div class="admin-table-container">
                <table class="admin-table">
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
                                    <span class="admin-status admin-status-<?php echo $order['status']; ?>">
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
                <div class="admin-pagination">
                <?php if ($currentPage > 1): ?>
                    <a href="?page=<?php echo $currentPage - 1; ?>">← Previous</a>
                <?php endif; ?>
                
                <?php for ($i = max(1, $currentPage - 2); $i <= min($totalPages, $currentPage + 2); $i++): ?>
                    <?php if ($i === $currentPage): ?>
                        <span class="current"><?php echo $i; ?></span>
                    <?php else: ?>
                        <a href="?page=<?php echo $i; ?>"><?php echo $i; ?></a>
                    <?php endif; ?>
                <?php endfor; ?>
                
                <?php if ($currentPage < $totalPages): ?>
                    <a href="?page=<?php echo $currentPage + 1; ?>">Next →</a>
                <?php endif; ?>
                </div>
            <?php endif; ?>
        </div>
    </div>
    
    <!-- SweetAlert2 -->
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    
    <script>
        function syncOrders() {
            Swal.fire({
                title: 'Sync Orders?',
                text: 'Sync all orders with Printful? This will update order statuses based on Printful data.',
                icon: 'question',
                showCancelButton: true,
                confirmButtonColor: '#667eea',
                cancelButtonColor: '#d33',
                confirmButtonText: 'Yes, sync orders',
                cancelButtonText: 'Cancel'
            }).then((result) => {
                if (result.isConfirmed) {
                    const form = document.createElement('form');
                    form.method = 'POST';
                    form.innerHTML = '<input type="hidden" name="action" value="sync_orders">';
                    document.body.appendChild(form);
                    form.submit();
                }
            });
        }
        
        function updateOrderStatus(orderId, newStatus) {
            Swal.fire({
                title: 'Update Order Status?',
                text: `Update order #${orderId} status to ${newStatus}?`,
                icon: 'question',
                showCancelButton: true,
                confirmButtonColor: '#667eea',
                cancelButtonColor: '#d33',
                confirmButtonText: 'Yes, update',
                cancelButtonText: 'Cancel'
            }).then((result) => {
                if (result.isConfirmed) {
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
            });
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
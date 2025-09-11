<?php
// admin_products.php - Print products management interface
require_once 'auth_check.php';
require_once 'config.php';
require_once 'PriceManager.php';

// Require admin authentication
$userFirebaseUID = requireAdmin();

// User is already verified as admin by requireAdmin()
try {
    $pdo = new PDO("mysql:host=" . DB_HOST . ";dbname=" . DB_NAME . ";charset=utf8mb4", DB_USER, DB_PASS, [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
    ]);
    
    // Create products table if it doesn't exist
    $pdo->exec("
        CREATE TABLE IF NOT EXISTS print_products (
            id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
            product_key VARCHAR(100) NOT NULL UNIQUE,
            printful_id BIGINT NOT NULL,
            name VARCHAR(255) NOT NULL,
            description TEXT,
            price INT NOT NULL,
            size VARCHAR(100),
            material VARCHAR(255),
            is_active BOOLEAN DEFAULT TRUE,
            sort_order INT DEFAULT 0,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            INDEX idx_active (is_active),
            INDEX idx_sort (sort_order)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4
    ");
    
    // Import current products from config if table is empty
    $count = $pdo->query("SELECT COUNT(*) as count FROM print_products")->fetch()['count'];
    if ($count == 0) {
        foreach ($GLOBALS['PRINT_PRODUCTS'] as $key => $product) {
            $pdo->prepare("
                INSERT INTO print_products 
                (product_key, printful_id, name, description, price, size, material, sort_order) 
                VALUES (:key, :printful_id, :name, :description, :price, :size, :material, :sort_order)
            ")->execute([
                ':key' => $key,
                ':printful_id' => $product['printful_id'],
                ':name' => $product['name'],
                ':description' => $product['description'],
                ':price' => $product['price'],
                ':size' => $product['size'],
                ':material' => $product['material'],
                ':sort_order' => array_search($key, array_keys($GLOBALS['PRINT_PRODUCTS']))
            ]);
        }
    }
    
} catch (PDOException $e) {
    http_response_code(500);
    echo "Database error: " . $e->getMessage();
    exit;
}

// Handle product management actions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    
    try {
        switch ($action) {
            case 'add_product':
                $stmt = $pdo->prepare("
                    INSERT INTO print_products 
                    (product_key, printful_id, name, description, price, size, material, sort_order) 
                    VALUES (:key, :printful_id, :name, :description, :price, :size, :material, :sort_order)
                ");
                $stmt->execute([
                    ':key' => $_POST['product_key'],
                    ':printful_id' => $_POST['printful_id'],
                    ':name' => $_POST['name'],
                    ':description' => $_POST['description'],
                    ':price' => PriceManager::forDatabase($_POST['price']),
                    ':size' => $_POST['size'],
                    ':material' => $_POST['material'],
                    ':sort_order' => intval($_POST['sort_order'] ?? 0)
                ]);
                $success = "Product added successfully";
                break;
                
            case 'update_product':
                $stmt = $pdo->prepare("
                    UPDATE print_products 
                    SET name = :name, description = :description, price = :price, 
                        size = :size, material = :material, sort_order = :sort_order,
                        is_active = :is_active
                    WHERE id = :id
                ");
                $stmt->execute([
                    ':id' => intval($_POST['product_id']),
                    ':name' => $_POST['name'],
                    ':description' => $_POST['description'],
                    ':price' => PriceManager::forDatabase($_POST['price']),
                    ':size' => $_POST['size'],
                    ':material' => $_POST['material'],
                    ':sort_order' => intval($_POST['sort_order'] ?? 0),
                    ':is_active' => isset($_POST['is_active']) ? 1 : 0
                ]);
                $success = "Product updated successfully";
                break;
                
            case 'delete_product':
                $pdo->prepare("DELETE FROM print_products WHERE id = :id")
                    ->execute([':id' => intval($_POST['product_id'])]);
                $success = "Product deleted successfully";
                break;
                
            case 'toggle_status':
                $stmt = $pdo->prepare("UPDATE print_products SET is_active = :is_active WHERE id = :id");
                $stmt->execute([
                    ':id' => intval($_POST['product_id']),
                    ':is_active' => intval($_POST['is_active'])
                ]);
                $status = intval($_POST['is_active']) ? 'activated' : 'deactivated';
                $success = "Product $status successfully";
                break;
                
            case 'sync_products':
                // First, delete all existing products to ensure clean sync
                try {
                    $deletedCount = $pdo->query("SELECT COUNT(*) as count FROM print_products")->fetch()['count'];
                    $pdo->exec("DELETE FROM print_products");
                } catch (Exception $e) {
                    $error = "❌ Failed to clear existing products: " . $e->getMessage();
                    break;
                }
                
                $syncResult = syncProductsFromPrintful($pdo);
                if ($syncResult['success']) {
                    $newCount = $syncResult['new'] ?? 0;
                    $totalCount = $syncResult['synced'] ?? 0;
                    
                    $success = "✅ Sync completed: Cleared $deletedCount old products, added $newCount new products from Printful store";
                    if (isset($syncResult['total_products'])) {
                        $success .= " (processed {$syncResult['total_products']} products from Printful)";
                    }
                } else {
                    $error = "❌ Sync failed: " . $syncResult['error'];
                }
                break;
        }
    } catch (PDOException $e) {
        $error = "Database error: " . $e->getMessage();
    }
}

// Pagination settings
$itemsPerPage = 20;
$currentPage = isset($_GET['page']) ? max(1, intval($_GET['page'])) : 1;
$offset = ($currentPage - 1) * $itemsPerPage;

// Get total count for pagination
try {
    $totalCount = $pdo->query("SELECT COUNT(*) as count FROM print_products")->fetch()['count'];
    $totalPages = ceil($totalCount / $itemsPerPage);
} catch (PDOException $e) {
    $totalCount = 0;
    $totalPages = 0;
}

// Get current products with pagination
try {
    $products = $pdo->query("
        SELECT 
            p.*,
            COUNT(o.id) as order_count,
            SUM(o.amount_paid) / 100 as total_revenue
        FROM print_products p
        LEFT JOIN orders o ON p.product_key = o.product_variant_id
        GROUP BY p.id
        ORDER BY p.sort_order ASC, p.name ASC
        LIMIT $itemsPerPage OFFSET $offset
    ")->fetchAll(PDO::FETCH_ASSOC);
    
} catch (PDOException $e) {
    $products = [];
    $error = $e->getMessage();
}

// Function to sync products from Printful
function syncProductsFromPrintful($pdo) {
    try {
        // Initialize cURL
        $ch = curl_init();
        
        // Set up the request to get products from your store (not catalog)
        curl_setopt($ch, CURLOPT_URL, PRINTFUL_API_URL . 'store/products');
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_HTTPHEADER, [
            'Authorization: Bearer ' . PRINTFUL_API_KEY,
            'Content-Type: application/json',
            'X-PF-Store-Id: ' . PRINTFUL_STORE_ID
        ]);
        
        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);
        
        if ($httpCode !== 200) {
            return ['success' => false, 'error' => "Printful API error (HTTP $httpCode): $response"];
        }
        
        $data = json_decode($response, true);
        
        if (!isset($data['result']) || !is_array($data['result'])) {
            return ['success' => false, 'error' => 'Invalid response from Printful API'];
        }
        
        $synced = 0;
        $totalProducts = count($data['result']);
        
        // Process each store product (sync template)
        foreach ($data['result'] as $index => $storeProduct) {
            // Add progress feedback for large syncs
            if ($totalProducts > 50 && $index % 10 == 0) {
                error_log("Sync progress: Processing product " . ($index + 1) . " of $totalProducts");
            }
            if (!isset($storeProduct['id'])) {
                continue;
            }
            
            // Get detailed info for this store product including variants
            $ch = curl_init();
            curl_setopt($ch, CURLOPT_URL, PRINTFUL_API_URL . 'store/products/' . $storeProduct['id']);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch, CURLOPT_TIMEOUT, 30); // 30 second timeout
            curl_setopt($ch, CURLOPT_HTTPHEADER, [
                'Authorization: Bearer ' . PRINTFUL_API_KEY,
                'Content-Type: application/json',
                'X-PF-Store-Id: ' . PRINTFUL_STORE_ID
            ]);
            
            $variantResponse = curl_exec($ch);
            $variantHttpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
            $curlError = curl_error($ch);
            curl_close($ch);
            
            if ($curlError) {
                error_log("cURL error for store product {$storeProduct['id']}: $curlError");
                continue;
            }
            
            if ($variantHttpCode !== 200) {
                continue; // Skip this product if we can't get variants
            }
            
            $variantData = json_decode($variantResponse, true);
            if (!isset($variantData['result']['sync_variants']) || !is_array($variantData['result']['sync_variants'])) {
                continue;
            }
            
            $variants = $variantData['result']['sync_variants'];
            $productInfo = $variantData['result']['sync_product'];
            
            foreach ($variants as $variant) {
                if (!isset($variant['id']) || !isset($variant['name'])) {
                    continue;
                }
                
                // Create a product key from the variant
                $productKey = 'printful_' . $variant['id'];
                
                // Extract size and material from variant
                $size = $variant['size'] ?? 'Standard';
                $material = 'Premium'; // Default material
                if (isset($variant['product']['name'])) {
                    // Extract material from product name if available
                    $material = $variant['product']['name'];
                }
                
                // Calculate price using PriceManager
                $price = isset($variant['retail_price']) ? PriceManager::forDatabase($variant['retail_price']) : 0;
                
                // Clean and sanitize text fields to prevent encoding issues
                $variantName = html_entity_decode($variant['name'], ENT_QUOTES | ENT_HTML5, 'UTF-8');
                $variantName = preg_replace('/[\x00-\x08\x0B\x0C\x0E-\x1F\x7F]/', '', $variantName); // Remove control characters
                $variantName = trim($variantName);
                
                $productDescription = html_entity_decode($productInfo['description'] ?? $variant['name'], ENT_QUOTES | ENT_HTML5, 'UTF-8');
                $productDescription = preg_replace('/[\x00-\x08\x0B\x0C\x0E-\x1F\x7F]/', '', $productDescription); // Remove control characters
                $productDescription = trim($productDescription);
                
                $size = html_entity_decode($size, ENT_QUOTES | ENT_HTML5, 'UTF-8');
                $size = preg_replace('/[\x00-\x08\x0B\x0C\x0E-\x1F\x7F]/', '', $size);
                $size = trim($size);
                
                $material = html_entity_decode($material, ENT_QUOTES | ENT_HTML5, 'UTF-8');
                $material = preg_replace('/[\x00-\x08\x0B\x0C\x0E-\x1F\x7F]/', '', $material);
                $material = trim($material);
                
                // Insert new product (since we cleared the table, all products are new)
                $insertStmt = $pdo->prepare("
                    INSERT INTO print_products 
                    (product_key, printful_variant_id, name, description, price, size, material, is_active, sort_order) 
                    VALUES (?, ?, ?, ?, ?, ?, ?, 1, ?)
                ");
                    $insertStmt->execute([
                        $productKey,
                        $variant['variant_id'], // Use variant_id instead of id
                        $variantName,
                        $productDescription,
                        $price,
                        $size,
                        $material,
                        $synced
                    ]);
                $synced++;
            }
        }
        
        return [
            'success' => true, 
            'synced' => $synced,
            'new' => $synced,
            'total_products' => $totalProducts
        ];
        
    } catch (Exception $e) {
        return ['success' => false, 'error' => $e->getMessage()];
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Product Management - MemoWindow Admin</title>
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
        }
        .header h1 {
            margin: 0 0 8px 0;
            font-size: 28px;
            font-weight: 600;
        }
        .nav-links {
            display: flex;
            gap: 12px;
            flex-wrap: wrap;
            margin-top: 16px;
        }
        .nav-link {
            background: rgba(255,255,255,0.2);
            color: white;
            padding: 8px 16px;
            border-radius: 8px;
            text-decoration: none;
            font-size: 14px;
        }
        .section {
            background: white;
            border-radius: 12px;
            padding: 24px;
            margin-bottom: 24px;
            border: 1px solid #e2e8f0;
        }
        .product-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
            gap: 20px;
        }
        .product-card {
            border: 1px solid #e2e8f0;
            border-radius: 8px;
            padding: 20px 70px 20px 20px;
            background: #fafbfc;
            position: relative;
        }
        .product-card.inactive {
            opacity: 0.6;
            background: #f1f5f9;
        }
        .product-header {
            display: flex;
            justify-content: space-between;
            align-items: start;
            margin-bottom: 16px;
        }
        .product-title {
            font-size: 18px;
            font-weight: 600;
            color: #1e293b;
            margin: 0 0 4px 0;
        }
        .product-price {
            font-size: 24px;
            font-weight: 700;
            color: #059669;
        }
        .product-stats {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 12px;
            margin: 16px 0;
            padding: 12px;
            background: white;
            border-radius: 6px;
        }
        .stat-item {
            text-align: center;
        }
        .stat-number {
            font-size: 16px;
            font-weight: 600;
            color: #2563eb;
        }
        .stat-label {
            font-size: 12px;
            color: #64748b;
        }
        .product-actions {
            display: flex;
            gap: 8px;
            margin-top: 16px;
        }
        .btn {
            padding: 8px 12px;
            border: none;
            border-radius: 6px;
            cursor: pointer;
            font-size: 12px;
            font-weight: 500;
            text-decoration: none;
            text-align: center;
        }
        .btn-primary { background: #2563eb; color: white; }
        .btn-success { background: #059669; color: white; }
        .btn-warning { background: #d97706; color: white; }
        .btn-danger { background: #dc2626; color: white; }
        .form-grid {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 20px;
        }
        .form-group {
            margin-bottom: 20px;
        }
        .form-label {
            display: block;
            margin-bottom: 6px;
            font-weight: 500;
            color: #374151;
            font-size: 14px;
        }
        .form-input {
            width: 100%;
            padding: 10px 12px;
            border: 1px solid #d1d5db;
            border-radius: 6px;
            font-size: 14px;
            box-sizing: border-box;
        }
        .form-textarea {
            width: 100%;
            padding: 10px 12px;
            border: 1px solid #d1d5db;
            border-radius: 6px;
            font-size: 14px;
            min-height: 80px;
            resize: vertical;
            box-sizing: border-box;
        }
        .form-full-width {
            grid-column: 1 / -1;
        }
        .alert {
            padding: 12px;
            border-radius: 6px;
            margin-bottom: 20px;
        }
        .alert-success {
            background: #d1fae5;
            color: #065f46;
            border: 1px solid #a7f3d0;
        }
        .alert-error {
            background: #fee2e2;
            color: #991b1b;
            border: 1px solid #fca5a5;
        }
        .status-toggle {
            position: absolute;
            top: 20px;
            right: 20px;
            width: 44px;
            height: 22px;
            background: #ccc;
            border-radius: 22px;
            cursor: pointer;
            transition: background 0.3s;
        }
        .status-toggle.active {
            background: #059669;
        }
        .status-toggle::before {
            content: '';
            position: absolute;
            top: 2px;
            left: 2px;
            width: 18px;
            height: 18px;
            background: white;
            border-radius: 50%;
            transition: transform 0.3s;
            box-shadow: 0 2px 4px rgba(0,0,0,0.2);
        }
        .status-toggle.active::before {
            transform: translateX(22px);
        }
        
        .pagination {
            display: flex;
            justify-content: center;
            align-items: center;
            gap: 8px;
            margin-top: 24px;
            flex-wrap: wrap;
        }
        
        .pagination-btn {
            padding: 8px 12px;
            border: 1px solid #d1d5db;
            border-radius: 6px;
            background: white;
            color: #374151;
            text-decoration: none;
            font-size: 14px;
            font-weight: 500;
            transition: all 0.2s;
        }
        
        .pagination-btn:hover {
            background: #f3f4f6;
            border-color: #9ca3af;
        }
        
        .pagination-btn.active {
            background: #2563eb;
            color: white;
            border-color: #2563eb;
        }
        
        .pagination-btn.active:hover {
            background: #1d4ed8;
            border-color: #1d4ed8;
        }
        
        @media (max-width: 768px) {
            .form-grid {
                grid-template-columns: 1fr;
            }
            .product-card {
                padding: 16px 60px 16px 16px;
            }
            .product-header {
                flex-direction: column;
                gap: 8px;
            }
            .product-stats {
                grid-template-columns: 1fr 1fr;
            }
            .status-toggle {
                top: 16px;
                right: 16px;
                width: 40px;
                height: 20px;
            }
            .status-toggle::before {
                width: 16px;
                height: 16px;
            }
            .status-toggle.active::before {
                transform: translateX(20px);
            }
            .pagination {
                gap: 4px;
            }
            .pagination-btn {
                padding: 6px 8px;
                font-size: 12px;
            }
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1>🛒 Product Management</h1>
            <p>Manage MemoWindow print products and pricing</p>
            <div class="nav-links">
                <a href="admin.php?user_id=<?php echo urlencode($userFirebaseUID); ?>" class="nav-link">← Dashboard</a>
                <a href="admin_users.php?user_id=<?php echo urlencode($userFirebaseUID); ?>" class="nav-link">Users</a>
                <a href="analytics.php?user_id=<?php echo urlencode($userFirebaseUID); ?>" class="nav-link">Analytics</a>
                <a href="admin_backups.php?user_id=<?php echo urlencode($userFirebaseUID); ?>" class="nav-link">Backups</a>
            </div>
        </div>

        <!-- Success/Error Messages -->
        <?php if (isset($success)): ?>
            <div class="alert alert-success">✅ <?php echo htmlspecialchars($success); ?></div>
        <?php endif; ?>
        <?php if (isset($error)): ?>
            <div class="alert alert-error">❌ <?php echo htmlspecialchars($error); ?></div>
        <?php endif; ?>

        <!-- Sync Products from Printful -->
        <div class="section">
            <h2>🔄 Sync Products from Printful Store</h2>
            <p>Fetch all available products from your Printful store and update the local database.</p>
            <div style="background: #f0f9ff; padding: 16px; border-radius: 8px; margin-bottom: 16px; border-left: 4px solid #0ea5e9;">
                <h4 style="margin: 0 0 8px 0; color: #0c4a6e;">ℹ️ Sync Template Info</h4>
                <p style="margin: 0; color: #374151;">
                    Your Printful sync template ID: <strong>392097114</strong> (Memorywaves)<br>
                    This sync will pull all products that are currently synced to your store.
                </p>
            </div>
            <div style="background: #fef3c7; padding: 16px; border-radius: 8px; margin-bottom: 16px; border-left: 4px solid #f59e0b;">
                <h4 style="margin: 0 0 8px 0; color: #92400e;">⚠️ Important: This will replace ALL products</h4>
                <ul style="margin: 0; padding-left: 20px; color: #374151;">
                    <li><strong>Deletes all existing products</strong> from your local database</li>
                    <li>Fetches all products and variants from your Printful store</li>
                    <li>Adds fresh products to your local database</li>
                    <li>Ensures your catalog matches Printful exactly</li>
                </ul>
            </div>
            <form method="POST" style="display: inline-block;">
                <input type="hidden" name="action" value="sync_products">
                <button type="submit" class="btn btn-primary" onclick="return confirm('⚠️ WARNING: This will DELETE ALL existing products and replace them with fresh data from Printful. This cannot be undone. Continue?')">
                    🔄 Replace All Products from Printful
                </button>
            </form>
        </div>

        <!-- Add New Product -->
        <div class="section">
            <h2>➕ Add New Product</h2>
            <form method="POST">
                <input type="hidden" name="action" value="add_product">
                <div class="form-grid">
                    <div class="form-group">
                        <label class="form-label">Product Key</label>
                        <input type="text" name="product_key" class="form-input" placeholder="e.g., poster_xl" required>
                    </div>
                    <div class="form-group">
                        <label class="form-label">Printful Sync Variant ID</label>
                        <input type="number" name="printful_id" class="form-input" placeholder="e.g., 4960692122" required>
                    </div>
                    <div class="form-group">
                        <label class="form-label">Product Name</label>
                        <input type="text" name="name" class="form-input" placeholder="e.g., XL Memory Frame" required>
                    </div>
                    <div class="form-group">
                        <label class="form-label">Price (dollars)</label>
                        <input type="number" step="0.01" name="price" class="form-input" placeholder="e.g., 55.00 for $55.00" required>
                    </div>
                    <div class="form-group">
                        <label class="form-label">Size</label>
                        <input type="text" name="size" class="form-input" placeholder="e.g., 24\" × 36\"">
                    </div>
                    <div class="form-group">
                        <label class="form-label">Sort Order</label>
                        <input type="number" name="sort_order" class="form-input" placeholder="0" value="0">
                    </div>
                </div>
                <div class="form-group form-full-width">
                    <label class="form-label">Material</label>
                    <input type="text" name="material" class="form-input" placeholder="e.g., Enhanced matte paper with frame">
                </div>
                <div class="form-group form-full-width">
                    <label class="form-label">Description</label>
                    <textarea name="description" class="form-textarea" placeholder="Product description for customers"></textarea>
                </div>
                <button type="submit" class="btn btn-success">Add Product</button>
            </form>
        </div>

        <!-- Current Products -->
        <div class="section">
            <h2>📦 Current Products 
                <span style="font-size: 14px; font-weight: normal; color: #64748b;">
                    (<?php echo $totalCount; ?> total, page <?php echo $currentPage; ?> of <?php echo $totalPages; ?>)
                </span>
            </h2>
            <div class="product-grid">
                <?php foreach ($products as $product): ?>
                <div class="product-card <?php echo $product['is_active'] ? '' : 'inactive'; ?>">
                    <div class="status-toggle <?php echo $product['is_active'] ? 'active' : ''; ?>" 
                         onclick="toggleProductStatus(<?php echo $product['id']; ?>, <?php echo $product['is_active'] ? 'false' : 'true'; ?>)">
                    </div>
                    
                    <div class="product-header">
                        <div>
                            <div class="product-title"><?php echo htmlspecialchars($product['name']); ?></div>
                            <div style="font-size: 12px; color: #64748b;">
                                Key: <?php echo htmlspecialchars($product['product_key']); ?> • 
                                Printful ID: <?php echo $product['printful_variant_id']; ?>
                            </div>
                        </div>
                        <div class="product-price"><?php echo PriceManager::formatPrice($product['price']); ?></div>
                    </div>

                    <div style="margin: 12px 0;">
                        <div style="font-size: 14px; color: #374151; margin-bottom: 4px;">
                            <strong><?php echo htmlspecialchars($product['size']); ?></strong>
                        </div>
                        <div style="font-size: 12px; color: #64748b; margin-bottom: 8px;">
                            <?php echo htmlspecialchars($product['material']); ?>
                        </div>
                        <div style="font-size: 12px; color: #64748b; line-height: 1.4;">
                            <?php echo htmlspecialchars($product['description']); ?>
                        </div>
                    </div>

                    <div class="product-stats">
                        <div class="stat-item">
                            <div class="stat-number"><?php echo $product['order_count']; ?></div>
                            <div class="stat-label">Orders</div>
                        </div>
                        <div class="stat-item">
                            <div class="stat-number">$<?php echo number_format($product['total_revenue'] ?? 0, 0); ?></div>
                            <div class="stat-label">Revenue</div>
                        </div>
                    </div>

                    <div class="product-actions">
                        <button onclick="editProduct(<?php echo htmlspecialchars(json_encode($product)); ?>)" 
                                class="btn btn-primary">Edit</button>
                        <button onclick="testProduct(<?php echo $product['printful_variant_id']; ?>)" 
                                class="btn btn-warning">Test Printful</button>
                        <button onclick="deleteProduct(<?php echo $product['id']; ?>, '<?php echo htmlspecialchars($product['name']); ?>')" 
                                class="btn btn-danger">Delete</button>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>
            
            <!-- Pagination -->
            <?php if ($totalPages > 1): ?>
            <div class="pagination">
                <?php if ($currentPage > 1): ?>
                    <a href="?user_id=<?php echo urlencode($userFirebaseUID); ?>&page=1" class="pagination-btn">« First</a>
                    <a href="?user_id=<?php echo urlencode($userFirebaseUID); ?>&page=<?php echo $currentPage - 1; ?>" class="pagination-btn">‹ Previous</a>
                <?php endif; ?>
                
                <?php
                $startPage = max(1, $currentPage - 2);
                $endPage = min($totalPages, $currentPage + 2);
                
                for ($i = $startPage; $i <= $endPage; $i++):
                ?>
                    <a href="?user_id=<?php echo urlencode($userFirebaseUID); ?>&page=<?php echo $i; ?>" 
                       class="pagination-btn <?php echo $i === $currentPage ? 'active' : ''; ?>">
                        <?php echo $i; ?>
                    </a>
                <?php endfor; ?>
                
                <?php if ($currentPage < $totalPages): ?>
                    <a href="?user_id=<?php echo urlencode($userFirebaseUID); ?>&page=<?php echo $currentPage + 1; ?>" class="pagination-btn">Next ›</a>
                    <a href="?user_id=<?php echo urlencode($userFirebaseUID); ?>&page=<?php echo $totalPages; ?>" class="pagination-btn">Last »</a>
                <?php endif; ?>
            </div>
            <?php endif; ?>
        </div>
    </div>

    <!-- Edit Product Modal -->
    <div id="editModal" style="display: none; position: fixed; top: 0; left: 0; width: 100%; height: 100%; background: rgba(0,0,0,0.6); z-index: 1000; align-items: center; justify-content: center;">
        <div style="background: white; border-radius: 12px; padding: 24px; max-width: 600px; width: 90%; max-height: 80vh; overflow-y: auto;">
            <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px;">
                <h3 style="margin: 0;">✏️ Edit Product</h3>
                <button onclick="closeEditModal()" style="background: none; border: none; font-size: 24px; cursor: pointer;">&times;</button>
            </div>
            <form method="POST" id="editForm">
                <input type="hidden" name="action" value="update_product">
                <input type="hidden" name="product_id" id="editProductId">
                
                <div class="form-grid">
                    <div class="form-group">
                        <label class="form-label">Product Name</label>
                        <input type="text" name="name" id="editName" class="form-input" required>
                    </div>
                    <div class="form-group">
                        <label class="form-label">Price (dollars)</label>
                        <input type="number" step="0.01" name="price" id="editPrice" class="form-input" required>
                    </div>
                    <div class="form-group">
                        <label class="form-label">Size</label>
                        <input type="text" name="size" id="editSize" class="form-input">
                    </div>
                    <div class="form-group">
                        <label class="form-label">Sort Order</label>
                        <input type="number" name="sort_order" id="editSortOrder" class="form-input">
                    </div>
                </div>
                <div class="form-group form-full-width">
                    <label class="form-label">Material</label>
                    <input type="text" name="material" id="editMaterial" class="form-input">
                </div>
                <div class="form-group form-full-width">
                    <label class="form-label">Description</label>
                    <textarea name="description" id="editDescription" class="form-textarea"></textarea>
                </div>
                <div class="form-group">
                    <label style="display: flex; align-items: center; gap: 8px;">
                        <input type="checkbox" name="is_active" id="editIsActive" value="1">
                        Product is active and available for orders
                    </label>
                </div>
                <div style="display: flex; gap: 12px; justify-content: flex-end;">
                    <button type="button" onclick="closeEditModal()" class="btn btn-warning">Cancel</button>
                    <button type="submit" class="btn btn-success">Update Product</button>
                </div>
            </form>
        </div>
    </div>

    <script>
        function toggleProductStatus(productId, newStatus) {
            const form = document.createElement('form');
            form.method = 'POST';
            form.innerHTML = `
                <input type="hidden" name="action" value="toggle_status">
                <input type="hidden" name="product_id" value="${productId}">
                <input type="hidden" name="is_active" value="${newStatus === 'true' ? '1' : '0'}">
            `;
            document.body.appendChild(form);
            form.submit();
        }

        function editProduct(product) {
            document.getElementById('editProductId').value = product.id;
            document.getElementById('editName').value = product.name;
            document.getElementById('editPrice').value = (product.price / 100).toFixed(2);
            document.getElementById('editSize').value = product.size || '';
            document.getElementById('editMaterial').value = product.material || '';
            document.getElementById('editDescription').value = product.description || '';
            document.getElementById('editSortOrder').value = product.sort_order || 0;
            document.getElementById('editIsActive').checked = product.is_active == 1;
            
            document.getElementById('editModal').style.display = 'flex';
        }

        function closeEditModal() {
            document.getElementById('editModal').style.display = 'none';
        }

        function deleteProduct(productId, productName) {
            if (confirm(`Delete "${productName}"?\n\nThis will remove the product from the store. Existing orders will not be affected.`)) {
                const form = document.createElement('form');
                form.method = 'POST';
                form.innerHTML = `
                    <input type="hidden" name="action" value="delete_product">
                    <input type="hidden" name="product_id" value="${productId}">
                `;
                document.body.appendChild(form);
                form.submit();
            }
        }

        function testProduct(printfulId) {
            alert(`Testing Printful sync variant ID: ${printfulId}\n\nThis would create a test order to verify the product works correctly.`);
        }

        // Close modal on escape key
        document.addEventListener('keydown', (e) => {
            if (e.key === 'Escape') {
                closeEditModal();
            }
        });
    </script>
</body>
</html>

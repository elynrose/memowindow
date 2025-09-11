<?php
// analytics.php - Analytics dashboard with charts
require_once 'secure_auth.php';
require_once 'config.php';

// Check session timeout
if (!checkSessionTimeout()) {
    header('Location: ' . BASE_URL . '/login.php?error=session_expired');
    exit;
}

// Require admin authentication
$userFirebaseUID = requireSecureAdmin();

try {
    $pdo = new PDO("mysql:host=" . DB_HOST . ";dbname=" . DB_NAME . ";charset=utf8mb4", DB_USER, DB_PASS, [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
    ]);
    
} catch (PDOException $e) {
    http_response_code(500);
    echo "Database error";
    exit;
}

// Get analytics data
try {
    // Daily user growth (last 30 days)
    $userGrowth = $pdo->query("
        SELECT 
            DATE(created_at) as date,
            COUNT(DISTINCT user_id) as new_users,
            COUNT(*) as memories_created
        FROM wave_assets 
        WHERE created_at >= DATE_SUB(NOW(), INTERVAL 30 DAY)
        GROUP BY DATE(created_at)
        ORDER BY date ASC
    ")->fetchAll(PDO::FETCH_ASSOC);
    
    // Daily orders and revenue (last 30 days)
    $orderStats = $pdo->query("
        SELECT 
            DATE(created_at) as date,
            COUNT(*) as order_count,
            SUM(amount_paid) as revenue,
            AVG(amount_paid) as avg_order_value
        FROM orders 
        WHERE created_at >= DATE_SUB(NOW(), INTERVAL 30 DAY)
        GROUP BY DATE(created_at)
        ORDER BY date ASC
    ")->fetchAll(PDO::FETCH_ASSOC);
    
    // Product popularity
    $productStats = $pdo->query("
        SELECT 
            product_id,
            COUNT(*) as order_count,
            SUM(amount_paid) as revenue
        FROM orders 
        GROUP BY product_id
        ORDER BY order_count DESC
    ")->fetchAll(PDO::FETCH_ASSOC);
    
    // Overall statistics
    $totalStats = $pdo->query("
        SELECT 
            (SELECT COUNT(DISTINCT user_id) FROM wave_assets) as total_users,
            (SELECT COUNT(*) FROM wave_assets) as total_memories,
            (SELECT COUNT(*) FROM orders) as total_orders,
            (SELECT SUM(amount_paid) FROM orders WHERE status = 'paid') as total_revenue,
            (SELECT COUNT(DISTINCT user_id) FROM wave_assets WHERE created_at >= DATE_SUB(NOW(), INTERVAL 7 DAY)) as active_users_7d,
            (SELECT COUNT(*) FROM orders WHERE created_at >= DATE_SUB(NOW(), INTERVAL 7 DAY)) as orders_7d
    ")->fetch(PDO::FETCH_ASSOC);
    
} catch (PDOException $e) {
    $userGrowth = [];
    $orderStats = [];
    $productStats = [];
    $totalStats = [];
    $error = $e->getMessage();
}

// Prepare data for charts
$dates = [];
$newUsersData = [];
$memoriesData = [];
$ordersData = [];
$revenueData = [];

// Fill in missing dates and prepare chart data
$startDate = date('Y-m-d', strtotime('-30 days'));
$endDate = date('Y-m-d');

for ($date = $startDate; $date <= $endDate; $date = date('Y-m-d', strtotime($date . ' +1 day'))) {
    $dates[] = date('M j', strtotime($date));
    
    // Find data for this date
    $dayUserData = array_filter($userGrowth, function($item) use ($date) {
        return $item['date'] === $date;
    });
    $dayOrderData = array_filter($orderStats, function($item) use ($date) {
        return $item['date'] === $date;
    });
    
    $newUsersData[] = !empty($dayUserData) ? array_values($dayUserData)[0]['new_users'] : 0;
    $memoriesData[] = !empty($dayUserData) ? array_values($dayUserData)[0]['memories_created'] : 0;
    $ordersData[] = !empty($dayOrderData) ? array_values($dayOrderData)[0]['order_count'] : 0;
    $revenueData[] = !empty($dayOrderData) ? (array_values($dayOrderData)[0]['revenue'] / 100) : 0;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Analytics - MemoWindow Admin</title>
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <link rel="stylesheet" href="includes/admin_styles.css">
    <style>
        /* Page-specific styles */
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
        .stat-change {
            font-size: 12px;
            margin-top: 4px;
        }
        .stat-change.positive {
            color: #059669;
        }
        .stat-change.negative {
            color: #dc2626;
        }
        .chart-section {
            background: white;
            border-radius: 12px;
            padding: 24px;
            margin-bottom: 24px;
            border: 1px solid #e2e8f0;
        }
        .chart-section h2 {
            margin: 0 0 20px 0;
            color: #1e293b;
            font-size: 20px;
            font-weight: 600;
        }
        .charts-grid {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 24px;
            margin-bottom: 24px;
        }
        .chart-container {
            position: relative;
            height: 300px;
        }
        .product-stats {
            background: white;
            border-radius: 12px;
            padding: 24px;
            border: 1px solid #e2e8f0;
        }
        .product-item {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 12px 0;
            border-bottom: 1px solid #f1f5f9;
        }
        .product-item:last-child {
            border-bottom: none;
        }
        @media (max-width: 768px) {
            .charts-grid {
                grid-template-columns: 1fr;
            }
            .stats-grid {
                grid-template-columns: repeat(2, 1fr);
            }
        }
    </style>
</head>
<body>
    <div class="admin-container">
        <div class="admin-header">
            <h1>📈 Analytics Dashboard</h1>
            <p>MemoWindow performance metrics and insights</p>
        </div>
        
        <?php include 'includes/admin_navigation.php'; ?>

        <div class="admin-content">
            <!-- Key Metrics -->
            <div class="admin-stats-grid">
                <div class="admin-stat-card">
                    <div class="number"><?php echo number_format($totalStats['total_users'] ?? 0); ?></div>
                    <p>Total Users</p>
                    <small style="color: #28a745;">+<?php echo $totalStats['active_users_7d'] ?? 0; ?> this week</small>
                </div>
                <div class="admin-stat-card">
                    <div class="number"><?php echo number_format($totalStats['total_memories'] ?? 0); ?></div>
                    <p>Memories Created</p>
                </div>
                <div class="admin-stat-card">
                    <div class="number"><?php echo number_format($totalStats['total_orders'] ?? 0); ?></div>
                    <p>Total Orders</p>
                    <small style="color: #28a745;">+<?php echo $totalStats['orders_7d'] ?? 0; ?> this week</small>
                </div>
                <div class="admin-stat-card">
                    <div class="number">$<?php echo number_format(($totalStats['total_revenue'] ?? 0) / 100, 2); ?></div>
                <div class="stat-label">Total Revenue</div>
            </div>
        </div>

        <!-- Charts Grid -->
        <div class="charts-grid">
            <!-- User Growth Chart -->
            <div class="chart-section">
                <h2>👥 User Growth (30 Days)</h2>
                <div class="chart-container">
                    <canvas id="userGrowthChart"></canvas>
                </div>
            </div>

            <!-- Revenue Chart -->
            <div class="chart-section">
                <h2>💰 Revenue Trends (30 Days)</h2>
                <div class="chart-container">
                    <canvas id="revenueChart"></canvas>
                </div>
            </div>
        </div>

        <!-- Orders and Memories Chart -->
        <div class="chart-section">
            <h2>📊 Activity Overview (30 Days)</h2>
            <div class="chart-container" style="height: 400px;">
                <canvas id="activityChart"></canvas>
            </div>
        </div>

        <!-- Product Performance -->
        <?php if (!empty($productStats)): ?>
        <div class="product-stats">
            <h2>🎯 Product Performance</h2>
            <?php foreach ($productStats as $product): ?>
            <div class="product-item">
                <div>
                    <strong><?php echo htmlspecialchars($product['product_id']); ?></strong>
                    <div style="font-size: 12px; color: #64748b;">
                        <?php echo $product['order_count']; ?> orders
                    </div>
                </div>
                <div style="text-align: right;">
                    <div style="font-weight: 600; color: #059669;">
                        $<?php echo number_format($product['revenue'] / 100, 2); ?>
                    </div>
                    <div style="font-size: 12px; color: #64748b;">
                        $<?php echo number_format(($product['revenue'] / $product['order_count']) / 100, 2); ?> avg
                    </div>
                </div>
            </div>
            <?php endforeach; ?>
        </div>
        <?php endif; ?>
    </div>

    <script>
        // Chart configuration
        Chart.defaults.font.family = 'system-ui, -apple-system, sans-serif';
        Chart.defaults.color = '#64748b';

        // Data from PHP
        const dates = <?php echo json_encode($dates); ?>;
        const newUsersData = <?php echo json_encode($newUsersData); ?>;
        const memoriesData = <?php echo json_encode($memoriesData); ?>;
        const ordersData = <?php echo json_encode($ordersData); ?>;
        const revenueData = <?php echo json_encode($revenueData); ?>;

        // User Growth Chart
        const userGrowthCtx = document.getElementById('userGrowthChart').getContext('2d');
        new Chart(userGrowthCtx, {
            type: 'line',
            data: {
                labels: dates,
                datasets: [{
                    label: 'New Users',
                    data: newUsersData,
                    borderColor: '#2563eb',
                    backgroundColor: 'rgba(37, 99, 235, 0.1)',
                    borderWidth: 3,
                    fill: true,
                    tension: 0.4
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: {
                        display: false
                    }
                },
                scales: {
                    y: {
                        beginAtZero: true,
                        ticks: {
                            stepSize: 1
                        }
                    }
                }
            }
        });

        // Revenue Chart
        const revenueCtx = document.getElementById('revenueChart').getContext('2d');
        new Chart(revenueCtx, {
            type: 'bar',
            data: {
                labels: dates,
                datasets: [{
                    label: 'Daily Revenue',
                    data: revenueData,
                    backgroundColor: '#059669',
                    borderColor: '#047857',
                    borderWidth: 1
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: {
                        display: false
                    }
                },
                scales: {
                    y: {
                        beginAtZero: true,
                        ticks: {
                            callback: function(value) {
                                return '$' + value;
                            }
                        }
                    }
                }
            }
        });

        // Activity Overview Chart
        const activityCtx = document.getElementById('activityChart').getContext('2d');
        new Chart(activityCtx, {
            type: 'line',
            data: {
                labels: dates,
                datasets: [{
                    label: 'Memories Created',
                    data: memoriesData,
                    borderColor: '#7c3aed',
                    backgroundColor: 'rgba(124, 58, 237, 0.1)',
                    borderWidth: 3,
                    tension: 0.4,
                    yAxisID: 'y'
                }, {
                    label: 'Orders Placed',
                    data: ordersData,
                    borderColor: '#dc2626',
                    backgroundColor: 'rgba(220, 38, 38, 0.1)',
                    borderWidth: 3,
                    tension: 0.4,
                    yAxisID: 'y1'
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                interaction: {
                    mode: 'index',
                    intersect: false,
                },
                plugins: {
                    legend: {
                        position: 'top',
                    }
                },
                scales: {
                    y: {
                        type: 'linear',
                        display: true,
                        position: 'left',
                        beginAtZero: true,
                        title: {
                            display: true,
                            text: 'Memories'
                        }
                    },
                    y1: {
                        type: 'linear',
                        display: true,
                        position: 'right',
                        beginAtZero: true,
                        title: {
                            display: true,
                            text: 'Orders'
                        },
                        grid: {
                            drawOnChartArea: false,
                        },
                    }
                }
            }
        });

        // Auto-refresh every 5 minutes
        setInterval(() => {
            location.reload();
        }, 300000);
    </script>
        </div>
    </div>
</body>
</html>

<?php

require_once '../../config.php';
require_once '../../helpers/DatabaseHelper.php';

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    exit(0);
}

if (!isLoggedIn()) {
    http_response_code(401);
    echo json_encode(['error' => 'Authentication required']);
    exit();
}

if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
    http_response_code(405);
    echo json_encode(['error' => 'Method not allowed']);
    exit();
}

$type = $_GET['type'] ?? 'dashboard';
$period = $_GET['period'] ?? '30'; 

$start_date = $_GET['start_date'] ?? null;
$end_date = $_GET['end_date'] ?? null;

try {
    $db = new DatabaseHelper($pdo);
    
    switch ($type) {
        case 'dashboard':
            echo json_encode(getDashboardAnalytics($period));
            break;
            
        case 'sales':
            echo json_encode(getSalesAnalytics($period, $start_date, $end_date));
            break;
            
        case 'inventory':
            echo json_encode(getInventoryAnalytics());
            break;
            
        case 'customers':
            echo json_encode(getCustomerAnalytics($period));
            break;
            
        case 'trends':
            echo json_encode(getTrendAnalytics($period));
            break;
            
        default:
            http_response_code(400);
            echo json_encode(['error' => 'Invalid analytics type']);
            break;
    }
    
} catch (Exception $e) {
    error_log("Analytics API Error: " . $e->getMessage());
    http_response_code(500);
    echo json_encode(['error' => 'Analytics failed']);
}

function getDashboardAnalytics($period) {
    global $pdo;
    
    $dateFilter = "DATE(created_at) >= DATE_SUB(CURDATE(), INTERVAL {$period} DAY)";
    
    

    $metrics = [];
    
    

    $stmt = $pdo->prepare("
        SELECT COUNT(*) as count, COALESCE(SUM(sale_price), 0) as revenue
        FROM products 
        WHERE sold_date IS NOT NULL AND {$dateFilter}
    ");
    $stmt->execute();
    $sales = $stmt->fetch(PDO::FETCH_ASSOC);
    
    

    $stmt = $pdo->prepare("
        SELECT COUNT(*) as count 
        FROM customers 
        WHERE {$dateFilter}
    ");
    $stmt->execute();
    $newCustomers = $stmt->fetch(PDO::FETCH_ASSOC);
    
    

    $stmt = $pdo->prepare("
        SELECT COUNT(*) as count 
        FROM products 
        WHERE sold_date IS NULL
    ");
    $stmt->execute();
    $inventory = $stmt->fetch(PDO::FETCH_ASSOC);
    
    

    $stmt = $pdo->prepare("
        SELECT m.brand, m.model_name, COUNT(*) as sales_count,
               AVG(p.sale_price) as avg_price
        FROM products p
        JOIN models m ON p.model_id = m.id
        WHERE p.sold_date IS NOT NULL AND {$dateFilter}
        GROUP BY m.id
        ORDER BY sales_count DESC
        LIMIT 5
    ");
    $stmt->execute();
    $topModels = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    return [
        'success' => true,
        'period' => $period . ' days',
        'metrics' => [
            'sales' => [
                'count' => (int)$sales['count'],
                'revenue' => (float)$sales['revenue']
            ],
            'new_customers' => (int)$newCustomers['count'],
            'available_inventory' => (int)$inventory['count']
        ],
        'top_models' => $topModels
    ];
}

function getSalesAnalytics($period, $start_date, $end_date) {
    global $pdo;
    
    

    if ($start_date && $end_date) {
        $dateFilter = "DATE(sold_date) BETWEEN ? AND ?";
        $params = [$start_date, $end_date];
    } else {
        $dateFilter = "DATE(sold_date) >= DATE_SUB(CURDATE(), INTERVAL {$period} DAY)";
        $params = [];
    }
    
    

    $stmt = $pdo->prepare("
        SELECT DATE(sold_date) as date, 
               COUNT(*) as sales_count,
               COALESCE(SUM(sale_price), 0) as revenue
        FROM products 
        WHERE sold_date IS NOT NULL AND {$dateFilter}
        GROUP BY DATE(sold_date)
        ORDER BY date DESC
        LIMIT 30
    ");
    $stmt->execute($params);
    $dailySales = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    

    $stmt = $pdo->prepare("
        SELECT m.brand, 
               COUNT(*) as sales_count,
               COALESCE(SUM(p.sale_price), 0) as revenue,
               AVG(p.sale_price) as avg_price
        FROM products p
        JOIN models m ON p.model_id = m.id
        WHERE p.sold_date IS NOT NULL AND {$dateFilter}
        GROUP BY m.brand
        ORDER BY sales_count DESC
    ");
    $stmt->execute($params);
    $salesByBrand = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    

    $stmt = $pdo->prepare("
        SELECT pl.platform_name,
               COUNT(*) as sales_count,
               COALESCE(SUM(p.sale_price), 0) as revenue
        FROM products p
        JOIN sales s ON p.id = s.product_id
        JOIN platforms pl ON s.platform_id = pl.id
        WHERE p.sold_date IS NOT NULL AND {$dateFilter}
        GROUP BY pl.id
        ORDER BY sales_count DESC
    ");
    $stmt->execute($params);
    $salesByPlatform = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    return [
        'success' => true,
        'daily_sales' => $dailySales,
        'sales_by_brand' => $salesByBrand,
        'sales_by_platform' => $salesByPlatform
    ];
}

function getInventoryAnalytics() {
    global $pdo;
    
    

    $stmt = $pdo->prepare("
        SELECT m.brand,
               COUNT(*) as total_products,
               COUNT(CASE WHEN p.sold_date IS NULL THEN 1 END) as available,
               COUNT(CASE WHEN p.sold_date IS NOT NULL THEN 1 END) as sold,
               AVG(CASE WHEN p.sold_date IS NULL THEN p.purchase_price END) as avg_cost
        FROM models m
        LEFT JOIN products p ON m.id = p.model_id
        GROUP BY m.brand
        ORDER BY total_products DESC
    ");
    $stmt->execute();
    $inventoryByBrand = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    

    $stmt = $pdo->prepare("
        SELECT m.brand, m.model_name,
               COUNT(CASE WHEN p.sold_date IS NULL THEN 1 END) as available_count
        FROM models m
        LEFT JOIN products p ON m.id = p.model_id
        GROUP BY m.id
        HAVING available_count < 5
        ORDER BY available_count ASC
    ");
    $stmt->execute();
    $lowStock = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    

    $stmt = $pdo->prepare("
        SELECT m.brand, m.model_name, p.serial_number,
               DATEDIFF(CURDATE(), p.created_at) as days_in_stock
        FROM products p
        JOIN models m ON p.model_id = m.id
        WHERE p.sold_date IS NULL 
        AND DATEDIFF(CURDATE(), p.created_at) > 90
        ORDER BY days_in_stock DESC
        LIMIT 20
    ");
    $stmt->execute();
    $agingInventory = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    return [
        'success' => true,
        'inventory_by_brand' => $inventoryByBrand,
        'low_stock_alerts' => $lowStock,
        'aging_inventory' => $agingInventory
    ];
}

function getCustomerAnalytics($period) {
    global $pdo;
    
    $dateFilter = "DATE(c.created_at) >= DATE_SUB(CURDATE(), INTERVAL {$period} DAY)";
    
    

    $stmt = $pdo->prepare("
        SELECT DATE(created_at) as date, COUNT(*) as new_customers
        FROM customers
        WHERE {$dateFilter}
        GROUP BY DATE(created_at)
        ORDER BY date DESC
        LIMIT 30
    ");
    $stmt->execute();
    $acquisitionTrend = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    

    $stmt = $pdo->prepare("
        SELECT c.first_name, c.last_name, c.email,
               COUNT(p.id) as total_purchases,
               COALESCE(SUM(p.sale_price), 0) as total_spent
        FROM customers c
        LEFT JOIN products p ON c.id = p.customer_id AND p.sold_date IS NOT NULL
        GROUP BY c.id
        ORDER BY total_spent DESC
        LIMIT 10
    ");
    $stmt->execute();
    $topCustomers = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    

    $stmt = $pdo->prepare("
        SELECT 
            CASE 
                WHEN purchase_count = 0 THEN 'No Purchases'
                WHEN purchase_count = 1 THEN 'One-time Buyer'
                WHEN purchase_count BETWEEN 2 AND 5 THEN 'Regular Customer'
                ELSE 'VIP Customer'
            END as segment,
            COUNT(*) as customer_count
        FROM (
            SELECT c.id, COUNT(p.id) as purchase_count
            FROM customers c
            LEFT JOIN products p ON c.id = p.customer_id AND p.sold_date IS NOT NULL
            GROUP BY c.id
        ) customer_purchases
        GROUP BY segment
    ");
    $stmt->execute();
    $customerSegments = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    return [
        'success' => true,
        'acquisition_trend' => $acquisitionTrend,
        'top_customers' => $topCustomers,
        'customer_segments' => $customerSegments
    ];
}

function getTrendAnalytics($period) {
    global $pdo;
    
    

    $stmt = $pdo->prepare("
        SELECT 
            DATE_FORMAT(sold_date, '%Y-%m') as month,
            COUNT(*) as sales_count,
            COALESCE(SUM(sale_price), 0) as revenue
        FROM products
        WHERE sold_date IS NOT NULL 
        AND sold_date >= DATE_SUB(CURDATE(), INTERVAL 12 MONTH)
        GROUP BY DATE_FORMAT(sold_date, '%Y-%m')
        ORDER BY month DESC
    ");
    $stmt->execute();
    $monthlySales = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    

    $stmt = $pdo->prepare("
        SELECT m.brand,
               DATE_FORMAT(p.sold_date, '%Y-%m') as month,
               COUNT(*) as sales_count
        FROM products p
        JOIN models m ON p.model_id = m.id
        WHERE p.sold_date IS NOT NULL 
        AND p.sold_date >= DATE_SUB(CURDATE(), INTERVAL 6 MONTH)
        GROUP BY m.brand, DATE_FORMAT(p.sold_date, '%Y-%m')
        ORDER BY month DESC, sales_count DESC
    ");
    $stmt->execute();
    $brandTrends = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    

    $stmt = $pdo->prepare("
        SELECT 
            MONTH(sold_date) as month,
            MONTHNAME(sold_date) as month_name,
            COUNT(*) as avg_sales
        FROM products
        WHERE sold_date IS NOT NULL
        GROUP BY MONTH(sold_date), MONTHNAME(sold_date)
        ORDER BY month
    ");
    $stmt->execute();
    $seasonalPatterns = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    return [
        'success' => true,
        'monthly_sales' => $monthlySales,
        'brand_trends' => $brandTrends,
        'seasonal_patterns' => $seasonalPatterns
    ];
}
?>

<?php

require_once '../../config.php';
require_once '../../helpers/DatabaseHelper.php';

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    exit(0);
}

function requireAuth() {
    if (!isLoggedIn()) {
        http_response_code(401);
        echo json_encode(['error' => 'Authentication required']);
        exit();
    }
}

$method = $_SERVER['REQUEST_METHOD'];
$path = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
$pathParts = explode('/', trim($path, '/'));

$productId = null;
if (count($pathParts) > 3 && is_numeric(end($pathParts))) {
    $productId = (int)end($pathParts);
}

try {
    $db = new DatabaseHelper($pdo);
    
    switch ($method) {
        case 'GET':
            requireAuth();
            handleGetProducts($db, $productId);
            break;
            
        case 'POST':
            requireAuth();
            handleCreateProduct($db);
            break;
            
        case 'PUT':
            requireAuth();
            handleUpdateProduct($db, $productId);
            break;
            
        case 'DELETE':
            requireAuth();
            handleDeleteProduct($db, $productId);
            break;
            
        default:
            http_response_code(405);
            echo json_encode(['error' => 'Method not allowed']);
            break;
    }
    
} catch (Exception $e) {
    error_log("Products API Error: " . $e->getMessage());
    http_response_code(500);
    echo json_encode(['error' => 'Internal server error']);
}

function handleGetProducts($db, $productId = null) {
    try {
        if ($productId) {
            

            $stmt = $GLOBALS['pdo']->prepare("
                SELECT p.*, m.model_name, m.brand, m.category, 
                       CASE WHEN p.sold_date IS NULL THEN 'available' ELSE 'sold' END as status
                FROM products p 
                JOIN models m ON p.model_id = m.id 
                WHERE p.id = ?
            ");
            $stmt->execute([$productId]);
            $product = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if (!$product) {
                http_response_code(404);
                echo json_encode(['error' => 'Product not found']);
                return;
            }
            
            echo json_encode([
                'success' => true,
                'data' => $product
            ]);
        } else {
            

            $status = $_GET['status'] ?? 'all'; 

            $model_id = $_GET['model_id'] ?? null;
            $limit = min((int)($_GET['limit'] ?? 50), 100); 

            $offset = (int)($_GET['offset'] ?? 0);
            
            $whereClause = "WHERE 1=1";
            $params = [];
            
            if ($status === 'available') {
                $whereClause .= " AND p.sold_date IS NULL";
            } elseif ($status === 'sold') {
                $whereClause .= " AND p.sold_date IS NOT NULL";
            }
            
            if ($model_id) {
                $whereClause .= " AND p.model_id = ?";
                $params[] = $model_id;
            }
            
            $stmt = $GLOBALS['pdo']->prepare("
                SELECT p.*, m.model_name, m.brand, m.category,
                       CASE WHEN p.sold_date IS NULL THEN 'available' ELSE 'sold' END as status,
                       c.first_name, c.last_name
                FROM products p 
                JOIN models m ON p.model_id = m.id 
                LEFT JOIN customers c ON p.customer_id = c.id
                {$whereClause}
                ORDER BY p.created_at DESC
                LIMIT {$limit} OFFSET {$offset}
            ");
            $stmt->execute($params);
            $products = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            

            $countStmt = $GLOBALS['pdo']->prepare("
                SELECT COUNT(*) as total 
                FROM products p 
                JOIN models m ON p.model_id = m.id 
                {$whereClause}
            ");
            $countStmt->execute($params);
            $total = $countStmt->fetch(PDO::FETCH_ASSOC)['total'];
            
            echo json_encode([
                'success' => true,
                'data' => $products,
                'pagination' => [
                    'total' => (int)$total,
                    'limit' => $limit,
                    'offset' => $offset,
                    'has_more' => ($offset + $limit) < $total
                ]
            ]);
        }
    } catch (Exception $e) {
        error_log("Get products error: " . $e->getMessage());
        http_response_code(500);
        echo json_encode(['error' => 'Failed to fetch products']);
    }
}

function handleCreateProduct($db) {
    $input = json_decode(file_get_contents('php://input'), true);
    
    

    $required = ['model_id', 'serial_number'];
    foreach ($required as $field) {
        if (empty($input[$field])) {
            http_response_code(400);
            echo json_encode(['error' => "Field '{$field}' is required"]);
            return;
        }
    }
    
    try {
        

        $stmt = $GLOBALS['pdo']->prepare("SELECT id FROM products WHERE serial_number = ?");
        $stmt->execute([$input['serial_number']]);
        if ($stmt->fetch()) {
            http_response_code(400);
            echo json_encode(['error' => 'Serial number already exists']);
            return;
        }
        
        

        $stmt = $GLOBALS['pdo']->prepare("
            INSERT INTO products (model_id, serial_number, purchase_price, notes, created_at) 
            VALUES (?, ?, ?, ?, NOW())
        ");
        
        $stmt->execute([
            $input['model_id'],
            $input['serial_number'],
            $input['purchase_price'] ?? null,
            $input['notes'] ?? null
        ]);
        
        $productId = $GLOBALS['pdo']->lastInsertId();
        
        

        auditLog('product_created', "Product created with serial: {$input['serial_number']}", $productId);
        
        echo json_encode([
            'success' => true,
            'message' => 'Product created successfully',
            'product_id' => $productId
        ]);
        
    } catch (Exception $e) {
        error_log("Create product error: " . $e->getMessage());
        http_response_code(500);
        echo json_encode(['error' => 'Failed to create product']);
    }
}

function handleUpdateProduct($db, $productId) {
    if (!$productId) {
        http_response_code(400);
        echo json_encode(['error' => 'Product ID is required']);
        return;
    }
    
    $input = json_decode(file_get_contents('php://input'), true);
    
    try {
        

        $stmt = $GLOBALS['pdo']->prepare("SELECT * FROM products WHERE id = ?");
        $stmt->execute([$productId]);
        $product = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if (!$product) {
            http_response_code(404);
            echo json_encode(['error' => 'Product not found']);
            return;
        }
        
        

        $updates = [];
        $params = [];
        
        $allowedFields = ['model_id', 'serial_number', 'purchase_price', 'notes'];
        foreach ($allowedFields as $field) {
            if (isset($input[$field])) {
                $updates[] = "{$field} = ?";
                $params[] = $input[$field];
            }
        }
        
        if (empty($updates)) {
            http_response_code(400);
            echo json_encode(['error' => 'No valid fields to update']);
            return;
        }
        
        $params[] = $productId;
        
        $stmt = $GLOBALS['pdo']->prepare("
            UPDATE products 
            SET " . implode(', ', $updates) . ", updated_at = NOW() 
            WHERE id = ?
        ");
        
        $stmt->execute($params);
        
        

        auditLog('product_updated', "Product updated: {$product['serial_number']}", $productId);
        
        echo json_encode([
            'success' => true,
            'message' => 'Product updated successfully'
        ]);
        
    } catch (Exception $e) {
        error_log("Update product error: " . $e->getMessage());
        http_response_code(500);
        echo json_encode(['error' => 'Failed to update product']);
    }
}

function handleDeleteProduct($db, $productId) {
    if (!$productId) {
        http_response_code(400);
        echo json_encode(['error' => 'Product ID is required']);
        return;
    }
    
    try {
        

        $stmt = $GLOBALS['pdo']->prepare("SELECT * FROM products WHERE id = ?");
        $stmt->execute([$productId]);
        $product = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if (!$product) {
            http_response_code(404);
            echo json_encode(['error' => 'Product not found']);
            return;
        }
        
        if ($product['sold_date']) {
            http_response_code(400);
            echo json_encode(['error' => 'Cannot delete sold product']);
            return;
        }
        
        

        $stmt = $GLOBALS['pdo']->prepare("DELETE FROM products WHERE id = ?");
        $stmt->execute([$productId]);
        
        

        auditLog('product_deleted', "Product deleted: {$product['serial_number']}", $productId);
        
        echo json_encode([
            'success' => true,
            'message' => 'Product deleted successfully'
        ]);
        
    } catch (Exception $e) {
        error_log("Delete product error: " . $e->getMessage());
        http_response_code(500);
        echo json_encode(['error' => 'Failed to delete product']);
    }
}
?>

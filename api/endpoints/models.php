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

$modelId = null;
if (count($pathParts) > 3 && is_numeric(end($pathParts))) {
    $modelId = (int)end($pathParts);
}

try {
    $db = new DatabaseHelper($pdo);
    
    switch ($method) {
        case 'GET':
            requireAuth();
            handleGetModels($db, $modelId);
            break;
            
        case 'POST':
            requireAuth();
            handleCreateModel($db);
            break;
            
        case 'PUT':
            requireAuth();
            handleUpdateModel($db, $modelId);
            break;
            
        case 'DELETE':
            requireAuth();
            handleDeleteModel($db, $modelId);
            break;
            
        default:
            http_response_code(405);
            echo json_encode(['error' => 'Method not allowed']);
            break;
    }
    
} catch (Exception $e) {
    error_log("Models API Error: " . $e->getMessage());
    http_response_code(500);
    echo json_encode(['error' => 'Internal server error']);
}

function handleGetModels($db, $modelId = null) {
    try {
        if ($modelId) {
            

            $stmt = $GLOBALS['pdo']->prepare("
                SELECT m.*, 
                       COUNT(p.id) as total_products,
                       COUNT(CASE WHEN p.sold_date IS NULL THEN 1 END) as available_products,
                       COUNT(CASE WHEN p.sold_date IS NOT NULL THEN 1 END) as sold_products
                FROM models m 
                LEFT JOIN products p ON m.id = p.model_id 
                WHERE m.id = ?
                GROUP BY m.id
            ");
            $stmt->execute([$modelId]);
            $model = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if (!$model) {
                http_response_code(404);
                echo json_encode(['error' => 'Model not found']);
                return;
            }
            
            echo json_encode([
                'success' => true,
                'data' => $model
            ]);
        } else {
            

            $brand = $_GET['brand'] ?? null;
            $category = $_GET['category'] ?? null;
            $limit = min((int)($_GET['limit'] ?? 50), 100);
            $offset = (int)($_GET['offset'] ?? 0);
            
            $whereClause = "WHERE 1=1";
            $params = [];
            
            if ($brand) {
                $whereClause .= " AND m.brand = ?";
                $params[] = $brand;
            }
            
            if ($category) {
                $whereClause .= " AND m.category = ?";
                $params[] = $category;
            }
            
            $stmt = $GLOBALS['pdo']->prepare("
                SELECT m.*, 
                       COUNT(p.id) as total_products,
                       COUNT(CASE WHEN p.sold_date IS NULL THEN 1 END) as available_products,
                       COUNT(CASE WHEN p.sold_date IS NOT NULL THEN 1 END) as sold_products
                FROM models m 
                LEFT JOIN products p ON m.id = p.model_id 
                {$whereClause}
                GROUP BY m.id
                ORDER BY m.model_name
                LIMIT {$limit} OFFSET {$offset}
            ");
            $stmt->execute($params);
            $models = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            

            $countStmt = $GLOBALS['pdo']->prepare("
                SELECT COUNT(*) as total FROM models m {$whereClause}
            ");
            $countStmt->execute($params);
            $total = $countStmt->fetch(PDO::FETCH_ASSOC)['total'];
            
            echo json_encode([
                'success' => true,
                'data' => $models,
                'pagination' => [
                    'total' => (int)$total,
                    'limit' => $limit,
                    'offset' => $offset,
                    'has_more' => ($offset + $limit) < $total
                ]
            ]);
        }
    } catch (Exception $e) {
        error_log("Get models error: " . $e->getMessage());
        http_response_code(500);
        echo json_encode(['error' => 'Failed to fetch models']);
    }
}

function handleCreateModel($db) {
    $input = json_decode(file_get_contents('php://input'), true);
    
    

    $required = ['model_name', 'brand', 'category'];
    foreach ($required as $field) {
        if (empty($input[$field])) {
            http_response_code(400);
            echo json_encode(['error' => "Field '{$field}' is required"]);
            return;
        }
    }
    
    try {
        

        $stmt = $GLOBALS['pdo']->prepare("
            SELECT id FROM models WHERE model_name = ? AND brand = ?
        ");
        $stmt->execute([$input['model_name'], $input['brand']]);
        if ($stmt->fetch()) {
            http_response_code(400);
            echo json_encode(['error' => 'Model already exists']);
            return;
        }
        
        

        $stmt = $GLOBALS['pdo']->prepare("
            INSERT INTO models (model_name, brand, category, description, created_at) 
            VALUES (?, ?, ?, ?, NOW())
        ");
        
        $stmt->execute([
            $input['model_name'],
            $input['brand'],
            $input['category'],
            $input['description'] ?? null
        ]);
        
        $modelId = $GLOBALS['pdo']->lastInsertId();
        
        

        auditLog('model_created', "Model created: {$input['brand']} {$input['model_name']}", $modelId);
        
        echo json_encode([
            'success' => true,
            'message' => 'Model created successfully',
            'model_id' => $modelId
        ]);
        
    } catch (Exception $e) {
        error_log("Create model error: " . $e->getMessage());
        http_response_code(500);
        echo json_encode(['error' => 'Failed to create model']);
    }
}

function handleUpdateModel($db, $modelId) {
    if (!$modelId) {
        http_response_code(400);
        echo json_encode(['error' => 'Model ID is required']);
        return;
    }
    
    $input = json_decode(file_get_contents('php://input'), true);
    
    try {
        

        $stmt = $GLOBALS['pdo']->prepare("SELECT * FROM models WHERE id = ?");
        $stmt->execute([$modelId]);
        $model = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if (!$model) {
            http_response_code(404);
            echo json_encode(['error' => 'Model not found']);
            return;
        }
        
        

        $updates = [];
        $params = [];
        
        $allowedFields = ['model_name', 'brand', 'category', 'description'];
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
        
        $params[] = $modelId;
        
        $stmt = $GLOBALS['pdo']->prepare("
            UPDATE models 
            SET " . implode(', ', $updates) . ", updated_at = NOW() 
            WHERE id = ?
        ");
        
        $stmt->execute($params);
        
        

        auditLog('model_updated', "Model updated: {$model['brand']} {$model['model_name']}", $modelId);
        
        echo json_encode([
            'success' => true,
            'message' => 'Model updated successfully'
        ]);
        
    } catch (Exception $e) {
        error_log("Update model error: " . $e->getMessage());
        http_response_code(500);
        echo json_encode(['error' => 'Failed to update model']);
    }
}

function handleDeleteModel($db, $modelId) {
    if (!$modelId) {
        http_response_code(400);
        echo json_encode(['error' => 'Model ID is required']);
        return;
    }
    
    try {
        

        $stmt = $GLOBALS['pdo']->prepare("SELECT * FROM models WHERE id = ?");
        $stmt->execute([$modelId]);
        $model = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if (!$model) {
            http_response_code(404);
            echo json_encode(['error' => 'Model not found']);
            return;
        }
        
        

        $stmt = $GLOBALS['pdo']->prepare("SELECT COUNT(*) as count FROM products WHERE model_id = ?");
        $stmt->execute([$modelId]);
        $productCount = $stmt->fetch(PDO::FETCH_ASSOC)['count'];
        
        if ($productCount > 0) {
            http_response_code(400);
            echo json_encode(['error' => 'Cannot delete model with existing products']);
            return;
        }
        
        

        $stmt = $GLOBALS['pdo']->prepare("DELETE FROM models WHERE id = ?");
        $stmt->execute([$modelId]);
        
        

        auditLog('model_deleted', "Model deleted: {$model['brand']} {$model['model_name']}", $modelId);
        
        echo json_encode([
            'success' => true,
            'message' => 'Model deleted successfully'
        ]);
        
    } catch (Exception $e) {
        error_log("Delete model error: " . $e->getMessage());
        http_response_code(500);
        echo json_encode(['error' => 'Failed to delete model']);
    }
}
?>

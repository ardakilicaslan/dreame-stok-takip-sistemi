<?php

require_once '../config.php';

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    header('Access-Control-Allow-Origin: *');
    header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE');
    header('Access-Control-Allow-Headers: Content-Type, Authorization');
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

try {
    switch ($method) {
        case 'GET':
            requireAuth();
            
            if (isset($_GET['search'])) {
                

                $customers = $db->getCustomers($_GET['search']);
            } else {
                

                $customers = $db->getCustomers();
            }
            
            echo json_encode([
                'success' => true,
                'data' => $customers,
                'count' => count($customers)
            ]);
            break;
            
        case 'POST':
            requireAuth();
            
            $input = json_decode(file_get_contents('php://input'), true);
            if (!$input) {
                $input = $_POST;
            }
            
            $result = $db->addCustomer($input);
            
            if ($result['success']) {
                http_response_code(201);
            } else {
                http_response_code(400);
            }
            
            echo json_encode($result);
            break;
            
        case 'PUT':
            requireAuth();
            
            

            $customerId = end($pathParts);
            if (!is_numeric($customerId)) {
                http_response_code(400);
                echo json_encode(['error' => 'Invalid customer ID']);
                exit();
            }
            
            $input = json_decode(file_get_contents('php://input'), true);
            
            $result = executeTransaction(function($conn) use ($input, $customerId) {
                $validatedData = validateAndSanitize($input, [
                    'name' => ['type' => 'string', 'required' => true, 'options' => ['min' => 2, 'max' => 100]],
                    'email' => ['type' => 'email', 'required' => false],
                    'phone' => ['type' => 'phone', 'required' => false]
                ]);
                
                $stmt = $conn->prepare("
                    UPDATE customers 
                    SET name = ?, email = ?, phone = ? 
                    WHERE id = ?
                ");
                
                $stmt->execute([
                    $validatedData['name'],
                    $validatedData['email'] ?? null,
                    $validatedData['phone'] ?? null,
                    $customerId
                ]);
                
                if ($stmt->rowCount() === 0) {
                    throw new ValidationException('Müşteri bulunamadı veya güncelleme yapılmadı.');
                }
                
                logAudit('UPDATE', 'customers', $customerId, null, $validatedData);
                
                return [
                    'success' => true,
                    'message' => 'Müşteri başarıyla güncellendi'
                ];
            });
            
            echo json_encode($result);
            break;
            
        case 'DELETE':
            requireAuth();
            
            $customerId = end($pathParts);
            if (!is_numeric($customerId)) {
                http_response_code(400);
                echo json_encode(['error' => 'Invalid customer ID']);
                exit();
            }
            
            $result = executeTransaction(function($conn) use ($customerId) {
                

                $stmt = $conn->prepare("SELECT COUNT(*) FROM sales WHERE customer_id = ?");
                $stmt->execute([$customerId]);
                $salesCount = $stmt->fetchColumn();
                
                if ($salesCount > 0) {
                    throw new ValidationException('Bu müşterinin satış kayıtları olduğu için silinemez.');
                }
                
                $stmt = $conn->prepare("DELETE FROM customers WHERE id = ?");
                $stmt->execute([$customerId]);
                
                if ($stmt->rowCount() === 0) {
                    throw new ValidationException('Müşteri bulunamadı.');
                }
                
                logAudit('DELETE', 'customers', $customerId);
                
                return [
                    'success' => true,
                    'message' => 'Müşteri başarıyla silindi'
                ];
            });
            
            echo json_encode($result);
            break;
            
        default:
            http_response_code(405);
            echo json_encode(['error' => 'Method not allowed']);
            break;
    }
    
} catch (ValidationException $e) {
    http_response_code(400);
    echo json_encode([
        'success' => false,
        'error' => $e->getUserMessage()
    ]);
} catch (SecurityException $e) {
    http_response_code(403);
    echo json_encode([
        'success' => false,
        'error' => $e->getUserMessage()
    ]);
} catch (Exception $e) {
    error_log("API Error: " . $e->getMessage());
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'error' => 'Internal server error'
    ]);
}
?>

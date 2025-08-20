<?php

class CustomerController {
    private $db;
    
    public function __construct(DatabaseHelper $db) {
        $this->db = $db;
    }
    
    

    public function index() {
        if (!isLoggedIn()) {
            redirect('login.php');
        }
        
        $notification = '';
        
        

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $notification = $this->handlePost();
        }
        
        

        if (isset($_GET['action'])) {
            $notification = $this->handleGetAction($_GET);
        }
        
        $pageData = [
            'title' => 'Müşteriler',
            'notification' => $notification,
            'customers' => $this->db->getCustomers()
        ];
        
        $this->render('customers', $pageData);
    }
    
    

    private function handlePost() {
        if (isset($_POST['update_sale'])) {
            return $this->updateSale($_POST);
        }
        
        return '';
    }
    
    

    private function handleGetAction($params) {
        switch ($params['action']) {
            case 'delete_sale':
                return $this->deleteSale($params['sale_id'] ?? null);
            default:
                return '';
        }
    }
    
    

    private function updateSale($data) {
        if (!verifyCsrfToken($data['csrf_token'])) {
            return FormHelper::notification('Güvenlik hatası!', 'error');
        }
        
        try {
            $result = executeTransaction(function($conn) use ($data) {
                $saleId = (int)$data['sale_id'];
                $customerId = (int)$data['customer_id'];
                
                $stmt = $conn->prepare("UPDATE sales SET customer_id = ? WHERE id = ?");
                $stmt->execute([$customerId, $saleId]);
                
                logAudit('UPDATE', 'sales', $saleId, null, ['customer_id' => $customerId]);
                
                return ['success' => true, 'message' => 'Satış kaydı güncellendi!'];
            });
            
            return FormHelper::notification($result['message'], 'success');
            
        } catch (Exception $e) {
            return FormHelper::notification('Hata: ' . $e->getMessage(), 'error');
        }
    }
    
    

    private function deleteSale($saleId) {
        if (!$saleId) {
            return FormHelper::notification('Geçersiz satış ID', 'error');
        }
        
        try {
            executeTransaction(function($conn) use ($saleId) {
                $saleId = (int)$saleId;
                
                

                $stmt = $conn->prepare("SELECT serial_number FROM sales WHERE id = ?");
                $stmt->execute([$saleId]);
                $sale = $stmt->fetch(PDO::FETCH_ASSOC);
                
                if ($sale) {
                    

                    $stmt = $conn->prepare("UPDATE serial_numbers SET sold = 0 WHERE serial_number = ?");
                    $stmt->execute([$sale['serial_number']]);
                    
                    

                    $stmt = $conn->prepare("DELETE FROM sales WHERE id = ?");
                    $stmt->execute([$saleId]);
                    
                    logAudit('DELETE', 'sales', $saleId);
                }
            });
            
            redirect("customers.php");
            
        } catch (Exception $e) {
            return FormHelper::notification('Hata: ' . $e->getMessage(), 'error');
        }
    }
    
    

    public function getPurchases() {
        if (!isLoggedIn()) {
            http_response_code(401);
            echo json_encode(['error' => 'Unauthorized']);
            return;
        }
        
        $customerId = $_POST['customer_id'] ?? null;
        if (!$customerId) {
            http_response_code(400);
            echo json_encode(['error' => 'Customer ID required']);
            return;
        }
        
        try {
            $purchases = $this->db->getCustomerPurchases($customerId);
            echo json_encode($purchases);
        } catch (Exception $e) {
            http_response_code(500);
            echo json_encode(['error' => 'Internal server error']);
        }
    }
    
    

    public function add() {
        if (!isLoggedIn()) {
            http_response_code(401);
            echo json_encode(['success' => false, 'message' => 'Unauthorized']);
            return;
        }
        
        if (!verifyCsrfToken($_POST['csrf_token'])) {
            echo json_encode(['success' => false, 'message' => 'Güvenlik hatası!']);
            return;
        }
        
        try {
            $result = $this->db->addCustomer($_POST);
            echo json_encode($result);
        } catch (Exception $e) {
            echo json_encode(['success' => false, 'message' => $e->getMessage()]);
        }
    }
    
    

    private function render($view, $data = []) {
        global $conn;

        

        require_once __DIR__ . '/../error_handler.php';
        require_once __DIR__ . '/../security_functions.php';

        extract($data);
        
        

        $viewPath = __DIR__ . "/../views/{$view}.php";

        if (file_exists($viewPath)) {
            include $viewPath;
        } else {
            

            trigger_error("View not found: {$viewPath}", E_USER_ERROR);
        }
    }
}
?>

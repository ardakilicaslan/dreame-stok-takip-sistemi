<?php

class DatabaseHelper {
    private $conn;
    
    public function __construct($connection) {
        $this->conn = $connection;
    }
    
    

    public function getCustomers($search = null) {
        $query = "SELECT id, name, email, phone FROM customers";
        $params = [];
        
        if ($search) {
            $query .= " WHERE name LIKE ? OR email LIKE ?";
            $searchTerm = "%{$search}%";
            $params = [$searchTerm, $searchTerm];
        }
        
        $query .= " ORDER BY name";
        
        $stmt = $this->conn->prepare($query);
        $stmt->execute($params);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
    
    

    public function getPlatforms() {
        $stmt = $this->conn->prepare("SELECT id, name, image FROM platforms ORDER BY name");
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
    
    

    public function getModelsWithStock($filter = 'all') {
        $query = "
            SELECT 
                m.barcode, 
                m.name, 
                m.price, 
                b.name AS brand_name, 
                c.name AS category_name,
                COALESCE(stock.quantity, 0) AS stock_quantity
            FROM models m
            LEFT JOIN brands b ON m.brand_id = b.id
            LEFT JOIN categories c ON m.category_id = c.id
            LEFT JOIN (
                SELECT barcode, COUNT(*) as quantity 
                FROM serial_numbers 
                WHERE sold = 0 
                GROUP BY barcode
            ) stock ON m.barcode = stock.barcode
        ";
        
        if ($filter === 'no_stock') {
            $query .= " WHERE COALESCE(stock.quantity, 0) = 0";
        }
        
        $query .= " ORDER BY m.name";
        
        $stmt = $this->conn->prepare($query);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
    
    

    public function getCustomerPurchases($customerId) {
        $stmt = $this->conn->prepare("
            SELECT 
                s.id as sale_id,
                s.customer_id,
                s.serial_number,
                m.name as product_name,
                c.name as category,
                s.sale_date,
                p.name as platform_name
            FROM sales s
            INNER JOIN serial_numbers sn ON s.serial_number = sn.serial_number
            INNER JOIN models m ON sn.barcode = m.barcode
            LEFT JOIN categories c ON m.category_id = c.id
            LEFT JOIN platforms p ON s.platform_id = p.id
            WHERE s.customer_id = ?
            ORDER BY s.sale_date DESC
        ");
        
        $stmt->execute([$customerId]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
    
    

    public function addCustomer($data) {
        return executeTransaction(function($conn) use ($data) {
            $validatedData = validateAndSanitize($data, [
                'name' => ['type' => 'string', 'required' => true, 'options' => ['min' => 2, 'max' => 100]],
                'email' => ['type' => 'email', 'required' => false],
                'phone' => ['type' => 'phone', 'required' => false]
            ]);
            
            $stmt = $conn->prepare("
                INSERT INTO customers (name, email, phone) 
                VALUES (?, ?, ?)
            ");
            
            $stmt->execute([
                $validatedData['name'],
                $validatedData['email'] ?? null,
                $validatedData['phone'] ?? null
            ]);
            
            $customerId = $conn->lastInsertId();
            
            

            logAudit('CREATE', 'customers', $customerId, null, $validatedData);
            
            return [
                'success' => true,
                'message' => 'Müşteri başarıyla eklendi',
                'customer' => array_merge(['id' => $customerId], $validatedData)
            ];
        });
    }
    
    

    public function addPlatform($data, $imageFile = null) {
        return executeTransaction(function($conn) use ($data, $imageFile) {
            $validatedData = validateAndSanitize($data, [
                'name' => ['type' => 'string', 'required' => true, 'options' => ['min' => 2, 'max' => 100]]
            ]);
            
            $imageName = null;
            if ($imageFile && $imageFile['error'] === UPLOAD_ERR_OK) {
                $imageName = optimizedFileUpload($imageFile, 'img/platforms');
            }
            
            $stmt = $conn->prepare("INSERT INTO platforms (name, image) VALUES (?, ?)");
            $stmt->execute([$validatedData['name'], $imageName]);
            
            $platformId = $conn->lastInsertId();
            
            

            logAudit('CREATE', 'platforms', $platformId, null, array_merge($validatedData, ['image' => $imageName]));
            
            return [
                'success' => true,
                'message' => 'Platform başarıyla eklendi',
                'platform' => [
                    'id' => $platformId,
                    'name' => $validatedData['name'],
                    'image' => $imageName
                ]
            ];
        });
    }
    
    

    public function processSale($data) {
        return executeTransaction(function($conn) use ($data) {
            $validatedData = validateAndSanitize($data, [
                'customer_id' => ['type' => 'string', 'required' => true],
                'platform_id' => ['type' => 'string', 'required' => true],
                'barcode' => ['type' => 'barcode', 'required' => true],
                'serial_numbers' => ['type' => 'string', 'required' => true]
            ]);
            
            $customerId = (int)$validatedData['customer_id'];
            $platformId = (int)$validatedData['platform_id'];
            $barcode = $validatedData['barcode'];
            $serialNumbers = array_filter(array_map('trim', explode("\n", $validatedData['serial_numbers'])));
            
            if (empty($serialNumbers)) {
                throw new ValidationException('Lütfen en az bir seri numarası girin!');
            }
            
            

            $stmt = $conn->prepare("SELECT id FROM customers WHERE id = ?");
            $stmt->execute([$customerId]);
            if (!$stmt->fetch()) {
                throw new ValidationException('Müşteri bulunamadı!');
            }
            
            

            $stmt = $conn->prepare("SELECT id FROM platforms WHERE id = ?");
            $stmt->execute([$platformId]);
            if (!$stmt->fetch()) {
                throw new ValidationException('Platform bulunamadı!');
            }
            
            

            $stmt = $conn->prepare("SELECT barcode, name FROM models WHERE barcode = ?");
            $stmt->execute([$barcode]);
            $model = $stmt->fetch(PDO::FETCH_ASSOC);
            if (!$model) {
                throw new ValidationException('Geçersiz barkod: ' . $barcode);
            }
            
            

            $invalidSerials = [];
            foreach ($serialNumbers as $serial) {
                $stmt = $conn->prepare("SELECT serial_number FROM serial_numbers WHERE serial_number = ? AND barcode = ? AND sold = 0");
                $stmt->execute([$serial, $barcode]);
                if (!$stmt->fetch()) {
                    $invalidSerials[] = $serial;
                }
            }
            
            if (!empty($invalidSerials)) {
                throw new ValidationException('Aşağıdaki seri numaraları stokta bulunamadı: ' . implode(', ', $invalidSerials));
            }
            
            

            $saleDate = date('Y-m-d');
            $saleIds = [];
            
            foreach ($serialNumbers as $serial) {
                $stmt = $conn->prepare("INSERT INTO sales (serial_number, customer_id, platform_id, quantity, sale_date) VALUES (?, ?, ?, ?, ?)");
                $stmt->execute([$serial, $customerId, $platformId, 1, $saleDate]);
                $saleIds[] = $conn->lastInsertId();
                
                $stmt = $conn->prepare("UPDATE serial_numbers SET sold = 1 WHERE serial_number = ?");
                $stmt->execute([$serial]);
            }
            
            

            logAudit('CREATE', 'sales', implode(',', $saleIds), null, [
                'customer_id' => $customerId,
                'platform_id' => $platformId,
                'barcode' => $barcode,
                'serial_count' => count($serialNumbers)
            ]);
            
            return [
                'success' => true,
                'message' => count($serialNumbers) . ' adet satış başarıyla kaydedildi!'
            ];
        });
    }
    
    

    public function getDashboardStats() {
        $cacheKey = 'dashboard_stats';
        $cached = SimpleCache::get($cacheKey);
        
        if ($cached !== null) {
            return $cached;
        }
        
        $stats = [
            'total_models' => $this->conn->query("SELECT COUNT(*) FROM models")->fetchColumn(),
            'total_customers' => $this->conn->query("SELECT COUNT(*) FROM customers")->fetchColumn(),
            'total_products' => $this->conn->query("SELECT COUNT(*) FROM serial_numbers WHERE sold = 0")->fetchColumn(),
            'total_sales_today' => $this->conn->query("SELECT COUNT(*) FROM sales WHERE DATE(sale_date) = CURDATE()")->fetchColumn()
        ];
        
        SimpleCache::set($cacheKey, $stats, 300); 

        return $stats;
    }
    
}
?>

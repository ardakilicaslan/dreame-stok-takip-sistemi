<?php

if (!defined('PERFORMANCE_OPTIMIZATIONS_LOADED')) {
    define('PERFORMANCE_OPTIMIZATIONS_LOADED', true);

    

    function getOptimizedModelsWithStock() {
        global $conn;
        
        $stmt = $conn->prepare("
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
            ORDER BY m.name
        ");
        
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    

    function getCustomerSalesOptimized($customerId) {
        global $conn;
        
        $stmt = $conn->prepare("
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

    

    class SimpleCache {
        private static $cache = [];
        private static $expiry = [];
        
        public static function get($key) {
            if (isset(self::$cache[$key]) && 
                isset(self::$expiry[$key]) && 
                self::$expiry[$key] > time()) {
                return self::$cache[$key];
            }
            return null;
        }
        
        public static function set($key, $value, $ttl = 300) { 

            self::$cache[$key] = $value;
            self::$expiry[$key] = time() + $ttl;
        }
        
        public static function delete($key) {
            unset(self::$cache[$key]);
            unset(self::$expiry[$key]);
        }
        
        public static function clear() {
            self::$cache = [];
            self::$expiry = [];
        }
    }

    

    function getPaginatedResults($query, $params, $page = 1, $perPage = 20) {
        global $conn;
        
        $offset = ($page - 1) * $perPage;
        
        

        $countQuery = "SELECT COUNT(*) FROM ($query) as count_table";
        $countStmt = $conn->prepare($countQuery);
        $countStmt->execute($params);
        $totalRecords = $countStmt->fetchColumn();
        
        

        $paginatedQuery = $query . " LIMIT $perPage OFFSET $offset";
        $stmt = $conn->prepare($paginatedQuery);
        $stmt->execute($params);
        $results = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        return [
            'data' => $results,
            'pagination' => [
                'current_page' => $page,
                'per_page' => $perPage,
                'total_records' => $totalRecords,
                'total_pages' => ceil($totalRecords / $perPage)
            ]
        ];
    }

    

    function batchInsertSerialNumbers($barcode, $serialNumbers) {
        global $conn;
        
        if (empty($serialNumbers)) {
            return false;
        }
        
        $placeholders = str_repeat('(?,?),', count($serialNumbers) - 1) . '(?,?)';
        $sql = "INSERT INTO serial_numbers (barcode, serial_number) VALUES $placeholders";
        
        $params = [];
        foreach ($serialNumbers as $serial) {
            $params[] = $barcode;
            $params[] = $serial;
        }
        
        $stmt = $conn->prepare($sql);
        return $stmt->execute($params);
    }

    

    class DatabaseManager {
        private static $connections = [];
        private static $maxConnections = 5;
        
        public static function getConnection() {
            

            foreach (self::$connections as $key => $conn) {
                if ($conn && $conn->getAttribute(PDO::ATTR_CONNECTION_STATUS)) {
                    return $conn;
                }
            }
            
            

            if (count(self::$connections) < self::$maxConnections) {
                $dsn = "mysql:host=localhost;dbname=snipeit_clone;charset=utf8mb4";
                $options = [
                    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                    PDO::ATTR_EMULATE_PREPARES => false,
                ];
                
                $conn = new PDO($dsn, "root", "", $options);
                self::$connections[] = $conn;
                return $conn;
            }
            
            

            return self::$connections[0];
        }
    }

    

    function processLargeDataset($callback, $batchSize = 1000) {
        global $conn;
        
        $offset = 0;
        $processed = 0;
        
        do {
            $stmt = $conn->prepare("
                SELECT * FROM serial_numbers 
                LIMIT $batchSize OFFSET $offset
            ");
            $stmt->execute();
            $batch = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            if (!empty($batch)) {
                $callback($batch);
                $processed += count($batch);
                $offset += $batchSize;
                
                

                unset($batch);
                gc_collect_cycles();
            }
        } while (count($batch) === $batchSize);
        
        return $processed;
    }

    

    function getCachedQuery($key, $query, $params = [], $ttl = 300) {
        $cached = SimpleCache::get($key);
        if ($cached !== null) {
            return $cached;
        }
        
        global $conn;
        $stmt = $conn->prepare($query);
        $stmt->execute($params);
        $result = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        SimpleCache::set($key, $result, $ttl);
        return $result;
    }

    

    function optimizedFileUpload($file, $destination) {
        

        validateFileUpload($file);
        
        $filename = generateSecureFilename($file['name']);
        $fullPath = $destination . '/' . $filename;
        
        

        if (move_uploaded_file($file['tmp_name'], $fullPath)) {
            

            if (in_array(strtolower(pathinfo($filename, PATHINFO_EXTENSION)), ['jpg', 'jpeg', 'png'])) {
                optimizeImage($fullPath);
            }
            return $filename;
        }
        
        throw new Exception('Dosya yükleme başarısız');
    }

    

    function optimizeImage($imagePath, $quality = 85) {
        $imageInfo = getimagesize($imagePath);
        if (!$imageInfo) return false;
        
        $width = $imageInfo[0];
        $height = $imageInfo[1];
        $type = $imageInfo[2];
        
        

        if ($width <= 800 && $height <= 800) return true;
        
        $newWidth = min($width, 800);
        $newHeight = ($height * $newWidth) / $width;
        
        $source = null;
        switch ($type) {
            case IMAGETYPE_JPEG:
                $source = imagecreatefromjpeg($imagePath);
                break;
            case IMAGETYPE_PNG:
                $source = imagecreatefrompng($imagePath);
                break;
            default:
                return false;
        }
        
        if (!$source) return false;
        
        $resized = imagecreatetruecolor($newWidth, $newHeight);
        
        

        if ($type === IMAGETYPE_PNG) {
            imagealphablending($resized, false);
            imagesavealpha($resized, true);
        }
        
        imagecopyresampled($resized, $source, 0, 0, 0, 0, $newWidth, $newHeight, $width, $height);
        
        switch ($type) {
            case IMAGETYPE_JPEG:
                imagejpeg($resized, $imagePath, $quality);
                break;
            case IMAGETYPE_PNG:
                imagepng($resized, $imagePath, 9);
                break;
        }
        
        imagedestroy($source);
        imagedestroy($resized);
        
        return true;
    }
}
?>

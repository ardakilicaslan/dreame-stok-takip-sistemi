<?php

if (!defined('SECURITY_FUNCTIONS_LOADED')) {
    define('SECURITY_FUNCTIONS_LOADED', true);

    

    function checkRateLimit($ip, $maxAttempts = 5, $timeWindow = 900) { 

        global $conn;
        
        $stmt = $conn->prepare("
            SELECT COUNT(*) FROM failed_login_attempts 
            WHERE ip_address = ? AND attempt_time > DATE_SUB(NOW(), INTERVAL ? SECOND)
        ");
        $stmt->execute([$ip, $timeWindow]);
        $attempts = $stmt->fetchColumn();
        
        return $attempts < $maxAttempts;
    }

    

    function logFailedLogin($ip, $username = null) {
        global $conn;
        
        $userAgent = $_SERVER['HTTP_USER_AGENT'] ?? '';
        $stmt = $conn->prepare("
            INSERT INTO failed_login_attempts (ip_address, username, user_agent) 
            VALUES (?, ?, ?)
        ");
        $stmt->execute([$ip, $username, $userAgent]);
    }

    

    function cleanupFailedLogins() {
        global $conn;
        
        $stmt = $conn->prepare("
            DELETE FROM failed_login_attempts 
            WHERE attempt_time < DATE_SUB(NOW(), INTERVAL 24 HOUR)
        ");
        $stmt->execute();
    }

    

    function validateInput($data, $type, $options = []) {
        $data = trim($data);
        
        switch ($type) {
            case 'email':
                if (!filter_var($data, FILTER_VALIDATE_EMAIL)) {
                    throw new InvalidArgumentException('Invalid email format');
                }
                break;
                
            case 'phone':
                if (!preg_match('/^[\+]?[0-9\s\-\(\)]{10,15}$/', $data)) {
                    throw new InvalidArgumentException('Invalid phone number format');
                }
                break;
                
            case 'barcode':
                if (!preg_match('/^[A-Za-z0-9]{8,20}$/', $data)) {
                    throw new InvalidArgumentException('Invalid barcode format');
                }
                break;
                
            case 'serial':
                if (!preg_match('/^[A-Za-z0-9\-]{5,30}$/', $data)) {
                    throw new InvalidArgumentException('Invalid serial number format');
                }
                break;
                
            case 'price':
                if (!is_numeric($data) || $data < 0) {
                    throw new InvalidArgumentException('Invalid price value');
                }
                break;
                
            case 'string':
                $minLength = $options['min'] ?? 1;
                $maxLength = $options['max'] ?? 255;
                if (strlen($data) < $minLength || strlen($data) > $maxLength) {
                    throw new InvalidArgumentException("Text length must be between {$minLength} and {$maxLength} characters");
                }
                break;
        }
        
        return sanitizeInput($data);
    }

    

    function logAudit($action, $tableName, $recordId = null, $oldValues = null, $newValues = null) {
        global $conn;
        
        $userId = $_SESSION['user_id'] ?? null;
        $ip = $_SERVER['REMOTE_ADDR'] ?? '';
        $userAgent = $_SERVER['HTTP_USER_AGENT'] ?? '';
        
        $stmt = $conn->prepare("
            INSERT INTO audit_logs (user_id, action, table_name, record_id, old_values, new_values, ip_address, user_agent) 
            VALUES (?, ?, ?, ?, ?, ?, ?, ?)
        ");
        
        $stmt->execute([
            $userId,
            $action,
            $tableName,
            $recordId,
            $oldValues ? json_encode($oldValues) : null,
            $newValues ? json_encode($newValues) : null,
            $ip,
            $userAgent
        ]);
    }

    

    function validatePassword($password) {
        if (strlen($password) < 8) {
            throw new InvalidArgumentException('Password must be at least 8 characters long');
        }
        
        if (!preg_match('/[A-Z]/', $password)) {
            throw new InvalidArgumentException('Password must contain at least one uppercase letter');
        }
        
        if (!preg_match('/[a-z]/', $password)) {
            throw new InvalidArgumentException('Password must contain at least one lowercase letter');
        }
        
        if (!preg_match('/[0-9]/', $password)) {
            throw new InvalidArgumentException('Password must contain at least one number');
        }
        
        return true;
    }

    

    function validateFileUpload($file, $allowedTypes = ['jpg', 'jpeg', 'png'], $maxSize = 2097152) { 

        if ($file['error'] !== UPLOAD_ERR_OK) {
            throw new InvalidArgumentException('File upload error');
        }
        
        if ($file['size'] > $maxSize) {
            throw new InvalidArgumentException('File size is too large (max 2MB)');
        }
        
        $fileInfo = pathinfo($file['name']);
        $extension = strtolower($fileInfo['extension']);
        
        if (!in_array($extension, $allowedTypes)) {
            throw new InvalidArgumentException('Invalid file type');
        }
        
        

        $finfo = finfo_open(FILEINFO_MIME_TYPE);
        $mimeType = finfo_file($finfo, $file['tmp_name']);
        finfo_close($finfo);
        
        $allowedMimes = [
            'jpg' => 'image/jpeg',
            'jpeg' => 'image/jpeg',
            'png' => 'image/png'
        ];
        
        if ($mimeType !== $allowedMimes[$extension]) {
            throw new InvalidArgumentException('File content does not match its extension');
        }
        
        return true;
    }

    

    function generateSecureFilename($originalName) {
        $fileInfo = pathinfo($originalName);
        $extension = strtolower($fileInfo['extension']);
        $timestamp = time();
        $random = bin2hex(random_bytes(8));
        
        return "upload_{$timestamp}_{$random}.{$extension}";
    }

    

    function executeTransaction($callback) {
        global $conn;
        
        try {
            $conn->beginTransaction();
            $result = $callback($conn);
            $conn->commit();
            return $result;
        } catch (Exception $e) {
            $conn->rollBack();
            error_log("Transaction failed: " . $e->getMessage());
            throw $e;
        }
    }

    

    function cleanupAuditLogs($daysToKeep = 90) {
        global $conn;
        
        $stmt = $conn->prepare("
            DELETE FROM audit_logs 
            WHERE created_at < DATE_SUB(NOW(), INTERVAL ? DAY)
        ");
        $stmt->execute([$daysToKeep]);
    }
}
?>

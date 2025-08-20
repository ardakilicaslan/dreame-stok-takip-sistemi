<?php
ob_start();
include 'config.php';
include 'functions.php';

ini_set('display_errors', 0);
ini_set('display_startup_errors', 0);
error_reporting(E_ALL);

header('Content-Type: application/json');

$response = ['success' => false, 'message' => ''];

try {
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        throw new Exception('Geçersiz istek!');
    }

    if (!isset($_POST['csrf_token']) || !verifyCsrfToken($_POST['csrf_token'])) {
        throw new Exception('Güvenlik hatası!');
    }

    $name = isset($_POST['name']) ? sanitizeInput($_POST['name']) : '';
    $email = isset($_POST['email']) && $_POST['email'] ? sanitizeInput($_POST['email']) : null;
    $phone = isset($_POST['phone']) && $_POST['phone'] ? sanitizeInput($_POST['phone']) : null;

    error_log("add_customer.php - Giriş: name=$name, email=$email, phone=$phone");

    if (empty($name)) {
        throw new Exception('Ad soyad zorunludur!');
    }

    if ($email && !filter_var($email, FILTER_VALIDATE_EMAIL)) {
        throw new Exception('Geçersiz e-posta formatı!');
    }

    if ($email) {
        $stmt = $conn->prepare("SELECT id FROM customers WHERE email = ?");
        $stmt->execute([$email]);
        if ($stmt->fetch()) {
            throw new Exception('Bu e-posta zaten kayıtlı!');
        }
    }

    $stmt = $conn->prepare("INSERT INTO customers (name, email, phone) VALUES (?, ?, ?)");
    $stmt->execute([$name, $email, $phone]);

    $response['success'] = true;
    $response['message'] = 'Müşteri başarıyla eklendi!';
    $response['customer'] = [
        'id' => $conn->lastInsertId(),
        'name' => $name
    ];

} catch (Exception $e) {
    $response['message'] = $e->getMessage();
    error_log("add_customer.php - Hata: " . $e->getMessage());
}

ob_end_clean();
echo json_encode($response);
exit;
?>
<?php

include 'config.php';

include 'functions.php';

header('Content-Type: application/json');

try {

    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {

        throw new Exception('Geçersiz istek!');

    }

    $customer_id = isset($_POST['customer_id']) ? (int)$_POST['customer_id'] : 0;

    if ($customer_id <= 0) {

        throw new Exception('Geçersiz müşteri ID!');

    }

    $stmt = $conn->prepare("

        SELECT 

            s.id AS sale_id,

            s.customer_id,

            s.serial_number,

            m.name AS product_name,

            c.name AS category

        FROM sales s

        JOIN serial_numbers sn ON s.serial_number = sn.serial_number

        JOIN models m ON sn.barcode = m.barcode

        JOIN categories c ON m.category_id = c.id

        WHERE s.customer_id = ?

    ");

    $stmt->execute([$customer_id]);

    $products = $stmt->fetchAll(PDO::FETCH_ASSOC);

    

    error_log("fetch_purchased_products.php - customer_id: $customer_id, bulunan ürün sayısı: " . count($products));

    echo json_encode($products);

} catch (Exception $e) {

    error_log("fetch_purchased_products.php - Hata: " . $e->getMessage());

    echo json_encode(['error' => $e->getMessage()]);

}

?>
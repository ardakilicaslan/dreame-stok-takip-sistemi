<?php
include 'config.php';
include 'functions.php';

header('Content-Type: application/json');

try {
    $query = "SELECT id, name, email, phone FROM customers";
    $searchValue = $_GET['search']['value'] ?? '';
    if (!empty($searchValue)) {
        $query .= " WHERE name LIKE :search OR email LIKE :search";
    }
    $stmt = $conn->prepare($query);
    if (!empty($searchValue)) {
        $searchParam = "%{$searchValue}%";
        $stmt->bindParam(':search', $searchParam);
    }
    $stmt->execute();
    $customers = $stmt->fetchAll(PDO::FETCH_ASSOC);
    echo json_encode($customers);
} catch (PDOException $e) {
    echo json_encode(['error' => $e->getMessage()]);
}
?>
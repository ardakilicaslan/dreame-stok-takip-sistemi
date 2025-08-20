<?php

header('Content-Type: application/json');

include 'config.php';

try {

        $stmt = $conn->prepare("SELECT id, name, image_path AS image FROM platforms ORDER BY CASE WHEN id = 10 THEN 0 ELSE 1 END, name ASC");

    $stmt->execute();

    $platforms = $stmt->fetchAll(PDO::FETCH_ASSOC);

    echo json_encode($platforms);

} catch (PDOException $e) {

    echo json_encode(['error' => 'Veritabanı hatası: ' . $e->getMessage()]);

}

?>
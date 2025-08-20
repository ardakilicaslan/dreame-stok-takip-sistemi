<?php

header('Content-Type: application/json');

include 'config.php';

include 'functions.php';

if (!verifyCsrfToken($_POST['csrf_token'])) {

    echo json_encode(['success' => false, 'message' => 'Güvenlik hatası!']);

    exit;

}

try {

    $name = sanitizeInput($_POST['name']);

    if (empty($name)) {

        echo json_encode(['success' => false, 'message' => 'Platform adı zorunludur!']);

        exit;

    }

    $image = null;

    if (!empty($_FILES['image']['name'])) {

        $allowed_types = ['image/jpeg', 'image/png', 'image/jpg'];

        $max_size = 2 * 1024 * 1024; 

        $file_type = $_FILES['image']['type'];

        $file_size = $_FILES['image']['size'];

        $file_tmp = $_FILES['image']['tmp_name'];

        $file_ext = strtolower(pathinfo($_FILES['image']['name'], PATHINFO_EXTENSION));

        $file_name = 'platform_' . time() . '.' . $file_ext;

        $upload_path = 'img/platforms/' . $file_name;

        if (!in_array($file_type, $allowed_types)) {

            echo json_encode(['success' => false, 'message' => 'Sadece JPG, JPEG veya PNG dosyaları yüklenebilir!']);

            exit;

        }

        if ($file_size > $max_size) {

            echo json_encode(['success' => false, 'message' => 'Dosya boyutu 2MB\'dan büyük olamaz!']);

            exit;

        }

        if (!move_uploaded_file($file_tmp, $upload_path)) {

            echo json_encode(['success' => false, 'message' => 'Resim yüklenirken bir hata oluştu!']);

            exit;

        }

        $image = $file_name;

        if (!chmod($upload_path, 0644)) {

            error_log("Dosya izinleri ayarlanamadı: $upload_path");

        }

    }

        $stmt = $conn->prepare("INSERT INTO platforms (name, image_path) VALUES (?, ?)");

    $stmt->execute([$name, $image]);

    $platform_id = $conn->lastInsertId();

    echo json_encode([

        'success' => true,

        'message' => 'Platform başarıyla eklendi!',

        'platform' => [

            'id' => $platform_id,

            'name' => $name,

            'image' => $image

        ]

    ]);

} catch (PDOException $e) {

    error_log("PDO Hatası: " . $e->getMessage());

    echo json_encode(['success' => false, 'message' => 'Veritabanı hatası: ' . $e->getMessage()]);

} catch (Exception $e) {

    error_log("Genel Hata: " . $e->getMessage());

    echo json_encode(['success' => false, 'message' => 'Hata: ' . $e->getMessage()]);

}

?>
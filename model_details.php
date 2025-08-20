<?php
session_start();
include 'config.php';
include 'functions.php';

if (!isLoggedIn()) {
    redirect('login.php');
}

if (!isset($_GET['model_name'])) {
    redirect('models.php');
}

$model_name = sanitizeInput($_GET['model_name']);

$stmt = $conn->prepare("SELECT id, name FROM categories ORDER BY name");
$stmt->execute();
$categories = $stmt->fetchAll(PDO::FETCH_ASSOC);

$stmt = $conn->prepare("SELECT id, name FROM brands ORDER BY name");
$stmt->execute();
$brands = $stmt->fetchAll(PDO::FETCH_ASSOC);

try {
    $stmt = $conn->prepare("
        SELECT m.barcode, m.name, m.purchase_price, m.sale_price, m.image, m.created_at, m.category_id, m.brand_id,
               b.name AS brand_name, c.name AS category_name,
               (SELECT COUNT(*) FROM serial_numbers sn WHERE sn.barcode = m.barcode AND sn.sold = 0) AS stock_quantity
        FROM models m
        LEFT JOIN brands b ON m.brand_id = b.id
        LEFT JOIN categories c ON m.category_id = c.id
        WHERE m.name = ?
    ");
    $stmt->execute([$model_name]);
    $model_info = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$model_info) {
        redirect('models.php');
    }

    $stmt = $conn->prepare("
        SELECT 
            sn.serial_number, 
            sn.created_at, 
            sn.created_by, 
            sn.sold,
            u.username AS created_by_username,
            s.sale_date
        FROM 
            serial_numbers sn
        LEFT JOIN 
            users u ON sn.created_by = u.id
        LEFT JOIN 
            sales s ON sn.serial_number = s.serial_number
        WHERE 
            sn.barcode = ?
        ORDER BY 
            sn.sold ASC, s.sale_date ASC
    ");
    $stmt->execute([$model_info['barcode']]);
    $serial_numbers = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    die("Hata: " . $e->getMessage());
}

if (isset($_GET['action']) && $_GET['action'] === 'delete' && isset($_GET['serial_number'])) {
    try {
        $stmt = $conn->prepare("DELETE FROM serial_numbers WHERE serial_number = ?");
        $stmt->execute([$_GET['serial_number']]);
        redirect("model_details.php?model_name=" . urlencode($model_name));
    } catch (PDOException $e) {
        die("Hata: " . $e->getMessage());
    }
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['delete_model'])) {
    if (!verifyCsrfToken($_POST['csrf_token'])) {
        $notification = '<div class="bg-red-500 text-white p-4 rounded mb-4">Güvenlik hatası!</div>';
    } else {
        try {
            if ($model_info['image'] && file_exists('img/uploads/' . $model_info['image'])) {
                unlink('img/uploads/' . $model_info['image']);
            }

            $stmt = $conn->prepare("DELETE FROM serial_numbers WHERE barcode = ?");
            $stmt->execute([$model_info['barcode']]);

            $stmt = $conn->prepare("DELETE FROM models WHERE barcode = ?");
            $stmt->execute([$model_info['barcode']]);

            $notification = '<div class="bg-green-500 text-white p-4 rounded mb-4">Model başarıyla kaldırıldı!</div>';
            redirect('models.php');
        } catch (PDOException $e) {
            $notification = '<div class="bg-red-500 text-white p-4 rounded mb-4">Hata: ' . $e->getMessage() . '</div>';
        }
    }
}

$notification = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_model'])) {
    if (!verifyCsrfToken($_POST['csrf_token'])) {
        $notification = '<div class="bg-red-500 text-white p-4 rounded mb-4">Güvenlik hatası!</div>';
    } else {
        try {
            $name = sanitizeInput($_POST['name']);
            $barcode = sanitizeInput($_POST['barcode']);
            $category_id = (int)$_POST['category_id'];
            $brand_id = (int)$_POST['brand_id'];
            $purchase_price = floatval($_POST['purchase_price']);
            $sale_price = floatval($_POST['sale_price']);
            $old_barcode = $model_info['barcode'];

            if (empty($name) || empty($barcode) || $category_id <= 0 || $brand_id <= 0 || $purchase_price <= 0 || $sale_price <= 0) {
                throw new Exception('Tüm alanlar zorunludur ve fiyatlar sıfırdan büyük olmalıdır!');
            }

            $conn->beginTransaction();

            if ($barcode !== $old_barcode) {
                $stmt = $conn->prepare("SELECT COUNT(*) FROM models WHERE barcode = ?");
                $stmt->execute([$barcode]);
                if ($stmt->fetchColumn() > 0) {
                    throw new Exception('Bu barkod zaten kullanılıyor!');
                }

                $stmt = $conn->prepare("
                    UPDATE models
                    SET name = ?, barcode = ?, category_id = ?, brand_id = ?, purchase_price = ?, sale_price = ?
                    WHERE barcode = ?
                ");
                $stmt->execute([$name, $barcode, $category_id, $brand_id, $purchase_price, $sale_price, $old_barcode]);
                $updated_models = $stmt->rowCount();
                error_log("Güncellenen models satır sayısı: " . $updated_models . " | Eski Barkod: $old_barcode | Yeni Barkod: $barcode");

                $stmt = $conn->prepare("UPDATE serial_numbers SET barcode = ? WHERE barcode = ?");
                $stmt->execute([$barcode, $old_barcode]);
                $updated_rows = $stmt->rowCount();
                error_log("Güncellenen serial_numbers satır sayısı: " . $updated_rows . " | Eski Barkod: $old_barcode | Yeni Barkod: $barcode");
            } else {
                $stmt = $conn->prepare("
                    UPDATE models
                    SET name = ?, barcode = ?, category_id = ?, brand_id = ?, purchase_price = ?, sale_price = ?
                    WHERE barcode = ?
                ");
                $stmt->execute([$name, $barcode, $category_id, $brand_id, $purchase_price, $sale_price, $old_barcode]);
                $updated_models = $stmt->rowCount();
                error_log("Güncellenen models satır sayısı: " . $updated_models . " | Barkod: $barcode");
            }

            $conn->commit();

            $notification = '<div class="bg-green-500 text-white p-4 rounded mb-4">Model başarıyla güncellendi!</div>';
            redirect("model_details.php?model_name=" . urlencode($name));
        } catch (PDOException $e) {
            $conn->rollBack();
            error_log("PDO Hata: " . $e->getMessage() . " | POST: " . print_r($_POST, true));
            $notification = '<div class="bg-red-500 text-white p-4 rounded mb-4">Hata: ' . $e->getMessage() . '</div>';
        } catch (Exception $e) {
            $conn->rollBack();
            $notification = '<div class="bg-red-500 text-white p-4 rounded mb-4">Hata: ' . $e->getMessage() . '</div>';
        }
    }
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_image'])) {
    if (!verifyCsrfToken($_POST['csrf_token'])) {
        $notification = '<div class="bg-red-500 text-white p-4 rounded mb-4">Güvenlik hatası!</div>';
    } else {
        try {
            if (!empty($_FILES['image']['name'])) {
                $allowed_types = ['image/jpeg', 'image/png', 'image/jpg'];
                $max_size = 5 * 1024 * 1024; 

                $file_type = $_FILES['image']['type'];
                $file_size = $_FILES['image']['size'];
                $file_tmp = $_FILES['image']['tmp_name'];
                $file_ext = strtolower(pathinfo($_FILES['image']['name'], PATHINFO_EXTENSION));
                $file_name = 'product_' . $model_info['barcode'] . '.' . $file_ext;
                $upload_path = 'img/uploads/' . $file_name;

                if (!in_array($file_type, $allowed_types)) {
                    throw new Exception('Sadece JPG, JPEG veya PNG dosyaları yüklenebilir!');
                }
                if ($file_size > $max_size) {
                    throw new Exception('Dosya boyutu 5MB\'dan büyük olamaz!');
                }

                if ($model_info['image'] && file_exists('img/uploads/' . $model_info['image'])) {
                    unlink('img/uploads/' . $model_info['image']);
                }

                if (!move_uploaded_file($file_tmp, $upload_path)) {
                    throw new Exception('Resim yüklenirken bir hata oluştu!');
                }

                $stmt = $conn->prepare("UPDATE models SET image = ? WHERE barcode = ?");
                $stmt->execute([$file_name, $model_info['barcode']]);

                $notification = '<div class="bg-green-500 text-white p-4 rounded mb-4">Resim başarıyla güncellendi!</div>';
                redirect("model_details.php?model_name=" . urlencode($model_name));
            } else {
                throw new Exception('Lütfen bir resim seçin!');
            }
        } catch (Exception $e) {
            $notification = '<div class="bg-red-500 text-white p-4 rounded mb-4">Hata: ' . $e->getMessage() . '</div>';
        }
    }
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_serial'])) {
    if (!verifyCsrfToken($_POST['csrf_token'])) {
        $notification = '<div class="bg-red-500 text-white p-4 rounded mb-4">Güvenlik hatası!</div>';
    } else {
        try {
            $old_serial_number = sanitizeInput($_POST['old_serial_number']);
            $new_serial_number = sanitizeInput($_POST['serial_number']);

            $stmt = $conn->prepare("SELECT serial_number FROM serial_numbers WHERE serial_number = ? AND serial_number != ?");
            $stmt->execute([$new_serial_number, $old_serial_number]);
            if ($stmt->fetch()) {
                throw new Exception('Bu seri numarası başka bir üründe kullanılıyor!');
            }

            $stmt = $conn->prepare("UPDATE serial_numbers SET serial_number = ? WHERE serial_number = ?");
            $stmt->execute([$new_serial_number, $old_serial_number]);

            $notification = '<div class="bg-green-500 text-white p-4 rounded mb-4">Seri numarası başarıyla güncellendi!</div>';
            redirect("model_details.php?model_name=" . urlencode($model_name));
        } catch (Exception $e) {
            $notification = '<div class="bg-red-500 text-white p-4 rounded mb-4">Hata: ' . $e->getMessage() . '</div>';
        }
    }
}
?>

<!DOCTYPE html>
<html lang="tr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Model Detayları: <?php echo sanitizeInput($model_name); ?></title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <link href="https://cdn.datatables.net/1.13.4/css/jquery.dataTables.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/remixicon@4.3.0/fonts/remixicon.css" rel="stylesheet">
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="https://cdn.datatables.net/1.13.4/js/jquery.dataTables.min.js"></script>
</head>
<body class="bg-gray-100 flex">
    <?php include 'sidebar.php'; ?>
    <div class="flex-1">
        <?php include 'header.php'; ?>
        <div class="content p-6">
            <h1 class="text-3xl font-bold mb-6 text-gray-800 flex items-center">
                <i class="ri-box-3-line mr-2 text-blue-600"></i> Model Detayları: <?php echo sanitizeInput($model_name); ?>
            </h1>
            <?php echo $notification; ?>
            <div class="bg-white shadow-lg rounded-lg p-6 mb-6">
                <h2 class="text-xl font-semibold mb-4 flex items-center">
                    <i class="ri-information-line mr-2 text-blue-600"></i> Model Bilgileri
                </h2>
                <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                    <div class="col-span-2">
                        <p><strong>Marka:</strong> <?php echo sanitizeInput($model_info['brand_name']); ?></p>
                        <p><strong>Model İsmi:</strong> <?php echo sanitizeInput($model_info['name']); ?></p>
                        <p><strong>Barkod:</strong> <?php echo sanitizeInput($model_info['barcode']); ?></p>
                        <p><strong>Kategori:</strong> <?php echo sanitizeInput($model_info['category_name']); ?></p>
                        <p><strong>Alış Fiyatı:</strong> <?php echo number_format($model_info['purchase_price'], 2); ?> TL</p>
                        <p><strong>Satış Fiyatı:</strong> <?php echo number_format($model_info['sale_price'], 2); ?> TL</p>
                        <p><strong>Stok Miktarı:</strong> <?php echo $model_info['stock_quantity']; ?></p>
                        <p><strong>Oluşturulma Tarihi:</strong> <?php echo date('d.m.Y H:i', strtotime($model_info['created_at'])); ?></p>
                    </div>
                    <div class="flex justify-center items-center">
                        <?php if ($model_info['image']): ?>
                            <img src="img/uploads/<?php echo sanitizeInput($model_info['image']); ?>" alt="Ürün Resmi" class="max-w-full h-auto rounded-lg shadow-md" style="max-height: 150px;">
                        <?php else: ?>
                            <div class="bg-gray-200 rounded-lg flex items-center justify-center" style="width: 150px; height: 150px;">
                                <i class="ri-image-line text-gray-500 text-3xl"></i>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
                <div class="mt-4 flex space-x-2">
                    <button onclick="openEditModelModal()" class="bg-blue-600 text-white px-4 py-2 rounded-md hover:bg-blue-700">
                        <i class="ri-edit-line mr-1"></i> Modeli Düzenle
                    </button>
                    <button onclick="openImageModal()" class="bg-green-600 text-white px-4 py-2 rounded-md hover:bg-green-700">
                        <i class="ri-image-line mr-1"></i> Resmi Güncelle
                    </button>
                    <form method="POST" onsubmit="return confirm('<?php echo $model_info['stock_quantity'] > 0 ? "Bu modelin stokta " . $model_info['stock_quantity'] . " adet ürünü var. Yine de kaldırmak istediğinizden emin misiniz?" : "Bu modeli kaldırmak istediğinizden emin misiniz?"; ?>');">
                        <input type="hidden" name="csrf_token" value="<?php echo generateCsrfToken(); ?>">
                        <button type="submit" name="delete_model" class="bg-red-600 text-white px-4 py-2 rounded-md hover:bg-red-700">
                            <i class="ri-delete-bin-line mr-1"></i> Modeli Kaldır
                        </button>
                    </form>
                </div>
            </div>
            <div class="bg-white shadow-lg rounded-lg overflow-hidden">
                <table id="productsTable" class="min-w-full divide-y divide-gray-200">
                    <thead class="bg-gray-50">
                        <tr>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Seri Numarası</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Oluşturma Tarihi</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Oluşturan</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Durum</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">İşlemler</th>
                        </tr>
                    </thead>
                    <tbody class="bg-white divide-y divide-gray-200">
                        <?php foreach ($serial_numbers as $serial): ?>
                            <tr class="hover:bg-gray-50">
                                <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900"><?php echo sanitizeInput($serial['serial_number']); ?></td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500"><?php echo date('d.m.Y H:i', strtotime($serial['created_at'])); ?></td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500"><?php echo $serial['created_by_username'] ?: 'Bilinmeyen Kullanıcı'; ?></td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm">
                                    <?php if ($serial['sold']): ?>
                                        <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full bg-red-100 text-red-800">
                                            Satıldı
                                        </span>
                                    <?php else: ?>
                                        <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full bg-green-100 text-green-800">
                                            Stokta
                                        </span>
                                    <?php endif; ?>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                    <button onclick="openEditSerialModal('<?php echo addslashes(sanitizeInput($serial['serial_number'])); ?>', '<?php echo addslashes(sanitizeInput($serial['serial_number'])); ?>')" class="text-blue-600 hover:underline mr-2"><i class="ri-edit-line"></i> Düzenle</button>
                                    <a href="model_details.php?model_name=<?php echo urlencode($model_name); ?>&action=delete&serial_number=<?php echo urlencode($serial['serial_number']); ?>" class="text-red-600 hover:underline" onclick="return confirm('Bu seri numarasını silmek istediğinizden emin misiniz?');"><i class="ri-delete-bin-line"></i> Sil</a>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <!-- Model Düzenleme Modal -->
    <div id="editModelModal" class="fixed inset-0 bg-gray-600 bg-opacity-50 flex items-center justify-center hidden" style="z-index: 1000;">
        <div class="bg-white p-6 rounded-lg shadow-lg w-full max-w-md">
            <h2 class="text-xl font-bold mb-4">Modeli Düzenle</h2>
            <form method="POST">
                <input type="hidden" name="csrf_token" value="<?php echo generateCsrfToken(); ?>">
                <div class="mb-4">
                    <label for="name" class="block text-sm font-medium text-gray-700">Model İsmi</label>
                    <input type="text" id="name" name="name" value="<?php echo sanitizeInput($model_info['name']); ?>" class="mt-1 block w-full p-2 border border-gray-300 rounded-md" required>
                </div>
                <div class="mb-4">
                    <label for="barcode" class="block text-sm font-medium text-gray-700">Barkod</label>
                    <input type="text" id="barcode" name="barcode" value="<?php echo sanitizeInput($model_info['barcode']); ?>" class="mt-1 block w-full p-2 border border-gray-300 rounded-md" required>
                </div>
                <div class="mb-4">
                    <label for="category_id" class="block text-sm font-medium text-gray-700">Kategori</label>
                    <select id="category_id" name="category_id" class="mt-1 block w-full p-2 border border-gray-300 rounded-md" required>
                        <?php foreach ($categories as $category): ?>
                            <option value="<?php echo $category['id']; ?>" <?php echo $category['id'] == $model_info['category_id'] ? 'selected' : ''; ?>><?php echo htmlspecialchars($category['name']); ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="mb-4">
                    <label for="brand_id" class="block text-sm font-medium text-gray-700">Marka</label>
                    <select id="brand_id" name="brand_id" class="mt-1 block w-full p-2 border border-gray-300 rounded-md" required>
                        <?php foreach ($brands as $brand): ?>
                            <option value="<?php echo $brand['id']; ?>" <?php echo $brand['id'] == $model_info['brand_id'] ? 'selected' : ''; ?>><?php echo htmlspecialchars($brand['name']); ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="mb-4">
                    <label for="purchase_price" class="block text-sm font-medium text-gray-700">Alış Fiyatı (TL)</label>
                    <input type="number" id="purchase_price" name="purchase_price" step="0.01" value="<?php echo $model_info['purchase_price']; ?>" class="mt-1 block w-full p-2 border border-gray-300 rounded-md" required>
                </div>
                <div class="mb-4">
                    <label for="sale_price" class="block text-sm font-medium text-gray-700">Satış Fiyatı (TL)</label>
                    <input type="number" id="sale_price" name="sale_price" step="0.01" value="<?php echo $model_info['sale_price']; ?>" class="mt-1 block w-full p-2 border border-gray-300 rounded-md" required>
                </div>
                <div class="flex justify-end">
                    <button type="button" onclick="closeEditModelModal()" class="bg-gray-500 text-white px-4 py-2 rounded-md hover:bg-gray-600 mr-2">İptal</button>
                    <button type="submit" name="update_model" class="bg-blue-600 text-white px-4 py-2 rounded-md hover:bg-blue-700">Kaydet</button>
                </div>
            </form>
        </div>
    </div>

    <!-- Resim Güncelleme Modal -->
    <div id="imageModal" class="fixed inset-0 bg-gray-600 bg-opacity-50 flex items-center justify-center hidden" style="z-index: 1000;">
        <div class="bg-white p-6 rounded-lg shadow-lg w-full max-w-md">
            <h2 class="text-xl font-bold mb-4">Resmi Güncelle</h2>
            <form method="POST" enctype="multipart/form-data">
                <input type="hidden" name="csrf_token" value="<?php echo generateCsrfToken(); ?>">
                <div class="mb-4">
                    <label for="image" class="block text-sm font-medium text-gray-700">Yeni Resim Seç</label>
                    <input type="file" id="image" name="image" accept=".jpg,.jpeg,.png" class="mt-1 block w-full p-2 border border-gray-300 rounded-md" required>
                </div>
                <div class="flex justify-end">
                    <button type="button" onclick="closeImageModal()" class="bg-gray-500 text-white px-4 py-2 rounded-md hover:bg-gray-600 mr-2">İptal</button>
                    <button type="submit" name="update_image" class="bg-green-600 text-white px-4 py-2 rounded-md hover:bg-green-700">Yükle</button>
                </div>
            </form>
        </div>
    </div>

    <!-- Seri Numarası Düzenleme Modal -->
    <div id="editSerialModal" class="fixed inset-0 bg-gray-600 bg-opacity-50 flex items-center justify-center hidden" style="z-index: 1000;">
        <div class="bg-white p-6 rounded-lg shadow-lg w-full max-w-md">
            <h2 class="text-xl font-bold mb-4">Seri Numarasını Düzenle</h2>
            <form method="POST">
                <input type="hidden" name="csrf_token" value="<?php echo generateCsrfToken(); ?>">
                <input type="hidden" id="old_serial_number" name="old_serial_number">
                <div class="mb-4">
                    <label for="serial_number" class="block text-sm font-medium text-gray-700">Seri Numarası</label>
                    <input type="text" id="serial_number" name="serial_number" class="mt-1 block w-full p-2 border border-gray-300 rounded-md" required>
                </div>
                <div class="flex justify-end">
                    <button type="button" onclick="closeEditSerialModal()" class="bg-gray-500 text-white px-4 py-2 rounded-md hover:bg-gray-600 mr-2">İptal</button>
                    <button type="submit" name="update_serial" class="bg-blue-600 text-white px-4 py-2 rounded-md hover:bg-blue-700">Kaydet</button>
                </div>
            </form>
        </div>
    </div>

    <script>
        $(document).ready(function () {
            $('#productsTable').DataTable({
                "language": {
                    "url": "//cdn.datatables.net/plug-ins/1.13.4/i18n/tr.json"
                },
                "order": []
            });
        });

        function openEditModelModal() {
            document.getElementById('editModelModal').classList.remove('hidden');
        }

        function closeEditModelModal() {
            document.getElementById('editModelModal').classList.add('hidden');
        }

        function openImageModal() {
            document.getElementById('imageModal').classList.remove('hidden');
        }

        function closeImageModal() {
            document.getElementById('imageModal').classList.add('hidden');
        }

        function openEditSerialModal(old_serial_number, serial_number) {
            document.getElementById('old_serial_number').value = old_serial_number;
            document.getElementById('serial_number').value = serial_number;
            document.getElementById('editSerialModal').classList.remove('hidden');
        }

        function closeEditSerialModal() {
            document.getElementById('editSerialModal').classList.add('hidden');
        }
    </script>
</body>
</html>
?>
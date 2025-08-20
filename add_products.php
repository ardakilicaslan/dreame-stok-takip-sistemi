<?php
include 'config.php';
include 'functions.php';

if (!isLoggedIn()) {
    redirect('login.php');
}

$notification = '';
$serial_count = 0;

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add_product'])) {
    if (!verifyCsrfToken($_POST['csrf_token'])) {
        $notification = '<div class="bg-red-500 text-white p-4 rounded mb-4">Güvenlik hatası!</div>';
    } else {
        try {
            $name = trim($_POST['name']);
            $barcode = trim($_POST['barcode']);
            $category_id = (int)$_POST['category_id'];
            $brand_id = (int)$_POST['brand_id'];
            $purchase_price = floatval(str_replace(',', '', $_POST['purchase_price'] ?? '0'));
            $sale_price = floatval(str_replace(',', '', $_POST['sale_price'] ?? '0'));
            $serial_numbers = isset($_POST['serial_numbers']) ? trim($_POST['serial_numbers']) : '';
            $image = null;

            if (empty($name) || empty($barcode) || $category_id <= 0 || $brand_id <= 0 || $purchase_price <= 0 || $sale_price <= 0) {
                throw new Exception('Tüm alanlar zorunludur ve fiyatlar sıfırdan büyük olmalıdır!');
            }

            if (!empty($_FILES['image']['name'])) {
                $allowed_types = ['image/jpeg', 'image/png', 'image/jpg'];
                $max_size = 5 * 1024 * 1024; 

                $file_type = $_FILES['image']['type'];
                $file_size = $_FILES['image']['size'];
                $file_tmp = $_FILES['image']['tmp_name'];
                $file_ext = strtolower(pathinfo($_FILES['image']['name'], PATHINFO_EXTENSION));
                $file_name = 'product_' . $barcode . '.' . $file_ext;
                $upload_path = 'img/uploads/' . $file_name;

                if (!in_array($file_type, $allowed_types)) {
                    throw new Exception('Sadece JPG, JPEG veya PNG dosyaları yüklenebilir!');
                }
                if ($file_size > $max_size) {
                    throw new Exception('Dosya boyutu 5MB\'dan büyük olamaz!');
                }

                if (!move_uploaded_file($file_tmp, $upload_path)) {
                    throw new Exception('Resim yüklenirken bir hata oluştu!');
                }

                $image = $file_name;
            }

            $stmt = $conn->prepare("SELECT COUNT(*) FROM models WHERE barcode = ?");
            $stmt->execute([$barcode]);
            if ($stmt->fetchColumn() > 0) {
                throw new Exception('Bu barkod zaten kullanılıyor!');
            }

            $stmt = $conn->prepare("
                INSERT INTO models (name, barcode, category_id, brand_id, purchase_price, sale_price, image, created_by, created_at)
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, NOW())
            ");
            $stmt->execute([$name, $barcode, $category_id, $brand_id, $purchase_price, $sale_price, $image, $_SESSION['user_id']]);

            if (!empty($serial_numbers)) {
                $serial_array = array_filter(array_map('trim', explode("\n", $serial_numbers)));
                $serial_count = count($serial_array);

                foreach ($serial_array as $serial) {
                    if (empty($serial)) continue;

                    $stmt = $conn->prepare("SELECT COUNT(*) FROM serial_numbers WHERE serial_number = ?");
                    $stmt->execute([$serial]);
                    if ($stmt->fetchColumn() > 0) {
                        throw new Exception("Seri numarası '$serial' zaten kayıtlı!");
                    }

                    $stmt = $conn->prepare("
                        INSERT INTO serial_numbers (serial_number, barcode, sold, created_by, created_at)
                        VALUES (?, ?, 0, ?, NOW())
                    ");
                    $stmt->execute([$serial, $barcode, $_SESSION['user_id']]);
                }
            }

            $notification = '<div class="bg-green-500 text-white p-4 rounded mb-4">Ürün ve ' . $serial_count . ' adet seri numarası başarıyla eklendi!</div>';
        } catch (Exception $e) {
            $notification = '<div class="bg-red-500 text-white p-4 rounded mb-4">Hata: ' . $e->getMessage() . '</div>';
        }
    }
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add_brand'])) {
    header('Content-Type: application/json');
    if (!verifyCsrfToken($_POST['csrf_token'])) {
        echo json_encode(['success' => false, 'message' => 'Güvenlik hatası!']);
        exit;
    }

    try {
        $brand_name = trim($_POST['brand_name']);
        if (empty($brand_name)) {
            throw new Exception('Marka ismi zorunludur!');
        }

        $stmt = $conn->prepare("INSERT INTO brands (name, created_at) VALUES (?, NOW())");
        $stmt->execute([$brand_name]);
        echo json_encode(['success' => true, 'message' => 'Marka başarıyla eklendi!']);
    } catch (Exception $e) {
        echo json_encode(['success' => false, 'message' => 'Hata: ' . $e->getMessage()]);
    }
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add_category'])) {
    header('Content-Type: application/json');
    if (!verifyCsrfToken($_POST['csrf_token'])) {
        echo json_encode(['success' => false, 'message' => 'Güvenlik hatası!']);
        exit;
    }

    try {
        $category_name = trim($_POST['category_name']);
        if (empty($category_name)) {
            throw new Exception('Kategori ismi zorunludur!');
        }

        $stmt = $conn->prepare("INSERT INTO categories (name, created_at) VALUES (?, NOW())");
        $stmt->execute([$category_name]);
        echo json_encode(['success' => true, 'message' => 'Kategori başarıyla eklendi!']);
    } catch (Exception $e) {
        echo json_encode(['success' => false, 'message' => 'Hata: ' . $e->getMessage()]);
    }
    exit;
}

if (isset($_GET['get_brands'])) {
    header('Content-Type: application/json');
    $stmt = $conn->prepare("SELECT id, name FROM brands ORDER BY name");
    $stmt->execute();
    $brands = $stmt->fetchAll(PDO::FETCH_ASSOC);
    $options = '';
    foreach ($brands as $brand) {
        $options .= '<option value="' . $brand['id'] . '">' . htmlspecialchars($brand['name']) . '</option>';
    }
    echo json_encode(['options' => $options]);
    exit;
}

if (isset($_GET['get_categories'])) {
    header('Content-Type: application/json');
    $stmt = $conn->prepare("SELECT id, name FROM categories ORDER BY name");
    $stmt->execute();
    $categories = $stmt->fetchAll(PDO::FETCH_ASSOC);
    $options = '';
    foreach ($categories as $category) {
        $options .= '<option value="' . $category['id'] . '">' . htmlspecialchars($category['name']) . '</option>';
    }
    echo json_encode(['options' => $options]);
    exit;
}

$stmt = $conn->prepare("SELECT id, name FROM categories ORDER BY name");
$stmt->execute();
$categories = $stmt->fetchAll(PDO::FETCH_ASSOC);

$stmt = $conn->prepare("SELECT id, name FROM brands ORDER BY name");
$stmt->execute();
$brands = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html lang="tr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Yeni Ürün Ekle</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://cdn.jsdelivr.net/npm/remixicon@4.3.0/fonts/remixicon.css" rel="stylesheet">
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <link href="https://fonts.googleapis.com/css2?family=Be+Vietnam+Pro:wght@400;500;600;700&display=swap" rel="stylesheet">
    <style>
        body {
            font-family: 'Be Vietnam Pro', sans-serif;
        }
        textarea {
            resize: vertical;
        }
        #serialCount {
            display: none;
        }
        #serialCount.show {
            display: block;
        }
        .modal {
            z-index: 1000;
        }
    </style>
</head>
<body class="bg-gray-100">
    <div class="flex h-screen">
        <?php include 'views/components/sidebar.php'; ?>
        <div class="flex-1 flex flex-col">
            <?php include 'views/components/header.php'; ?>
            <main class="flex-1 p-6 content">
            <h1 class="text-3xl font-bold mb-6 text-gray-800 flex items-center">
                <i class="ri-box-3-line mr-2 text-blue-600"></i> Yeni Ürün Ekle
            </h1>
            <?php echo $notification; ?>
            <div class="bg-white shadow-lg rounded-lg p-6">
                <form method="POST" id="addProductForm" enctype="multipart/form-data">
                    <input type="hidden" name="csrf_token" value="<?php echo generateCsrfToken(); ?>">
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mb-4">
                        <div>
                            <label for="name" class="block text-sm font-medium text-gray-700 flex items-center">
                                <i class="ri-box-2-line mr-2 text-blue-600"></i> Ürün İsmi
                            </label>
                            <input type="text" id="name" name="name" class="mt-1 block w-full p-2 border border-gray-300 rounded-md" required>
                        </div>
                        <div>
                            <label for="barcode" class="block text-sm font-medium text-gray-700 flex items-center">
                                <i class="ri-barcode-line mr-2 text-blue-600"></i> Barkod
                            </label>
                            <input type="text" id="barcode" name="barcode" class="mt-1 block w-full p-2 border border-gray-300 rounded-md" required>
                        </div>
                    </div>
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mb-4">
                        <div>
                            <label for="category_id" class="block text-sm font-medium text-gray-700 flex items-center">
                                <i class="ri-folder-line mr-2 text-blue-600"></i> Kategori
                            </label>
                            <div class="flex items-center">
                                <select id="category_id" name="category_id" class="mt-1 block w-full p-2 border border-gray-300 rounded-md" required>
                                    <option value="">Kategori Seç</option>
                                    <?php foreach ($categories as $category): ?>
                                        <option value="<?php echo $category['id']; ?>"><?php echo htmlspecialchars($category['name']); ?></option>
                                    <?php endforeach; ?>
                                </select>
                                <button type="button" id="addCategoryBtn" class="ml-2 bg-blue-600 text-white px-3 py-1 rounded-md hover:bg-blue-700">
                                    <i class="ri-add-line"></i>
                                </button>
                            </div>
                        </div>
                        <div>
                            <label for="brand_id" class="block text-sm font-medium text-gray-700 flex items-center">
                                <i class="ri-price-tag-3-line mr-2 text-blue-600"></i> Marka
                            </label>
                            <div class="flex items-center">
                                <select id="brand_id" name="brand_id" class="mt-1 block w-full p-2 border border-gray-300 rounded-md" required>
                                    <option value="">Marka Seç</option>
                                    <?php foreach ($brands as $brand): ?>
                                        <option value="<?php echo $brand['id']; ?>"><?php echo htmlspecialchars($brand['name']); ?></option>
                                    <?php endforeach; ?>
                                </select>
                                <button type="button" id="addBrandBtn" class="ml-2 bg-blue-600 text-white px-3 py-1 rounded-md hover:bg-blue-700">
                                    <i class="ri-add-line"></i>
                                </button>
                            </div>
                        </div>
                    </div>
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mb-4">
                        <div>
                            <label for="purchase_price" class="block text-sm font-medium text-gray-700 flex items-center">
                                <i class="ri-money-dollar-circle-line mr-2 text-blue-600"></i> Alış Fiyatı (TL)
                            </label>
                            <input type="text" id="purchase_price" name="purchase_price" class="mt-1 block w-full p-2 border border-gray-300 rounded-md price-input" required>
                        </div>
                        <div>
                            <label for="sale_price" class="block text-sm font-medium text-gray-700 flex items-center">
                                <i class="ri-money-dollar-circle-line mr-2 text-blue-600"></i> Satış Fiyatı (TL)
                            </label>
                            <input type="text" id="sale_price" name="sale_price" class="mt-1 block w-full p-2 border border-gray-300 rounded-md price-input" required>
                        </div>
                        <div>
                            <label for="image" class="block text-sm font-medium text-gray-700 flex items-center">
                                <i class="ri-image-line mr-2 text-blue-600"></i> Ürün Resmi (Opsiyonel)
                            </label>
                            <input type="file" id="image" name="image" accept=".jpg,.jpeg,.png" class="mt-1 block w-full p-2 border border-gray-300 rounded-md">
                        </div>
                    </div>
                    <div class="mb-4">
                        <label for="serial_numbers" class="block text-sm font-medium text-gray-700 flex items-center">
                            <i class="ri-barcode-box-line mr-2 text-blue-600"></i> Seri Numaraları (Her satıra bir seri numarası)
                        </label>
                        <textarea id="serial_numbers" name="serial_numbers" rows="5" class="mt-1 block w-full p-2 border border-gray-300 rounded-md" placeholder="Barkod okuyucuyla tarayın veya elle girin"></textarea>
                        <p id="serialCount" class="text-green-600 mt-2">0 adet seri numarası girildi</p>
                    </div>
                    <div class="flex justify-end">
                        <button type="submit" name="add_product" class="bg-blue-600 text-white px-4 py-2 rounded-md hover:bg-blue-700">
                            <i class="ri-save-line mr-1"></i> Ürünü ve Seri Numaralarını Kaydet
                        </button>
                    </div>
                </form>
            </div>
            </main>
        </div>
    </div>
        <!-- Marka Ekle Modal -->
        <div id="addBrandModal" class="fixed inset-0 bg-gray-600 bg-opacity-50 flex items-center justify-center hidden modal">
            <div class="bg-white p-6 rounded-lg shadow-lg w-full max-w-md">
                <h2 class="text-xl font-bold mb-4 flex items-center">
                    <i class="ri-price-tag-3-line mr-2 text-blue-600"></i> Yeni Marka Ekle
                </h2>
                <form method="POST" id="addBrandForm">
                    <input type="hidden" name="csrf_token" value="<?php echo generateCsrfToken(); ?>">
                    <input type="hidden" name="add_brand" value="1">
                    <div class="mb-4">
                        <label for="brand_name" class="block text-sm font-medium text-gray-700">Marka İsmi</label>
                        <input type="text" id="brand_name" name="brand_name" class="mt-1 block w-full p-2 border border-gray-300 rounded-md" required>
                    </div>
                    <div class="flex justify-end">
                        <button type="button" id="closeBrandModalBtn" class="bg-gray-500 text-white px-4 py-2 rounded-md hover:bg-gray-600 mr-2">
                            <i class="ri-close-line mr-1"></i> İptal
                        </button>
                        <button type="submit" class="bg-blue-600 text-white px-4 py-2 rounded-md hover:bg-blue-700">
                            <i class="ri-save-line mr-1"></i> Kaydet
                        </button>
                    </div>
                </form>
            </div>
        </div>

        <!-- Kategori Ekle Modal -->
        <div id="addCategoryModal" class="fixed inset-0 bg-gray-600 bg-opacity-50 flex items-center justify-center hidden modal">
            <div class="bg-white p-6 rounded-lg shadow-lg w-full max-w-md">
                <h2 class="text-xl font-bold mb-4 flex items-center">
                    <i class="ri-folder-line mr-2 text-blue-600"></i> Yeni Kategori Ekle
                </h2>
                <form method="POST" id="addCategoryForm">
                    <input type="hidden" name="csrf_token" value="<?php echo generateCsrfToken(); ?>">
                    <input type="hidden" name="add_category" value="1">
                    <div class="mb-4">
                        <label for="category_name" class="block text-sm font-medium text-gray-700">Kategori İsmi</label>
                        <input type="text" id="category_name" name="category_name" class="mt-1 block w-full p-2 border border-gray-300 rounded-md" required>
                    </div>
                    <div class="flex justify-end">
                        <button type="button" id="closeCategoryModalBtn" class="bg-gray-500 text-white px-4 py-2 rounded-md hover:bg-gray-600 mr-2">
                            <i class="ri-close-line mr-1"></i> İptal
                        </button>
                        <button type="submit" class="bg-blue-600 text-white px-4 py-2 rounded-md hover:bg-blue-700">
                            <i class="ri-save-line mr-1"></i> Kaydet
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <script>
    $(document).ready(function () {
        console.log('jQuery yüklendi, sayfa hazır');

        $('.price-input').on('input', function () {
            let value = $(this).val().replace(/[^0-9]/g, '');
            if (value) {
                value = parseInt(value).toLocaleString('tr-TR');
                $(this).val(value);
            }
        }).on('blur', function () {
            let value = $(this).val().replace(/[^0-9]/g, '');
            if (!value) {
                $(this).val('');
                return;
            }
            value = parseInt(value).toLocaleString('tr-TR');
            $(this).val(value);
        });

        const serialTextarea = $('#serial_numbers');
        const serialCountDisplay = $('#serialCount');

        function updateSerialCount() {
            const serials = serialTextarea.val().split('\n').filter(serial => serial.trim() !== '');
            const count = serials.length;
            serialCountDisplay.text(`${count} adet seri numarası girildi`);
            serialCountDisplay.toggleClass('show', count > 0);
        }

        serialTextarea.on('input change', function () {
            updateSerialCount();
            this.scrollTop = this.scrollHeight;
        });

        $('#addBrandBtn').on('click', function () {
            console.log('Marka Ekle butonuna tıklandı');
            $('#addBrandModal').removeClass('hidden');
        });

        $('#closeBrandModalBtn').on('click', function () {
            console.log('Marka modalı kapatıldı');
            $('#addBrandModal').addClass('hidden');
            $('#addBrandForm')[0].reset();
        });

        $('#addBrandForm').on('submit', function (e) {
            e.preventDefault();
            console.log('Marka ekleme formu gönderildi');
            const formData = $(this).serialize();
            console.log('Gönderilen veri:', formData);

            $.ajax({
                url: 'add_products.php',
                type: 'POST',
                data: formData,
                dataType: 'json',
                success: function (response) {
                    console.log('Marka ekleme yanıtı:', response);
                    if (response.success) {
                        $('#addBrandModal').addClass('hidden');
                        $('#addBrandForm')[0].reset();
                        $.get('add_products.php?get_brands=1', function (data) {
                            console.log('Marka listesi alındı:', data);
                            $('#brand_id').html('<option value="">Marka Seç</option>' + data.options);
                        }, 'json').fail(function (xhr, status, error) {
                            console.error('Marka listesi alınamadı:', status, error, xhr.responseText);
                        });
                        alert('Marka başarıyla eklendi!');
                    } else {
                        alert('Hata: ' + response.message);
                    }
                },
                error: function (xhr, status, error) {
                    console.error('Marka ekleme AJAX hatası:', status, error, xhr.responseText);
                    alert('Bir hata oluştu: ' + (xhr.responseText || 'Bilinmeyen hata'));
                }
            });
        });

        $('#addCategoryBtn').on('click', function () {
            console.log('Kategori Ekle butonuna tıklandı');
            $('#addCategoryModal').removeClass('hidden');
        });

        $('#closeCategoryModalBtn').on('click', function () {
            console.log('Kategori modalı kapatıldı');
            $('#addCategoryModal').addClass('hidden');
            $('#addCategoryForm')[0].reset();
        });

        $('#addCategoryForm').on('submit', function (e) {
            e.preventDefault();
            console.log('Kategori ekleme formu gönderildi');
            const formData = $(this).serialize();
            console.log('Gönderilen veri:', formData);

            $.ajax({
                url: 'add_products.php',
                type: 'POST',
                data: formData,
                dataType: 'json',
                success: function (response) {
                    console.log('Kategori ekleme yanıtı:', response);
                    if (response.success) {
                        $('#addCategoryModal').addClass('hidden');
                        $('#addCategoryForm')[0].reset();
                        $.get('add_products.php?get_categories=1', function (data) {
                            console.log('Kategori listesi alındı:', data);
                            $('#category_id').html('<option value="">Kategori Seç</option>' + data.options);
                        }, 'json').fail(function (xhr, status, error) {
                            console.error('Kategori listesi alınamadı:', status, error, xhr.responseText);
                        });
                        alert('Kategori başarıyla eklendi!');
                    } else {
                        alert('Hata: ' + response.message);
                    }
                },
                error: function (xhr, status, error) {
                    console.error('Kategori ekleme AJAX hatası:', status, error, xhr.responseText);
                    alert('Bir hata oluştu: ' + (xhr.responseText || 'Bilinmeyen hata'));
                }
            });
        });

        $('#addProductForm').on('submit', function (e) {
            const name = $('#name').val().trim();
            const barcode = $('#barcode').val().trim();
            const category_id = $('#category_id').val();
            const brand_id = $('#brand_id').val();
            const purchase_price = $('#purchase_price').val().replace(/[^0-9]/g, '');
            const sale_price = $('#sale_price').val().replace(/[^0-9]/g, '');

            if (!name || !barcode || !category_id || !brand_id || !purchase_price || parseFloat(purchase_price) <= 0 || !sale_price || parseFloat(sale_price) <= 0) {
                alert('Lütfen tüm zorunlu alanları doldurun ve geçerli fiyatlar girin!');
                e.preventDefault();
                return false;
            }

            $('#purchase_price').val(parseFloat(purchase_price));
            $('#sale_price').val(parseFloat(sale_price));
            const serials = serialTextarea.val().split('\n').filter(serial => serial.trim() !== '');
            serialTextarea.val(serials.join('\n'));
        });

        updateSerialCount();

        $('#addBrandModal').on('click', function (event) {
            if (event.target === this) {
                console.log('Marka modalı arka plana tıklanarak kapatıldı');
                $('#addBrandModal').addClass('hidden');
                $('#addBrandForm')[0].reset();
            }
        });

        $('#addCategoryModal').on('click', function (event) {
            if (event.target === this) {
                console.log('Kategori modalı arka plana tıklanarak kapatıldı');
                $('#addCategoryModal').addClass('hidden');
                $('#addCategoryForm')[0].reset();
            }
        });
    });
    </script>
</body>
</html>
?>
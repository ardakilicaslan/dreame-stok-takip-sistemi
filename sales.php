<?php
include 'config.php';
include 'functions.php';

if (!isLoggedIn()) {
    redirect('login.php');
}

setlocale(LC_TIME, 'tr_TR');
$notification = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!verifyCsrfToken($_POST['csrf_token'])) {
        $notification = '<div class="bg-red-500 text-white p-4 rounded mb-4">Güvenlik hatası!</div>';
    } else {
        try {
            $customer_id = (int)$_POST['customer_id'];
            $platform_id = (int)$_POST['platform_id'];
            $barcode = sanitizeInput($_POST['barcode']);
            $serial_numbers_input = sanitizeInput($_POST['serial_numbers']);
            $sale_date = date('Y-m-d');

            $serial_numbers = array_filter(array_map('trim', explode("\n", $serial_numbers_input)));

            if (empty($serial_numbers)) {
                $notification = '<div class="bg-red-500 text-white p-4 rounded mb-4">Lütfen en az bir seri numarası girin!</div>';
            } else {
                $stmt = $conn->prepare("SELECT id FROM customers WHERE id = ?");
                $stmt->execute([$customer_id]);
                if (!$stmt->fetch()) {
                    $notification = '<div class="bg-red-500 text-white p-4 rounded mb-4">Müşteri bulunamadı!</div>';
                } else {
                    $stmt = $conn->prepare("SELECT id FROM platforms WHERE id = ?");
                    $stmt->execute([$platform_id]);
                    if (!$stmt->fetch()) {
                        $notification = '<div class="bg-red-500 text-white p-4 rounded mb-4">Platform bulunamadı!</div>';
                    } else {
                                                                        $stmt = $conn->prepare("SELECT barcode, name, sale_price FROM models WHERE barcode = ?");
                        $stmt->execute([$barcode]);
                        $model = $stmt->fetch(PDO::FETCH_ASSOC);
                        if (!$model) {
                            $notification = '<div class="bg-red-500 text-white p-4 rounded mb-4">Geçersiz barkod: ' . htmlspecialchars($barcode) . '</div>';
                        } else {
                            $invalid_serials = [];
                            foreach ($serial_numbers as $serial) {
                                $stmt = $conn->prepare("SELECT serial_number FROM serial_numbers WHERE serial_number = ? AND barcode = ? AND sold = 0");
                                $stmt->execute([$serial, $barcode]);
                                if (!$stmt->fetch()) {
                                    $invalid_serials[] = $serial;
                                }
                            }

                            if (!empty($invalid_serials)) {
                                $notification = '<div class="bg-red-500 text-white p-4 rounded mb-4">Aşağıdaki seri numaraları stokta bulunamadı, bu modele ait değil veya zaten satılmış: ' . htmlspecialchars(implode(', ', $invalid_serials)) . '</div>';
                            } else {
                                foreach ($serial_numbers as $serial) {
                                                                                                            $stmt = $conn->prepare("INSERT INTO sales (serial_number, customer_id, platform_id, sale_date, sale_price) VALUES (?, ?, ?, ?, ?)");
                                                                        $stmt->execute([$serial, $customer_id, $platform_id, $sale_date, $model['sale_price']]);

                                    $stmt = $conn->prepare("UPDATE serial_numbers SET sold = 1 WHERE serial_number = ?");
                                    $stmt->execute([$serial]);
                                }

                                $notification = '<div class="bg-green-500 text-white p-4 rounded mb-4">' . count($serial_numbers) . ' adet satış başarıyla kaydedildi!</div>';
                            }
                        }
                    }
                }
            }
        } catch (PDOException $e) {
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
    <title>Satış Yap</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://cdn.jsdelivr.net/npm/remixicon@4.3.0/fonts/remixicon.css" rel="stylesheet">
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <link href="https://fonts.googleapis.com/css2?family=Be+Vietnam+Pro:wght@400;500;600;700&display=swap" rel="stylesheet">
    <style>
        body {
            font-family: 'Be Vietnam Pro', sans-serif;
        }
        .custom-dropdown {
            position: relative;
            width: 100%;
        }
        .custom-dropdown-toggle {
            width: 100%;
            padding: 0.5rem;
            border: 1px solid #d1d5db;
            border-radius: 0.375rem;
            background-color: white;
            display: flex;
            align-items: center;
            cursor: pointer;
        }
        .custom-dropdown-toggle img {
            width: 50px;
            height: 24px;
            object-fit: contain;
            margin-right: 0.5rem;
            border-radius: 4px;
        }
        .custom-dropdown-menu {
            position: absolute;
            top: 100%;
            left: 0;
            width: 100%;
            max-height: 200px;
            overflow-y: auto;
            border: 1px solid #d1d5db;
            border-radius: 0.375rem;
            background-color: white;
            z-index: 1000;
            display: none;
        }
        .custom-dropdown-item {
            padding: 0.5rem;
            display: flex;
            align-items: center;
            cursor: pointer;
        }
        .custom-dropdown-item:hover {
            background-color: #f1f5f9;
        }
        .custom-dropdown-item img {
            width: 50px;
            height: 24px;
            object-fit: contain;
            margin-right: 0.5rem;
            border-radius: 4px;
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
                <i class="ri-shopping-cart-line mr-2 text-blue-600"></i> Satış Yap
            </h1>
            <?php echo $notification; ?>
            <form method="POST" class="bg-white p-6 rounded-lg shadow-lg" enctype="multipart/form-data">
                <input type="hidden" name="csrf_token" value="<?php echo generateCsrfToken(); ?>">
                <input type="hidden" name="sale_date" value="<?php echo date('Y-m-d'); ?>">
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <div>
                        <label for="customer_id" class="block text-sm font-medium text-gray-700 flex items-center">
                            <i class="ri-user-line mr-2 text-blue-600"></i> Müşteri
                        </label>
                        <div class="flex items-center mt-1">
                            <select id="customer_id" name="customer_id" class="block w-full p-2 border border-gray-300 rounded-md" required>
                                <!-- AJAX ile doldurulacak -->
                            </select>
                            <button type="button" onclick="openAddCustomerModal()" class="ml-2 bg-blue-600 text-white px-3 py-1 rounded-md hover:bg-blue-700">
                                <i class="ri-user-add-line"></i>
                            </button>
                        </div>
                    </div>
                    <div>
                        <label for="platform_id" class="block text-sm font-medium text-gray-700 flex items-center">
                            <i class="ri-store-line mr-2 text-blue-600"></i> Platform
                        </label>
                        <div class="flex items-center mt-1">
                            <div class="custom-dropdown">
                                <input type="hidden" id="platform_id" name="platform_id" required>
                                <div class="custom-dropdown-toggle" id="platformDropdownToggle">
                                    <span>Lütfen bir platform seçin</span>
                                </div>
                                <div class="custom-dropdown-menu" id="platformDropdownMenu">
                                    <!-- AJAX ile doldurulacak -->
                                </div>
                            </div>
                            <button type="button" onclick="openAddPlatformModal()" class="ml-2 bg-blue-600 text-white px-3 py-1 rounded-md hover:bg-blue-700">
                                <i class="ri-add-line"></i>
                            </button>
                        </div>
                    </div>
                    <div>
                        <label for="barcode" class="block text-sm font-medium text-gray-700 flex items-center">
                            <i class="ri-box-3-line mr-2 text-blue-600"></i> Ürün Barkodu
                        </label>
                        <input type="text" id="barcode" name="barcode" class="mt-1 block w-full p-2 border border-gray-300 rounded-md" required placeholder="Barkodu tarayın">
                    </div>
                    <div class="md:col-span-2">
                        <label for="serial_numbers" class="block text-sm font-medium text-gray-700 flex items-center">
                            <i class="ri-barcode-line mr-2 text-blue-600"></i> Seri Numaraları
                        </label>
                        <textarea id="serial_numbers" name="serial_numbers" rows="5" class="mt-1 block w-full p-2 border border-gray-300 rounded-md" required placeholder="Her satıra bir seri numarası girin"></textarea>
                        <p class="text-sm text-gray-500 mt-1">Toplam: <span id="serial_count">0</span> seri numarası</p>
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 flex items-center">
                            <i class="ri-calendar-line mr-2 text-blue-600"></i> Satış Tarihi
                        </label>
                        <p class="mt-1 text-sm text-gray-900"><?php echo strftime('%d %B %Y'); ?></p>
                    </div>
                </div>
                <div class="mt-6">
                    <button type="submit" class="bg-blue-600 text-white px-4 py-2 rounded-md hover:bg-blue-700"><i class="ri-save-line mr-1"></i> Satışı Kaydet</button>
                    <a href="stock.php" class="ml-2 bg-gray-500 text-white px-4 py-2 rounded-md hover:bg-gray-600"><i class="ri-close-line mr-1"></i> İptal</a>
                </div>
            </form>
            </main>
        </div>
    </div>

    <!-- Müşteri Ekle Modal -->
    <div id="addCustomerModal" class="fixed inset-0 bg-gray-600 bg-opacity-50 flex items-center justify-center hidden" style="z-index: 1000;">
        <div class="bg-white p-6 rounded-lg shadow-lg w-full max-w-md">
            <h2 class="text-xl font-bold mb-4 flex items-center">
                <i class="ri-user-add-line mr-2 text-blue-600"></i> Müşteri Ekle
            </h2>
            <form id="addCustomerForm">
                <input type="hidden" name="csrf_token" value="<?php echo generateCsrfToken(); ?>">
                <div class="mb-4">
                    <label for="name" class="block text-sm font-medium text-gray-700 flex items-center">
                        <i class="ri-user-line mr-2 text-blue-600"></i> Ad Soyad
                    </label>
                    <input type="text" id="name" name="name" class="mt-1 block w-full p-2 border border-gray-300 rounded-md" required>
                </div>
                <div class="mb-4">
                    <label for="email" class="block text-sm font-medium text-gray-700 flex items-center">
                        <i class="ri-mail-line mr-2 text-blue-600"></i> E-posta
                    </label>
                    <input type="email" id="email" name="email" class="mt-1 block w-full p-2 border border-gray-300 rounded-md">
                </div>
                <div class="mb-4">
                    <label for="phone" class="block text-sm font-medium text-gray-700 flex items-center">
                        <i class="ri-phone-line mr-2 text-blue-600"></i> Telefon
                    </label>
                    <input type="text" id="phone" name="phone" class="mt-1 block w-full p-2 border border-gray-300 rounded-md">
                </div>
                <div class="flex justify-end">
                    <button type="button" onclick="closeAddCustomerModal()" class="bg-gray-500 text-white px-4 py-2 rounded-md hover:bg-gray-600 mr-2"><i class="ri-close-line mr-1"></i> İptal</button>
                    <button type="submit" class="bg-blue-600 text-white px-4 py-2 rounded-md hover:bg-blue-700"><i class="ri-save-line mr-1"></i> Kaydet</button>
                </div>
            </form>
        </div>
    </div>

    <!-- Platform Ekle Modal -->
    <div id="addPlatformModal" class="fixed inset-0 bg-gray-600 bg-opacity-50 flex items-center justify-center hidden" style="z-index: 1000;">
        <div class="bg-white p-6 rounded-lg shadow-lg w-full max-w-md">
            <h2 class="text-xl font-bold mb-4 flex items-center">
                <i class="ri-store-line mr-2 text-blue-600"></i> Platform Ekle
            </h2>
            <form id="addPlatformForm" enctype="multipart/form-data">
                <input type="hidden" name="csrf_token" value="<?php echo generateCsrfToken(); ?>">
                <div class="mb-4">
                    <label for="platform_name" class="block text-sm font-medium text-gray-700 flex items-center">
                        <i class="ri-store-line mr-2 text-blue-600"></i> Platform Adı
                    </label>
                    <input type="text" id="platform_name" name="name" class="mt-1 block w-full p-2 border border-gray-300 rounded-md" required>
                </div>
                <div class="mb-4">
                    <label for="platform_image" class="block text-sm font-medium text-gray-700 flex items-center">
                        <i class="ri-image-line mr-2 text-blue-600"></i> Platform Resmi (Opsiyonel)
                    </label>
                    <input type="file" id="platform_image" name="image" accept=".jpg,.jpeg,.png" class="mt-1 block w-full p-2 border border-gray-300 rounded-md">
                </div>
                <div class="flex justify-end">
                    <button type="button" onclick="closeAddPlatformModal()" class="bg-gray-500 text-white px-4 py-2 rounded-md hover:bg-gray-600 mr-2"><i class="ri-close-line mr-1"></i> İptal</button>
                    <button type="submit" class="bg-blue-600 text-white px-4 py-2 rounded-md hover:bg-blue-700"><i class="ri-save-line mr-1"></i> Kaydet</button>
                </div>
            </form>
        </div>
    </div>

    <script>
document.addEventListener('DOMContentLoaded', function () {
    const serialNumbersTextarea = document.getElementById('serial_numbers');
    const serialCountSpan = document.getElementById('serial_count');
    const customerSelect = $('#customer_id');
    const platformInput = $('#platform_id');
    const platformDropdownToggle = $('#platformDropdownToggle');
    const platformDropdownMenu = $('#platformDropdownMenu');

    function updateSerialCount() {
        const serials = serialNumbersTextarea.value.split('\n').filter(line => line.trim() !== '');
        serialCountSpan.textContent = serials.length;
    }

    serialNumbersTextarea.addEventListener('input', updateSerialCount);
    updateSerialCount();

    function loadCustomers() {
        $.ajax({
            url: 'fetch_customers.php',
            type: 'GET',
            dataType: 'json',
            success: function(customers) {
                customerSelect.empty();
                customers.forEach(customer => {
                    customerSelect.append(`<option value="${customer.id}">${customer.name}</option>`);
                });
            },
            error: function(xhr) {
                console.error('Müşteri yükleme hatası:', xhr.responseText);
            }
        });
    }
    loadCustomers();

    function loadPlatforms() {
        $.ajax({
            url: 'fetch_platforms.php',
            type: 'GET',
            dataType: 'json',
            success: function(platforms) {
                platformDropdownMenu.empty();
                platforms.forEach(platform => {
                    if (platform.image) {
                                                const imagePath = `/Envantera/img/platforms/${platform.image}`;
                        console.log(`Platform resmi kontrol ediliyor: ${imagePath}`); // Hata ayıklama için
                        platformDropdownMenu.append(`
                            <div class="custom-dropdown-item" data-value="${platform.id}" data-image="${imagePath}">
                                <img src="${imagePath}" alt="${platform.name}" onerror="this.style.display='none';">
                                <span>${platform.name}</span>
                            </div>
                        `);
                    } else {
                        platformDropdownMenu.append(`
                            <div class="custom-dropdown-item" data-value="${platform.id}">
                                <span>${platform.name}</span>
                            </div>
                        `);
                    }
                });

                const trendyol = platforms.find(platform => platform.id == 10);
                if (trendyol) {
                    platformInput.val(trendyol.id);
                    if (trendyol.image) {
                                                const imagePath = `/Envantera/img/platforms/${trendyol.image}`;
                        platformDropdownToggle.html(`
                            <img src="${imagePath}" alt="${trendyol.name}" onerror="this.style.display='none';">
                            <span>${trendyol.name}</span>
                        `);
                    } else {
                        platformDropdownToggle.html(`<span>${trendyol.name}</span>`);
                    }
                } else if (platforms.length > 0) {
                    const firstPlatform = platforms[0];
                    platformInput.val(firstPlatform.id);
                    if (firstPlatform.image) {
                                                const imagePath = `/Envantera/img/platforms/${firstPlatform.image}`;
                        platformDropdownToggle.html(`
                            <img src="${imagePath}" alt="${firstPlatform.name}" onerror="this.style.display='none';">
                            <span>${firstPlatform.name}</span>
                        `);
                    } else {
                        platformDropdownToggle.html(`<span>${firstPlatform.name}</span>`);
                    }
                }
            },
            error: function(xhr) {
                console.error('Platform yükleme hatası:', xhr.responseText);
            }
        });
    }
    loadPlatforms();

    platformDropdownToggle.on('click', function() {
        platformDropdownMenu.toggle();
    });

    $(document).on('click', function(e) {
        if (!$(e.target).closest('.custom-dropdown').length) {
            platformDropdownMenu.hide();
        }
    });

    platformDropdownMenu.on('click', '.custom-dropdown-item', function() {
        const value = $(this).data('value');
        const image = $(this).data('image');
        const name = $(this).find('span').text().trim();
        platformInput.val(value);
        if (image) {
            platformDropdownToggle.html(`
                <img src="${image}" alt="${name}" onerror="this.style.display='none';">
                <span>${name}</span>
            `);
        } else {
            platformDropdownToggle.html(`<span>${name}</span>`);
        }
        platformDropdownMenu.hide();
    });

    $('#addCustomerForm').on('submit', function(e) {
        e.preventDefault();
        const formData = $(this).serialize();

        $.ajax({
            url: 'add_customer.php',
            type: 'POST',
            data: formData,
            dataType: 'json',
            success: function(response) {
                if (response.success) {
                    closeAddCustomerModal();
                    customerSelect.append(`<option value="${response.customer.id}">${response.customer.name}</option>`);
                    customerSelect.val(response.customer.id);
                    alert(response.message);
                } else {
                    alert(response.message);
                }
            },
            error: function(xhr) {
                alert('Bir hata oluştu: ' + xhr.responseText);
            }
        });
    });

    $('#addPlatformForm').on('submit', function(e) {
        e.preventDefault();
        const formData = new FormData(this);

        $.ajax({
            url: 'add_platform.php',
            type: 'POST',
            data: formData,
            dataType: 'json',
            contentType: false,
            processData: false,
            success: function(response) {
                console.log('add_platform.php yanıtı:', response);
                if (response.success) {
                    closeAddPlatformModal();
                    loadPlatforms();
                    alert(response.message);
                    document.dispatchEvent(new Event('platformAdded'));
                } else {
                    alert('Hata: ' + response.message);
                }
            },
            error: function(xhr) {
                console.error('Platform ekleme AJAX hatası:', xhr.responseText);
                alert('Bir hata oluştu: ' + xhr.responseText);
            }
        });
    });

    document.addEventListener('customerAdded', function() {
        loadCustomers();
    });
    document.addEventListener('platformAdded', function() {
        loadPlatforms();
    });
});

function openAddCustomerModal() {
    document.getElementById('addCustomerModal').classList.remove('hidden');
}

function closeAddCustomerModal() {
    document.getElementById('addCustomerModal').classList.add('hidden');
    $('#addCustomerForm')[0].reset();
}

function openAddPlatformModal() {
    document.getElementById('addPlatformModal').classList.remove('hidden');
}

function closeAddPlatformModal() {
    document.getElementById('addPlatformModal').classList.add('hidden');
    $('#addPlatformForm')[0].reset();
}

document.addEventListener('keydown', function(event) {
    if (event.key === 'Escape') {
        closeAddCustomerModal();
        closeAddPlatformModal();
    }
});

document.getElementById('addCustomerModal').addEventListener('click', function(event) {
    if (event.target === this) {
        closeAddCustomerModal();
    }
});

document.getElementById('addPlatformModal').addEventListener('click', function(event) {
    if (event.target === this) {
        closeAddPlatformModal();
    }
});
</script>
</body>
</html>
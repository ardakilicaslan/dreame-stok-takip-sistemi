<?php
include 'config.php';
include 'functions.php';

if (!isLoggedIn()) {
    redirect('login.php');
}

$notification = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add_stock'])) {
    header('Content-Type: application/json');
    if (!verifyCsrfToken($_POST['csrf_token'])) {
        echo json_encode(['success' => false, 'message' => 'Güvenlik hatası!']);
        exit;
    }

    try {
        $barcode = trim($_POST['barcode']);
        $serial_numbers = trim($_POST['serial_numbers']);
        $serial_count = 0;

        if (empty($barcode)) {
            throw new Exception('Barkod zorunludur!');
        }

        $stmt = $conn->prepare("SELECT COUNT(*) FROM models WHERE barcode = ?");
        $stmt->execute([$barcode]);
        if ($stmt->fetchColumn() == 0) {
            throw new Exception('Bu barkod sistemde kayıtlı değil!');
        }

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
                    VALUES (?, ?, 0, 1, NOW())
                ");
                $stmt->execute([$serial, $barcode]);
            }
        }

        echo json_encode(['success' => true, 'message' => $serial_count . ' adet seri numarası başarıyla eklendi!']);
        exit;
    } catch (Exception $e) {
        echo json_encode(['success' => false, 'message' => 'Hata: ' . $e->getMessage()]);
        exit;
    }
}

try {
    $stmt = $conn->query("
        SELECT m.barcode, m.name AS model_name, b.name AS brand_name, c.name AS category_name,
               (SELECT COUNT(*) FROM serial_numbers sn WHERE sn.barcode = m.barcode AND sn.sold = 0) AS stock_quantity
        FROM models m
        LEFT JOIN brands b ON m.brand_id = b.id
        LEFT JOIN categories c ON m.category_id = c.id
        GROUP BY m.barcode
    ");
    $stocks = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    die("Hata: " . $e->getMessage());
}
?>

<!DOCTYPE html>
<html lang="tr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Stok Durumu</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://cdn.datatables.net/1.13.4/css/jquery.dataTables.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/remixicon@4.3.0/fonts/remixicon.css" rel="stylesheet">
    <!-- Merkezi stiller header bileşeninden gelecek -->
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="https://cdn.datatables.net/1.13.4/js/jquery.dataTables.min.js"></script>
    <style>
        /* Sayfaya özel stiller burada kalabilir */
        .dataTable td {
            max-width: 500px;
            word-break: break-word;
            overflow: hidden;
            text-overflow: ellipsis;
        }
        .dataTables_wrapper .dataTables_length,
        .dataTables_wrapper .dataTables_filter,
        .dataTables_wrapper .dataTables_info,
        .dataTables_wrapper .dataTables_paginate {
            font-size: 0.8rem;
            padding: 5px;
        }
        .dataTables_wrapper .dataTables_filter input {
            padding: 2px 5px;
            font-size: 0.8rem;
        }
        .dataTables_wrapper .dataTables_paginate .paginate_button {
            padding: 2px 6px;
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
        body {
            font-family: 'Be Vietnam Pro', sans-serif;
        }
    </style>
</head>
<body class="bg-gray-100 flex">
    <?php include 'views/components/sidebar.php'; ?>
    <div class="flex-1 flex flex-col">
        <?php include 'views/components/header.php'; ?>
        <main class="flex-1 p-6 content">
            <h1 class="text-3xl font-bold mb-6 text-gray-800 flex items-center">
                <i class="ri-archive-line mr-2 text-blue-600"></i> Stok Durumu
            </h1>
            <?php echo $notification; ?>
            <div class="bg-white shadow-lg rounded-lg overflow-hidden">
                <table id="stockTable" class="min-w-full divide-y divide-gray-200">
                    <thead class="bg-gray-50">
                        <tr>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Barkod</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Marka</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Model Adı</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Kategori</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Stok Miktarı</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">İşlemler</th>
                        </tr>
                    </thead>
                    <tbody class="bg-white divide-y divide-gray-200">
                        <?php foreach ($stocks as $stock): ?>
                            <tr class="hover:bg-gray-50">
                                <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900"><?php echo sanitizeInput($stock['barcode']); ?></td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900"><?php echo sanitizeInput($stock['brand_name']); ?></td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900"><?php echo sanitizeInput($stock['model_name']); ?></td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500"><?php echo sanitizeInput($stock['category_name']); ?></td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500"><?php echo $stock['stock_quantity']; ?></td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                    <button class="add-stock-btn text-blue-600 hover:underline" data-barcode="<?php echo sanitizeInput($stock['barcode']); ?>">
                                        <i class="ri-add-box-line mr-1"></i> Stok Ekle
                                    </button>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>

        <!-- Stok Ekle Modal -->
        <div id="addStockModal" class="fixed inset-0 bg-gray-600 bg-opacity-50 flex items-center justify-center hidden modal">
            <div class="bg-white p-6 rounded-lg shadow-lg w-full max-w-md">
                <h2 class="text-xl font-bold mb-4 flex items-center">
                    <i class="ri-barcode-box-line mr-2 text-blue-600"></i> Stok Ekle
                </h2>
                <form method="POST" id="addStockForm">
                    <input type="hidden" name="csrf_token" value="<?php echo generateCsrfToken(); ?>">
                    <input type="hidden" name="add_stock" value="1">
                    <div class="mb-4">
                        <label for="barcode" class="block text-sm font-medium text-gray-700 flex items-center">
                            <i class="ri-barcode-line mr-2 text-blue-600"></i> Barkod
                        </label>
                        <input type="text" id="barcode" name="barcode" class="mt-1 block w-full p-2 border border-gray-300 rounded-md" readonly>
                    </div>
                    <div class="mb-4">
                        <label for="serial_numbers" class="block text-sm font-medium text-gray-700 flex items-center">
                            <i class="ri-barcode-box-line mr-2 text-blue-600"></i> Seri Numaraları (Her satıra bir seri numarası)
                        </label>
                        <textarea id="serial_numbers" name="serial_numbers" rows="5" class="mt-1 block w-full p-2 border border-gray-300 rounded-md" placeholder="Barkod okuyucuyla tarayın veya elle girin"></textarea>
                        <p id="serialCount" class="text-green-600 mt-2">0 adet seri numarası girildi</p>
                    </div>
                    <div class="flex justify-end">
                        <button type="button" id="closeStockModalBtn" class="bg-gray-500 text-white px-4 py-2 rounded-md hover:bg-gray-600 mr-2">
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

        const stockTable = $('#stockTable').DataTable({
            "language": {
                "url": "//cdn.datatables.net/plug-ins/1.13.4/i18n/tr.json"
            },
            "columnDefs": [
                { "width": "15%", "targets": 0 },
                { "width": "15%", "targets": 1 },
                { "width": "25%", "targets": 2 },
                { "width": "15%", "targets": 3 },
                { "width": "10%", "targets": 4 },
                { "width": "20%", "targets": 5 }
            ]
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

        $(document).on('click', '.add-stock-btn', function () {
            const barcode = $(this).data('barcode');
            console.log('Stok Ekle butonuna tıklandı, barcode:', barcode);
            $('#barcode').val(barcode);
            $('#serial_numbers').val('');
            updateSerialCount();
            $('#addStockModal').removeClass('hidden');
        });

        $('#closeStockModalBtn').on('click', function () {
            console.log('Stok modalı kapatıldı');
            $('#addStockModal').addClass('hidden');
            $('#addStockForm')[0].reset();
            updateSerialCount();
        });

        $('#addStockModal').on('click', function (event) {
            if (event.target === this) {
                console.log('Stok modalı arka plana tıklanarak kapatıldı');
                $('#addStockModal').addClass('hidden');
                $('#addStockForm')[0].reset();
                updateSerialCount();
            }
        });

$('#addStockForm').on('submit', function (e) {
            e.preventDefault();
            console.log('Stok ekleme formu gönderildi');
            const formData = $(this).serialize();
            console.log('Gönderilen veri:', formData);

            $.ajax({
                url: 'stock.php',
                type: 'POST',
                data: formData,
                dataType: 'json',
                success: function (response) {
                    console.log('Stok ekleme yanıtı:', response);
                    if (response.success) {
                        $('#addStockModal').addClass('hidden');
                        $('#addStockForm')[0].reset();
                        updateSerialCount();
                        location.reload();
                        alert(response.message);
                    } else {
                        alert('Hata: ' + response.message);
                    }
                },
                error: function (xhr, status, error) {
                    console.error('Stok ekleme AJAX hatası:', status, error, xhr.responseText);
                    alert('Bir hata oluştu: ' + (xhr.responseText || 'Bilinmeyen hata'));
                }
            });
        });
    });
    </script>
</body>
</html>
?>
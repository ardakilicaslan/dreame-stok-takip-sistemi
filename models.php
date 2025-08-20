<?php
session_start();
include 'config.php';
include 'functions.php';

if (!isLoggedIn()) {
    redirect('login.php');
}

$notification = '';
$filter = isset($_GET['filter']) && $_GET['filter'] === 'no_stock' ? 'no_stock' : 'all';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['delete_models'])) {
    if (!verifyCsrfToken($_POST['csrf_token'])) {
        $notification = '<div class="bg-red-500 text-white p-4 rounded mb-4">Güvenlik hatası!</div>';
    } else {
        try {
            $barcodes = isset($_POST['barcodes']) ? $_POST['barcodes'] : [];
            if (empty($barcodes)) {
                throw new Exception('Silmek için model seçmediniz!');
            }

            $stocked = [];
            $sold = [];
            foreach ($barcodes as $barcode) {
                $stmt = $conn->prepare("SELECT COUNT(*) AS stock_count FROM serial_numbers WHERE barcode = ? AND sold = 0");
                $stmt->execute([$barcode]);
                $stock_count = $stmt->fetchColumn();
                if ($stock_count > 0) {
                    $stocked[] = $barcode . " ($stock_count adet)";
                }

                $stmt = $conn->prepare("
                    SELECT COUNT(*) AS sales_count 
                    FROM serial_numbers sn 
                    INNER JOIN sales s ON sn.serial_number = s.serial_number 
                    WHERE sn.barcode = ?
                ");
                $stmt->execute([$barcode]);
                $sales_count = $stmt->fetchColumn();
                if ($sales_count > 0) {
                    $sold[] = $barcode . " ($sales_count satış)";
                }
            }

            if (!isset($_POST['confirmed']) && (!empty($stocked) || !empty($sold))) {
                $message = '';
                if (!empty($stocked)) {
                    $message .= 'Stokta ürün var: ' . implode(', ', $stocked) . '. ';
                }
                if (!empty($sold)) {
                    $message .= 'Satış kaydı var: ' . implode(', ', $sold) . '. ';
                }
                $message .= 'Yine de silmek istiyor musunuz?';
                $_SESSION['confirm_message'] = $message;
                $_SESSION['confirm_barcodes'] = $barcodes;
            } else {
                foreach ($barcodes as $barcode) {
                    $stmt = $conn->prepare("SELECT image FROM models WHERE barcode = ?");
                    $stmt->execute([$barcode]);
                    $image = $stmt->fetchColumn();
                    if ($image && file_exists('img/uploads/' . $image)) {
                        unlink('img/uploads/' . $image);
                    }

                    $stmt = $conn->prepare("
                        DELETE FROM sales 
                        WHERE serial_number IN (
                            SELECT serial_number 
                            FROM serial_numbers 
                            WHERE barcode = ?
                        )
                    ");
                    $stmt->execute([$barcode]);

                    $stmt = $conn->prepare("DELETE FROM serial_numbers WHERE barcode = ?");
                    $stmt->execute([$barcode]);

                    $stmt = $conn->prepare("DELETE FROM models WHERE barcode = ?");
                    $stmt->execute([$barcode]);
                }

                unset($_SESSION['confirm_message']);
                unset($_SESSION['confirm_barcodes']);
                $notification = '<div class="bg-green-500 text-white p-4 rounded mb-4">' . count($barcodes) . ' model başarıyla silindi!</div>';
            }
        } catch (Exception $e) {
            $notification = '<div class="bg-red-500 text-white p-4 rounded mb-4">Hata: ' . $e->getMessage() . '</div>';
        }
    }
}

try {
    $query = "
        SELECT m.barcode, m.name, m.purchase_price, m.sale_price, b.name AS brand_name, c.name AS category_name,
               (SELECT COUNT(*) FROM serial_numbers sn WHERE sn.barcode = m.barcode AND sn.sold = 0) AS stock_quantity
        FROM models m
        LEFT JOIN brands b ON m.brand_id = b.id
        LEFT JOIN categories c ON m.category_id = c.id
    ";
    if ($filter === 'no_stock') {
        $query .= " WHERE (SELECT COUNT(*) FROM serial_numbers sn WHERE sn.barcode = m.barcode AND sn.sold = 0) = 0";
    }
    $query .= " ORDER BY m.name";

    $stmt = $conn->prepare($query);
    $stmt->execute();
    $models = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    die("Hata: " . $e->getMessage());
}
?>

<!DOCTYPE html>
<html lang="tr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo Config::get('APP_NAME'); ?> - Modeller</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://cdn.jsdelivr.net/npm/remixicon@4.3.0/fonts/remixicon.css" rel="stylesheet">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Be+Vietnam+Pro:wght@400;500;600;700&display=swap" rel="stylesheet">
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="https://cdn.datatables.net/1.13.4/js/jquery.dataTables.min.js"></script>
    <link href="https://cdn.datatables.net/1.13.4/css/jquery.dataTables.min.css" rel="stylesheet">
    <style>
        body {
            font-family: 'Be Vietnam Pro', sans-serif;
        }
    </style>
</head>
<body class="bg-gray-100 flex">
    <?php include __DIR__ . '/views/components/sidebar.php'; ?>
    <div class="flex-1 flex flex-col">
        <?php include __DIR__ . '/views/components/header.php'; ?>
        <main class="flex-1 p-6 content">
            <h1 class="text-3xl font-bold mb-6 text-gray-800 flex items-center">
                <i class="ri-box-3-line mr-2 text-blue-600"></i> Modeller
            </h1>
            <?php echo $notification; ?>
            <div class="mb-4 flex justify-between items-center">
                <div>
                    <a href="models.php?filter=all" class="px-4 py-2 rounded-md <?php echo $filter === 'all' ? 'bg-blue-600 text-white' : 'bg-gray-200'; ?>">Tüm Modeller</a>
                    <a href="models.php?filter=no_stock" class="px-4 py-2 rounded-md <?php echo $filter === 'no_stock' ? 'bg-blue-600 text-white' : 'bg-gray-200'; ?>">Stokta Olmayanlar</a>
                </div>
            </div>
            <form method="POST" id="deleteForm">
                <input type="hidden" name="csrf_token" value="<?php echo generateCsrfToken(); ?>">
                <input type="hidden" name="confirmed" id="confirmed" value="0">
                <div class="mb-4">
                    <button type="submit" name="delete_models" class="bg-red-600 text-white px-4 py-2 rounded-md hover:bg-red-700 disabled:bg-gray-400" id="deleteButton" disabled>
                        <i class="ri-delete-bin-line mr-1"></i> Seçilenleri Sil
                    </button>
                </div>
                <div class="bg-white shadow-lg rounded-lg overflow-hidden">
                    <table id="modelsTable" class="min-w-full divide-y divide-gray-200">
                        <thead class="bg-gray-50">
                            <tr>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                    <input type="checkbox" id="selectAll" onchange="toggleSelectAll()">
                                </th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Marka</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Model</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Barkod</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Kategori</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Alış Fiyatı</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Satış Fiyatı</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Stok</th>
                            </tr>
                        </thead>
                        <tbody class="bg-white divide-y divide-gray-200">
                            <?php foreach ($models as $model): ?>
                                <tr class="<?php echo $model['stock_quantity'] == 0 ? 'bg-red-100' : ''; ?>">
                                    <td class="px-6 py-4 whitespace-nowrap">
                                        <input type="checkbox" name="barcodes[]" value="<?php echo htmlspecialchars($model['barcode']); ?>" class="modelCheckbox" onchange="toggleDeleteButton()">
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500"><?php echo sanitizeInput($model['brand_name']); ?></td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900">
                                        <a href="model_details.php?model_name=<?php echo urlencode($model['name']); ?>" class="text-blue-600 hover:underline"><?php echo sanitizeInput($model['name']); ?></a>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500"><?php echo sanitizeInput($model['barcode']); ?></td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500"><?php echo sanitizeInput($model['category_name']); ?></td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500"><?php echo number_format($model['purchase_price'], 2); ?> TL</td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500"><?php echo number_format($model['sale_price'], 2); ?> TL</td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500"><?php echo $model['stock_quantity']; ?></td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </form>

            <!-- Onay Modalı -->
            <div id="confirmModal" class="fixed inset-0 bg-gray-600 bg-opacity-50 flex items-center justify-center hidden" style="z-index: 1000;">
                <div class="bg-white p-6 rounded-lg shadow-lg w-full max-w-md">
                    <h2 class="text-xl font-bold mb-4 flex items-center">
                        <i class="ri-error-warning-line mr-2 text-yellow-600"></i> Silme Onayı
                    </h2>
                    <p id="confirmMessage" class="mb-4 text-gray-700"></p>
                    <div class="flex justify-end space-x-2">
                        <button onclick="cancelDelete()" class="bg-gray-500 text-white px-4 py-2 rounded-md hover:bg-gray-600">Hayır</button>
                        <button onclick="confirmDelete()" class="bg-red-600 text-white px-4 py-2 rounded-md hover:bg-red-700">Evet</button>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script>
        $(document).ready(function () {
            $('#modelsTable').DataTable({
                "language": {
                    "url": "//cdn.datatables.net/plug-ins/1.13.4/i18n/tr.json"
                },
                "columnDefs": [
                    { "orderable": false, "targets": 0 }
                ]
            });

            const confirmMessage = '<?php echo isset($_SESSION['confirm_message']) ? addslashes($_SESSION['confirm_message']) : ''; ?>';
            if (confirmMessage) {
                document.getElementById('confirmMessage').innerText = confirmMessage;
                document.getElementById('confirmModal').classList.remove('hidden');
            }
        });

        function toggleSelectAll() {
            const selectAll = document.getElementById('selectAll');
            const checkboxes = document.querySelectorAll('.modelCheckbox');
            checkboxes.forEach(checkbox => checkbox.checked = selectAll.checked);
            toggleDeleteButton();
        }

        function toggleDeleteButton() {
            const checkboxes = document.querySelectorAll('.modelCheckbox:checked');
            const deleteButton = document.getElementById('deleteButton');
            deleteButton.disabled = checkboxes.length === 0;
            deleteButton.innerHTML = `<i class="ri-delete-bin-line mr-1"></i> Seçilenleri Sil (${checkboxes.length})`;
        }

        function confirmDelete() {
            document.getElementById('confirmed').value = '1';
            document.getElementById('deleteForm').submit();
        }

        function cancelDelete() {
            document.getElementById('confirmModal').classList.add('hidden');
            $.post('clear_session.php', { clear_confirm: true }, function(response) {
                if (response !== 'success') {
                    console.error('Session temizlenemedi');
                }
            });
        }
    </script>
</body>
</html>
?>
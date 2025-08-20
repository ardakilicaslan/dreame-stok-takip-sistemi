<?php

include 'config.php';

include 'functions.php';

echo "Bu dosya 19 Nisan 2025'te güncellendi! Eğer bu mesajı görüyorsanız, doğru dosyadasınız.<br>";

if (!isLoggedIn()) {

    redirect('login.php');

}

setlocale(LC_TIME, 'tr_TR'); 

try {

    

    $salesStmt = $conn->query("

        SELECT s.id, m.name AS product_name, c.first_name, c.last_name, s.quantity, s.sale_date, p.name AS platform_name, p.image AS platform_image

        FROM sales s

        LEFT JOIN serial_numbers sn ON s.serial_number = sn.serial_number

        LEFT JOIN models m ON sn.barcode = m.barcode

        LEFT JOIN customers c ON s.customer_id = c.id

        LEFT JOIN platforms p ON s.platform_id = p.id

        ORDER BY s.created_at DESC

    ");

    $sales = $salesStmt->fetchAll(PDO::FETCH_ASSOC);

    

    if (empty($sales)) {

        error_log("Detaylı satış kayıtları sorgusu boş döndü. SQL: " . $salesStmt->queryString);

    } else {

        error_log("Detaylı satış kayıtları sorgusu " . count($sales) . " kayıt döndürdü.");

    }

    

    $summaryStmt = $conn->query("

        SELECT m.name AS product_name, p.name AS platform_name, SUM(s.quantity) AS total_sold

        FROM sales s

        LEFT JOIN serial_numbers sn ON s.serial_number = sn.serial_number

        LEFT JOIN models m ON sn.barcode = m.barcode

        LEFT JOIN platforms p ON s.platform_id = p.id

        GROUP BY m.barcode, p.id

        ORDER BY m.name, p.name

    ");

    $summary = $summaryStmt->fetchAll(PDO::FETCH_ASSOC);

    

    if (empty($summary)) {

        error_log("Özet tablosu sorgusu boş döndü. SQL: " . $summaryStmt->queryString);

    } else {

        error_log("Özet tablosu sorgusu " . count($summary) . " kayıt döndürdü.");

    }

} catch (PDOException $e) {

    error_log("Veritabanı hatası: " . $e->getMessage());

    die("Hata: " . $e->getMessage());

}

?>

<!DOCTYPE html>

<html lang="tr">

<head>

    <meta charset="UTF-8">

    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>Satış Raporları</title>

    <script src="https://cdn.tailwindcss.com"></script>

    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">

    <style>

        .platform-logo {

            width: 24px;

            height: 24px;

            margin-right: 0.5rem;

            border-radius: 4px;

            object-fit: contain;

        }

    </style>

</head>

<body class="bg-gray-100 flex">

    <?php include 'sidebar.php'; ?>

    <div class="flex-1">

        <?php include 'header.php'; ?>

        <div class="p-8">

            <h1 class="text-3xl font-bold mb-6 text-gray-800">Satış Raporları</h1>

            <div class="bg-white shadow-lg rounded-lg p-6 mb-6">

                <h2 class="text-xl font-semibold mb-4">Özet</h2>

                <table class="min-w-full divide-y divide-gray-200">

                    <thead class="bg-gray-50">

                        <tr>

                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Ürün Adı</th>

                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Platform</th>

                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Toplam Satılan</th>

                        </tr>

                    </thead>

                    <tbody class="bg-white divide-y divide-gray-200">

                        <?php if (empty($summary)): ?>

                            <tr>

                                <td colspan="3" class="px-6 py-4 whitespace-nowrap text-sm text-gray-500 text-center">Satış kaydı bulunamadı.</td>

                            </tr>

                        <?php else: ?>

                            <?php foreach ($summary as $item): ?>

                                <tr class="hover:bg-gray-50">

                                    <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900"><?php echo sanitizeInput($item['product_name'] ?? 'Bilinmeyen Ürün'); ?></td>

                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500"><?php echo sanitizeInput($item['platform_name'] ?? 'Bilinmeyen Platform'); ?></td>

                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500"><?php echo $item['total_sold']; ?></td>

                                </tr>

                            <?php endforeach; ?>

                        <?php endif; ?>

                    </tbody>

                </table>

            </div>

            <div class="bg-white shadow-lg rounded-lg p-6">

                <h2 class="text-xl font-semibold mb-4">Detaylı Satış Kayıtları</h2>

                <table class="min-w-full divide-y divide-gray-200">

                    <thead class="bg-gray-50">

                        <tr>

                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Ürün Adı</th>

                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Müşteri</th>

                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Platform</th>

                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Miktar</th>

                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Satış Tarihi</th>

                        </tr>

                    </thead>

                    <tbody class="bg-white divide-y divide-gray-200">

                        <?php if (empty($sales)): ?>

                            <tr>

                                <td colspan="5" class="px-6 py-4 whitespace-nowrap text-sm text-gray-500 text-center">Satış kaydı bulunamadı.</td>

                            </tr>

                        <?php else: ?>

                            <?php foreach ($sales as $sale): ?>

                                <tr class="hover:bg-gray-50">

                                    <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900"><?php echo sanitizeInput($sale['product_name'] ?? 'Bilinmeyen Ürün'); ?></td>

                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500"><?php echo sanitizeInput(($sale['first_name'] ?? '') . ' ' . ($sale['last_name'] ?? '')); ?></td>

                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">

                                        <?php if ($sale['platform_image']): ?>

                                            <img src="/Envanter/img/platforms/<?php echo htmlspecialchars($sale['platform_image']); ?>" alt="<?php echo htmlspecialchars($sale['platform_name'] ?? 'Bilinmeyen Platform'); ?>" class="platform-logo" onerror="this.style.display='none';">

                                        <?php endif; ?>

                                        <?php echo sanitizeInput($sale['platform_name'] ?? 'Bilinmeyen Platform'); ?>

                                    </td>

                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500"><?php echo $sale['quantity']; ?></td>

                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500"><?php echo strftime('%d %B %Y', strtotime($sale['sale_date'])); ?></td>

                                </tr>

                            <?php endforeach; ?>

                        <?php endif; ?>

                    </tbody>

                </table>

            </div>

        </div>

    </div>

</body>

</html>
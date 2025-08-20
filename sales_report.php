<?php
date_default_timezone_set('Europe/Istanbul');

include 'config.php';
include 'functions.php';

if (!isLoggedIn()) {
    redirect('login.php');
}

function format_turkish_date($date_string) {
    $months = [
        'January'   => 'Ocak',
        'February'  => 'Şubat',
        'March'     => 'Mart',
        'April'     => 'Nisan',
        'May'       => 'Mayıs',
        'June'      => 'Haziran',
        'July'      => 'Temmuz',
        'August'    => 'Ağustos',
        'September' => 'Eylül',
        'October'   => 'Ekim',
        'November'  => 'Kasım',
        'December'  => 'Aralık'
    ];
    $english_date = date('d F Y', strtotime($date_string));
    return str_replace(array_keys($months), array_values($months), $english_date);
}

switch ($filter) {
    case '24hours':
        $startDate = date('Y-m-d H:i:s', strtotime('-24 hours'));
        $whereClause = "WHERE s.sale_date >= '$startDate'";
        break;
    case '1week':
        $startDate = date('Y-m-d H:i:s', strtotime('-1 week'));
        $whereClause = "WHERE s.sale_date >= '$startDate'";
        break;
    case '1month':
        $startDate = date('Y-m-d H:i:s', strtotime('-1 month'));
        $whereClause = "WHERE s.sale_date >= '$startDate'";
        break;
    case '1year':
        $startDate = date('Y-m-d H:i:s', strtotime('-1 year'));
        $whereClause = "WHERE s.sale_date >= '$startDate'";
        break;
    case 'all':
    default:
        $whereClause = '';
        break;
}

try {
    $salesQuery = "
        SELECT s.id, s.sale_date, 
               c.name AS customer_name, 
               m.name AS model_name, 
               s.serial_number,
               p.name AS platform_name, 
               p.image_path AS platform_image
        FROM sales s
        JOIN customers c ON s.customer_id = c.id
        JOIN serial_numbers sn ON s.serial_number = sn.serial_number
        JOIN models m ON sn.barcode = m.barcode
        JOIN platforms p ON s.platform_id = p.id
        $whereClause
        ORDER BY s.sale_date DESC
    ";
    $stmt = $conn->query($salesQuery);
    $sales = $stmt->fetchAll(PDO::FETCH_ASSOC);

    $summaryQuery = "
        SELECT p.name AS platform_name, p.image_path AS platform_image, COUNT(s.id) AS total_sold
        FROM sales s
        JOIN serial_numbers sn ON s.serial_number = sn.serial_number
        JOIN models m ON sn.barcode = m.barcode
        JOIN platforms p ON s.platform_id = p.id
        $whereClause
        GROUP BY p.id
        ORDER BY p.name
    ";
    $summaryStmt = $conn->query($summaryQuery);
    $summary = $summaryStmt->fetchAll(PDO::FETCH_ASSOC);

    $platform_names = [];
    $platform_sales = [];
    foreach ($summary as $item) {
        $platform_names[] = $item['platform_name'];
        $platform_sales[] = (int)$item['total_sold'];
    }

    if (isset($_POST['download_csv'])) {
        header('Content-Type: text/csv; charset=utf-8');
        header('Content-Disposition: attachment; filename="satis_raporlari_' . date('Ymd_His') . '.csv"');

        $output = fopen('php://output', 'w');

        fprintf($output, chr(0xEF).chr(0xBB).chr(0xBF));

        fputcsv($output, [
            'Satış Tarihi',
            'Müşteri',
            'Ürün',
            'Platform',
            'Seri Numarası'
        ], ';');

        foreach ($sales as $sale) {
            fputcsv($output, [
                format_turkish_date($sale['sale_date']),
                $sale['customer_name'],
                $sale['model_name'],
                $sale['platform_name'] ?? 'Bilinmeyen Platform',
                $sale['serial_number']
            ], ';');
        }

        fclose($output);
        exit();
    }
} catch (PDOException $e) {
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
    <link href="https://cdn.jsdelivr.net/npm/remixicon@4.3.0/fonts/remixicon.css" rel="stylesheet">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Be+Vietnam+Pro:wght@400;500;600;700&display=swap" rel="stylesheet">
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <style>
        body {
            font-family: 'Be Vietnam Pro', sans-serif;
        }
        .platform-logo {
            width: 60px;
            height: 40px;
            margin-right: 0.5rem;
            border-radius: 4px;
            object-fit: contain;
        }
        .platform-cell {
            display: flex;
            align-items: center;
        }
        .tab {
            padding: 10px 20px;
            cursor: pointer;
            border-bottom: 2px solid transparent;
        }
        .tab.active {
            border-bottom: 2px solid #2563eb;
            color: #2563eb;
        }
        .tab-content {
            display: none;
        }
        .tab-content.active {
            display: block;
        }
        .chart-container {
            max-width: 300px;
            margin: 0 auto;
        }
    </style>
</head>
<body class="bg-gray-100 flex">
    <?php include 'views/components/sidebar.php'; ?>
    <div class="flex-1 flex flex-col">
        <?php include 'views/components/header.php'; ?>
        <main class="flex-1 p-6 content">
            <h1 class="text-3xl font-bold mb-6 text-gray-800 flex items-center">
                <i class="ri-file-chart-line mr-2 text-blue-600"></i> Satış Raporları
            </h1>
            <div class="bg-white shadow-lg rounded-lg overflow-hidden mb-6">
                <div class="flex justify-between items-center p-4 border-b">
                    <div class="flex">
                        <div class="tab active" onclick="showTab('text-tab')">Yazı</div>
                        <div class="tab" onclick="showTab('chart-tab')">Grafik</div>
                    </div>
                    <div>
                        <select id="filter" onchange="applyFilter()" class="border rounded px-2 py-1">
                            <option value="all" <?php echo $filter == 'all' ? 'selected' : ''; ?>>Tüm Zamanlar</option>
                            <option value="24hours" <?php echo $filter == '24hours' ? 'selected' : ''; ?>>Son 24 Saat</option>
                            <option value="1week" <?php echo $filter == '1week' ? 'selected' : ''; ?>>1 Hafta</option>
                            <option value="1month" <?php echo $filter == '1month' ? 'selected' : ''; ?>>1 Ay</option>
                            <option value="1year" <?php echo $filter == '1year' ? 'selected' : ''; ?>>1 Yıl</option>
                        </select>
                    </div>
                </div>
                <!-- Yazı Sekmesi -->
                <div id="text-tab" class="tab-content active">
                    <h2 class="text-xl font-semibold p-4">Özet</h2>
                    <table class="min-w-full divide-y divide-gray-200">
                        <thead class="bg-gray-50">
                            <tr>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Platform</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Toplam Satılan</th>
                            </tr>
                        </thead>
                        <tbody class="bg-white divide-y divide-gray-200">
                            <?php if (empty($summary)): ?>
                                <tr>
                                    <td colspan="2" class="px-6 py-4 whitespace-nowrap text-sm text-gray-500 text-center">Satış kaydı bulunamadı.</td>
                                </tr>
                            <?php else: ?>
                                <?php foreach ($summary as $item): ?>
                                    <tr class="hover:bg-gray-50">
                                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500 platform-cell">
                                            <?php if ($item['platform_image']): ?>
                                                <img src="/Envantera/img/platforms/<?php echo htmlspecialchars($item['platform_image']); ?>" alt="<?php echo htmlspecialchars($item['platform_name'] ?? 'Bilinmeyen Platform'); ?>" class="platform-logo" onerror="this.style.display='none';">
                                            <?php endif; ?>
                                            <?php echo sanitizeInput($item['platform_name'] ?? 'Bilinmeyen Platform'); ?>
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500"><?php echo $item['total_sold']; ?></td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
                <!-- Grafik -->
                <div id="chart-tab" class="tab-content p-4">
                    <h2 class="text-xl font-semibold mb-4">Platform Bazında Satışlar (Grafik)</h2>
                    <div class="chart-container">
                        <canvas id="salesChart" width="300" height="300"></canvas>
                    </div>
                </div>
            </div>
            <div class="bg-white shadow-lg rounded-lg overflow-hidden">
                <div class="flex justify-between items-center p-4">
                    <h2 class="text-xl font-semibold">Detaylı Satış Kayıtları</h2>
                    <form method="post">
                        <button type="submit" name="download_csv" class="bg-blue-600 text-white px-4 py-2 rounded hover:bg-blue-700">İndir</button>
                    </form>
                </div>
                <table class="min-w-full divide-y divide-gray-200">
                    <thead class="bg-gray-50">
                        <tr>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Satış Tarihi</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Müşteri</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Ürün</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Platform</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Seri Numarası</th>
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
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500"><?php echo format_turkish_date($sale['sale_date']); ?></td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500"><?php echo sanitizeInput($sale['customer_name']); ?></td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500"><?php echo sanitizeInput($sale['model_name']); ?></td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500 platform-cell">
                                        <?php if ($sale['platform_image']): ?>
                                            <img src="/Envantera/img/platforms/<?php echo htmlspecialchars($sale['platform_image']); ?>" alt="<?php echo htmlspecialchars($sale['platform_name'] ?? 'Bilinmeyen Platform'); ?>" class="platform-logo" onerror="this.style.display='none';">
                                        <?php endif; ?>
                                        <?php echo sanitizeInput($sale['platform_name'] ?? 'Bilinmeyen Platform'); ?>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500"><?php echo sanitizeInput($sale['serial_number']); ?></td>
                                </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </main>
    </div>

    <script>
        function showTab(tabId) {
            document.querySelectorAll('.tab-content').forEach(tab => {
                tab.classList.remove('active');
            });
            document.querySelectorAll('.tab').forEach(tab => {
                tab.classList.remove('active');
            });
            document.getElementById(tabId).classList.add('active');
            document.querySelector(`[onclick="showTab('${tabId}')"]`).classList.add('active');
        }

        function applyFilter() {
            const filter = document.getElementById('filter').value;
            window.location.href = 'sales_report.php?filter=' + filter;
        }

        document.addEventListener('DOMContentLoaded', function () {
            const ctx = document.getElementById('salesChart').getContext('2d');
            const platformNames = <?php echo json_encode($platform_names); ?>;
            const platformSales = <?php echo json_encode($platform_sales); ?>;

            new Chart(ctx, {
                type: 'pie',
                data: {
                    labels: platformNames,
                    datasets: [{
                        label: 'Satışlar',
                        data: platformSales,
                        backgroundColor: [
                            '#f87171', // Kırmızı
                            '#4ade80', // Yeşil
                            '#60a5fa', // Mavi
                            '#facc15', // Sarı
                            '#a78bfa', // Mor
                        ],
                        borderWidth: 1
                    }]
                },
                options: {
                    responsive: true,
                    plugins: {
                        legend: {
                            position: 'bottom',
                        },
                        tooltip: {
                            callbacks: {
                                label: function(context) {
                                    let label = context.label || '';
                                    if (label) {
                                        label += ': ';
                                    }
                                    let value = context.raw;
                                    let total = context.dataset.data.reduce((a, b) => a + b, 0);
                                    let percentage = ((value / total) * 100).toFixed(1) + '%';
                                    return label + percentage;
                                }
                            }
                        },
                        datalabels: {
                            formatter: (value, ctx) => {
                                let total = ctx.dataset.data.reduce((a, b) => a + b, 0);
                                let percentage = ((value / total) * 100).toFixed(1) + '%';
                                return percentage;
                            },
                            color: '#fff',
                            font: {
                                weight: 'bold'
                            }
                        }
                    }
                },
                plugins: [ChartDataLabels] // ChartDataLabels eklentisi için
            });
        });
    </script>
    <!-- ChartDataLabels eklentisi -->
    <script src="https://cdn.jsdelivr.net/npm/chartjs-plugin-datalabels@2.0.0/dist/chartjs-plugin-datalabels.min.js"></script>
</body>
</html>
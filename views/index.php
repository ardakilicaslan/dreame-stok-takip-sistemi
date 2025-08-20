<!DOCTYPE html>
<html lang="tr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo isset($title) ? sanitizeInput($title) : 'Genel Bakış'; ?></title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://cdn.jsdelivr.net/npm/remixicon@4.3.0/fonts/remixicon.css" rel="stylesheet">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Be+Vietnam+Pro:wght@400;500;600;700&display=swap" rel="stylesheet">
    <style>
        body {
            font-family: 'Be Vietnam Pro', sans-serif;
        }
    </style>
</head>
<body class="bg-gray-100 flex">
    <?php include __DIR__ . '/components/sidebar.php'; ?>
    <div class="flex-1 flex flex-col">
        <?php include __DIR__ . '/components/header.php'; ?>
        <main class="flex-1 p-6 content">
            <?php if (isset($_SESSION['success_message'])): ?>
                <div id="success-alert" class="bg-green-100 border-l-4 border-green-500 text-green-700 p-4 mb-4 rounded-md relative" role="alert">
                    <p class="font-bold">Başarılı!</p>
                    <p><?php echo $_SESSION['success_message']; ?></p>
                    <button onclick="document.getElementById('success-alert').style.display='none'" class="absolute top-0 bottom-0 right-0 px-4 py-3">
                        <span aria-hidden="true">&times;</span>
                    </button>
                </div>
                <?php unset($_SESSION['success_message']); ?>
            <?php endif; ?>

            <?php if (isset($_SESSION['error_message'])): ?>
                <div id="error-alert" class="bg-red-100 border-l-4 border-red-500 text-red-700 p-4 mb-4 rounded-md relative" role="alert">
                    <p class="font-bold">Hata!</p>
                    <p><?php echo $_SESSION['error_message']; ?></p>
                    <button onclick="document.getElementById('error-alert').style.display='none'" class="absolute top-0 bottom-0 right-0 px-4 py-3">
                        <span aria-hidden="true">&times;</span>
                    </button>
                </div>
                <?php unset($_SESSION['error_message']); ?>
            <?php endif; ?>
            <h1 class="text-3xl font-bold mb-6 text-gray-800 flex items-center">
                <i class="ri-dashboard-line mr-2 text-blue-600"></i> Genel Bakış
            </h1>
            <div class="grid grid-cols-1 md:grid-cols-3 gap-6 mb-6">
                <div class="bg-white p-6 rounded-lg shadow-lg flex items-center">
                    <i class="ri-box-3-line text-4xl text-blue-600 mr-4"></i>
                    <div>
                        <h2 class="text-xl font-semibold text-gray-800">Toplam Model</h2>
                        <p class="text-3xl font-bold text-blue-600"><?php echo $total_models; ?></p>
                    </div>
                </div>
                <div class="bg-white p-6 rounded-lg shadow-lg flex items-center">
                    <i class="ri-user-line text-4xl text-green-600 mr-4"></i>
                    <div>
                        <h2 class="text-xl font-semibold text-gray-800">Toplam Müşteri</h2>
                        <p class="text-3xl font-bold text-green-600"><?php echo $total_customers; ?></p>
                    </div>
                </div>
                <div class="bg-white p-6 rounded-lg shadow-lg flex items-center">
                    <i class="ri-archive-line text-4xl text-purple-600 mr-4"></i>
                    <div>
                        <h2 class="text-xl font-semibold text-gray-800">Toplam Ürün</h2>
                        <p class="text-3xl font-bold text-purple-600"><?php echo $total_products; ?></p>
                    </div>
                </div>
            </div>
            <div class="bg-white shadow-lg rounded-lg overflow-hidden">
                <table class="min-w-full divide-y divide-gray-200">
                    <thead class="bg-gray-50">
                        <tr>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Marka</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Model Adı</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Kategori</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Toplam Miktar</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">İşlemler</th>
                        </tr>
                    </thead>
                    <tbody class="bg-white divide-y divide-gray-200">
                        <?php foreach ($models as $model): ?>
                            <tr class="hover:bg-gray-50">
                                <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900"><?php echo sanitizeInput($model['brand_name']); ?></td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900"><?php echo sanitizeInput($model['name']); ?></td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500"><?php echo sanitizeInput($model['category_name']); ?></td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500"><?php echo $model['total_quantity']; ?></td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                    <a href="model_details.php?model_name=<?php echo urlencode($model['name']); ?>" class="text-blue-600 hover:underline"><i class="ri-eye-line mr-1"></i> Detaylar</a>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</body>
</html>

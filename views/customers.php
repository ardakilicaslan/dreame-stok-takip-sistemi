<!DOCTYPE html>
<html lang="tr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo Config::get('APP_NAME') . ' - ' . ($title ?? 'Müşteriler'); ?></title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://cdn.datatables.net/1.13.4/css/jquery.dataTables.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/remixicon@4.3.0/fonts/remixicon.css" rel="stylesheet">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Be+Vietnam+Pro:wght@400;500;600;700&display=swap" rel="stylesheet">
    <style>
        body {
            font-family: 'Be Vietnam Pro', sans-serif;
        }
    </style>
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="https://cdn.datatables.net/1.13.4/js/jquery.dataTables.min.js"></script>
</head>
<body class="bg-gray-100 flex">
    <?php include __DIR__ . '/components/sidebar.php'; ?>
    
    <div class="flex-1">
        <?php include __DIR__ . '/components/header.php'; ?>
        
        <div class="content">
            <h1 class="text-3xl font-bold mb-6 text-gray-800 flex items-center">
                <i class="ri-user-line mr-2 text-blue-600"></i> <?php echo $title ?? 'Müşteriler'; ?>
            </h1>
            
            <?php echo $notification ?? ''; ?>
            
            <div class="mb-4">
                <?php echo FormHelper::button(
                    'Müşteri Ekle',
                    'button',
                    [
                        'id' => 'addCustomerBtn',
                        'icon' => 'ri-user-add-line',
                        'class' => 'bg-blue-600 text-white px-4 py-2 rounded-md hover:bg-blue-700'
                    ]
                ); ?>
            </div>
            
            <div class="bg-white shadow-lg rounded-lg overflow-hidden">
                <?php echo FormHelper::dataTable('customersTable', [
                    'Ad Soyad',
                    'E-posta', 
                    'Telefon',
                    'İşlemler'
                ]); ?>
            </div>
        </div>
    </div>

    <!-- Add Customer Modal -->
    <?php echo FormHelper::modal('addCustomerModal', 'Müşteri Ekle', '
        <form id="addCustomerForm">
            ' . FormHelper::csrfToken() . '
            <div class="mb-4">
                ' . FormHelper::label('name', 'Ad Soyad', 'ri-user-line', true) . '
                ' . FormHelper::input('name', 'text', ['required' => true]) . '
            </div>
            <div class="mb-4">
                ' . FormHelper::label('email', 'E-posta', 'ri-mail-line') . '
                ' . FormHelper::input('email', 'email') . '
            </div>
            <div class="mb-4">
                ' . FormHelper::label('phone', 'Telefon', 'ri-phone-line') . '
                ' . FormHelper::input('phone', 'text') . '
            </div>
            <div class="flex justify-end">
                ' . FormHelper::button('İptal', 'button', [
                    'onclick' => 'closeAddCustomerModal()',
                    'class' => 'bg-gray-500 text-white px-4 py-2 rounded-md hover:bg-gray-600 mr-2',
                    'icon' => 'ri-close-line'
                ]) . '
                ' . FormHelper::button('Kaydet', 'submit', [
                    'class' => 'bg-blue-600 text-white px-4 py-2 rounded-md hover:bg-blue-700',
                    'icon' => 'ri-save-line'
                ]) . '
            </div>
        </form>
    ', ['icon' => 'ri-user-add-line']); ?>

    <!-- Products Modal -->
    <?php echo FormHelper::modal('productsModal', 'Satın Alınan Ürünler', '
        ' . FormHelper::dataTable('purchasedProductsTable', [
            'Ürün Adı',
            'Seri Numarası', 
            'Kategori',
            'İşlemler'
        ]) . '
        <div class="mt-4 flex justify-end">
            ' . FormHelper::button('Kapat', 'button', [
                'id' => 'closeProductsModalBtn',
                'class' => 'bg-gray-500 text-white px-4 py-2 rounded-md hover:bg-gray-600',
                'icon' => 'ri-close-line'
            ]) . '
        </div>
    ', ['size' => 'max-w-4xl', 'icon' => 'ri-shopping-cart-line']); ?>

    <!-- Edit Sale Modal -->
    <?php echo FormHelper::modal('editSaleModal', 'Satışı Güncelle', '
        <form method="POST">
            ' . FormHelper::csrfToken() . '
            <input type="hidden" id="sale_id" name="sale_id">
            <div class="mb-4">
                ' . FormHelper::label('customer_id', 'Müşteri') . '
                <select id="customer_id" name="customer_id" class="mt-1 block w-full p-2 border border-gray-300 rounded-md" required>
                    <!-- AJAX ile doldurulacak -->
                </select>
            </div>
            <div class="flex justify-end">
                ' . FormHelper::button('İptal', 'button', [
                    'id' => 'closeEditSaleModalBtn',
                    'class' => 'bg-gray-500 text-white px-4 py-2 rounded-md hover:bg-gray-600 mr-2',
                    'icon' => 'ri-close-line'
                ]) . '
                ' . FormHelper::button('Kaydet', 'submit', [
                    'name' => 'update_sale',
                    'class' => 'bg-blue-600 text-white px-4 py-2 rounded-md hover:bg-blue-700',
                    'icon' => 'ri-save-line'
                ]) . '
            </div>
        </form>
    ', ['icon' => 'ri-edit-line']); ?>

    <script>
    $(document).ready(function () {
        console.log('jQuery yüklendi, sayfa hazır');

        const customersTable = $('#customersTable').DataTable({
            "language": {
                "url": "//cdn.datatables.net/plug-ins/1.13.4/i18n/tr.json"
            },
            "ajax": {
                "url": "fetch_customers.php",
                "type": "GET",
                "dataSrc": ""
            },
            "columns": [
                {
                    "data": "name"
                },
                {
                    "data": "email",
                    "render": function(data) {
                        return data || '-';
                    }
                },
                {
                    "data": "phone",
                    "render": function(data) {
                        return data || '-';
                    }
                },
                {
                    "data": "id",
                    "render": function(data) {
                        return `<button class="show-products-btn text-blue-600 hover:underline mr-2" data-customer-id="${data}">
                                    <i class="ri-shopping-cart-line mr-1"></i> Ürünleri Göster
                                </button>`;
                    }
                }
            ],
            "columnDefs": [
                { "width": "40%", "targets": 0 },
                { "width": "40%", "targets": 1 },
                { "width": "10%", "targets": 2 },
                { "width": "10%", "targets": 3 }
            ]
        });

        $('#addCustomerBtn').on('click', function() {
            console.log('Müşteri Ekle butonuna tıklandı');
            openAddCustomerModal();
        });

        $(document).on('click', '.show-products-btn', function() {
            const customerId = $(this).data('customer-id');
            console.log('Ürünleri Göster tıklandı, customerId:', customerId);
            showPurchasedProducts(customerId);
        });

        $(document).on('click', '.edit-sale-btn', function() {
            const saleId = $(this).data('sale-id');
            const customerId = $(this).data('customer-id');
            openEditSaleModal(saleId, customerId);
        });

        $('#closeProductsModalBtn').on('click', function() {
            console.log('Kapat butonuna tıklandı');
            closeProductsModal();
        });

        $('#closeEditSaleModalBtn').on('click', function() {
            closeEditSaleModal();
        });

        $('#addCustomerForm').on('submit', function(e) {
            e.preventDefault();
            console.log('Form gönderildi!');
            const formData = $(this).serialize();
            console.log('Gönderilen veri:', formData);

            $.ajax({
                url: 'add_customer.php',
                type: 'POST',
                data: formData,
                dataType: 'json',
                success: function(response) {
                    console.log('Ham yanıt:', response);
                    console.log('Yanıt tipi:', typeof response);
                    if (response.success) {
                        closeAddCustomerModal();
                        customersTable.ajax.reload();
                        alert(response.message);
                        const event = new Event('customerAdded');
                        document.dispatchEvent(event);
                    } else {
                        alert(response.message);
                    }
                },
                error: function(xhr, status, error) {
                    console.error('AJAX hatası:', status, error);
                    console.log('Sunucu yanıtı:', xhr.responseText);
                    alert('Bir hata oluştu: ' + xhr.responseText);
                }
            });
        });

                function loadCustomersForEditSale(selectedCustomerId) {
            $.ajax({
                url: 'fetch_customers.php',
                type: 'GET',
                dataType: 'json',
                success: function(customers) {
                    const select = $('#customer_id');
                    select.empty();
                    customers.forEach(customer => {
                                                const selected = customer.id == selectedCustomerId ? ' selected' : '';
                        select.append(`<option value="${customer.id}"${selected}>${customer.name}</option>`);
                    });
                }
            });
        }

        let purchasedProductsTable;
        let currentCustomerId;

        function showPurchasedProducts(customerId) {
            console.log('showPurchasedProducts çağrıldı, customerId:', customerId);
            currentCustomerId = customerId;
            document.getElementById('productsModal').classList.remove('hidden');

            if (purchasedProductsTable) {
                console.log('Mevcut tablo güncelleniyor, customerId:', customerId);
                purchasedProductsTable.clear().draw();
                purchasedProductsTable.ajax.reload();
            } else {
                console.log('Yeni DataTable başlatılıyor, customerId:', customerId);
                purchasedProductsTable = $('#purchasedProductsTable').DataTable({
                    "language": {
                        "url": "//cdn.datatables.net/plug-ins/1.13.4/i18n/tr.json"
                    },
                    "ajax": {
                        "url": "fetch_purchased_products.php",
                        "type": "POST",
                        "data": function() {
                            console.log('AJAX isteği gönderiliyor, customer_id:', currentCustomerId);
                            return { customer_id: currentCustomerId };
                        },
                        "dataSrc": "",
                        "cache": false,
                        "error": function(xhr, status, error) {
                            console.error('fetch_purchased_products.php AJAX Hatası:', status, error, 'Yanıt:', xhr.responseText);
                            alert('Ürünler yüklenirken hata oluştu: ' + (xhr.responseText || 'Bilinmeyen hata'));
                        }
                    },
                    "columns": [
                        { 
                            "data": "product_name",
                            "render": function(data) {
                                return data || '-';
                            }
                        },
                        { 
                            "data": "serial_number",
                            "render": function(data) {
                                return data || '-';
                            }
                        },
                        { 
                            "data": "category",
                            "render": function(data) {
                                return data || '-';
                            }
                        },
                        {
                            "data": null,
                            "render": function(data, type, row) {
                                                                return `
                                    <button class="edit-sale-btn text-blue-600 hover:underline mr-2" data-sale-id="${row.sale_id}" data-customer-id="${row.customer_id}"><i class="ri-edit-line"></i> Düzenle</button>
                                    <a href="customers.php?action=delete_sale&sale_id=${row.sale_id}" class="text-red-600 hover:underline" onclick="return confirm('Bu satış kaydını silmek istediğinizden emin misiniz?');"><i class="ri-delete-bin-line"></i> Sil</a>
                                `;
                            }
                        }
                    ],
                    "columnDefs": [
                        { "width": "35%", "targets": 0 },
                        { "width": "25%", "targets": 1 },
                        { "width": "20%", "targets": 2 },
                        { "width": "20%", "targets": 3 }
                    ],
                    "initComplete": function(settings, json) {
                        console.log('purchasedProductsTable başlatıldı, veri:', json);
                        if (!json || json.length === 0) {
                            console.log('Uyarı: customer_id', currentCustomerId, 'için veri bulunamadı veya boş dizi döndü');
                        }
                    }
                });
            }
        }

        function openAddCustomerModal() {
            console.log('openAddCustomerModal çağrıldı');
            document.getElementById('addCustomerModal').classList.remove('hidden');
        }

        function closeAddCustomerModal() {
            console.log('closeAddCustomerModal çağrıldı');
            document.getElementById('addCustomerModal').classList.add('hidden');
            $('#addCustomerForm')[0].reset();
        }

                function closeProductsModal() {
            console.log('closeProductsModal çağrıldı');
            document.getElementById('productsModal').classList.add('hidden');
        }

        function openEditSaleModal(saleId, customerId) {
            console.log('openEditSaleModal çağrıldı, saleId:', saleId, 'customerId:', customerId);
            document.getElementById('sale_id').value = saleId;
            loadCustomersForEditSale(customerId);
            document.getElementById('editSaleModal').classList.remove('hidden');
        }

        function closeEditSaleModal() {
            console.log('closeEditSaleModal çağrıldı');
            document.getElementById('editSaleModal').classList.add('hidden');
        }

        document.addEventListener('keydown', function(event) {
            if (event.key === 'Escape') {
                closeAddCustomerModal();
                closeProductsModal();
                closeEditSaleModal();
            }
        });

        document.getElementById('addCustomerModal').addEventListener('click', function(event) {
            if (event.target === this) {
                closeAddCustomerModal();
            }
        });

        document.getElementById('productsModal').addEventListener('click', function(event) {
            if (event.target === this) {
                closeProductsModal();
            }
        });

        document.getElementById('editSaleModal').addEventListener('click', function(event) {
            if (event.target === this) {
                closeEditSaleModal();
            }
        });
    });
    </script>
</body>
</html>

<?php

?>

<nav class="w-64 bg-gray-800 text-white h-screen sidebar fixed top-0 left-0" id="sidebar">
    <div class="toggle-btn" id="toggle-btn" onclick="toggleSidebar()">
        <i class="ri-menu-line text-xl"></i>
    </div>
    
    <div class="flex justify-center mt-4 mb-4">
        <img src="<?php echo Config::get('APP_URL'); ?>/img/logo/logo.png" alt="<?php echo Config::get('APP_NAME'); ?>" class="logo w-32 h-auto">
    </div>
    
    <ul class="space-y-2">
        <li>
            <a href="index.php" class="block py-2 px-4 hover:bg-gray-700 flex items-center nav-item <?php echo basename($_SERVER['PHP_SELF']) === 'index.php' ? 'bg-gray-700' : ''; ?>">
                <i class="ri-dashboard-line mr-2"></i>
                <span class="nav-text">Genel Bakış</span>
            </a>
        </li>
        
        <li>
            <a href="stock.php" class="block py-2 px-4 hover:bg-gray-700 flex items-center nav-item <?php echo basename($_SERVER['PHP_SELF']) === 'stock.php' ? 'bg-gray-700' : ''; ?>">
                <i class="ri-archive-line mr-2"></i>
                <span class="nav-text">Stok Yönetimi</span>
            </a>
        </li>
        
        <li>
            <a href="models.php" class="block py-2 px-4 hover:bg-gray-700 flex items-center nav-item <?php echo basename($_SERVER['PHP_SELF']) === 'models.php' ? 'bg-gray-700' : ''; ?>">
                <i class="ri-box-3-line mr-2"></i>
                <span class="nav-text">Tüm Modeller</span>
            </a>
        </li>
        
        <li>
            <a href="add_products.php" class="block py-2 px-4 hover:bg-gray-700 flex items-center nav-item <?php echo basename($_SERVER['PHP_SELF']) === 'add_products.php' ? 'bg-gray-700' : ''; ?>">
                <i class="ri-add-box-line mr-2"></i>
                <span class="nav-text">Ürün Ekle</span>
            </a>
        </li>
        
        <li>
            <a href="sales.php" class="block py-2 px-4 hover:bg-gray-700 flex items-center nav-item <?php echo basename($_SERVER['PHP_SELF']) === 'sales.php' ? 'bg-gray-700' : ''; ?>">
                <i class="ri-shopping-cart-line mr-2"></i>
                <span class="nav-text">Satış Yap</span>
            </a>
        </li>
        
        <li>
            <a href="customers.php" class="block py-2 px-4 hover:bg-gray-700 flex items-center nav-item <?php echo basename($_SERVER['PHP_SELF']) === 'customers.php' ? 'bg-gray-700' : ''; ?>">
                <i class="ri-user-line mr-2"></i>
                <span class="nav-text">Müşteriler</span>
            </a>
        </li>
        
        <li>
            <a href="sales_report.php" class="block py-2 px-4 hover:bg-gray-700 flex items-center nav-item <?php echo basename($_SERVER['PHP_SELF']) === 'sales_report.php' ? 'bg-gray-700' : ''; ?>">
                <i class="ri-file-chart-line mr-2"></i>
                <span class="nav-text">Satış Raporları</span>
            </a>
        </li>
        
        <?php if (isAdmin()): ?>
        <li class="border-t border-gray-600 mt-4 pt-4">
            <a href="admin.php" class="block py-2 px-4 hover:bg-gray-700 flex items-center nav-item <?php echo basename($_SERVER['PHP_SELF']) === 'admin.php' ? 'bg-gray-700' : ''; ?>">
                <i class="ri-settings-line mr-2"></i>
                <span class="nav-text">Yönetim</span>
            </a>
        </li>
        <?php endif; ?>
        
        <li class="border-t border-gray-600 mt-4 pt-4">
            <a href="logout.php" class="block py-2 px-4 hover:bg-red-600 flex items-center nav-item">
                <i class="ri-logout-box-line mr-2"></i>
                <span class="nav-text">Çıkış Yap</span>
            </a>
        </li>
    </ul>
</nav>

<style>
    .sidebar {
        width: 250px;
        transition: width 0.3s ease;
        z-index: 1001;
    }
    .sidebar.collapsed {
        width: 60px;
    }
    .sidebar.collapsed .nav-text,
    .sidebar.collapsed .logo {
        display: none;
    }
    .sidebar.collapsed .nav-item {
        justify-content: center;
    }
    .toggle-btn {
        position: absolute;
        top: 15px;
        right: 15px; /* Butonu içeri alır */
        background-color: #374151; /* Rengi güncellendi */
        color: white;
        padding: 5px;
        border-radius: 5px;
        cursor: pointer;
        z-index: 1002;
        transition: background-color 0.2s;
    }
    .toggle-btn:hover {
        background-color: #4B5563;
    }
    .sidebar.collapsed .toggle-btn {
        right: 15px; /* Daraltıldığında da aynı konumda kalır */
    }
    .content {
        transition: margin-left 0.3s ease;
        margin-left: 250px;
        padding-top: 86px; /* increased space for header */
        padding-left: 1.5rem; /* 24px */
        padding-right: 1.5rem; /* 24px */
        padding-bottom: 1.5rem; /* 24px */
    }
    .content.collapsed {
        margin-left: 60px;
    }
    .header {
        transition: left 0.3s ease, width 0.3s ease;
        position: fixed;
        top: 0;
        left: 250px;
        width: calc(100% - 250px);
        z-index: 1000;
    }
    .header.collapsed {
        left: 60px;
        width: calc(100% - 60px);
    }
    .nav-item.bg-gray-700 {
        background-color: #374151;
        border-left: 4px solid #3B82F6;
    }
</style>

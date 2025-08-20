<?php

?>

<nav class="w-64 bg-gray-800 text-white h-screen sidebar" id="sidebar">

    <div class="toggle-btn" onclick="toggleSidebar()">

        <i class="ri-menu-line text-xl"></i>

    </div>

    <div class="flex justify-center mt-4 mb-4">

        <img src="/Envanter/img/logo/logo.png" alt="Dreame Logo" class="logo w-32 h-auto">

    </div>

    <ul class="space-y-2">

        <li>

            <a href="index.php" class="block py-2 px-4 hover:bg-gray-700 flex items-center nav-item">

                <i class="ri-dashboard-line mr-2"></i><span class="nav-text">Genel Bakış</span>

            </a>

        </li>

        <li>

            <a href="stock.php" class="block py-2 px-4 hover:bg-gray-700 flex items-center nav-item">

                <i class="ri-archive-line mr-2"></i><span class="nav-text">Stok Yönetimi</span>

            </a>

        </li>

        <li>

            <a href="models.php" class="block py-2 px-4 hover:bg-gray-700 flex items-center nav-item">

                <i class="ri-box-3-line mr-2"></i><span class="nav-text">Tüm Modeller</span>

            </a>

        </li>

        <li>

            <a href="add_products.php" class="block py-2 px-4 hover:bg-gray-700 flex items-center nav-item">

                <i class="ri-add-box-line mr-2"></i><span class="nav-text">Ürün Ekle</span>

            </a>

        </li>

        <li>

            <a href="sales.php" class="block py-2 px-4 hover:bg-gray-700 flex items-center nav-item">

                <i class="ri-shopping-cart-line mr-2"></i><span class="nav-text">Satış Yap</span>

            </a>

        </li>

        <li>

            <a href="customers.php" class="block py-2 px-4 hover:bg-gray-700 flex items-center nav-item">

                <i class="ri-user-line mr-2"></i><span class="nav-text">Müşteriler</span>

            </a>

        </li>

        <li>

            <a href="sales_report.php" class="block py-2 px-4 hover:bg-gray-700 flex items-center nav-item">

                <i class="ri-file-chart-line mr-2"></i><span class="nav-text">Satış Raporları</span>

            </a>

        </li>

        <li>

            <a href="logout.php" class="block py-2 px-4 hover:bg-gray-700 flex items-center nav-item">

                <i class="ri-logout-box-line mr-2"></i><span class="nav-text">Çıkış Yap</span>

            </a>

        </li>

    </ul>

</nav>

<style>

    body {

        display: flex;

        min-height: 100vh;

        margin: 0;

        overflow-x: hidden;

    }

    .sidebar {

        position: fixed;

        top: 0;

        left: 0;

        height: 100%;

        width: 250px;

        transition: width 0.3s ease;

        z-index: 40;

    }

    .sidebar.collapsed {

        width: 60px;

    }

    .sidebar.collapsed .nav-text {

        display: none;

    }

    .sidebar.collapsed .nav-item {

        justify-content: center;

    }

    .sidebar.collapsed .logo {

        width: 40px;

    }

    .toggle-btn {

        position: absolute;

        top: 10px;

        right: 10px;

        background-color: #374151;

        color: white;

        padding: 5px;

        border-radius: 5px;

        cursor: pointer;

    }

    .content {

        margin-left: 250px;

        padding: 2rem;

        width: calc(100% - 250px);

        transition: margin-left 0.3s ease, width 0.3s ease;

    }

    .content.collapsed {

        margin-left: 60px;

        width: calc(100% - 60px);

    }

</style>

<script>

    function toggleSidebar() {

        const sidebar = document.getElementById('sidebar');

        const content = document.querySelector('.content');

        const header = document.getElementById('header');

        sidebar.classList.toggle('collapsed');

        if (content) {

            content.classList.toggle('collapsed');

        }

        if (header) {

            header.classList.toggle('collapsed');

        }

    }

</script>
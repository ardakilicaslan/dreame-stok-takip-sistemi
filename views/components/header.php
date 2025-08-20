<?php

?>

<header id="header" class="header bg-white shadow-sm p-4 flex justify-between items-center fixed top-0 left-0 right-0 z-50">
    <!-- Sol Taraf: Sayfa Başlığı -->
    <div>
        <h2 class="text-lg font-semibold text-gray-800">
            Dreame Stok Takip Sistemi v.1.0
        </h2>
    </div>

    <!-- Sağ Taraf: Dil ve Kullanıcı Menüsü -->
    <div class="flex items-center space-x-2">
        <!-- Dil, Bildirim ve Kullanıcı Butonları -->
        <div class="flex items-center space-x-1">
            <!-- Language Selector -->
            <div class="relative" id="lang-dropdown-container">
                <button id="lang-menu-button" class="w-10 h-10 flex items-center justify-center text-gray-500 hover:text-gray-700 hover:bg-gray-100 rounded-full transition-colors">
                    <i class="ri-global-line text-xl"></i>
                </button>
                <div id="langMenu" class="absolute right-0 mt-2 w-32 bg-white rounded-lg shadow-lg border border-gray-200 hidden z-50">
                    <a href="?lang=tr" class="block px-4 py-2 text-sm text-gray-700 hover:bg-gray-100">Türkçe (TR)</a>
                    <a href="?lang=en" class="block px-4 py-2 text-sm text-gray-700 hover:bg-gray-100">English (EN)</a>
                </div>
            </div>

            <!-- Notifications -->
            <button class="w-10 h-10 flex items-center justify-center text-gray-500 hover:text-gray-700 hover:bg-gray-100 rounded-full transition-colors relative">
                <i class="ri-notification-line text-xl"></i>
                <span class="absolute top-1 right-1 bg-red-500 text-white text-xs rounded-full h-4 w-4 flex items-center justify-center hidden" id="notificationBadge">0</span>
            </button>

            <!-- Kullanıcı Menüsü -->
            <div class="relative" id="user-dropdown-container">
                <button id="user-menu-button" class="flex items-center space-x-2 p-1 hover:bg-gray-100 rounded-full transition-colors">
                    <div class="w-8 h-8 rounded-full bg-blue-500 flex items-center justify-center text-white font-bold">
                        <?php 
                        $username = $_SESSION['username'] ?? 'User';
                        echo strtoupper(substr($username, 0, 1));
                        ?>
                    </div>
                    <span class="font-medium hidden sm:block"><?php echo $username; ?></span>
                    <i class="ri-arrow-down-s-line hidden sm:block text-gray-500 pr-1"></i>
                </button>
            
            <!-- Dropdown Menüsü -->
            <div id="userMenu" class="absolute right-0 mt-2 w-48 bg-white rounded-lg shadow-lg border border-gray-200 hidden z-50">
                <div class="py-1">
                    <div class="px-4 py-2 text-sm text-gray-500 border-b border-gray-100">
                        <div class="font-medium"><?php echo $username; ?></div>
                        <div class="text-xs"><?php echo $_SESSION['role'] ?? 'User'; ?></div>
                    </div>
                    
                    
                    <?php if (isAdmin()): ?>
                    <a href="admin.php" class="block px-4 py-2 text-sm text-gray-700 hover:bg-gray-100">
                        <i class="ri-shield-user-line mr-2"></i>Yönetim Paneli
                    </a>
                    <?php endif; ?>
                    
                    <div class="border-t border-gray-100 my-1"></div>
                    
                    <a href="logout.php" class="block px-4 py-2 text-sm text-red-600 hover:bg-red-50">
                        <i class="ri-logout-box-line mr-2"></i>Çıkış Yap
                    </a>
                </div>
            </div>
        </div>
    </div>
</header>

<script>
    // Sidebar toggle functionality
    function toggleSidebar() {
        const sidebar = document.getElementById('sidebar');
        const content = document.querySelector('.content');
        const header = document.getElementById('header');
        
        if (sidebar) sidebar.classList.toggle('collapsed');
        if (content) content.classList.toggle('collapsed');
        if (header) header.classList.toggle('collapsed');
        
        if (sidebar) {
            localStorage.setItem('sidebarCollapsed', sidebar.classList.contains('collapsed'));
        }
    }

    document.addEventListener('DOMContentLoaded', function() {
        // Restore sidebar state on page load
        const isCollapsed = localStorage.getItem('sidebarCollapsed') === 'true';
        if (isCollapsed) {
            const sidebar = document.getElementById('sidebar');
            const content = document.querySelector('.content');
            const header = document.getElementById('header');

            if (sidebar) sidebar.classList.add('collapsed');
            if (content) content.classList.add('collapsed');
            if (header) header.classList.add('collapsed');
        }

        // Dropdown Menu Logic
        const userMenuButton = document.getElementById('user-menu-button');
        const userMenu = document.getElementById('userMenu');
        const langMenuButton = document.getElementById('lang-menu-button');
        const langMenu = document.getElementById('langMenu');

        if (userMenuButton) {
            userMenuButton.addEventListener('click', function(event) {
                event.stopPropagation();
                userMenu.classList.toggle('hidden');
                langMenu.classList.add('hidden'); // Close other dropdown
            });
        }

        if (langMenuButton) {
            langMenuButton.addEventListener('click', function(event) {
                event.stopPropagation();
                langMenu.classList.toggle('hidden');
                userMenu.classList.add('hidden'); // Close other dropdown
            });
        }

        // Global click listener to close dropdowns
        document.addEventListener('click', function(event) {
            // Close user menu
            if (userMenu && !userMenu.contains(event.target) && !userMenuButton.contains(event.target)) {
                userMenu.classList.add('hidden');
            }
            // Close lang menu
            if (langMenu && !langMenu.contains(event.target) && !langMenuButton.contains(event.target)) {
                langMenu.classList.add('hidden');
            }
        });

        // Keyboard shortcuts
        document.addEventListener('keydown', function(event) {
            if ((event.ctrlKey || event.metaKey) && event.key === 'k') {
                event.preventDefault();
                const globalSearch = document.getElementById('globalSearch');
                if(globalSearch) globalSearch.focus();
            }
            if (event.key === 'Escape') {
                hideSearchResults();
                const userMenu = document.getElementById('userMenu');
                if(userMenu) userMenu.classList.add('hidden');
            }
        });
    });

    // Update notification badge (placeholder for future notifications)
    function updateNotificationBadge(count) {
        const badge = document.getElementById('notificationBadge');
        if (badge) {
            if (count > 0) {
                badge.textContent = count > 99 ? '99+' : count;
                badge.classList.remove('hidden');
            } else {
                badge.classList.add('hidden');
            }
        }
    }
</script>

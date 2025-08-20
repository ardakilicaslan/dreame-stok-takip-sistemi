<?php

include 'config.php';

include 'functions.php';

if (!isLoggedIn()) {

    redirect('login.php');

}

$stmt = $conn->prepare("SELECT username FROM users WHERE id = ?");

$stmt->execute([$_SESSION['user_id']]);

$username = $stmt->fetchColumn();

$display_username = $username ? sanitizeInput($username) : 'Kullanıcı';

?>

<header class="bg-gray-200 p-4 flex justify-between items-center w-full header" id="header">

    <div class="text-lg font-semibold flex items-center">

        <i class="ri-home-line mr-2 text-blue-600"></i> Dreame Türkiye Ürün Yönetimi

    </div>

    <div class="flex items-center space-x-4">

        <span class="mr-4 flex items-center">

            <i class="ri-user-line mr-1 text-blue-600"></i> <?php echo $display_username; ?>

        </span>

        <a href="logout.php" class="bg-red-600 text-white px-4 py-2 rounded-md hover:bg-red-700 flex items-center">

            <i class="ri-logout-box-line mr-1"></i> Çıkış Yap

        </a>

    </div>

</header>

<style>

    @import url('https://fonts.googleapis.com/css2?family=Be+Vietnam+Pro:ital,wght@0,100;0,200;0,300;0,400;0,500;0,600;0,700;0,800;0,900;1,100;1,200;1,300;1,400;1,500;1,600;1,700;1,800;1,900&family=Roboto+Condensed:wght@500&family=Sofia+Sans:ital,wght@0,1..1000;1,1..1000&display=swap');

    body {

        font-family: 'Be Vietnam Pro', 'Roboto Condensed', 'Sofia Sans', sans-serif;

    }

    .header {

        position: fixed;

        top: 0;

        left: 0;

        margin-left: 250px;

        width: calc(100% - 250px);

        transition: margin-left 0.3s ease, width 0.3s ease;

        z-index: 50;

    }

    .header.collapsed {

        margin-left: 60px;

        width: calc(100% - 60px);

    }

</style>

<link href="styles.css" rel="stylesheet">
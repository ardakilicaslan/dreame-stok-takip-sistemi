<?php
require_once 'config.php';
require_once 'error_handler.php';
require_once 'security_functions.php';
require_once 'functions.php';

if (!isLoggedIn()) {
    redirect('login.php');
}

require_once 'controllers/IndexController.php';

$controller = new IndexController($conn);
$controller->index();
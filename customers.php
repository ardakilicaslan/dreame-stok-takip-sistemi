<?php

require_once 'config.php';
require_once 'functions.php';
require_once 'helpers/DatabaseHelper.php';
require_once 'helpers/FormHelper.php';
require_once 'controllers/CustomerController.php';

$db = new DatabaseHelper($conn);

$controller = new CustomerController($db);

$controller->index();
?>
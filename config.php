<?php

require_once __DIR__ . '/config/Config.php';
require_once __DIR__ . '/helpers/DatabaseHelper.php';
require_once __DIR__ . '/helpers/FormHelper.php';

Config::load();

if (session_status() === PHP_SESSION_NONE) {
    $securityConfig = Config::security();
    
    ini_set('session.cookie_httponly', 1);
    ini_set('session.cookie_secure', Config::isProduction() ? 1 : 0);
    ini_set('session.use_strict_mode', 1);
    ini_set('session.cookie_samesite', 'Strict');
    ini_set('session.gc_maxlifetime', $securityConfig['session_lifetime']);
    
    session_start();
}

header('X-Content-Type-Options: nosniff');
header('X-Frame-Options: DENY');
header('X-XSS-Protection: 1; mode=block');
header('Referrer-Policy: strict-origin-when-cross-origin');

if (Config::isProduction()) {
    header('Strict-Transport-Security: max-age=31536000; includeSubDomains');
}

try {
    $dbConfig = Config::database();
    $dsn = "mysql:host={$dbConfig['host']};dbname={$dbConfig['dbname']};charset=utf8mb4";
    
    $options = [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        PDO::ATTR_EMULATE_PREPARES => false,
        PDO::MYSQL_ATTR_INIT_COMMAND => "SET NAMES utf8mb4 COLLATE utf8mb4_unicode_ci"
    ];
    
    $conn = new PDO($dsn, $dbConfig['username'], $dbConfig['password'], $options);
    
    

    $conn->exec("SET sql_mode = 'STRICT_TRANS_TABLES,ERROR_FOR_DIVISION_BY_ZERO,NO_AUTO_CREATE_USER,NO_ENGINE_SUBSTITUTION'");
    
    

    $db = new DatabaseHelper($conn);
    
} catch (PDOException $e) {
    if (Config::isDebug()) {
        error_log("Database connection failed: " . $e->getMessage());
    }
    throw new PDOException("Veritabanı bağlantısı kurulamadı.");
}

require_once 'security_functions.php';
require_once 'error_handler.php';
require_once 'performance_optimizations.php';

if (rand(1, 100) === 1) {
    cleanupFailedLogins();
    cleanupAuditLogs();
}

if (Config::isProduction()) {
    error_reporting(0);
    ini_set('display_errors', 0);
} else {
    error_reporting(E_ALL);
    ini_set('display_errors', 1);
}
?>
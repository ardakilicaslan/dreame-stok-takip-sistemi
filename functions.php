<?php
if (!defined('FUNCTIONS_PHP_LOADED')) {
    define('FUNCTIONS_PHP_LOADED', true);

    function isLoggedIn() {
        return isset($_SESSION['user_id']);
    }

    function isAdmin() {
        return isset($_SESSION['role']) && $_SESSION['role'] === 'admin';
    }

    function redirect($url) {
        header("Location: $url");
        exit;
    }

    function sanitizeInput($data) {
        return htmlspecialchars(trim($data), ENT_QUOTES, 'UTF-8');
    }

    function generateCsrfToken() {
        if (!isset($_SESSION['csrf_token'])) {
            $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
        }
        return $_SESSION['csrf_token'];
    }

    function verifyCsrfToken($token) {
        return isset($_SESSION['csrf_token']) && hash_equals($_SESSION['csrf_token'], $token);
    }
}
?>
<?php

class Config {
    private static $config = [];
    private static $loaded = false;
    
    

    public static function load($envFile = '.env') {
        if (self::$loaded) return;
        
        $envPath = __DIR__ . '/../' . $envFile;
        
        

        self::$config = [
            'DB_HOST' => 'localhost',
            'DB_NAME' => 'envantera',
            'DB_USER' => 'root',
            'DB_PASS' => '',
            'APP_NAME' => 'Dreame Envanter Yönetim Sistemi',
            'APP_ENV' => 'development',
            'APP_DEBUG' => true,
            'APP_URL' => 'http://localhost/Envantera',
            'SESSION_LIFETIME' => 7200,
            'CSRF_TOKEN_LIFETIME' => 3600,
            'MAX_LOGIN_ATTEMPTS' => 5,
            'LOGIN_LOCKOUT_TIME' => 900,
            'MAX_FILE_SIZE' => 2097152,
            'ALLOWED_IMAGE_TYPES' => 'jpg,jpeg,png',
            'UPLOAD_PATH' => 'img/uploads/',
            'PLATFORM_UPLOAD_PATH' => 'img/platforms/',
            'CACHE_ENABLED' => true,
            'CACHE_DEFAULT_TTL' => 300,
            'LOG_LEVEL' => 'info',
            'LOG_FILE' => 'logs/app.log',
            'ERROR_LOG_FILE' => 'logs/error.log'
        ];
        
        

        if (file_exists($envPath)) {
            $lines = file($envPath, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
            
            foreach ($lines as $line) {
                if (strpos(trim($line), '#') === 0) continue;
                
                list($name, $value) = explode('=', $line, 2);
                $name = trim($name);
                $value = trim($value);
                
                

                if (preg_match('/^"(.*)"$/', $value, $matches)) {
                    $value = $matches[1];
                } elseif (preg_match("/^'(.*)'$/", $value, $matches)) {
                    $value = $matches[1];
                }
                
                

                if ($value === 'true') $value = true;
                elseif ($value === 'false') $value = false;
                elseif (is_numeric($value)) $value = is_float($value) ? (float)$value : (int)$value;
                
                self::$config[$name] = $value;
            }
        }
        
        self::$loaded = true;
    }
    
    

    public static function get($key, $default = null) {
        if (!self::$loaded) self::load();
        
        return self::$config[$key] ?? $default;
    }
    
    

    public static function set($key, $value) {
        if (!self::$loaded) self::load();
        
        self::$config[$key] = $value;
    }
    
    

    public static function database() {
        return [
            'host' => self::get('DB_HOST'),
            'dbname' => self::get('DB_NAME'),
            'username' => self::get('DB_USER'),
            'password' => self::get('DB_PASS')
        ];
    }
    
    

    public static function isDebug() {
        return self::get('APP_DEBUG', false);
    }
    
    

    public static function environment() {
        return self::get('APP_ENV', 'production');
    }
    
    

    public static function isProduction() {
        return self::environment() === 'production';
    }
    
    

    public static function upload() {
        return [
            'max_size' => self::get('MAX_FILE_SIZE'),
            'allowed_types' => explode(',', self::get('ALLOWED_IMAGE_TYPES')),
            'upload_path' => self::get('UPLOAD_PATH'),
            'platform_path' => self::get('PLATFORM_UPLOAD_PATH')
        ];
    }
    
    

    public static function security() {
        return [
            'session_lifetime' => self::get('SESSION_LIFETIME'),
            'csrf_lifetime' => self::get('CSRF_TOKEN_LIFETIME'),
            'max_login_attempts' => self::get('MAX_LOGIN_ATTEMPTS'),
            'lockout_time' => self::get('LOGIN_LOCKOUT_TIME')
        ];
    }
    
    

    public static function cache() {
        return [
            'enabled' => self::get('CACHE_ENABLED'),
            'default_ttl' => self::get('CACHE_DEFAULT_TTL')
        ];
    }
}
?>

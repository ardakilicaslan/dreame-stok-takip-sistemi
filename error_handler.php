<?php

if (!defined('ERROR_HANDLER_LOADED')) {
    define('ERROR_HANDLER_LOADED', true);

    

    function customErrorHandler($severity, $message, $file, $line) {
        if (!(error_reporting() & $severity)) {
            return false;
        }

        $errorTypes = [
            E_ERROR => 'ERROR',
            E_WARNING => 'WARNING',
            E_PARSE => 'PARSE',
            E_NOTICE => 'NOTICE',
            E_CORE_ERROR => 'CORE_ERROR',
            E_CORE_WARNING => 'CORE_WARNING',
            E_COMPILE_ERROR => 'COMPILE_ERROR',
            E_COMPILE_WARNING => 'COMPILE_WARNING',
            E_USER_ERROR => 'USER_ERROR',
            E_USER_WARNING => 'USER_WARNING',
            E_USER_NOTICE => 'USER_NOTICE',
            E_STRICT => 'STRICT',
            E_RECOVERABLE_ERROR => 'RECOVERABLE_ERROR',
            E_DEPRECATED => 'DEPRECATED',
            E_USER_DEPRECATED => 'USER_DEPRECATED'
        ];

        $errorType = $errorTypes[$severity] ?? 'UNKNOWN';
        $logMessage = sprintf(
            "[%s] %s: %s in %s on line %d",
            date('Y-m-d H:i:s'),
            $errorType,
            $message,
            $file,
            $line
        );

        error_log($logMessage);

        

        return true;
    }

    

    function customExceptionHandler($exception) {
        $logMessage = sprintf(
            "[%s] EXCEPTION: %s in %s on line %d\nStack trace:\n%s",
            date('Y-m-d H:i:s'),
            $exception->getMessage(),
            $exception->getFile(),
            $exception->getLine(),
            $exception->getTraceAsString()
        );

        error_log($logMessage);

        

        if (!headers_sent()) {
            http_response_code(500);
            header('Content-Type: text/html; charset=utf-8');
        }

        echo '<!DOCTYPE html>
        <html lang="en">
        <head>
            <meta charset="UTF-8">
            <meta name="viewport" content="width=device-width, initial-scale=1.0">
            <title>System Error</title>
            <script src="https://cdn.tailwindcss.com"></script>
        </head>
        <body class="bg-gray-100 flex items-center justify-center h-screen">
            <div class="bg-white p-8 rounded-lg shadow-lg w-full max-w-md text-center">
                <div class="text-red-500 text-6xl mb-4">⚠️</div>
                <h1 class="text-2xl font-bold mb-4 text-gray-800">System Error</h1>
                <p class="text-gray-600 mb-6">An unexpected error occurred. Please try again later.</p>
                <a href="index.php" class="bg-blue-600 text-white px-4 py-2 rounded-md hover:bg-blue-700">Back to Home</a>
            </div>
        </body>
        </html>';
        
        exit();
    }

    

    set_error_handler('customErrorHandler');
    set_exception_handler('customExceptionHandler');

    

    class AppException extends Exception {
        protected $userMessage;
        
        public function __construct($message, $userMessage = null, $code = 0, Throwable $previous = null) {
            parent::__construct($message, $code, $previous);
            $this->userMessage = $userMessage ?? 'An error occurred. Please try again.';
        }
        
        public function getUserMessage() {
            return $this->userMessage;
        }
    }

    class ValidationException extends AppException {
        public function __construct($message, $userMessage = null) {
            parent::__construct($message, $userMessage ?? $message);
        }
    }

    class SecurityException extends AppException {
        public function __construct($message, $userMessage = 'A security error was detected.') {
            parent::__construct($message, $userMessage);
        }
    }

    

    function handleDatabaseError($e, $operation = 'database operation') {
        $errorCode = $e->getCode();
        $errorMessage = $e->getMessage();
        
        

        error_log("Database Error [{$errorCode}]: {$errorMessage} during {$operation}");
        
        

        switch ($errorCode) {
            case 1062: 

                if (strpos($errorMessage, 'email') !== false) {
                    throw new ValidationException('This email address is already in use.');
                } elseif (strpos($errorMessage, 'username') !== false) {
                    throw new ValidationException('This username is already taken.');
                } else {
                    throw new ValidationException('This record already exists.');
                }
                break;
                
            case 1452: 

                throw new ValidationException('Related record not found. Please select a valid value.');
                break;
                
            case 1451: 

                throw new ValidationException('This record cannot be deleted because it is in use elsewhere.');
                break;
                
            default:
                throw new AppException(
                    "Database error: {$errorMessage}",
                    'A database error occurred. Please try again.'
                );
        }
    }

    

    function safeExecute($callback, $errorMessage = 'Operation failed.') {
        try {
            return $callback();
        } catch (ValidationException $e) {
            return ['success' => false, 'message' => $e->getUserMessage()];
        } catch (SecurityException $e) {
            return ['success' => false, 'message' => $e->getUserMessage()];
        } catch (PDOException $e) {
            try {
                handleDatabaseError($e);
            } catch (AppException $appE) {
                return ['success' => false, 'message' => $appE->getUserMessage()];
            }
        } catch (Exception $e) {
            error_log("Unexpected error: " . $e->getMessage());
            return ['success' => false, 'message' => $errorMessage];
        }
    }

    

    function validateAndSanitize($data, $rules) {
        $result = [];
        $errors = [];
        
        foreach ($rules as $field => $rule) {
            try {
                if (isset($data[$field])) {
                    $result[$field] = validateInput($data[$field], $rule['type'], $rule['options'] ?? []);
                } elseif ($rule['required'] ?? false) {
                    $errors[$field] = $rule['error'] ?? "The {$field} field is required.";
                }
            } catch (InvalidArgumentException $e) {
                $errors[$field] = $e->getMessage();
            }
        }
        
        if (!empty($errors)) {
            throw new ValidationException('Validation error', implode(' ', $errors));
        }
        
        return $result;
    }
}
?>

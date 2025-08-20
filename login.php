<?php
include 'config.php';
include 'functions.php';

if (isLoggedIn()) {
    redirect('index.php');
}

$notification = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $ip = $_SERVER['REMOTE_ADDR'] ?? '';

    

    if (!checkRateLimit($ip)) {
        $notification = '<div class="bg-red-500 text-white p-4 rounded mb-4">Çok fazla başarısız giriş denemesi. 15 dakika sonra tekrar deneyin.</div>';
    } else {
        try {
            $username = validateInput($_POST['username'], 'string', ['min' => 3, 'max' => 50]);
            $password = $_POST['password'];

            $stmt = $conn->prepare("SELECT * FROM users WHERE username = ?");
            $stmt->execute([$username]);
            $user = $stmt->fetch(PDO::FETCH_ASSOC);

            if ($user && password_verify($password, $user['password'])) {
                

                session_regenerate_id(true);
                
                $_SESSION['user_id'] = $user['id'];
                $_SESSION['username'] = $user['username']; 

                $_SESSION['role'] = $user['role'];
                $_SESSION['login_time'] = time();
                
                

                logAudit('LOGIN', 'users', $user['id']);
                
                redirect('index.php');
            } else {
                

                logFailedLogin($ip, $username);
                logAudit('FAILED_LOGIN', 'users', null, null, ['username' => $username, 'ip' => $ip]);
                
                $notification = '<div class="bg-red-500 text-white p-4 rounded mb-4">Kullanıcı adı veya şifre yanlış!</div>';
            }
        } catch (InvalidArgumentException $e) {
            $notification = '<div class="bg-red-500 text-white p-4 rounded mb-4">' . $e->getMessage() . '</div>';
        } catch (PDOException $e) {
            error_log("Login error: " . $e->getMessage());
            $notification = '<div class="bg-red-500 text-white p-4 rounded mb-4">Sistem hatası. Lütfen daha sonra tekrar deneyin.</div>';
        }
    }
}

try {
    $stmt = $conn->query("SELECT COUNT(*) FROM users");
    if ($stmt->fetchColumn() == 0) {
        $password = password_hash('admin123', PASSWORD_DEFAULT);
        $conn->exec("INSERT INTO users (username, password, role) VALUES ('admin', '$password', 'admin')");
    }
} catch (PDOException $e) {
    die("Hata: " . $e->getMessage());
}
?>

<!DOCTYPE html>
<html lang="tr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Giriş Yap</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
</head>
<body class="bg-gray-100 flex items-center justify-center h-screen">
    <div class="bg-white p-8 rounded-lg shadow-lg w-full max-w-md">
        <h1 class="text-2xl font-bold mb-6 text-gray-800">Giriş Yap</h1>
        <?php echo $notification; ?>
        <form method="POST">
            <div class="mb-4">
                <label for="username" class="block text-sm font-medium text-gray-700">Kullanıcı Adı</label>
                <input type="text" id="username" name="username" class="mt-1 block w-full p-2 border border-gray-300 rounded-md" required>
            </div>
            <div class="mb-6">
                <label for="password" class="block text-sm font-medium text-gray-700">Şifre</label>
                <input type="password" id="password" name="password" class="mt-1 block w-full p-2 border border-gray-300 rounded-md" required>
            </div>
            <button type="submit" class="bg-blue-600 text-white px-4 py-2 rounded-md hover:bg-blue-700 w-full"><i class="fas fa-sign-in-alt"></i> Giriş Yap</button>
        </form>
    </div>
</body>
</html>
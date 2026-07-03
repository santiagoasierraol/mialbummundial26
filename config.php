<?php
// 1. Forzar la bandera HTTPS para el proxy de InfinityFree
if (isset($_SERVER['HTTP_X_FORWARDED_PROTO']) && $_SERVER['HTTP_X_FORWARDED_PROTO'] == 'https') {
    $_SERVER['HTTPS'] = 'on';
}

// 2. Iniciar la sesión de forma segura (Solo una vez)
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// 3. Credenciales de la Base de Datos
define('DB_HOST', 'xxxxxx');     // Ejemplo: sql205.infinityfree.com
define('DB_NAME', 'xxxxxx');       // Ejemplo: if0_3821045_album_panini
define('DB_USER', 'ixxxxx');     // Ejemplo: if0_3821045
define('DB_PASS', 'xxxx');     // La contraseña de tu cuenta de InfinityFree

// 4. Conexión mediante PDO
try {
    $dsn = "mysql:host=" . DB_HOST . ";dbname=" . DB_NAME . ";charset=utf8mb4";
    $pdo = new PDO($dsn, DB_USER, DB_PASS, [
        PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC
    ]);
} catch (\PDOException $e) {
     die("Error crítico de conexión: " . $e->getMessage());
}

// 5. Función única de validación de Login (Elige si redirige a index.php o login.php)
if (!function_exists('check_login')) {
    function check_login() {
        if (!isset($_SESSION['usuario_id'])) {
            header("Location: login.php"); // Cambia a login.php si tu formulario está allá
            exit();
        }
    }
}
?>
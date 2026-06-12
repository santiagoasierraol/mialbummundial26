<?php
// config.php - Configuración exclusiva para Laragon local
if (isset($_SERVER['HTTP_X_FORWARDED_PROTO']) && $_SERVER['HTTP_X_FORWARDED_PROTO'] == 'https') {
    $_SERVER['HTTPS'] = 'on';
}

define('DB_HOST', '');     // Ejemplo: sql205.infinityfree.com
define('DB_NAME', '');       // Ejemplo: if0_3821045_album_panini
define('DB_USER', '');     // Ejemplo: if0_3821045
define('DB_PASS', '');     // La contraseña de tu cuenta de InfinityFree

try {
    $dsn = "mysql:host=" . DB_HOST . ";dbname=" . DB_NAME . ";charset=utf8mb4";
    $pdo = new PDO($dsn, DB_USER, DB_PASS, [
        PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC
    ]);
} catch (\PDOException $e) {
     die("Error crítico de conexión en Laragon: " . $e->getMessage());
}

// Inicializar la sesión de PHP de forma segura
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

function check_login() {
    if (!isset($_SESSION['usuario_id'])) {
        header("Location: login.php");
        exit;
    }
}
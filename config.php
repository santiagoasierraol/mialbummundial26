<?php
// config.php - Configuración exclusiva para Laragon local

define('DB_HOST', 'xx');     // Ejemplo: sql205.infinityfree.com
define('DB_NAME', 'xx');       // Ejemplo: if0_3821045_album_panini
define('DB_USER', 'xx');     // Ejemplo: if0_3821045
define('DB_PASS', 'bxx');     // La contraseña de tu cuenta de InfinityFree

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
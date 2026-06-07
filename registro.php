<?php
// ==============================================================================
// registro.php - Creado para mialbummundial26.infinityfreeapp.com
// Optimizado para evitar el error "Cookies are not enabled" en InfinityFree
// ==============================================================================

// 1. CONFIGURACIÓN DE CONEXIÓN DIRECTA (Evita abrir sesiones de PHP antes de tiempo)
define('DB_HOST', 'sql103.infinityfree.com');     // Ejemplo: sql205.infinityfree.com
define('DB_NAME', 'if0_42109150_album_panini');       // Ejemplo: if0_3821045_album_panini
define('DB_USER', 'if0_42109150');     // Ejemplo: if0_3821045
define('DB_PASS', 'boaCmmPTtZ7JAu9');     // La contraseña de tu cuenta de InfinityFree

$error = '';

// 2. FUNCIÓN LOCAL PARA ASIGNAR EL ÁLBUM INICIAL AL NUEVO USUARIO
function inicializar_album_usuario_local($pdo, $usuario_id) {
    // Listado base de láminas para el mundial (puedes expandir este arreglo con más jugadores)
    $laminas_iniciales = [
        ['FWC 0', 'Escudo Panini', 1],
        ['COL 1', 'Luis Díaz', 0],
        ['COL 2', 'James Rodríguez', 0],
        ['ARG 10', 'Lionel Messi', 1],
        ['POR 7', 'Cristiano Ronaldo', 1],
        ['BRA 10', 'Neymar Jr', 0],
        ['FRA 10', 'Kylian Mbappé', 0]
    ];

    $stmt = $pdo->prepare("INSERT INTO laminas (usuario_id, numero, nombre, es_especial, cantidad) VALUES (?, ?, ?, ?, 0)");
    foreach ($laminas_iniciales as $lamina) {
        $stmt->execute([$usuario_id, $lamina[0], $lamina[1], $lamina[2]]);
    }
}

// 3. PROCESAMIENTO DEL FORMULARIO ENVIADO (POST)
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = trim($_POST['username']);
    $password = trim($_POST['password']);

    if (!empty($username) && !empty($password)) {
        // Encriptar la contraseña de manera segura
        $password_hash = password_hash($password, PASSWORD_BCRYPT);

        try {
            $dsn = "mysql:host=" . DB_HOST . ";dbname=" . DB_NAME . ";charset=utf8mb4";
            $pdo = new PDO($dsn, DB_USER, DB_PASS, [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]);
            
            // Insertar el nuevo usuario en la tabla
            $stmt = $pdo->prepare("INSERT INTO usuarios (username, password) VALUES (?, ?)");
            $stmt->execute([$username, $password_hash]);
            $nuevo_usuario_id = $pdo->lastInsertId();

            // Llenar automáticamente su álbum vacío con cantidad 0
            inicializar_album_usuario_local($pdo, $nuevo_usuario_id);

            // 🚀 REDIRECCIÓN JAVASCRIPT: Engaña al cortafuegos simulando una interacción real del cliente
            echo "<script>
                    alert('¡Usuario registrado con éxito! Serás redirigido al inicio de sesión.');
                    window.location.href = 'login.php';
                  </script>";
            exit;

        } catch (\PDOException $e) {
            // El campo 'username' es UNIQUE, por lo que si ya existe saltará esta excepción
            $error = "El nombre de usuario ya está en uso por otra persona.";
        }
    } else {
        $error = "Todos los campos son obligatorios.";
    }
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Registro - Mundial 2026</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body class="bg-light d-flex align-items-center justify-content-center" style="min-height: 100vh;">

    <div class="card shadow-sm p-4" style="width: 100%; max-width: 400px;">
        <h2 class="text-center mb-4">Crear Cuenta</h2>
        
        <?php if(!empty($error)): ?> 
            <div class="alert alert-danger py-2 text-center small"><?= htmlspecialchars($error) ?></div> 
        <?php endif; ?>

        <form method="POST" action="registro.php">
            <div class="mb-3">
                <label class="form-label fw-semibold text-secondary">Nombre de Usuario</label>
                <input type="text" name="username" class="form-control" required autocomplete="off" placeholder="Ej: juan2026">
            </div>
            <div class="mb-3">
                <label class="form-label fw-semibold text-secondary">Contraseña</label>
                <input type="password" name="password" class="form-control" required placeholder="Crea una contraseña segura">
            </div>
            <button type="submit" class="btn btn-primary w-100 mb-3 fw-bold">Registrarse</button>
            
            <div class="text-center border-top pt-2">
                <a href="login.php" class="small text-decoration-none">¿Ya tienes cuenta? Inicia sesión aquí</a>
            </div>
        </form>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
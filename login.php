<?php
// ==============================================================================
// login.php - Control de Acceso y Registro con Inyección Masiva desde Archivo Externo
// ==============================================================================
require_once 'config.php';

$error_login = '';
$error_registro = '';
$success_registro = '';
$mostrar_pestana_registro = false;

// FUNCIÓN DE INICIALIZACIÓN OPTIMIZADA (Versión 4 Parámetros - V06)
function inicializar_album_local($pdo, $usuario_id) {
    // 📂 Importamos el listado masivo desde el archivo externo aislado
    $laminas_iniciales = require 'datos_album.php';

    try {
        $pdo->beginTransaction(); // Abrimos transacción para máxima velocidad local
        
        // Cambiamos el '0' fijo por un signo de interrogación '?' para la cantidad
        $stmt = $pdo->prepare("INSERT INTO laminas (usuario_id, numero, nombre, es_especial, cantidad) VALUES (?, ?, ?, ?, ?)");
        
        foreach ($laminas_iniciales as $lamina) {
            // Asignamos las posiciones del arreglo de forma segura
            $numero      = $lamina[0];
            $nombre      = $lamina[1];
            $es_especial = $lamina[2] ?? 0; // Tercer elemento del array
            $cantidad    = $lamina[3] ?? 0; // Cuarto elemento del array (Si no existe, arranca en 0)

            // Ejecutamos pasando las 5 variables en orden a los '?'
            $stmt->execute([
                $usuario_id, 
                $numero, 
                $nombre, 
                $es_especial, 
                $cantidad
            ]);
        }
        
        $pdo->commit(); // Confirmamos los inserts simultáneos
    } catch (\PDOException $e) {
        $pdo->rollBack(); // Cancelamos en caso de error para proteger la BD
        throw $e;
    }
}

// CONTROL DE PETICIONES POST
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    
    // LOGIN
    if (isset($_POST['btn_ingresar'])) {
        $username = trim($_POST['user_login'] ?? '');
        $password = trim($_POST['pass_login'] ?? '');

        if (!empty($username) && !empty($password)) {
            $stmt = $pdo->prepare("SELECT * FROM usuarios WHERE username = ?");
            $stmt->execute([$username]);
            $user = $stmt->fetch();

            if ($user && password_verify($password, $user['password'])) {
                $_SESSION['usuario_id'] = $user['id'];
                $_SESSION['username'] = $user['username'];
                header("Location: index.php");
                exit;
            } else {
                $error_login = "Usuario o contraseña incorrectos.";
            }
        } else {
            $error_login = "Todos los campos son obligatorios.";
        }
    }
    
    // REGISTRO
    if (isset($_POST['btn_registrar'])) {
        $username = trim($_POST['user_registro'] ?? '');
        $password = trim($_POST['pass_registro'] ?? '');
        $mostrar_pestana_registro = true;

        if (!empty($username) && !empty($password)) {
            $password_hash = password_hash($password, PASSWORD_BCRYPT);

            try {
                $stmt = $pdo->prepare("INSERT INTO usuarios (username, password) VALUES (?, ?)");
                $stmt->execute([$username, $password_hash]);
                $nuevo_id = $pdo->lastInsertId();

                // Llama al inicializador que consume datos_album.php
                inicializar_album_local($pdo, $nuevo_id);
                
                $success_registro = "¡Cuenta configurada con éxito! Ya puedes iniciar sesión.";
                $mostrar_pestana_registro = false;
            } catch (\PDOException $e) {
                $error_registro = "Error: El usuario ya existe o la base de datos falló.";
            }
        } else {
            $error_registro = "Todos los campos son obligatorios.";
        }
    }
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Acceso - Mi Álbum Mundial 2026</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        .nav-tabs .nav-link { color: #495057; font-weight: 600; border: none; }
        .nav-tabs .nav-link.active { color: #198754 !important; border-bottom: 3px solid #198754; background: none; }
        .card-auth { border-radius: 16px; border: none; }
    </style>
</head>
<body class="bg-light d-flex align-items-center justify-content-center" style="min-height: 100vh;">

    <div class="card card-auth shadow-lg p-4" style="width: 100%; max-width: 430px;">
        <h2 class="text-center mb-4 fw-bold">⚽ Mi Álbum Mundial 2026</h2>
        
        <ul class="nav nav-tabs nav-fill mb-4" id="authTabs" role="tablist">
            <li class="nav-item">
                <button class="nav-link <?= !$mostrar_pestana_registro ? 'active' : '' ?>" id="login-tab" data-bs-toggle="tab" data-bs-target="#login-panel" type="button" role="tab">Ingresar</button>
            </li>
            <li class="nav-item">
                <button class="nav-link <?= $mostrar_pestana_registro ? 'active' : '' ?>" id="registro-tab" data-bs-toggle="tab" data-bs-target="#registro-panel" type="button" role="tab">Registrarse</button>
            </li>
        </ul>

        <div class="tab-content" id="authTabsContent">
            <div class="tab-pane fade <?= !$mostrar_pestana_registro ? 'show active' : '' ?>" id="login-panel" role="tabpanel">
                <?php if(!empty($error_login)): ?> <div class="alert alert-danger py-2 text-center small fw-bold"><?= htmlspecialchars($error_login) ?></div> <?php endif; ?>
                <?php if(!empty($success_registro)): ?> <div class="alert alert-success py-2 text-center small fw-bold"><?= htmlspecialchars($success_registro) ?></div> <?php endif; ?>

                <form method="POST" action="login.php">
                    <div class="mb-3">
                        <label class="form-label text-secondary small fw-bold">Usuario</label>
                        <input type="text" name="user_login" class="form-control form-control-lg fs-6" required autocomplete="off">
                    </div>
                    <div class="mb-3">
                        <label class="form-label text-secondary small fw-bold">Contraseña</label>
                        <input type="password" name="pass_login" class="form-control form-control-lg fs-6" required>
                    </div>
                    <button type="submit" name="btn_ingresar" class="btn btn-success w-100 fw-bold py-2 fs-5 mt-2">Entrar al Álbum</button>
                </form>
            </div>

            <div class="tab-pane fade <?= $mostrar_pestana_registro ? 'show active' : '' ?>" id="registro-panel" role="tabpanel">
                <?php if(!empty($error_registro)): ?> <div class="alert alert-danger py-2 small fw-bold"><?= htmlspecialchars($error_registro) ?></div> <?php endif; ?>

                <form method="POST" action="login.php">
                    <div class="mb-3">
                        <label class="form-label text-secondary small fw-bold">Nombre de Usuario</label>
                        <input type="text" name="user_registro" class="form-control form-control-lg fs-6" required autocomplete="off">
                    </div>
                    <div class="mb-3">
                        <label class="form-label text-secondary small fw-bold">Contraseña</label>
                        <input type="password" name="pass_registro" class="form-control form-control-lg fs-6" required>
                    </div>
                    <button type="submit" name="btn_registrar" class="btn btn-primary w-100 fw-bold py-2 fs-5 mt-2">Crear mi Cuenta</button>
                </form>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
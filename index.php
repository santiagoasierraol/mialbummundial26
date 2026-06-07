<?php
// ==============================================================================
// index.php - Dashboard Principal del Álbum Mundial 2026
// Muestra estadísticas dinámicas y progreso del coleccionista
// ==============================================================================
require_once 'config.php';

// Validar que el usuario tenga una sesión activa
check_login();

$usuario_id = $_SESSION['usuario_id'];
$username = $_SESSION['username'];

try {
    // 1. OBTENER TOTAL DE LÁMINAS REGISTRADAS PARA ESTE USUARIO
    $stmt_total = $pdo->prepare("SELECT COUNT(*) AS total FROM laminas WHERE usuario_id = ?");
    $stmt_total->execute([$usuario_id]);
    $total_laminas = $stmt_total->fetch()['total'];

    // Si por alguna razón el usuario no tiene láminas (tabla vacía para él), evitamos división por cero
    if ($total_laminas == 0) {
        $total_laminas = 1; 
    }

    // 2. LÁMINAS PEGADAS (Tienen al menos 1 en cantidad)
    $stmt_pegadas = $pdo->prepare("SELECT COUNT(*) AS pegadas FROM laminas WHERE usuario_id = ? AND cantidad > 0");
    $stmt_pegadas->execute([$usuario_id]);
    $laminas_pegadas = $stmt_pegadas->fetch()['pegadas'];

    // 3. LÁMINAS REPETIDAS (Suma del excedente: si tiene 3, son 2 repetidas)
    $stmt_repetidas = $pdo->prepare("SELECT SUM(cantidad - 1) AS repetidas FROM laminas WHERE usuario_id = ? AND cantidad > 1");
    $stmt_repetidas->execute([$usuario_id]);
    $laminas_repetidas = $stmt_repetidas->fetch()['repetidas'] ?? 0;

    // 4. LÁMINAS FALTANTES
    $laminas_faltantes = $total_laminas - $laminas_pegadas;

    // 5. CALCULAR PORCENTAJE DE PROGRESO
    $porcentaje_progreso = round(($laminas_pegadas / $total_laminas) * 100, 1);

} catch (\PDOException $e) {
    die("Error al cargar las estadísticas: " . $e->getMessage());
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard - Álbum Collector</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        .card-stat { transition: transform 0.2s; border: none; }
        .card-stat:hover { transform: translateY(-5px); }
    </style>
</head>
<body class="bg-light">

    <nav class="navbar navbar-expand-lg navbar-dark bg-dark shadow-sm mb-4">
        <div class="container">
            <a class="navbar-brand fw-bold" href="index.php">⚽ Álbum Mundial 2026</a>
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
                <span class="navbar-toggler-icon"></span>
            </button>
            <div class="collapse navbar-collapse justify-content-end" id="navbarNav">
                <ul class="navbar-nav align-items-center">
                    <li class="nav-itemme-3">
                        <span class="navbar-text text-white fw-bold">¡Hola, <?= htmlspecialchars($username) ?>!</span>
                    </li>
                    <li class="nav-item">
                        <a class="btn btn-sm btn-outline-danger fw-bold" href="logout.php">Cerrar Sesión</a>
                    </li>
                </ul>
            </div>
        </div>
    </nav>

    <div class="container">
        
        <div class="row mb-4">
            <div class="col-12">
                <div class="card shadow-sm p-4 bg-white rounded">
                    <h3 class="fw-bold text-secondary mb-3">Progreso de tu Colección</h3>
                    
                    <div class="d-flex justify-content-between align-items-center mb-2">
                        <span class="text-muted small fw-bold">Porcentaje completado</span>
                        <span class="badge bg-success fs-6"><?= $porcentaje_progreso ?>%</span>
                    </div>
                    
                    <div class="progress" style="height: 25px;">
                        <div class="progress-bar progress-bar-striped progress-bar-animated bg-success" 
                             role="progressbar" 
                             style="width: <?= $porcentaje_progreso ?>%;" 
                             aria-valuenow="<?= $porcentaje_progreso ?>" 
                             aria-valuemin="0" 
                             aria-valuemax="100">
                             <?= $porcentaje_progreso ?>%
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="row g-3 mb-4">
            <div class="col-6 col-md-3">
                <div class="card card-stat bg-primary text-white text-center p-3 shadow-sm">
                    <div class="fs-1">📋</div>
                    <h2 class="fw-bold mb-0"><?= $total_laminas ?></h2>
                    <p class="small mb-0 opacity-75">Total Álbum</p>
                </div>
            </div>
            <div class="col-6 col-md-3">
                <div class="card card-stat bg-success text-white text-center p-3 shadow-sm">
                    <div class="fs-1">✅</div>
                    <h2 class="fw-bold mb-0"><?= $laminas_pegadas ?></h2>
                    <p class="small mb-0 opacity-75">Pegadas</p>
                </div>
            </div>
            <div class="col-6 col-md-3">
                <div class="card card-stat bg-danger text-white text-center p-3 shadow-sm">
                    <div class="fs-1">❌</div>
                    <h2 class="fw-bold mb-0"><?= $laminas_faltantes ?></h2>
                    <p class="small mb-0 opacity-75">Faltantes</p>
                </div>
            </div>
            <div class="col-6 col-md-3">
                <div class="card card-stat bg-warning text-dark text-center p-3 shadow-sm">
                    <div class="fs-1">🔄</div>
                    <h2 class="fw-bold mb-0"><?= $laminas_repetidas ?></h2>
                    <p class="small mb-0 opacity-75">Repetidas</p>
                </div>
            </div>
        </div>

        <div class="row g-4">
            <div class="col-12 col-md-6">
                <div class="card shadow-sm h-100 p-4 bg-white">
                    <h4 class="fw-bold text-dark mb-3">Gestionar mi Álbum</h4>
                    <p class="text-muted">Ingresa al inventario completo para registrar las láminas que te van saliendo en los sobres, organizadas por selecciones.</p>
                    <a href="laminas.php" class="btn btn-primary fw-bold py-2 mt-auto">👀 Ver y Editar mis Láminas</a>
                </div>
            </div>
            <div class="col-12 col-md-6">
                <div class="card shadow-sm h-100 p-4 bg-white">
                    <h4 class="fw-bold text-dark mb-3">Zona de Intercambio</h4>
                    <p class="text-muted">Mira rápidamente un listado exclusivo de tus láminas repetidas para que te sea más fácil negociar y cambiar con tus amigos.</p>
                    <a href="repetidas.php" class="btn btn-warning fw-bold py-2 mt-auto text-dark">🔄 Ver Solo Repetidas</a>
                </div>
            </div>
        </div>

    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
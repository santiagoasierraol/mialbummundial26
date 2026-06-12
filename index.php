<?php
// ==============================================================================
// index.php - Dashboard Principal del Álbum Mundial 2026
// Muestra estadísticas dinámicas y progreso del coleccionista
// ==============================================================================
if (isset($_SERVER['HTTP_X_FORWARDED_PROTO']) && $_SERVER['HTTP_X_FORWARDED_PROTO'] == 'https') {
    //$_SERVER['HTTPS'] = 'on';
}
session_start();
require_once 'config.php';

// Validar que el usuario tenga una sesión activa
check_login();

$usuario_id = $_SESSION['usuario_id'];
$username = $_SESSION['username'];

// ==============================================================================
// LÓGICA DE ESTADÍSTICAS POR EQUIPOS - MIALBUMMUNDIAL26 V07
// ==============================================================================

// Mapeo interno para mostrar nombres reales en lugar de solo las siglas de la BD
$nombres_equipos = [
        'FWC' => '🏆 Especiales y Estadios Panini',
        // ANFITRIONES
        'CAN' => '🇨🇦 Selección Canadá',
        'USA' => '🇺🇸 Selección Estados Unidos',
        'MEX' => '🇲🇽 Selección México',

        // CONMEBOL / SUDAMÉRICA
        'ARG' => '🇦🇷 Selección Argentina',
        'BRA' => '🇧🇷 Selección Brasil',
        'COL' => '🇨🇴 Selección Colombia',
        'ECU' => '🇪🇨 Selección Ecuador',
        'PAR' => '🇵🇾 Selección Paraguay',
        'URU' => '🇺🇺 Selección Uruguay',
        'HAI' => '🇭🇹 Selección Haití',
        'CUW' => '🇨🇼 Selección Curazao',
        'PAN' => '🇵🇦 Selección Panama',

        // UEFA / EUROPA
        'GER' => '🇩🇪 Selección Alemania',
        'AUT' => '🇦🇹 Selección Austria',
        'BEL' => '🇧🇪 Selección Bélgica',
        'CRO' => '🇭🇷 Selección Croacia',
        'SCO' => '🏴󠁧󠁢󠁳󠁣󠁴󠁿 Selección Escocia',
        'ESP' => '🇪🇸 Selección España',
        'FRA' => '🇫🇷 Selección Francia',
        'ENG' => '🏴󠁧󠁢󠁥󠁮󠁧󠁿 Selección Inglaterra',
        'NOR' => '🇳🇴 Selección Noruega',
        'NED' => '🇳🇱 Selección Países Bajos',
        'POR' => '🇵🇹 Selección Portugal',
        'SUI' => '🇨🇭 Selección Suiza',
        'SWE' => '🇸🇪 Selección Suecia',
        'CZE' => '🇨🇿 Selección Chequia',
        'TUR' => '🇹🇷 Selección Turquía',
        'BIH' => '🇧🇦 Selección Bosnia y H.',

        // CAF / ÁFRICA
        'ALG' => '🇩🇿 Selección Argelia',
        'CPV' => '🇨🇻 Selección Cabo Verde',
        'CIV' => '🇨🇮 Selección Costa de Marfil',
        'EGY' => '🇪🇬 Selección Egipto',
        'GHA' => '🇬🇭 Selección Ghana',
        'MAR' => '🇲🇦 Selección Marruecos',
        'SEN' => '🇸🇳 Selección Senegal',
        'RSA' => '🇿🇦 Selección Sudáfrica',
        'TUN' => '🇹🇳 Selección Túnez',
        'COD' => '🇨🇩 Selección RD Congo',

        // AFC / ASIA
        'KSA' => '🇸🇦 Selección Arabia S.',
        'AUS' => '🇦🇺 Selección Australia',
        'KOR' => '🇰🇷 Selección Corea del Sur',
        'IRQ' => '🇮🇶 Selección Irak/EAU',
        'IRN' => '🇮🇷 Selección Irán',
        'JPN' => '🇯🇵 Selección Japón',
        'JOR' => '🇯🇴 Selección Jordania',
        'QAT' => '🇶🇦 Selección Qatar',
        'UZB' => '🇺🇿 Selección Uzbekistán',

        // OFC / OCEANÍA
        'NZL' => '🇳🇿 Selección Nueva Zelanda',
        'CC'  => '🥤 Sección Especial Coca-Cola'
];

try {
    // 1. TOP 5: Equipos con MÁS láminas (Donde cantidad > 0, es decir, láminas obtenidas únicas)
    $sql_mas = "SELECT SUBSTRING_INDEX(numero, ' ', 1) AS sigla, COUNT(*) AS total 
                FROM laminas 
                WHERE usuario_id = ? AND cantidad > 0 
                GROUP BY sigla 
                ORDER BY total DESC 
                LIMIT 5";
    $stmt = $pdo->prepare($sql_mas);
    $stmt->execute([$usuario_id]);
    $top_mas_laminas = $stmt->fetchAll();

    // 2. TOP 5: Equipos con MENOS láminas (Contamos cuántas tiene obtenidas cada equipo para ver los más vacíos)
    $sql_menos = "SELECT SUBSTRING_INDEX(numero, ' ', 1) AS sigla, COUNT(*) AS total 
                  FROM laminas 
                  WHERE usuario_id = ? AND cantidad > 0 
                  GROUP BY sigla 
                  ORDER BY total ASC 
                  LIMIT 5";
    $stmt = $pdo->prepare($sql_menos);
    $stmt->execute([$usuario_id]);
    $top_menos_laminas = $stmt->fetchAll();

    // 3. TOP 5: Equipos con MÁS REPETIDAS (Suma de excedentes: si cantidad es 3, sumamos 2 repetidas)
    $sql_rep = "SELECT SUBSTRING_INDEX(numero, ' ', 1) AS sigla, SUM(cantidad - 1) AS total_repetidas 
                FROM laminas 
                WHERE usuario_id = ? AND cantidad > 1 
                GROUP BY sigla 
                ORDER BY total_repetidas DESC 
                LIMIT 5";
    $stmt = $pdo->prepare($sql_rep);
    $stmt->execute([$usuario_id]);
    $top_repetidas = $stmt->fetchAll();

} catch (\PDOException $e) {
    die("Error calculando estadísticas: " . $e->getMessage());
}

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
    <title>Mi Álbum Mundial - Inicio</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        .card-stat { transition: transform 0.2s; border: none; }
        .card-stat:hover { transform: translateY(-5px); }
    </style>
</head>

<body class="bg-light">
    <?php include 'navbar.php'; ?>
    <div class="container mb-5">

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

        <!-- ==============================================================================
            SECCIÓN DE RANKINGS Y TOPS - VERSIÓN V07
            ============================================================================== -->
        <div class="row g-3 mb-5">

            <!-- COLUMNA 1: TOP 5 MÁS LLENOS -->
            <div class="col-12 col-md-4">
                <div class="card h-100 shadow-sm border-0">
                    <div class="card-header bg-success text-white fw-bold py-3">
                        📈 Top 5: Equipos Más Avanzados
                    </div>
                    <ul class="list-group list-group-flush">
                        <?php if (empty($top_mas_laminas)): ?>
                            <li class="list-group-item text-muted text-center py-3">Aún no tienes láminas registradas.</li>
                        <?php else: ?>
                            <?php foreach ($top_mas_laminas as $index => $equipo): 
                                $nombre_real = $nombres_equipos[$equipo['sigla']] ?? '🌍 ' . $equipo['sigla'];
                            ?>
                                <li class="list-group-item d-flex justify-content-between align-items-center fw-medium text-secondary">
                                    <div>
                                        <span class="badge bg-light text-dark me-2"><?= $index + 1 ?>°</span>
                                        <?= $nombre_real ?>
                                    </div>
                                    <span class="badge bg-success rounded-pill"><?= $equipo['total'] ?> monas</span>
                                </li>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </ul>
                </div>
            </div>

            <!-- COLUMNA 2: TOP 5 MENOS AVANZADOS -->
            <div class="col-12 col-md-4">
                <div class="card h-100 shadow-sm border-0">
                    <div class="card-header bg-danger text-white fw-bold py-3">
                        📉 Top 5: Equipos Menos Avanzados
                    </div>
                    <ul class="list-group list-group-flush">
                        <?php if (empty($top_menos_laminas)): ?>
                            <li class="list-group-item text-muted text-center py-3">Aún no tienes láminas registradas.</li>
                        <?php else: ?>
                            <?php foreach ($top_menos_laminas as $index => $equipo): 
                                $nombre_real = $nombres_equipos[$equipo['sigla']] ?? '🌍 ' . $equipo['sigla'];
                            ?>
                                <li class="list-group-item d-flex justify-content-between align-items-center fw-medium text-secondary">
                                    <div>
                                        <span class="badge bg-light text-dark me-2"><?= $index + 1 ?>°</span>
                                        <?= $nombre_real ?>
                                    </div>
                                    <span class="badge bg-danger rounded-pill"><?= $equipo['total'] ?> monas</span>
                                </li>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </ul>
                </div>
            </div>

            <!-- COLUMNA 3: TOP 5 MÁS REPETIDAS -->
            <div class="col-12 col-md-4">
                <div class="card h-100 shadow-sm border-0">
                    <div class="card-header bg-warning text-dark fw-bold py-3">
                        🔄 Top 5: Equipos con Más Repetidas
                    </div>
                    <ul class="list-group list-group-flush">
                        <?php if (empty($top_repetidas)): ?>
                            <li class="list-group-item text-muted text-center py-3">¡Excelente! No tienes láminas repetidas aún.</li>
                        <?php else: ?>
                            <?php foreach ($top_repetidas as $index => $equipo): 
                                $nombre_real = $nombres_equipos[$equipo['sigla']] ?? '🌍 ' . $equipo['sigla'];
                            ?>
                                <li class="list-group-item d-flex justify-content-between align-items-center fw-medium text-secondary">
                                    <div>
                                        <span class="badge bg-light text-dark me-2"><?= $index + 1 ?>°</span>
                                        <?= $nombre_real ?>
                                    </div>
                                    <span class="badge bg-warning text-dark rounded-pill fw-bold">+<?= $equipo['total_repetidas'] ?></span>
                                </li>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </ul>
                </div>
            </div>

        </div>        

    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
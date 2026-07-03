<?php
// ==============================================================================
// index.php - Dashboard Principal del Álbum Mundial 2026
// Muestra estadísticas dinámicas y progreso del coleccionista
// ==============================================================================
require_once 'config.php';

// Validar que el usuario tenga una sesión activa (excepto en el index si es el login)
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
                LIMIT 10";
    $stmt = $pdo->prepare($sql_mas);
    $stmt->execute([$usuario_id]);
    $top_mas_laminas = $stmt->fetchAll();

    // 2. TOP 5: Equipos con MENOS láminas (Contamos cuántas tiene obtenidas cada equipo para ver los más vacíos)
    $sql_menos = "SELECT SUBSTRING_INDEX(numero, ' ', 1) AS sigla, COUNT(*) AS total 
                  FROM laminas 
                  WHERE usuario_id = ? AND cantidad > 0 
                  GROUP BY sigla 
                  ORDER BY total ASC 
                  LIMIT 10";
    $stmt = $pdo->prepare($sql_menos);
    $stmt->execute([$usuario_id]);
    $top_menos_laminas = $stmt->fetchAll();

    // 3. TOP 5: Equipos con MÁS REPETIDAS (Suma de excedentes: si cantidad es 3, sumamos 2 repetidas)
    $sql_rep = "SELECT SUBSTRING_INDEX(numero, ' ', 1) AS sigla, SUM(cantidad - 1) AS total_repetidas 
                FROM laminas 
                WHERE usuario_id = ? AND cantidad > 1 
                GROUP BY sigla 
                ORDER BY total_repetidas DESC 
                LIMIT 10";
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

// ==============================================================================
// LOGICA DE ESCUDOS - PANEL DE CONTROL (V08-MICRO)
// ==============================================================================
try {
    // NOTA: Ajusta el LIKE '%Escudo%' si en tu BD se llaman diferente (ej: '%Badge%' o 'numero LIKE "% 1"')
    // Esta consulta trae todas las monas que sean escudos para el usuario actual
    $stmt_escudos = $pdo->prepare("
        SELECT *, (cantidad - 1) AS excedente 
        FROM laminas 
        WHERE usuario_id = ? AND nombre LIKE '%Escudo%'
        ORDER BY id ASC
    ");
    $stmt_escudos->execute([$usuario_id]);
    $todos_los_escudos = $stmt_escudos->fetchAll();

    // Contadores para el resumen del Panel
    $total_escudos_album = count($todos_los_escudos);
    $escudos_poseidos = 0;
    $escudos_faltantes = 0;
    $escudos_repetidos_total = 0;

    $lista_faltantes = [];
    $lista_repetidos = [];
    $lista_poseidos = [];

    foreach ($todos_los_escudos as $escudo) {
        if ($escudo['cantidad'] == 0) {
            $escudos_faltantes++;
            $lista_faltantes[] = $escudo;
        } else {
            $escudos_poseidos++;
            $lista_poseidos[] = $escudo;
            
            if ($escudo['cantidad'] > 1) {
                $escudos_repetidos_total += $escudo['excedente'];
                $lista_repetidos[] = $escudo;
            }
        }
    }
} catch (\PDOException $e) {
    die("Error al cargar control de escudos: " . $e->getMessage());
}

// ==============================================================================
// LÓGICA DE FOTOS DE EQUIPOS (NÚMERO 13) - PANEL DE CONTROL (V08-MICRO)
// ==============================================================================
try {
    // Consulta para traer únicamente las láminas de fotos grupales de los equipos (Número 13)
    $stmt_fotos_equipos = $pdo->prepare("
        SELECT *, (cantidad - 1) AS excedente 
        FROM laminas 
        WHERE usuario_id = ? AND numero LIKE '% 13' AND nombre LIKE '%Equipo%'
        ORDER BY id ASC
    ");
    $stmt_fotos_equipos->execute([$usuario_id]);
    $todas_las_fotos = $stmt_fotos_equipos->fetchAll();

    // Contadores para el resumen del Panel
    $total_fotos_album = count($todas_las_fotos);
    $fotos_poseidas = 0;
    $fotos_faltantes = 0;
    $fotos_repetidas_total = 0;

    $lista_fotos_faltantes = [];
    $lista_fotos_repetidos = [];
    $lista_fotos_poseidas = [];

    foreach ($todas_las_fotos as $foto) {
        if ($foto['cantidad'] == 0) {
            $fotos_faltantes++;
            $lista_fotos_faltantes[] = $foto;
        } else {
            $fotos_poseidas++;
            $lista_fotos_poseidas[] = $foto;
            
            if ($foto['cantidad'] > 1) {
                $fotos_repetidas_total += $foto['excedente'];
                $lista_fotos_repetidas[] = $foto;
            }
        }
    }
} catch (\PDOException $e) {
    die("Error al cargar control de fotos de equipos: " . $e->getMessage());
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Mi Álbum Mundial - Inicio</title>
    <link rel="icon" type="image/png" href="img/favicon.png">
    <link class="favicon-apple" rel="apple-touch-icon" href="img/favicon.png">
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
            <!-- CONTROL DE ESCUDOS -->
            <div class="card shadow-sm border-0 bg-white p-3 mb-4 rounded-3">
                <div class="d-flex justify-content-between align-items-center mb-3 border-bottom pb-2">
                    <div>
                        <h5 class="fw-bold text-dark mb-0">🛡️ Control de Escudos</h5>
                        <p class="text-muted small mb-0">Progreso exclusivo de las insignias de las selecciones.</p>
                    </div>
                    <span class="badge bg-dark fs-6"><?= $escudos_poseidos ?> / <?= $total_escudos_album ?></span>
                </div>

                <div class="row g-2 mb-3 text-center">
                    <div class="col-4">
                        <div class="p-2 bg-success-subtle rounded border border-success-subtle">
                            <strong class="d-block text-success fs-5"><?= $escudos_poseidos ?></strong>
                            <span class="text-muted fw-bold" style="font-size: 0.65rem;">Tengo</span>
                        </div>
                    </div>
                    <div class="col-4">
                        <div class="p-2 bg-danger-subtle rounded border border-danger-subtle">
                            <strong class="d-block text-danger fs-5"><?= $escudos_faltantes ?></strong>
                            <span class="text-muted fw-bold" style="font-size: 0.65rem;">Faltan</span>
                        </div>
                    </div>
                    <div class="col-4">
                        <div class="p-2 bg-warning-subtle rounded border border-warning-subtle">
                            <strong class="d-block text-warning-dark fs-5" style="color: #856404;"><?= $escudos_repetidos_total ?></strong>
                            <span class="text-muted fw-bold" style="font-size: 0.65rem;">Repes</span>
                        </div>
                    </div>
                </div>

                <ul class="nav nav-pills nav-fill bg-light p-1 rounded-2 mb-3" id="pills-tab-escudos" role="tablist" style="font-size: 0.8rem;">
                    <li class="nav-item">
                        <button class="nav-link active py-1 fw-bold" id="pills-tengo-tab" data-bs-toggle="pill" data-bs-target="#pills-tengo" type="button" role="tab">Tengo</button>
                    </li>
                    <li class="nav-item">
                        <button class="nav-link py-1 fw-bold text-danger" id="pills-faltan-tab" data-bs-toggle="pill" data-bs-target="#pills-faltan" type="button" role="tab">Faltan (<?= $escudos_faltantes ?>)</button>
                    </li>
                    <li class="nav-item">
                        <button class="nav-link py-1 fw-bold text-warning" id="pills-repes-tab" data-bs-toggle="pill" data-bs-target="#pills-repes" type="button" role="tab">Repes (<?= $escudos_repetidos_total ?>)</button>
                    </li>
                </ul>

                <div class="tab-content" id="pills-tabContentEscudos">
                    
                    <div class="tab-pane fade show active" id="pills-tengo" role="tabpanel">
                        <?php if(empty($lista_poseidos)): ?>
                            <p class="text-center text-muted small my-3">No tienes ningún escudo pegado aún. 😢</p>
                        <?php else: ?>
                            <div class="row row-cols-3 row-cols-sm-4 row-cols-md-6 g-1">
                                <?php foreach($lista_poseidos as $esc): ?>
                                    <div class="col">
                                        <div class="p-1 text-center rounded-2 border border-success text-dark fw-bold" style="font-size: 0.72rem; background-color: #f8fff9; min-height: 42px; display: flex; flex-direction: column; justify-content: center;">
                                            <span><?= htmlspecialchars($esc['numero']) ?></span>
                                            <span class="text-muted text-truncate" style="font-size: 0.58rem; width: 100%;"><?= htmlspecialchars($esc['equipo']) ?></span>
                                        </div>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                        <?php endif; ?>
                    </div>

                    <div class="tab-pane fade" id="pills-faltan" role="tabpanel">
                        <?php if(empty($lista_faltantes)): ?>
                            <p class="text-center text-success small fw-bold my-3">🎉 ¡Felicidades! Tienes todos los escudos del mundial.</p>
                        <?php else: ?>
                            <div class="row row-cols-3 row-cols-sm-4 row-cols-md-6 g-1">
                                <?php foreach($lista_faltantes as $esc): ?>
                                    <div class="col">
                                        <div class="p-1 text-center rounded-2 border text-secondary" style="font-size: 0.72rem; background-color: #eaeaea; border-color: #b9b9b9; min-height: 42px; display: flex; flex-direction: column; justify-content: center; font-weight: bold;">
                                            <span><?= htmlspecialchars($esc['numero']) ?></span>
                                            <span class="text-muted text-truncate" style="font-size: 0.58rem; width: 100%;"><?= htmlspecialchars($esc['equipo']) ?></span>
                                        </div>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                        <?php endif; ?>
                    </div>

                    <div class="tab-pane fade" id="pills-repes" role="tabpanel">
                        <?php if(empty($lista_repetidos)): ?>
                            <p class="text-center text-muted small my-3">No tienes escudos repetidos para negociar.</p>
                        <?php else: ?>
                            <div class="row row-cols-3 row-cols-sm-4 row-cols-md-6 g-1">
                                <?php foreach($lista_repetidos as $esc): ?>
                                    <div class="col">
                                        <div class="p-1 text-center rounded-2 border border-warning text-dark fw-bold d-flex flex-column justify-content-center align-items-center" style="font-size: 0.72rem; background-color: #fffdf0; min-height: 42px;">
                                            <span><?= htmlspecialchars($esc['numero']) ?></span>
                                            <span class="badge bg-warning text-dark p-0 px-1 mt-0" style="font-size: 0.62rem; font-family: monospace;">+<?= $esc['excedente'] ?></span>
                                        </div>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                        <?php endif; ?>
                    </div>

                </div>
            </div>
            <!-- CONTROL DE SELECCIONES -->
            <div class="card shadow-sm border-0 bg-white p-3 mb-4 rounded-3">
                <div class="d-flex justify-content-between align-items-center mb-3 border-bottom pb-2">
                    <div>
                        <h5 class="fw-bold text-dark mb-0">👥 Fotos de Equipos (N° 13)</h5>
                        <p class="text-muted small mb-0">Progreso de las láminas de las formaciones de cada selección.</p>
                    </div>
                    <span class="badge bg-dark fs-6"><?= $fotos_poseidas ?> / <?= $total_fotos_album ?></span>
                </div>

                <div class="row g-2 mb-3 text-center">
                    <div class="col-4">
                        <div class="p-2 bg-success-subtle rounded border border-success-subtle">
                            <strong class="d-block text-success fs-5"><?= $fotos_poseidas ?></strong>
                            <span class="text-muted fw-bold" style="font-size: 0.65rem;">Tengo</span>
                        </div>
                    </div>
                    <div class="col-4">
                        <div class="p-2 bg-danger-subtle rounded border border-danger-subtle">
                            <strong class="d-block text-danger fs-5"><?= $fotos_faltantes ?></strong>
                            <span class="text-muted fw-bold" style="font-size: 0.65rem;">Faltan</span>
                        </div>
                    </div>
                    <div class="col-4">
                        <div class="p-2 bg-warning-subtle rounded border border-warning-subtle">
                            <strong class="d-block text-warning-dark fs-5" style="color: #856404;"><?= $fotos_repetidas_total ?></strong>
                            <span class="text-muted fw-bold" style="font-size: 0.65rem;">Repes</span>
                        </div>
                    </div>
                </div>

                <ul class="nav nav-pills nav-fill bg-light p-1 rounded-2 mb-3" id="pills-tab-fotos" role="tablist" style="font-size: 0.8rem;">
                    <li class="nav-item">
                        <button class="nav-link active py-1 fw-bold" id="pills-fotos-tengo-tab" data-bs-toggle="pill" data-bs-target="#pills-fotos-tengo" type="button" role="tab">Tengo</button>
                    </li>
                    <li class="nav-item">
                        <button class="nav-link py-1 fw-bold text-danger" id="pills-fotos-faltan-tab" data-bs-toggle="pill" data-bs-target="#pills-fotos-faltan" type="button" role="tab">Faltan (<?= $fotos_faltantes ?>)</button>
                    </li>
                    <li class="nav-item">
                        <button class="nav-link py-1 fw-bold text-warning" id="pills-fotos-repes-tab" data-bs-toggle="pill" data-bs-target="#pills-fotos-repes" type="button" role="tab">Repes (<?= $fotos_repetidas_total ?>)</button>
                    </li>
                </ul>

                <div class="tab-content" id="pills-tabContentFotos">
                    
                    <div class="tab-pane fade show active" id="pills-fotos-tengo" role="tabpanel">
                        <?php if(empty($lista_fotos_poseidas)): ?>
                            <p class="text-center text-muted small my-3">No tienes ninguna foto de equipo pegada todavía. 😢</p>
                        <?php else: ?>
                            <div class="row row-cols-3 row-cols-sm-4 row-cols-md-6 g-1" style="max-height: 220px; overflow-y: auto;">
                                <?php foreach($lista_fotos_poseidas as $f_pos): ?>
                                    <div class="col">
                                        <div class="p-1 text-center rounded-2 border border-success text-dark fw-bold" style="font-size: 0.72rem; background-color: #f8fff9; min-height: 42px; display: flex; flex-direction: column; justify-content: center;">
                                            <span><?= htmlspecialchars($f_pos['numero']) ?></span>
                                            <span class="text-muted text-truncate" style="font-size: 0.58rem; width: 100%;"><?= htmlspecialchars($f_pos['equipo']) ?></span>
                                        </div>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                        <?php endif; ?>
                    </div>

                    <div class="tab-pane fade" id="pills-fotos-faltan" role="tabpanel">
                        <?php if(empty($lista_fotos_faltantes)): ?>
                            <p class="text-center text-success small fw-bold my-3">🎉 ¡Excelente! Tienes todas las fotos de equipo del álbum.</p>
                        <?php else: ?>
                            <div class="row row-cols-3 row-cols-sm-4 row-cols-md-6 g-1" style="max-height: 220px; overflow-y: auto;">
                                <?php foreach($lista_fotos_faltantes as $f_fal): ?>
                                    <div class="col">
                                        <div class="p-1 text-center rounded-2 border text-secondary" style="font-size: 0.72rem; background-color: #eaeaea; border-color: #b9b9b9; min-height: 42px; display: flex; flex-direction: column; justify-content: center; font-weight: bold;">
                                            <span><?= htmlspecialchars($f_fal['numero']) ?></span>
                                            <span class="text-muted text-truncate" style="font-size: 0.58rem; width: 100%;"><?= htmlspecialchars($f_fal['equipo']) ?></span>
                                        </div>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                        <?php endif; ?>
                    </div>

                    <div class="tab-pane fade" id="pills-fotos-repes" role="tabpanel">
                        <?php if(empty($lista_fotos_repetidas)): ?>
                            <p class="text-center text-muted small my-3">No tienes fotos de equipos repetidas.</p>
                        <?php else: ?>
                            <div class="row row-cols-3 row-cols-sm-4 row-cols-md-6 g-1" style="max-height: 220px; overflow-y: auto;">
                                <?php foreach($lista_fotos_repetidas as $f_rep): ?>
                                    <div class="col">
                                        <div class="p-1 text-center rounded-2 border border-warning text-dark fw-bold d-flex flex-column justify-content-center align-items-center" style="font-size: 0.72rem; background-color: #fffdf0; min-height: 42px;">
                                            <span><?= htmlspecialchars($f_rep['numero']) ?></span>
                                            <span class="badge bg-warning text-dark p-0 px-1 mt-0" style="font-size: 0.62rem; font-family: monospace;">+<?= $f_rep['excedente'] ?></span>
                                        </div>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                        <?php endif; ?>
                    </div>

                </div>
            </div>

        </div>        

    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
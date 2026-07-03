<?php
// ==============================================================================
// perfil.php (Vista Pública de Álbum - V10)
// Permite a cualquier persona ver las láminas públicas de un usuario específico
// ==============================================================================
require_once 'config.php'; // Solo para la conexión a la base de datos, SIN check_login()

// 1. Capturar el usuario desde la URL (Ej: perfil.php?u=santiago26)
$user_slug = isset($_GET['u']) ? trim($_GET['u']) : '';

if (empty($user_slug)) {
    die("<h1>Error: Usuario no especificado.</h1>");
}

try {
    // 2. Buscar al usuario en la base de datos para obtener su ID real
    $stmtUser = $pdo->prepare("SELECT id, username FROM usuarios WHERE username = ?");
    $stmtUser->execute([$user_slug]);
    $usuario = $stmtUser->fetch();

    if (!$usuario) {
        die("<h1>El coleccionista '" . htmlspecialchars($user_slug) . "' no existe.</h1>");
    }

    $usuario_id = $usuario['id'];
    $username_publico = $usuario['username'];

    // 3. Traer las láminas de ESTE usuario específico (reutilizando tu consulta indexada)
    $stmt = $pdo->prepare("SELECT * FROM laminas WHERE usuario_id = ? ORDER BY id ASC");
    $stmt->execute([$usuario_id]);
    $todas_las_laminas = $stmt->fetchAll();

// MAPEO COMPLETO DE PAÍSES CON BANDERAS NATIVAS (V10)
    $paises_map = [
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
        'URU' => '🇺🇾 Selección Uruguay',
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

    $album_agrupado = [];
    foreach ($todas_las_laminas as $lamina) {
        $sigla = $lamina['equipo']; 
        $grupo_nombre = $paises_map[$sigla] ?? '🌍 Otras Naciones';
        $album_agrupado[$grupo_nombre][] = $lamina;
    }

} catch (\PDOException $e) {
    die("Error en el sistema: " . $e->getMessage());
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Álbum de <?= htmlspecialchars($username_publico) ?> - Mundial 2026</title>
    <link rel="icon" type="image/png" href="img/favicon.png">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        /* Reutiliza tus mismos estilos compactos de laminas.php */
        .card-lamina { border: 2px solid #b9b9b9; border-radius: 8px; background-color: #dfdfdf; font-size: 0.8rem; }
        .card-lamina.poseida { border-color: #198754; background-color: #f8fff9; }
        .card-lamina.repetida-1 { border-color: #ffc107; background-color: #fffdf0; }
        .card-lamina.repetida-mas { border-color: #fd7e14; background-color: #fff8f2; }
        .seccion-titulo { border-bottom: 2px solid #212529; padding-bottom: 4px; margin-top: 20px; font-size: 1rem; }
    </style>
</head>
<body class="bg-light">

    <div class="container px-2 pt-4 mb-5">
        <!-- Encabezado estilo Perfil Social -->
        <div class="card shadow-sm p-3 text-center mb-4 border-0 bg-white">
            <span class="fs-1">⚽</span>
            <h4 class="fw-bold text-dark mb-1">Álbum de <?= htmlspecialchars($username_publico) ?></h4>
            <p class="text-muted small mb-0">¡Mira qué láminas le faltan o cuáles tiene repetidas para intercambiar!</p>
            <div class="mt-2">
                <span class="badge bg-success">Leyenda: Verde = La tiene</span>
                <span class="badge bg-warning text-dark">Amarillo/Naranja = Repetida</span>
                <span class="badge bg-secondary">Gris = Le falta</span>
            </div>
        </div>

        <!-- Renderizado de la Grilla (Solo lectura, sin botones de + o -) -->
        <div id="contenedor-album-publico">
            <?php foreach ($album_agrupado as $pais => $laminas_del_pais): ?>
                <div class="mb-4">
                    <h6 class="fw-bold text-dark seccion-titulo text-uppercase"><?= $pais ?></h6>
                    <div class="row row-cols-3 row-cols-sm-4 row-cols-md-6 g-1 mt-2">
                        <?php foreach ($laminas_del_pais as $lamina): ?>
                            <?php 
                                $cant = $lamina['cantidad'];
                                $clase_color = $cant == 1 ? 'poseida' : ($cant == 2 ? 'repetida-1' : ($cant > 2 ? 'repetida-mas' : ''));
                            ?>
                            <div class="col text-center">
                                <div class="card card-lamina p-2 shadow-sm <?= $clase_color ?>">
                                    <div class="fw-bold text-primary" style="font-size: 0.75rem;"><?= htmlspecialchars($lamina['numero']) ?></div>
                                    <div class="text-muted text-truncate" style="font-size: 0.65rem;"><?= htmlspecialchars($lamina['nombre']) ?></div>
                                    <div class="mt-1 small">
                                        <strong><?= $cant > 1 ? "Repes: " . ($cant - 1) : ($cant == 1 ? "✔" : "❌") ?></strong>
                                    </div>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    </div>

</body>
</html>
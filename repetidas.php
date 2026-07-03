<?php
// ==============================================================================
// repetidas.php - Filtro Inteligente de Láminas Repetidas (Versión Corregida)
// Muestra únicamente el inventario con cantidad > 1 para facilitar intercambios
// ==============================================================================
// ==============================================================================
// repetidas.php - Filtro Inteligente de Láminas Repetidas (Versión V08-MICRO)
// Muestra únicamente el inventario agrupado por países con cantidad > 1
// ==============================================================================
require_once 'config.php';

// Validar que el usuario tenga una sesión activa (excepto en el index si es el login)
check_login();

$usuario_id = $_SESSION['usuario_id'];
$username = $_SESSION['username'];

try {
    // CONSULTA: Traer solo las láminas cuyo conteo sea mayor a 1
    $stmt = $pdo->prepare("SELECT *, (cantidad - 1) AS excedente FROM laminas WHERE usuario_id = ? AND cantidad > 1 ORDER BY id ASC");
    $stmt->execute([$usuario_id]);
    $repetidas = $stmt->fetchAll();

    // MAPEO DE PAÍSES CON BANDERAS NATIVAS
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

    // Calcular el total de monas repetidas acumuladas y agrupar por país
    $total_excedente = 0;
    $album_agrupado = [];

    foreach ($repetidas as $lamina) {
        $total_excedente += $lamina['excedente'];

        $partes = explode(' ', $lamina['numero']);
        $sigla = $partes[0];

        $grupo_nombre = $paises_map[$sigla] ?? '🌍 Otras Naciones';
        $album_agrupado[$grupo_nombre][] = $lamina;
    }

} catch (\PDOException $e) {
    die("Error al cargar las láminas repetidas: " . $e->getMessage());
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Mi Álbum Mundial - Repetidas</title>
    <link rel="icon" type="image/png" href="img/favicon.png">
    <link class="favicon-apple" rel="apple-touch-icon" href="img/favicon.png">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        /* Estructura Micro-Cromo basada en laminas.php */
        .card-lamina { border: 2px solid #ced4da; transition: all 0.2s; border-radius: 8px; background-color: #ffffff; }
        
        /* Pintar de naranja puesto que por definición todas aquí son repetidas */
        .card-lamina.tiene-repetida { border-color: #ffc107; background-color: #fffdf0; }
        .card-lamina.tiene-mas-repetidas { border-color: #fd7e14; background-color: #fff8f2; }
        
        .bg-coca-cola { background-color: #dc3545 !important; color: white !important; }
        .card-cc { border-color: #dc3545; }
        .card-cc.tiene-repetida { border-color: #ffc107; background-color: #fffdf0; }
        .card-cc.tiene-mas-repetidas { border-color: #fd7e14; background-color: #fff8f2; }
        
        .seccion-titulo { border-bottom: 2px solid #212529; padding-bottom: 4px; margin-top: 25px; font-size: 1.1rem; }
        #sin-resultados { display: none; }
        .sticky-top { top: 0; z-index: 1020; }
    </style>
</head>

<body class="bg-light">
    <?php include 'navbar.php'; ?>

    <div class="container px-2 px-sm-3 mb-5">
        
        <!-- CABECERA RESUMIDA -->
        <div class="row mb-3">
            <div class="col-12 d-flex justify-content-between align-items-center bg-white p-2 px-3 rounded shadow-sm">
                <div>
                    <h4 class="fw-bold text-dark mb-0">🔄 Mis Repetidas</h4>
                    <p class="text-muted small mb-0 d-none d-sm-block">Monas con excedentes para intercambio.</p>
                </div>
                <div class="text-end">
                    <span class="fs-4 fw-bold text-orange" style="color: #fd7e14;"><?= $total_excedente ?></span>
                    <p class="text-muted mb-0 fw-bold" style="font-size: 0.75rem;">Para Negocio</p>
                </div>
            </div>
        </div>

        <!-- BUSCADOR COMPACTO ASÍNCRONO -->
        <div class="row justify-content-center mb-3 sticky-top pt-2 pb-2 bg-light shadow-sm rounded">
            <div class="col-md-6 col-12">
                <div class="input-group input-group-sm">
                    <span class="input-group-text bg-white border-end-0 fs-6">🔍</span>
                    <input type="text" 
                           id="txtBuscador" 
                           class="form-control border-start-0 fs-6 shadow-none" 
                           placeholder="Buscar repetida (Ej: ARG 10 o Messi)..."
                           autocomplete="off">
                    <button class="btn btn-outline-secondary" type="button" id="btn-limpiar-busqueda" style="display:none;">❌</button>
                </div>
            </div>
        </div>

        <!-- ALERTA DE SIN RESULTADOS -->
        <div id="sin-resultados" class="row my-4">
            <div class="col-12 text-center">
                <div class="alert alert-warning d-inline-block p-3 rounded shadow-sm mb-0">
                    <span class="fs-4">⚠️</span>
                    <h6 class="fw-bold mt-1 mb-0" style="font-size: 0.9rem;">No hay repetidas con ese filtro</h6>
                </div>
            </div>
        </div>

        <!-- LISTADO AGRUPADO POR EQUIPOS -->
        <div id="contenedor-album">
            <?php if (empty($repetidas)): ?>
                <div class="text-center bg-white rounded shadow-sm py-5 px-3">
                    <span class="fs-1">😎</span>
                    <h5 class="mt-3 fw-bold text-secondary">¡No tienes láminas repetidas aún!</h5>
                    <p class="text-muted small">Al subir la cantidad a más de 1 en el panel general, aparecerán aquí.</p>
                    <a href="laminas.php" class="btn btn-primary btn-sm fw-bold mt-1">Ir a mis láminas</a>
                </div>
            <?php else: ?>
                
                <?php foreach ($album_agrupado as $pais => $laminas_del_pais): ?>
                    <?php $es_cc = (strpos($pais, 'Coca-Cola') !== false); ?>

                    <div class="bloque-pais-seccion mb-3">
                        
                        <!-- Título del Equipo -->
                        <div class="row mb-2 seccion-titulo-row">
                            <div class="col-12">
                                <?php if ($es_cc): ?>
                                    <div class="p-2 bg-coca-cola rounded shadow-sm d-flex align-items-center mt-3">
                                        <span class="fs-6 me-2">🥤</span>
                                        <h6 class="fw-bold mb-0" style="font-size: 0.9rem;"><?= $pais ?></h6>
                                    </div>
                                <?php else: ?>
                                    <h6 class="fw-bold text-dark seccion-titulo text-uppercase"><?= $pais ?></h6>
                                <?php endif; ?>
                            </div>
                        </div>

                        <!-- GRILLA DE MINICROMOS REPETIDOS -->
                        <div class="row row-cols-2 row-cols-sm-3 row-cols-md-4 row-cols-lg-6 g-1 grilla-tarjetas-row">
                            <?php foreach ($laminas_del_pais as $reg): ?>
                                <?php 
                                    $excedente = $reg['excedente'];
                                    $clase_color = ($excedente == 1) ? 'tiene-repetida' : 'tiene-mas-repetidas';
                                ?>
                                
                                <div class="col tarjeta-item-col" 
                                     data-nombre="<?= strtolower(htmlspecialchars($reg['nombre'])) ?>" 
                                     data-numero="<?= strtolower(htmlspecialchars($reg['numero'])) ?>">
                                    
                                    <div class="card card-lamina h-100 p-1 shadow-sm <?= $es_cc ? 'card-cc' : '' ?> <?= $clase_color ?>" style="min-height: 90px; border-radius: 8px;">
                                        
                                        <!-- Header de la tarjeta -->
                                        <div class="d-flex justify-content-between align-items-center mb-1">
                                            <span class="badge <?= $es_cc ? 'bg-danger' : 'bg-primary' ?>" style="font-size: 0.68rem; padding: 2px 4px;">
                                                <?= htmlspecialchars($reg['numero']) ?>
                                            </span>
                                            <?php if ($es_cc): ?>
                                                <span class="text-danger fw-bold" style="font-size: 0.6rem;">🥤</span>
                                            <?php elseif ($reg['es_especial']): ?>
                                                <span class="text-warning fw-bold" style="font-size: 0.65rem;">⭐</span>
                                            <?php endif; ?>
                                        </div>
                                        
                                        <!-- Nombre del Jugador -->
                                        <h6 class="fw-bold text-dark mb-1 text-truncate nombre-jugador-txt" 
                                            style="font-size: 0.75rem; line-height: 1.1; letter-spacing: -0.3px;" 
                                            title="<?= htmlspecialchars($reg['nombre']) ?>">
                                            <?= htmlspecialchars($reg['nombre']) ?>
                                        </h6>
                                        
                                        <!-- Footer: Indicador de Excedente Horizontal -->
                                        <div class="d-flex align-items-center justify-content-between mt-auto bg-dark text-white rounded-2 px-2 py-1" style="font-size: 0.72rem;">
                                            <span class="text-light-50" style="font-size: 0.65rem;">Sobra:</span>
                                            <strong class="text-warning" style="font-size: 0.9rem; font-family: monospace;">+<?= $excedente ?></strong>
                                        </div>

                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    </div>
                <?php endforeach; ?>

            <?php endif; ?>
        </div>
    </div>

    <!-- MOTOR DE FILTRADO COMPACTO EN TIEMPO REAL -->
    <script>
    document.addEventListener('DOMContentLoaded', function () {
        const buscador = document.getElementById('txtBuscador');
        const btnLimpiar = document.getElementById('btn-limpiar-busqueda');
        const seccionesPaises = document.querySelectorAll('.bloque-pais-seccion');
        const alertaSinResultados = document.getElementById('sin-resultados');

        buscador.addEventListener('input', function() {
            const termino = this.value.trim().toLowerCase();
            let totalVisiblesGlobal = 0;

            if (termino.length > 0) {
                if(btnLimpiar) btnLimpiar.style.display = 'block';
            } else {
                if(btnLimpiar) btnLimpiar.style.display = 'none';
            }

            seccionesPaises.forEach(seccion => {
                const tarjetas = seccion.querySelectorAll('.tarjeta-item-col');
                let visiblesEnPais = 0;

                tarjetas.forEach(tarjeta => {
                    const nombre = tarjeta.getAttribute('data-nombre');
                    const numero = tarjeta.getAttribute('data-numero');

                    if (nombre.includes(termino) || numero.includes(termino)) {
                        tarjeta.style.setProperty('display', 'block', 'important');
                        visiblesEnPais++;
                        totalVisiblesGlobal++;
                    } else {
                        tarjeta.style.setProperty('display', 'none', 'important');
                    }
                });

                if (visiblesEnPais === 0 && termino.length > 0) {
                    seccion.style.display = 'none';
                } else {
                    seccion.style.display = 'block';
                }
            });

            if (totalVisiblesGlobal === 0 && termino.length > 0) {
                alertaSinResultados.style.display = 'block';
            } else {
                alertaSinResultados.style.display = 'none';
            }
        });
    });
    </script>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
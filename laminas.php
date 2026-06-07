<?php
// ==============================================================================
// laminas.php (Versión Completa con Buscador Asíncrono en Tiempo Real)
// Gestión de Inventario Organizado por Países con Filtro de Búsqueda Rápida
// ==============================================================================
require_once 'config.php';

// Validar sesión activa en Laragon
check_login();

$usuario_id = $_SESSION['usuario_id'];
$username = $_SESSION['username'];

try {
    // CONSULTA GENERAL DE LÁMINAS DEL USUARIO
    $stmt = $pdo->prepare("SELECT * FROM laminas WHERE usuario_id = ? ORDER BY id ASC");
    $stmt->execute([$usuario_id]);
    $todas_las_laminas = $stmt->fetchAll();

    // MAPEO DE PAÍSES CON BANDERAS NATIVAS
    $paises_map = [
        'FWC' => '🏆 Especiales y Estadios Panini',
        'COL' => '🇨🇴 Selección Colombia',
        'ARG' => '🇦🇷 Selección Argentina',
        'BRA' => '🇧🇷 Selección Brasil',
        'POR' => '🇵🇹 Selección Portugal',
        'FRA' => '🇫🇷 Selección Francia',
        'MEX' => '🇲🇽 Selección México',
        'USA' => '🇺🇸 Selección Estados Unidos',
        'GER' => '🇩🇪 Selección Alemania',
        'ESP' => '🇪🇸 Selección España',
        'ITA' => '🇮🇹 Selección Italia',
        'ENG' => '🏴' . "󠁢󠁥󠁮󠁧󠁿" . ' Selección Inglaterra', // Fix sintaxis string largo
        'CC'  => '🥤 Sección Especial Coca-Cola'
    ];

    // AGRUPACIÓN EN ARREGLO MULTIDIMENSIONAL
    $album_agrupado = [];
    foreach ($todas_las_laminas as $lamina) {
        $partes = explode(' ', $lamina['numero']);
        $sigla = $partes[0];

        $grupo_nombre = $paises_map[$sigla] ?? '🌍 Otras Naciones';
        $album_agrupado[$grupo_nombre][] = $lamina;
    }

} catch (\PDOException $e) {
    die("Error al cargar el álbum: " . $e->getMessage());
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Mi Álbum - Collector</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        .card-lamina { border: 2px solid #dee2e6; transition: all 0.2s; border-radius: 12px; }
        
        /* 🟩 COLOR VERDE: Tienes exactamente 1 (Pegada) */
        .card-lamina.poseida { border-color: #198754; background-color: #f8fff9; }
        
        /* 🟨 COLOR AMARILLO: Tienes exactamente 2 (1 repetida) */
        .card-lamina.repetida-1 { border-color: #ffc107; background-color: #fffdf0; }
        
        /* 🟧 COLOR NARANJA: Tienes 3 o más (Múltiples repetidas) */
        .card-lamina.repetida-mas { border-color: #fd7e14; background-color: #fff8f2; }
        
        /* Estilos base para las tarjetas de Coca-Cola */
        .bg-coca-cola { background-color: #dc3545 !important; color: white !important; }
        .card-cc { border-color: #dc3545; }
        .card-cc.poseida { border-color: #dc3545; background-color: #fff5f5; }
        
        /* Sobrescribir repetidas de Coca-Cola para que hereden el amarillo/naranja global */
        .card-cc.repetida-1 { border-color: #ffc107; background-color: #fffdf0; }
        .card-cc.repetida-mas { border-color: #fd7e14; background-color: #fff8f2; }
        
        .numero-badge { font-size: 0.95rem; font-weight: bold; padding: 6px 10px; }
        .seccion-titulo { border-bottom: 3px solid #212529; padding-bottom: 6px; margin-top: 45px; }
        
        /* Estilo para destacar cuando no hay resultados de búsqueda */
        #sin-resultados { display: none; }
    </style>
</head>
<body class="bg-light">

    <nav class="navbar navbar-expand-lg navbar-dark bg-dark shadow-sm mb-4">
        <div class="container">
            <a class="navbar-brand fw-bold" href="index.php">⚽ Álbum Mundial 2026</a>
            <div class="navbar-nav ms-auto">
                <a class="btn btn-sm btn-outline-light me-2 fw-bold" href="index.php">🏠 Volver al Home</a>
                <a class="btn btn-sm btn-outline-danger fw-bold" href="logout.php">Cerrar Sesión</a>
            </div>
        </div>
    </nav>

    <div class="container mb-5">
        <div class="row mb-4">
            <div class="col-12 text-center">
                <h2 class="fw-bold text-dark">📋 Mi Álbum de Láminas</h2>
                <p class="text-muted">Gestiona tus monas en tiempo real. ¡Usa el buscador para filtrar al instante!</p>
            </div>
        </div>

        <div class="row justify-content-center mb-4 sticky-top pt-2 pb-3 bg-light shadow-sm rounded">
            <div class="col-md-6 col-12">
                <div class="input-group">
                    <span class="input-group-text bg-white border-end-0 fs-5">🔍</span>
                    <input type="text" 
                           id="buscador-laminas" 
                           class="form-control form-control-lg border-start-0 fs-6 shadow-none" 
                           placeholder="Buscar por nombre o número (Ej: James, COL 2, Lamine)..."
                           autocomplete="off">
                    <button class="btn btn-outline-secondary" type="button" id="btn-limpiar-busqueda" style="display:none;">❌</button>
                </div>
            </div>
        </div>

        <div id="sin-resultados" class="row my-5">
            <div class="col-12 text-center">
                <div class="alert alert-warning d-inline-block p-4 rounded-3 shadow-sm">
                    <span class="fs-2">⚠️</span>
                    <h5 class="fw-bold mt-2 mb-1">No se encontraron láminas</h5>
                    <p class="text-muted small mb-0">Prueba verificando la ortografía o el número de la mona.</p>
                </div>
            </div>
        </div>

        <div id="contenedor-album">
            <?php foreach ($album_agrupado as $pais => $laminas_del_pais): ?>
                <?php $es_cc = (strpos($pais, 'Coca-Cola') !== false); ?>

                <div class="bloque-pais-seccion mb-4">
                    
                    <div class="row mb-3 seccion-titulo-row">
                        <div class="col-12">
                            <?php if ($es_cc): ?>
                                <div class="p-3 bg-coca-cola rounded shadow-sm d-flex align-items-center mt-4">
                                    <span class="fs-3 me-2">🥤</span>
                                    <h4 class="fw-bold mb-0"><?= $pais ?></h4>
                                </div>
                            <?php else: ?>
                                <h4 class="fw-bold text-dark seccion-titulo text-uppercase"><?= $pais ?></h4>
                            <?php endif; ?>
                        </div>
                    </div>

                    <div class="row g-3 grilla-tarjetas-row">
                        <?php foreach ($laminas_del_pais as $lamina): ?>
                            <?php 
                                $cant = $lamina['cantidad'];
                                
                                // Lógica PHP inicial para pintar el color correcto al cargar la página
                                $clase_color = '';
                                if ($cant == 1) {
                                    $clase_color = 'poseida';
                                } elseif ($cant == 2) {
                                    $clase_color = 'repetida-1';
                                } elseif ($cant > 2) {
                                    $clase_color = 'repetida-mas';
                                }
                            ?>
                            
                            <div class="col-12 col-sm-6 col-md-4 col-lg-3 tarjeta-item-col" 
                                 data-nombre="<?= strtolower(htmlspecialchars($lamina['nombre'])) ?>" 
                                 data-numero="<?= strtolower(htmlspecialchars($lamina['numero'])) ?>">
                                
                                <div class="card card-lamina h-100 p-3 shadow-sm <?= $es_cc ? 'card-cc' : '' ?> <?= $clase_color ?>">
                                    
                                    <div class="d-flex justify-content-between align-items-start mb-2">
                                        <span class="badge <?= $es_cc ? 'bg-danger' : 'bg-primary' ?> numero-badge">
                                            <?= htmlspecialchars($lamina['numero']) ?>
                                        </span>
                                        <?php if ($es_cc): ?>
                                            <span class="badge bg-dark fw-bold text-warning">🥤 Exclusiva</span>
                                        <?php elseif ($lamina['es_especial']): ?>
                                            <span class="badge bg-warning text-dark fw-bold">⭐ Especial</span>
                                        <?php endif; ?>
                                    </div>
                                    
                                    <h6 class="fw-bold text-dark mb-1 text-truncate nombre-jugador-txt" title="<?= htmlspecialchars($lamina['nombre']) ?>">
                                        <?= htmlspecialchars($lamina['nombre']) ?>
                                    </h6>
                                    
                                    <p class="text-muted small mb-3">Tienes: <strong class="fs-5 text-dark txt-cantidad"><?= $cant ?></strong></p>
                                    
                                    <div class="d-flex gap-2 mt-auto">
                                        <button type="button" 
                                                class="btn btn-sm btn-outline-secondary w-50 fw-bold btn-restar" 
                                                data-id="<?= $lamina['id'] ?>" 
                                                <?= ($cant == 0) ? 'disabled' : '' ?>>-</button>
                                        
                                        <button type="button" 
                                                class="btn btn-sm <?= $es_cc ? 'btn-danger' : 'btn-success' ?> w-50 fw-bold btn-sumar" 
                                                data-id="<?= $lamina['id'] ?>">+</button>
                                    </div>

                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    </div>

    <script>
    document.addEventListener('DOMContentLoaded', function () {
        
        // --- 1. LÓGICA DEL MOTOR DE BÚSQUEDA EN TIEMPO REAL ---
        const buscador = document.getElementById('buscador-laminas');
        const btnLimpiar = document.getElementById('btn-limpiar-busqueda');
        const seccionesPaises = document.querySelectorAll('.bloque-pais-seccion');
        const alertaSinResultados = document.getElementById('sin-resultados');

        buscador.addEventListener('input', function() {
            const termino = this.value.trim().toLowerCase();
            let totalVisiblesGlobal = 0;

            // Mostrar/Ocultar botón de limpiar (X)
            if (termino.length > 0) {
                btnLimpiar.style.display = 'block';
            } else {
                btnLimpiar.style.display = 'none';
            }

            seccionesPaises.forEach(seccion => {
                const tarjetas = seccion.querySelectorAll('.tarjeta-item-col');
                let visiblesEnPais = 0;

                tarjetas.forEach(tarjeta => {
                    const nombre = tarjeta.getAttribute('data-nombre');
                    const numero = tarjeta.getAttribute('data-numero');

                    // Si el término coincide con el nombre o el número de la mona...
                    if (nombre.includes(termino) || numero.includes(termino)) {
                        tarjeta.style.setProperty('display', 'block', 'important');
                        visiblesEnPais++;
                        totalVisiblesGlobal++;
                    } else {
                        tarjeta.style.setProperty('display', 'none', 'important');
                    }
                });

                // Si ninguna tarjeta de este país coincide, ocultamos todo el bloque (incluyendo la bandera)
                if (visiblesEnPais === 0 && termino.length > 0) {
                    seccion.style.display = 'none';
                } else {
                    seccion.style.display = 'block';
                }
            });

            // Si no se encuentra absolutamente nada en todo el álbum, activamos la alerta de error
            if (totalVisiblesGlobal === 0 && termino.length > 0) {
                alertaSinResultados.style.display = 'block';
            } else {
                alertaSinResultados.style.display = 'none';
            }
        });

        // Evento para el botón limpiar (X)
        btnLimpiar.addEventListener('click', function() {
            buscador.value = '';
            buscador.dispatchEvent(new Event('input')); // Dispara el filtro para restablecer todo
            buscador.focus();
        });


        // --- 2. LÓGICA ASÍNCRONA (AJAX) DE SUMAR/RESTAR YA CONFIGURADA ---
        function ejecutarCambio(id, accion, botonOriginal) {
            const tarjeta = botonOriginal.closest('.card-lamina');
            const txtCantidad = tarjeta.querySelector('.txt-cantidad');
            const btnRestar = tarjeta.querySelector('.btn-restar');
            
            const formData = new FormData();
            formData.append('id', id);
            formData.append('accion', accion);

            fetch('actualizar_lamina.php', {
                method: 'POST',
                body: formData
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    const nuevaCant = data.nueva_cantidad;
                    
                    txtCantidad.textContent = nuevaCant;

                    if (nuevaCant === 0) {
                        btnRestar.setAttribute('disabled', 'disabled');
                    } else {
                        btnRestar.removeAttribute('disabled');
                    }

                    tarjeta.classList.remove('poseida', 'repetida-1', 'repetida-mas');
                    
                    if (nuevaCant === 1) {
                        tarjeta.classList.add('poseida');       
                    } else if (nuevaCant === 2) {
                        tarjeta.classList.add('repetida-1');     
                    } else if (nuevaCant > 2) {
                        tarjeta.classList.add('repetida-mas');   
                    }
                }
            })
            .catch(err => console.error('Error en procesamiento AJAX:', err));
        }

        document.querySelectorAll('.btn-sumar').forEach(btn => {
            btn.addEventListener('click', function() {
                const id = this.getAttribute('data-id');
                ejecutarCambio(id, 'sumar', this);
            });
        });

        document.querySelectorAll('.btn-restar').forEach(btn => {
            btn.addEventListener('click', function() {
                const id = this.getAttribute('data-id');
                ejecutarCambio(id, 'restar', this);
            });
        });
    });
    </script>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
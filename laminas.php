<?php
// ==============================================================================
// laminas.php (Versión Completa con Buscador Asíncrono en Tiempo Real)
// Gestión de Inventario Organizado por Países con Filtro de Búsqueda Rápida
// ==============================================================================
// ==============================================================================
// laminas.php (Versión V08 - Grilla Compacta y Mobile-First)
// Gestión de Inventario con Filtro en Tiempo Real, AJAX y Diseño Optimizado para Celulares
// ==============================================================================
// ==============================================================================
// laminas.php (Versión V08-MICRO - Alto Rendimiento)
// Gestión de Inventario con Filtro Asíncrono y Agrupación Indexada por Base de Datos
// ==============================================================================
// ==============================================================================
// laminas.php (Versión V09-10 - Arquitectura Híbrida de Alto Rendimiento)
// Carga Asíncrona bajo demanda (Infinite Scroll) con Motor de Búsqueda Debounce
// ==============================================================================
require_once 'config.php';

// Validar sesión activa en Laragon
check_login();

$usuario_id = $_SESSION['usuario_id'];
$username = $_SESSION['username'];
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Mi Álbum Mundial - Láminas</title>
    <link rel="icon" type="image/png" href="img/favicon.png">
    <link class="favicon-apple" rel="apple-touch-icon" href="img/favicon.png">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        /* Base por defecto en gris para las faltantes (cantidad 0) */
        .card-lamina { border: 2px solid #b9b9b9; transition: all 0.2s; border-radius: 8px; background-color: #dfdfdf; }
        
        /* 🟩 COLOR VERDE: Tienes exactamente 1 (Pegada) */
        .card-lamina.poseida { border-color: #198754; background-color: #f8fff9; }
        
        /* 🟨 COLOR AMARILLO: Tienes exactamente 2 (1 repetida) */
        .card-lamina.repetida-1 { border-color: #ffc107; background-color: #fffdf0; }
        
        /* 🟧 COLOR NARANJA: Tienes 3 o más (Múltiples repetidas) */
        .card-lamina.repetida-mas { border-color: #fd7e14; background-color: #fff8f2; }
        
        /* Estilos para Coca-Cola */
        .bg-coca-cola { background-color: #dc3545 !important; color: white !important; }
        .card-cc { border-color: #dc3545; }
        .card-cc.poseida { border-color: #dc3545; background-color: #fff5f5; }
        .card-cc.repetida-1 { border-color: #ffc107; background-color: #fffdf0; }
        .card-cc.repetida-mas { border-color: #fd7e14; background-color: #fff8f2; }
        
        .seccion-titulo { border-bottom: 2px solid #212529; padding-bottom: 4px; margin-top: 25px; font-size: 1.1rem; }
        #sin-resultados { display: none; }
        .sticky-top { top: 0; z-index: 1020; }
    </style>
</head>

<body class="bg-light">
    <?php include 'navbar.php'; ?>

    <div class="container px-2 px-sm-3 mb-5">
        <div class="row mb-3">
            <div class="col-12 text-center">
                <h3 class="fw-bold text-dark mb-1">📋 Mi Álbum de Láminas</h3>
                <p class="text-muted small mb-0">Gestiona tus monas en tiempo real.</p>
            </div>
        </div>

        <div class="row justify-content-center mb-3 sticky-top pt-2 pb-2 bg-light shadow-sm rounded">
            <div class="col-md-6 col-12">
                <div class="input-group input-group-sm">
                    <span class="input-group-text bg-white border-end-0 fs-6">🔍</span>
                    <input type="text" 
                           id="buscador-laminas" 
                           class="form-control border-start-0 fs-6 shadow-none" 
                           placeholder="Buscar jugador o número (Ej: COL 10)..."
                           autocomplete="off">
                    <button class="btn btn-outline-secondary" type="button" id="btn-limpiar-busqueda" style="display:none;">❌</button>
                </div>
            </div>
        </div>

        <div id="sin-resultados" class="row my-4">
            <div class="col-12 text-center">
                <div class="alert alert-warning d-inline-block p-3 rounded shadow-sm mb-0">
                    <span class="fs-4">⚠️</span>
                    <h6 class="fw-bold mt-1 mb-0" style="font-size: 0.9rem;">No se encontraron láminas</h6>
                </div>
            </div>
        </div>
        
        <div class="d-flex justify-content-end mb-3">
            <button id="btn-candado" class="btn btn-danger fw-bold d-flex align-items-center gap-2 shadow-sm" onclick="alternarCandado()">
                <span id="icono-candado">🔒</span> 
                <span id="texto-candado">Álbum Bloqueado</span>
            </button>
        </div>

        <!-- ⚡ CONTENEDOR DE ALTO RENDIMIENTO (V09) -->
        <div id="contenedor-album">
            <!-- Los bloques de países y grillas se inyectarán de forma dinámica aquí -->
        </div>

        <!-- Spinner indicador para cuando vas bajando en el celular -->
        <div id="cargando-spinner" class="text-center my-4" style="display: none;">
            <div class="spinner-border text-success spinner-border-sm" role="status"></div>
            <p class="text-muted small mt-1" style="font-size: 0.75rem;">Cargando más láminas...</p>
        </div>
    </div>

    <script>
    // Mapeo global de títulos de países para renderizar las cabeceras al vuelo
    const paisesMap = {
        'FWC': '🏆 Especiales y Estadios Panini', 'CAN': '🇨🇦 Selección Canadá', 'USA': '🇺🇸 Selección Estados Unidos', 'MEX': '🇲🇽 Selección México',
        'ARG': '🇦🇷 Selección Argentina', 'BRA': '🇧🇷 Selección Brasil', 'COL': '🇨🇴 Selección Colombia', 'ECU': '🇪🇨 Selección Ecuador',
        'PAR': '🇵🇾 Selección Paraguay', 'URU': '🇺🇾 Selección Uruguay', 'HAI': '🇭🇹 Selección Haití', 'CUW': '🇨🇼 Selección Curazao', 'PAN': '🇵🇦 Selección Panama',
        'GER': '🇩🇪 Selección Alemania', 'AUT': '🇦🇹 Selección Austria', 'BEL': '🇧🇪 Selección Bélgica', 'CRO': '🇭🇷 Selección Croacia',
        'SCO': '🏴󠁧󠁢󠁳󠁣󠁴󠁿 Selección Escocia', 'ESP': '🇪🇸 Selección España', 'FRA': '🇫🇷 Selección Francia', 'ENG': '🏴󠁧󠁢󠁥󠁮󠁧󠁿 Selección Inglaterra',
        'NOR': '🇳🇴 Selección Noruega', 'NED': '🇳🇱 Selección Países Bajos', 'POR' : '🇵🇹 Selección Portugal', 'SUI': '🇨🇭 Selección Suiza',
        'SWE': '🇸🇪 Selección Suecia', 'CZE': '🇨🇿 Selección Chequia', 'TUR': '🇹🇷 Selección Turquía', 'BIH': '🇧🇦 Selección Bosnia y H.',
        'ALG': '🇩🇿 Selección Argelia', 'CPV': '🇨🇻 Selección Cabo Verde', 'CIV': '🇨🇮 Selección Costa de Marfil', 'EGY': '🇪🇬 Selección Egipto',
        'GHA': '🇬🇭 Selección Ghana', 'MAR': '🇲🇦 Selección Marruecos', 'SEN': '🇸🇳 Selección Senegal', 'RSA': '🇿🇦 Selección Sudáfrica',
        'TUN': '🇹🇳 Selección Túnez', 'COD': '🇨🇩 Selección RD Congo', 'KSA': '🇸🇦 Selección Arabia S.', 'AUS': '🇦🇺 Selección Australia',
        'KOR': '🇰🇷 Selección Corea del Sur', 'IRQ': '🇮🇶 Selección Irak/EAU', 'IRN': '🇮🇷 Selección Irán', 'JPN': '🇯🇵 Selección Japón',
        'JOR': '🇯🇴 Selección Jordania', 'QAT': '🇶🇦 Selección Qatar', 'UZB': '🇺🇿 Selección Uzbekistán', 'NZL': '🇳🇿 Selección Nueva Zelanda',
        'CC': '🥤 Sección Especial Coca-Cola'
    };

    let offset = 0;
    const limite = 48; // Bloques ideales para grillas de responsive
    let cargando = false;
    let finDeLaminas = false;
    let timerBusqueda;
    let ultimoPaisRenderizado = "";

    // --- CONTROL DE INICIALIZACIÓN ---
    document.addEventListener('DOMContentLoaded', function () {
        
        // Primera carga inmediata
        cargarMasLaminas(true);

        // Detectar scroll en el móvil
        window.addEventListener('scroll', () => {
            if (window.innerHeight + window.scrollY >= document.body.offsetHeight - 600 && !cargando && !finDeLaminas) {
                cargarMasLaminas(false);
            }
        });

        const buscador = document.getElementById('buscador-laminas');
        const btnLimpiar = document.getElementById('btn-limpiar-busqueda');

        // Lógica del Buscador con DEBOUNCE
        if (buscador) {
            buscador.addEventListener('input', function() {
                const termino = this.value.trim();
                
                if (termino.length > 0) {
                    btnLimpiar.style.display = 'block';
                } else {
                    btnLimpiar.style.display = 'none';
                }

                clearTimeout(timerBusqueda);
                timerBusqueda = setTimeout(() => {
                    cargarMasLaminas(true); // Reinicia todo y busca desde cero
                }, 400); 
            });
        }

        if (btnLimpiar) {
            btnLimpiar.addEventListener('click', function() {
                buscador.value = '';
                buscador.dispatchEvent(new Event('input'));
                buscador.focus();
            });
        }

        // 🔒 Inicializar Estado del Candado
        const estadoBloqueo = localStorage.getItem('album_bloqueado');
        if (estadoBloqueo === null || estadoBloqueo === 'true') {
            bloquearControles();
        } else {
            desbloquearControles();
        }
    });

    // --- MOTOR ASÍNCRONO DE CARGA (V09) ---
    function cargarMasLaminas(reiniciarContenedor = false) {
        if (cargando) return;
        cargando = true;
        document.getElementById('cargando-spinner').style.display = 'block';
        document.getElementById('sin-resultados').style.display = 'none';

        if (reiniciarContenedor) {
            offset = 0;
            finDeLaminas = false;
            ultimoPaisRenderizado = "";
            document.getElementById('contenedor-album').innerHTML = '';
        }

        const buscador = document.getElementById('buscador-laminas');
        const termino = buscador ? buscador.value.trim() : '';

        fetch(`obtener_laminas.php?limite=${limite}&offset=${offset}&buscar=${encodeURIComponent(termino)}`)
            .then(res => res.json())
            .then(data => {
                if (data.success && data.laminas.length > 0) {
                    const contenedorAlbum = document.getElementById('contenedor-album');
                    
                    data.laminas.forEach(lamina => {
                        const sigla = lamina.equipo;
                        const esCc = (sigla === 'CC');
                        const nombrePais = paisesMap[sigla] || '🌍 Otras Naciones';

                        // Generar el bloque del país si cambió con respecto a la iteración anterior
                        if (ultimoPaisRenderizado !== nombrePais) {
                            ultimoPaisRenderizado = nombrePais;
                            
                            let cabeceraHtml = '';
                            if (esCc) {
                                cabeceraHtml = `
                                    <div class="bloque-pais-seccion mb-3" data-pais-codigo="${sigla}">
                                        <div class="row mb-2 seccion-titulo-row">
                                            <div class="col-12">
                                                <div class="p-2 bg-coca-cola rounded shadow-sm d-flex align-items-center mt-3">
                                                    <span class="fs-6 me-2">🥤</span>
                                                    <h6 class="fw-bold mb-0" style="font-size: 0.9rem;">${nombrePais}</h6>
                                                </div>
                                            </div>
                                        </div>
                                        <div class="row row-cols-2 row-cols-sm-3 row-cols-md-4 row-cols-lg-6 g-1 grilla-tarjetas-row"></div>
                                    </div>`;
                            } else {
                                cabeceraHtml = `
                                    <div class="bloque-pais-seccion mb-3" data-pais-codigo="${sigla}">
                                        <div class="row mb-2 seccion-titulo-row">
                                            <div class="col-12">
                                                <h6 class="fw-bold text-dark seccion-titulo text-uppercase">${nombrePais}</h6>
                                            </div>
                                        </div>
                                        <div class="row row-cols-2 row-cols-sm-3 row-cols-md-4 row-cols-lg-6 g-1 grilla-tarjetas-row"></div>
                                    </div>`;
                            }
                            contenedorAlbum.insertAdjacentHTML('beforeend', cabeceraHtml);
                        }

                        // Localizar la grilla activa del país correspondiente
                        const bloquesActivos = contenedorAlbum.querySelectorAll(`[data-pais-codigo="${sigla}"] .grilla-tarjetas-row`);
                        const grillaDestino = bloquesActivos[bloquesActivos.length - 1];

                        const cant = parseInt(lamina.cantidad);
                        let claseColor = '';
                        if (cant === 1) claseColor = 'poseida';
                        else if (cant === 2) claseColor = 'repetida-1';
                        else if (cant > 2) claseColor = 'repetida-mas';

                        const iconoDecorativo = esCc ? '<span class="text-danger fw-bold" style="font-size: 0.6rem;">🥤</span>' : (parseInt(lamina.es_especial) ? '<span class="text-warning fw-bold" style="font-size: 0.65rem;">⭐</span>' : '');

                        const tarjetaHtml = `
                            <div class="col tarjeta-item-col">
                                <div class="card card-lamina h-100 p-1 shadow-sm ${esCc ? 'card-cc' : ''} ${claseColor}" style="min-height: 95px; border-radius: 8px;">
                                    <div class="d-flex justify-content-between align-items-center mb-1">
                                        <span class="badge ${esCc ? 'bg-danger' : 'bg-primary'}" style="font-size: 0.7rem; padding: 2px 4px;">
                                            ${lamina.numero}
                                        </span>
                                        ${iconoDecorativo}
                                    </div>
                                    <h6 class="fw-bold text-dark mb-1 text-truncate nombre-jugador-txt" 
                                        style="font-size: 0.75rem; line-height: 1.1; letter-spacing: -0.3px;" 
                                        title="${lamina.nombre}">
                                        ${lamina.nombre}
                                    </h6>
                                    <div class="d-flex align-items-center justify-content-between mt-auto bg-light rounded-2 p-1" style="border: 1px solid #dee2e6;">
                                        <button type="button" 
                                                class="btn btn-sm btn-outline-secondary p-0 fw-bold btn-restar" 
                                                style="width: 20px; height: 20px; font-size: 0.75rem; line-height: 1;"
                                                ${cant === 0 ? 'disabled' : ''} 
                                                onclick="ejecutarCambio(${lamina.id}, 'restar', this)">-</button>
                                        <strong class="text-dark txt-cantidad" style="font-size: 0.95rem; font-family: monospace;">${cant}</strong>
                                        <button type="button" 
                                                class="btn btn-sm ${esCc ? 'btn-danger' : 'btn-success'} p-0 fw-bold btn-sumar" 
                                                style="width: 20px; height: 20px; font-size: 0.75rem; line-height: 1;"
                                                onclick="ejecutarCambio(${lamina.id}, 'sumar', this)">+</button>
                                    </div>
                                </div>
                            </div>`;
                        
                        if(grillaDestino) {
                            grillaDestino.insertAdjacentHTML('beforeend', tarjetaHtml);
                        }
                    });

                    // Si vinieron menos elementos del límite, frenamos el infinite scroll
                    if (data.laminas.length < limite) {
                        finDeLaminas = true;
                    }
                    offset += limite;

                    // Ajustar la opacidad del candado sobre las nuevas tarjetas añadidas
                    const modoBloqueo = localStorage.getItem('album_bloqueado') === 'true';
                    ajustarOpacidadBotones(modoBloqueo ? 0.5 : 1);

                } else {
                    finDeLaminas = true;
                    if (reiniciarContenedor) {
                        document.getElementById('sin-resultados').style.display = 'block';
                    }
                }
            })
            .catch(err => console.error("Error al procesar flujo:", err))
            .finally(() => {
                cargando = false;
                document.getElementById('cargando-spinner').style.display = 'none';
            });
    }

    // --- FUNCIONES DE CONTROL DEL CANDADO (GLOBALES) ---
    function alternarCandado() {
        const estaBloqueo = localStorage.getItem('album_bloqueado') === 'true';
        if (estaBloqueo) {
            desbloquearControles();
        } else {
            bloquearControles();
        }
    }

    function bloquearControles() {
        localStorage.setItem('album_bloqueado', 'true');
        const btn = document.getElementById('btn-candado');
        if (btn) {
            btn.className = "btn btn-danger fw-bold d-flex align-items-center gap-2 shadow-sm";
            document.getElementById('icono-candado').innerText = "🔒";
            document.getElementById('texto-candado').innerText = "Álbum Bloqueado";
        }
        ajustarOpacidadBotones(0.5);
    }

    function desbloquearControles() {
        localStorage.setItem('album_bloqueado', 'false');
        const btn = document.getElementById('btn-candado');
        if (btn) {
            btn.className = "btn btn-success fw-bold d-flex align-items-center gap-2 shadow-sm";
            document.getElementById('icono-candado').innerText = "🔓";
            document.getElementById('texto-candado').innerText = "Modo Edición Activo";
        }
        ajustarOpacidadBotones(1);
    }

    function ajustarOpacidadBotones(valor) {
        const botonesAC = document.querySelectorAll('.btn-sumar, .btn-restar');
        botonesAC.forEach(b => {
            b.style.opacity = valor;
        });
    }

    // --- PROCESAMIENTO AJAX PROTEGIDO ---
    function ejecutarCambio(id, accion, botonOriginal) {
        if (localStorage.getItem('album_bloqueado') === 'true') {
            alert("🚨 El álbum está bloqueado. Desactiva el candado superior para realizar modificaciones.");
            return;
        }

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
    </script>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
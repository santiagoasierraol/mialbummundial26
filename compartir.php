<?php
// ==============================================================================
// compartir.php (Versión V10-SHARE - Alto Rendimiento)
// Listar y copiar láminas para WhatsApp con enlace de perfil público dinámico
// ==============================================================================
require_once 'config.php';

// Validar sesión activa en Laragon o Servidor
check_login();

$usuario_id = $_SESSION['usuario_id'];
// 🔥 CORRECCIÓN 1: Capturar el username desde la sesión para poder armar el link
$username = isset($_SESSION['username']) ? $_SESSION['username'] : 'usuario';

// 🔥 CORRECCIÓN 2: Detectar el dominio dinámicamente (Laragon local vs InfinityFree)
$protocolo = (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on') ? "https://" : "http://";
$dominio_actual = $_SERVER['HTTP_HOST'];

// Si estás en Laragon, por ejemplo local/album26/, esto ayuda a mantener la ruta limpia
$base_url = $protocolo . $dominio_actual . dirname($_SERVER['SCRIPT_NAME']);

// Asegurar que termine en una sola barra diagonal
$base_url = rtrim($base_url, '/\\') . '/';

try {
    // Traemos todas las láminas ordenadas por id/número usando el índice
    $stmt = $pdo->prepare("SELECT * FROM laminas WHERE usuario_id = ? ORDER BY id ASC");
    $stmt->execute([$usuario_id]);
    $todas_las_laminas = $stmt->fetchAll();

    // Mapeo de banderas nativas por sigla para el formato de WhatsApp
    $banderas = [
        'FWC' => '🏆', 'CAN' => '🇨🇦', 'USA' => '🇺🇸', 'MEX' => '🇲🇽',
        'ARG' => '🇦🇷', 'BRA' => '🇧🇷', 'COL' => '🇨🇴', 'ECU' => '🇪🇨',
        'PAR' => '🇵🇾', 'URU' => '🇺🇾', 'HAI' => '🇭🇹', 'CUW' => '🇨🇼',
        'PAN' => '🇵🇦', 'GER' => '🇩🇪', 'AUT' => '🇦🇹', 'BEL' => '🇧🇪',
        'CRO' => '🇭🇷', 'SCO' => '🏴󠁧󠁢󠁳󠁣󠁴󠁿', 'ESP' => '🇪🇸', 'FRA' => '🇫🇷',
        'ENG' => '🏴󠁧󠁢󠁥󠁮󠁧󠁿', 'NOR' => '🇳🇴', 'NED' => '🇳🇱', 'POR' => '🇵🇹',
        'SUI' => '🇨🇭', 'SWE' => '🇸🇪', 'CZE' => '🇨🇿', 'TUR' => '🇹🇷',
        'BIH' => '🇧🇦', 'ALG' => '🇩🇿', 'CPV' => '🇨🇻', 'CIV' => '🇨🇮',
        'EGY' => '🇪🇬', 'GHA' => '🇬🇭', 'MAR' => '🇲🇦', 'SEN' => '🇸🇳',
        'RSA' => '🇿🇦', 'TUN' => '🇹🇳', 'COD' => '🇨🇩', 'KSA' => '🇸🇦',
        'AUS' => '🇦🇺', 'KOR' => '🇰🇷', 'IRQ' => '🇮🇶', 'IRN' => '🇮🇷',
        'JPN' => '🇯🇵', 'JOR' => '🇯🇴', 'QAT' => '🇶🇦', 'UZB' => '🇺🇿',
        'NZL' => '🇳🇿', 'CC'  => '🥤'
    ];

    $tengo_raw = [];
    $faltan_raw = [];
    $repes_raw = [];

    // Agrupamos en crudo usando PHP ultrarrápido en memoria
    foreach ($todas_las_laminas as $lamina) {
        $eq = $lamina['equipo'];
        $bandera = $banderas[$eq] ?? '🌍';
        
        $solo_numero = trim(str_replace($eq, '', $lamina['numero']));
        $key = "{$eq} {$bandera}";

        if ($lamina['cantidad'] == 0) {
            $faltan_raw[$key][] = $solo_numero;
        } else {
            $tengo_raw[$key][] = $solo_numero;
            if ($lamina['cantidad'] > 1) {
                $excedente = $lamina['cantidad'] - 1;
                for ($i = 0; $i < $excedente; $i++) {
                    $repes_raw[$key][] = $solo_numero;
                }
            }
        }
    }

    function formatear_lista($array_crudo) {
        $salida = [];
        foreach ($array_crudo as $equipo_txt => $numeros) {
            $salida[] = "{$equipo_txt}: " . implode(', ', $numeros);
        }
        return implode("\n", $salida);
    }

    $texto_tengo = formatear_lista($tengo_raw);
    $texto_faltan = formatear_lista($faltan_raw);
    $texto_repes = formatear_lista($repes_raw);

} catch (\PDOException $e) {
    die("Error al procesar listas: " . $e->getMessage());
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Compartir Láminas - Mi Álbum</title>
    <link rel="icon" type="image/png" href="img/favicon.png">
    <link class="favicon-apple" rel="apple-touch-icon" href="img/favicon.png">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        .textarea-share { font-family: monospace; font-size: 0.85rem; background-color: #f8f9fa; resize: none; height: 320px; }
        .nav-pills .nav-link.active { background-color: #25D366 !important; color: white !important; } /* Verde WhatsApp */
        .nav-pills .nav-link { color: #495057; font-weight: bold; }
    </style>
</head>
<body class="bg-light">
    <?php include 'navbar.php'; ?>

    <div class="container px-3 mt-4 mb-5" style="max-width: 600px;">
        <div class="text-center mb-3">
            <h4 class="fw-bold text-dark mb-1">📲 Copiar para WhatsApp</h4>
            <p class="text-muted small">Genera los listados formateados o comparte tu perfil con amigos.</p>
        </div>

        <!-- 🔥 CORRECCIÓN 3: Tarjeta de Enlace Público integrada dentro del flujo principal -->
        <div class="card shadow-sm p-3 mb-4 text-center border-0 bg-white">
            <h6 class="fw-bold text-dark mb-2">🔗 Tu Perfil Público del Álbum</h6>
            <p class="text-muted small mb-2">Comparte este enlace personalizado para que otros vean tus láminas en tiempo real:</p>
            
            <div class="input-group input-group-sm mb-1">
                <input type="text" id="url-perfil" class="form-control text-center bg-light font-monospace small" 
                       value="<?= $base_url . htmlspecialchars($username) ?>" readonly>
                <button class="btn btn-primary fw-bold" type="button" onclick="copiarEnlacePerfil()">Copiar Link</button>
            </div>
        </div>

        <ul class="nav nav-pills nav-fill bg-white p-1 rounded-3 shadow-sm mb-3" id="pills-tab" role="tablist">
            <li class="nav-item">
                <button class="nav-link active py-2" id="pills-repes-tab" data-bs-toggle="pill" data-bs-target="#panel-repes" type="button" role="tab">🔄 Repetidas</button>
            </li>
            <li class="nav-item">
                <button class="nav-link py-2 text-danger" id="pills-faltan-tab" data-bs-toggle="pill" data-bs-target="#panel-faltan" type="button" role="tab">❌ Faltantes</button>
            </li>
            <li class="nav-item">
                <button class="nav-link py-2 text-success" id="pills-tengo-tab" data-bs-toggle="pill" data-bs-target="#panel-tengo" type="button" role="tab">✅ Mis Láminas</button>
            </li>
        </ul>

        <div class="tab-content" id="pills-tabContent">
            
            <div class="tab-pane fade show active" id="panel-repes" role="tabpanel">
                <div class="card p-3 shadow-sm border-0">
                    <label class="form-label fw-bold text-secondary small">Texto de Repetidas listo:</label>
                    <textarea id="txt-repes" class="form-control textarea-share mb-3" readonly><?= !empty($texto_repes) ? "📋 MIS REPETIDAS ⚽\n\n" . htmlspecialchars($texto_repes) : "No tienes láminas repetidas aún." ?></textarea>
                    <button class="btn btn-success fw-bold py-2" onclick="copiarTexto('txt-repes')">📋 Copiar Repetidas</button>
                </div>
            </div>

            <div class="tab-pane fade" id="panel-faltan" role="tabpanel">
                <div class="card p-3 shadow-sm border-0">
                    <label class="form-label fw-bold text-secondary small">Texto de Faltantes listo:</label>
                    <textarea id="txt-faltan" class="form-control textarea-share mb-3" readonly><?= !empty($texto_faltan) ? "📌 LÁMINAS QUE ME FALTAN 🔍\n\n" . htmlspecialchars($texto_faltan) : "¡Felicidades! Tienes el álbum completo." ?></textarea>
                    <button class="btn btn-success fw-bold py-2" onclick="copiarTexto('txt-faltan')">📋 Copiar Faltantes</button>
                </div>
            </div>

            <div class="tab-pane fade" id="panel-tengo" role="tabpanel">
                <div class="card p-3 shadow-sm border-0">
                    <label class="form-label fw-bold text-secondary small">Texto de Pegadas listo:</label>
                    <textarea id="txt-tengo" class="form-control textarea-share mb-3" readonly><?= !empty($texto_tengo) ? "✅ LÁMINAS QUE YA TENGO 📦\n\n" . htmlspecialchars($texto_tengo) : "No tienes láminas en el inventario." ?></textarea>
                    <button class="btn btn-success fw-bold py-2" onclick="copiarTexto('txt-tengo')">📋 Copiar Lo Que Tengo</button>
                </div>
            </div>

        </div>
    </div>

    <!-- Toast de Notificación Flotante -->
    <div class="position-fixed bottom-0 start-50 translate-middle-x mb-4" style="z-index: 1050;">
        <div id="toast-copiado" class="toast align-items-center text-white bg-dark border-0 shadow-lg" role="alert" aria-live="assertive" aria-atomic="true" data-bs-delay="2000">
            <div class="d-flex">
                <div class="toast-body fw-bold text-center w-100" id="toast-mensaje">
                    ¡Copiado al portapapeles! 🚀
                </div>
            </div>
        </div>
    </div>

    <script>
    // Función reutilizable para lanzar el Toast nativo de Bootstrap
    function mostrarToast(mensaje) {
        document.getElementById('toast-mensaje').innerText = mensaje;
        const toastEl = document.getElementById('toast-copiado');
        const toast = new bootstrap.Toast(toastEl);
        toast.show();
    }

    function copiarEnlacePerfil() {
        const inputUrl = document.getElementById('url-perfil');
        inputUrl.select();
        inputUrl.setSelectionRange(0, 99999);
        
        navigator.clipboard.writeText(inputUrl.value).then(() => {
            mostrarToast("¡Link de perfil copiado! 🔗");
        }).catch(err => {
            console.error('Error al copiar link:', err);
        });
    }

    function copiarTexto(idElemento) {
        const textarea = document.getElementById(idElemento);
        textarea.select();
        textarea.setSelectionRange(0, 99999); 
        
        navigator.clipboard.writeText(textarea.value).then(() => {
            mostrarToast("¡Texto copiado para WhatsApp! 🚀");
        }).catch(err => {
            console.error('Error al copiar listado:', err);
        });
    }
    </script>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
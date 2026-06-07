<?php
// ==============================================================================
// repetidas.php - Filtro Inteligente de Láminas Repetidas (Versión Corregida)
// Muestra únicamente el inventario con cantidad > 1 para facilitar intercambios
// ==============================================================================
require_once 'config.php';

// Validar sesión activa
check_login();

$usuario_id = $_SESSION['usuario_id'];
$username = $_SESSION['username'];

try {
    // CONSULTA: Traer solo las láminas cuyo conteo sea mayor a 1
    // Ordenadas por número para que sea fácil buscarlas en orden físico
    $stmt = $pdo->prepare("SELECT *, (cantidad - 1) AS excedente FROM laminas WHERE usuario_id = ? AND cantidad > 1 ORDER BY numero ASC");
    $stmt->execute([$usuario_id]);
    $repetidas = $stmt->fetchAll();

    // Calcular el total de monas repetidas acumuladas
    $total_excedente = 0;
    foreach ($repetidas as $r) {
        $total_excedente += $r['excedente'];
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
    <title>Mis Repetidas - Álbum Collector</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
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

    <div class="container">
        <div class="row mb-3">
            <div class="col-12 d-flex justify-content-between align-items-center bg-white p-3 rounded shadow-sm">
                <div>
                    <h3 class="fw-bold text-dark mb-0">🔄 Mis Láminas Repetidas</h3>
                    <p class="text-muted small mb-0">Filtro automático de monas con excedentes para negocio.</p>
                </div>
                <div class="text-end">
                    <span class="fs-4 fw-bold text-warning"><?= $total_excedente ?></span>
                    <p class="text-muted small mb-0 fw-bold">Total a Cambiar</p>
                </div>
            </div>
        </div>

        <div class="row mb-4">
            <div class="col-12">
                <input type="text" id="txtBuscador" class="form-control form-control-lg shadow-sm" placeholder="🔍 Escribe el número o nombre de la lámina para buscar..." onkeyup="filtrarRepetidas()">
            </div>
        </div>

        <div class="card shadow-sm border-0 bg-white p-3">
            <?php if (empty($repetidas)): ?>
                <div class="text-center py-5">
                    <span class="fs-1">😎</span>
                    <h4 class="mt-3 fw-bold text-secondary">¡No tienes láminas repetidas aún!</h4>
                    <p class="text-muted">A medida que sumes más de una unidad en tu inventario, aparecerán en esta lista.</p>
                    <a href="laminas.php" class="btn btn-primary btn-sm fw-bold mt-2">Ir a cargar láminas</a>
                </div>
            <?php else: ?>
                <div class="table-responsive">
                    <table class="table table-hover align-middle" id="tablaRepetidas">
                        <thead class="table-light">
                            <tr>
                                <th style="width: 15%;">Número</th>
                                <th style="width: 45%;">Nombre / Descripción</th>
                                <th style="width: 20%;" class="text-center">Tipo</th>
                                <th style="width: 20%;" class="text-center bg-warning-subtle">Tienes de Más</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($repetidas as $reg): ?>
                                <tr>
                                    <td class="fw-bold text-primary fs-5"><?= htmlspecialchars($reg['numero']) ?></td>
                                    <td>
                                        <span class="fw-bold d-block"><?= htmlspecialchars($reg['nombre']) ?></span>
                                        <small class="text-muted">Total en tu poder: <?= $reg['cantidad'] ?></small>
                                    </td>
                                    <td class="text-center">
                                        <?php if ($reg['es_especial']): ?>
                                            <span class="badge bg-danger text-uppercase fw-bold p-2 shadow-sm" style="letter-spacing: 1px;">⭐ Especial</span>
                                        <?php else: ?>
                                            <span class="badge bg-secondary p-2">Estándar</span>
                                        <?php endif; ?>
                                    </td>
                                    <td class="text-center bg-warning-subtle fw-bold text-dark fs-5">
                                        +<?= $reg['excedente'] ?>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php endif; ?>
        </div>
    </div>

    <script>
    function filtrarRepetidas() {
        const input = document.getElementById("txtBuscador");
        const filter = input.value.toUpperCase();
        const table = document.getElementById("tablaRepetidas");
        
        if (!table) return;
        
        const tr = table.getElementsByTagName("tr");

        for (let i = 1; i < tr.length; i++) {
            const tdNumero = tr[i].getElementsByTagName("td")[0];
            const tdNombre = tr[i].getElementsByTagName("td")[1];
            
            if (tdNumero || tdNombre) {
                const txtNumero = tdNumero.textContent || tdNumero.innerText;
                const txtNombre = tdNombre.textContent || tdNombre.innerText;
                
                if (txtNumero.toUpperCase().indexOf(filter) > -1 || txtNombre.toUpperCase().indexOf(filter) > -1) {
                    tr[i].style.display = ""; 
                } else {
                    tr[i].style.display = "none"; 
                }
            }       
        }
    }
    </script>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
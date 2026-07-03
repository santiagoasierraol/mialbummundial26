<?php
require_once 'config.php';
check_login();

$usuario_id = $_SESSION['usuario_id'];
$limite = isset($_GET['limite']) ? (int)$_GET['limite'] : 40;
$offset = isset($_GET['offset']) ? (int)$_GET['offset'] : 0;
$busqueda = isset($_GET['buscar']) ? trim($_GET['buscar']) : '';

try {
    if (!empty($busqueda)) {
        // Si hay búsqueda, filtramos sin paginar para no romper la experiencia
        $stmt = $pdo->prepare("
            SELECT * FROM laminas 
            WHERE usuario_id = ? AND (nombre LIKE ? OR numero LIKE ?)
            ORDER BY id ASC
        ");
        $stmt->execute([$usuario_id, "%$busqueda%", "%$busqueda%"]);
    } else {
        // Si no hay búsqueda, paginamos fluidamente
        $stmt = $pdo->prepare("
            SELECT * FROM laminas 
            WHERE usuario_id = ? 
            ORDER BY id ASC 
            LIMIT ? OFFSET ?
        ");
        // Forzar enteros para que PDO no falle en el LIMIT
        $stmt->bindValue(1, $usuario_id, PDO::PARAM_INT);
        $stmt->bindValue(2, $limite, PDO::PARAM_INT);
        $stmt->bindValue(3, $offset, PDO::PARAM_INT);
        $stmt->execute();
    }
    
    $laminas = $stmt->fetchAll(PDO::FETCH_ASSOC);
    echo json_encode(['success' => true, 'laminas' => $laminas]);

} catch (\PDOException $e) {
    echo json_encode(['success' => false, 'error' => $e->getMessage()]);
}
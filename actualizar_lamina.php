<?php
// ==============================================================================
// actualizar_lamina.php - Procesador en Segundo Plano (AJAX)
// Recibe los clics de laminas.php, actualiza MySQL y devuelve el resultado
// ==============================================================================
require_once 'config.php';

// Validar que el usuario tenga una sesión activa (excepto en el index si es el login)
check_login();

$usuario_id = $_SESSION['usuario_id'];
$lamina_id = intval($_POST['id']);
$accion = $_POST['accion'];

try {
    if ($accion === 'sumar') {
        // Incrementa en 1 la mona seleccionada
        $stmt = $pdo->prepare("UPDATE laminas SET cantidad = cantidad + 1 WHERE id = ? AND usuario_id = ?");
        $stmt->execute([$lamina_id, $usuario_id]);
    } elseif ($accion === 'restar') {
        // Decrementa en 1 controlando que no baje de cero
        $stmt = $pdo->prepare("UPDATE laminas SET cantidad = cantidad - 1 WHERE id = ? AND usuario_id = ? AND cantidad > 0");
        $stmt->execute([$lamina_id, $usuario_id]);
    }

    // Consultar el nuevo inventario real para responderle a JavaScript
    $stmt = $pdo->prepare("SELECT cantidad FROM laminas WHERE id = ? AND usuario_id = ?");
    $stmt->execute([$lamina_id, $usuario_id]);
    $nueva_cantidad = $stmt->fetchColumn();

    // Responder con éxito en formato JSON para que la pantalla cambie de color en vivo
    echo json_encode([
        'success' => true,
        'nueva_cantidad' => intval($nueva_cantidad)
    ]);
    exit;

} catch (\PDOException $e) {
    // Si la base de datos saca la mano, le avisa discretamente a la consola del navegador
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage()
    ]);
    exit;
}
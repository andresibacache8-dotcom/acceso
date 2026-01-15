<?php
/**
 * control-personal-status.php
 * Endpoint para gestionar el estado de Control de Unidades
 * Almacena el estado en la sesión del servidor
 */

session_start();

header('Content-Type: application/json');

// Verificar que el usuario esté autenticado
if (!isset($_SESSION['logged_in']) || $_SESSION['logged_in'] !== true) {
    http_response_code(401);
    echo json_encode(['error' => 'No autenticado']);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    // Obtener estado actual
    $isEnabled = isset($_SESSION['controlPersonalEnabled']) && $_SESSION['controlPersonalEnabled'] === true;

    http_response_code(200);
    echo json_encode([
        'success' => true,
        'enabled' => $isEnabled
    ]);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Actualizar estado
    $data = json_decode(file_get_contents('php://input'), true);
    $enabled = isset($data['enabled']) && $data['enabled'] === true;

    // Guardar en la sesión
    $_SESSION['controlPersonalEnabled'] = $enabled;

    http_response_code(200);
    echo json_encode([
        'success' => true,
        'enabled' => $enabled,
        'message' => $enabled ? 'Control de Unidades habilitado' : 'Control de Unidades deshabilitado'
    ]);
    exit;
}

// Método no permitido
http_response_code(405);
echo json_encode(['error' => 'Método no permitido']);
?>

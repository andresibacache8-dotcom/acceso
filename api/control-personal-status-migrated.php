<?php
/**
 * api/control-personal-status-migrated.php
 * Endpoint para gestionar estado de Control de Unidades (sesión)
 *
 * Migración desde control-personal-status.php original:
 * - Config: Sin cambios (no usa BD, solo sesión)
 * - Respuestas: echo json_encode() → ApiResponse::*()
 * - Estructura: funciones separadas por método HTTP
 *
 * Endpoints:
 * GET    /api/control-personal-status.php    - Obtener estado actual
 * POST   /api/control-personal-status.php    - Actualizar estado
 *
 * @version 2.0 (Migrated)
 */

require_once __DIR__ . '/core/ResponseHandler.php';
require_once __DIR__ . '/core/AuthMiddleware.php';
require_once __DIR__ . '/core/AuditLogger.php';
require_once __DIR__ . '/core/SecurityHeaders.php';

// Headers
// Aplicar security headers
SecurityHeaders::applyApiHeaders();

// Manejar preflight CORS
SecurityHeaders::handleCors();

// Verificar autenticación
    // Verificar autenticación con JWT
    try {
        $user = AuthMiddleware::requireAuth();
    } catch (Exception $e) {
        ApiResponse::unauthorized($e->getMessage());
    }

// Obtener método HTTP
$method = $_SERVER['REQUEST_METHOD'];

// Router de métodos HTTP
switch ($method) {
    case 'GET':
        handleGet();
        break;
    case 'POST':
        handlePost();
        break;
    default:
        ApiResponse::error('Método no permitido', 405);
}

/**
 * GET /api/control-personal-status.php
 * Obtiene el estado actual del Control de Unidades
 *
 * Retorna:
 * {
 *   "success": true,
 *   "data": {
 *     "enabled": true/false
 *   }
 * }
 */
function handleGet()
{
    try {
        $isEnabled = isset($_SESSION['controlPersonalEnabled']) && $_SESSION['controlPersonalEnabled'] === true;

        $response = [
            'enabled' => $isEnabled
        ];

        ApiResponse::success($response);

    } catch (Exception $e) {
        ApiResponse::serverError('Error al obtener estado: ' . $e->getMessage());
    }
}

/**
 * POST /api/control-personal-status.php
 * Actualiza el estado del Control de Unidades
 *
 * Parámetros (JSON):
 * - enabled: boolean (requerido)
 *
 * Retorna:
 * {
 *   "success": true,
 *   "data": {
 *     "enabled": true/false,
 *     "message": "Control de Unidades habilitado/deshabilitado"
 *   }
 * }
 */
function handlePost()
{
    try {
        $data = json_decode(file_get_contents('php://input'), true);

        // Validar que existe el parámetro enabled
        if (!isset($data['enabled'])) {
            ApiResponse::badRequest('Parámetro requerido: enabled (boolean)');
        }

        $enabled = isset($data['enabled']) && $data['enabled'] === true;

        // Guardar en sesión
        $_SESSION['controlPersonalEnabled'] = $enabled;

        $response = [
            'enabled' => $enabled,
            'message' => $enabled
                ? 'Control de Unidades habilitado'
                : 'Control de Unidades deshabilitado'
        ];

        ApiResponse::success($response);

    } catch (Exception $e) {
        ApiResponse::serverError('Error al actualizar estado: ' . $e->getMessage());
    }
}

?>

<?php
/**
 * api/auth-refresh.php
 * Endpoint para refrescar JWT access token
 *
 * Migración desde auth.php original:
 * - Usa JwtHandler para validación y generación
 * - Usa AuthMiddleware para verificación
 * - Usa AuditLogger para registro de eventos
 *
 * Endpoints:
 * POST /api/auth-refresh.php - Refrescar access token usando refresh token
 *
 * @version 1.0
 */

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/core/ResponseHandler.php';
require_once __DIR__ . '/core/JwtHandler.php';
require_once __DIR__ . '/core/AuditLogger.php';
require_once __DIR__ . '/core/SecurityHeaders.php';

// Headers de seguridad
SecurityHeaders::applyApiHeaders();

// Manejar preflight CORS
SecurityHeaders::handleCors();

// Solo POST permitido
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    ApiResponse::error('Método no permitido', 405);
}

try {
    // Obtener refresh token del header Authorization
    $header = getallheaders()['Authorization'] ?? null;

    if (!$header || !preg_match('/Bearer\s+(.*)$/i', $header, $matches)) {
        ApiResponse::unauthorized('Refresh token requerido en Authorization header');
    }

    $refreshToken = $matches[1];

    // Verificar que el token es válido y es un refresh token
    try {
        $payload = JwtHandler::verify($refreshToken);

        // Verificar que es un refresh token (no un access token)
        if (($payload['type'] ?? 'access') !== 'refresh') {
            throw new Exception('Token inválido: debe ser un refresh token');
        }

        // Generar nuevo access token
        $newAccessToken = JwtHandler::generate(
            $payload['userId'],
            $payload['username'],
            $payload['role'],
            false  // Access token (no refresh)
        );

        // Log de refresh exitoso
        AuditLogger::log('TOKEN_REFRESHED', [
            'user_id' => $payload['userId'] ?? null,
            'username' => $payload['username'] ?? null
        ]);

        // Retornar nuevo access token
        ApiResponse::success([
            'token' => $newAccessToken,
            'expires_in' => 3600  // 1 hora
        ], 200);

    } catch (Exception $e) {
        AuditLogger::log('TOKEN_REFRESH_FAILED', [
            'reason' => $e->getMessage()
        ], 'WARNING');

        ApiResponse::unauthorized('Token de refresco inválido: ' . $e->getMessage());
    }

} catch (Exception $e) {
    ApiResponse::serverError('Error al refrescar token: ' . $e->getMessage());
}

?>

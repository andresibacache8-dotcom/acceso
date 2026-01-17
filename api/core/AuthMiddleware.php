<?php
/**
 * api/core/AuthMiddleware.php
 *
 * Middleware de autenticación
 * Verifica JWT y autoriza solicitudes
 *
 * Uso en APIs:
 *   AuthMiddleware::requireAuth();                    // Requiere login
 *   AuthMiddleware::requireRole('admin');            // Requiere rol específico
 *   $user = AuthMiddleware::getAuthenticatedUser();  // Obtener usuario actual
 */

require_once __DIR__ . '/JwtHandler.php';
require_once __DIR__ . '/AuditLogger.php';

class AuthMiddleware
{
    private static $authenticated = false;
    private static $user = null;
    private static $token = null;

    /**
     * Verificar autenticación
     * Extrae y valida JWT del header Authorization
     *
     * @throws Exception Si token es inválido o expirado
     * @return array Datos del usuario autenticado
     */
    public static function requireAuth()
    {
        // Si ya verificamos, retornar usuario
        if (self::$authenticated) {
            return self::$user;
        }

        // Obtener token del header
        $token = JwtHandler::getTokenFromHeader();

        if (!$token) {
            throw new Exception('Token requerido', 401);
        }

        try {
            // Verificar token
            $payload = JwtHandler::verify($token);

            self::$authenticated = true;
            self::$user = $payload;
            self::$token = $token;

            return $payload;

        } catch (Exception $e) {
            AuditLogger::log('AUTH_FAILED', [
                'reason' => $e->getMessage(),
                'ip' => $_SERVER['REMOTE_ADDR'] ?? 'unknown',
                'user_agent' => $_SERVER['HTTP_USER_AGENT'] ?? 'unknown'
            ]);
            throw new Exception('Autenticación fallida: ' . $e->getMessage(), 401);
        }
    }

    /**
     * Requerir rol específico
     *
     * @param string|array $roles Rol o array de roles permitidos
     * @throws Exception Si usuario no tiene rol
     * @return array Datos del usuario
     */
    public static function requireRole($roles)
    {
        // Primero verificar autenticación
        $user = self::requireAuth();

        // Convertir a array si es string
        if (is_string($roles)) {
            $roles = [$roles];
        }

        // Verificar si el rol del usuario está en la lista permitida
        if (!in_array($user['role'] ?? null, $roles)) {
            AuditLogger::log('AUTH_FORBIDDEN', [
                'user_id' => $user['userId'] ?? null,
                'required_role' => implode(',', $roles),
                'user_role' => $user['role'] ?? 'none'
            ]);
            throw new Exception('Rol no autorizado', 403);
        }

        return $user;
    }

    /**
     * Obtener usuario autenticado
     *
     * @return array|null Datos del usuario o null si no autenticado
     */
    public static function getAuthenticatedUser()
    {
        return self::$user;
    }

    /**
     * Obtener ID del usuario autenticado
     *
     * @return int|null ID del usuario o null
     */
    public static function getUserId()
    {
        return self::$user['userId'] ?? null;
    }

    /**
     * Obtener token actual
     *
     * @return string|null Token o null
     */
    public static function getToken()
    {
        return self::$token;
    }

    /**
     * Verificar si está autenticado
     *
     * @return bool True si está autenticado
     */
    public static function isAuthenticated()
    {
        return self::$authenticated;
    }
}

?>

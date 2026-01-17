<?php
/**
 * api/auth-migrated.php
 * API de autenticación - Login de usuarios
 *
 * Migración desde auth.php original:
 * - Config: database/db_acceso.php → config/database.php (DatabaseConfig)
 * - Respuestas: echo json_encode() → ApiResponse::*()
 * - Estructura: funciones separadas por método HTTP
 *
 * Endpoints:
 * POST   /api/auth.php    - Login con username/password
 * GET    /api/auth.php    - Verificar autenticación actual (token de sesión)
 *
 * @version 2.0 (Migrated)
 */

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/core/ResponseHandler.php';
require_once __DIR__ . '/core/JwtHandler.php';
require_once __DIR__ . '/core/RateLimiter.php';
require_once __DIR__ . '/core/AuditLogger.php';
require_once __DIR__ . '/core/SecurityHeaders.php';
require_once __DIR__ . '/core/AuthMiddleware.php';

// Headers de seguridad
SecurityHeaders::applyApiHeaders();

// Obtener conexión desde DatabaseConfig singleton
$databaseConfig = DatabaseConfig::getInstance();
$conn_acceso = $databaseConfig->getAccesoConnection();

if (!$conn_acceso) {
    AuditLogger::log('AUTH_FAILED', [
        'reason' => 'Database connection failed'
    ], 'CRITICAL');
    ApiResponse::serverError('Error conectando a base de datos');
}

// Obtener método HTTP
$method = $_SERVER['REQUEST_METHOD'];

// Router de métodos HTTP
switch ($method) {
    case 'GET':
        handleGet();
        break;
    case 'POST':
        handlePost($conn_acceso);
        break;
    case 'DELETE':
        handleDelete();
        break;
    default:
        ApiResponse::error('Método no permitido', 405);
}

/**
 * GET /api/auth.php
 * Verifica si el usuario actual está autenticado mediante JWT
 *
 * Requiere:
 * - Header: Authorization: Bearer <token>
 *
 * Retorna:
 * - success: true + datos del usuario si está autenticado
 * - error 401 si no está autenticado o token inválido
 */
function handleGet()
{
    try {
        // Verificar autenticación con JWT
        $user = AuthMiddleware::requireAuth();

        // Retornar datos del usuario actual
        $response = [
            'id' => $user['userId'] ?? null,
            'username' => $user['username'] ?? null,
            'role' => $user['role'] ?? null
        ];

        AuditLogger::log('AUTH_TOKEN_VALIDATED', [
            'user_id' => $user['userId'] ?? null
        ]);

        ApiResponse::success($response);

    } catch (Exception $e) {
        ApiResponse::unauthorized($e->getMessage());
    }
}

/**
 * POST /api/auth.php
 * Autentica un usuario y retorna JWT token
 *
 * Parámetros (JSON):
 * - username: nombre de usuario (requerido)
 * - password: contraseña (requerido)
 *
 * Respuesta exitosa (200):
 * {
 *   "success": true,
 *   "data": {
 *     "token": "eyJ0eXAiOiJKV1QiLCJhbGc...",
 *     "refreshToken": "eyJ0eXAiOiJKV1QiLCJhbGc...",
 *     "user": {
 *       "id": 1,
 *       "username": "usuario",
 *       "role": "admin"
 *     }
 *   }
 * }
 *
 * Respuesta fallida (401):
 * {
 *   "success": false,
 *   "error": {
 *     "message": "Usuario o contraseña incorrectos",
 *     "code": 401
 *   }
 * }
 *
 * @param mysqli $conn_acceso Conexión a BD acceso
 */
function handlePost($conn_acceso)
{
    try {
        // Aplicar rate limiting en login (máx 5 intentos en 5 minutos)
        try {
            RateLimiter::check('login', 5, 300);
        } catch (Exception $e) {
            AuditLogger::log('LOGIN_RATE_LIMITED', [
                'ip' => $_SERVER['REMOTE_ADDR'] ?? 'unknown'
            ], 'WARNING');
            ApiResponse::error($e->getMessage(), $e->getCode());
        }

        $data = json_decode(file_get_contents('php://input'), true);

        // Validar campos requeridos
        if (empty($data['username']) || empty($data['password'])) {
            AuditLogger::log('LOGIN_FAILED', [
                'reason' => 'Missing required fields'
            ], 'WARNING');
            ApiResponse::badRequest('Campos requeridos: username, password');
        }

        $username = trim($data['username']);
        $password = $data['password'];

        // Preparar consulta para buscar usuario
        $stmt = $conn_acceso->prepare("SELECT id, username, password, role FROM users WHERE username = ?");
        if (!$stmt) {
            throw new Exception("Error preparando consulta: " . $conn_acceso->error);
        }

        $stmt->bind_param("s", $username);
        $stmt->execute();
        $result = $stmt->get_result();
        $user = $result->fetch_assoc();
        $stmt->close();

        // Verificar que el usuario existe y contraseña es correcta
        if (!$user || !password_verify($password, $user['password'])) {
            AuditLogger::log('LOGIN_FAILED', [
                'username' => $username,
                'reason' => 'Invalid credentials'
            ], 'WARNING');
            ApiResponse::unauthorized('Usuario o contraseña incorrectos');
        }

        // Generar JWT tokens
        $accessToken = JwtHandler::generate($user['id'], $user['username'], $user['role'], false);
        $refreshToken = JwtHandler::generate($user['id'], $user['username'], $user['role'], true);

        // Log login exitoso
        AuditLogger::log('LOGIN', [
            'user_id' => $user['id'],
            'username' => $user['username'],
            'role' => $user['role']
        ]);

        // Resetear rate limiter después de login exitoso
        RateLimiter::reset('login');

        // Retornar datos del usuario autenticado con tokens
        $response = [
            'token' => $accessToken,
            'refreshToken' => $refreshToken,
            'user' => [
                'id' => (int)$user['id'],
                'username' => $user['username'],
                'role' => $user['role']
            ]
        ];

        ApiResponse::success($response, 200);

    } catch (Exception $e) {
        ApiResponse::serverError('Error al autenticar: ' . $e->getMessage());
    }
}

/**
 * DELETE /api/auth.php
 * Cerrar sesión del usuario (logout)
 *
 * Requiere:
 * - Header: Authorization: Bearer <token>
 *
 * Retorna:
 * - success: true (204 No Content)
 */
function handleDelete()
{
    try {
        // Verificar autenticación
        $user = AuthMiddleware::requireAuth();

        // Log logout
        AuditLogger::log('LOGOUT', [
            'user_id' => $user['userId'] ?? null,
            'username' => $user['username'] ?? null
        ]);

        // Retornar 204 No Content
        ApiResponse::noContent();

    } catch (Exception $e) {
        ApiResponse::unauthorized($e->getMessage());
    }
}

?>

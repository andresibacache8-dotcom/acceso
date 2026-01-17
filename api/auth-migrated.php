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

// Headers
header('Content-Type: application/json');
session_start();

// Obtener conexión desde DatabaseConfig singleton
$databaseConfig = DatabaseConfig::getInstance();
$conn_acceso = $databaseConfig->getAccesoConnection();

if (!$conn_acceso) {
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
    default:
        ApiResponse::error('Método no permitido', 405);
}

/**
 * GET /api/auth.php
 * Verifica si el usuario actual está autenticado
 *
 * Retorna:
 * - success: true + datos del usuario si está autenticado
 * - error 401 si no está autenticado
 */
function handleGet()
{
    // Verificar autenticación
    if (!isset($_SESSION['logged_in']) || $_SESSION['logged_in'] !== true) {
        ApiResponse::unauthorized('No autorizado. Por favor, inicie sesión.');
    }

    // Retornar datos del usuario actual
    $response = [
        'id' => $_SESSION['user_id'] ?? null,
        'username' => $_SESSION['username'] ?? null,
        'role' => $_SESSION['role'] ?? null
    ];

    ApiResponse::success($response);
}

/**
 * POST /api/auth.php
 * Autentica un usuario y crea sesión
 *
 * Parámetros (JSON):
 * - username: nombre de usuario (requerido)
 * - password: contraseña (requerido)
 *
 * Respuesta exitosa (200):
 * {
 *   "success": true,
 *   "data": {
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
        $data = json_decode(file_get_contents('php://input'), true);

        // Validar campos requeridos
        if (empty($data['username']) || empty($data['password'])) {
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
            ApiResponse::unauthorized('Usuario o contraseña incorrectos');
        }

        // Crear sesión
        $_SESSION['user_id'] = $user['id'];
        $_SESSION['username'] = $user['username'];
        $_SESSION['role'] = $user['role'];
        $_SESSION['logged_in'] = true;

        // Retornar datos del usuario autenticado
        $response = [
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

?>

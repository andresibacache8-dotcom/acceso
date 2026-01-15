<?php
/**
 * api/users-migrated.php
 * CRUD API para gestión de usuarios del sistema
 *
 * Migración desde users.php original:
 * - Config: database/db_acceso.php → config/database.php (DatabaseConfig)
 * - Respuestas: echo json_encode() → ApiResponse::*()
 * - Estructura: funciones separadas por método HTTP
 *
 * Endpoints:
 * GET    /api/users.php              - Lista todos los usuarios (sin contraseñas)
 * POST   /api/users.php              - Crear nuevo usuario
 * PUT    /api/users.php              - Actualizar usuario
 * DELETE /api/users.php?id=1         - Eliminar usuario
 *
 * @version 2.0 (Migrated)
 */

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/core/ResponseHandler.php';

// Headers y autenticación
header('Content-Type: application/json');
session_start();

// Verificar autenticación
if (!isset($_SESSION['logged_in']) || $_SESSION['logged_in'] !== true) {
    ApiResponse::unauthorized('No autorizado. Por favor, inicie sesión.');
}

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
        handleGet($conn_acceso);
        break;
    case 'POST':
        handlePost($conn_acceso);
        break;
    case 'PUT':
        handlePut($conn_acceso);
        break;
    case 'DELETE':
        handleDelete($conn_acceso);
        break;
    default:
        ApiResponse::error('Método no permitido', 405);
}

/**
 * GET /api/users.php
 * Retorna lista de todos los usuarios (sin revelar contraseñas)
 *
 * @param mysqli $conn_acceso Conexión a BD acceso
 */
function handleGet($conn_acceso)
{
    try {
        $result = $conn_acceso->query("SELECT id, username, role FROM users ORDER BY username ASC");
        if (!$result) {
            throw new Exception("Error en consulta: " . $conn_acceso->error);
        }

        $users = [];
        while ($row = $result->fetch_assoc()) {
            $row['id'] = (int)$row['id'];
            $users[] = $row;
        }

        ApiResponse::success($users);

    } catch (Exception $e) {
        ApiResponse::serverError('Error al obtener usuarios: ' . $e->getMessage());
    }
}

/**
 * POST /api/users.php
 * Crea un nuevo usuario
 *
 * Parámetros (JSON):
 * - username: nombre de usuario (requerido)
 * - password: contraseña (requerido)
 * - role:     rol del usuario (opcional, default: 'operator')
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

        // Normalizar datos
        $username = trim($data['username']);
        $password = password_hash($data['password'], PASSWORD_DEFAULT);
        $role = strtolower(trim($data['role'] ?? 'operator'));

        // Preparar inserción
        $stmt = $conn_acceso->prepare("INSERT INTO users (username, password, role) VALUES (?, ?, ?)");
        if (!$stmt) {
            throw new Exception("Error preparando inserción: " . $conn_acceso->error);
        }

        $stmt->bind_param("sss", $username, $password, $role);

        if (!$stmt->execute()) {
            throw new Exception("Error ejecutando inserción: " . $stmt->error);
        }

        $id = $stmt->insert_id;
        $stmt->close();

        // Retornar usuario creado (sin contraseña)
        $response = [
            'id' => $id,
            'username' => $username,
            'role' => $role
        ];

        ApiResponse::created($response, ['id' => $id]);

    } catch (Exception $e) {
        ApiResponse::serverError('Error al crear usuario: ' . $e->getMessage());
    }
}

/**
 * PUT /api/users.php
 * Actualiza un usuario existente
 *
 * Parámetros (JSON):
 * - id:       ID del usuario (requerido)
 * - username: nuevo nombre de usuario (requerido)
 * - password: nueva contraseña (opcional)
 * - role:     nuevo rol (opcional, default: 'operator')
 *
 * @param mysqli $conn_acceso Conexión a BD acceso
 */
function handlePut($conn_acceso)
{
    try {
        $data = json_decode(file_get_contents('php://input'), true);

        // Validar campos requeridos
        if (empty($data['id']) || empty($data['username'])) {
            ApiResponse::badRequest('Campos requeridos: id, username');
        }

        $id = (int)$data['id'];
        $username = trim($data['username']);
        $role = strtolower(trim($data['role'] ?? 'operator'));

        // Verificar que usuario existe
        $checkStmt = $conn_acceso->prepare("SELECT id FROM users WHERE id = ?");
        if (!$checkStmt) {
            throw new Exception("Error preparando consulta: " . $conn_acceso->error);
        }

        $checkStmt->bind_param("i", $id);
        $checkStmt->execute();
        $checkResult = $checkStmt->get_result();

        if ($checkResult->num_rows === 0) {
            $checkStmt->close();
            ApiResponse::notFound('Usuario no encontrado');
        }

        $checkStmt->close();

        // Actualizar con o sin contraseña
        if (isset($data['password']) && !empty($data['password'])) {
            // Actualizar incluyendo contraseña
            $password = password_hash($data['password'], PASSWORD_DEFAULT);
            $stmt = $conn_acceso->prepare("UPDATE users SET username=?, password=?, role=? WHERE id=?");
            if (!$stmt) {
                throw new Exception("Error preparando update: " . $conn_acceso->error);
            }

            $stmt->bind_param("sssi", $username, $password, $role, $id);
        } else {
            // Actualizar sin cambiar contraseña
            $stmt = $conn_acceso->prepare("UPDATE users SET username=?, role=? WHERE id=?");
            if (!$stmt) {
                throw new Exception("Error preparando update: " . $conn_acceso->error);
            }

            $stmt->bind_param("ssi", $username, $role, $id);
        }

        if (!$stmt->execute()) {
            throw new Exception("Error ejecutando update: " . $stmt->error);
        }

        $stmt->close();

        // Retornar usuario actualizado (sin contraseña)
        $response = [
            'id' => $id,
            'username' => $username,
            'role' => $role
        ];

        ApiResponse::success($response);

    } catch (Exception $e) {
        ApiResponse::serverError('Error al actualizar usuario: ' . $e->getMessage());
    }
}

/**
 * DELETE /api/users.php?id=1
 * Elimina un usuario
 *
 * @param mysqli $conn_acceso Conexión a BD acceso
 */
function handleDelete($conn_acceso)
{
    try {
        $id = isset($_GET['id']) ? (int)$_GET['id'] : null;

        if (!$id) {
            ApiResponse::badRequest('ID de usuario requerido');
        }

        // Verificar que existe
        $checkStmt = $conn_acceso->prepare("SELECT id FROM users WHERE id = ?");
        if (!$checkStmt) {
            throw new Exception("Error preparando consulta: " . $conn_acceso->error);
        }

        $checkStmt->bind_param("i", $id);
        $checkStmt->execute();
        $checkResult = $checkStmt->get_result();

        if ($checkResult->num_rows === 0) {
            $checkStmt->close();
            ApiResponse::notFound('Usuario no encontrado');
        }

        $checkStmt->close();

        // Ejecutar DELETE
        $stmt = $conn_acceso->prepare("DELETE FROM users WHERE id = ?");
        if (!$stmt) {
            throw new Exception("Error preparando delete: " . $conn_acceso->error);
        }

        $stmt->bind_param("i", $id);

        if (!$stmt->execute()) {
            throw new Exception("Error ejecutando delete: " . $stmt->error);
        }

        $affected = $stmt->affected_rows;
        $stmt->close();

        if ($affected === 0) {
            ApiResponse::serverError('No se pudo eliminar el usuario');
        }

        // Respuesta 204 No Content para DELETE exitoso
        ApiResponse::noContent();

    } catch (Exception $e) {
        ApiResponse::serverError('Error al eliminar usuario: ' . $e->getMessage());
    }
}

?>

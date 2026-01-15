<?php
/**
 * api/empresa_empleados-migrated.php
 * API para gestión de empleados de empresas asociadas
 *
 * Migración desde empresa_empleados.php original:
 * - Config: database/db_acceso.php → config/database.php
 * - Respuestas: echo json_encode() → ApiResponse::*()
 * - Estructura: métodos HTTP estándar
 *
 * Endpoints:
 * GET    /api/empresa_empleados.php                - Listar todos
 * GET    /api/empresa_empleados.php?empresa_id=1  - Listar por empresa
 * POST   /api/empresa_empleados.php                - Crear empleado
 * PUT    /api/empresa_empleados.php                - Actualizar empleado
 * DELETE /api/empresa_empleados.php?id=1           - Eliminar (soft delete)
 *
 * @version 2.0 (Migrated)
 */

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/core/ResponseHandler.php';

// Configurar manejo de errores robusto
set_error_handler(function($errno, $errstr, $errfile, $errline) {
    ApiResponse::serverError("Error PHP [$errno]: $errstr en $errfile:$errline");
});

register_shutdown_function(function() {
    $error = error_get_last();
    if ($error !== null && in_array($error['type'], [E_ERROR, E_PARSE, E_CORE_ERROR, E_COMPILE_ERROR])) {
        ApiResponse::serverError("Error Fatal: " . $error['message']);
    }
});

// Headers CORS
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization');

// Manejar preflight CORS
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}

// Iniciar sesión y verificar autenticación
session_start();
if (!isset($_SESSION['logged_in']) || $_SESSION['logged_in'] !== true) {
    ApiResponse::unauthorized('No autorizado. Por favor, inicie sesión.');
}

// Obtener conexión
$databaseConfig = DatabaseConfig::getInstance();
$conn = $databaseConfig->getAccesoConnection();

if (!$conn) {
    ApiResponse::serverError('Error conectando a base de datos');
}

// Obtener método HTTP
$method = $_SERVER['REQUEST_METHOD'];

// Router de métodos
switch ($method) {
    case 'GET':
        handleGet($conn);
        break;
    case 'POST':
        handlePost($conn);
        break;
    case 'PUT':
        handlePut($conn);
        break;
    case 'DELETE':
        handleDelete($conn);
        break;
    default:
        ApiResponse::error('Método no permitido', 405);
}

/**
 * Calcular status basado en acceso permanente y fecha de expiración
 */
function calculateStatus($is_permanent, $expiration_date_str) {
    if ($is_permanent) return 'autorizado';
    if (empty($expiration_date_str)) return 'no autorizado';

    try {
        $expiration_date = new DateTime($expiration_date_str);
        $today = new DateTime('today');
        return ($expiration_date >= $today) ? 'autorizado' : 'no autorizado';
    } catch (Exception $e) {
        return 'no autorizado';
    }
}

/**
 * GET /api/empresa_empleados.php
 * GET /api/empresa_empleados.php?empresa_id=1
 */
function handleGet($conn) {
    try {
        if (isset($_GET['empresa_id'])) {
            $empresa_id = (int)$_GET['empresa_id'];

            if ($empresa_id <= 0) {
                ApiResponse::badRequest('ID de empresa debe ser mayor a 0');
            }

            $stmt = $conn->prepare(
                "SELECT * FROM empresa_empleados WHERE empresa_id = ? ORDER BY paterno, nombre ASC"
            );
            if (!$stmt) {
                throw new Exception('Error preparando consulta: ' . $conn->error);
            }

            $stmt->bind_param("i", $empresa_id);
            $stmt->execute();
            $result = $stmt->get_result();

            $empleados = [];
            while ($row = $result->fetch_assoc()) {
                $row['id'] = (int)$row['id'];
                $row['empresa_id'] = (int)$row['empresa_id'];
                $row['acceso_permanente'] = (bool)$row['acceso_permanente'];
                $row['status'] = calculateStatus($row['acceso_permanente'], $row['fecha_expiracion']);
                $empleados[] = $row;
            }

            $stmt->close();
            ApiResponse::success($empleados);
        } else {
            // Obtener todos los empleados
            $result = $conn->query(
                "SELECT * FROM empresa_empleados ORDER BY empresa_id, paterno, nombre ASC"
            );

            if (!$result) {
                throw new Exception('Error en consulta: ' . $conn->error);
            }

            $empleados = [];
            while ($row = $result->fetch_assoc()) {
                $row['id'] = (int)$row['id'];
                $row['empresa_id'] = (int)$row['empresa_id'];
                $row['acceso_permanente'] = (bool)$row['acceso_permanente'];
                $row['status'] = calculateStatus($row['acceso_permanente'], $row['fecha_expiracion']);
                $empleados[] = $row;
            }

            ApiResponse::success($empleados);
        }
    } catch (Exception $e) {
        ApiResponse::serverError('Error al obtener empleados: ' . $e->getMessage());
    }
}

/**
 * POST /api/empresa_empleados.php
 */
function handlePost($conn) {
    try {
        $data = json_decode(file_get_contents('php://input'), true);

        // Validar campos requeridos
        if (empty($data['empresa_id'])) {
            ApiResponse::badRequest('Campo requerido: empresa_id');
        }

        if (empty(trim($data['nombre'] ?? ''))) {
            ApiResponse::badRequest('Campo requerido: nombre');
        }

        if (empty(trim($data['paterno'] ?? ''))) {
            ApiResponse::badRequest('Campo requerido: paterno');
        }

        if (empty(trim($data['rut'] ?? ''))) {
            ApiResponse::badRequest('Campo requerido: rut');
        }

        if (empty(trim($data['fecha_inicio'] ?? ''))) {
            ApiResponse::badRequest('Campo requerido: fecha_inicio');
        }

        $acceso_permanente = !empty($data['acceso_permanente']) ? 1 : 0;

        if (!$acceso_permanente && empty(trim($data['fecha_expiracion'] ?? ''))) {
            ApiResponse::badRequest('Campo requerido: fecha_expiracion (o activar acceso_permanente)');
        }

        // Normalizar datos
        $nombre = trim($data['nombre']);
        $paterno = trim($data['paterno']);
        $materno = isset($data['materno']) && !empty(trim($data['materno'])) ? trim($data['materno']) : null;
        $rut = trim($data['rut']);
        $fecha_inicio = $data['fecha_inicio'];
        $fecha_expiracion = $acceso_permanente ? null : ($data['fecha_expiracion'] ?? null);
        $status = calculateStatus($acceso_permanente, $fecha_expiracion);

        // Insertar
        $stmt = $conn->prepare(
            "INSERT INTO empresa_empleados
             (empresa_id, nombre, paterno, materno, rut, fecha_inicio, fecha_expiracion, acceso_permanente, status)
             VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)"
        );
        if (!$stmt) {
            throw new Exception('Error preparando inserción: ' . $conn->error);
        }

        $stmt->bind_param(
            "issssssis",
            $data['empresa_id'],
            $nombre,
            $paterno,
            $materno,
            $rut,
            $fecha_inicio,
            $fecha_expiracion,
            $acceso_permanente,
            $status
        );

        if (!$stmt->execute()) {
            throw new Exception('Error ejecutando inserción: ' . $stmt->error);
        }

        $insert_id = $stmt->insert_id;
        $stmt->close();

        // Obtener datos completos del empleado creado
        $stmt_get = $conn->prepare("SELECT * FROM empresa_empleados WHERE id = ?");
        if ($stmt_get) {
            $stmt_get->bind_param("i", $insert_id);
            $stmt_get->execute();
            $result_get = $stmt_get->get_result();
            $empleado = $result_get->fetch_assoc();
            $stmt_get->close();

            if ($empleado) {
                $empleado['id'] = (int)$empleado['id'];
                $empleado['empresa_id'] = (int)$empleado['empresa_id'];
                $empleado['acceso_permanente'] = (bool)$empleado['acceso_permanente'];

                ApiResponse::created($empleado, ['id' => $insert_id]);
                return;
            }
        }

        // Fallback
        $response = [
            'id' => $insert_id,
            'empresa_id' => $data['empresa_id'],
            'nombre' => $nombre,
            'paterno' => $paterno
        ];
        ApiResponse::created($response, ['id' => $insert_id]);

    } catch (Exception $e) {
        ApiResponse::serverError('Error al crear empleado: ' . $e->getMessage());
    }
}

/**
 * PUT /api/empresa_empleados.php
 */
function handlePut($conn) {
    try {
        $data = json_decode(file_get_contents('php://input'), true);

        // Validar ID
        if (empty($data['id'])) {
            ApiResponse::badRequest('Campo requerido: id');
        }

        $id = (int)$data['id'];

        // Validar campos requeridos
        if (empty(trim($data['nombre'] ?? ''))) {
            ApiResponse::badRequest('Campo requerido: nombre');
        }

        if (empty(trim($data['paterno'] ?? ''))) {
            ApiResponse::badRequest('Campo requerido: paterno');
        }

        if (empty(trim($data['rut'] ?? ''))) {
            ApiResponse::badRequest('Campo requerido: rut');
        }

        if (empty(trim($data['fecha_inicio'] ?? ''))) {
            ApiResponse::badRequest('Campo requerido: fecha_inicio');
        }

        // Verificar que existe
        $stmt_check = $conn->prepare("SELECT id FROM empresa_empleados WHERE id = ?");
        if (!$stmt_check) {
            throw new Exception('Error preparando verificación: ' . $conn->error);
        }

        $stmt_check->bind_param("i", $id);
        $stmt_check->execute();
        $result_check = $stmt_check->get_result();

        if ($result_check->num_rows === 0) {
            $stmt_check->close();
            ApiResponse::notFound('Empleado no encontrado');
        }
        $stmt_check->close();

        // Validar acceso
        $acceso_permanente = !empty($data['acceso_permanente']) ? 1 : 0;

        if (!$acceso_permanente && empty(trim($data['fecha_expiracion'] ?? ''))) {
            ApiResponse::badRequest('Campo requerido: fecha_expiracion (o activar acceso_permanente)');
        }

        // Normalizar datos
        $nombre = trim($data['nombre']);
        $paterno = trim($data['paterno']);
        $materno = isset($data['materno']) && !empty(trim($data['materno'])) ? trim($data['materno']) : null;
        $rut = trim($data['rut']);
        $fecha_inicio = $data['fecha_inicio'];
        $fecha_expiracion = $acceso_permanente ? null : ($data['fecha_expiracion'] ?? null);
        $status = calculateStatus($acceso_permanente, $fecha_expiracion);

        // Actualizar
        $stmt = $conn->prepare(
            "UPDATE empresa_empleados
             SET nombre=?, paterno=?, materno=?, rut=?, fecha_inicio=?, fecha_expiracion=?, acceso_permanente=?, status=?
             WHERE id=?"
        );
        if (!$stmt) {
            throw new Exception('Error preparando actualización: ' . $conn->error);
        }

        $stmt->bind_param(
            "ssssssisi",
            $nombre,
            $paterno,
            $materno,
            $rut,
            $fecha_inicio,
            $fecha_expiracion,
            $acceso_permanente,
            $status,
            $id
        );

        if (!$stmt->execute()) {
            throw new Exception('Error ejecutando actualización: ' . $stmt->error);
        }

        $stmt->close();

        // Obtener datos actualizados
        $stmt_get = $conn->prepare("SELECT * FROM empresa_empleados WHERE id = ?");
        if ($stmt_get) {
            $stmt_get->bind_param("i", $id);
            $stmt_get->execute();
            $result_get = $stmt_get->get_result();
            $empleado = $result_get->fetch_assoc();
            $stmt_get->close();

            if ($empleado) {
                $empleado['id'] = (int)$empleado['id'];
                $empleado['empresa_id'] = (int)$empleado['empresa_id'];
                $empleado['acceso_permanente'] = (bool)$empleado['acceso_permanente'];

                ApiResponse::success($empleado);
                return;
            }
        }

        ApiResponse::success(['id' => $id, 'message' => 'Empleado actualizado']);

    } catch (Exception $e) {
        ApiResponse::serverError('Error al actualizar empleado: ' . $e->getMessage());
    }
}

/**
 * DELETE /api/empresa_empleados.php?id=1
 * Soft delete: marca como inactivo
 */
function handleDelete($conn) {
    try {
        $id = isset($_GET['id']) ? (int)$_GET['id'] : null;

        if (!$id) {
            ApiResponse::badRequest('ID de empleado requerido');
        }

        // Soft delete: actualizar status a 'inactivo'
        $stmt = $conn->prepare("UPDATE empresa_empleados SET status = 'inactivo' WHERE id = ?");
        if (!$stmt) {
            throw new Exception('Error preparando actualización: ' . $conn->error);
        }

        $stmt->bind_param("i", $id);

        if (!$stmt->execute()) {
            throw new Exception('Error ejecutando actualización: ' . $stmt->error);
        }

        if ($stmt->affected_rows > 0) {
            $stmt->close();
            ApiResponse::noContent();
        } else {
            $stmt->close();
            ApiResponse::notFound('Empleado no encontrado');
        }

    } catch (Exception $e) {
        ApiResponse::serverError('Error al eliminar empleado: ' . $e->getMessage());
    }
}

?>

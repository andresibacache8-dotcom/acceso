<?php
/**
 * api/horas_extra.php
 * API de Gestión de Horas Extra (Refactorizado)
 *
 * Cambios en esta versión:
 * - Usa config/database.php (centralizado) en lugar de database/db_acceso.php
 * - Usa api/core/ResponseHandler.php para respuestas estandarizadas
 * - Implementa paginación para consultas GET
 * - Mantiene la lógica de negocio idéntica
 *
 * @author Refactorización 2025
 */

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/core/ResponseHandler.php';

// Iniciar sesión
session_start();

// Headers CORS y Content-Type
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, DELETE, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization');

// Manejar preflight OPTIONS
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}

// Verificar autenticación
if (!isset($_SESSION['logged_in']) || $_SESSION['logged_in'] !== true) {
    ApiResponse::unauthorized('No autorizado. Por favor, inicie sesión.');
}

// Obtener conexión a base de datos acceso_pro
$databaseConfig = DatabaseConfig::getInstance();
$conn = $databaseConfig->getAccesoConnection();

if (!$conn) {
    ApiResponse::serverError('Error de conexión a la base de datos');
}

$method = $_SERVER['REQUEST_METHOD'];

try {
    switch ($method) {
        case 'GET':
            handleGet($conn);
            break;

        case 'POST':
            handlePost($conn);
            break;

        case 'DELETE':
            handleDelete($conn);
            break;

        default:
            ApiResponse::error('Método no permitido', 405);
    }
} catch (Exception $e) {
    error_log('Error en horas_extra.php: ' . $e->getMessage());
    ApiResponse::serverError('Error procesando la solicitud: ' . $e->getMessage());
}

/**
 * Manejar GET - Obtener registros con paginación
 */
function handleGet($conn) {
    try {
        // Parámetros de paginación
        $page = isset($_GET['page']) ? max(1, (int)$_GET['page']) : 1;
        $perPage = isset($_GET['perPage']) ? max(1, min(500, (int)$_GET['perPage'])) : 50;
        $offset = ($page - 1) * $perPage;

        // Contar total de registros
        $countResult = $conn->query("SELECT COUNT(*) as total FROM horas_extra WHERE status = 'activo'");
        if (!$countResult) {
            throw new Exception("Error al contar registros: " . $conn->error);
        }

        $countRow = $countResult->fetch_assoc();
        $total = (int)$countRow['total'];

        // Obtener registros con paginación
        $query = "SELECT * FROM horas_extra
                 WHERE status = 'activo'
                 ORDER BY fecha_registro DESC
                 LIMIT {$perPage} OFFSET {$offset}";

        $result = $conn->query($query);

        if (!$result) {
            throw new Exception("Error al obtener registros: " . $conn->error);
        }

        $horas_extra = [];
        while ($row = $result->fetch_assoc()) {
            $horas_extra[] = [
                'id' => (int)$row['id'],
                'personal_rut' => $row['personal_rut'] ?? '',
                'personal_nombre' => $row['personal_nombre'] ?? '',
                'fecha_hora_termino' => $row['fecha_hora_termino'] ?? '',
                'motivo' => $row['motivo'] ?? '',
                'motivo_detalle' => $row['motivo_detalle'] ?? null,
                'autorizado_por_rut' => $row['autorizado_por_rut'] ?? '',
                'autorizado_por_nombre' => $row['autorizado_por_nombre'] ?? '',
                'fecha_registro' => $row['fecha_registro'] ?? '',
                'status' => $row['status'] ?? 'activo'
            ];
        }

        // Retornar con paginación
        ApiResponse::paginated($horas_extra, $page, $perPage, $total);

    } catch (Exception $e) {
        throw $e;
    }
}

/**
 * Manejar POST - Crear registros de horas extra
 */
function handlePost($conn) {
    try {
        $data = json_decode(file_get_contents('php://input'), true);

        // Validar estructura básica
        if (!isset($data['personal']) || !is_array($data['personal']) || empty($data['personal'])) {
            ApiResponse::badRequest('Debe proporcionar al menos una persona en el array "personal".');
        }

        // Validar campos requeridos
        $requiredFields = ['fecha_hora_termino', 'motivo', 'autorizado_por_rut', 'autorizado_por_nombre'];
        foreach ($requiredFields as $field) {
            if (!isset($data[$field]) || empty(trim($data[$field]))) {
                ApiResponse::badRequest("Falta campo requerido: $field.");
            }
        }

        // Validar formato de datetime
        if (!strtotime($data['fecha_hora_termino'])) {
            ApiResponse::badRequest('Formato de fecha_hora_termino inválido. Use formato YYYY-MM-DD HH:MM:SS.');
        }

        // Preparar statement
        $stmt = $conn->prepare(
            "INSERT INTO horas_extra (personal_rut, personal_nombre, fecha_hora_termino, motivo, motivo_detalle, autorizado_por_rut, autorizado_por_nombre)
             VALUES (?, ?, ?, ?, ?, ?, ?)"
        );

        if (!$stmt) {
            throw new Exception("Error preparando la consulta: " . $conn->error);
        }

        $motivo_detalle = isset($data['motivo_detalle']) && !empty(trim($data['motivo_detalle']))
            ? trim($data['motivo_detalle'])
            : null;

        // Usar transacción
        $conn->begin_transaction();
        $insert_count = 0;

        foreach ($data['personal'] as $index => $persona) {
            // Validar estructura de persona
            if (!isset($persona['rut']) || empty(trim($persona['rut']))) {
                throw new Exception("Persona en índice $index no tiene RUT.");
            }
            if (!isset($persona['nombre']) || empty(trim($persona['nombre']))) {
                throw new Exception("Persona en índice $index no tiene nombre.");
            }

            $personal_rut = trim($persona['rut']);
            $personal_nombre = trim($persona['nombre']);

            $stmt->bind_param(
                "sssssss",
                $personal_rut,
                $personal_nombre,
                $data['fecha_hora_termino'],
                $data['motivo'],
                $motivo_detalle,
                $data['autorizado_por_rut'],
                $data['autorizado_por_nombre']
            );

            if (!$stmt->execute()) {
                throw new Exception("Error al registrar horas para $personal_nombre: " . $stmt->error);
            }

            $insert_count++;
        }

        $conn->commit();
        $stmt->close();

        // Respuesta exitosa
        ApiResponse::created(
            [
                'message' => "Registros de horas extra creados correctamente ($insert_count registros).",
                'count' => $insert_count,
                'success' => true
            ],
            ['records_created' => $insert_count]
        );

    } catch (Exception $e) {
        if ($conn->in_transaction) {
            $conn->rollback();
        }
        throw $e;
    }
}

/**
 * Manejar DELETE - Eliminar registro (borrado lógico)
 */
function handleDelete($conn) {
    try {
        $id = $_GET['id'] ?? null;

        if (!$id) {
            ApiResponse::badRequest('ID de horas extra no proporcionado.');
        }

        $id = (int)$id;

        // Borrado lógico: actualizar status a 'inactivo'
        $stmt = $conn->prepare("UPDATE horas_extra SET status = 'inactivo' WHERE id = ?");

        if (!$stmt) {
            throw new Exception("Error preparando la consulta: " . $conn->error);
        }

        $stmt->bind_param("i", $id);

        if (!$stmt->execute()) {
            throw new Exception("Error al ejecutar la consulta: " . $stmt->error);
        }

        if ($stmt->affected_rows > 0) {
            // Eliminado correctamente
            ApiResponse::noContent();
        } else {
            // No existe el registro
            ApiResponse::notFound('Registro de horas extra no encontrado.');
        }

        $stmt->close();

    } catch (Exception $e) {
        throw $e;
    }
}
?>

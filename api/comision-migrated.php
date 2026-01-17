<?php
/**
 * api/comision-migrated.php
 * API para gestión de comisiones de personal
 *
 * Migración desde comision.php original:
 * - Config: database/db_personal.php → config/database.php
 * - Respuestas: echo json_encode() → ApiResponse::*()
 * - Estructura: métodos HTTP estándar
 *
 * Endpoints:
 * GET    /api/comision.php              - Listar todas las comisiones
 * POST   /api/comision.php              - Crear nueva comisión
 * PUT    /api/comision.php              - Actualizar comisión
 * DELETE /api/comision.php?id=1         - Eliminar comisión
 *
 * @version 2.0 (Migrated)
 */

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/core/ResponseHandler.php';
require_once __DIR__ . '/core/AuthMiddleware.php';
require_once __DIR__ . '/core/AuditLogger.php';
require_once __DIR__ . '/core/SecurityHeaders.php';

// Headers
// Aplicar security headers
SecurityHeaders::applyApiHeaders();

// Manejar preflight CORS
SecurityHeaders::handleCors();

// Obtener conexión desde DatabaseConfig singleton
$databaseConfig = DatabaseConfig::getInstance();
$conn = $databaseConfig->getPersonalConnection();

if (!$conn) {
    ApiResponse::serverError('Error conectando a base de datos');
}

try {
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

} catch (Exception $e) {
    ApiResponse::serverError('Error: ' . $e->getMessage());
}

/**
 * Calcular estado de comisión basado en fecha de fin
 */
function calculateComisionStatus($fecha_fin_str) {
    if (empty($fecha_fin_str)) {
        return 'Activo';
    }

    try {
        $fecha_fin = new DateTime($fecha_fin_str);
        $today = new DateTime('today');
        return ($fecha_fin >= $today) ? 'Activo' : 'Finalizado';
    } catch (Exception $e) {
        return 'Activo';
    }
}

/**
 * GET /api/comision.php
 * Listar todas las comisiones activas
 */
function handleGet($conn) {
    try {
        $sql = "SELECT
                    id,
                    rut,
                    grado,
                    nombres,
                    paterno,
                    materno,
                    CONCAT_WS(' ', grado, nombres, paterno, materno) as nombre_completo,
                    unidad_origen,
                    unidad_poc,
                    DATE_FORMAT(fecha_inicio, '%Y-%m-%d') as fecha_inicio,
                    DATE_FORMAT(fecha_fin, '%Y-%m-%d') as fecha_fin,
                    motivo,
                    poc_nombre,
                    poc_anexo,
                    estado
                FROM personal_comision
                ORDER BY paterno, materno, nombres ASC";

        $result = $conn->query($sql);

        if (!$result) {
            throw new Exception('Error en consulta: ' . $conn->error);
        }

        $comisiones = [];
        while ($row = $result->fetch_assoc()) {
            // Normalizar tipos de datos
            $row['id'] = (int)$row['id'];
            $comisiones[] = $row;
        }

        if (count($comisiones) > 0) {
            ApiResponse::success($comisiones);
        } else {
            ApiResponse::notFound('No hay comisiones registradas');
        }

    } catch (Exception $e) {
        ApiResponse::serverError('Error al obtener comisiones: ' . $e->getMessage());
    }
}

/**
 * POST /api/comision.php
 * Crear nueva comisión
 *
 * Parámetros (JSON):
 * - rut: RUT del personal (requerido)
 * - grado: Grado militar (requerido)
 * - nombres: Nombres (requerido)
 * - paterno: Apellido paterno (requerido)
 * - materno: Apellido materno (opcional)
 * - unidad_origen: Unidad de origen (requerido)
 * - unidad_poc: Unidad POC (requerido)
 * - fecha_inicio: Fecha inicio de comisión (requerido)
 * - fecha_fin: Fecha fin de comisión (requerido)
 * - motivo: Motivo de comisión (requerido)
 * - poc_nombre: Nombre del POC (requerido)
 * - poc_anexo: Anexo del POC (requerido)
 */
function handlePost($conn) {
    try {
        $data = json_decode(file_get_contents('php://input'), true);

        // Validar campos requeridos
        $required_fields = ['rut', 'grado', 'nombres', 'paterno', 'unidad_origen',
                          'unidad_poc', 'fecha_inicio', 'fecha_fin', 'motivo', 'poc_nombre', 'poc_anexo'];

        foreach ($required_fields as $field) {
            if (empty($data[$field])) {
                ApiResponse::badRequest("Campo requerido: $field");
            }
        }

        // Normalizar datos
        $rut = trim($data['rut']);
        $grado = trim($data['grado']);
        $nombres = trim($data['nombres']);
        $paterno = trim($data['paterno']);
        $materno = isset($data['materno']) && !empty(trim($data['materno'])) ? trim($data['materno']) : null;
        $unidad_origen = trim($data['unidad_origen']);
        $unidad_poc = trim($data['unidad_poc']);
        $fecha_inicio = $data['fecha_inicio'];
        $fecha_fin = $data['fecha_fin'];
        $motivo = trim($data['motivo']);
        $poc_nombre = trim($data['poc_nombre']);
        $poc_anexo = trim($data['poc_anexo']);
        $estado = calculateComisionStatus($fecha_fin);

        // Insertar
        $stmt = $conn->prepare(
            "INSERT INTO personal_comision
             (rut, grado, nombres, paterno, materno, unidad_origen, unidad_poc, fecha_inicio, fecha_fin, motivo, poc_nombre, poc_anexo, estado)
             VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)"
        );

        if (!$stmt) {
            throw new Exception('Error preparando inserción: ' . $conn->error);
        }

        $stmt->bind_param(
            "sssssssssssss",
            $rut,
            $grado,
            $nombres,
            $paterno,
            $materno,
            $unidad_origen,
            $unidad_poc,
            $fecha_inicio,
            $fecha_fin,
            $motivo,
            $poc_nombre,
            $poc_anexo,
            $estado
        );

        if (!$stmt->execute()) {
            throw new Exception('Error ejecutando inserción: ' . $stmt->error);
        }

        $insert_id = $stmt->insert_id;
        $stmt->close();

        // Retornar comisión creada
        $response = [
            'id' => $insert_id,
            'rut' => $rut,
            'nombres' => $nombres,
            'paterno' => $paterno,
            'grado' => $grado,
            'unidad_origen' => $unidad_origen,
            'unidad_poc' => $unidad_poc,
            'fecha_inicio' => $fecha_inicio,
            'fecha_fin' => $fecha_fin,
            'motivo' => $motivo,
            'poc_nombre' => $poc_nombre,
            'poc_anexo' => $poc_anexo,
            'estado' => $estado
        ];

        ApiResponse::created($response, ['id' => $insert_id]);

    } catch (Exception $e) {
        ApiResponse::serverError('Error al crear comisión: ' . $e->getMessage());
    }
}

/**
 * PUT /api/comision.php
 * Actualizar comisión
 *
 * Parámetros (JSON):
 * - id: ID de la comisión (requerido)
 * - Todos los campos de POST para actualización
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
        $required_fields = ['rut', 'grado', 'nombres', 'paterno', 'unidad_origen',
                          'unidad_poc', 'fecha_inicio', 'fecha_fin', 'motivo', 'poc_nombre', 'poc_anexo'];

        foreach ($required_fields as $field) {
            if (empty($data[$field])) {
                ApiResponse::badRequest("Campo requerido: $field");
            }
        }

        // Verificar que existe
        $stmt_check = $conn->prepare("SELECT id FROM personal_comision WHERE id = ?");
        if (!$stmt_check) {
            throw new Exception('Error preparando verificación: ' . $conn->error);
        }

        $stmt_check->bind_param("i", $id);
        $stmt_check->execute();
        $result_check = $stmt_check->get_result();

        if ($result_check->num_rows === 0) {
            $stmt_check->close();
            ApiResponse::notFound('Comisión no encontrada');
        }
        $stmt_check->close();

        // Normalizar datos
        $rut = trim($data['rut']);
        $grado = trim($data['grado']);
        $nombres = trim($data['nombres']);
        $paterno = trim($data['paterno']);
        $materno = isset($data['materno']) && !empty(trim($data['materno'])) ? trim($data['materno']) : null;
        $unidad_origen = trim($data['unidad_origen']);
        $unidad_poc = trim($data['unidad_poc']);
        $fecha_inicio = $data['fecha_inicio'];
        $fecha_fin = $data['fecha_fin'];
        $motivo = trim($data['motivo']);
        $poc_nombre = trim($data['poc_nombre']);
        $poc_anexo = trim($data['poc_anexo']);
        $estado = calculateComisionStatus($fecha_fin);

        // Actualizar
        $stmt = $conn->prepare(
            "UPDATE personal_comision
             SET rut=?, grado=?, nombres=?, paterno=?, materno=?, unidad_origen=?, unidad_poc=?,
                 fecha_inicio=?, fecha_fin=?, motivo=?, poc_nombre=?, poc_anexo=?, estado=?
             WHERE id=?"
        );

        if (!$stmt) {
            throw new Exception('Error preparando actualización: ' . $conn->error);
        }

        $stmt->bind_param(
            "sssssssssssssi",
            $rut,
            $grado,
            $nombres,
            $paterno,
            $materno,
            $unidad_origen,
            $unidad_poc,
            $fecha_inicio,
            $fecha_fin,
            $motivo,
            $poc_nombre,
            $poc_anexo,
            $estado,
            $id
        );

        if (!$stmt->execute()) {
            throw new Exception('Error ejecutando actualización: ' . $stmt->error);
        }

        $stmt->close();

        // Retornar comisión actualizada
        $response = [
            'id' => $id,
            'rut' => $rut,
            'nombres' => $nombres,
            'paterno' => $paterno,
            'grado' => $grado,
            'unidad_origen' => $unidad_origen,
            'unidad_poc' => $unidad_poc,
            'fecha_inicio' => $fecha_inicio,
            'fecha_fin' => $fecha_fin,
            'motivo' => $motivo,
            'poc_nombre' => $poc_nombre,
            'poc_anexo' => $poc_anexo,
            'estado' => $estado
        ];

        ApiResponse::success($response);

    } catch (Exception $e) {
        ApiResponse::serverError('Error al actualizar comisión: ' . $e->getMessage());
    }
}

/**
 * DELETE /api/comision.php?id=1
 * Eliminar comisión (hard delete)
 */
function handleDelete($conn) {
    try {
        $id = isset($_GET['id']) ? (int)$_GET['id'] : null;

        if (!$id) {
            ApiResponse::badRequest('ID de comisión requerido');
        }

        // Eliminar
        $stmt = $conn->prepare("DELETE FROM personal_comision WHERE id = ?");
        if (!$stmt) {
            throw new Exception('Error preparando eliminación: ' . $conn->error);
        }

        $stmt->bind_param("i", $id);

        if (!$stmt->execute()) {
            throw new Exception('Error ejecutando eliminación: ' . $stmt->error);
        }

        if ($stmt->affected_rows > 0) {
            $stmt->close();
            ApiResponse::noContent();
        } else {
            $stmt->close();
            ApiResponse::notFound('Comisión no encontrada');
        }

    } catch (Exception $e) {
        ApiResponse::serverError('Error al eliminar comisión: ' . $e->getMessage());
    }
}

?>

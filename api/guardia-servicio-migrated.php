<?php
/**
 * api/guardia-servicio-migrated.php
 * API para gestión de personal de Guardia y Servicio
 *
 * Migración desde guardia-servicio.php original:
 * - Config: database/db_acceso.php → config/database.php (DatabaseConfig)
 * - Respuestas: echo json_encode() → ApiResponse::*()
 * - Estructura: acciones vía query params → métodos HTTP formales
 *
 * Endpoints:
 * GET  /api/guardia-servicio.php              - Listar registros activos
 * GET  /api/guardia-servicio.php?action=verify&rut=XXX - Verificar RUT
 * GET  /api/guardia-servicio.php?action=history - Listar historial
 * POST /api/guardia-servicio.php              - Crear nuevo registro
 * POST /api/guardia-servicio.php?action=finish - Finalizar registro
 *
 * @version 2.0 (Migrated)
 */

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/core/ResponseHandler.php';

// Headers
header('Content-Type: application/json');

// Obtener conexiones desde DatabaseConfig singleton
$databaseConfig = DatabaseConfig::getInstance();
$conn_acceso = $databaseConfig->getAccesoConnection();
$conn_personal = $databaseConfig->getPersonalConnection();

if (!$conn_acceso || !$conn_personal) {
    ApiResponse::serverError('Error conectando a base de datos');
}

try {
    // Obtener método HTTP y acción
    $method = $_SERVER['REQUEST_METHOD'];
    $action = $_GET['action'] ?? $_POST['action'] ?? null;

    // Router de métodos HTTP
    switch ($method) {
        case 'GET':
            handleGet($conn_acceso, $conn_personal, $action);
            break;
        case 'POST':
            handlePost($conn_acceso, $conn_personal, $action);
            break;
        default:
            ApiResponse::error('Método no permitido', 405);
    }

} catch (Exception $e) {
    ApiResponse::serverError('Error: ' . $e->getMessage());
}

/**
 * GET /api/guardia-servicio.php
 * GET /api/guardia-servicio.php?action=verify&rut=XXX
 * GET /api/guardia-servicio.php?action=history&page=1&perPage=50
 *
 * @param mysqli $conn_acceso Conexión a BD acceso
 * @param mysqli $conn_personal Conexión a BD personal
 * @param string|null $action Acción específica (verify, history)
 */
function handleGet($conn_acceso, $conn_personal, $action)
{
    try {
        // Action: verify - Verificar si RUT tiene registro activo
        if ($action === 'verify') {
            verifyGuardiaRut($conn_acceso);
            return;
        }

        // Action: history - Listar historial con paginación
        if ($action === 'history') {
            getGuardiaHistory($conn_acceso, $conn_personal);
            return;
        }

        // Default: Listar registros activos
        listGuardiaActivos($conn_acceso, $conn_personal);

    } catch (Exception $e) {
        ApiResponse::serverError('Error en GET: ' . $e->getMessage());
    }
}

/**
 * POST /api/guardia-servicio.php
 * POST /api/guardia-servicio.php?action=finish
 *
 * @param mysqli $conn_acceso Conexión a BD acceso
 * @param mysqli $conn_personal Conexión a BD personal
 * @param string|null $action Acción específica (finish)
 */
function handlePost($conn_acceso, $conn_personal, $action)
{
    try {
        $data = json_decode(file_get_contents('php://input'), true);

        // Action: finish - Finalizar/cerrar registro
        if ($action === 'finish') {
            finishGuardia($conn_acceso, $conn_personal, $data);
            return;
        }

        // Default: Crear nuevo registro
        createGuardia($conn_acceso, $conn_personal, $data);

    } catch (Exception $e) {
        ApiResponse::serverError('Error en POST: ' . $e->getMessage());
    }
}

/**
 * GET /api/guardia-servicio.php
 * Listar todos los registros activos de guardia/servicio
 *
 * @param mysqli $conn_acceso Conexión a BD acceso
 * @param mysqli $conn_personal Conexión a BD personal
 */
function listGuardiaActivos($conn_acceso, $conn_personal)
{
    $sql = "SELECT
                gs.id,
                gs.personal_rut,
                gs.personal_nombre,
                gs.tipo,
                gs.servicio_detalle,
                gs.anexo,
                gs.movil,
                gs.fecha_ingreso,
                gs.status,
                p.Grado
            FROM guardia_servicio gs
            LEFT JOIN personal p ON gs.personal_rut = p.NrRut
            WHERE gs.status = 'ACTIVO'
            ORDER BY gs.fecha_ingreso DESC, gs.id DESC";

    $result = $conn_acceso->query($sql);

    if (!$result) {
        throw new Exception('Error en consulta: ' . $conn_acceso->error);
    }

    $data = [];
    while ($row = $result->fetch_assoc()) {
        // Normalizar tipos de datos
        $row['id'] = (int)$row['id'];
        $data[] = $row;
    }

    if (count($data) > 0) {
        ApiResponse::success($data);
    } else {
        ApiResponse::notFound('No hay registros activos de guardia/servicio');
    }
}

/**
 * POST /api/guardia-servicio.php
 * Crear nuevo registro de guardia/servicio
 *
 * Parámetros (JSON):
 * - personal_rut: RUT del personal (requerido)
 * - personal_nombre: Nombre del personal (requerido)
 * - tipo: GUARDIA o SERVICIO (requerido)
 * - fecha_ingreso: Fecha/hora de ingreso (requerido)
 * - servicio_detalle: Detalle del servicio (opcional)
 * - anexo: Anexo/teléfono (opcional)
 * - movil: Móvil del personal (opcional)
 *
 * @param mysqli $conn_acceso Conexión a BD acceso
 * @param mysqli $conn_personal Conexión a BD personal
 * @param array $data Datos del request
 */
function createGuardia($conn_acceso, $conn_personal, $data)
{
    // Validar campos requeridos
    if (empty($data['personal_rut']) || empty($data['personal_nombre']) ||
        empty($data['tipo']) || empty($data['fecha_ingreso'])) {
        ApiResponse::badRequest('Campos requeridos: personal_rut, personal_nombre, tipo, fecha_ingreso');
    }

    // Validar tipo
    if (!in_array($data['tipo'], ['GUARDIA', 'SERVICIO'])) {
        ApiResponse::badRequest('Tipo inválido. Debe ser GUARDIA o SERVICIO.');
    }

    // Verificar si el personal ya tiene un registro activo
    $stmt = $conn_acceso->prepare(
        "SELECT id FROM guardia_servicio
         WHERE personal_rut = ? AND status = 'ACTIVO'"
    );
    if (!$stmt) {
        throw new Exception('Error preparando verificación: ' . $conn_acceso->error);
    }

    $stmt->bind_param("s", $data['personal_rut']);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows > 0) {
        $stmt->close();
        ApiResponse::conflict('Este personal ya tiene un registro de guardia/servicio activo. Debe finalizarlo primero.');
    }
    $stmt->close();

    // Normalizar datos opcionales
    $servicio_detalle = $data['servicio_detalle'] ?? null;
    $anexo = $data['anexo'] ?? null;
    $movil = $data['movil'] ?? null;

    // Insertar nuevo registro
    $sql = "INSERT INTO guardia_servicio
            (personal_rut, personal_nombre, tipo, servicio_detalle, anexo, movil, fecha_ingreso, status)
            VALUES (?, ?, ?, ?, ?, ?, ?, 'ACTIVO')";

    $stmt = $conn_acceso->prepare($sql);
    if (!$stmt) {
        throw new Exception('Error preparando inserción: ' . $conn_acceso->error);
    }

    $stmt->bind_param(
        "sssssss",
        $data['personal_rut'],
        $data['personal_nombre'],
        $data['tipo'],
        $servicio_detalle,
        $anexo,
        $movil,
        $data['fecha_ingreso']
    );

    if (!$stmt->execute()) {
        throw new Exception('Error ejecutando inserción: ' . $stmt->error);
    }

    $new_id = $conn_acceso->insert_id;
    $stmt->close();

    // Registrar entrada en access_logs
    $stmt_personal = $conn_acceso->prepare("SELECT id FROM personal WHERE NrRut = ?");
    if (!$stmt_personal) {
        throw new Exception('Error preparando búsqueda personal: ' . $conn_acceso->error);
    }

    $stmt_personal->bind_param("s", $data['personal_rut']);
    $stmt_personal->execute();
    $result_personal = $stmt_personal->get_result();

    if ($row_personal = $result_personal->fetch_assoc()) {
        $personal_id = $row_personal['id'];
        $motivo = $data['tipo'] === 'GUARDIA' ? 'Guardia' : 'Servicio';

        $stmt_log = $conn_acceso->prepare(
            "INSERT INTO access_logs
             (target_id, target_type, action, punto_acceso, motivo, log_time)
             VALUES (?, 'personal', 'entrada', 'guardia', ?, NOW())"
        );
        if ($stmt_log) {
            $stmt_log->bind_param("is", $personal_id, $motivo);
            $stmt_log->execute();
            $stmt_log->close();
        }
    }
    $stmt_personal->close();

    // Retornar registro creado
    $response = [
        'id' => $new_id,
        'personal_rut' => $data['personal_rut'],
        'personal_nombre' => $data['personal_nombre'],
        'tipo' => $data['tipo'],
        'fecha_ingreso' => $data['fecha_ingreso'],
        'status' => 'ACTIVO'
    ];

    ApiResponse::created($response, ['id' => $new_id]);
}

/**
 * POST /api/guardia-servicio.php?action=finish
 * Finalizar registro (marcar salida)
 *
 * Parámetros (JSON):
 * - id: ID del registro a finalizar (requerido)
 *
 * @param mysqli $conn_acceso Conexión a BD acceso
 * @param mysqli $conn_personal Conexión a BD personal
 * @param array $data Datos del request
 */
function finishGuardia($conn_acceso, $conn_personal, $data)
{
    if (empty($data['id'])) {
        ApiResponse::badRequest('ID requerido para finalizar registro');
    }

    $id = (int)$data['id'];

    // Obtener datos del registro antes de finalizarlo
    $stmt_get = $conn_acceso->prepare(
        "SELECT personal_rut, tipo FROM guardia_servicio WHERE id = ?"
    );
    if (!$stmt_get) {
        throw new Exception('Error preparando búsqueda: ' . $conn_acceso->error);
    }

    $stmt_get->bind_param("i", $id);
    $stmt_get->execute();
    $result_get = $stmt_get->get_result();
    $registro = $result_get->fetch_assoc();
    $stmt_get->close();

    if (!$registro) {
        ApiResponse::notFound('Registro no encontrado');
    }

    // Actualizar registro a FINALIZADO
    $stmt = $conn_acceso->prepare(
        "UPDATE guardia_servicio
         SET status = 'FINALIZADO'
         WHERE id = ?"
    );
    if (!$stmt) {
        throw new Exception('Error preparando actualización: ' . $conn_acceso->error);
    }

    $stmt->bind_param("i", $id);

    if (!$stmt->execute()) {
        throw new Exception('Error ejecutando actualización: ' . $stmt->error);
    }

    if ($stmt->affected_rows === 0) {
        $stmt->close();
        ApiResponse::serverError('No se encontró el registro o ya estaba finalizado');
    }
    $stmt->close();

    // Registrar salida en access_logs
    $stmt_personal = $conn_acceso->prepare("SELECT id FROM personal WHERE NrRut = ?");
    if (!$stmt_personal) {
        throw new Exception('Error preparando búsqueda personal: ' . $conn_acceso->error);
    }

    $stmt_personal->bind_param("s", $registro['personal_rut']);
    $stmt_personal->execute();
    $result_personal = $stmt_personal->get_result();

    if ($row_personal = $result_personal->fetch_assoc()) {
        $personal_id = $row_personal['id'];
        $motivo = $registro['tipo'] === 'GUARDIA' ? 'Guardia' : 'Servicio';

        $stmt_log = $conn_acceso->prepare(
            "INSERT INTO access_logs
             (target_id, target_type, action, punto_acceso, motivo, log_time)
             VALUES (?, 'personal', 'salida', 'guardia', ?, NOW())"
        );
        if ($stmt_log) {
            $stmt_log->bind_param("is", $personal_id, $motivo);
            $stmt_log->execute();
            $stmt_log->close();
        }
    }
    $stmt_personal->close();

    // Retornar éxito
    $response = [
        'id' => $id,
        'status' => 'FINALIZADO',
        'message' => 'Registro finalizado exitosamente'
    ];

    ApiResponse::success($response);
}

/**
 * GET /api/guardia-servicio.php?action=verify&rut=XXX
 * Verificar si un RUT tiene registro activo
 *
 * @param mysqli $conn_acceso Conexión a BD acceso
 */
function verifyGuardiaRut($conn_acceso)
{
    $rut = $_GET['rut'] ?? '';

    if (empty($rut)) {
        ApiResponse::badRequest('RUT requerido');
    }

    $stmt = $conn_acceso->prepare(
        "SELECT id, tipo, fecha_ingreso
         FROM guardia_servicio
         WHERE personal_rut = ? AND status = 'ACTIVO'"
    );
    if (!$stmt) {
        throw new Exception('Error preparando búsqueda: ' . $conn_acceso->error);
    }

    $stmt->bind_param("s", $rut);
    $stmt->execute();
    $result = $stmt->get_result();

    $data = $result->fetch_assoc();
    $stmt->close();

    $response = [
        'has_active' => $data !== null,
        'data' => $data
    ];

    ApiResponse::success($response);
}

/**
 * GET /api/guardia-servicio.php?action=history&page=1&perPage=50
 * Listar historial completo (activos y finalizados) con paginación
 *
 * @param mysqli $conn_acceso Conexión a BD acceso
 * @param mysqli $conn_personal Conexión a BD personal
 */
function getGuardiaHistory($conn_acceso, $conn_personal)
{
    $page = isset($_GET['page']) ? max(1, (int)$_GET['page']) : 1;
    $perPage = isset($_GET['perPage']) ? max(1, min(500, (int)$_GET['perPage'])) : 50;
    $offset = ($page - 1) * $perPage;

    // Obtener total de registros
    $countResult = $conn_acceso->query("SELECT COUNT(*) as total FROM guardia_servicio");
    $countRow = $countResult->fetch_assoc();
    $total = (int)$countRow['total'];

    // Obtener registros paginados
    $sql = "SELECT
                gs.id,
                gs.personal_rut,
                gs.personal_nombre,
                gs.tipo,
                gs.servicio_detalle,
                gs.anexo,
                gs.movil,
                gs.fecha_ingreso,
                gs.status,
                gs.fecha_registro,
                p.Grado
            FROM guardia_servicio gs
            LEFT JOIN personal p ON gs.personal_rut = p.NrRut
            ORDER BY gs.fecha_ingreso DESC, gs.id DESC
            LIMIT ? OFFSET ?";

    $stmt = $conn_acceso->prepare($sql);
    if (!$stmt) {
        throw new Exception('Error preparando búsqueda: ' . $conn_acceso->error);
    }

    $stmt->bind_param("ii", $perPage, $offset);
    $stmt->execute();
    $result = $stmt->get_result();

    $data = [];
    while ($row = $result->fetch_assoc()) {
        $row['id'] = (int)$row['id'];
        $data[] = $row;
    }
    $stmt->close();

    ApiResponse::paginated($data, $page, $perPage, $total);
}

?>

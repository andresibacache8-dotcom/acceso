<?php
/**
 * api/vehiculos-migrated.php
 *
 * API RESTful para gestión de vehículos
 * CRUD completo + validación de patentes + historial de cambios
 *
 * Métodos:
 * - GET    : Listar vehículos (paginado)
 * - POST   : Crear vehículo
 * - PUT    : Actualizar vehículo
 * - DELETE : Eliminar vehículo
 */

// ============================================================================
// CONFIGURATION & IMPORTS
// ============================================================================

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/core/ResponseHandler.php';
require_once __DIR__ . '/core/Paginator.php';

// Headers
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization');

// Session
session_start();

// Preflight
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}

// ============================================================================
// AUTHENTICATION
// ============================================================================

if (!isset($_SESSION['logged_in']) || $_SESSION['logged_in'] !== true) {
    ApiResponse::unauthorized('No autorizado. Por favor, inicie sesión.');
}

// ============================================================================
// HELPER FUNCTIONS
// ============================================================================

/**
 * Valida formato de patente chilena
 * Soporta 5 formatos:
 * - AA1234 (antiguo auto)
 * - ABCD12 (nuevo auto)
 * - ABC12 (nuevo moto)
 * - AB123 (antiguo moto)
 * - ABC123 (remolque)
 */
function validar_patente_chilena($patente) {
    $patente = strtoupper(trim($patente));

    $formatos = [
        '/^[A-Z]{2}[0-9]{4}$/',                    // AA1234
        '/^[B-DF-HJ-NP-TV-Z]{4}[0-9]{2}$/',        // ABCD12
        '/^[B-DF-HJ-NP-TV-Z]{3}[0-9]{2}$/',        // ABC12
        '/^[A-Z]{2}[0-9]{3}$/',                    // AB123
        '/^[A-Z]{3}[0-9]{3}$/',                    // ABC123
    ];

    foreach ($formatos as $formato) {
        if (preg_match($formato, $patente)) {
            return true;
        }
    }

    return false;
}

/**
 * Calcula estado basado en fechas
 */
function get_status_by_date($is_permanent, $expiration_date_str) {
    if ($is_permanent) {
        return 'autorizado';
    }

    if (empty($expiration_date_str)) {
        return 'no autorizado';
    }

    try {
        $expiration_date = new DateTime($expiration_date_str);
        $today = new DateTime('today');
        return ($expiration_date >= $today) ? 'autorizado' : 'no autorizado';
    } catch (Exception $e) {
        return 'no autorizado';
    }
}

/**
 * Resuelve asociado_id desde RUT o usa directo
 * Retorna: ['id' => int|null, 'error' => string|null]
 */
function resolver_asociado($tipo, $rut_o_id, $conn_personal, $conn_acceso) {
    // Si ya es un ID directo
    if (is_numeric($rut_o_id) && (int)$rut_o_id > 0) {
        return ['id' => (int)$rut_o_id, 'error' => null];
    }

    // Si es RUT, buscar en BD correspondiente
    if (!empty($rut_o_id)) {
        $tipo = strtoupper(trim($tipo));

        switch ($tipo) {
            case 'PERSONAL':
            case 'FUNCIONARIO':
            case 'RESIDENTE':
            case 'FISCAL':
                $stmt = $conn_personal->prepare("SELECT id FROM personal WHERE NrRut = ? LIMIT 1");
                if (!$stmt) {
                    return ['id' => null, 'error' => "Error DB: " . $conn_personal->error];
                }
                $stmt->bind_param("s", $rut_o_id);
                $stmt->execute();
                $result = $stmt->get_result();
                $row = $result->fetch_assoc();
                $stmt->close();

                return $row
                    ? ['id' => (int)$row['id'], 'error' => null]
                    : ['id' => null, 'error' => "RUT de $tipo no encontrado"];
                break;

            case 'EMPLEADO':
            case 'EMPRESA':
                $stmt = $conn_acceso->prepare("SELECT id FROM empresa_empleados WHERE rut = ? LIMIT 1");
                if (!$stmt) {
                    return ['id' => null, 'error' => "Error DB: " . $conn_acceso->error];
                }
                $stmt->bind_param("s", $rut_o_id);
                $stmt->execute();
                $result = $stmt->get_result();
                $row = $result->fetch_assoc();
                $stmt->close();

                return $row
                    ? ['id' => (int)$row['id'], 'error' => null]
                    : ['id' => null, 'error' => "RUT de empleado no encontrado"];
                break;

            case 'VISITA':
                $stmt = $conn_acceso->prepare("SELECT id FROM visitas WHERE rut = ? LIMIT 1");
                if (!$stmt) {
                    return ['id' => null, 'error' => "Error DB: " . $conn_acceso->error];
                }
                $stmt->bind_param("s", $rut_o_id);
                $stmt->execute();
                $result = $stmt->get_result();
                $row = $result->fetch_assoc();
                $stmt->close();

                return $row
                    ? ['id' => (int)$row['id'], 'error' => null]
                    : ['id' => null, 'error' => "RUT de visita no encontrado"];
                break;

            default:
                return ['id' => null, 'error' => "Tipo de asociado no válido: $tipo"];
        }
    }

    return ['id' => null, 'error' => null];
}

/**
 * Obtiene datos completos de vehículo con JOINs
 */
function obtener_vehiculo($conn_acceso, $conn_personal, $vehiculo_id) {
    $sql = "SELECT
                v.id, v.patente, v.marca, v.modelo, v.tipo, v.tipo_vehiculo,
                v.asociado_id, v.asociado_tipo, v.status, v.fecha_inicio, v.fecha_expiracion, v.acceso_permanente,
                CASE
                    WHEN v.tipo IN ('PERSONAL', 'FUNCIONARIO', 'RESIDENTE', 'FISCAL')
                        THEN TRIM(CONCAT_WS(' ', p.Grado, p.Nombres, p.Paterno, p.Materno))
                    WHEN v.tipo IN ('EMPLEADO', 'EMPRESA')
                        THEN TRIM(CONCAT_WS(' ', ee.nombre, ee.paterno, ee.materno))
                    WHEN v.tipo = 'VISITA'
                        THEN vis.nombre
                    ELSE 'N/A'
                END as asociado_nombre,
                COALESCE(p.NrRut, ee.rut, vis.rut, '') as rut_asociado
            FROM vehiculos v
            LEFT JOIN personal_db.personal p
                ON v.asociado_id = p.id AND v.tipo IN ('PERSONAL', 'FUNCIONARIO', 'RESIDENTE', 'FISCAL')
            LEFT JOIN empresa_empleados ee
                ON v.asociado_id = ee.id AND v.tipo IN ('EMPLEADO', 'EMPRESA')
            LEFT JOIN visitas vis
                ON v.asociado_id = vis.id AND v.tipo = 'VISITA'
            WHERE v.id = ?";

    $stmt = $conn_acceso->prepare($sql);
    if (!$stmt) {
        return null;
    }

    $stmt->bind_param("i", $vehiculo_id);
    $stmt->execute();
    $result = $stmt->get_result();
    $vehiculo = $result->fetch_assoc();
    $stmt->close();

    return $vehiculo;
}

/**
 * Formatea vehículo para respuesta
 */
function formatar_vehiculo($row) {
    if (!$row) return null;

    return [
        'id' => (int)$row['id'],
        'patente' => $row['patente'] ?? '',
        'marca' => $row['marca'] ?? '',
        'modelo' => $row['modelo'] ?? '',
        'tipo' => $row['tipo'] ?? '',
        'tipo_vehiculo' => $row['tipo_vehiculo'] ?? '',
        'asociado_id' => $row['asociado_id'] ? (int)$row['asociado_id'] : null,
        'asociado_tipo' => $row['asociado_tipo'] ?? '',
        'status' => $row['status'] ?? 'no autorizado',
        'fecha_inicio' => $row['fecha_inicio'] ?? null,
        'fecha_expiracion' => $row['fecha_expiracion'] ?? null,
        'acceso_permanente' => (bool)($row['acceso_permanente'] ?? false),
        'asociado_nombre' => trim($row['asociado_nombre'] ?? 'N/A'),
        'rut_asociado' => $row['rut_asociado'] ?? ''
    ];
}

/**
 * Registra cambios en historial de vehículos
 */
function registrar_historial_vehiculo($conn_acceso, $vehiculo_id, $patente, $asociado_id_anterior,
                                     $asociado_id_nuevo, $tipo_cambio, $detalles = null) {
    $usuario_id = $_SESSION['user_id'] ?? null;

    $stmt = $conn_acceso->prepare(
        "INSERT INTO vehiculo_historial
         (vehiculo_id, patente, asociado_id_anterior, asociado_id_nuevo, fecha_cambio, usuario_id, tipo_cambio, detalles)
         VALUES (?, ?, ?, ?, NOW(), ?, ?, ?)"
    );

    if (!$stmt) {
        return false;
    }

    $stmt->bind_param("isiiiss", $vehiculo_id, $patente, $asociado_id_anterior, $asociado_id_nuevo,
                      $usuario_id, $tipo_cambio, $detalles);
    $result = $stmt->execute();
    $stmt->close();

    return $result;
}

// ============================================================================
// ROUTERS
// ============================================================================

$method = $_SERVER['REQUEST_METHOD'];
$conn_acceso = DatabaseConfig::getInstance()->getAccesoConnection();
$conn_personal = DatabaseConfig::getInstance()->getPersonalConnection();

try {
    switch ($method) {
        case 'GET':
            handle_get($conn_acceso, $conn_personal);
            break;

        case 'POST':
            handle_post($conn_acceso, $conn_personal);
            break;

        case 'PUT':
            handle_put($conn_acceso, $conn_personal);
            break;

        case 'DELETE':
            handle_delete($conn_acceso, $conn_personal);
            break;

        default:
            ApiResponse::error('Método no permitido', 405);
    }
} catch (Exception $e) {
    ApiResponse::serverError($e->getMessage());
}

// ============================================================================
// HANDLERS
// ============================================================================

/**
 * GET: Listar vehículos (paginado)
 */
function handle_get($conn_acceso, $conn_personal) {
    $page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
    $perPage = isset($_GET['per_page']) ? min((int)$_GET['per_page'], 500) : 100;

    // Query base
    $baseQuery = "SELECT
                    v.id, v.patente, v.marca, v.modelo, v.tipo, v.tipo_vehiculo,
                    v.asociado_id, v.asociado_tipo, v.status, v.fecha_inicio, v.fecha_expiracion, v.acceso_permanente,
                    CASE
                        WHEN v.tipo IN ('PERSONAL', 'FUNCIONARIO', 'RESIDENTE', 'FISCAL')
                            THEN TRIM(CONCAT_WS(' ', p.Grado, p.Nombres, p.Paterno, p.Materno))
                        WHEN v.tipo IN ('EMPLEADO', 'EMPRESA')
                            THEN TRIM(CONCAT_WS(' ', ee.nombre, ee.paterno, ee.materno))
                        WHEN v.tipo = 'VISITA'
                            THEN vis.nombre
                        ELSE 'N/A'
                    END as asociado_nombre,
                    COALESCE(p.NrRut, ee.rut, vis.rut, '') as rut_asociado
                FROM vehiculos v
                LEFT JOIN personal_db.personal p
                    ON v.asociado_id = p.id AND v.tipo IN ('PERSONAL', 'FUNCIONARIO', 'RESIDENTE', 'FISCAL')
                LEFT JOIN empresa_empleados ee
                    ON v.asociado_id = ee.id AND v.tipo IN ('EMPLEADO', 'EMPRESA')
                LEFT JOIN visitas vis
                    ON v.asociado_id = vis.id AND v.tipo = 'VISITA'
                ORDER BY v.id DESC";

    // Count total
    $countQuery = "SELECT COUNT(*) as total FROM vehiculos";
    $countResult = $conn_acceso->query($countQuery);
    $countRow = $countResult->fetch_assoc();
    $total = (int)$countRow['total'];

    // Paginate
    $sql = Paginator::generateSQL($baseQuery, $page, $perPage);
    $result = $conn_acceso->query($sql);

    if (!$result) {
        ApiResponse::serverError("Error en consulta: " . $conn_acceso->error);
    }

    $vehiculos = [];
    while ($row = $result->fetch_assoc()) {
        $vehiculos[] = formatar_vehiculo($row);
    }

    ApiResponse::paginated($vehiculos, $page, $perPage, $total);
}

/**
 * POST: Crear vehículo
 */
function handle_post($conn_acceso, $conn_personal) {
    $data = json_decode(file_get_contents('php://input'), true);

    if (!$data) {
        ApiResponse::badRequest('Datos JSON inválidos');
    }

    // Validar campos requeridos
    $patente = isset($data['patente']) ? strtoupper(trim($data['patente'])) : null;
    if (!$patente) {
        ApiResponse::badRequest('La patente es obligatoria');
    }

    if (!validar_patente_chilena($patente)) {
        ApiResponse::badRequest('Formato de patente inválido. Debe ser formato chileno (AA1234 o ABCD12)');
    }

    // Validar fecha_inicio
    if (!isset($data['fecha_inicio']) || empty(trim($data['fecha_inicio'] ?? ''))) {
        ApiResponse::badRequest('Falta campo requerido: fecha de inicio');
    }

    // Validar fecha_expiracion (solo si no es acceso permanente)
    $acceso_permanente = !empty($data['acceso_permanente']) ? 1 : 0;
    if (!$acceso_permanente && (!isset($data['fecha_expiracion']) || empty(trim($data['fecha_expiracion'] ?? '')))) {
        ApiResponse::badRequest('Falta campo requerido: fecha de expiración (o active acceso permanente)');
    }

    // Verificar patente no exista
    $stmt_check = $conn_acceso->prepare("SELECT id FROM vehiculos WHERE patente = ? LIMIT 1");
    $stmt_check->bind_param("s", $patente);
    $stmt_check->execute();
    if ($stmt_check->get_result()->num_rows > 0) {
        $stmt_check->close();
        ApiResponse::badRequest("Ya existe un vehículo registrado con la patente $patente");
    }
    $stmt_check->close();

    // Resolver asociado
    $tipo = isset($data['tipo']) ? strtoupper(trim($data['tipo'])) : null;
    $rut_o_id = $data['personalNrRut'] ?? $data['asociado_id'] ?? null;

    $resolver = resolver_asociado($tipo, $rut_o_id, $conn_personal, $conn_acceso);
    if ($resolver['error']) {
        ApiResponse::badRequest($resolver['error']);
    }
    $asociado_id = $resolver['id'];

    // Procesar fecha expiración
    $fecha_expiracion_raw = $data['fecha_expiracion'] ?? null;
    if ($acceso_permanente || empty($fecha_expiracion_raw) || $fecha_expiracion_raw === 'null' || $fecha_expiracion_raw === '0000-00-00') {
        $fecha_expiracion = null;
    } else {
        $fecha_expiracion = $fecha_expiracion_raw;
    }

    $status = get_status_by_date($acceso_permanente, $fecha_expiracion);
    $fecha_inicio = $data['fecha_inicio'];

    // Prepare insert
    $marca = isset($data['marca']) ? strtoupper(trim($data['marca'])) : null;
    $modelo = isset($data['modelo']) ? strtoupper(trim($data['modelo'])) : null;
    $tipo_vehiculo = isset($data['tipo_vehiculo']) ? strtoupper(trim($data['tipo_vehiculo'])) : 'AUTO';
    $asociado_tipo = $tipo;

    $stmt = $conn_acceso->prepare(
        "INSERT INTO vehiculos
         (patente, marca, modelo, tipo, tipo_vehiculo, asociado_id, asociado_tipo, status, fecha_inicio, fecha_expiracion, acceso_permanente)
         VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)"
    );

    if (!$stmt) {
        ApiResponse::serverError("Error preparando inserción: " . $conn_acceso->error);
    }

    $stmt->bind_param("sssssissssi", $patente, $marca, $modelo, $tipo, $tipo_vehiculo, $asociado_id,
                      $asociado_tipo, $status, $fecha_inicio, $fecha_expiracion, $acceso_permanente);

    if (!$stmt->execute()) {
        $stmt->close();
        ApiResponse::serverError("Error ejecutando inserción: " . $conn_acceso->error);
    }

    $newId = $stmt->insert_id;
    $stmt->close();

    // Registrar historial
    $detalles = json_encode([
        'marca' => $marca,
        'modelo' => $modelo,
        'tipo' => $tipo,
        'tipo_vehiculo' => $tipo_vehiculo,
        'acceso_permanente' => (bool)$acceso_permanente,
        'fecha_expiracion' => $fecha_expiracion
    ]);

    registrar_historial_vehiculo($conn_acceso, $newId, $patente, null, $asociado_id, 'creacion', $detalles);

    // Obtener vehículo recién creado
    $vehiculo = obtener_vehiculo($conn_acceso, $conn_personal, $newId);
    $vehiculo_formateado = formatar_vehiculo($vehiculo);

    ApiResponse::created($vehiculo_formateado);
}

/**
 * PUT: Actualizar vehículo
 */
function handle_put($conn_acceso, $conn_personal) {
    $data = json_decode(file_get_contents('php://input'), true);

    if (!$data) {
        ApiResponse::badRequest('Datos JSON inválidos');
    }

    $id = $data['id'] ?? null;
    if (!$id) {
        ApiResponse::badRequest('ID de vehículo no proporcionado');
    }

    // Validar campos
    $patente = isset($data['patente']) ? strtoupper(trim($data['patente'])) : null;
    if (!$patente) {
        ApiResponse::badRequest('La patente es obligatoria');
    }

    if (!validar_patente_chilena($patente)) {
        ApiResponse::badRequest('Formato de patente inválido. Debe ser formato chileno (AA1234 o ABCD12)');
    }

    if (!isset($data['fecha_inicio']) || empty(trim($data['fecha_inicio'] ?? ''))) {
        ApiResponse::badRequest('Falta campo requerido: fecha de inicio');
    }

    $acceso_permanente = !empty($data['acceso_permanente']) ? 1 : 0;
    if (!$acceso_permanente && (!isset($data['fecha_expiracion']) || empty(trim($data['fecha_expiracion'] ?? '')))) {
        ApiResponse::badRequest('Falta campo requerido: fecha de expiración (o active acceso permanente)');
    }

    // Verificar patente no exista (excepto en mismo vehículo)
    $stmt_check = $conn_acceso->prepare("SELECT id FROM vehiculos WHERE patente = ? AND id != ? LIMIT 1");
    $stmt_check->bind_param("si", $patente, $id);
    $stmt_check->execute();
    if ($stmt_check->get_result()->num_rows > 0) {
        $stmt_check->close();
        ApiResponse::badRequest("Ya existe otro vehículo registrado con la patente $patente");
    }
    $stmt_check->close();

    // Obtener asociado anterior
    $stmt_anterior = $conn_acceso->prepare("SELECT asociado_id FROM vehiculos WHERE id = ?");
    $stmt_anterior->bind_param("i", $id);
    $stmt_anterior->execute();
    $result_anterior = $stmt_anterior->get_result();
    $vehiculo_anterior = $result_anterior->fetch_assoc();
    $stmt_anterior->close();

    if (!$vehiculo_anterior) {
        ApiResponse::notFound('Vehículo no encontrado');
    }

    $asociado_id_anterior = $vehiculo_anterior['asociado_id'];

    // Resolver asociado nuevo
    $tipo = isset($data['tipo']) ? strtoupper(trim($data['tipo'])) : null;
    $rut_o_id = $data['personalNrRut'] ?? $data['asociado_id'] ?? null;

    $resolver = resolver_asociado($tipo, $rut_o_id, $conn_personal, $conn_acceso);
    if ($resolver['error']) {
        ApiResponse::badRequest($resolver['error']);
    }
    $asociado_id = $resolver['id'];

    // Procesar fecha expiración
    $fecha_expiracion_raw = $data['fecha_expiracion'] ?? null;
    if ($acceso_permanente || empty($fecha_expiracion_raw) || $fecha_expiracion_raw === 'null' || $fecha_expiracion_raw === '0000-00-00') {
        $fecha_expiracion = null;
    } else {
        $fecha_expiracion = $fecha_expiracion_raw;
    }

    $status = get_status_by_date($acceso_permanente, $fecha_expiracion);
    $fecha_inicio = $data['fecha_inicio'];

    // Prepare update
    $marca = isset($data['marca']) ? strtoupper(trim($data['marca'])) : null;
    $modelo = isset($data['modelo']) ? strtoupper(trim($data['modelo'])) : null;
    $tipo_vehiculo = isset($data['tipo_vehiculo']) ? strtoupper(trim($data['tipo_vehiculo'])) : 'AUTO';
    $asociado_tipo = $tipo;

    $stmt = $conn_acceso->prepare(
        "UPDATE vehiculos SET patente=?, marca=?, modelo=?, tipo=?, tipo_vehiculo=?, asociado_id=?,
         asociado_tipo=?, status=?, fecha_inicio=?, fecha_expiracion=?, acceso_permanente=? WHERE id=?"
    );

    if (!$stmt) {
        ApiResponse::serverError("Error preparando actualización: " . $conn_acceso->error);
    }

    $stmt->bind_param("sssssissssii", $patente, $marca, $modelo, $tipo, $tipo_vehiculo, $asociado_id,
                      $asociado_tipo, $status, $fecha_inicio, $fecha_expiracion, $acceso_permanente, $id);

    if (!$stmt->execute()) {
        $stmt->close();
        ApiResponse::serverError("Error ejecutando actualización: " . $conn_acceso->error);
    }

    $stmt->close();

    // Registrar historial
    $tipo_cambio = (!empty($asociado_id_anterior) && !empty($asociado_id) && $asociado_id != $asociado_id_anterior)
        ? 'cambio_propietario'
        : 'actualizacion';

    $detalles = json_encode([
        'marca' => $marca,
        'modelo' => $modelo,
        'tipo' => $tipo,
        'tipo_vehiculo' => $tipo_vehiculo,
        'acceso_permanente' => (bool)$acceso_permanente,
        'fecha_expiracion' => $fecha_expiracion
    ]);

    registrar_historial_vehiculo($conn_acceso, $id, $patente, $asociado_id_anterior, $asociado_id,
                                 $tipo_cambio, $detalles);

    // Obtener vehículo actualizado
    $vehiculo = obtener_vehiculo($conn_acceso, $conn_personal, $id);
    $vehiculo_formateado = formatar_vehiculo($vehiculo);

    ApiResponse::success($vehiculo_formateado);
}

/**
 * DELETE: Eliminar vehículo
 */
function handle_delete($conn_acceso, $conn_personal) {
    $id = $_GET['id'] ?? null;

    if (!$id) {
        ApiResponse::badRequest('ID de vehículo no proporcionado');
    }

    // Obtener vehículo antes de eliminar
    $stmt_get = $conn_acceso->prepare("SELECT patente, asociado_id FROM vehiculos WHERE id=?");
    $stmt_get->bind_param("i", $id);
    $stmt_get->execute();
    $result_get = $stmt_get->get_result();
    $vehiculo = $result_get->fetch_assoc();
    $stmt_get->close();

    if (!$vehiculo) {
        ApiResponse::notFound('Vehículo no encontrado');
    }

    // Registrar en historial antes de eliminar
    registrar_historial_vehiculo($conn_acceso, $id, $vehiculo['patente'], $vehiculo['asociado_id'], null,
                                 'eliminacion', json_encode(['fecha_eliminacion' => date('Y-m-d H:i:s')]));

    // Eliminar vehículo
    $stmt = $conn_acceso->prepare("DELETE FROM vehiculos WHERE id=?");
    $stmt->bind_param("i", $id);
    $stmt->execute();

    if ($stmt->affected_rows > 0) {
        $stmt->close();
        ApiResponse::success(['message' => 'Vehículo eliminado correctamente']);
    } else {
        $stmt->close();
        ApiResponse::serverError('Error al eliminar el vehículo');
    }
}

// Close connections
$conn_acceso->close();
$conn_personal->close();

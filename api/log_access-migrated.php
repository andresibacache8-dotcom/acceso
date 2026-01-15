<?php
/**
 * api/log_access-migrated.php
 * API para registro de acceso con soporte multi-tipo (personal, vehículos, visitas, empleados)
 *
 * Migración desde log_access.php original:
 * - Config: database/db_*.php → config/database.php
 * - Respuestas: echo json_encode() → ApiResponse::*()
 * - Estructura: métodos HTTP estándar (GET, POST, DELETE)
 *
 * Endpoints:
 * GET    /api/log_access.php?target_type=personal       - Listar logs del día actual
 * POST   /api/log_access.php                              - Registrar nuevo acceso
 * DELETE /api/log_access.php?id=123                       - Cancelar acceso (soft delete)
 *
 * @version 2.0 (Migrated)
 */

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/core/ResponseHandler.php';

// Headers CORS
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, DELETE, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization');

// Manejar preflight CORS
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}

// Iniciar sesión y validar autenticación
session_start();
if (!isset($_SESSION['logged_in']) || $_SESSION['logged_in'] !== true) {
    ApiResponse::unauthorized('No autorizado. Por favor, inicie sesión.');
}

// Obtener conexiones
$databaseConfig = DatabaseConfig::getInstance();
$conn_acceso = $databaseConfig->getAccesoConnection();
$conn_personal = $databaseConfig->getPersonalConnection();

if (!$conn_acceso || !$conn_personal) {
    ApiResponse::serverError('Error conectando a base de datos');
}

try {
    $method = $_SERVER['REQUEST_METHOD'];

    switch ($method) {
        case 'GET':
            handleGet($conn_acceso, $conn_personal);
            break;
        case 'POST':
            handlePost($conn_acceso, $conn_personal);
            break;
        case 'DELETE':
            handleDelete($conn_acceso);
            break;
        default:
            ApiResponse::error('Método no permitido', 405);
    }

} catch (Exception $e) {
    ApiResponse::serverError('Error: ' . $e->getMessage());
}

/**
 * Calcular estado basado en acceso permanente y fecha de expiración
 */
function getStatusByDate($is_permanent, $expiration_date_str) {
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
 * GET /api/log_access.php?target_type=personal
 * Listar logs del día actual filtrando por tipo
 */
function handleGet($conn_acceso, $conn_personal) {
    try {
        if (!isset($_GET['target_type'])) {
            ApiResponse::badRequest('Tipo de objetivo no proporcionado');
        }

        $target_type = $_GET['target_type'];

        // Obtener logs activos del día actual
        $stmt_logs = $conn_acceso->prepare(
            "SELECT id, target_id, action, log_time
             FROM access_logs
             WHERE target_type = ? AND log_status = 'activo' AND DATE(log_time) = CURDATE()
             ORDER BY log_time DESC LIMIT 50"
        );

        if (!$stmt_logs) {
            throw new Exception('Error preparando consulta: ' . $conn_acceso->error);
        }

        $stmt_logs->bind_param("s", $target_type);
        $stmt_logs->execute();
        $result_logs = $stmt_logs->get_result();
        $logs = $result_logs->fetch_all(MYSQLI_ASSOC);
        $stmt_logs->close();

        if (empty($logs)) {
            ApiResponse::success([]);
            return;
        }

        $target_ids = array_unique(array_column($logs, 'target_id'));
        $ids_placeholder = implode(',', array_fill(0, count($target_ids), '?'));
        $ids_types = str_repeat('i', count($target_ids));
        $response_logs = [];

        // Router por tipo de objetivo
        if ($target_type === 'personal') {
            handleGetPersonal($conn_personal, $logs, $target_ids, $ids_placeholder, $ids_types, $response_logs);
        } elseif ($target_type === 'vehiculo') {
            handleGetVehiculo($conn_acceso, $conn_personal, $logs, $target_ids, $ids_placeholder, $ids_types, $response_logs);
        } elseif ($target_type === 'visita') {
            handleGetVisita($conn_acceso, $logs, $target_ids, $ids_placeholder, $ids_types, $response_logs);
        } elseif ($target_type === 'empresa_empleado') {
            handleGetEmpresaEmpleado($conn_acceso, $logs, $target_ids, $ids_placeholder, $ids_types, $response_logs);
        } elseif ($target_type === 'personal_comision') {
            handleGetPersonalComision($conn_personal, $logs, $target_ids, $ids_placeholder, $ids_types, $response_logs);
        } else {
            ApiResponse::badRequest('Tipo de objetivo no válido: ' . $target_type);
        }

        ApiResponse::success($response_logs);

    } catch (Exception $e) {
        ApiResponse::serverError('Error al obtener logs: ' . $e->getMessage());
    }
}

/**
 * Procesar GET para tipo 'personal'
 */
function handleGetPersonal($conn_personal, $logs, $target_ids, $ids_placeholder, $ids_types, &$response_logs) {
    $stmt_personal = $conn_personal->prepare(
        "SELECT id, Grado, Nombres, Paterno, Materno, NrRut, Unidad
         FROM personal WHERE id IN ($ids_placeholder)"
    );

    if (!$stmt_personal) {
        throw new Exception('Error preparando consulta de personal: ' . $conn_personal->error);
    }

    $stmt_personal->bind_param($ids_types, ...$target_ids);
    $stmt_personal->execute();
    $result_personal = $stmt_personal->get_result();
    $personal_map = [];

    while ($row = $result_personal->fetch_assoc()) {
        $personal_map[$row['id']] = [
            'name' => trim(($row['Grado'] ?? '') . ' ' . ($row['Nombres'] ?? '') . ' ' .
                          ($row['Paterno'] ?? '') . ' ' . ($row['Materno'] ?? '')),
            'rut' => $row['NrRut'],
            'unidad' => $row['Unidad'] ?? 'N/A'
        ];
    }
    $stmt_personal->close();

    foreach ($logs as $log) {
        $personal_info = $personal_map[$log['target_id']] ?? null;
        $response_logs[] = [
            'log_id' => $log['id'],
            'nombre' => $personal_info ? $personal_info['name'] : 'ID ' . $log['target_id'],
            'rut' => $personal_info ? $personal_info['rut'] : 'N/A',
            'unidad' => $personal_info ? $personal_info['unidad'] : 'N/A',
            'action' => $log['action'],
            'timestamp' => date("d-m-Y H:i:s", strtotime($log['log_time'])),
            'log_time' => $log['log_time'],
            'type' => 'personal'
        ];
    }
}

/**
 * Procesar GET para tipo 'vehiculo'
 */
function handleGetVehiculo($conn_acceso, $conn_personal, $logs, $target_ids, $ids_placeholder, $ids_types, &$response_logs) {
    $stmt_vehiculos = $conn_acceso->prepare(
        "SELECT id, patente, marca, modelo, asociado_id, asociado_tipo
         FROM vehiculos WHERE id IN ($ids_placeholder)"
    );

    if (!$stmt_vehiculos) {
        throw new Exception('Error preparando consulta de vehículos: ' . $conn_acceso->error);
    }

    $stmt_vehiculos->bind_param($ids_types, ...$target_ids);
    $stmt_vehiculos->execute();
    $result_vehiculos = $stmt_vehiculos->get_result();
    $vehiculo_map = [];
    $personal_ids = [];
    $empresa_ids = [];
    $visita_ids = [];

    while ($row = $result_vehiculos->fetch_assoc()) {
        $vehiculo_map[$row['id']] = $row;

        if ($row['asociado_id'] && !empty($row['asociado_tipo'])) {
            $tipo = strtoupper($row['asociado_tipo']);
            if (in_array($tipo, ['PERSONAL', 'FUNCIONARIO', 'RESIDENTE', 'FISCAL'])) {
                $personal_ids[] = $row['asociado_id'];
            } elseif (in_array($tipo, ['EMPRESA', 'EMPLEADO'])) {
                $empresa_ids[] = $row['asociado_id'];
            } elseif ($tipo === 'VISITA') {
                $visita_ids[] = $row['asociado_id'];
            }
        }
    }
    $stmt_vehiculos->close();

    // Cargar datos de asociados
    $personal_map = [];
    $empresa_map = [];
    $visita_map = [];

    if (!empty($personal_ids)) {
        $personal_ids = array_unique($personal_ids);
        $p_placeholder = implode(',', array_fill(0, count($personal_ids), '?'));
        $p_types = str_repeat('i', count($personal_ids));
        $stmt = $conn_personal->prepare("SELECT id, Grado, Nombres, Paterno, Materno FROM personal WHERE id IN ($p_placeholder)");
        if ($stmt) {
            $stmt->bind_param($p_types, ...$personal_ids);
            $stmt->execute();
            $result = $stmt->get_result();
            while ($row = $result->fetch_assoc()) {
                $personal_map[$row['id']] = trim(($row['Grado'] ?? '') . ' ' . ($row['Nombres'] ?? '') . ' ' .
                                                  ($row['Paterno'] ?? '') . ' ' . ($row['Materno'] ?? ''));
            }
            $stmt->close();
        }
    }

    if (!empty($empresa_ids)) {
        $empresa_ids = array_unique($empresa_ids);
        $e_placeholder = implode(',', array_fill(0, count($empresa_ids), '?'));
        $e_types = str_repeat('i', count($empresa_ids));
        $stmt = $conn_acceso->prepare("SELECT id, nombre, paterno, materno FROM empresa_empleados WHERE id IN ($e_placeholder)");
        if ($stmt) {
            $stmt->bind_param($e_types, ...$empresa_ids);
            $stmt->execute();
            $result = $stmt->get_result();
            while ($row = $result->fetch_assoc()) {
                $paterno = isset($row['paterno']) && trim($row['paterno']) !== '' ? " {$row['paterno']}" : "";
                $materno = isset($row['materno']) && trim($row['materno']) !== '' ? " {$row['materno']}" : "";
                $empresa_map[$row['id']] = trim("{$row['nombre']}{$paterno}{$materno}");
            }
            $stmt->close();
        }
    }

    if (!empty($visita_ids)) {
        $visita_ids = array_unique($visita_ids);
        $v_placeholder = implode(',', array_fill(0, count($visita_ids), '?'));
        $v_types = str_repeat('i', count($visita_ids));
        $stmt = $conn_acceso->prepare("SELECT id, nombre FROM visitas WHERE id IN ($v_placeholder)");
        if ($stmt) {
            $stmt->bind_param($v_types, ...$visita_ids);
            $stmt->execute();
            $result = $stmt->get_result();
            while ($row = $result->fetch_assoc()) {
                $visita_map[$row['id']] = trim($row['nombre']);
            }
            $stmt->close();
        }
    }

    // Construir respuesta
    foreach ($logs as $log) {
        $vehiculo_info = $vehiculo_map[$log['target_id']] ?? null;
        $personal_asociado_nombre = 'N/A';

        if ($vehiculo_info && $vehiculo_info['asociado_id'] && !empty($vehiculo_info['asociado_tipo'])) {
            $tipo = strtoupper($vehiculo_info['asociado_tipo']);
            if (in_array($tipo, ['PERSONAL', 'FUNCIONARIO', 'RESIDENTE', 'FISCAL']) && isset($personal_map[$vehiculo_info['asociado_id']])) {
                $personal_asociado_nombre = $personal_map[$vehiculo_info['asociado_id']];
            } elseif (in_array($tipo, ['EMPRESA', 'EMPLEADO']) && isset($empresa_map[$vehiculo_info['asociado_id']])) {
                $personal_asociado_nombre = $empresa_map[$vehiculo_info['asociado_id']];
            } elseif ($tipo === 'VISITA' && isset($visita_map[$vehiculo_info['asociado_id']])) {
                $personal_asociado_nombre = $visita_map[$vehiculo_info['asociado_id']];
            }
        }

        $response_logs[] = [
            'log_id' => $log['id'],
            'patente' => $vehiculo_info['patente'] ?? 'ID ' . $log['target_id'],
            'personalName' => $personal_asociado_nombre,
            'action' => $log['action'],
            'timestamp' => date("d-m-Y H:i:s", strtotime($log['log_time'])),
            'log_time' => $log['log_time'],
            'type' => 'vehiculo'
        ];
    }
}

/**
 * Procesar GET para tipo 'visita'
 */
function handleGetVisita($conn_acceso, $logs, $target_ids, $ids_placeholder, $ids_types, &$response_logs) {
    $stmt_visitas = $conn_acceso->prepare(
        "SELECT id, nombre, tipo FROM visitas WHERE id IN ($ids_placeholder)"
    );

    if (!$stmt_visitas) {
        throw new Exception('Error preparando consulta de visitas: ' . $conn_acceso->error);
    }

    $stmt_visitas->bind_param($ids_types, ...$target_ids);
    $stmt_visitas->execute();
    $result_visitas = $stmt_visitas->get_result();
    $visita_map = [];

    while ($row = $result_visitas->fetch_assoc()) {
        $visita_map[$row['id']] = $row;
    }
    $stmt_visitas->close();

    foreach ($logs as $log) {
        $visita_info = $visita_map[$log['target_id']] ?? null;
        $nombre_completo = 'ID ' . $log['target_id'];
        if ($visita_info) {
            $nombre_completo = trim($visita_info['nombre']);
        }

        $response_logs[] = [
            'log_id' => $log['id'],
            'nombre' => $nombre_completo,
            'tipo' => $visita_info['tipo'] ?? 'N/A',
            'action' => $log['action'],
            'timestamp' => date("d-m-Y H:i:s", strtotime($log['log_time'])),
            'log_time' => $log['log_time'],
            'type' => 'visita'
        ];
    }
}

/**
 * Procesar GET para tipo 'empresa_empleado'
 */
function handleGetEmpresaEmpleado($conn_acceso, $logs, $target_ids, $ids_placeholder, $ids_types, &$response_logs) {
    $stmt_empleados = $conn_acceso->prepare(
        "SELECT ee.id, ee.nombre, ee.paterno, ee.materno, ee.rut, e.nombre as empresa_nombre
         FROM empresa_empleados ee
         JOIN empresas e ON ee.empresa_id = e.id
         WHERE ee.id IN ($ids_placeholder)"
    );

    if (!$stmt_empleados) {
        throw new Exception('Error preparando consulta de empleados: ' . $conn_acceso->error);
    }

    $stmt_empleados->bind_param($ids_types, ...$target_ids);
    $stmt_empleados->execute();
    $result_empleados = $stmt_empleados->get_result();
    $empleado_map = [];

    while ($row = $result_empleados->fetch_assoc()) {
        $empleado_map[$row['id']] = [
            'name' => trim($row['nombre'] . ' ' . $row['paterno'] . ' ' . $row['materno']),
            'rut' => $row['rut'],
            'empresa_nombre' => $row['empresa_nombre']
        ];
    }
    $stmt_empleados->close();

    foreach ($logs as $log) {
        $empleado_info = $empleado_map[$log['target_id']] ?? null;
        $response_logs[] = [
            'log_id' => $log['id'],
            'name' => $empleado_info ? $empleado_info['name'] : 'ID ' . $log['target_id'],
            'rut' => $empleado_info ? $empleado_info['rut'] : 'N/A',
            'empresa_nombre' => $empleado_info ? $empleado_info['empresa_nombre'] : 'N/A',
            'action' => $log['action'],
            'timestamp' => date("d-m-Y H:i:s", strtotime($log['log_time'])),
            'log_time' => $log['log_time'],
            'type' => 'empresa_empleado'
        ];
    }
}

/**
 * Procesar GET para tipo 'personal_comision'
 */
function handleGetPersonalComision($conn_personal, $logs, $target_ids, $ids_placeholder, $ids_types, &$response_logs) {
    $stmt_comision = $conn_personal->prepare(
        "SELECT id, nombre_completo FROM personal_comision WHERE id IN ($ids_placeholder)"
    );

    if (!$stmt_comision) {
        throw new Exception('Error preparando consulta de personal en comisión: ' . $conn_personal->error);
    }

    $stmt_comision->bind_param($ids_types, ...$target_ids);
    $stmt_comision->execute();
    $result_comision = $stmt_comision->get_result();
    $comision_map = [];

    while ($row = $result_comision->fetch_assoc()) {
        $comision_map[$row['id']] = $row['nombre_completo'];
    }
    $stmt_comision->close();

    foreach ($logs as $log) {
        $response_logs[] = [
            'log_id' => $log['id'],
            'name' => $comision_map[$log['target_id']] ?? 'ID ' . $log['target_id'],
            'action' => $log['action'],
            'timestamp' => date("d-m-Y H:i:s", strtotime($log['log_time'])),
            'log_time' => $log['log_time'],
            'type' => 'personal_comision'
        ];
    }
}

/**
 * POST /api/log_access.php
 * Registrar nuevo acceso (entrada/salida)
 */
function handlePost($conn_acceso, $conn_personal) {
    try {
        $data = json_decode(file_get_contents('php://input'), true);

        if (!$data || !isset($data['target_id']) || !isset($data['target_type'])) {
            ApiResponse::badRequest('Datos de entrada inválidos');
        }

        $target_id_input = $data['target_id'];
        $target_type = $data['target_type'];
        $punto_acceso = $data['punto_acceso'] ?? 'desconocido';
        $log_target_id = null;
        $response_data = [];

        // Router por tipo
        if ($target_type === 'personal') {
            processPersonal($conn_personal, $target_id_input, $log_target_id, $response_data);
        } elseif ($target_type === 'vehiculo') {
            processVehiculo($conn_acceso, $conn_personal, $target_id_input, $log_target_id, $response_data);
        } elseif ($target_type === 'visita') {
            processVisita($conn_acceso, $target_id_input, $log_target_id, $response_data);
        } else {
            ApiResponse::badRequest('Tipo de objetivo no válido: ' . $target_type);
        }

        // Obtener última acción
        $stmt_action = $conn_acceso->prepare(
            "SELECT action FROM access_logs WHERE target_id = ? AND target_type = ?
             ORDER BY id DESC LIMIT 1"
        );

        if (!$stmt_action) {
            throw new Exception('Error preparando consulta: ' . $conn_acceso->error);
        }

        $stmt_action->bind_param("is", $log_target_id, $target_type);
        $stmt_action->execute();
        $last_log = $stmt_action->get_result()->fetch_assoc();
        $stmt_action->close();

        $new_action = ($last_log && $last_log['action'] === 'entrada') ? 'salida' : 'entrada';

        // Lógica especial de horarios para oficina
        if ($target_type === 'personal' && $punto_acceso === 'oficina') {
            date_default_timezone_set('America/Santiago');
            $current_hour = (int)date('G');

            if ($current_hour === 7) {
                if ($new_action === 'salida') {
                    ApiResponse::error('Ya tiene una entrada registrada. Solo puede marcar SALIDA en el horario de la tarde.', 403);
                }
                $new_action = 'entrada';
            } elseif ($current_hour === 16) {
                if ($new_action === 'entrada') {
                    ApiResponse::error('No tiene una entrada registrada para marcar salida. Solo puede marcar ENTRADA en el horario de la mañana.', 403);
                }
                $new_action = 'salida';
            } else {
                ApiResponse::error('Registro de jornada fuera de horario (07:00-07:59 y 16:00-16:59). Utilice el Pórtico.', 403);
            }
        }

        $message = "Acceso registrado: " . $new_action;

        // Obtener nombre de la entidad
        $entity_name = '';
        if (!empty($response_data['personalName'])) {
            $entity_name = $response_data['personalName'];
        } elseif (!empty($response_data['nombre'])) {
            $entity_name = $response_data['nombre'];
        } elseif (!empty($response_data['patente'])) {
            $entity_name = $response_data['patente'];
        }

        // Insertar log
        $stmt_insert = $conn_acceso->prepare(
            "INSERT INTO access_logs (target_id, target_type, action, name, status_message, punto_acceso, motivo)
             VALUES (?, ?, ?, ?, ?, ?, ?)"
        );

        if (!$stmt_insert) {
            throw new Exception('Error preparando inserción: ' . $conn_acceso->error);
        }

        $motivo = null;
        $stmt_insert->bind_param("issssss", $log_target_id, $target_type, $new_action, $entity_name, $message, $punto_acceso, $motivo);
        $stmt_insert->execute();
        $stmt_insert->close();

        ApiResponse::created(
            array_merge(['message' => $message, 'action' => $new_action], $response_data),
            ['id' => $conn_acceso->insert_id]
        );

    } catch (Exception $e) {
        ApiResponse::serverError('Error al registrar acceso: ' . $e->getMessage());
    }
}

/**
 * Procesar y validar personal
 */
function processPersonal($conn_personal, $target_id_input, &$log_target_id, &$response_data) {
    $stmt = $conn_personal->prepare(
        "SELECT id, Grado, Nombres, Paterno, Materno, NrRut, foto, Unidad
         FROM personal WHERE NrRut = ? OR id = ?"
    );

    if (!$stmt) {
        ApiResponse::serverError('Error: ' . $conn_personal->error);
    }

    $stmt->bind_param("si", $target_id_input, $target_id_input);
    $stmt->execute();
    $person = $stmt->get_result()->fetch_assoc();
    $stmt->close();

    if (!$person) {
        ApiResponse::notFound('Persona no encontrada');
    }

    $log_target_id = $person['id'];
    $response_data['personalName'] = trim(($person['Grado'] ?? '') . ' ' . ($person['Nombres'] ?? '') . ' ' .
                                          ($person['Paterno'] ?? '') . ' ' . ($person['Materno'] ?? ''));
    $response_data['personalRut'] = $person['NrRut'];
    $response_data['personalUnidad'] = $person['Unidad'] ?? 'N/A';
    $response_data['personalPhotoUrl'] = $person['foto'];
}

/**
 * Procesar y validar vehículo
 */
function processVehiculo($conn_acceso, $conn_personal, $target_id_input, &$log_target_id, &$response_data) {
    $stmt = $conn_acceso->prepare(
        "SELECT id, patente, marca, modelo, asociado_id, asociado_tipo, status, fecha_expiracion, acceso_permanente
         FROM vehiculos WHERE patente = ? OR id = ?"
    );

    if (!$stmt) {
        ApiResponse::serverError('Error: ' . $conn_acceso->error);
    }

    $stmt->bind_param("si", $target_id_input, $target_id_input);
    $stmt->execute();
    $vehiculo = $stmt->get_result()->fetch_assoc();
    $stmt->close();

    if (!$vehiculo) {
        ApiResponse::notFound('Vehículo no encontrado');
    }

    if (getStatusByDate((bool)$vehiculo['acceso_permanente'], $vehiculo['fecha_expiracion']) === 'no autorizado') {
        ApiResponse::error("Acceso denegado para el vehículo [" . $vehiculo['patente'] . "]. Autorización expirada o no válida.", 403);
    }

    $log_target_id = $vehiculo['id'];
    $response_data['patente'] = $vehiculo['patente'];
    $response_data['marca'] = $vehiculo['marca'] ?? '';
    $response_data['modelo'] = $vehiculo['modelo'] ?? '';

    // Buscar propietario
    if ($vehiculo['asociado_id']) {
        if (in_array($vehiculo['asociado_tipo'], ['personal', 'FUNCIONARIO'])) {
            $stmt_p = $conn_personal->prepare("SELECT Grado, Nombres, Paterno, Materno FROM personal WHERE id = ?");
            if ($stmt_p) {
                $stmt_p->bind_param("i", $vehiculo['asociado_id']);
                $stmt_p->execute();
                $person = $stmt_p->get_result()->fetch_assoc();
                $stmt_p->close();
                if ($person) {
                    $response_data['personalName'] = trim(($person['Grado'] ?? '') . ' ' . ($person['Nombres'] ?? '') . ' ' .
                                                          ($person['Paterno'] ?? '') . ' ' . ($person['Materno'] ?? ''));
                }
            }
        } elseif ($vehiculo['asociado_tipo'] === 'EMPRESA') {
            $stmt_e = $conn_acceso->prepare("SELECT nombre, paterno, materno FROM empresa_empleados WHERE id = ?");
            if ($stmt_e) {
                $stmt_e->bind_param("i", $vehiculo['asociado_id']);
                $stmt_e->execute();
                $empleado = $stmt_e->get_result()->fetch_assoc();
                $stmt_e->close();
                if ($empleado) {
                    $paterno = isset($empleado['paterno']) && trim($empleado['paterno']) !== '' ? " {$empleado['paterno']}" : "";
                    $materno = isset($empleado['materno']) && trim($empleado['materno']) !== '' ? " {$empleado['materno']}" : "";
                    $response_data['personalName'] = trim("{$empleado['nombre']}{$paterno}{$materno}");
                }
            }
        } elseif ($vehiculo['asociado_tipo'] === 'VISITA') {
            $stmt_v = $conn_acceso->prepare("SELECT nombre, paterno, materno FROM visitas WHERE id = ?");
            if ($stmt_v) {
                $stmt_v->bind_param("i", $vehiculo['asociado_id']);
                $stmt_v->execute();
                $visita = $stmt_v->get_result()->fetch_assoc();
                $stmt_v->close();
                if ($visita) {
                    $paterno = isset($visita['paterno']) && trim($visita['paterno']) !== '' ? " {$visita['paterno']}" : "";
                    $materno = isset($visita['materno']) && trim($visita['materno']) !== '' ? " {$visita['materno']}" : "";
                    $response_data['personalName'] = trim("{$visita['nombre']}{$paterno}{$materno}");
                }
            }
        }
    }
}

/**
 * Procesar y validar visita
 */
function processVisita($conn_acceso, $target_id_input, &$log_target_id, &$response_data) {
    $stmt = $conn_acceso->prepare(
        "SELECT id, nombre, paterno, materno, tipo, status, fecha_expiracion, acceso_permanente, en_lista_negra
         FROM visitas WHERE rut = ? OR nombre LIKE ?"
    );

    if (!$stmt) {
        ApiResponse::serverError('Error: ' . $conn_acceso->error);
    }

    $search_term = '%' . $target_id_input . '%';
    $stmt->bind_param("ss", $target_id_input, $search_term);
    $stmt->execute();
    $visita = $stmt->get_result()->fetch_assoc();
    $stmt->close();

    if (!$visita) {
        ApiResponse::notFound('Visita no encontrada');
    }

    if ($visita['en_lista_negra']) {
        ApiResponse::error('PROHIBIDO SU INGRESO, PERSONA EN LISTA NEGRA, LLAMAR AL CUERPO DE GUARDIA', 403);
    }

    if (getStatusByDate((bool)$visita['acceso_permanente'], $visita['fecha_expiracion']) === 'no autorizado') {
        $paterno = isset($visita['paterno']) && trim($visita['paterno']) !== '' ? " {$visita['paterno']}" : "";
        $materno = isset($visita['materno']) && trim($visita['materno']) !== '' ? " {$visita['materno']}" : "";
        $nombre_completo = trim($visita['nombre'] . $paterno . $materno);
        ApiResponse::error("Acceso denegado. La autorización para '" . $nombre_completo . "' ha expirado o no es válida.", 403);
    }

    $log_target_id = $visita['id'];
    $paterno = isset($visita['paterno']) && trim($visita['paterno']) !== '' ? " {$visita['paterno']}" : "";
    $materno = isset($visita['materno']) && trim($visita['materno']) !== '' ? " {$visita['materno']}" : "";
    $response_data['nombre'] = trim($visita['nombre'] . $paterno . $materno);
    $response_data['tipo'] = $visita['tipo'];
}

/**
 * DELETE /api/log_access.php?id=123
 * Cancelar registro de acceso (soft delete)
 */
function handleDelete($conn_acceso) {
    try {
        if (!isset($_GET['id'])) {
            ApiResponse::badRequest('ID de registro no proporcionado');
        }

        $log_id = (int)$_GET['id'];

        $stmt = $conn_acceso->prepare("UPDATE access_logs SET log_status = 'cancelado' WHERE id = ?");

        if (!$stmt) {
            throw new Exception('Error preparando actualización: ' . $conn_acceso->error);
        }

        $stmt->bind_param("i", $log_id);
        $stmt->execute();

        if ($stmt->affected_rows > 0) {
            $stmt->close();
            ApiResponse::noContent();
        } else {
            $stmt->close();
            ApiResponse::notFound('Registro no encontrado o ya fue cancelado');
        }

    } catch (Exception $e) {
        ApiResponse::serverError('Error al cancelar registro: ' . $e->getMessage());
    }
}

?>

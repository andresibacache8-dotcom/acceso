<?php
// api/log_access.php
require_once 'database/db_acceso.php';
require_once 'database/db_personal.php';

// Iniciar sesión para validación de usuario
session_start();

// Encabezados CORS y Content-Type
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, DELETE, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization');

// Si es una solicitud OPTIONS (preflight), devolver solo los headers y terminar
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}

// Verificar si el usuario está autenticado (TODOS los métodos)
if (!isset($_SESSION['logged_in']) || $_SESSION['logged_in'] !== true) {
    http_response_code(401);
    echo json_encode(['error' => 'No autorizado. Por favor, inicie sesión.']);
    exit;
}

// Configuración de errores (no mostrar en producción)
ini_set('display_errors', 0);
ini_set('log_errors', 1);
error_reporting(E_ALL);

$method = $_SERVER['REQUEST_METHOD'];

function send_error($code, $message) {
    http_response_code($code);
    echo json_encode(['error' => $message]);
    exit;
}

function get_status_by_date($is_permanent, $expiration_date_str) {
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

switch ($method) {
    case 'GET':
        if (!isset($_GET['target_type'])) {
            send_error(400, 'Tipo de objetivo no proporcionado');
        }
        $target_type = $_GET['target_type'];

        // MODIFICADO: Añadido "log_status = 'activo'" a la consulta.
        $stmt_logs = $conn_acceso->prepare("SELECT id, target_id, action, log_time FROM access_logs WHERE target_type = ? AND log_status = 'activo' AND DATE(log_time) = CURDATE() ORDER BY log_time DESC LIMIT 50");
        if (!$stmt_logs) send_error(500, "Error preparando la consulta de logs: " . $conn_acceso->error);
        $stmt_logs->bind_param("s", $target_type);
        $stmt_logs->execute();
        $result_logs = $stmt_logs->get_result();
        $logs = $result_logs->fetch_all(MYSQLI_ASSOC);
        $stmt_logs->close();

        if (empty($logs)) {
            echo json_encode([]);
            exit;
        }

        $target_ids = array_unique(array_column($logs, 'target_id'));
        $ids_placeholder = implode(',', array_fill(0, count($target_ids), '?'));
        $ids_types = str_repeat('i', count($target_ids));
        $response_logs = [];

        if ($target_type === 'personal') {
            $stmt_personal = $conn_personal->prepare("SELECT id, Grado, Nombres, Paterno, Materno, NrRut, Unidad FROM personal WHERE id IN ($ids_placeholder)");
            if (!$stmt_personal) send_error(500, "Error preparando la consulta de personal: " . $conn_personal->error);
            $stmt_personal->bind_param($ids_types, ...$target_ids);
            $stmt_personal->execute();
            $result_personal = $stmt_personal->get_result();
            $personal_map = [];
            while ($row = $result_personal->fetch_assoc()) {
                $personal_map[$row['id']] = [
                    'name' => trim($row['Grado'] . ' ' . $row['Nombres'] . ' ' . $row['Paterno'] . ' ' . $row['Materno']),
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
                    'name' => $personal_info ? $personal_info['name'] : 'ID ' . $log['target_id'],
                    'rut' => $personal_info ? $personal_info['rut'] : 'N/A',
                    'unidad' => $personal_info ? $personal_info['unidad'] : 'N/A',
                    'accion' => $log['action'],
                    'action' => $log['action'],
                    'timestamp' => date("d-m-Y H:i:s", strtotime($log['log_time'])),
                    'log_time' => $log['log_time'],
                    'type' => $target_type
                ];
            }
        } else if ($target_type === 'vehiculo') {
            $stmt_vehiculos = $conn_acceso->prepare("SELECT id, patente, marca, modelo, asociado_id, asociado_tipo FROM vehiculos WHERE id IN ($ids_placeholder)");
            if (!$stmt_vehiculos) send_error(500, "Error preparando la consulta de vehículos: " . $conn_acceso->error);
            $stmt_vehiculos->bind_param($ids_types, ...$target_ids);
            $stmt_vehiculos->execute();
            $result_vehiculos = $stmt_vehiculos->get_result();
            $vehiculo_map = [];
            $personal_ids_from_vehiculos = [];
            $empresa_ids_from_vehiculos = [];
            $visita_ids_from_vehiculos = [];
            while ($row = $result_vehiculos->fetch_assoc()) {
                $vehiculo_map[$row['id']] = $row;
                // Separar IDs según el tipo de asociado
                if ($row['asociado_id'] && !empty($row['asociado_tipo'])) {
                    $tipo = strtoupper($row['asociado_tipo']);
                    if (in_array($tipo, ['PERSONAL', 'FUNCIONARIO', 'RESIDENTE', 'FISCAL'])) {
                        $personal_ids_from_vehiculos[] = $row['asociado_id'];
                    } elseif (in_array($tipo, ['EMPRESA', 'EMPLEADO'])) {
                        $empresa_ids_from_vehiculos[] = $row['asociado_id'];
                    } elseif ($tipo === 'VISITA') {
                        $visita_ids_from_vehiculos[] = $row['asociado_id'];
                    }
                }
            }
            $stmt_vehiculos->close();

            $personal_map = [];
            if (!empty($personal_ids_from_vehiculos)) {
                $personal_ids_from_vehiculos = array_unique($personal_ids_from_vehiculos);
                $p_ids_placeholder = implode(',', array_fill(0, count($personal_ids_from_vehiculos), '?'));
                $p_ids_types = str_repeat('i', count($personal_ids_from_vehiculos));
                $stmt_personal = $conn_personal->prepare("SELECT id, Grado, Nombres, Paterno, Materno FROM personal WHERE id IN ($p_ids_placeholder)");
                if ($stmt_personal) {
                    $stmt_personal->bind_param($p_ids_types, ...$personal_ids_from_vehiculos);
                    $stmt_personal->execute();
                    $result_personal = $stmt_personal->get_result();
                    while ($row = $result_personal->fetch_assoc()) {
                        $personal_map[$row['id']] = trim($row['Grado'] . ' ' . $row['Nombres'] . ' ' . $row['Paterno'] . ' ' . $row['Materno']);
                    }
                    $stmt_personal->close();
                }
            }

            $empresa_map = [];
            if (!empty($empresa_ids_from_vehiculos)) {
                $empresa_ids_from_vehiculos = array_unique($empresa_ids_from_vehiculos);
                $e_ids_placeholder = implode(',', array_fill(0, count($empresa_ids_from_vehiculos), '?'));
                $e_ids_types = str_repeat('i', count($empresa_ids_from_vehiculos));
                $stmt_empresa = $conn_acceso->prepare("SELECT id, nombre, paterno, materno FROM empresa_empleados WHERE id IN ($e_ids_placeholder)");
                if ($stmt_empresa) {
                    $stmt_empresa->bind_param($e_ids_types, ...$empresa_ids_from_vehiculos);
                    $stmt_empresa->execute();
                    $result_empresa = $stmt_empresa->get_result();
                    while ($row = $result_empresa->fetch_assoc()) {
                        $paterno = isset($row['paterno']) && trim($row['paterno']) !== '' ? " {$row['paterno']}" : "";
                        $materno = isset($row['materno']) && trim($row['materno']) !== '' ? " {$row['materno']}" : "";
                        $empresa_map[$row['id']] = trim("{$row['nombre']}{$paterno}{$materno}");
                    }
                    $stmt_empresa->close();
                }
            }

            $visita_map = [];
            if (!empty($visita_ids_from_vehiculos)) {
                $visita_ids_from_vehiculos = array_unique($visita_ids_from_vehiculos);
                $v_ids_placeholder = implode(',', array_fill(0, count($visita_ids_from_vehiculos), '?'));
                $v_ids_types = str_repeat('i', count($visita_ids_from_vehiculos));
                $stmt_visita = $conn_acceso->prepare("SELECT id, nombre FROM visitas WHERE id IN ($v_ids_placeholder)");
                if ($stmt_visita) {
                    $stmt_visita->bind_param($v_ids_types, ...$visita_ids_from_vehiculos);
                    $stmt_visita->execute();
                    $result_visita = $stmt_visita->get_result();
                    while ($row = $result_visita->fetch_assoc()) {
                        $visita_map[$row['id']] = trim($row['nombre']);
                    }
                    $stmt_visita->close();
                }
            }

            foreach ($logs as $log) {
                $vehiculo_info = $vehiculo_map[$log['target_id']] ?? null;
                $personal_asociado_nombre = 'N/A';
                // Buscar en personal_map, empresa_map o visita_map según el tipo de asociado
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
                    'type' => $target_type
                ];
            }

        } else if ($target_type === 'visita') {
            $stmt_visitas = $conn_acceso->prepare("SELECT id, nombre, tipo FROM visitas WHERE id IN ($ids_placeholder)");
            if (!$stmt_visitas) send_error(500, "Error preparando la consulta de visitas: " . $conn_acceso->error);
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
                    'type' => $target_type
                ];
            }
        } else if ($target_type === 'empresa_empleado') {
            $stmt_empleados = $conn_acceso->prepare("SELECT ee.id, ee.nombre, ee.paterno, ee.materno, ee.rut, e.nombre as empresa_nombre FROM empresa_empleados ee JOIN empresas e ON ee.empresa_id = e.id WHERE ee.id IN ($ids_placeholder)");
            if (!$stmt_empleados) send_error(500, "Error preparando la consulta de empleados de empresa: " . $conn_acceso->error);
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
                    'type' => $target_type
                ];
            }
        } else if ($target_type === 'personal_comision') {
            $stmt_comision = $conn_personal->prepare("SELECT id, nombre_completo FROM personal_comision WHERE id IN ($ids_placeholder)");
            if (!$stmt_comision) send_error(500, "Error preparando la consulta de personal en comisión: " . $conn_personal->error);
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
                    'type' => $target_type
                ];
            }
        }
        echo json_encode($response_logs);
        break;

    case 'POST':
        $data = json_decode(file_get_contents('php://input'), true);
        if (!$data || !isset($data['target_id']) || !isset($data['target_type'])) {
            send_error(400, 'Datos de entrada inválidos.');
        }
        $target_id_input = $data['target_id'];
        $target_type = $data['target_type'];
        $punto_acceso = $data['punto_acceso'] ?? 'desconocido';
        $log_target_id = null;
        $response_data = [];

        if ($target_type === 'personal') {
            $stmt_person = $conn_personal->prepare("SELECT id, Grado, Nombres, Paterno, Materno, NrRut, foto, Unidad FROM personal WHERE NrRut = ? OR id = ?");
            if (!$stmt_person) send_error(500, "Error: " . $conn_personal->error);
            $stmt_person->bind_param("si", $target_id_input, $target_id_input);
            $stmt_person->execute();
            $person = $stmt_person->get_result()->fetch_assoc();
            $stmt_person->close();
            if (!$person) send_error(404, 'Persona no encontrada.');
            $log_target_id = $person['id'];
            $response_data['personalName'] = trim(($person['Grado'] ?? '') . ' ' . ($person['Nombres'] ?? '') . ' ' . ($person['Paterno'] ?? '') . ' ' . ($person['Materno'] ?? ''));
            $response_data['personalRut'] = $person['NrRut'];
            $response_data['personalUnidad'] = $person['Unidad'] ?? 'N/A';
            $response_data['personalPhotoUrl'] = $person['foto'];

        } else if ($target_type === 'vehiculo') {
            $stmt_vehiculo = $conn_acceso->prepare("SELECT id, patente, marca, modelo, asociado_id, asociado_tipo, status, fecha_expiracion, acceso_permanente FROM vehiculos WHERE patente = ? OR id = ?");
            if (!$stmt_vehiculo) send_error(500, "Error: " . $conn_acceso->error);
            $stmt_vehiculo->bind_param("si", $target_id_input, $target_id_input);
            $stmt_vehiculo->execute();
            $vehiculo = $stmt_vehiculo->get_result()->fetch_assoc();
            $stmt_vehiculo->close();
            if (!$vehiculo) send_error(404, 'Vehículo no encontrado.');
            if (get_status_by_date((bool)$vehiculo['acceso_permanente'], $vehiculo['fecha_expiracion']) === 'no autorizado') {
                send_error(403, "Acceso denegado para el vehículo [" . $vehiculo['patente'] . "]. Autorización expirada o no válida.");
            }
            $log_target_id = $vehiculo['id'];
            $response_data['patente'] = $vehiculo['patente'];
            $response_data['marca'] = $vehiculo['marca'] ?? '';
            $response_data['modelo'] = $vehiculo['modelo'] ?? '';
            // Buscar propietario según el tipo de asociado
            if ($vehiculo['asociado_id']) {
                if ($vehiculo['asociado_tipo'] === 'personal' || $vehiculo['asociado_tipo'] === 'FUNCIONARIO') {
                    // Buscar en tabla personal
                    $stmt_personal_vehiculo = $conn_personal->prepare("SELECT Grado, Nombres, Paterno, Materno FROM personal WHERE id = ?");
                    if ($stmt_personal_vehiculo) {
                        $stmt_personal_vehiculo->bind_param("i", $vehiculo['asociado_id']);
                        $stmt_personal_vehiculo->execute();
                        $person_vehiculo = $stmt_personal_vehiculo->get_result()->fetch_assoc();
                        $stmt_personal_vehiculo->close();
                        $response_data['personalName'] = trim(($person_vehiculo['Grado'] ?? '') . ' ' . ($person_vehiculo['Nombres'] ?? '') . ' ' . ($person_vehiculo['Paterno'] ?? '') . ' ' . ($person_vehiculo['Materno'] ?? ''));
                    }
                } elseif ($vehiculo['asociado_tipo'] === 'EMPRESA') {
                    // Buscar en tabla empresa_empleados
                    $stmt_empresa_empleado = $conn_acceso->prepare("SELECT nombre, paterno, materno FROM empresa_empleados WHERE id = ?");
                    if ($stmt_empresa_empleado) {
                        $stmt_empresa_empleado->bind_param("i", $vehiculo['asociado_id']);
                        $stmt_empresa_empleado->execute();
                        $empleado_vehiculo = $stmt_empresa_empleado->get_result()->fetch_assoc();
                        $stmt_empresa_empleado->close();
                        $paterno = isset($empleado_vehiculo['paterno']) && trim($empleado_vehiculo['paterno']) !== '' ? " {$empleado_vehiculo['paterno']}" : "";
                        $materno = isset($empleado_vehiculo['materno']) && trim($empleado_vehiculo['materno']) !== '' ? " {$empleado_vehiculo['materno']}" : "";
                        $response_data['personalName'] = trim("{$empleado_vehiculo['nombre']}{$paterno}{$materno}");
                    }
                } elseif ($vehiculo['asociado_tipo'] === 'VISITA') {
                    // Buscar en tabla visitas
                    $stmt_visita_vehiculo = $conn_acceso->prepare("SELECT nombre, paterno, materno FROM visitas WHERE id = ?");
                    if ($stmt_visita_vehiculo) {
                        $stmt_visita_vehiculo->bind_param("i", $vehiculo['asociado_id']);
                        $stmt_visita_vehiculo->execute();
                        $visita_vehiculo = $stmt_visita_vehiculo->get_result()->fetch_assoc();
                        $stmt_visita_vehiculo->close();
                        $paterno = isset($visita_vehiculo['paterno']) && trim($visita_vehiculo['paterno']) !== '' ? " {$visita_vehiculo['paterno']}" : "";
                        $materno = isset($visita_vehiculo['materno']) && trim($visita_vehiculo['materno']) !== '' ? " {$visita_vehiculo['materno']}" : "";
                        $response_data['personalName'] = trim("{$visita_vehiculo['nombre']}{$paterno}{$materno}");
                    }
                }
            }

        } else if ($target_type === 'visita') {
            $stmt_visita = $conn_acceso->prepare("SELECT id, nombre, paterno, materno, tipo, status, fecha_expiracion, acceso_permanente, en_lista_negra FROM visitas WHERE rut = ? OR nombre LIKE ?");
            if (!$stmt_visita) send_error(500, "Error: " . $conn_acceso->error);
            $search_term = '%' . $target_id_input . '%';
            $stmt_visita->bind_param("ss", $target_id_input, $search_term);
            $stmt_visita->execute();
            $visita = $stmt_visita->get_result()->fetch_assoc();
            $stmt_visita->close();
            if (!$visita) send_error(404, 'Visita no encontrada.');

            if ($visita['en_lista_negra']) {
                send_error(403, "PROHIBIDO SU INGRESO, PERSONA EN LISTA NEGRA, LLAMAR AL CUERPO DE GUARDIA");
            }

            if (get_status_by_date((bool)$visita['acceso_permanente'], $visita['fecha_expiracion']) === 'no autorizado') {
                $paterno = isset($visita['paterno']) && trim($visita['paterno']) !== '' ? " {$visita['paterno']}" : "";
                $materno = isset($visita['materno']) && trim($visita['materno']) !== '' ? " {$visita['materno']}" : "";
                $nombre_completo = trim($visita['nombre'] . $paterno . $materno);
                send_error(403, "Acceso denegado. La autorización para '" . $nombre_completo . "' ha expirado o no es válida.");
            }
            $log_target_id = $visita['id'];
            $paterno = isset($visita['paterno']) && trim($visita['paterno']) !== '' ? " {$visita['paterno']}" : "";
            $materno = isset($visita['materno']) && trim($visita['materno']) !== '' ? " {$visita['materno']}" : "";
            $response_data['nombre'] = trim($visita['nombre'] . $paterno . $materno);
            $response_data['tipo'] = $visita['tipo'];
        }

        $stmt_action = $conn_acceso->prepare("SELECT action FROM access_logs WHERE target_id = ? AND target_type = ? ORDER BY id DESC LIMIT 1");
        if (!$stmt_action) send_error(500, "Error: " . $conn_acceso->error);
        $stmt_action->bind_param("is", $log_target_id, $target_type);
        $stmt_action->execute();
        $last_log = $stmt_action->get_result()->fetch_assoc();
        $stmt_action->close();
        $new_action = ($last_log && $last_log['action'] === 'entrada') ? 'salida' : 'entrada';

        // --- INICIO: Lógica de Horarios para Oficina ---
        if ($target_type === 'personal' && $punto_acceso === 'oficina') {
            date_default_timezone_set('America/Santiago'); // Asegurar la zona horaria correcta
            $current_hour = (int)date('G');

            if ($current_hour === 7) { // Ventana de ENTRADA es a las 7 AM
                if ($new_action === 'salida') {
                    send_error(403, 'Error: Ya tiene una entrada registrada. Solo puede marcar SALIDA en el horario de la tarde.');
                }
                 $new_action = 'entrada'; // Forzar entrada
            } elseif ($current_hour === 16) { // Ventana de SALIDA es a las 4 PM
                if ($new_action === 'entrada') {
                    send_error(403, 'Error: No tiene una entrada registrada para marcar salida. Solo puede marcar ENTRADA en el horario de la mañana.');
                }
                $new_action = 'salida'; // Forzar salida
            } else { // Fuera de horario
                send_error(403, 'Registro de jornada fuera de horario (07:00-07:59 y 16:00-16:59). Utilice el Pórtico.');
            }
        }
        // --- FIN: Lógica de Horarios para Oficina ---

        $custom_message = null;

        $message = $custom_message ?? "Acceso registrado: " . $new_action;

        // Obtener el nombre de la entidad para grabar en campo 'name'
        $entity_name = '';
        if (!empty($response_data['personalName'])) {
            $entity_name = $response_data['personalName'];
        } elseif (!empty($response_data['nombre'])) {
            $entity_name = $response_data['nombre'];
        } elseif (!empty($response_data['patente'])) {
            $entity_name = $response_data['patente'];
        }

        // ✅ CORREGIDO: Agregar campos 'name' y 'motivo' al INSERT
        $stmt_insert = $conn_acceso->prepare("INSERT INTO access_logs (target_id, target_type, action, name, status_message, punto_acceso, motivo) VALUES (?, ?, ?, ?, ?, ?, ?)");
        if (!$stmt_insert) send_error(500, "Error: " . $conn_acceso->error);
        $motivo = null; // Por defecto, sin motivo específico en logs manuales
        $stmt_insert->bind_param("issssss", $log_target_id, $target_type, $new_action, $entity_name, $message, $punto_acceso, $motivo);
        $stmt_insert->execute();
        $stmt_insert->close();

        http_response_code(201);
        echo json_encode(array_merge(['message' => $message, 'action' => $new_action], $response_data));
        break;

    case 'DELETE':
        if (!isset($_GET['id'])) {
            send_error(400, 'ID de registro no proporcionado.');
        }
        $log_id = intval($_GET['id']);

        $stmt = $conn_acceso->prepare("UPDATE access_logs SET log_status = 'cancelado' WHERE id = ?");
        if (!$stmt) {
            send_error(500, "Error preparando la consulta: " . $conn_acceso->error);
        }
        
        $stmt->bind_param("i", $log_id);
        $stmt->execute();

        if ($stmt->affected_rows > 0) {
            http_response_code(200);
            echo json_encode(['message' => 'Registro cancelado correctamente.']);
        } else {
            send_error(404, 'Registro no encontrado o ya fue cancelado.');
        }
        $stmt->close();
        break;

    default:
        send_error(405, 'Método no permitido');
        break;
}

$conn_acceso->close();
if (isset($conn_personal)) {
    $conn_personal->close();
}
?>
<?php
// api/portico.php
require_once 'database/db_acceso.php';
require_once 'database/db_personal.php';

ini_set('display_errors', 1);
error_reporting(E_ALL);

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

$method = $_SERVER['REQUEST_METHOD'];

if ($method === 'OPTIONS') {
    http_response_code(204);
    exit();
}

function send_error($code, $message) {
    http_response_code($code);
    echo json_encode(['message' => $message]);
    exit;
}

if ($method !== 'POST') {
    send_error(405, 'Método no permitido.');
}

$data = json_decode(file_get_contents('php://input'), true);
if (!$data || !isset($data['id'])) {
    send_error(400, 'ID no proporcionado.');
}
$scanned_id = trim($data['id']);

$entity = null;
$entity_type = null;
$unauthorized_vehicle_plate = null;
$unauthorized_vehicle_reason = null;
$unauthorized_visitor_name = null;
$unauthorized_visitor_reason = null;

// 1. Buscar en Personal
$stmt = $conn_personal->prepare("SELECT id, Grado, Nombres, Paterno, Materno, NrRut, foto, es_residente, Unidad FROM personal WHERE NrRut = ?");
if ($stmt) {
    $stmt->bind_param("s", $scanned_id);
    $stmt->execute();
    $result = $stmt->get_result();
    if ($result->num_rows > 0) {
        $entity = $result->fetch_assoc();
        $entity_type = 'personal';
    }
    $stmt->close();
}

// 2. Si no es personal, buscar en Vehículos
if (!$entity) {
    $stmt_find = $conn_acceso->prepare("SELECT id, patente, tipo, tipo_vehiculo, marca, modelo, asociado_id, asociado_tipo, status, acceso_permanente, fecha_inicio, fecha_expiracion FROM vehiculos WHERE patente = ? OR id = ?");
    if ($stmt_find) {
        $stmt_find->bind_param("ss", $scanned_id, $scanned_id);
        $stmt_find->execute();
        $result = $stmt_find->get_result();
        if ($result->num_rows > 0) {
            $vehicle_data = $result->fetch_assoc();

            $is_authorized = false;
            $rejection_reasons = []; // Array para múltiples razones

            // 1. Validar status
            if ($vehicle_data['status'] !== 'autorizado') {
                $rejection_reasons[] = "Status no autorizado";
            }

            // 2. Validar fecha de inicio (siempre, independiente del status)
            if (!empty($vehicle_data['fecha_inicio'])) {
                try {
                    $start_date = new DateTime($vehicle_data['fecha_inicio']);
                    $today = new DateTime('today');
                    if ($start_date > $today) {
                        $rejection_reasons[] = "su fecha de ingreso aún no ha comenzado";
                    }
                } catch (Exception $e) {
                    $rejection_reasons[] = "Fecha de inicio inválida";
                }
            }

            // 3. Validar fecha de expiración (siempre, si no es permanente)
            if (!$vehicle_data['acceso_permanente']) {
                if (!empty($vehicle_data['fecha_expiracion'])) {
                    try {
                        $expiration_date = new DateTime($vehicle_data['fecha_expiracion']);
                        $today = new DateTime('today');
                        if ($expiration_date < $today) {
                            $rejection_reasons[] = "su fecha de ingreso expiró";
                        } else {
                            // Si el status es autorizado y las fechas son válidas, autorizar
                            if ($vehicle_data['status'] === 'autorizado' &&
                                (empty($vehicle_data['fecha_inicio']) || new DateTime($vehicle_data['fecha_inicio']) <= $today)) {
                                $is_authorized = true;
                            }
                        }
                    } catch (Exception $e) {
                        $rejection_reasons[] = "Fecha de expiración inválida";
                    }
                } else {
                    $rejection_reasons[] = "Sin fecha de expiración válida";
                }
            } else {
                // Si es acceso permanente y status es autorizado, autorizar
                if ($vehicle_data['status'] === 'autorizado' &&
                    (empty($vehicle_data['fecha_inicio']) || new DateTime($vehicle_data['fecha_inicio']) <= new DateTime('today'))) {
                    $is_authorized = true;
                }
            }

            if ($is_authorized) {
                $entity = $vehicle_data;
                $entity_type = 'vehiculo';
            } else {
                $unauthorized_vehicle_plate = $vehicle_data['patente'];
                // Combinar múltiples razones con comas
                $unauthorized_vehicle_reason = implode(", ", $rejection_reasons);
            }
        }
        $stmt_find->close();
    }
}

// 3. Si no es vehículo, buscar en Visitas
if (!$entity) {
    $stmt_find_visita = $conn_acceso->prepare("SELECT id, nombre, paterno, materno, rut, tipo, status, acceso_permanente, fecha_inicio, fecha_expiracion, en_lista_negra, poc_personal_id, familiar_de_personal_id FROM visitas WHERE rut = ?");
    if ($stmt_find_visita) {
        $stmt_find_visita->bind_param("s", $scanned_id);
        $stmt_find_visita->execute();
        $result = $stmt_find_visita->get_result();
        if ($result->num_rows > 0) {
            $visita_data = $result->fetch_assoc();

            if ($visita_data['en_lista_negra']) {
                send_error(403, "PROHIBIDO SU INGRESO, PERSONA EN LISTA NEGRA, LLAMAR AL CUERPO DE GUARDIA");
            }

            $is_authorized = false;
            $rejection_reasons = []; // Array para múltiples razones

            // 1. Validar status
            if ($visita_data['status'] !== 'autorizado') {
                $rejection_reasons[] = "Status no autorizado";
            }

            // 2. Validar fecha de inicio (siempre, independiente del status)
            if (!empty($visita_data['fecha_inicio'])) {
                try {
                    $start_date = new DateTime($visita_data['fecha_inicio']);
                    $today = new DateTime('today');
                    if ($start_date > $today) {
                        $rejection_reasons[] = "su fecha de ingreso aún no ha comenzado";
                    }
                } catch (Exception $e) {
                    $rejection_reasons[] = "Fecha de inicio inválida";
                }
            }

            // 3. Validar fecha de expiración (siempre, si no es permanente)
            if (!$visita_data['acceso_permanente']) {
                if (!empty($visita_data['fecha_expiracion'])) {
                    try {
                        $expiration_date = new DateTime($visita_data['fecha_expiracion']);
                        $today = new DateTime('today');
                        if ($expiration_date < $today) {
                            $rejection_reasons[] = "su fecha de ingreso expiró";
                        } else {
                            // Si el status es autorizado y las fechas son válidas, autorizar
                            if ($visita_data['status'] === 'autorizado' &&
                                (empty($visita_data['fecha_inicio']) || new DateTime($visita_data['fecha_inicio']) <= $today)) {
                                $is_authorized = true;
                            }
                        }
                    } catch (Exception $e) {
                        $is_authorized = false;
                        $rejection_reasons[] = "Fecha de expiración inválida";
                    }
                } else {
                    $rejection_reasons[] = "Sin fecha de expiración válida";
                }
            } else {
                // Si es acceso permanente y status es autorizado, autorizar
                if ($visita_data['status'] === 'autorizado' &&
                    (empty($visita_data['fecha_inicio']) || new DateTime($visita_data['fecha_inicio']) <= new DateTime('today'))) {
                    $is_authorized = true;
                }
            }

            if ($is_authorized) {
                $entity = $visita_data;
                $entity_type = 'visita';
            } else {
                $unauthorized_visitor_name = $visita_data['nombre'];
                // Combinar múltiples razones con comas
                $unauthorized_visitor_reason = implode(", ", $rejection_reasons);
            }
        }
        $stmt_find_visita->close();
    }
}

// 4. Si no es visita, buscar en Empleados de Empresas
if (!$entity) {
    $stmt_empleado = $conn_acceso->prepare("SELECT ee.id, ee.nombre, ee.paterno, ee.materno, ee.rut, ee.acceso_permanente, ee.fecha_inicio, ee.fecha_expiracion, e.nombre as empresa_nombre FROM empresa_empleados ee JOIN empresas e ON ee.empresa_id = e.id WHERE ee.rut = ?");
    if ($stmt_empleado) {
        $stmt_empleado->bind_param("s", $scanned_id);
        $stmt_empleado->execute();
        $result = $stmt_empleado->get_result();
        if ($result->num_rows > 0) {
            $empleado_data = $result->fetch_assoc();

            $is_authorized = false;
            $rejection_reasons = [];

            // 1. Validar fecha de inicio
            if (!empty($empleado_data['fecha_inicio'])) {
                try {
                    $start_date = new DateTime($empleado_data['fecha_inicio']);
                    $today = new DateTime('today');
                    if ($start_date > $today) {
                        $rejection_reasons[] = "su fecha de ingreso aún no ha comenzado";
                    }
                } catch (Exception $e) {
                    $rejection_reasons[] = "Fecha de inicio inválida";
                }
            }

            // 2. Validar fecha de expiración (si no es acceso permanente)
            if ($empleado_data['acceso_permanente']) {
                // Si es acceso permanente y no hay razones de rechazo, autorizar
                if (empty($rejection_reasons)) {
                    $is_authorized = true;
                }
            } elseif (!empty($empleado_data['fecha_expiracion'])) {
                try {
                    $expiration_date = new DateTime($empleado_data['fecha_expiracion']);
                    $today = new DateTime('today');
                    if ($expiration_date < $today) {
                        $rejection_reasons[] = "su fecha de ingreso expiró";
                    } else if (empty($rejection_reasons)) {
                        // Si no hay razones de rechazo y la fecha es válida, autorizar
                        $is_authorized = true;
                    }
                } catch (Exception $e) {
                    $rejection_reasons[] = "Fecha de expiración inválida";
                }
            } else {
                $rejection_reasons[] = "Sin fecha de expiración válida";
            }

            if ($is_authorized) {
                $entity = $empleado_data;
                $entity_type = 'empresa_empleado';
            } else if (!empty($rejection_reasons)) {
                // Guardar razones de rechazo para mostrar después
                $unauthorized_visitor_name = $empleado_data['nombre'] . ' ' . $empleado_data['paterno'];
                $unauthorized_visitor_reason = implode(", ", $rejection_reasons);
            }
        }
        $stmt_empleado->close();
    }
}

// 5. Si no es empleado de empresa, buscar en Personal en Comisión
if (!$entity) {
    $stmt_comision = $conn_personal->prepare("SELECT id, rut, nombre_completo, estado FROM personal_db.personal_comision WHERE rut = ? AND estado = 'Activo'");
    if ($stmt_comision) {
        $stmt_comision->bind_param("s", $scanned_id);
        $stmt_comision->execute();
        $result = $stmt_comision->get_result();
        if ($result->num_rows > 0) {
            $entity = $result->fetch_assoc();
            $entity_type = 'personal_comision';
        }
        $stmt_comision->close();
    }
}

if (!$entity) {
    if ($unauthorized_vehicle_plate) {
        $message = "Acceso denegado para el vehículo [{$unauthorized_vehicle_plate}]";
        if ($unauthorized_vehicle_reason) {
            $message .= ": " . $unauthorized_vehicle_reason;
        } else {
            $message .= ". Autorización expirada o no válida.";
        }
        send_error(403, $message);
    } elseif ($unauthorized_visitor_name) {
        $message = "Acceso denegado para la visita [{$unauthorized_visitor_name}]";
        if ($unauthorized_visitor_reason) {
            $message .= ": " . $unauthorized_visitor_reason;
        } else {
            $message .= ". Autorización expirada o no válida.";
        }
        send_error(403, $message);
    } else {
        send_error(404, "RUT no registrado en el sistema. Por favor, verifique que el RUT sea correcto o contacte al guardia.");
    }
}

// Determinar la última acción para deducir la nueva acción
$stmt_action = $conn_acceso->prepare("SELECT action FROM access_logs WHERE target_id = ? AND target_type = ? AND log_status = 'activo' ORDER BY id DESC LIMIT 1");
$stmt_action->bind_param("is", $entity['id'], $entity_type);
$stmt_action->execute();
$last_log = $stmt_action->get_result()->fetch_assoc();
$stmt_action->close();

$new_action = ($last_log && $last_log['action'] === 'entrada') ? 'salida' : 'entrada';

$response_data = [
    'id' => $entity['id'],
    'type' => $entity_type,
    'action' => $new_action
];
$custom_message = null;

// Lógica específica por tipo de entidad
switch ($entity_type) {
    case 'personal':
        $apellidoMaterno = isset($entity['Materno']) && !empty($entity['Materno']) ? " {$entity['Materno']}" : "";
        $response_data['name'] = trim("{$entity['Grado']} {$entity['Nombres']} {$entity['Paterno']}{$apellidoMaterno}");
        $response_data['photoUrl'] = $entity['foto'];

        if ($new_action === 'entrada') {
            // Ahora TODOS los usuarios, residentes y no residentes, deben pasar por la clarificación
            http_response_code(200);
            echo json_encode([
                'action' => 'clarification_required',
                'person_details' => [
                    'id' => $entity['id'],
                    'name' => trim("{$entity['Grado']} {$entity['Nombres']} {$entity['Paterno']}{$apellidoMaterno}"),
                    'rut' => $entity['NrRut'],
                    'photoUrl' => $entity['foto'],
                    'unidad' => $entity['Unidad'] ?? 'No especificada',
                    'es_residente' => $entity['es_residente']
                ]
            ]);
            $conn_acceso->close();
            $conn_personal->close();
            exit;
        }
        
        if ($new_action === 'salida') {
            // Se revierte a borrado suave (soft delete) actualizando el status, según solicitud del usuario.
            $stmt_he = $conn_acceso->prepare("UPDATE horas_extra SET status = 'finalizado' WHERE personal_rut = ? AND status = 'activo'");
            if($stmt_he) {
                $stmt_he->bind_param("s", $entity['NrRut']);
                if ($stmt_he->execute()) {
                    if ($stmt_he->affected_rows > 0) {
                        $custom_message = "Salida registrada. Se finalizó el registro de Salida Posterior.";
                    }
                    // Si affected_rows es 0, no se encontró un registro activo, lo cual es normal.
                } else {
                    $custom_message = "Error al ejecutar la actualización de Salida Posterior.";
                }
                $stmt_he->close();
            } else {
                $custom_message = "Error al preparar la consulta para finalizar Salida Posterior.";
            }
        }
        break;
    case 'vehiculo':
        $response_data['name'] = $entity['patente'];
        $response_data['patente'] = $entity['patente'];
        $response_data['tipo'] = $entity['tipo'] ?? '';
        $response_data['tipo_vehiculo'] = $entity['tipo_vehiculo'] ?? '';
        $response_data['marca'] = $entity['marca'] ?? '';
        $response_data['modelo'] = $entity['modelo'] ?? '';

        // Si hay un asociado (propietario), obtener su información según el tipo
        if (!empty($entity['asociado_id'])) {
            if ($entity['asociado_tipo'] === 'personal' || $entity['asociado_tipo'] === 'FUNCIONARIO') {
                // Buscar en tabla personal
                $stmt_owner = $conn_personal->prepare("SELECT Grado, Nombres, Paterno, Materno FROM personal WHERE id = ?");
                if ($stmt_owner) {
                    $stmt_owner->bind_param("i", $entity['asociado_id']);
                    $stmt_owner->execute();
                    $result_owner = $stmt_owner->get_result();
                    if ($result_owner->num_rows > 0) {
                        $owner = $result_owner->fetch_assoc();
                        // Construir el nombre completo del propietario
                        $apellidoMaterno = isset($owner['Materno']) && trim($owner['Materno']) !== '' ? " {$owner['Materno']}" : "";
                        $response_data['personalName'] = trim("{$owner['Grado']} {$owner['Nombres']} {$owner['Paterno']}{$apellidoMaterno}");
                    }
                    $stmt_owner->close();
                }
            } elseif ($entity['asociado_tipo'] === 'EMPRESA') {
                // Buscar en tabla empresa_empleados
                $stmt_owner = $conn_acceso->prepare("SELECT nombre, paterno, materno FROM empresa_empleados WHERE id = ?");
                if ($stmt_owner) {
                    $stmt_owner->bind_param("i", $entity['asociado_id']);
                    $stmt_owner->execute();
                    $result_owner = $stmt_owner->get_result();
                    if ($result_owner->num_rows > 0) {
                        $owner = $result_owner->fetch_assoc();
                        // Construir el nombre completo del empleado
                        $paterno = isset($owner['paterno']) && trim($owner['paterno']) !== '' ? " {$owner['paterno']}" : "";
                        $materno = isset($owner['materno']) && trim($owner['materno']) !== '' ? " {$owner['materno']}" : "";
                        $response_data['personalName'] = trim("{$owner['nombre']}{$paterno}{$materno}");
                    }
                    $stmt_owner->close();
                }
            } elseif ($entity['asociado_tipo'] === 'VISITA') {
                // Buscar en tabla visitas
                $stmt_owner = $conn_acceso->prepare("SELECT nombre, paterno, materno FROM visitas WHERE id = ?");
                if ($stmt_owner) {
                    $stmt_owner->bind_param("i", $entity['asociado_id']);
                    $stmt_owner->execute();
                    $result_owner = $stmt_owner->get_result();
                    if ($result_owner->num_rows > 0) {
                        $owner = $result_owner->fetch_assoc();
                        // Construir el nombre completo de la visita
                        $paterno = isset($owner['paterno']) && trim($owner['paterno']) !== '' ? " {$owner['paterno']}" : "";
                        $materno = isset($owner['materno']) && trim($owner['materno']) !== '' ? " {$owner['materno']}" : "";
                        $response_data['personalName'] = trim("{$owner['nombre']}{$paterno}{$materno}");
                    }
                    $stmt_owner->close();
                }
            }
        }
        break;
    case 'visita':
        // Construir nombre completo con paterno y materno si existen
        $paterno = isset($entity['paterno']) && trim($entity['paterno']) !== '' ? " {$entity['paterno']}" : "";
        $materno = isset($entity['materno']) && trim($entity['materno']) !== '' ? " {$entity['materno']}" : "";
        $response_data['name'] = trim($entity['nombre'] . $paterno . $materno);
        $response_data['tipo'] = $entity['tipo'] ?? '';
        break;
    case 'personal_comision':
        $response_data['name'] = $entity['nombre_completo'];
        break;
    case 'empresa_empleado':
        $response_data['name'] = trim("{$entity['nombre']} {$entity['paterno']} {$entity['materno']}");
        $response_data['empresa_nombre'] = $entity['empresa_nombre'];
        break;
}

// Insertar el nuevo registro de acceso
$message_to_log = $custom_message ?? "Acceso {$new_action} registrado via Portico.";
$log_status = 'activo';
$punto_acceso = 'portico';

// Para asegurarnos de que el nombre incluye el apellido materno si existe
if ($entity_type === 'personal') {
    // Asegurémonos de que tenemos el apellido materno si existe
    if (isset($entity['Materno']) && trim($entity['Materno']) !== '') {
        $full_name = trim("{$entity['Grado']} {$entity['Nombres']} {$entity['Paterno']} {$entity['Materno']}");
    } else {
        $full_name = trim("{$entity['Grado']} {$entity['Nombres']} {$entity['Paterno']}");
    }
    $response_data['name'] = $full_name;
}

$stmt_insert = $conn_acceso->prepare("INSERT INTO access_logs (target_id, name, action, status_message, target_type, log_status, punto_acceso) VALUES (?, ?, ?, ?, ?, ?, ?)");
if (!$stmt_insert) {
    var_dump($conn_acceso->error);
    exit();
}

$stmt_insert->bind_param("issssss", $entity['id'], $response_data['name'], $new_action, $message_to_log, $entity_type, $log_status, $punto_acceso);
$stmt_insert->execute();

if ($stmt_insert->affected_rows > 0) {
    http_response_code(201);
    
    // Personalizar el mensaje según el tipo de entidad
    if ($entity_type === 'vehiculo') {
        $response_data['message'] = $custom_message ?? "Acceso '{$new_action}' registrado correctamente.";
    } else {
        $response_data['message'] = $custom_message ?? "Acceso '{$new_action}' para {$response_data['name']} registrado correctamente.";
    }
    
    echo json_encode($response_data);
} else {
    send_error(500, "Error al registrar el acceso.");
}

$stmt_insert->close();
$conn_acceso->close();
$conn_personal->close();

?>
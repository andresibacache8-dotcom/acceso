<?php
/**
 * api/portico-migrated.php
 *
 * Control de escaneo de pórtico - Validación de acceso
 * POST-only API para procesar entradas/salidas
 *
 * Busca en 5 tablas:
 * 1. Personal (personal_db)
 * 2. Vehículos (acceso_pro_db)
 * 3. Visitas (acceso_pro_db)
 * 4. Empleados de Empresas (acceso_pro_db)
 * 5. Personal en Comisión (personal_db)
 *
 * Método:
 * - POST: Procesar escaneo (RUT o patente)
 */

// ============================================================================
// CONFIGURATION & IMPORTS
// ============================================================================

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/core/ResponseHandler.php';

// Headers
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

// Preflight
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(204);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    ApiResponse::error('Método no permitido', 405);
}

// ============================================================================
// HELPER FUNCTIONS
// ============================================================================

/**
 * Valida acceso por fechas y estado
 * Retorna: ['authorized' => bool, 'reasons' => array]
 */
function validar_acceso($status, $fecha_inicio, $fecha_expiracion, $acceso_permanente) {
    $authorized = false;
    $reasons = [];

    // Validar status (si existe)
    if (!empty($status) && $status !== 'autorizado') {
        $reasons[] = "Status no autorizado";
    }

    // Validar fecha de inicio
    if (!empty($fecha_inicio)) {
        try {
            $start_date = new DateTime($fecha_inicio);
            $today = new DateTime('today');
            if ($start_date > $today) {
                $reasons[] = "su fecha de ingreso aún no ha comenzado";
            }
        } catch (Exception $e) {
            $reasons[] = "Fecha de inicio inválida";
        }
    }

    // Validar fecha de expiración
    if (!$acceso_permanente) {
        if (!empty($fecha_expiracion)) {
            try {
                $expiration_date = new DateTime($fecha_expiracion);
                $today = new DateTime('today');
                if ($expiration_date < $today) {
                    $reasons[] = "su fecha de ingreso expiró";
                } else {
                    // Si status es válido y fechas son OK, autorizar
                    if (empty($status) || $status === 'autorizado') {
                        if (empty($fecha_inicio) || new DateTime($fecha_inicio) <= new DateTime('today')) {
                            $authorized = true;
                        }
                    }
                }
            } catch (Exception $e) {
                $reasons[] = "Fecha de expiración inválida";
            }
        } else {
            $reasons[] = "Sin fecha de expiración válida";
        }
    } else {
        // Acceso permanente: solo validar status y fecha inicio
        if (empty($status) || $status === 'autorizado') {
            if (empty($fecha_inicio) || new DateTime($fecha_inicio) <= new DateTime('today')) {
                $authorized = true;
            }
        }
    }

    return [
        'authorized' => $authorized,
        'reasons' => $reasons
    ];
}

/**
 * Busca persona en tabla personal
 */
function buscar_personal($rut, $conn_personal) {
    $stmt = $conn_personal->prepare(
        "SELECT id, Grado, Nombres, Paterno, Materno, NrRut, foto, es_residente, Unidad
         FROM personal WHERE NrRut = ? LIMIT 1"
    );

    if (!$stmt) return null;

    $stmt->bind_param("s", $rut);
    $stmt->execute();
    $result = $stmt->get_result();
    $row = $result->fetch_assoc();
    $stmt->close();

    return $row;
}

/**
 * Busca vehículo en tabla vehiculos
 */
function buscar_vehiculo($rut_o_patente, $conn_acceso) {
    $stmt = $conn_acceso->prepare(
        "SELECT id, patente, tipo, tipo_vehiculo, marca, modelo, asociado_id, asociado_tipo,
                status, acceso_permanente, fecha_inicio, fecha_expiracion
         FROM vehiculos WHERE patente = ? OR id = ? LIMIT 1"
    );

    if (!$stmt) return null;

    $stmt->bind_param("ss", $rut_o_patente, $rut_o_patente);
    $stmt->execute();
    $result = $stmt->get_result();
    $row = $result->fetch_assoc();
    $stmt->close();

    return $row;
}

/**
 * Busca visita en tabla visitas
 */
function buscar_visita($rut, $conn_acceso) {
    $stmt = $conn_acceso->prepare(
        "SELECT id, nombre, paterno, materno, rut, tipo, status, acceso_permanente,
                fecha_inicio, fecha_expiracion, en_lista_negra, poc_personal_id, familiar_de_personal_id
         FROM visitas WHERE rut = ? LIMIT 1"
    );

    if (!$stmt) return null;

    $stmt->bind_param("s", $rut);
    $stmt->execute();
    $result = $stmt->get_result();
    $row = $result->fetch_assoc();
    $stmt->close();

    return $row;
}

/**
 * Busca empleado de empresa
 */
function buscar_empleado_empresa($rut, $conn_acceso) {
    $stmt = $conn_acceso->prepare(
        "SELECT ee.id, ee.nombre, ee.paterno, ee.materno, ee.rut, ee.acceso_permanente,
                ee.fecha_inicio, ee.fecha_expiracion, e.nombre as empresa_nombre
         FROM empresa_empleados ee
         JOIN empresas e ON ee.empresa_id = e.id
         WHERE ee.rut = ? LIMIT 1"
    );

    if (!$stmt) return null;

    $stmt->bind_param("s", $rut);
    $stmt->execute();
    $result = $stmt->get_result();
    $row = $result->fetch_assoc();
    $stmt->close();

    return $row;
}

/**
 * Busca personal en comisión
 */
function buscar_personal_comision($rut, $conn_personal) {
    $stmt = $conn_personal->prepare(
        "SELECT id, rut, nombre_completo, estado FROM personal_comision
         WHERE rut = ? AND estado = 'Activo' LIMIT 1"
    );

    if (!$stmt) return null;

    $stmt->bind_param("s", $rut);
    $stmt->execute();
    $result = $stmt->get_result();
    $row = $result->fetch_assoc();
    $stmt->close();

    return $row;
}

/**
 * Obtiene información del propietario de un vehículo
 */
function obtener_propietario_vehiculo($asociado_id, $asociado_tipo, $conn_personal, $conn_acceso) {
    $name = null;

    if (empty($asociado_id)) {
        return $name;
    }

    if ($asociado_tipo === 'PERSONAL' || $asociado_tipo === 'FUNCIONARIO') {
        $stmt = $conn_personal->prepare("SELECT Grado, Nombres, Paterno, Materno FROM personal WHERE id = ?");
        if ($stmt) {
            $stmt->bind_param("i", $asociado_id);
            $stmt->execute();
            $result = $stmt->get_result();
            if ($result->num_rows > 0) {
                $owner = $result->fetch_assoc();
                $materno = isset($owner['Materno']) && trim($owner['Materno']) !== '' ? " {$owner['Materno']}" : "";
                $name = trim("{$owner['Grado']} {$owner['Nombres']} {$owner['Paterno']}{$materno}");
            }
            $stmt->close();
        }
    } elseif ($asociado_tipo === 'EMPRESA') {
        $stmt = $conn_acceso->prepare("SELECT nombre, paterno, materno FROM empresa_empleados WHERE id = ?");
        if ($stmt) {
            $stmt->bind_param("i", $asociado_id);
            $stmt->execute();
            $result = $stmt->get_result();
            if ($result->num_rows > 0) {
                $owner = $result->fetch_assoc();
                $paterno = isset($owner['paterno']) && trim($owner['paterno']) !== '' ? " {$owner['paterno']}" : "";
                $materno = isset($owner['materno']) && trim($owner['materno']) !== '' ? " {$owner['materno']}" : "";
                $name = trim("{$owner['nombre']}{$paterno}{$materno}");
            }
            $stmt->close();
        }
    } elseif ($asociado_tipo === 'VISITA') {
        $stmt = $conn_acceso->prepare("SELECT nombre, paterno, materno FROM visitas WHERE id = ?");
        if ($stmt) {
            $stmt->bind_param("i", $asociado_id);
            $stmt->execute();
            $result = $stmt->get_result();
            if ($result->num_rows > 0) {
                $owner = $result->fetch_assoc();
                $paterno = isset($owner['paterno']) && trim($owner['paterno']) !== '' ? " {$owner['paterno']}" : "";
                $materno = isset($owner['materno']) && trim($owner['materno']) !== '' ? " {$owner['materno']}" : "";
                $name = trim("{$owner['nombre']}{$paterno}{$materno}");
            }
            $stmt->close();
        }
    }

    return $name;
}

/**
 * Obtiene última acción (entrada/salida)
 */
function obtener_nueva_accion($entity_id, $entity_type, $conn_acceso) {
    $stmt = $conn_acceso->prepare(
        "SELECT action FROM access_logs
         WHERE target_id = ? AND target_type = ? AND log_status = 'activo'
         ORDER BY id DESC LIMIT 1"
    );

    if (!$stmt) {
        return 'entrada'; // Default
    }

    $stmt->bind_param("is", $entity_id, $entity_type);
    $stmt->execute();
    $result = $stmt->get_result();
    $last_log = $result->fetch_assoc();
    $stmt->close();

    return ($last_log && $last_log['action'] === 'entrada') ? 'salida' : 'entrada';
}

/**
 * Registra acceso en BD
 */
function registrar_acceso($entity_id, $name, $action, $message, $entity_type, $conn_acceso) {
    $log_status = 'activo';
    $punto_acceso = 'portico';

    $stmt = $conn_acceso->prepare(
        "INSERT INTO access_logs (target_id, name, action, status_message, target_type, log_status, punto_acceso)
         VALUES (?, ?, ?, ?, ?, ?, ?)"
    );

    if (!$stmt) {
        return false;
    }

    $stmt->bind_param("issssss", $entity_id, $name, $action, $message, $entity_type, $log_status, $punto_acceso);
    $result = $stmt->execute();
    $affected = $stmt->affected_rows;
    $stmt->close();

    return ($result && $affected > 0);
}

/**
 * Finaliza horas_extra al salir
 */
function finalizar_horas_extra($personal_rut, $conn_acceso) {
    $stmt = $conn_acceso->prepare(
        "UPDATE horas_extra SET status = 'finalizado'
         WHERE personal_rut = ? AND status = 'activo'"
    );

    if (!$stmt) {
        return null;
    }

    $stmt->bind_param("s", $personal_rut);
    $stmt->execute();
    $affected = $stmt->affected_rows;
    $stmt->close();

    if ($affected > 0) {
        return "Salida registrada. Se finalizó el registro de Salida Posterior.";
    }

    return null;
}

// ============================================================================
// MAIN HANDLER
// ============================================================================

function handle_post($conn_acceso, $conn_personal) {
    $data = json_decode(file_get_contents('php://input'), true);

    if (!$data || !isset($data['id'])) {
        ApiResponse::badRequest('ID no proporcionado');
    }

    $scanned_id = trim($data['id']);
    $entity = null;
    $entity_type = null;
    $rejection_info = null; // Para vehículos/visitas rechazados

    // ========== BÚSQUEDA 1: Personal ==========
    $entity = buscar_personal($scanned_id, $conn_personal);
    if ($entity) {
        $entity_type = 'personal';
    }

    // ========== BÚSQUEDA 2: Vehículos ==========
    if (!$entity) {
        $vehicle = buscar_vehiculo($scanned_id, $conn_acceso);
        if ($vehicle) {
            $validation = validar_acceso(
                $vehicle['status'],
                $vehicle['fecha_inicio'],
                $vehicle['fecha_expiracion'],
                $vehicle['acceso_permanente']
            );

            if ($validation['authorized']) {
                $entity = $vehicle;
                $entity_type = 'vehiculo';
            } else {
                $rejection_info = [
                    'type' => 'vehiculo',
                    'patente' => $vehicle['patente'],
                    'reasons' => $validation['reasons']
                ];
            }
        }
    }

    // ========== BÚSQUEDA 3: Visitas ==========
    if (!$entity) {
        $visita = buscar_visita($scanned_id, $conn_acceso);
        if ($visita) {
            // Validar lista negra
            if ($visita['en_lista_negra']) {
                ApiResponse::error(
                    "PROHIBIDO SU INGRESO, PERSONA EN LISTA NEGRA, LLAMAR AL CUERPO DE GUARDIA",
                    403
                );
            }

            $validation = validar_acceso(
                $visita['status'],
                $visita['fecha_inicio'],
                $visita['fecha_expiracion'],
                $visita['acceso_permanente']
            );

            if ($validation['authorized']) {
                $entity = $visita;
                $entity_type = 'visita';
            } else {
                $rejection_info = [
                    'type' => 'visita',
                    'nombre' => $visita['nombre'],
                    'reasons' => $validation['reasons']
                ];
            }
        }
    }

    // ========== BÚSQUEDA 4: Empleados de Empresa ==========
    if (!$entity) {
        $empleado = buscar_empleado_empresa($scanned_id, $conn_acceso);
        if ($empleado) {
            $validation = validar_acceso(
                null, // No hay campo 'status' en empleados
                $empleado['fecha_inicio'],
                $empleado['fecha_expiracion'],
                $empleado['acceso_permanente']
            );

            if ($validation['authorized']) {
                $entity = $empleado;
                $entity_type = 'empresa_empleado';
            } else {
                $rejection_info = [
                    'type' => 'visita',
                    'nombre' => $empleado['nombre'] . ' ' . $empleado['paterno'],
                    'reasons' => $validation['reasons']
                ];
            }
        }
    }

    // ========== BÚSQUEDA 5: Personal en Comisión ==========
    if (!$entity) {
        $comision = buscar_personal_comision($scanned_id, $conn_personal);
        if ($comision) {
            $entity = $comision;
            $entity_type = 'personal_comision';
        }
    }

    // ========== NO ENCONTRADO ==========
    if (!$entity) {
        if ($rejection_info) {
            $message = "Acceso denegado para ";
            if ($rejection_info['type'] === 'vehiculo') {
                $message .= "el vehículo [{$rejection_info['patente']}]";
            } else {
                $message .= "la visita [{$rejection_info['nombre']}]";
            }

            if (!empty($rejection_info['reasons'])) {
                $message .= ": " . implode(", ", $rejection_info['reasons']);
            } else {
                $message .= ". Autorización expirada o no válida.";
            }

            ApiResponse::error($message, 403);
        } else {
            ApiResponse::notFound("RUT no registrado en el sistema. Por favor, verifique que el RUT sea correcto o contacte al guardia.");
        }
    }

    // ========== OBTENER NUEVA ACCIÓN ==========
    $new_action = obtener_nueva_accion($entity['id'], $entity_type, $conn_acceso);

    // ========== CONSTRUIR RESPUESTA SEGÚN TIPO ==========
    $response_data = [
        'id' => $entity['id'],
        'type' => $entity_type,
        'action' => $new_action
    ];
    $custom_message = null;

    switch ($entity_type) {
        case 'personal':
            $materno = isset($entity['Materno']) && trim($entity['Materno']) !== '' ? " {$entity['Materno']}" : "";
            $full_name = trim("{$entity['Grado']} {$entity['Nombres']} {$entity['Paterno']}{$materno}");
            $response_data['name'] = $full_name;
            $response_data['photoUrl'] = $entity['foto'];
            $response_data['unidad'] = $entity['Unidad'] ?? 'No especificada';
            $response_data['es_residente'] = $entity['es_residente'];

            // Si es entrada, requerir clarificación
            if ($new_action === 'entrada') {
                http_response_code(200);
                echo json_encode([
                    'action' => 'clarification_required',
                    'person_details' => $response_data
                ]);
                $conn_acceso->close();
                $conn_personal->close();
                exit;
            }

            // Si es salida, finalizar horas_extra
            if ($new_action === 'salida') {
                $msg = finalizar_horas_extra($entity['NrRut'], $conn_acceso);
                if ($msg) {
                    $custom_message = $msg;
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

            // Obtener propietario
            if (!empty($entity['asociado_id'])) {
                $propietario_nombre = obtener_propietario_vehiculo(
                    $entity['asociado_id'],
                    $entity['asociado_tipo'],
                    $conn_personal,
                    $conn_acceso
                );
                if ($propietario_nombre) {
                    $response_data['personalName'] = $propietario_nombre;
                }
            }
            break;

        case 'visita':
            $paterno = isset($entity['paterno']) && trim($entity['paterno']) !== '' ? " {$entity['paterno']}" : "";
            $materno = isset($entity['materno']) && trim($entity['materno']) !== '' ? " {$entity['materno']}" : "";
            $response_data['name'] = trim($entity['nombre'] . $paterno . $materno);
            $response_data['tipo'] = $entity['tipo'] ?? '';
            break;

        case 'empresa_empleado':
            $paterno = isset($entity['paterno']) && trim($entity['paterno']) !== '' ? " {$entity['paterno']}" : "";
            $materno = isset($entity['materno']) && trim($entity['materno']) !== '' ? " {$entity['materno']}" : "";
            $response_data['name'] = trim("{$entity['nombre']}{$paterno}{$materno}");
            $response_data['empresa_nombre'] = $entity['empresa_nombre'];
            break;

        case 'personal_comision':
            $response_data['name'] = $entity['nombre_completo'];
            break;
    }

    // ========== REGISTRAR ACCESO ==========
    $message_to_log = $custom_message ?? "Acceso {$new_action} registrado via Portico.";

    $success = registrar_acceso(
        $entity['id'],
        $response_data['name'],
        $new_action,
        $message_to_log,
        $entity_type,
        $conn_acceso
    );

    if ($success) {
        $response_data['message'] = $custom_message ?? "Acceso '{$new_action}' registrado correctamente.";
        if ($entity_type === 'vehiculo') {
            // Ya tiene el mensaje
        } else {
            $response_data['message'] = $custom_message ?? "Acceso '{$new_action}' para {$response_data['name']} registrado correctamente.";
        }

        ApiResponse::created($response_data);
    } else {
        ApiResponse::serverError("Error al registrar el acceso");
    }
}

// ============================================================================
// EXECUTION
// ============================================================================

$conn_acceso = DatabaseConfig::getInstance()->getAccesoConnection();
$conn_personal = DatabaseConfig::getInstance()->getPersonalConnection();

try {
    handle_post($conn_acceso, $conn_personal);
} catch (Exception $e) {
    ApiResponse::serverError($e->getMessage());
} finally {
    $conn_acceso->close();
    $conn_personal->close();
}

<?php
// api/vehiculos.php
ini_set('display_errors', 1); // Temporalmente activado para debugging
error_reporting(E_ALL);
// Manejar todos los errores
set_error_handler(function($errno, $errstr, $errfile, $errline) {
    header('Content-Type: application/json');
    http_response_code(500);
    echo json_encode([
        'error' => 'Error PHP: ' . $errstr,
        'file' => $errfile,
        'line' => $errline
    ]);
    exit;
});

// Capturar errores fatales
register_shutdown_function(function() {
    $error = error_get_last();
    if ($error !== null && in_array($error['type'], [E_ERROR, E_PARSE, E_CORE_ERROR, E_COMPILE_ERROR])) {
        header('Content-Type: application/json');
        http_response_code(500);
        echo json_encode([
            'error' => 'Error Fatal: ' . $error['message'],
            'file' => $error['file'],
            'line' => $error['line']
        ]);
        exit;
    }
});

ini_set('display_errors', 0);
error_reporting(E_ALL);

// Asegurar que la salida sea siempre JSON
header('Content-Type: application/json');

require_once 'database/db_acceso.php'; // Conexión a la BD de acceso
require_once 'database/db_personal.php'; // Conexión a la BD de personal
// La línea require_once 'auth.php' fue eliminada para permitir solicitudes GET

// Iniciar sesión para tener acceso al usuario actual
session_start();

// Encabezados para permitir CORS y métodos HTTP
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *'); // Permitir cualquier origen
header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS'); // Permitir todos los métodos
header('Access-Control-Allow-Headers: Content-Type, Authorization'); // Permitir estos encabezados

// Si es una solicitud OPTIONS (preflight), devolver solo los headers y terminar
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}

// Verificar si el usuario está autenticado
if (!isset($_SESSION['logged_in']) || $_SESSION['logged_in'] !== true) {
    http_response_code(401);
    echo json_encode(['error' => 'No autorizado. Por favor, inicie sesión.']);
    exit;
}
$method = $_SERVER['REQUEST_METHOD'];

// Función para enviar errores
function send_error($code, $message) {
    http_response_code($code);
    echo json_encode(['message' => $message]);
    exit;
}

// Función para validar formato de patente chilena
function validar_patente_chilena($patente) {
    // Aseguramos que esté en mayúsculas
    $patente = strtoupper($patente);
    
    // Formato antiguo: dos letras y cuatro dígitos (AA1234)
    $formato_antiguo = '/^[A-Z]{2}[0-9]{4}$/';
    
    // Formato nuevo: cuatro letras y dos dígitos (ABCD12)
    $formato_nuevo = '/^[B-DF-HJ-NP-TV-Z]{4}[0-9]{2}$/';
    
    // Formato de motos nuevo: tres letras y dos números (ABC12)
    $formato_moto_nuevo = '/^[B-DF-HJ-NP-TV-Z]{3}[0-9]{2}$/';

    // Formato de motos antiguo: dos letras y tres números (AB123)
    $formato_moto_antiguo = '/^[A-Z]{2}[0-9]{3}$/';
    
    // Formato de remolques: tres letras y tres números (ABC123)
    $formato_remolque = '/^[A-Z]{3}[0-9]{3}$/';
    
    return preg_match($formato_antiguo, $patente) || 
           preg_match($formato_nuevo, $patente) || 
           preg_match($formato_moto_nuevo, $patente) ||
           preg_match($formato_moto_antiguo, $patente) ||
           preg_match($formato_remolque, $patente);
}

// Función para registrar cambios en el historial de vehículos
function registrar_historial_vehiculo($conn_acceso, $vehiculo_id, $patente, $asociado_id_anterior, $asociado_id_nuevo, $tipo_cambio, $detalles = null) {
    // Obtener el ID del usuario actual
    $usuario_id = null;
    if (isset($_SESSION['user_id'])) {
        $usuario_id = $_SESSION['user_id'];
    }
    
    $stmt = $conn_acceso->prepare("INSERT INTO vehiculo_historial 
        (vehiculo_id, patente, asociado_id_anterior, asociado_id_nuevo, fecha_cambio, usuario_id, tipo_cambio, detalles) 
        VALUES (?, ?, ?, ?, NOW(), ?, ?, ?)");
    
    if ($stmt) {
        // Corregir los tipos de parámetros: i=int, s=string
        // El tipo_cambio debe ser "s" (string) no "i" (integer)
        $stmt->bind_param("isiiiss", $vehiculo_id, $patente, $asociado_id_anterior, $asociado_id_nuevo, $usuario_id, $tipo_cambio, $detalles);
        $stmt->execute();
        $stmt->close();
        return true;
    }
    
    return false;
}

// Función para calcular el estado basado en la fecha
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
        if ($expiration_date >= $today) {
            return 'autorizado';
        } else {
            return 'no autorizado';
        }
    } catch (Exception $e) {
        return 'no autorizado';
    }
}

switch ($method) {
    case 'GET':
        try {
            // Consulta base para vehículos
            $sql = "SELECT 
                v.id, v.patente, v.marca, v.modelo, v.tipo, v.tipo_vehiculo,
                v.asociado_id, v.asociado_tipo, v.status, v.fecha_inicio, v.fecha_expiracion, v.acceso_permanente,
                CASE
                    WHEN v.tipo IN ('PERSONAL', 'FUNCIONARIO', 'RESIDENTE', 'FISCAL') THEN TRIM(CONCAT_WS(' ', p.Grado, p.Nombres, p.Paterno, p.Materno))
                    WHEN v.tipo IN ('EMPLEADO', 'EMPRESA') THEN TRIM(CONCAT_WS(' ', ee.nombre, ee.paterno, ee.materno))
                    WHEN v.tipo = 'VISITA' THEN vis.nombre
                    ELSE 'N/A'
                END as asociado_nombre,
                COALESCE(p.NrRut, ee.rut, vis.rut, '') as rut_asociado
            FROM vehiculos v
            LEFT JOIN personal_db.personal p ON v.asociado_id = p.id AND v.tipo IN ('PERSONAL', 'FUNCIONARIO', 'RESIDENTE', 'FISCAL')
            LEFT JOIN empresa_empleados ee ON v.asociado_id = ee.id AND v.tipo IN ('EMPLEADO', 'EMPRESA')
            LEFT JOIN visitas vis ON v.asociado_id = vis.id AND v.tipo = 'VISITA'
            ORDER BY v.id DESC";

            if (!($result = $conn_acceso->query($sql))) {
                throw new Exception($conn_acceso->error);
            }

            $vehiculos = [];
            while ($row = $result->fetch_assoc()) {
                // Asegurar que todos los campos requeridos existan y tengan el tipo correcto
                $vehiculo = [
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
                $vehiculos[] = $vehiculo;
            }

            // Devolver directamente el array de vehículos
            echo json_encode($vehiculos);
            exit;

        } catch (Exception $e) {
            send_error(500, 'Error al obtener vehículos: ' . $e->getMessage());
        }
        break;

    case 'POST':
        $data = json_decode(file_get_contents('php://input'), true);

        // Datos del vehículo (forzar mayúsculas en campos de texto)
        $patente = isset($data['patente']) ? strtoupper(trim($data['patente'])) : null;
        $marca = isset($data['marca']) ? strtoupper(trim($data['marca'])) : null;
        $modelo = isset($data['modelo']) ? strtoupper(trim($data['modelo'])) : null;
        $tipo = isset($data['tipo']) ? strtoupper(trim($data['tipo'])) : null;
        $tipo_vehiculo = isset($data['tipo_vehiculo']) ? strtoupper(trim($data['tipo_vehiculo'])) : 'AUTO';
        $personalNrRut = $data['personalNrRut'] ?? null;

        // Validar formato de patente
        if (!$patente) {
            send_error(400, 'La patente es obligatoria');
        }

        if (!validar_patente_chilena($patente)) {
            send_error(400, 'Formato de patente inválido. Debe ser formato chileno (AA1234 o ABCD12)');
        }

        // Validar fecha_inicio
        if (!isset($data['fecha_inicio']) || empty(trim($data['fecha_inicio'] ?? ''))) {
            send_error(400, 'Falta campo requerido: fecha de inicio.');
        }

        // Validar fecha_expiracion (solo si no es acceso permanente)
        $acceso_permanente = !empty($data['acceso_permanente']) ? 1 : 0;
        if (!$acceso_permanente && (!isset($data['fecha_expiracion']) || empty(trim($data['fecha_expiracion'] ?? '')))) {
            send_error(400, 'Falta campo requerido: fecha de expiración (o active acceso permanente).');
        }
        
        // Verificar si la patente ya existe
        $stmt_check = $conn_acceso->prepare("SELECT id FROM vehiculos WHERE patente = ? LIMIT 1");
        $stmt_check->bind_param("s", $patente);
        $stmt_check->execute();
        $result_check = $stmt_check->get_result();
        if ($result_check->num_rows > 0) {
            send_error(400, "Ya existe un vehículo registrado con la patente $patente");
        }
        $stmt_check->close();

        // Nuevos campos de autorización (ya validados arriba)
        // Si es acceso permanente, o si no se proporciona una fecha de expiración válida, establecer como null
        $fecha_expiracion_raw = $data['fecha_expiracion'] ?? null;
        // Validar que no sea cadena vacía, "null", "0000-00-00" o valores inválidos
        if ($acceso_permanente || empty($fecha_expiracion_raw) || $fecha_expiracion_raw === 'null' || $fecha_expiracion_raw === '0000-00-00') {
            $fecha_expiracion = null;
        } else {
            $fecha_expiracion = $fecha_expiracion_raw;
        }
        error_log("POST Acceso permanente: " . ($acceso_permanente ? "SI" : "NO"));
        error_log("POST Fecha expiración recibida: " . (isset($data['fecha_expiracion']) ? var_export($data['fecha_expiracion'], true) : "NO DEFINIDA"));
        error_log("POST Fecha expiración procesada: " . ($fecha_expiracion ? $fecha_expiracion : "NULL"));
        $status = get_status_by_date($acceso_permanente, $fecha_expiracion);

        // Obtener asociado_id - priorizar asociado_id si se proporciona directamente
        $asociado_id = null;
        if (isset($data['asociado_id']) && !empty($data['asociado_id'])) {
            $asociado_id = $data['asociado_id'];
        } elseif ($personalNrRut) {
            switch ($tipo) {
                case 'PERSONAL':
                case 'FUNCIONARIO':
                case 'RESIDENTE':
                case 'FISCAL':
                    $stmt = $conn_personal->prepare("SELECT id FROM personal WHERE NrRut = ?");
                    if (!$stmt) send_error(500, "Error preparando la consulta de personal: " . $conn_personal->error);
                    $stmt->bind_param("s", $personalNrRut);
                    $stmt->execute();
                    $result = $stmt->get_result();
                    $row = $result->fetch_assoc();
                    $stmt->close();
                    if ($row) {
                        $asociado_id = $row['id'];
                    } else {
                        send_error(404, "RUT de $tipo no encontrado.");
                    }
                    break;
                
                case 'EMPLEADO':
                case 'EMPRESA':
                    $stmt = $conn_acceso->prepare("SELECT id FROM empresa_empleados WHERE rut = ?");
                    if (!$stmt) send_error(500, "Error preparando la consulta de empleado: " . $conn_acceso->error);
                    $stmt->bind_param("s", $personalNrRut);
                    $stmt->execute();
                    $result = $stmt->get_result();
                    $row = $result->fetch_assoc();
                    $stmt->close();
                    if ($row) {
                        $asociado_id = $row['id'];
                    } else {
                        send_error(404, "RUT de empleado no encontrado.");
                    }
                    break;
                
                case 'VISITA':
                    $stmt = $conn_acceso->prepare("SELECT id FROM visitas WHERE rut = ?");
                    if (!$stmt) send_error(500, "Error preparando la consulta de visita: " . $conn_acceso->error);
                    $stmt->bind_param("s", $personalNrRut);
                    $stmt->execute();
                    $result = $stmt->get_result();
                    $row = $result->fetch_assoc();
                    $stmt->close();
                    if ($row) {
                        $asociado_id = $row['id'];
                    } else {
                        send_error(404, "RUT de visita no encontrado.");
                    }
                    break;
                
                default:
                    send_error(400, "Tipo de asociado no válido: $tipo");
            }
        }

        $stmt = $conn_acceso->prepare("INSERT INTO vehiculos (patente, marca, modelo, tipo, tipo_vehiculo, asociado_id, asociado_tipo, status, fecha_inicio, fecha_expiracion, acceso_permanente) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
        if (!$stmt) send_error(500, "Error preparando la inserción de vehículo: " . $conn_acceso->error);
        $fecha_inicio = !empty($data['fecha_inicio']) ? $data['fecha_inicio'] : date('Y-m-d'); // Usar la fecha actual si no se proporciona
        
        // Determinar el tipo de asociado basado en los datos proporcionados
        $asociado_tipo = $tipo; // Mantener el tipo proporcionado (PERSONAL, EMPLEADO o EMPRESA)

        // Corregir los tipos de parámetros para coincidirlos con la estructura de la base de datos
        // s=string (patente, marca, modelo, tipo, tipo_vehiculo, status, fecha_inicio, fecha_expiracion), i=integer (asociado_id, acceso_permanente)
        // Total: 11 parámetros = sssssissssi
        $stmt->bind_param("sssssissssi", $patente, $marca, $modelo, $tipo, $tipo_vehiculo, $asociado_id, $asociado_tipo, $status, $fecha_inicio, $fecha_expiracion, $acceso_permanente);
        $stmt->execute();
        $newId = $stmt->insert_id;
        $stmt->close();
        
        // Registrar en historial
        $detalles = json_encode([
            'marca' => $marca,
            'modelo' => $modelo,
            'tipo' => $tipo,
            'tipo_vehiculo' => $tipo_vehiculo,
            'acceso_permanente' => $acceso_permanente,
            'fecha_expiracion' => $fecha_expiracion
        ]);
        registrar_historial_vehiculo(
            $conn_acceso,
            $newId,
            $patente,
            null, // No hay propietario anterior en la creación
            $asociado_id,
            'creacion',
            $detalles
        );

        // Obtener datos completos del vehículo recién creado
        $stmt_new = $conn_acceso->prepare("
            SELECT
                v.id, v.patente, v.marca, v.modelo, v.tipo, v.tipo_vehiculo,
                v.asociado_id, v.asociado_tipo, v.status, v.fecha_inicio, v.fecha_expiracion, v.acceso_permanente,
                CASE
                    WHEN v.tipo IN ('PERSONAL', 'FUNCIONARIO', 'RESIDENTE', 'FISCAL') THEN TRIM(CONCAT_WS(' ', p.Grado, p.Nombres, p.Paterno, p.Materno))
                    WHEN v.tipo IN ('EMPLEADO', 'EMPRESA') THEN TRIM(CONCAT_WS(' ', ee.nombre, ee.paterno, ee.materno))
                    WHEN v.tipo = 'VISITA' THEN vis.nombre
                    ELSE 'N/A'
                END as asociado_nombre,
                COALESCE(p.NrRut, ee.rut, vis.rut, '') as rut_asociado
            FROM vehiculos v
            LEFT JOIN personal_db.personal p ON v.asociado_id = p.id AND v.tipo IN ('PERSONAL', 'FUNCIONARIO', 'RESIDENTE', 'FISCAL')
            LEFT JOIN empresa_empleados ee ON v.asociado_id = ee.id AND v.tipo IN ('EMPLEADO', 'EMPRESA')
            LEFT JOIN visitas vis ON v.asociado_id = vis.id AND v.tipo = 'VISITA'
            WHERE v.id = ?
        ");

        if ($stmt_new) {
            $stmt_new->bind_param("i", $newId);
            $stmt_new->execute();
            $result_new = $stmt_new->get_result();
            $vehiculo_creado = $result_new->fetch_assoc();
            $stmt_new->close();

            // Asegurar que todos los campos requeridos existan y tengan el tipo correcto
            if ($vehiculo_creado) {
                $vehiculo_creado = [
                    'id' => (int)$vehiculo_creado['id'],
                    'patente' => $vehiculo_creado['patente'] ?? '',
                    'marca' => $vehiculo_creado['marca'] ?? '',
                    'modelo' => $vehiculo_creado['modelo'] ?? '',
                    'tipo' => $vehiculo_creado['tipo'] ?? '',
                    'tipo_vehiculo' => $vehiculo_creado['tipo_vehiculo'] ?? '',
                    'asociado_id' => $vehiculo_creado['asociado_id'] ? (int)$vehiculo_creado['asociado_id'] : null,
                    'asociado_tipo' => $vehiculo_creado['asociado_tipo'] ?? '',
                    'status' => $vehiculo_creado['status'] ?? 'no autorizado',
                    'fecha_inicio' => $vehiculo_creado['fecha_inicio'] ?? null,
                    'fecha_expiracion' => $vehiculo_creado['fecha_expiracion'] ?? null,
                    'acceso_permanente' => (bool)($vehiculo_creado['acceso_permanente'] ?? false),
                    'asociado_nombre' => trim($vehiculo_creado['asociado_nombre'] ?? 'N/A'),
                    'rut_asociado' => $vehiculo_creado['rut_asociado'] ?? ''
                ];

                http_response_code(201);
                echo json_encode($vehiculo_creado);
            } else {
                // Fallback si la consulta no retorna datos
                http_response_code(201);
                echo json_encode(['id' => $newId, 'status' => $status, 'acceso_permanente' => (bool)$acceso_permanente]);
            }
        } else {
            // Fallback si la preparación de la consulta falla
            http_response_code(201);
            echo json_encode(['id' => $newId, 'status' => $status, 'acceso_permanente' => (bool)$acceso_permanente]);
        }
        break;

    case 'PUT':
        $data = json_decode(file_get_contents('php://input'), true);
        $id = $data['id'] ?? null;
        if (!$id) send_error(400, 'ID de vehículo no proporcionado para la actualización.');

    // Datos del vehículo (forzar mayúsculas en campos de texto)
    $patente = isset($data['patente']) ? strtoupper(trim($data['patente'])) : null;
    $marca = isset($data['marca']) ? strtoupper(trim($data['marca'])) : null;
    $modelo = isset($data['modelo']) ? strtoupper(trim($data['modelo'])) : null;
    $tipo = isset($data['tipo']) ? strtoupper(trim($data['tipo'])) : null;  // Tipo de Acceso
    $tipo_vehiculo = isset($data['tipo_vehiculo']) ? strtoupper(trim($data['tipo_vehiculo'])) : 'AUTO';  // Tipo de Vehículo
        $personalNrRut = $data['personalNrRut'] ?? null;
        
        // Validar formato de patente
        if (!$patente) {
            send_error(400, 'La patente es obligatoria');
        }

        if (!validar_patente_chilena($patente)) {
            send_error(400, 'Formato de patente inválido. Debe ser formato chileno (AA1234 o ABCD12)');
        }

        // Validar fecha_inicio
        if (!isset($data['fecha_inicio']) || empty(trim($data['fecha_inicio'] ?? ''))) {
            send_error(400, 'Falta campo requerido: fecha de inicio.');
        }

        // Validar fecha_expiracion (solo si no es acceso permanente)
        $acceso_permanente = !empty($data['acceso_permanente']) ? 1 : 0;
        if (!$acceso_permanente && (!isset($data['fecha_expiracion']) || empty(trim($data['fecha_expiracion'] ?? '')))) {
            send_error(400, 'Falta campo requerido: fecha de expiración (o active acceso permanente).');
        }

        // Verificar si la patente ya existe (excepto para el mismo vehículo)
        $stmt_check = $conn_acceso->prepare("SELECT id FROM vehiculos WHERE patente = ? AND id != ? LIMIT 1");
        $stmt_check->bind_param("si", $patente, $id);
        $stmt_check->execute();
        $result_check = $stmt_check->get_result();
        if ($result_check->num_rows > 0) {
            send_error(400, "Ya existe otro vehículo registrado con la patente $patente");
        }
        $stmt_check->close();

        // Obtener el propietario anterior para comparar con el nuevo
        $stmt_anterior = $conn_acceso->prepare("SELECT asociado_id FROM vehiculos WHERE id = ?");
        $stmt_anterior->bind_param("i", $id);
        $stmt_anterior->execute();
        $result_anterior = $stmt_anterior->get_result();
        $vehiculo_anterior = $result_anterior->fetch_assoc();
        $personalId_anterior = $vehiculo_anterior ? $vehiculo_anterior['asociado_id'] : null;
        $stmt_anterior->close();

        // Nuevos campos de autorización (ya validados arriba)
        // Si es acceso permanente, o si no se proporciona una fecha de expiración válida, establecer como null
        $fecha_expiracion_raw = $data['fecha_expiracion'] ?? null;
        // Validar que no sea cadena vacía, "null", "0000-00-00" o valores inválidos
        if ($acceso_permanente || empty($fecha_expiracion_raw) || $fecha_expiracion_raw === 'null' || $fecha_expiracion_raw === '0000-00-00') {
            $fecha_expiracion = null;
        } else {
            $fecha_expiracion = $fecha_expiracion_raw;
        }
        error_log("PUT Acceso permanente: " . ($acceso_permanente ? "SI" : "NO"));
        error_log("PUT Fecha expiración recibida: " . (isset($data['fecha_expiracion']) ? var_export($data['fecha_expiracion'], true) : "NO DEFINIDA"));
        error_log("PUT Fecha expiración procesada: " . ($fecha_expiracion ? $fecha_expiracion : "NULL"));
        $status = get_status_by_date($acceso_permanente, $fecha_expiracion);

        // Obtener asociado_id - priorizar asociado_id si se proporciona directamente
        $asociado_id = null;
        if (isset($data['asociado_id']) && !empty($data['asociado_id'])) {
            $asociado_id = $data['asociado_id'];
        } elseif ($personalNrRut) {
            switch ($tipo) {
                case 'PERSONAL':
                case 'FUNCIONARIO':
                case 'RESIDENTE':
                case 'FISCAL':
                    $stmt = $conn_personal->prepare("SELECT id FROM personal WHERE NrRut = ?");
                    if (!$stmt) send_error(500, "Error preparando la consulta de personal: " . $conn_personal->error);
                    $stmt->bind_param("s", $personalNrRut);
                    $stmt->execute();
                    $result = $stmt->get_result();
                    $row = $result->fetch_assoc();
                    $stmt->close();
                    if ($row) {
                        $asociado_id = $row['id'];
                    } else {
                        send_error(404, "RUT de $tipo no encontrado.");
                    }
                    break;
                
                case 'EMPLEADO':
                case 'EMPRESA':
                    $stmt = $conn_acceso->prepare("SELECT id FROM empresa_empleados WHERE rut = ?");
                    if (!$stmt) send_error(500, "Error preparando la consulta de empleado: " . $conn_acceso->error);
                    $stmt->bind_param("s", $personalNrRut);
                    $stmt->execute();
                    $result = $stmt->get_result();
                    $row = $result->fetch_assoc();
                    $stmt->close();
                    if ($row) {
                        $asociado_id = $row['id'];
                    } else {
                        send_error(404, "RUT de empleado no encontrado.");
                    }
                    break;
                
                case 'VISITA':
                    $stmt = $conn_acceso->prepare("SELECT id FROM visitas WHERE rut = ?");
                    if (!$stmt) send_error(500, "Error preparando la consulta de visita: " . $conn_acceso->error);
                    $stmt->bind_param("s", $personalNrRut);
                    $stmt->execute();
                    $result = $stmt->get_result();
                    $row = $result->fetch_assoc();
                    $stmt->close();
                    if ($row) {
                        $asociado_id = $row['id'];
                    } else {
                        send_error(404, "RUT de visita no encontrado.");
                    }
                    break;
                
                default:
                    send_error(400, "Tipo de asociado no válido: $tipo");
            }
        }

        $stmt = $conn_acceso->prepare("UPDATE vehiculos SET patente=?, marca=?, modelo=?, tipo=?, tipo_vehiculo=?, asociado_id=?, asociado_tipo=?, status=?, fecha_inicio=?, fecha_expiracion=?, acceso_permanente=? WHERE id=?");
        if (!$stmt) send_error(500, "Error preparando la actualización de vehículo: " . $conn_acceso->error);
        $fecha_inicio = !empty($data['fecha_inicio']) ? $data['fecha_inicio'] : null;
        
        // Determinar el tipo de asociado basado en el tipo de vehículo
        $asociado_tipo = $tipo; // Por defecto, usar el tipo del vehículo
        
        // Corregir los tipos de parámetros para coincidirlos con la estructura de la base de datos
        // s=patente, s=marca, s=modelo, s=tipo, s=tipo_vehiculo, i=asociado_id, s=asociado_tipo, s=status, s=fecha_inicio, s=fecha_expiracion, i=acceso_permanente, i=id
        // Total: 12 parámetros = sssssissssii
        $stmt->bind_param("sssssissssii", $patente, $marca, $modelo, $tipo, $tipo_vehiculo, $asociado_id, $asociado_tipo, $status, $fecha_inicio, $fecha_expiracion, $acceso_permanente, $id);
        $stmt->execute();
        $stmt->close();
        
        // Registrar en historial: solo marcar 'cambio_propietario' si realmente cambió el propietario
        if (!empty($personalId_anterior) && !empty($asociado_id) && $asociado_id != $personalId_anterior) {
            $tipo_cambio = 'cambio_propietario';
        } else {
            $tipo_cambio = 'actualizacion';
        }
        
        $detalles = json_encode([
            'marca' => $marca,
            'modelo' => $modelo,
            'tipo' => $tipo,
            'tipo_vehiculo' => $tipo_vehiculo,
            'acceso_permanente' => $acceso_permanente,
            'fecha_expiracion' => $fecha_expiracion
        ]);
        
        registrar_historial_vehiculo(
            $conn_acceso,
            $id,
            $patente,
            $personalId_anterior,
            $asociado_id,
            $tipo_cambio,
            $detalles
        );

        // Después de actualizar, obtener el nombre del asociado para devolverlo en la respuesta
        if ($asociado_id) {
            if ($tipo == 'PERSONAL' || $tipo == 'FUNCIONARIO' || $tipo == 'RESIDENTE' || $tipo == 'FISCAL') {
                $stmt_personal = $conn_personal->prepare("SELECT NrRut, Grado, Nombres, Paterno, Materno FROM personal WHERE id = ?");
                if ($stmt_personal) {
                    $stmt_personal->bind_param("i", $asociado_id);
                    $stmt_personal->execute();
                    $result_personal = $stmt_personal->get_result();
                    $person = $result_personal->fetch_assoc();
                    $stmt_personal->close();
                    if ($person) {
                        $data['rut_asociado'] = $person['NrRut'];
                        $apellidoMaterno = isset($person['Materno']) && trim($person['Materno']) !== '' ? " {$person['Materno']}" : "";
                        $data['asociado_nombre'] = trim(($person['Grado'] ?? '') . ' ' . ($person['Nombres'] ?? '') . ' ' . ($person['Paterno'] ?? '') . $apellidoMaterno);
                    }
                }
            } else if ($tipo == 'EMPRESA' || $tipo == 'EMPLEADO') {
                $stmt_empleado = $conn_acceso->prepare("SELECT nombre, paterno, materno, rut FROM empresa_empleados WHERE id = ?");
                if ($stmt_empleado) {
                    $stmt_empleado->bind_param("i", $asociado_id);
                    $stmt_empleado->execute();
                    $result_empleado = $stmt_empleado->get_result();
                    $empleado = $result_empleado->fetch_assoc();
                    $stmt_empleado->close();
                    if ($empleado) {
                        $data['rut_asociado'] = $empleado['rut'];
                        $apellidoMaterno = isset($empleado['materno']) && trim($empleado['materno']) !== '' ? " {$empleado['materno']}" : "";
                        $data['asociado_nombre'] = trim($empleado['nombre'] . ' ' . $empleado['paterno'] . $apellidoMaterno);
                    }
                }
            } else if ($tipo == 'VISITA') {
                $stmt_visita = $conn_acceso->prepare("SELECT nombre, rut FROM visitas WHERE id = ?");
                if ($stmt_visita) {
                    $stmt_visita->bind_param("i", $asociado_id);
                    $stmt_visita->execute();
                    $result_visita = $stmt_visita->get_result();
                    $visita = $result_visita->fetch_assoc();
                    $stmt_visita->close();
                    if ($visita) {
                        $data['rut_asociado'] = $visita['rut'];
                        $data['asociado_nombre'] = trim($visita['nombre']);
                    }
                }
            }

            if (!isset($data['asociado_nombre'])) {
                $data['rut_asociado'] = $personalNrRut ?? null; // Devuelve el RUT que se intentó guardar
                $data['asociado_nombre'] = 'Asociado no encontrado';
            }
        } else {
            $data['rut_asociado'] = null;
            $data['asociado_nombre'] = 'N/A';
        }

        $data['status'] = $status;
        $data['acceso_permanente'] = (bool)$acceso_permanente;
        echo json_encode($data);
        break;

    case 'DELETE':
        $id = $_GET['id'];
        
        // Obtener datos del vehículo antes de eliminarlo para el historial
        $stmt_get = $conn_acceso->prepare("SELECT patente, asociado_id FROM vehiculos WHERE id=?");
        $stmt_get->bind_param("i", $id);
        $stmt_get->execute();
        $result_get = $stmt_get->get_result();
        $vehiculo = $result_get->fetch_assoc();
        $stmt_get->close();
        
        if (!$vehiculo) {
            http_response_code(404);
            echo json_encode(['message' => 'Vehículo no encontrado']);
            break;
        }
        
        // Registrar en historial antes de eliminar
        registrar_historial_vehiculo(
            $conn_acceso,
            $id,
            $vehiculo['patente'],
            $vehiculo['asociado_id'],
            null,
            'eliminacion',
            json_encode(['fecha_eliminacion' => date('Y-m-d H:i:s')])
        );
        
        // Ahora eliminamos el vehículo
        $stmt = $conn_acceso->prepare("DELETE FROM vehiculos WHERE id=?");
        $stmt->bind_param("i", $id);
        $stmt->execute();
        if ($stmt->affected_rows > 0) {
            http_response_code(204);
        } else {
            http_response_code(404);
            echo json_encode(['message' => 'Error al eliminar el vehículo']);
        }
        $stmt->close();
        break;

    default:
        http_response_code(405);
        echo json_encode(['message' => 'Método no permitido']);
        break;
}

$conn_acceso->close();
?>
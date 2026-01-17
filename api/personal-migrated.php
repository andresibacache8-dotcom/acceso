<?php
/**
 * api/personal-migrated.php
 * API de Gestión de Personal (Refactorizado)
 *
 * Cambios en esta versión:
 * - Usa config/database.php (centralizado) en lugar de database/db_personal.php
 * - Usa api/core/ResponseHandler.php para respuestas estandarizadas
 * - Implementa paginación para consultas GET
 * - Refactoriza con funciones separadas para GET, POST, PUT, DELETE
 * - Mantiene la lógica de negocio idéntica (importación masiva, búsqueda, etc.)
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
header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization');

// Manejar preflight OPTIONS
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}

// Obtener conexión a base de datos personal
$databaseConfig = DatabaseConfig::getInstance();
$conn = $databaseConfig->getPersonalConnection();

if (!$conn) {
    ApiResponse::serverError('Error de conexión a la base de datos personal');
}

// También obtener conexión a acceso para el endpoint status=inside
$connAcceso = $databaseConfig->getAccesoConnection();

$method = $_SERVER['REQUEST_METHOD'];

try {
    switch ($method) {
        case 'GET':
            handleGet($conn, $connAcceso);
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
    error_log('Error en personal-migrated.php: ' . $e->getMessage());
    ApiResponse::serverError('Error procesando la solicitud: ' . $e->getMessage());
}

/**
 * Manejar GET - Obtener personal con búsqueda y paginación
 */
function handleGet($conn, $connAcceso) {
    try {
        // Búsqueda por texto (nombre o RUT)
        if (isset($_GET['search'])) {
            $query = trim($_GET['search']);
            $search = "%{$query}%";

            $stmt = $conn->prepare(
                "SELECT id, Grado, Nombres, Paterno, Materno, NrRut, Unidad, anexo FROM personal
                 WHERE Nombres LIKE ? OR Paterno LIKE ? OR Materno LIKE ? OR NrRut LIKE ?
                 ORDER BY Paterno, Nombres
                 LIMIT 10"
            );

            if (!$stmt) {
                throw new Exception("Error al preparar la consulta: " . $conn->error);
            }

            $stmt->bind_param("ssss", $search, $search, $search, $search);
            $stmt->execute();
            $result = $stmt->get_result();

            $data = [];
            while ($row = $result->fetch_assoc()) {
                $row['Materno'] = $row['Materno'] ?? '';
                $data[] = $row;
            }
            $stmt->close();

            ApiResponse::success($data);
        }
        // Búsqueda por RUT
        elseif (isset($_GET['rut'])) {
            $rut = $_GET['rut'];
            $stmt = $conn->prepare("SELECT id, Grado, Nombres, Paterno, Materno, NrRut, Unidad, anexo FROM personal WHERE NrRut = ?");

            if (!$stmt) {
                throw new Exception("Error al preparar la consulta: " . $conn->error);
            }

            $stmt->bind_param("s", $rut);
            $stmt->execute();
            $result = $stmt->get_result();
            $data = $result->fetch_assoc();
            $stmt->close();

            if ($data === null || $data === false) {
                $data = [];
            } else {
                $data['Materno'] = $data['Materno'] ?? '';
            }

            ApiResponse::success($data);
        }
        // Búsqueda por ID
        elseif (isset($_GET['id'])) {
            $id = intval($_GET['id']);
            $stmt = $conn->prepare("SELECT * FROM personal WHERE id = ?");
            $stmt->bind_param("i", $id);
            $stmt->execute();
            $result = $stmt->get_result();
            $data = $result->fetch_assoc();
            $stmt->close();

            if (!$data) {
                ApiResponse::notFound('Personal no encontrado');
            }

            ApiResponse::success($data);
        }
        // Personal que está "dentro" (status=inside)
        elseif (isset($_GET['status']) && $_GET['status'] == 'inside') {
            $sql_inside = "
                SELECT a.target_id
                FROM access_logs a
                INNER JOIN (
                    SELECT target_id, MAX(id) as max_id
                    FROM access_logs
                    WHERE target_type = 'personal'
                    GROUP BY target_id
                ) b ON a.target_id = b.target_id AND a.id = b.max_id
                WHERE a.action = 'entrada'
            ";
            $result_inside = $connAcceso->query($sql_inside);
            $inside_ids = [];
            while ($row = $result_inside->fetch_assoc()) {
                $inside_ids[] = $row['target_id'];
            }

            $data = [];
            if (!empty($inside_ids)) {
                $id_list = implode(',', array_map('intval', $inside_ids));
                $sql_personal = "SELECT * FROM personal WHERE id IN ($id_list) ORDER BY Paterno, Nombres";
                $result_personal = $conn->query($sql_personal);
                while ($row = $result_personal->fetch_assoc()) {
                    $data[] = $row;
                }
            }

            ApiResponse::success($data);
        }
        // Obtener todo el personal con paginación (por defecto)
        else {
            // Parámetros de paginación
            $page = isset($_GET['page']) ? max(1, (int)$_GET['page']) : 1;
            $perPage = isset($_GET['perPage']) ? max(1, min(500, (int)$_GET['perPage'])) : 50;
            $offset = ($page - 1) * $perPage;

            // Contar total de registros
            $countResult = $conn->query("SELECT COUNT(*) as total FROM personal");
            if (!$countResult) {
                throw new Exception("Error al contar registros: " . $conn->error);
            }

            $countRow = $countResult->fetch_assoc();
            $total = (int)$countRow['total'];

            // Obtener registros con paginación
            $query = "SELECT * FROM personal ORDER BY Paterno, Nombres LIMIT {$perPage} OFFSET {$offset}";
            $result = $conn->query($query);

            if (!$result) {
                throw new Exception("Error al obtener registros: " . $conn->error);
            }

            $data = [];
            while ($row = $result->fetch_assoc()) {
                $data[] = $row;
            }

            // Retornar con paginación
            ApiResponse::paginated($data, $page, $perPage, $total);
        }

    } catch (Exception $e) {
        throw $e;
    }
}

/**
 * Manejar POST - Crear personal o importación masiva
 */
function handlePost($conn) {
    try {
        $data = json_decode(file_get_contents('php://input'), true);

        // Importación masiva
        if (isset($_GET['action']) && $_GET['action'] === 'import') {
            handleImportMasivo($conn, $data);
        }
        // Crear un único personal
        else {
            handleCreatePersonal($conn, $data);
        }

    } catch (Exception $e) {
        throw $e;
    }
}

/**
 * Manejar importación masiva de personal
 */
function handleImportMasivo($conn, $data) {
    $personal_data = $data['personal'] ?? [];

    $results = [
        'success' => [],
        'errors' => [],
        'total' => count($personal_data),
        'processed' => 0,
        'created' => 0,
        'updated' => 0
    ];

    // Iniciar transacción
    $conn->begin_transaction();

    try {
        foreach ($personal_data as $index => $person) {
            $row_number = $index + 2;

            // Validar campos requeridos
            if (empty($person['Nombres']) || empty($person['Paterno']) || empty($person['NrRut'])) {
                $results['errors'][] = [
                    'row' => $row_number,
                    'message' => 'Faltan campos requeridos: Nombres, Paterno o NrRut'
                ];
                continue;
            }

            // Limpiar y normalizar datos
            $nombres = strtoupper(trim($person['Nombres']));
            $paterno = strtoupper(trim($person['Paterno']));
            $materno = strtoupper(trim($person['Materno'] ?? ''));
            $nrRut = strtoupper(trim($person['NrRut']));
            $grado = strtoupper(trim($person['Grado'] ?? ''));
            $unidad = strtoupper(trim($person['Unidad'] ?? ''));
            $estado = isset($person['Estado']) ? intval($person['Estado']) : 1;
            $es_residente = isset($person['es_residente']) ? (int)$person['es_residente'] : 0;

            // Validar formato RUT (básico)
            if (!preg_match('/^[0-9]{1,10}-[0-9Kk]{1}$/', $nrRut) && !preg_match('/^[0-9]{7,10}$/', $nrRut)) {
                $results['errors'][] = [
                    'row' => $row_number,
                    'message' => 'RUT inválido: ' . $nrRut . '. Formato: 12345678-9 o 123456789'
                ];
                continue;
            }

            // Verificar si el personal ya existe por RUT
            $check_stmt = $conn->prepare("SELECT id FROM personal WHERE NrRut = ?");
            $check_stmt->bind_param("s", $nrRut);
            $check_stmt->execute();
            $existing = $check_stmt->get_result()->fetch_assoc();
            $check_stmt->close();

            if ($existing) {
                // Actualizar personal existente
                $update_result = updatePersonalFields($conn, $person, $nrRut);

                if ($update_result['success']) {
                    $results['success'][] = [
                        'row' => $row_number,
                        'rut' => $nrRut,
                        'action' => 'actualizado'
                    ];
                    $results['updated']++;
                } else {
                    $results['errors'][] = [
                        'row' => $row_number,
                        'message' => $update_result['error']
                    ];
                }
            } else {
                // Crear nuevo personal
                $insert_stmt = $conn->prepare(
                    "INSERT INTO personal (Grado, Nombres, Paterno, Materno, NrRut, Unidad, Estado, es_residente)
                     VALUES (?, ?, ?, ?, ?, ?, ?, ?)"
                );
                $insert_stmt->bind_param("ssssssii", $grado, $nombres, $paterno, $materno, $nrRut, $unidad, $estado, $es_residente);

                if ($insert_stmt->execute()) {
                    $results['success'][] = [
                        'row' => $row_number,
                        'rut' => $nrRut,
                        'action' => 'creado'
                    ];
                    $results['created']++;
                } else {
                    $results['errors'][] = [
                        'row' => $row_number,
                        'message' => 'Error al insertar: ' . $insert_stmt->error
                    ];
                }
                $insert_stmt->close();
            }

            $results['processed']++;
        }

        // Confirmar transacción
        $conn->commit();

        ApiResponse::success(
            $results,
            200,
            [
                'records_created' => $results['created'],
                'records_updated' => $results['updated'],
                'errors' => count($results['errors'])
            ]
        );

    } catch (Exception $e) {
        if ($conn->in_transaction) {
            $conn->rollback();
        }
        throw $e;
    }
}

/**
 * Actualizar campos de personal dinámicamente
 */
function updatePersonalFields($conn, $person, $nrRut) {
    $allowed_fields = array(
        'Grado' => 's', 'Nombres' => 's', 'Paterno' => 's', 'Materno' => 's',
        'fechaNacimiento' => 's', 'sexo' => 's', 'estadoCivil' => 's',
        'nrEmpleado' => 's', 'puesto' => 's', 'especialidadPrimaria' => 's',
        'fechaIngreso' => 's', 'fechaPresentacion' => 's', 'Unidad' => 's',
        'unidadEspecifica' => 's', 'categoria' => 's', 'escalafon' => 's',
        'trabajoExterno' => 's', 'calle' => 's', 'numeroDepto' => 's',
        'poblacionVilla' => 's', 'telefonoFijo' => 's', 'movil1' => 's',
        'movil2' => 's', 'email1' => 's', 'email2' => 's', 'anexo' => 's',
        'foto' => 's', 'prevision' => 's', 'sistemaSalud' => 's',
        'regimenMatrimonial' => 's', 'religion' => 's', 'tipoVivienda' => 's',
        'nombreConyuge' => 's', 'profesionConyuge' => 's',
        'nombreContactoEmergencia' => 's', 'direccionEmergencia' => 's',
        'movilEmergencia' => 's', 'Estado' => 'i', 'fechaExpiracion' => 's',
        'accesoPermanente' => 'i', 'es_residente' => 'i'
    );

    $set_parts = array();
    $update_fields = array();
    $bind_types = "";

    foreach ($allowed_fields as $field => $type) {
        if (isset($person[$field]) && $person[$field] !== '') {
            $value = $person[$field];

            // Normalizar valores según tipo
            if ($type === 's') {
                if (in_array($field, array('email1', 'email2'))) {
                    $value = strtolower(trim($value));
                } elseif (!in_array($field, array('foto', 'fechaNacimiento', 'fechaIngreso', 'fechaPresentacion', 'fechaExpiracion'))) {
                    $value = strtoupper(trim($value));
                } else {
                    $value = trim($value);
                }
            } elseif ($type === 'i') {
                $value = (int)$value;
            }

            $set_parts[] = "$field=?";
            $update_fields[] = $value;
            $bind_types .= $type;
        }
    }

    // Si no hay campos para actualizar, retornar éxito sin cambios
    if (empty($set_parts)) {
        return ['success' => true];
    }

    // Construir SQL final
    $update_sql = "UPDATE personal SET " . implode(", ", $set_parts) . " WHERE NrRut=?";
    $update_fields[] = $nrRut;
    $bind_types .= "s";

    $update_stmt = $conn->prepare($update_sql);

    if ($update_stmt === false) {
        return ['success' => false, 'error' => 'Error al preparar UPDATE: ' . $conn->error];
    }

    // Usar call_user_func_array para bind_param dinámico
    $params = array(&$bind_types);
    foreach ($update_fields as &$param) {
        $params[] = &$param;
    }
    call_user_func_array(array($update_stmt, 'bind_param'), $params);

    if ($update_stmt->execute()) {
        $update_stmt->close();
        return ['success' => true];
    } else {
        $error = $update_stmt->error;
        $update_stmt->close();
        return ['success' => false, 'error' => 'Error al actualizar: ' . $error];
    }
}

/**
 * Crear un único personal
 */
function handleCreatePersonal($conn, $data) {
    $es_residente = isset($data['es_residente']) && $data['es_residente'] == '1' ? 1 : 0;

    $stmt = $conn->prepare(
        "INSERT INTO personal (Nombres, Paterno, Materno, NrRut, Grado, Unidad, Estado, es_residente)
         VALUES (?, ?, ?, ?, ?, ?, ?, ?)"
    );

    $stmt->bind_param(
        "ssssssii",
        $data['Nombres'],
        $data['Paterno'],
        $data['Materno'],
        $data['NrRut'],
        $data['Grado'],
        $data['Unidad'],
        $data['Estado'],
        $es_residente
    );

    if (!$stmt->execute()) {
        throw new Exception("Error al insertar: " . $stmt->error);
    }

    $data['id'] = $stmt->insert_id;
    $stmt->close();

    ApiResponse::created($data, ['id' => $data['id']]);
}

/**
 * Manejar PUT - Actualizar personal
 */
function handlePut($conn) {
    try {
        $data = json_decode(file_get_contents('php://input'), true);
        $id = intval($data['id'] ?? 0);

        if (!$id) {
            ApiResponse::badRequest('ID de personal requerido');
        }

        // Mapeo de datos
        $fields = [
            'Grado' => 's', 'Nombres' => 's', 'Paterno' => 's', 'Materno' => 's',
            'NrRut' => 's', 'fechaNacimiento' => 's', 'sexo' => 's', 'estadoCivil' => 's',
            'nrEmpleado' => 's', 'puesto' => 's', 'especialidadPrimaria' => 's',
            'fechaIngreso' => 's', 'fechaPresentacion' => 's', 'Unidad' => 's',
            'unidadEspecifica' => 's', 'categoria' => 's', 'escalafon' => 's',
            'trabajoExterno' => 's', 'calle' => 's', 'numeroDepto' => 's',
            'poblacionVilla' => 's', 'telefonoFijo' => 's', 'movil1' => 's',
            'movil2' => 's', 'email1' => 's', 'email2' => 's', 'anexo' => 's',
            'foto' => 's', 'prevision' => 's', 'sistemaSalud' => 's',
            'regimenMatrimonial' => 's', 'religion' => 's', 'tipoVivienda' => 's',
            'nombreConyuge' => 's', 'profesionConyuge' => 's',
            'nombreContactoEmergencia' => 's', 'direccionEmergencia' => 's',
            'movilEmergencia' => 's', 'Estado' => 'i', 'fechaExpiracion' => 's',
            'accesoPermanente' => 'i', 'es_residente' => 'i'
        ];

        // Preparar valores
        $values = [];
        $types = "";

        foreach ($fields as $field => $type) {
            if (isset($data[$field])) {
                $values[$field] = $data[$field];
                $types .= $type;
            }
        }

        // Crear SQL dinámicamente
        $set_parts = array_keys($values);
        $set_parts = array_map(function($f) { return "`$f`=?"; }, $set_parts);
        $sql = "UPDATE personal SET " . implode(", ", $set_parts) . " WHERE id=?";

        $stmt = $conn->prepare($sql);
        if (!$stmt) {
            throw new Exception("Error al preparar UPDATE: " . $conn->error);
        }

        // Bind dinámico
        $values_array = array_values($values);
        $values_array[] = $id;
        $types .= "i";

        $params = array(&$types);
        foreach ($values_array as &$val) {
            $params[] = &$val;
        }
        call_user_func_array(array($stmt, 'bind_param'), $params);

        if (!$stmt->execute()) {
            throw new Exception("Error al actualizar: " . $stmt->error);
        }

        $stmt->close();

        ApiResponse::success($data);

    } catch (Exception $e) {
        throw $e;
    }
}

/**
 * Manejar DELETE - Eliminar personal
 */
function handleDelete($conn) {
    try {
        $id = isset($_GET['id']) ? intval($_GET['id']) : null;

        if (!$id) {
            ApiResponse::badRequest('ID de personal no especificado');
        }

        $stmt = $conn->prepare("DELETE FROM personal WHERE id = ?");
        $stmt->bind_param("i", $id);

        if (!$stmt->execute()) {
            throw new Exception("Error al eliminar: " . $stmt->error);
        }

        if ($stmt->affected_rows > 0) {
            ApiResponse::noContent();
        } else {
            ApiResponse::notFound('Personal no encontrado');
        }

        $stmt->close();

    } catch (Exception $e) {
        throw $e;
    }
}
?>

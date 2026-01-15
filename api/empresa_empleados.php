<?php
// api/empresa_empleados.php

// Configurar error handling ANTES de cualquier otra cosa
ini_set('display_errors', 1);
error_reporting(E_ALL);

// Manejar errores de PHP
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

require_once 'database/db_acceso.php';

// Iniciar sesión para tener acceso al usuario actual
session_start();

// Encabezados para permitir CORS y métodos HTTP
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS');
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

$method = $_SERVER['REQUEST_METHOD'];
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
        try {
            if (isset($_GET['empresa_id'])) {
                $empresa_id = intval($_GET['empresa_id']);

                if ($empresa_id <= 0) {
                    http_response_code(400);
                    echo json_encode(['error' => 'ID de empresa inválido.']);
                    exit;
                }

                $stmt = $conn_acceso->prepare("SELECT * FROM empresa_empleados WHERE empresa_id = ? ORDER BY paterno, nombre ASC");

                if (!$stmt) {
                    throw new Exception("Error preparando la consulta: " . $conn_acceso->error);
                }

                $stmt->bind_param("i", $empresa_id);

                if (!$stmt->execute()) {
                    throw new Exception("Error al ejecutar la consulta: " . $stmt->error);
                }

                $result = $stmt->get_result();
                $empleados = [];

                while ($row = $result->fetch_assoc()) {
                    $row['id'] = (int)$row['id'];
                    $row['empresa_id'] = (int)$row['empresa_id'];
                    $row['acceso_permanente'] = (bool)$row['acceso_permanente'];
                    $row['status'] = get_status_by_date($row['acceso_permanente'], $row['fecha_expiracion']);
                    $empleados[] = $row;
                }

                echo json_encode($empleados);
                $stmt->close();
                exit;
            } else {
                // Obtener todos los empleados (con tipado correcto)
                $result = $conn_acceso->query("SELECT * FROM empresa_empleados ORDER BY empresa_id, paterno, nombre ASC");

                if (!$result) {
                    throw new Exception($conn_acceso->error);
                }

                $empleados = [];
                while ($row = $result->fetch_assoc()) {
                    $row['id'] = (int)$row['id'];
                    $row['empresa_id'] = (int)$row['empresa_id'];
                    $row['acceso_permanente'] = (bool)$row['acceso_permanente'];
                    $row['status'] = get_status_by_date($row['acceso_permanente'], $row['fecha_expiracion']);
                    $empleados[] = $row;
                }

                echo json_encode($empleados);
                exit;
            }
        } catch (Exception $e) {
            http_response_code(500);
            echo json_encode(['error' => 'Error al obtener empleados: ' . $e->getMessage()]);
            exit;
        }
        break;
    case 'POST':
        try {
            $data = json_decode(file_get_contents('php://input'), true);

            // Validación de campos requeridos
            if (!isset($data['empresa_id']) || empty($data['empresa_id'])) {
                http_response_code(400);
                echo json_encode(['error' => 'Falta campo requerido: empresa_id.']);
                exit;
            }

            if (!isset($data['nombre']) || empty(trim($data['nombre'] ?? ''))) {
                http_response_code(400);
                echo json_encode(['error' => 'Falta campo requerido: nombre.']);
                exit;
            }

            if (!isset($data['paterno']) || empty(trim($data['paterno'] ?? ''))) {
                http_response_code(400);
                echo json_encode(['error' => 'Falta campo requerido: paterno.']);
                exit;
            }

            if (!isset($data['rut']) || empty(trim($data['rut'] ?? ''))) {
                http_response_code(400);
                echo json_encode(['error' => 'Falta campo requerido: rut.']);
                exit;
            }

            if (!isset($data['fecha_inicio']) || empty(trim($data['fecha_inicio'] ?? ''))) {
                http_response_code(400);
                echo json_encode(['error' => 'Falta campo requerido: fecha de inicio.']);
                exit;
            }

            $acceso_permanente = !empty($data['acceso_permanente']) ? 1 : 0;

            if (!$acceso_permanente && (!isset($data['fecha_expiracion']) || empty(trim($data['fecha_expiracion'] ?? '')))) {
                http_response_code(400);
                echo json_encode(['error' => 'Falta campo requerido: fecha de expiración (o active acceso permanente).']);
                exit;
            }

            $fecha_expiracion = $acceso_permanente ? null : ($data['fecha_expiracion'] ?? null);
            $status = get_status_by_date($acceso_permanente, $fecha_expiracion);

            $stmt = $conn_acceso->prepare("INSERT INTO empresa_empleados (empresa_id, nombre, paterno, materno, rut, fecha_inicio, fecha_expiracion, acceso_permanente, status) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)");

            if (!$stmt) {
                throw new Exception("Error preparando la consulta: " . $conn_acceso->error);
            }

            $materno = isset($data['materno']) && !empty(trim($data['materno'] ?? '')) ? trim($data['materno'] ?? '') : null;
            $nombre = trim($data['nombre'] ?? '');
            $paterno = trim($data['paterno'] ?? '');
            $rut = trim($data['rut'] ?? '');
            $fecha_inicio = !empty($data['fecha_inicio']) ? $data['fecha_inicio'] : null;

            $stmt->bind_param("issssssis", $data['empresa_id'], $nombre, $paterno, $materno, $rut, $fecha_inicio, $fecha_expiracion, $acceso_permanente, $status);

            if (!$stmt->execute()) {
                throw new Exception("Error al ejecutar la consulta: " . $stmt->error);
            }

            $insert_id = $stmt->insert_id;
            $stmt->close();

            // Obtener datos completos del empleado creado
            $stmt_get = $conn_acceso->prepare("SELECT * FROM empresa_empleados WHERE id = ?");
            if ($stmt_get) {
                $stmt_get->bind_param("i", $insert_id);
                $stmt_get->execute();
                $result_get = $stmt_get->get_result();
                $empleado = $result_get->fetch_assoc();
                $stmt_get->close();

                if ($empleado) {
                    $empleado['id'] = (int)$empleado['id'];
                    $empleado['empresa_id'] = (int)$empleado['empresa_id'];
                    $empleado['acceso_permanente'] = (bool)$empleado['acceso_permanente'];

                    http_response_code(201);
                    echo json_encode($empleado);
                    exit;
                }
            }

            // Fallback si la consulta GET falla
            http_response_code(201);
            echo json_encode([
                'id' => $insert_id,
                'message' => 'Empleado creado correctamente.',
                'success' => true
            ]);
            exit;

        } catch (Exception $e) {
            http_response_code(500);
            echo json_encode([
                'error' => 'Error al crear empleado.',
                'details' => $e->getMessage()
            ]);
            exit;
        }
        break;
    case 'PUT':
        try {
            $data = json_decode(file_get_contents('php://input'), true);

            // Validación de ID
            if (!isset($data['id']) || empty($data['id'])) {
                http_response_code(400);
                echo json_encode(['error' => 'Falta campo requerido: id.']);
                exit;
            }

            $id = (int)$data['id'];

            // Validación de campos requeridos
            if (!isset($data['nombre']) || empty(trim($data['nombre'] ?? ''))) {
                http_response_code(400);
                echo json_encode(['error' => 'Falta campo requerido: nombre.']);
                exit;
            }

            if (!isset($data['paterno']) || empty(trim($data['paterno'] ?? ''))) {
                http_response_code(400);
                echo json_encode(['error' => 'Falta campo requerido: paterno.']);
                exit;
            }

            if (!isset($data['rut']) || empty(trim($data['rut'] ?? ''))) {
                http_response_code(400);
                echo json_encode(['error' => 'Falta campo requerido: rut.']);
                exit;
            }

            if (!isset($data['fecha_inicio']) || empty(trim($data['fecha_inicio'] ?? ''))) {
                http_response_code(400);
                echo json_encode(['error' => 'Falta campo requerido: fecha de inicio.']);
                exit;
            }

            // Verificar que el registro existe
            $stmt_check = $conn_acceso->prepare("SELECT id FROM empresa_empleados WHERE id = ?");
            if (!$stmt_check) {
                throw new Exception("Error preparando consulta de verificación: " . $conn_acceso->error);
            }

            $stmt_check->bind_param("i", $id);
            $stmt_check->execute();
            $result_check = $stmt_check->get_result();

            if ($result_check->num_rows === 0) {
                http_response_code(404);
                echo json_encode(['error' => 'Empleado no encontrado.']);
                $stmt_check->close();
                exit;
            }

            $stmt_check->close();

            $acceso_permanente = !empty($data['acceso_permanente']) ? 1 : 0;

            if (!$acceso_permanente && (!isset($data['fecha_expiracion']) || empty(trim($data['fecha_expiracion'] ?? '')))) {
                http_response_code(400);
                echo json_encode(['error' => 'Falta campo requerido: fecha de expiración (o active acceso permanente).']);
                exit;
            }

            $fecha_inicio = !empty($data['fecha_inicio']) ? $data['fecha_inicio'] : null;
            $fecha_expiracion = $acceso_permanente ? null : ($data['fecha_expiracion'] ?? null);
            $status = get_status_by_date($acceso_permanente, $fecha_expiracion);

            $stmt = $conn_acceso->prepare("UPDATE empresa_empleados SET nombre=?, paterno=?, materno=?, rut=?, fecha_inicio=?, fecha_expiracion=?, acceso_permanente=?, status=? WHERE id=?");

            if (!$stmt) {
                throw new Exception("Error preparando la consulta: " . $conn_acceso->error);
            }

            $materno = isset($data['materno']) && !empty(trim($data['materno'] ?? '')) ? trim($data['materno'] ?? '') : null;
            $nombre = trim($data['nombre'] ?? '');
            $paterno = trim($data['paterno'] ?? '');
            $rut = trim($data['rut'] ?? '');

            $stmt->bind_param("ssssssisi", $nombre, $paterno, $materno, $rut, $fecha_inicio, $fecha_expiracion, $acceso_permanente, $status, $id);

            if (!$stmt->execute()) {
                throw new Exception("Error al ejecutar la consulta: " . $stmt->error);
            }

            $stmt->close();

            // Obtener datos actualizados
            $stmt_get = $conn_acceso->prepare("SELECT * FROM empresa_empleados WHERE id = ?");
            if ($stmt_get) {
                $stmt_get->bind_param("i", $id);
                $stmt_get->execute();
                $result_get = $stmt_get->get_result();
                $empleado = $result_get->fetch_assoc();
                $stmt_get->close();

                if ($empleado) {
                    $empleado['id'] = (int)$empleado['id'];
                    $empleado['empresa_id'] = (int)$empleado['empresa_id'];
                    $empleado['acceso_permanente'] = (bool)$empleado['acceso_permanente'];

                    echo json_encode($empleado);
                    exit;
                }
            }

            // Fallback
            echo json_encode(['message' => 'Empleado actualizado correctamente.', 'success' => true]);
            exit;

        } catch (Exception $e) {
            http_response_code(500);
            echo json_encode([
                'error' => 'Error al actualizar empleado.',
                'details' => $e->getMessage()
            ]);
            exit;
        }
        break;
    case 'DELETE':
        try {
            $id = $_GET['id'] ?? null;

            if (!$id) {
                http_response_code(400);
                echo json_encode(['error' => 'ID de empleado no proporcionado.']);
                exit;
            }

            $id = (int)$id;

            // Usar borrado LÓGICO en lugar de físico (actualizar status a 'inactivo')
            $stmt = $conn_acceso->prepare("UPDATE empresa_empleados SET status = 'inactivo' WHERE id = ?");

            if (!$stmt) {
                throw new Exception("Error preparando la consulta: " . $conn_acceso->error);
            }

            $stmt->bind_param("i", $id);

            if ($stmt->execute()) {
                if ($stmt->affected_rows > 0) {
                    http_response_code(204); // No Content - Eliminado correctamente
                } else {
                    http_response_code(404); // Not Found
                    echo json_encode(['error' => 'Empleado no encontrado.']);
                }
            } else {
                throw new Exception("Error al ejecutar la consulta: " . $stmt->error);
            }

            $stmt->close();
            exit;

        } catch (Exception $e) {
            http_response_code(500);
            echo json_encode([
                'error' => 'Error al eliminar empleado.',
                'details' => $e->getMessage()
            ]);
            exit;
        }
        break;
    default:
        http_response_code(405);
        echo json_encode(['message' => 'Método no permitido']);
        break;
}
$conn_acceso->close();
?>
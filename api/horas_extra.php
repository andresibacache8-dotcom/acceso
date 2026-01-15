<?php
// api/horas_extra.php
require_once 'database/db_acceso.php';
require_once 'database/db_personal.php';

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

switch ($method) {
    case 'GET':
        try {
            // Obtener registros activos ordenados por fecha más reciente
            $result = $conn_acceso->query("SELECT * FROM horas_extra WHERE status = 'activo' ORDER BY fecha_registro DESC");

            if (!$result) {
                throw new Exception($conn_acceso->error);
            }

            $horas_extra = [];
            while ($row = $result->fetch_assoc()) {
                // Asegurar que todos los campos tengan el tipo correcto
                $horas_extra[] = [
                    'id' => (int)$row['id'],
                    'personal_rut' => $row['personal_rut'] ?? '',
                    'personal_nombre' => $row['personal_nombre'] ?? '',
                    'fecha_hora_termino' => $row['fecha_hora_termino'] ?? '',
                    'motivo' => $row['motivo'] ?? '',
                    'motivo_detalle' => $row['motivo_detalle'] ?? null,
                    'autorizado_por_rut' => $row['autorizado_por_rut'] ?? '',
                    'autorizado_por_nombre' => $row['autorizado_por_nombre'] ?? '',
                    'fecha_registro' => $row['fecha_registro'] ?? '',
                    'status' => $row['status'] ?? 'activo'
                ];
            }

            // Devolver directamente el array de horas extra
            echo json_encode($horas_extra);
            exit;
        } catch (Exception $e) {
            http_response_code(500);
            echo json_encode(['error' => 'Error al obtener registros de horas extra: ' . $e->getMessage()]);
            exit;
        }
        break;

    case 'POST':
        try {
            $data = json_decode(file_get_contents('php://input'), true);

            // Validación de datos - verificar estructura
            if (!isset($data['personal']) || !is_array($data['personal']) || empty($data['personal'])) {
                http_response_code(400);
                echo json_encode(['error' => 'Debe proporcionar al menos una persona en el array "personal".']);
                exit;
            }

            if (!isset($data['fecha_hora_termino']) || empty(trim($data['fecha_hora_termino']))) {
                http_response_code(400);
                echo json_encode(['error' => 'Falta campo requerido: fecha_hora_termino.']);
                exit;
            }

            if (!isset($data['motivo']) || empty(trim($data['motivo']))) {
                http_response_code(400);
                echo json_encode(['error' => 'Falta campo requerido: motivo.']);
                exit;
            }

            if (!isset($data['autorizado_por_rut']) || empty(trim($data['autorizado_por_rut']))) {
                http_response_code(400);
                echo json_encode(['error' => 'Falta campo requerido: autorizado_por_rut.']);
                exit;
            }

            if (!isset($data['autorizado_por_nombre']) || empty(trim($data['autorizado_por_nombre']))) {
                http_response_code(400);
                echo json_encode(['error' => 'Falta campo requerido: autorizado_por_nombre.']);
                exit;
            }

            // Validar formato de datetime
            if (!strtotime($data['fecha_hora_termino'])) {
                http_response_code(400);
                echo json_encode(['error' => 'Formato de fecha_hora_termino inválido. Use formato YYYY-MM-DD HH:MM:SS.']);
                exit;
            }

            $stmt = $conn_acceso->prepare(
                "INSERT INTO horas_extra (personal_rut, personal_nombre, fecha_hora_termino, motivo, motivo_detalle, autorizado_por_rut, autorizado_por_nombre) VALUES (?, ?, ?, ?, ?, ?, ?)"
            );

            if (!$stmt) {
                throw new Exception("Error preparando la consulta: " . $conn_acceso->error);
            }

            $motivo_detalle = isset($data['motivo_detalle']) && !empty(trim($data['motivo_detalle'])) ? trim($data['motivo_detalle']) : null;

            $conn_acceso->begin_transaction();
            $insert_count = 0;

            foreach ($data['personal'] as $index => $persona) {
                // Validar que tenga rut y nombre
                if (!isset($persona['rut']) || empty(trim($persona['rut']))) {
                    throw new Exception("Persona en índice $index no tiene RUT.");
                }
                if (!isset($persona['nombre']) || empty(trim($persona['nombre']))) {
                    throw new Exception("Persona en índice $index no tiene nombre.");
                }

                $personal_rut = trim($persona['rut']);
                $personal_nombre = trim($persona['nombre']);

                $stmt->bind_param(
                    "sssssss",
                    $personal_rut,
                    $personal_nombre,
                    $data['fecha_hora_termino'],
                    $data['motivo'],
                    $motivo_detalle,
                    $data['autorizado_por_rut'],
                    $data['autorizado_por_nombre']
                );

                if (!$stmt->execute()) {
                    throw new Exception("Error al registrar horas para $personal_nombre: " . $stmt->error);
                }

                $insert_count++;
            }

            $conn_acceso->commit();
            $stmt->close();

            http_response_code(201);
            echo json_encode([
                'message' => "Registros de horas extra creados correctamente ($insert_count registros).",
                'count' => $insert_count,
                'success' => true
            ]);
            exit;

        } catch (Exception $e) {
            if ($conn_acceso->in_transaction) {
                $conn_acceso->rollback();
            }
            http_response_code(500);
            echo json_encode([
                'error' => 'Error al registrar las horas extra.',
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
                echo json_encode(['error' => 'ID de horas extra no proporcionado.']);
                exit;
            }

            // Usar borrado LÓGICO en lugar de físico (actualizar status a 'inactivo')
            $stmt = $conn_acceso->prepare("UPDATE horas_extra SET status = 'inactivo' WHERE id = ?");

            if (!$stmt) {
                throw new Exception("Error preparando la consulta: " . $conn_acceso->error);
            }

            $stmt->bind_param("i", $id);

            if ($stmt->execute()) {
                if ($stmt->affected_rows > 0) {
                    http_response_code(204); // No Content - Eliminado correctamente
                } else {
                    http_response_code(404); // Not Found - No existe el registro
                    echo json_encode(['error' => 'Registro de horas extra no encontrado.']);
                }
            } else {
                throw new Exception("Error al ejecutar la consulta: " . $stmt->error);
            }

            $stmt->close();
            exit;

        } catch (Exception $e) {
            http_response_code(500);
            echo json_encode([
                'error' => 'Error al eliminar el registro de horas extra.',
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
// $conn_personal se cierra en db_personal.php si es necesario, o aquí si la conexión persiste.
// Si db_personal.php no cierra la conexión, descomentar la siguiente línea:
// $conn_personal->close();
?>

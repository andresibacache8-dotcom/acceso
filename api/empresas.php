<?php
// api/empresas.php
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
            $result_empresas = $conn_acceso->query("SELECT * FROM empresas ORDER BY nombre ASC");

            if (!$result_empresas) {
                throw new Exception($conn_acceso->error);
            }

            $empresas = [];
            while ($row = $result_empresas->fetch_assoc()) {
                $row['id'] = (int)$row['id'];

                // Si hay un RUT de POC, buscar su nombre en la BD de personal
                if (!empty($row['poc_rut']) && isset($conn_personal)) {
                    $stmt_personal = $conn_personal->prepare("SELECT Grado, Nombres, Paterno, anexo FROM personal WHERE NrRut = ?");
                    if ($stmt_personal) {
                        $stmt_personal->bind_param("s", $row['poc_rut']);
                        $stmt_personal->execute();
                        $result_personal = $stmt_personal->get_result();
                        $person = $result_personal->fetch_assoc();
                        $stmt_personal->close();
                        if ($person) {
                            $row['poc_nombre'] = trim(($person['Grado'] ?? '') . ' ' . ($person['Nombres'] ?? '') . ' ' . ($person['Paterno'] ?? ''));
                            // Solo usar el anexo de personal si no hay uno guardado en la empresa
                            if (empty($row['poc_anexo']) && !empty($person['anexo'])) {
                                $row['poc_anexo'] = $person['anexo'];
                            }
                        }
                    }
                }
                $empresas[] = $row;
            }

            echo json_encode($empresas);
            exit;

        } catch (Exception $e) {
            http_response_code(500);
            echo json_encode(['error' => 'Error al obtener empresas: ' . $e->getMessage()]);
            exit;
        }
        break;
    case 'POST':
        $data = json_decode(file_get_contents('php://input'), true);
        $stmt = $conn_acceso->prepare("INSERT INTO empresas (nombre, unidad_poc, poc_rut, poc_nombre, poc_anexo) VALUES (?, ?, ?, ?, ?)");
        $stmt->bind_param("sssss", $data['nombre'], $data['unidad_poc'], $data['poc_rut'], $data['poc_nombre'], $data['poc_anexo']);
        $stmt->execute();
        $data['id'] = $stmt->insert_id;
        $stmt->close();
        http_response_code(201);
        echo json_encode($data);
        break;
    case 'PUT':
        $data = json_decode(file_get_contents('php://input'), true);
        $id = $data['id'];
        $stmt = $conn_acceso->prepare("UPDATE empresas SET nombre=?, unidad_poc=?, poc_rut=?, poc_nombre=?, poc_anexo=? WHERE id=?");
        $stmt->bind_param("sssssi", $data['nombre'], $data['unidad_poc'], $data['poc_rut'], $data['poc_nombre'], $data['poc_anexo'], $id);
        $stmt->execute();
        $stmt->close();
        echo json_encode($data);
        break;
    case 'DELETE':
        try {
            $id = $_GET['id'] ?? null;

            if (!$id) {
                http_response_code(400);
                echo json_encode(['error' => 'ID de empresa no proporcionado.']);
                exit;
            }

            $id = (int)$id;

            // Para empresas, hacer borrado físico (ya que no hay campo status)
            // Pero primero verificar que la empresa existe
            $stmt_check = $conn_acceso->prepare("SELECT id FROM empresas WHERE id = ?");
            if (!$stmt_check) {
                throw new Exception("Error preparando consulta: " . $conn_acceso->error);
            }

            $stmt_check->bind_param("i", $id);
            $stmt_check->execute();
            $result_check = $stmt_check->get_result();

            if ($result_check->num_rows === 0) {
                http_response_code(404);
                echo json_encode(['error' => 'Empresa no encontrada.']);
                $stmt_check->close();
                exit;
            }

            $stmt_check->close();

            // Eliminar la empresa
            $stmt = $conn_acceso->prepare("DELETE FROM empresas WHERE id=?");

            if (!$stmt) {
                throw new Exception("Error preparando la consulta: " . $conn_acceso->error);
            }

            $stmt->bind_param("i", $id);

            if ($stmt->execute()) {
                http_response_code(204);
            } else {
                throw new Exception("Error al ejecutar la consulta: " . $stmt->error);
            }

            $stmt->close();
            exit;

        } catch (Exception $e) {
            http_response_code(500);
            echo json_encode([
                'error' => 'Error al eliminar empresa.',
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
if (isset($conn_personal)) $conn_personal->close();
?>
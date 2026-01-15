<?php
require_once 'database/db_personal.php';
require_once 'database/db_acceso.php';

header('Content-Type: application/json');

if (!isset($_GET['query']) || !isset($_GET['tipo'])) {
    http_response_code(400);
    echo json_encode(['message' => 'Faltan parámetros de búsqueda (query y tipo).']);
    exit;
}

$query = $_GET['query'];
$tipo = $_GET['tipo'];
$searchTerm = "%{$query}%";
$stmt = null;
$resultados = [];

// Seleccionar la base de datos y consulta según el tipo de acceso
switch ($tipo) {
    case 'FISCAL':
    case 'FUNCIONARIO':
    case 'RESIDENTE': // Agrupamos residente aquí, ya que también busca en la tabla personal
        $sql = "SELECT id, NrRut, Grado, Nombres, Paterno, Materno, Unidad 
                FROM personal 
                WHERE (NrRut LIKE ? OR Nombres LIKE ? OR Paterno LIKE ? OR Materno LIKE ?)";
        
        // Si el tipo es Residente, añadimos la condición extra
        if ($tipo === 'RESIDENTE') {
            $sql .= " AND es_residente = 1";
        }
        
        $sql .= " LIMIT 10";
        $stmt = $conn_personal->prepare($sql);
        if ($stmt) {
            $stmt->bind_param("ssss", $searchTerm, $searchTerm, $searchTerm, $searchTerm);
        }
        break;

    case 'EMPRESA':
        $sql = "SELECT ee.id, ee.rut AS NrRut, ee.nombre AS Nombres, 
                       ee.paterno AS Paterno, ee.materno AS Materno,
                       e.nombre AS Unidad
                FROM empresa_empleados ee
                JOIN empresas e ON ee.empresa_id = e.id
                WHERE (ee.rut LIKE ? OR ee.nombre LIKE ? OR ee.paterno LIKE ? OR ee.materno LIKE ?)
                LIMIT 10";
        $stmt = $conn_acceso->prepare($sql);
        if ($stmt) {
            $stmt->bind_param("ssss", $searchTerm, $searchTerm, $searchTerm, $searchTerm);
        }
        break;

    case 'VISITA':
        $sql = "SELECT id, rut AS NrRut, '' AS Grado, nombre AS Nombres, 
                       paterno AS Paterno, materno AS Materno,
                       'Visita' AS Unidad
                FROM visitas 
                WHERE (rut LIKE ? OR nombre LIKE ? OR paterno LIKE ? OR materno LIKE ?)
                      AND en_lista_negra = 0
                LIMIT 10";
        $stmt = $conn_acceso->prepare($sql);
        if ($stmt) {
            $stmt->bind_param("ssss", $searchTerm, $searchTerm, $searchTerm, $searchTerm);
        }
        break;

    default:
        http_response_code(400);
        echo json_encode(['message' => 'Tipo de acceso no válido']);
        exit;
}

if (!$stmt || !$stmt->execute()) {
    http_response_code(500);
    $error_msg = $stmt ? $stmt->error : ($conn_acceso->error ?: $conn_personal->error);
    echo json_encode(['message' => 'Error en la consulta: ' . $error_msg]);
    exit;
}

$result = $stmt->get_result();
while ($row = $result->fetch_assoc()) {
    $resultados[] = [
        'id' => $row['id'],
        'NrRut' => $row['NrRut'],
        'Grado' => $row['Grado'] ?? '',
        'Nombres' => $row['Nombres'],
        'Paterno' => $row['Paterno'],
        'Materno' => $row['Materno'] ?? '',
        'Unidad' => $row['Unidad'] ?? ''
    ];
}

$stmt->close();

if (count($resultados) > 0) {
    echo json_encode($resultados);
} else {
    http_response_code(404);
    echo json_encode(['message' => 'No se encontraron resultados']);
}
?>

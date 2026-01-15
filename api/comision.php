<?php
// api/comision.php
require_once 'database/db_personal.php'; // Conexión a la BD de personal

header('Content-Type: application/json');
$method = $_SERVER['REQUEST_METHOD'];

// Función para calcular el estado basado en la fecha de fin de la comisión
function get_comision_status($fecha_fin_str) {
    if (empty($fecha_fin_str)) {
        return 'Activo';
    }
    try {
        $fecha_fin = new DateTime($fecha_fin_str);
        $today = new DateTime('today');
        return ($fecha_fin >= $today) ? 'Activo' : 'Finalizado';
    } catch (Exception $e) {
        return 'Activo';
    }
}

switch ($method) {
    case 'GET':
        // Selecciona campos individuales y forma el nombre completo
        $sql = "SELECT
                    id, rut, grado, nombres, paterno, materno,
                    CONCAT_WS(' ', grado, nombres, paterno, materno) as nombre_completo,
                    unidad_origen, unidad_poc,
                    DATE_FORMAT(fecha_inicio, '%Y-%m-%d') as fecha_inicio,
                    DATE_FORMAT(fecha_fin, '%Y-%m-%d') as fecha_fin,
                    motivo, poc_nombre, poc_anexo, estado
                FROM personal_db.personal_comision
                ORDER BY paterno, materno, nombres ASC";

        $result = $conn_personal->query($sql);

        if (!$result) {
            http_response_code(500);
            echo json_encode(['message' => 'Error al obtener los datos: ' . $conn_personal->error]);
            break;
        }

        $comisiones = [];
        while ($row = $result->fetch_assoc()) {
            $comisiones[] = $row;
        }
        echo json_encode($comisiones);
        break;

    case 'POST':
        $data = json_decode(file_get_contents('php://input'), true);
        $estado = get_comision_status($data['fecha_fin'] ?? null);

        // Preparar variables para bind_param
        $rut = $data['rut'] ?? null;
        $grado = $data['grado'] ?? null;
        $nombres = $data['nombres'] ?? null;
        $paterno = $data['paterno'] ?? null;
        $materno = $data['materno'] ?? null;
        $unidad_origen = $data['unidad_origen'] ?? null;
        $unidad_poc = $data['unidad_poc'] ?? null;
        $fecha_inicio = $data['fecha_inicio'] ?? null;
        $fecha_fin = $data['fecha_fin'] ?? null;
        $motivo = $data['motivo'] ?? null;
        $poc_nombre = $data['poc_nombre'] ?? null;
        $poc_anexo = $data['poc_anexo'] ?? null;

        $stmt = $conn_personal->prepare("INSERT INTO personal_comision (rut, grado, nombres, paterno, materno, unidad_origen, unidad_poc, fecha_inicio, fecha_fin, motivo, poc_nombre, poc_anexo, estado) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
        $stmt->bind_param("sssssssssssss",
            $rut,
            $grado,
            $nombres,
            $paterno,
            $materno,
            $unidad_origen,
            $unidad_poc,
            $fecha_inicio,
            $fecha_fin,
            $motivo,
            $poc_nombre,
            $poc_anexo,
            $estado
        );
        $stmt->execute();
        $newId = $stmt->insert_id;
        $stmt->close();

        $data['id'] = $newId;
        $data['estado'] = $estado;
        http_response_code(201);
        echo json_encode($data);
        break;

    case 'PUT':
        $data = json_decode(file_get_contents('php://input'), true);
        $id = $data['id'];
        $estado = get_comision_status($data['fecha_fin'] ?? null);

        // Preparar variables para bind_param
        $rut = $data['rut'] ?? null;
        $grado = $data['grado'] ?? null;
        $nombres = $data['nombres'] ?? null;
        $paterno = $data['paterno'] ?? null;
        $materno = $data['materno'] ?? null;
        $unidad_origen = $data['unidad_origen'] ?? null;
        $unidad_poc = $data['unidad_poc'] ?? null;
        $fecha_inicio = $data['fecha_inicio'] ?? null;
        $fecha_fin = $data['fecha_fin'] ?? null;
        $motivo = $data['motivo'] ?? null;
        $poc_nombre = $data['poc_nombre'] ?? null;
        $poc_anexo = $data['poc_anexo'] ?? null;

        $stmt = $conn_personal->prepare("UPDATE personal_comision SET rut=?, grado=?, nombres=?, paterno=?, materno=?, unidad_origen=?, unidad_poc=?, fecha_inicio=?, fecha_fin=?, motivo=?, poc_nombre=?, poc_anexo=?, estado=? WHERE id=?");
        $stmt->bind_param("sssssssssssssi",
            $rut,
            $grado,
            $nombres,
            $paterno,
            $materno,
            $unidad_origen,
            $unidad_poc,
            $fecha_inicio,
            $fecha_fin,
            $motivo,
            $poc_nombre,
            $poc_anexo,
            $estado,
            $id
        );
        $stmt->execute();
        $stmt->close();

        $data['estado'] = $estado;
        echo json_encode($data);
        break;

    case 'DELETE':
        // CORREGIDO: Asegura que se usa la conexión a la BD de personal.
        $id = $_GET['id'];
        $stmt = $conn_personal->prepare("DELETE FROM personal_comision WHERE id=?");
        $stmt->bind_param("i", $id);
        $stmt->execute();
        
        if ($stmt->affected_rows > 0) {
            http_response_code(204); // Éxito, sin contenido
        } else {
            http_response_code(404);
            echo json_encode(['message' => 'Registro de comisión no encontrado']);
        }
        $stmt->close();
        break;

    default:
        http_response_code(405);
        echo json_encode(['message' => 'Método no permitido']);
        break;
}

if (isset($conn_personal)) {
    $conn_personal->close();
}
?>
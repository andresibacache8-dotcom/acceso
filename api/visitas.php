<?php
// api/visitas.php
require_once 'database/db_acceso.php';
require_once 'database/db_personal.php'; // Necesario para buscar datos del personal

header('Content-Type: application/json');
$method = $_SERVER['REQUEST_METHOD'];

function calculate_visita_status($is_blacklisted, $is_permanent, $start_date_str, $end_date_str) {
    if ($is_blacklisted) return 'no autorizado';
    if ($is_permanent) return 'autorizado';
    if (empty($start_date_str) || empty($end_date_str)) return 'no autorizado';
    try {
        $start_date = new DateTime($start_date_str);
        $end_date = new DateTime($end_date_str);
        $today = new DateTime('today');
        return ($today >= $start_date && $today <= $end_date) ? 'autorizado' : 'no autorizado';
    } catch (Exception $e) {
        return 'no autorizado';
    }
}

switch ($method) {
    case 'GET':
        $sql = "SELECT
                    v.*,
                    CONCAT_WS(' ', p_poc.Grado, p_poc.Nombres, p_poc.Paterno, p_poc.Materno) as poc_nombre,
                    p_poc.NrRut as poc_rut,
                    CONCAT_WS(' ', p_fam.Grado, p_fam.Nombres, p_fam.Paterno, p_fam.Materno) as familiar_nombre,
                    p_fam.NrRut as familiar_rut
                FROM visitas v
                LEFT JOIN personal_db.personal p_poc ON v.poc_personal_id = p_poc.id
                LEFT JOIN personal_db.personal p_fam ON v.familiar_de_personal_id = p_fam.id";
        $result = $conn_acceso->query($sql);
        $visitas = [];
        while ($row = $result->fetch_assoc()) {
            $row['acceso_permanente'] = (bool)$row['acceso_permanente'];
            $row['en_lista_negra'] = (bool)$row['en_lista_negra'];
            $row['status'] = calculate_visita_status($row['en_lista_negra'], $row['acceso_permanente'], $row['fecha_inicio'], $row['fecha_expiracion']);
            $visitas[] = $row;
        }
        echo json_encode($visitas);
        break;

    case 'POST':
        $data = json_decode(file_get_contents('php://input'), true);
        $acceso_permanente = !empty($data['acceso_permanente']) ? 1 : 0;
        $en_lista_negra = !empty($data['en_lista_negra']) ? 1 : 0;
        // Fecha de inicio SIEMPRE se guarda
        $fecha_inicio = $data['fecha_inicio'] ?? null;
        // Fecha de expiración solo se guarda si NO es acceso permanente
        $fecha_expiracion = $acceso_permanente ? null : ($data['fecha_expiracion'] ?? null);
        $status = calculate_visita_status($en_lista_negra, $acceso_permanente, $fecha_inicio, $fecha_expiracion);

        $poc_personal_id = null;
        $poc_unidad = null;
        $poc_anexo = null;
        $familiar_de_personal_id = null;
        $familiar_unidad = null;
        $familiar_anexo = null;

        if ($data['tipo'] == 'Visita' && !empty($data['poc_personal_id'])) {
            $poc_personal_id = $data['poc_personal_id'];
            $poc_anexo = $data['poc_anexo'] ?? null; // Prioritize form input
            
            $stmt_poc = $conn_personal->prepare("SELECT Unidad, anexo FROM personal WHERE id = ?");
            $stmt_poc->bind_param("i", $poc_personal_id);
            $stmt_poc->execute();
            $result_poc = $stmt_poc->get_result();
            if ($poc_data = $result_poc->fetch_assoc()) {
                $poc_unidad = $poc_data['Unidad'];
                if (empty($poc_anexo)) { // Use DB value as fallback
                    $poc_anexo = $poc_data['anexo'];
                }
            }
            $stmt_poc->close();
        } elseif ($data['tipo'] == 'Familiar' && !empty($data['familiar_de_personal_id'])) {
            $familiar_de_personal_id = $data['familiar_de_personal_id'];
            $familiar_unidad = $data['familiar_unidad'] ?? null;
            $familiar_anexo = $data['familiar_anexo'] ?? null;
        }

        $stmt = $conn_acceso->prepare("INSERT INTO visitas (rut, nombre, paterno, materno, movil, tipo, fecha_inicio, fecha_expiracion, acceso_permanente, en_lista_negra, status, poc_personal_id, poc_unidad, poc_anexo, familiar_de_personal_id, familiar_unidad, familiar_anexo) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
        $stmt->bind_param("ssssssssiisississ", $data['rut'], $data['nombre'], $data['paterno'], $data['materno'], $data['movil'], $data['tipo'], $fecha_inicio, $fecha_expiracion, $acceso_permanente, $en_lista_negra, $status, $poc_personal_id, $poc_unidad, $poc_anexo, $familiar_de_personal_id, $familiar_unidad, $familiar_anexo);
        
        if ($stmt->execute()) {
            $newId = $stmt->insert_id;
            $data['id'] = $newId;
            $data['status'] = $status;
            http_response_code(201);
            echo json_encode($data);
        } else {
            http_response_code(500);
            echo json_encode(['error' => 'Error al crear la visita: ' . $stmt->error]);
        }
        $stmt->close();
        break;

    case 'PUT':
        $data = json_decode(file_get_contents('php://input'), true);

        if (isset($_GET['action']) && $_GET['action'] === 'toggle_blacklist') {
            $id = $_GET['id'] ?? null;
            $en_lista_negra = isset($data['en_lista_negra']) ? ($data['en_lista_negra'] ? 1 : 0) : null;

            if ($id === null || $en_lista_negra === null) {
                http_response_code(400);
                echo json_encode(['error' => 'Datos incompletos para actualizar la lista negra.']);
                exit;
            }

            // Obtener datos actuales para recalcular el estado
            $stmt_select = $conn_acceso->prepare("SELECT acceso_permanente, fecha_inicio, fecha_expiracion FROM visitas WHERE id = ?");
            $stmt_select->bind_param("i", $id);
            $stmt_select->execute();
            $result_select = $stmt_select->get_result();
            $current_visita = $result_select->fetch_assoc();
            $stmt_select->close();

            if (!$current_visita) {
                http_response_code(404);
                echo json_encode(['error' => 'Visita no encontrada.']);
                exit;
            }

            // Calcular nuevo estado
            $new_status = calculate_visita_status(
                $en_lista_negra, 
                $current_visita['acceso_permanente'], 
                $current_visita['fecha_inicio'], 
                $current_visita['fecha_expiracion']
            );

            $stmt = $conn_acceso->prepare("UPDATE visitas SET en_lista_negra = ?, status = ? WHERE id = ?");
            $stmt->bind_param("isi", $en_lista_negra, $new_status, $id);
            
            if ($stmt->execute()) {
                echo json_encode(['success' => true, 'id' => $id, 'en_lista_negra' => (bool)$en_lista_negra, 'status' => $new_status]);
            } else {
                http_response_code(500);
                echo json_encode(['error' => 'Error al actualizar la lista negra: ' . $stmt->error]);
            }
            $stmt->close();
            exit; // Detener la ejecución para no continuar con la lógica de actualización general
        }

        // Lógica para la actualización general de la visita
        $id = $data['id'] ?? null;
        if (!$id) {
            http_response_code(400);
            echo json_encode(['error' => 'ID de visita no proporcionado para la actualización.']);
            exit;
        }

        $acceso_permanente = !empty($data['acceso_permanente']) ? 1 : 0;
        $en_lista_negra = !empty($data['en_lista_negra']) ? 1 : 0;
        // Fecha de inicio SIEMPRE se guarda
        $fecha_inicio = $data['fecha_inicio'] ?? null;
        // Fecha de expiración solo se guarda si NO es acceso permanente
        $fecha_expiracion = $acceso_permanente ? null : ($data['fecha_expiracion'] ?? null);
        $status = calculate_visita_status($en_lista_negra, $acceso_permanente, $fecha_inicio, $fecha_expiracion);

        $poc_personal_id = null;
        $poc_unidad = null;
        $poc_anexo = null;
        $familiar_de_personal_id = null;
        $familiar_unidad = null;
        $familiar_anexo = null;

        if ($data['tipo'] == 'Visita' && !empty($data['poc_personal_id'])) {
            $poc_personal_id = $data['poc_personal_id'];
            $poc_anexo = $data['poc_anexo'] ?? null; // Prioritize form input

            $stmt_poc = $conn_personal->prepare("SELECT Unidad, anexo FROM personal WHERE id = ?");
            $stmt_poc->bind_param("i", $poc_personal_id);
            $stmt_poc->execute();
            $result_poc = $stmt_poc->get_result();
            if ($poc_data = $result_poc->fetch_assoc()) {
                $poc_unidad = $poc_data['Unidad'];
                if (empty($poc_anexo)) { // Use DB value as fallback
                    $poc_anexo = $poc_data['anexo'];
                }
            }
            $stmt_poc->close();
        } elseif ($data['tipo'] == 'Familiar' && !empty($data['familiar_de_personal_id'])) {
            $familiar_de_personal_id = $data['familiar_de_personal_id'];
            $familiar_unidad = $data['familiar_unidad'] ?? null;
            $familiar_anexo = $data['familiar_anexo'] ?? null;
        }

        $stmt = $conn_acceso->prepare("UPDATE visitas SET rut=?, nombre=?, paterno=?, materno=?, movil=?, tipo=?, fecha_inicio=?, fecha_expiracion=?, acceso_permanente=?, en_lista_negra=?, status=?, poc_personal_id=?, poc_unidad=?, poc_anexo=?, familiar_de_personal_id=?, familiar_unidad=?, familiar_anexo=? WHERE id=?");
        $stmt->bind_param("ssssssssiisississi", $data['rut'], $data['nombre'], $data['paterno'], $data['materno'], $data['movil'], $data['tipo'], $fecha_inicio, $fecha_expiracion, $acceso_permanente, $en_lista_negra, $status, $poc_personal_id, $poc_unidad, $poc_anexo, $familiar_de_personal_id, $familiar_unidad, $familiar_anexo, $id);
        
        if ($stmt->execute()) {
            $data['status'] = $status;
            echo json_encode($data);
        } else {
            http_response_code(500);
            echo json_encode(['error' => 'Error al actualizar la visita: ' . $stmt->error]);
        }
        $stmt->close();
        break;

    case 'DELETE':
        $id = $_GET['id'];
        $stmt = $conn_acceso->prepare("DELETE FROM visitas WHERE id=?");
        $stmt->bind_param("i", $id);
        $stmt->execute();
        if ($stmt->affected_rows > 0) {
            http_response_code(204);
        } else {
            http_response_code(404);
            echo json_encode(['message' => 'Visita no encontrada']);
        }
        $stmt->close();
        break;
        
    default:
        http_response_code(405);
        echo json_encode(['error' => 'Method Not Allowed']);
        break;
}

$conn_acceso->close();
$conn_personal->close();
?>

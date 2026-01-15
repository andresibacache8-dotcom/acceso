<?php
/**
 * api/guardia-servicio.php
 * API para gestión de personal de Guardia y Servicio
 *
 * Endpoints:
 * - GET  ?action=list   - Listar registros activos
 * - POST ?action=create - Crear nuevo registro
 * - POST ?action=finish - Finalizar registro (marcar salida)
 * - GET  ?action=verify - Verificar RUT (usado para validación)
 */

require_once __DIR__ . '/database/db_acceso.php';
header('Content-Type: application/json; charset=utf-8');

$action = $_GET['action'] ?? $_POST['action'] ?? '';

try {
    switch ($action) {
        case 'list':
            // Listar todos los registros activos de guardia/servicio
            // JOIN con personal para obtener el Grado
            $sql = "SELECT
                        gs.id,
                        gs.personal_rut,
                        gs.personal_nombre,
                        gs.tipo,
                        gs.servicio_detalle,
                        gs.anexo,
                        gs.movil,
                        gs.fecha_ingreso,
                        gs.status,
                        p.Grado
                    FROM guardia_servicio gs
                    LEFT JOIN personal_db.personal p ON gs.personal_rut = p.NrRut
                    WHERE gs.status = 'ACTIVO'
                    ORDER BY gs.fecha_ingreso DESC, gs.id DESC";

            $result = $conn_acceso->query($sql);

            if (!$result) {
                throw new Exception('Error al consultar los registros: ' . $conn_acceso->error);
            }

            $data = [];
            while ($row = $result->fetch_assoc()) {
                $data[] = $row;
            }

            echo json_encode($data);
            break;

        case 'create':
            // Crear nuevo registro de guardia/servicio
            $input = json_decode(file_get_contents('php://input'), true);

            // Validar campos requeridos
            $required = ['personal_rut', 'personal_nombre', 'tipo', 'fecha_ingreso'];
            foreach ($required as $field) {
                if (empty($input[$field])) {
                    throw new Exception("Campo requerido: $field");
                }
            }

            // Validar tipo
            if (!in_array($input['tipo'], ['GUARDIA', 'SERVICIO'])) {
                throw new Exception('Tipo inválido. Debe ser GUARDIA o SERVICIO.');
            }

            // Verificar si el personal ya tiene un registro activo
            $stmt = $conn_acceso->prepare(
                "SELECT id FROM guardia_servicio
                 WHERE personal_rut = ? AND status = 'ACTIVO'"
            );
            $stmt->bind_param("s", $input['personal_rut']);
            $stmt->execute();
            $result = $stmt->get_result();

            if ($result->num_rows > 0) {
                throw new Exception('Este personal ya tiene un registro de guardia/servicio activo. Debe finalizarlo primero.');
            }

            // Insertar nuevo registro
            $sql = "INSERT INTO guardia_servicio
                    (personal_rut, personal_nombre, tipo, servicio_detalle, anexo, movil, fecha_ingreso, status)
                    VALUES (?, ?, ?, ?, ?, ?, ?, 'ACTIVO')";

            $stmt = $conn_acceso->prepare($sql);

            $servicio_detalle = $input['servicio_detalle'] ?? null;
            $anexo = $input['anexo'] ?? null;
            $movil = $input['movil'] ?? null;

            $stmt->bind_param(
                "sssssss",
                $input['personal_rut'],
                $input['personal_nombre'],
                $input['tipo'],
                $servicio_detalle,
                $anexo,
                $movil,
                $input['fecha_ingreso']
            );

            if (!$stmt->execute()) {
                throw new Exception('Error al crear el registro: ' . $stmt->error);
            }

            $new_id = $conn_acceso->insert_id;

            // Registrar en access_logs (entrada con motivo "Guardia" o "Servicio")
            // Obtener el ID del personal
            $stmt_personal = $conn_acceso->prepare("SELECT id FROM personal_db.personal WHERE NrRut = ?");
            $stmt_personal->bind_param("s", $input['personal_rut']);
            $stmt_personal->execute();
            $result_personal = $stmt_personal->get_result();

            if ($row_personal = $result_personal->fetch_assoc()) {
                $personal_id = $row_personal['id'];
                $motivo = $input['tipo'] === 'GUARDIA' ? 'Guardia' : 'Servicio';

                $stmt_log = $conn_acceso->prepare(
                    "INSERT INTO access_logs
                     (target_id, target_type, action, punto_acceso, motivo, log_time)
                     VALUES (?, 'personal', 'entrada', 'guardia', ?, NOW())"
                );
                $stmt_log->bind_param("is", $personal_id, $motivo);
                $stmt_log->execute();
            }

            echo json_encode([
                'success' => true,
                'message' => 'Registro creado exitosamente',
                'id' => $new_id
            ]);
            break;

        case 'finish':
            // Finalizar registro (marcar salida)
            $input = json_decode(file_get_contents('php://input'), true);

            if (empty($input['id'])) {
                throw new Exception('ID requerido para finalizar registro');
            }

            $id = intval($input['id']);

            // Obtener datos del registro antes de finalizarlo
            $stmt_get = $conn_acceso->prepare(
                "SELECT personal_rut, tipo FROM guardia_servicio WHERE id = ?"
            );
            $stmt_get->bind_param("i", $id);
            $stmt_get->execute();
            $result_get = $stmt_get->get_result();
            $registro = $result_get->fetch_assoc();

            if (!$registro) {
                throw new Exception('Registro no encontrado');
            }

            // Actualizar registro a FINALIZADO
            $stmt = $conn_acceso->prepare(
                "UPDATE guardia_servicio
                 SET status = 'FINALIZADO'
                 WHERE id = ?"
            );
            $stmt->bind_param("i", $id);

            if (!$stmt->execute()) {
                throw new Exception('Error al finalizar el registro: ' . $stmt->error);
            }

            if ($stmt->affected_rows === 0) {
                throw new Exception('No se encontró el registro o ya estaba finalizado');
            }

            // Registrar salida en access_logs
            $stmt_personal = $conn_acceso->prepare("SELECT id FROM personal_db.personal WHERE NrRut = ?");
            $stmt_personal->bind_param("s", $registro['personal_rut']);
            $stmt_personal->execute();
            $result_personal = $stmt_personal->get_result();

            if ($row_personal = $result_personal->fetch_assoc()) {
                $personal_id = $row_personal['id'];
                $motivo = $registro['tipo'] === 'GUARDIA' ? 'Guardia' : 'Servicio';

                $stmt_log = $conn_acceso->prepare(
                    "INSERT INTO access_logs
                     (target_id, target_type, action, punto_acceso, motivo, log_time)
                     VALUES (?, 'personal', 'salida', 'guardia', ?, NOW())"
                );
                $stmt_log->bind_param("is", $personal_id, $motivo);
                $stmt_log->execute();
            }

            echo json_encode([
                'success' => true,
                'message' => 'Registro finalizado exitosamente'
            ]);
            break;

        case 'verify':
            // Verificar si un RUT tiene registro activo
            $rut = $_GET['rut'] ?? '';

            if (empty($rut)) {
                throw new Exception('RUT requerido');
            }

            $stmt = $conn_acceso->prepare(
                "SELECT id, tipo, fecha_ingreso
                 FROM guardia_servicio
                 WHERE personal_rut = ? AND status = 'ACTIVO'"
            );
            $stmt->bind_param("s", $rut);
            $stmt->execute();
            $result = $stmt->get_result();

            $data = $result->fetch_assoc();

            echo json_encode([
                'has_active' => $data !== null,
                'data' => $data
            ]);
            break;

        case 'history':
            // Listar historial completo (activos y finalizados)
            $limit = intval($_GET['limit'] ?? 100);
            $offset = intval($_GET['offset'] ?? 0);

            $sql = "SELECT
                        gs.id,
                        gs.personal_rut,
                        gs.personal_nombre,
                        gs.tipo,
                        gs.servicio_detalle,
                        gs.anexo,
                        gs.movil,
                        gs.fecha_ingreso,
                        gs.status,
                        gs.fecha_registro,
                        p.Grado
                    FROM guardia_servicio gs
                    LEFT JOIN personal_db.personal p ON gs.personal_rut = p.NrRut
                    ORDER BY gs.fecha_ingreso DESC, gs.id DESC
                    LIMIT ? OFFSET ?";

            $stmt = $conn_acceso->prepare($sql);
            $stmt->bind_param("ii", $limit, $offset);
            $stmt->execute();
            $result = $stmt->get_result();

            $data = [];
            while ($row = $result->fetch_assoc()) {
                $data[] = $row;
            }

            echo json_encode($data);
            break;

        default:
            throw new Exception('Acción no válida. Acciones disponibles: list, create, finish, verify, history');
    }
} catch (Exception $e) {
    http_response_code(400);
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage()
    ]);
}

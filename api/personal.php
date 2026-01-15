<?php
require_once 'database/db_personal.php';
require_once 'database/db_acceso.php'; // Conexión a la base de datos de acceso

header('Content-Type: application/json');
$method = $_SERVER['REQUEST_METHOD'];

try {
    switch ($method) {
        case 'GET':
            if (isset($_GET['search'])) {
                // Búsqueda por texto (nombre o RUT)
                $query = trim($_GET['search']);
                
                // Preparar los términos de búsqueda
                $search = "%{$query}%";
                
                // Buscar por nombre, apellido o RUT
                $stmt = $conn_personal->prepare(
                    "SELECT id, Grado, Nombres, Paterno, Materno, NrRut, Unidad, anexo FROM personal 
                    WHERE Nombres LIKE ? OR Paterno LIKE ? OR Materno LIKE ? OR NrRut LIKE ? 
                    ORDER BY Paterno, Nombres
                    LIMIT 10"
                );
                
                if ($stmt === false) {
                    http_response_code(500);
                    echo json_encode(['message' => 'Error al preparar la consulta de búsqueda: ' . $conn_personal->error]);
                    exit();
                }
                
                $stmt->bind_param("ssss", $search, $search, $search, $search);
                $stmt->execute();
                $result = $stmt->get_result();
                
                $data = [];
                while ($row = $result->fetch_assoc()) {
                    // Ensure Materno is an empty string if null or not set
                    $row['Materno'] = $row['Materno'] ?? '';
                    $data[] = $row;
                }
                $stmt->close();
                
            } elseif (isset($_GET['rut'])) {
                $rut = $_GET['rut'];
                $stmt = $conn_personal->prepare("SELECT id, Grado, Nombres, Paterno, Materno, NrRut, Unidad, anexo FROM personal WHERE NrRut = ?");
                if ($stmt === false) {
                    http_response_code(500);
                    echo json_encode(['message' => 'Error al preparar la consulta: ' . $conn_personal->error]);
                    exit();
                }
                $stmt->bind_param("s", $rut);
                $stmt->execute();
                $result = $stmt->get_result();
                $data = $result->fetch_assoc();
                
                // Si no se encontró, retornar array vacío
                if ($data === null || $data === false) {
                    $data = [];
                } else {
                    // Ensure Materno is an empty string if null or not set
                    $data['Materno'] = $data['Materno'] ?? '';
                }
                $stmt->close();
            } elseif (isset($_GET['id'])) {
                $id = intval($_GET['id']);
                $stmt = $conn_personal->prepare("SELECT * FROM personal WHERE id = ?");
                $stmt->bind_param("i", $id);
                $stmt->execute();
                $result = $stmt->get_result();
                $data = $result->fetch_assoc();
                $stmt->close();
            } elseif (isset($_GET['status']) && $_GET['status'] == 'inside') {
                // Nueva lógica para obtener solo personal "dentro"
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
                $result_inside = $conn_acceso->query($sql_inside);
                $inside_ids = [];
                while($row = $result_inside->fetch_assoc()) {
                    $inside_ids[] = $row['target_id'];
                }

                $data = [];
                if (!empty($inside_ids)) {
                    $id_list = implode(',', array_map('intval', $inside_ids));
                    $sql_personal = "SELECT * FROM personal WHERE id IN ($id_list) ORDER BY Paterno, Nombres";
                    $result_personal = $conn_personal->query($sql_personal);
                    while ($row = $result_personal->fetch_assoc()) {
                        $data[] = $row;
                    }
                }
            } else {
                // Lógica original para obtener todo el personal
                $result = $conn_personal->query("SELECT * FROM personal ORDER BY Paterno, Nombres");
                $data = [];
                while ($row = $result->fetch_assoc()) {
                    $data[] = $row;
                }
            }
            echo json_encode($data);
            break;

        case 'POST':
            // Verificar si es una importación masiva
            if (isset($_GET['action']) && $_GET['action'] === 'import') {
                $data = json_decode(file_get_contents('php://input'), true);
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
                $conn_personal->begin_transaction();

                try {
                    foreach ($personal_data as $index => $person) {
                        $row_number = $index + 2; // +2 porque Excel comienza en fila 1 y los índices en 0

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
                        $check_stmt = $conn_personal->prepare("SELECT id FROM personal WHERE NrRut = ?");
                        $check_stmt->bind_param("s", $nrRut);
                        $check_stmt->execute();
                        $existing = $check_stmt->get_result()->fetch_assoc();
                        $check_stmt->close();

                        if ($existing) {
                            // Actualizar personal existente - TODOS los campos dinámicamente
                            $update_sql = "UPDATE personal SET ";
                            $update_fields = array();
                            $bind_types = "";
                            $set_parts = array();

                            // Lista de campos que se pueden actualizar (mapeo de nombre a tipo)
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

                            // Procesar cada campo del archivo
                            foreach ($allowed_fields as $field => $type) {
                                if (isset($person[$field]) && $person[$field] !== '') {
                                    $value = $person[$field];

                                    // Normalizar valores según tipo
                                    if ($type === 's') {
                                        // Campos especiales que NO deben ir a mayúsculas
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

                            // Si no hay campos para actualizar, saltar
                            if (empty($set_parts)) {
                                $results['success'][] = [
                                    'row' => $row_number,
                                    'rut' => $nrRut,
                                    'action' => 'sin cambios'
                                ];
                                $results['processed']++;
                                continue;
                            }

                            // Construir SQL final
                            $update_sql .= implode(", ", $set_parts) . " WHERE NrRut=?";
                            $update_fields[] = $nrRut;
                            $bind_types .= "s";

                            $update_stmt = $conn_personal->prepare($update_sql);

                            if ($update_stmt === false) {
                                $results['errors'][] = [
                                    'row' => $row_number,
                                    'message' => 'Error al preparar UPDATE: ' . $conn_personal->error
                                ];
                                $results['processed']++;
                                continue;
                            }

                            // Usar call_user_func_array para bind_param dinámico
                            $params = array(&$bind_types);
                            foreach ($update_fields as &$param) {
                                $params[] = &$param;
                            }
                            call_user_func_array(array($update_stmt, 'bind_param'), $params);

                            if ($update_stmt->execute()) {
                                $results['success'][] = [
                                    'row' => $row_number,
                                    'rut' => $nrRut,
                                    'action' => 'actualizado'
                                ];
                                $results['updated']++;
                            } else {
                                $results['errors'][] = [
                                    'row' => $row_number,
                                    'message' => 'Error al actualizar: ' . $update_stmt->error
                                ];
                            }
                            $update_stmt->close();
                        } else {
                            // Crear nuevo personal - campos básicos obligatorios
                            $insert_stmt = $conn_personal->prepare(
                                "INSERT INTO personal (Grado, Nombres, Paterno, Materno, NrRut, Unidad, Estado, es_residente) VALUES (?, ?, ?, ?, ?, ?, ?, ?)"
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
                    $conn_personal->commit();

                    http_response_code(200);
                    echo json_encode($results);

                } catch (Exception $e) {
                    // Revertir transacción en caso de error
                    $conn_personal->rollback();
                    http_response_code(500);
                    echo json_encode([
                        'error' => 'Error durante la importación: ' . $e->getMessage(),
                        'results' => $results
                    ]);
                }
            } else {
                // Crear un único personal
                $data = json_decode(file_get_contents('php://input'), true);
                // Aquí iría una validación más robusta
                $es_residente = isset($data['es_residente']) && $data['es_residente'] == '1' ? 1 : 0;
                $stmt = $conn_personal->prepare("INSERT INTO personal (Nombres, Paterno, Materno, NrRut, Grado, Unidad, Estado, es_residente) VALUES (?, ?, ?, ?, ?, ?, ?, ?)");
                $stmt->bind_param("ssssssii",
                    $data['Nombres'],
                    $data['Paterno'],
                    $data['Materno'],
                    $data['NrRut'],
                    $data['Grado'],
                    $data['Unidad'],
                    $data['Estado'],
                    $es_residente
                );
                $stmt->execute();
                $data['id'] = $stmt->insert_id;
                $stmt->close();
                http_response_code(201);
                echo json_encode($data);
            }
            break;

        case 'PUT':
            $data = json_decode(file_get_contents('php://input'), true);
            $id = intval($data['id']);

            // Mapeo de datos del JSON a variables locales
            $grado = $data['Grado'] ?? null;
            $nombres = $data['Nombres'] ?? null;
            $paterno = $data['Paterno'] ?? null;
            $materno = $data['Materno'] ?? null;
            $nrRut = $data['NrRut'] ?? null;
            $fechaNacimiento = $data['fechaNacimiento'] ?? null;
            $sexo = $data['sexo'] ?? null;
            $estadoCivil = $data['estadoCivil'] ?? null;
            $nrEmpleado = $data['nrEmpleado'] ?? null;
            $puesto = $data['puesto'] ?? null;
            $especialidadPrimaria = $data['especialidadPrimaria'] ?? null;
            $fechaIngreso = $data['fechaIngreso'] ?? null;
            $fechaPresentacion = $data['fechaPresentacion'] ?? null;
            $unidad = $data['Unidad'] ?? null;
            $unidadEspecifica = $data['unidadEspecifica'] ?? null;
            $categoria = $data['categoria'] ?? null;
            $escalafon = $data['escalafon'] ?? null;
            $trabajoExterno = $data['trabajoExterno'] ?? null;
            $calle = $data['calle'] ?? null;
            $numeroDepto = $data['numeroDepto'] ?? null;
            $poblacionVilla = $data['poblacionVilla'] ?? null;
            $telefonoFijo = $data['telefonoFijo'] ?? null;
            $movil1 = $data['movil1'] ?? null;
            $movil2 = $data['movil2'] ?? null;
            $email1 = $data['email1'] ?? null;
            $email2 = $data['email2'] ?? null;
            $anexo = $data['anexo'] ?? null;
            $foto = $data['foto'] ?? null;
            $prevision = $data['prevision'] ?? null;
            $sistemaSalud = $data['sistemaSalud'] ?? null;
            $regimenMatrimonial = $data['regimenMatrimonial'] ?? null;
            $religion = $data['religion'] ?? null;
            $tipoVivienda = $data['tipoVivienda'] ?? null;
            $nombreConyuge = $data['nombreConyuge'] ?? null;
            $profesionConyuge = $data['profesionConyuge'] ?? null;
            $nombreContactoEmergencia = $data['nombreContactoEmergencia'] ?? null;
            $direccionEmergencia = $data['direccionEmergencia'] ?? null;
            $movilEmergencia = $data['movilEmergencia'] ?? null;
            $estado = isset($data['Estado']) ? intval($data['Estado']) : null;
            $fechaExpiracion = empty($data['fechaExpiracion']) ? null : $data['fechaExpiracion'];
            $accesoPermanente = isset($data['accesoPermanente']) ? intval($data['accesoPermanente']) : null;
            $es_residente = isset($data['es_residente']) ? intval($data['es_residente']) : null;

            $sql = "UPDATE `personal` SET 
                `Grado`=?, `Nombres`=?, `Paterno`=?, `Materno`=?, `NrRut`=?, `fechaNacimiento`=?, `sexo`=?, 
                `estadoCivil`=?, `nrEmpleado`=?, `puesto`=?, `especialidadPrimaria`=?, `fechaIngreso`=?, 
                `fechaPresentacion`=?, `Unidad`=?, `unidadEspecifica`=?, `categoria`=?, `escalafon`=?, 
                `trabajoExterno`=?, `calle`=?, `numeroDepto`=?, `poblacionVilla`=?, `telefonoFijo`=?, 
                `movil1`=?, `movil2`=?, `email1`=?, `email2`=?, `anexo`=?, `foto`=?, `prevision`=?, 
                `sistemaSalud`=?, `regimenMatrimonial`=?, `religion`=?, `tipoVivienda`=?, `nombreConyuge`=?, 
                `profesionConyuge`=?, `nombreContactoEmergencia`=?, `direccionEmergencia`=?, `movilEmergencia`=?, 
                `Estado`=?, `fechaExpiracion`=?, `accesoPermanente`=?, `es_residente`=?
                WHERE `id`=?";

            $stmt = $conn_personal->prepare($sql);
            
            if ($stmt === false) {
                http_response_code(500);
                echo json_encode(['message' => 'Error al preparar la consulta: ' . $conn_personal->error]);
                exit();
            }

            $types = "ssssssssssssssssssssssssssssssssssssssisiii";
            $stmt->bind_param($types, 
                $grado, $nombres, $paterno, $materno, $nrRut, $fechaNacimiento, $sexo, $estadoCivil, 
                $nrEmpleado, $puesto, $especialidadPrimaria, $fechaIngreso, $fechaPresentacion, $unidad, 
                $unidadEspecifica, $categoria, $escalafon, $trabajoExterno, $calle, $numeroDepto, 
                $poblacionVilla, $telefonoFijo, $movil1, $movil2, $email1, $email2, $anexo, $foto, 
                $prevision, $sistemaSalud, $regimenMatrimonial, $religion, $tipoVivienda, $nombreConyuge, 
                $profesionConyuge, $nombreContactoEmergencia, $direccionEmergencia, $movilEmergencia, 
                $estado, $fechaExpiracion, $accesoPermanente, $es_residente, $id
            );

            $stmt->execute();
            $stmt->close();
            echo json_encode($data);
            break;

        case 'DELETE':
            $id = isset($_GET['id']) ? intval($_GET['id']) : null;
            if (!$id) {
                http_response_code(400);
                echo json_encode(['message' => 'ID de personal no especificado.']);
                exit;
            }
            $stmt = $conn_personal->prepare("DELETE FROM personal WHERE id = ?");
            $stmt->bind_param("i", $id);
            $stmt->execute();
            if ($stmt->affected_rows > 0) {
                http_response_code(204); // No Content
            } else {
                http_response_code(404); // Not Found
                echo json_encode(['message' => 'Personal no encontrado.']);
            }
            $stmt->close();
            break;

        default:
            http_response_code(405);
            echo json_encode(['message' => 'Método no permitido']);
            break;
    }
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['message' => 'Error interno del servidor: ' . $e->getMessage()]);
} catch (Error $e) { // For PHP 7+ fatal errors
    http_response_code(500);
    echo json_encode(['message' => 'Error fatal del servidor: ' . $e->getMessage()]);
} finally {
    if (isset($conn_personal) && $conn_personal->ping()) {
        $conn_personal->close();
    }
}
?>
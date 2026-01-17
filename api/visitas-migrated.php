<?php
/**
 * api/visitas-migrated.php
 * CRUD API para gestión de visitas con control de acceso
 *
 * Migración desde visitas.php original:
 * - Config: database/db_acceso.php → config/database.php (DatabaseConfig)
 * - Respuestas: echo json_encode() → ApiResponse::*()
 * - Paginación: GET implementa page/perPage
 * - Estructura: funciones separadas por método HTTP
 *
 * Endpoints:
 * GET    /api/visitas.php                              - Lista visitas con paginación
 * GET    /api/visitas.php?id=1                         - Obtener visita específica
 * GET    /api/visitas.php?search=NOMBRE                - Buscar visita por nombre
 * GET    /api/visitas.php?tipo=Visita                  - Filtrar por tipo (Visita/Familiar)
 * POST   /api/visitas.php                              - Crear visita
 * PUT    /api/visitas.php                              - Actualizar visita
 * PUT    /api/visitas.php?action=toggle_blacklist      - Toggle lista negra
 * DELETE /api/visitas.php?id=1                         - Eliminar visita
 *
 * @version 2.0 (Migrated)
 */

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/core/ResponseHandler.php';
require_once __DIR__ . '/core/AuthMiddleware.php';
require_once __DIR__ . '/core/AuditLogger.php';
require_once __DIR__ . '/core/SecurityHeaders.php';

// Aplicar security headers
SecurityHeaders::applyApiHeaders();

// Manejar preflight CORS
SecurityHeaders::handleCors();

// Verificar autenticación con JWT
try {
    $user = AuthMiddleware::requireAuth();
} catch (Exception $e) {
    ApiResponse::unauthorized($e->getMessage());
}

// Obtener conexiones desde DatabaseConfig singleton
$databaseConfig = DatabaseConfig::getInstance();
$conn_acceso = $databaseConfig->getAccesoConnection();
$conn_personal = $databaseConfig->getPersonalConnection();

if (!$conn_acceso) {
    ApiResponse::serverError('Error conectando a base de datos de acceso');
}

// Obtener método HTTP
$method = $_SERVER['REQUEST_METHOD'];

// Router de métodos HTTP
switch ($method) {
    case 'GET':
        handleGet($conn_acceso, $conn_personal);
        break;
    case 'POST':
        handlePost($conn_acceso, $conn_personal);
        break;
    case 'PUT':
        handlePut($conn_acceso, $conn_personal);
        break;
    case 'DELETE':
        handleDelete($conn_acceso);
        break;
    default:
        ApiResponse::error('Método no permitido', 405);
}

/**
 * Calcula el estado de autorización de una visita
 *
 * Estados:
 * - 'no autorizado': Si está en lista negra
 * - 'autorizado': Si tiene acceso permanente O está en rango de fechas válido
 *
 * @param bool $is_blacklisted Si está en lista negra
 * @param bool $is_permanent Si tiene acceso permanente
 * @param string $start_date_str Fecha de inicio (YYYY-MM-DD)
 * @param string $end_date_str Fecha de expiración (YYYY-MM-DD)
 * @return string 'autorizado' o 'no autorizado'
 */
function calculateVisitaStatus($is_blacklisted, $is_permanent, $start_date_str, $end_date_str)
{
    if ($is_blacklisted) {
        return 'no autorizado';
    }

    if ($is_permanent) {
        return 'autorizado';
    }

    if (empty($start_date_str) || empty($end_date_str)) {
        return 'no autorizado';
    }

    try {
        $start_date = new DateTime($start_date_str);
        $end_date = new DateTime($end_date_str);
        $today = new DateTime('today');
        return ($today >= $start_date && $today <= $end_date) ? 'autorizado' : 'no autorizado';
    } catch (Exception $e) {
        return 'no autorizado';
    }
}

/**
 * Enriquece datos de visita con información de POC/Familiar desde tabla personal
 *
 * @param array $visita Datos de visita
 * @param mysqli $conn_personal Conexión a BD personal
 * @return array Visita con datos enriquecidos
 */
function enrichVisitaWithPersonal(&$visita, $conn_personal)
{
    // Enriquecer POC si existe
    if (!empty($visita['poc_personal_id']) && $conn_personal) {
        $stmt = $conn_personal->prepare(
            "SELECT Grado, Nombres, Paterno, Materno, Unidad, anexo
             FROM personal WHERE id = ?"
        );
        if ($stmt) {
            $stmt->bind_param("i", $visita['poc_personal_id']);
            $stmt->execute();
            $result = $stmt->get_result();
            $person = $result->fetch_assoc();
            $stmt->close();

            if ($person) {
                $visita['poc_nombre'] = trim(
                    ($person['Grado'] ?? '') . ' ' .
                    ($person['Nombres'] ?? '') . ' ' .
                    ($person['Paterno'] ?? '') . ' ' .
                    ($person['Materno'] ?? '')
                );
                $visita['poc_rut'] = $person['NrRut'] ?? null;
                if (empty($visita['poc_unidad'])) {
                    $visita['poc_unidad'] = $person['Unidad'];
                }
                if (empty($visita['poc_anexo'])) {
                    $visita['poc_anexo'] = $person['anexo'];
                }
            }
        }
    }

    // Enriquecer Familiar si existe
    if (!empty($visita['familiar_de_personal_id']) && $conn_personal) {
        $stmt = $conn_personal->prepare(
            "SELECT Grado, Nombres, Paterno, Materno, NrRut
             FROM personal WHERE id = ?"
        );
        if ($stmt) {
            $stmt->bind_param("i", $visita['familiar_de_personal_id']);
            $stmt->execute();
            $result = $stmt->get_result();
            $person = $result->fetch_assoc();
            $stmt->close();

            if ($person) {
                $visita['familiar_nombre'] = trim(
                    ($person['Grado'] ?? '') . ' ' .
                    ($person['Nombres'] ?? '') . ' ' .
                    ($person['Paterno'] ?? '') . ' ' .
                    ($person['Materno'] ?? '')
                );
                $visita['familiar_rut'] = $person['NrRut'] ?? null;
            }
        }
    }

    // Convertir booleanos
    $visita['acceso_permanente'] = (bool)$visita['acceso_permanente'];
    $visita['en_lista_negra'] = (bool)$visita['en_lista_negra'];

    return $visita;
}

/**
 * GET /api/visitas.php
 * Retorna lista paginada de visitas con datos enriquecidos de POC/Familiar
 *
 * Parámetros:
 * - page:    número de página (default: 1)
 * - perPage: registros por página, máx 500 (default: 50)
 * - id:      si se proporciona, devuelve visita específica
 * - search:  búsqueda por nombre/paterno/rut
 * - tipo:    filtrar por tipo (Visita/Familiar)
 * - status:  filtrar por status (autorizado/no autorizado)
 *
 * @param mysqli $conn_acceso Conexión a BD acceso
 * @param mysqli $conn_personal Conexión a BD personal
 */
function handleGet($conn_acceso, $conn_personal)
{
    try {
        // Parámetros de paginación
        $page = isset($_GET['page']) ? max(1, (int)$_GET['page']) : 1;
        $perPage = isset($_GET['perPage']) ? max(1, min(500, (int)$_GET['perPage'])) : 50;
        $offset = ($page - 1) * $perPage;

        // Si se solicita visita específica por ID
        if (isset($_GET['id'])) {
            $id = (int)$_GET['id'];
            $stmt = $conn_acceso->prepare("SELECT * FROM visitas WHERE id = ?");
            if (!$stmt) {
                throw new Exception("Error preparando consulta: " . $conn_acceso->error);
            }

            $stmt->bind_param("i", $id);
            $stmt->execute();
            $result = $stmt->get_result();

            if ($result->num_rows === 0) {
                $stmt->close();
                ApiResponse::notFound('Visita no encontrada');
            }

            $visita = $result->fetch_assoc();
            $stmt->close();

            // Enriquecer con datos de POC/Familiar
            $visita = enrichVisitaWithPersonal($visita, $conn_personal);

            ApiResponse::success($visita);
        }

        // Construir consulta con filtros opcionales
        $whereConditions = [];
        $bind_types = "";
        $bind_params = [];

        // Búsqueda por nombre/paterno/rut
        if (isset($_GET['search']) && !empty($_GET['search'])) {
            $search = '%' . $_GET['search'] . '%';
            $whereConditions[] = "(nombre LIKE ? OR paterno LIKE ? OR rut LIKE ?)";
            $bind_types .= "sss";
            $bind_params[] = &$search;
            $bind_params[] = &$search;
            $bind_params[] = &$search;
        }

        // Filtrar por tipo
        if (isset($_GET['tipo']) && !empty($_GET['tipo'])) {
            $tipo = $_GET['tipo'];
            $whereConditions[] = "tipo = ?";
            $bind_types .= "s";
            $bind_params[] = &$tipo;
        }

        // Filtrar por status
        if (isset($_GET['status']) && !empty($_GET['status'])) {
            $status = $_GET['status'];
            $whereConditions[] = "status = ?";
            $bind_types .= "s";
            $bind_params[] = &$status;
        }

        $whereClause = !empty($whereConditions) ? "WHERE " . implode(" AND ", $whereConditions) : "";

        // Contar total
        $countSql = "SELECT COUNT(*) as total FROM visitas " . $whereClause;
        $countStmt = $conn_acceso->prepare($countSql);

        if (!empty($whereConditions)) {
            array_unshift($bind_params, $bind_types);
            call_user_func_array([$countStmt, 'bind_param'], $bind_params);
        }

        $countStmt->execute();
        $countResult = $countStmt->get_result();
        $total = (int)$countResult->fetch_assoc()['total'];
        $countStmt->close();

        // Obtener página de datos
        $querySql = "SELECT * FROM visitas " . $whereClause . " ORDER BY fecha_inicio DESC LIMIT ? OFFSET ?";
        $stmt = $conn_acceso->prepare($querySql);

        if (!$stmt) {
            throw new Exception("Error preparando consulta: " . $conn_acceso->error);
        }

        // Agregar paginación al binding
        $bind_types .= "ii";
        $bind_params[] = &$perPage;
        $bind_params[] = &$offset;

        if (!empty($whereConditions)) {
            array_unshift($bind_params, $bind_types);
            call_user_func_array([$stmt, 'bind_param'], $bind_params);
        } else {
            $stmt->bind_param("ii", $perPage, $offset);
        }

        $stmt->execute();
        $result = $stmt->get_result();

        $visitas = [];
        while ($row = $result->fetch_assoc()) {
            $row = enrichVisitaWithPersonal($row, $conn_personal);
            $visitas[] = $row;
        }

        $stmt->close();

        // Respuesta paginada
        ApiResponse::paginated($visitas, $page, $perPage, $total);

    } catch (Exception $e) {
        ApiResponse::serverError('Error al obtener visitas: ' . $e->getMessage());
    }
}

/**
 * POST /api/visitas.php
 * Crea una nueva visita
 *
 * Parámetros (JSON):
 * - rut:                    RUT del visitante (requerido)
 * - nombre:                 Nombre del visitante (requerido)
 * - paterno:                Apellido paterno
 * - materno:                Apellido materno
 * - movil:                  Teléfono móvil
 * - tipo:                   'Visita' o 'Familiar' (requerido)
 * - fecha_inicio:           Fecha de inicio (requerido)
 * - fecha_expiracion:       Fecha de expiración (si no es acceso permanente)
 * - acceso_permanente:      bool (opcional)
 * - en_lista_negra:         bool (opcional)
 * - poc_personal_id:        ID de POC (si tipo='Visita')
 * - poc_anexo:              Anexo del POC (opcional)
 * - familiar_de_personal_id: ID de personal (si tipo='Familiar')
 * - familiar_unidad:        Unidad del familiar
 * - familiar_anexo:         Anexo del familiar
 *
 * @param mysqli $conn_acceso Conexión a BD acceso
 * @param mysqli $conn_personal Conexión a BD personal
 */
function handlePost($conn_acceso, $conn_personal)
{
    try {
        $data = json_decode(file_get_contents('php://input'), true);

        // Validar campos requeridos
        if (empty($data['rut']) || empty($data['nombre']) || empty($data['tipo'])) {
            ApiResponse::badRequest('Campos requeridos: rut, nombre, tipo');
        }

        // Normalizar datos
        $rut = strtoupper(trim($data['rut']));
        $nombre = strtoupper(trim($data['nombre']));
        $paterno = isset($data['paterno']) ? strtoupper(trim($data['paterno'])) : '';
        $materno = isset($data['materno']) ? strtoupper(trim($data['materno'])) : '';
        $movil = isset($data['movil']) ? trim($data['movil']) : '';
        $tipo = strtoupper(trim($data['tipo']));

        // Validar tipo
        if ($tipo !== 'VISITA' && $tipo !== 'FAMILIAR') {
            ApiResponse::badRequest('Tipo debe ser "Visita" o "Familiar"');
        }

        // Normalizar fechas y accesos
        $acceso_permanente = !empty($data['acceso_permanente']) ? 1 : 0;
        $en_lista_negra = !empty($data['en_lista_negra']) ? 1 : 0;
        $fecha_inicio = $data['fecha_inicio'] ?? null;
        $fecha_expiracion = $acceso_permanente ? null : ($data['fecha_expiracion'] ?? null);

        // Calcular status
        $status = calculateVisitaStatus($en_lista_negra, $acceso_permanente, $fecha_inicio, $fecha_expiracion);

        // Inicializar variables de POC/Familiar
        $poc_personal_id = null;
        $poc_unidad = null;
        $poc_anexo = null;
        $familiar_de_personal_id = null;
        $familiar_unidad = null;
        $familiar_anexo = null;

        // Procesar POC si es tipo Visita
        if ($tipo === 'VISITA' && !empty($data['poc_personal_id'])) {
            $poc_personal_id = (int)$data['poc_personal_id'];
            $poc_anexo = isset($data['poc_anexo']) ? trim($data['poc_anexo']) : null;

            // Obtener datos de POC desde personal
            $stmt_poc = $conn_personal->prepare("SELECT Unidad, anexo FROM personal WHERE id = ?");
            if ($stmt_poc) {
                $stmt_poc->bind_param("i", $poc_personal_id);
                $stmt_poc->execute();
                $result_poc = $stmt_poc->get_result();
                if ($poc_data = $result_poc->fetch_assoc()) {
                    $poc_unidad = $poc_data['Unidad'];
                    if (empty($poc_anexo)) {
                        $poc_anexo = $poc_data['anexo'];
                    }
                }
                $stmt_poc->close();
            }
        } // Procesar Familiar si es tipo Familiar
        elseif ($tipo === 'FAMILIAR' && !empty($data['familiar_de_personal_id'])) {
            $familiar_de_personal_id = (int)$data['familiar_de_personal_id'];
            $familiar_unidad = isset($data['familiar_unidad']) ? strtoupper(trim($data['familiar_unidad'])) : null;
            $familiar_anexo = isset($data['familiar_anexo']) ? trim($data['familiar_anexo']) : null;
        }

        // Insertar visita
        $stmt = $conn_acceso->prepare(
            "INSERT INTO visitas (rut, nombre, paterno, materno, movil, tipo, fecha_inicio,
             fecha_expiracion, acceso_permanente, en_lista_negra, status, poc_personal_id,
             poc_unidad, poc_anexo, familiar_de_personal_id, familiar_unidad, familiar_anexo)
             VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)"
        );

        if (!$stmt) {
            throw new Exception("Error preparando inserción: " . $conn_acceso->error);
        }

        $stmt->bind_param(
            "ssssssssiisississ",
            $rut, $nombre, $paterno, $materno, $movil, $tipo, $fecha_inicio, $fecha_expiracion,
            $acceso_permanente, $en_lista_negra, $status, $poc_personal_id, $poc_unidad,
            $poc_anexo, $familiar_de_personal_id, $familiar_unidad, $familiar_anexo
        );

        if (!$stmt->execute()) {
            throw new Exception("Error ejecutando inserción: " . $stmt->error);
        }

        $id = $stmt->insert_id;
        $stmt->close();

        // Retornar visita creada
        $response = [
            'id' => $id,
            'rut' => $rut,
            'nombre' => $nombre,
            'paterno' => $paterno,
            'materno' => $materno,
            'movil' => $movil,
            'tipo' => $tipo,
            'fecha_inicio' => $fecha_inicio,
            'fecha_expiracion' => $fecha_expiracion,
            'acceso_permanente' => (bool)$acceso_permanente,
            'en_lista_negra' => (bool)$en_lista_negra,
            'status' => $status,
            'poc_personal_id' => $poc_personal_id,
            'poc_unidad' => $poc_unidad,
            'poc_anexo' => $poc_anexo,
            'familiar_de_personal_id' => $familiar_de_personal_id,
            'familiar_unidad' => $familiar_unidad,
            'familiar_anexo' => $familiar_anexo
        ];

        ApiResponse::created($response, ['id' => $id]);

    } catch (Exception $e) {
        ApiResponse::serverError('Error al crear visita: ' . $e->getMessage());
    }
}

/**
 * PUT /api/visitas.php
 * Actualiza una visita existente o toggle lista negra
 *
 * Acciones especiales:
 * - PUT ?action=toggle_blacklist&id=X - Toggle lista negra y recalcular status
 * - PUT (sin action) - Actualización general de campos
 *
 * @param mysqli $conn_acceso Conexión a BD acceso
 * @param mysqli $conn_personal Conexión a BD personal
 */
function handlePut($conn_acceso, $conn_personal)
{
    try {
        // Acción especial: toggle lista negra
        if (isset($_GET['action']) && $_GET['action'] === 'toggle_blacklist') {
            $id = isset($_GET['id']) ? (int)$_GET['id'] : null;
            $data = json_decode(file_get_contents('php://input'), true);
            $en_lista_negra = isset($data['en_lista_negra']) ? ($data['en_lista_negra'] ? 1 : 0) : null;

            if (!$id || $en_lista_negra === null) {
                ApiResponse::badRequest('Datos incompletos para toggle blacklist');
            }

            // Obtener datos actuales
            $stmt_select = $conn_acceso->prepare(
                "SELECT acceso_permanente, fecha_inicio, fecha_expiracion FROM visitas WHERE id = ?"
            );
            if (!$stmt_select) {
                throw new Exception("Error preparando consulta: " . $conn_acceso->error);
            }

            $stmt_select->bind_param("i", $id);
            $stmt_select->execute();
            $result_select = $stmt_select->get_result();
            $current_visita = $result_select->fetch_assoc();
            $stmt_select->close();

            if (!$current_visita) {
                ApiResponse::notFound('Visita no encontrada');
            }

            // Calcular nuevo status
            $new_status = calculateVisitaStatus(
                $en_lista_negra,
                $current_visita['acceso_permanente'],
                $current_visita['fecha_inicio'],
                $current_visita['fecha_expiracion']
            );

            // Actualizar lista negra y status
            $stmt = $conn_acceso->prepare("UPDATE visitas SET en_lista_negra = ?, status = ? WHERE id = ?");
            if (!$stmt) {
                throw new Exception("Error preparando update: " . $conn_acceso->error);
            }

            $stmt->bind_param("isi", $en_lista_negra, $new_status, $id);

            if (!$stmt->execute()) {
                throw new Exception("Error ejecutando update: " . $stmt->error);
            }

            $stmt->close();

            $response = [
                'id' => $id,
                'en_lista_negra' => (bool)$en_lista_negra,
                'status' => $new_status
            ];

            ApiResponse::success($response);
        }

        // Actualización general de visita
        $data = json_decode(file_get_contents('php://input'), true);
        $id = $data['id'] ?? null;

        if (!$id) {
            ApiResponse::badRequest('ID de visita requerido');
        }

        $id = (int)$id;

        // Verificar que existe
        $checkStmt = $conn_acceso->prepare("SELECT id FROM visitas WHERE id = ?");
        if (!$checkStmt) {
            throw new Exception("Error preparando consulta: " . $conn_acceso->error);
        }

        $checkStmt->bind_param("i", $id);
        $checkStmt->execute();
        $checkResult = $checkStmt->get_result();

        if ($checkResult->num_rows === 0) {
            $checkStmt->close();
            ApiResponse::notFound('Visita no encontrada');
        }

        $checkStmt->close();

        // Normalizar datos
        $acceso_permanente = !empty($data['acceso_permanente']) ? 1 : 0;
        $en_lista_negra = !empty($data['en_lista_negra']) ? 1 : 0;
        $fecha_inicio = $data['fecha_inicio'] ?? null;
        $fecha_expiracion = $acceso_permanente ? null : ($data['fecha_expiracion'] ?? null);
        $status = calculateVisitaStatus($en_lista_negra, $acceso_permanente, $fecha_inicio, $fecha_expiracion);

        // Procesar POC/Familiar
        $tipo = strtoupper(trim($data['tipo'] ?? 'VISITA'));
        $poc_personal_id = null;
        $poc_unidad = null;
        $poc_anexo = null;
        $familiar_de_personal_id = null;
        $familiar_unidad = null;
        $familiar_anexo = null;

        if ($tipo === 'VISITA' && !empty($data['poc_personal_id'])) {
            $poc_personal_id = (int)$data['poc_personal_id'];
            $poc_anexo = isset($data['poc_anexo']) ? trim($data['poc_anexo']) : null;

            $stmt_poc = $conn_personal->prepare("SELECT Unidad, anexo FROM personal WHERE id = ?");
            if ($stmt_poc) {
                $stmt_poc->bind_param("i", $poc_personal_id);
                $stmt_poc->execute();
                $result_poc = $stmt_poc->get_result();
                if ($poc_data = $result_poc->fetch_assoc()) {
                    $poc_unidad = $poc_data['Unidad'];
                    if (empty($poc_anexo)) {
                        $poc_anexo = $poc_data['anexo'];
                    }
                }
                $stmt_poc->close();
            }
        } elseif ($tipo === 'FAMILIAR' && !empty($data['familiar_de_personal_id'])) {
            $familiar_de_personal_id = (int)$data['familiar_de_personal_id'];
            $familiar_unidad = isset($data['familiar_unidad']) ? strtoupper(trim($data['familiar_unidad'])) : null;
            $familiar_anexo = isset($data['familiar_anexo']) ? trim($data['familiar_anexo']) : null;
        }

        // Construir UPDATE dinámico
        $rut = strtoupper(trim($data['rut'] ?? ''));
        $nombre = strtoupper(trim($data['nombre'] ?? ''));
        $paterno = isset($data['paterno']) ? strtoupper(trim($data['paterno'])) : '';
        $materno = isset($data['materno']) ? strtoupper(trim($data['materno'])) : '';
        $movil = isset($data['movil']) ? trim($data['movil']) : '';

        $stmt = $conn_acceso->prepare(
            "UPDATE visitas SET rut=?, nombre=?, paterno=?, materno=?, movil=?, tipo=?,
             fecha_inicio=?, fecha_expiracion=?, acceso_permanente=?, en_lista_negra=?,
             status=?, poc_personal_id=?, poc_unidad=?, poc_anexo=?, familiar_de_personal_id=?,
             familiar_unidad=?, familiar_anexo=? WHERE id=?"
        );

        if (!$stmt) {
            throw new Exception("Error preparando update: " . $conn_acceso->error);
        }

        $stmt->bind_param(
            "ssssssssiisississi",
            $rut, $nombre, $paterno, $materno, $movil, $tipo, $fecha_inicio, $fecha_expiracion,
            $acceso_permanente, $en_lista_negra, $status, $poc_personal_id, $poc_unidad,
            $poc_anexo, $familiar_de_personal_id, $familiar_unidad, $familiar_anexo, $id
        );

        if (!$stmt->execute()) {
            throw new Exception("Error ejecutando update: " . $stmt->error);
        }

        $stmt->close();

        $response = $data;
        $response['status'] = $status;
        ApiResponse::success($response);

    } catch (Exception $e) {
        ApiResponse::serverError('Error al actualizar visita: ' . $e->getMessage());
    }
}

/**
 * DELETE /api/visitas.php?id=1
 * Elimina una visita
 *
 * @param mysqli $conn_acceso Conexión a BD acceso
 */
function handleDelete($conn_acceso)
{
    try {
        $id = isset($_GET['id']) ? (int)$_GET['id'] : null;

        if (!$id) {
            ApiResponse::badRequest('ID de visita requerido');
        }

        // Verificar que existe
        $checkStmt = $conn_acceso->prepare("SELECT id FROM visitas WHERE id = ?");
        if (!$checkStmt) {
            throw new Exception("Error preparando consulta: " . $conn_acceso->error);
        }

        $checkStmt->bind_param("i", $id);
        $checkStmt->execute();
        $checkResult = $checkStmt->get_result();

        if ($checkResult->num_rows === 0) {
            $checkStmt->close();
            ApiResponse::notFound('Visita no encontrada');
        }

        $checkStmt->close();

        // Ejecutar DELETE
        $stmt = $conn_acceso->prepare("DELETE FROM visitas WHERE id = ?");
        if (!$stmt) {
            throw new Exception("Error preparando delete: " . $conn_acceso->error);
        }

        $stmt->bind_param("i", $id);

        if (!$stmt->execute()) {
            throw new Exception("Error ejecutando delete: " . $stmt->error);
        }

        $affected = $stmt->affected_rows;
        $stmt->close();

        if ($affected === 0) {
            ApiResponse::serverError('No se pudo eliminar la visita');
        }

        // Respuesta 204 No Content para DELETE exitoso
        ApiResponse::noContent();

    } catch (Exception $e) {
        ApiResponse::serverError('Error al eliminar visita: ' . $e->getMessage());
    }
}

?>

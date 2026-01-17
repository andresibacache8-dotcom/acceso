<?php
/**
 * api/empresas-migrated.php
 * CRUD API para gestión de empresas
 *
 * Migración desde empresas.php original:
 * - Config: database/db_acceso.php → config/database.php (DatabaseConfig)
 * - Respuestas: echo json_encode() → ApiResponse::*()
 * - Paginación: GET implementa page/perPage
 * - Estructura: funciones separadas por método HTTP
 *
 * Endpoints:
 * GET    /api/empresas.php              - Lista empresas con paginación
 * GET    /api/empresas.php?id=1         - Obtener empresa específica
 * POST   /api/empresas.php              - Crear empresa
 * PUT    /api/empresas.php              - Actualizar empresa
 * DELETE /api/empresas.php?id=1         - Eliminar empresa
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
    AuthMiddleware::requireAuth();
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
        handlePost($conn_acceso);
        break;
    case 'PUT':
        handlePut($conn_acceso);
        break;
    case 'DELETE':
        handleDelete($conn_acceso);
        break;
    default:
        ApiResponse::error('Método no permitido', 405);
}

/**
 * GET /api/empresas.php
 * Retorna lista paginada de empresas con información de POC desde tabla personal
 *
 * Parámetros:
 * - page:    número de página (default: 1)
 * - perPage: registros por página, máx 500 (default: 50)
 * - id:      si se proporciona, devuelve empresa específica
 * - search:  búsqueda por nombre (opcional)
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

        // Si se solicita empresa específica por ID
        if (isset($_GET['id'])) {
            $id = (int)$_GET['id'];
            $stmt = $conn_acceso->prepare("SELECT * FROM empresas WHERE id = ?");
            if (!$stmt) {
                throw new Exception("Error preparando consulta: " . $conn_acceso->error);
            }

            $stmt->bind_param("i", $id);
            $stmt->execute();
            $result = $stmt->get_result();

            if ($result->num_rows === 0) {
                $stmt->close();
                ApiResponse::notFound('Empresa no encontrada');
            }

            $empresa = $result->fetch_assoc();
            $stmt->close();

            // Enriquecer con datos de POC si existe
            $empresa = enrichEmpresaWithPOC($empresa, $conn_personal);

            ApiResponse::success($empresa);
        }

        // Búsqueda opcional
        $searchQuery = "";
        if (isset($_GET['search']) && !empty($_GET['search'])) {
            $search = '%' . $_GET['search'] . '%';
            $searchQuery = " WHERE nombre LIKE ?";
        }

        // Contar total
        $countSql = "SELECT COUNT(*) as total FROM empresas" . $searchQuery;
        $countStmt = $conn_acceso->prepare($countSql);

        if ($searchQuery && isset($search)) {
            $countStmt->bind_param("s", $search);
        }

        $countStmt->execute();
        $countResult = $countStmt->get_result();
        $total = (int)$countResult->fetch_assoc()['total'];
        $countStmt->close();

        // Obtener página de datos
        $querySql = "SELECT * FROM empresas" . $searchQuery . " ORDER BY nombre ASC LIMIT ? OFFSET ?";
        $stmt = $conn_acceso->prepare($querySql);

        if (!$stmt) {
            throw new Exception("Error preparando consulta: " . $conn_acceso->error);
        }

        // Binding dinámico según si hay búsqueda
        if ($searchQuery && isset($search)) {
            $stmt->bind_param("sii", $search, $perPage, $offset);
        } else {
            $stmt->bind_param("ii", $perPage, $offset);
        }

        $stmt->execute();
        $result = $stmt->get_result();

        $empresas = [];
        while ($row = $result->fetch_assoc()) {
            $row['id'] = (int)$row['id'];
            // Enriquecer empresa con datos de POC
            $row = enrichEmpresaWithPOC($row, $conn_personal);
            $empresas[] = $row;
        }

        $stmt->close();

        // Respuesta paginada
        ApiResponse::paginated($empresas, $page, $perPage, $total);

    } catch (Exception $e) {
        ApiResponse::serverError('Error al obtener empresas: ' . $e->getMessage());
    }
}

/**
 * POST /api/empresas.php
 * Crea una nueva empresa
 *
 * Parámetros (JSON):
 * - nombre:      nombre de la empresa (requerido)
 * - unidad_poc:  unidad del POC (requerido)
 * - poc_rut:     RUT del POC (requerido)
 * - poc_nombre:  nombre del POC (opcional, se obtiene de personal si no se proporciona)
 * - poc_anexo:   anexo del POC (opcional)
 *
 * @param mysqli $conn_acceso Conexión a BD acceso
 */
function handlePost($conn_acceso)
{
    try {
        $data = json_decode(file_get_contents('php://input'), true);

        // Validar campos requeridos
        if (empty($data['nombre']) || empty($data['unidad_poc']) || empty($data['poc_rut'])) {
            ApiResponse::badRequest('Campos requeridos: nombre, unidad_poc, poc_rut');
        }

        // Normalizar datos
        $nombre = strtoupper(trim($data['nombre']));
        $unidad_poc = strtoupper(trim($data['unidad_poc']));
        $poc_rut = strtoupper(trim($data['poc_rut']));
        $poc_nombre = isset($data['poc_nombre']) ? strtoupper(trim($data['poc_nombre'])) : '';
        $poc_anexo = isset($data['poc_anexo']) ? strtoupper(trim($data['poc_anexo'])) : '';

        // Preparar inserción
        $stmt = $conn_acceso->prepare("INSERT INTO empresas (nombre, unidad_poc, poc_rut, poc_nombre, poc_anexo)
                                       VALUES (?, ?, ?, ?, ?)");

        if (!$stmt) {
            throw new Exception("Error preparando consulta: " . $conn_acceso->error);
        }

        $stmt->bind_param("sssss", $nombre, $unidad_poc, $poc_rut, $poc_nombre, $poc_anexo);

        if (!$stmt->execute()) {
            throw new Exception("Error ejecutando inserción: " . $stmt->error);
        }

        $id = $stmt->insert_id;
        $stmt->close();

        // Retornar empresa creada
        $response = [
            'id' => $id,
            'nombre' => $nombre,
            'unidad_poc' => $unidad_poc,
            'poc_rut' => $poc_rut,
            'poc_nombre' => $poc_nombre,
            'poc_anexo' => $poc_anexo
        ];

        ApiResponse::created($response, ['id' => $id]);

    } catch (Exception $e) {
        ApiResponse::serverError('Error al crear empresa: ' . $e->getMessage());
    }
}

/**
 * PUT /api/empresas.php
 * Actualiza una empresa existente
 *
 * Parámetros (JSON):
 * - id:          ID de la empresa (requerido)
 * - nombre:      nombre de la empresa
 * - unidad_poc:  unidad del POC
 * - poc_rut:     RUT del POC
 * - poc_nombre:  nombre del POC
 * - poc_anexo:   anexo del POC
 *
 * @param mysqli $conn_acceso Conexión a BD acceso
 */
function handlePut($conn_acceso)
{
    try {
        $data = json_decode(file_get_contents('php://input'), true);

        // Validar que existe el ID
        if (empty($data['id'])) {
            ApiResponse::badRequest('ID de empresa requerido');
        }

        $id = (int)$data['id'];

        // Verificar que la empresa existe
        $checkStmt = $conn_acceso->prepare("SELECT id FROM empresas WHERE id = ?");
        if (!$checkStmt) {
            throw new Exception("Error preparando consulta: " . $conn_acceso->error);
        }

        $checkStmt->bind_param("i", $id);
        $checkStmt->execute();
        $checkResult = $checkStmt->get_result();

        if ($checkResult->num_rows === 0) {
            $checkStmt->close();
            ApiResponse::notFound('Empresa no encontrada');
        }

        $checkStmt->close();

        // Construir dinámicamente la consulta UPDATE con campos disponibles
        $allowed_fields = [
            'nombre' => 's',
            'unidad_poc' => 's',
            'poc_rut' => 's',
            'poc_nombre' => 's',
            'poc_anexo' => 's'
        ];

        $set_parts = [];
        $update_fields = [];
        $bind_types = "";

        foreach ($allowed_fields as $field => $type) {
            if (isset($data[$field]) && $data[$field] !== '') {
                $value = strtoupper(trim($data[$field]));
                $set_parts[] = "$field = ?";
                $update_fields[] = $value;
                $bind_types .= $type;
            }
        }

        if (empty($set_parts)) {
            ApiResponse::badRequest('No hay campos para actualizar');
        }

        // Agregar ID al final del binding
        $update_fields[] = $id;
        $bind_types .= "i";

        // Ejecutar UPDATE dinámico
        $updateSql = "UPDATE empresas SET " . implode(", ", $set_parts) . " WHERE id = ?";
        $updateStmt = $conn_acceso->prepare($updateSql);

        if (!$updateStmt) {
            throw new Exception("Error preparando consulta: " . $conn_acceso->error);
        }

        // Binding dinámico
        $params = [&$bind_types];
        foreach ($update_fields as &$param) {
            $params[] = &$param;
        }
        call_user_func_array([$updateStmt, 'bind_param'], $params);

        if (!$updateStmt->execute()) {
            throw new Exception("Error ejecutando update: " . $updateStmt->error);
        }

        $updateStmt->close();

        // Retornar empresa actualizada
        ApiResponse::success($data);

    } catch (Exception $e) {
        ApiResponse::serverError('Error al actualizar empresa: ' . $e->getMessage());
    }
}

/**
 * DELETE /api/empresas.php?id=1
 * Elimina una empresa (borrado físico, sin status soft delete)
 *
 * Parámetros:
 * - id: ID de la empresa a eliminar (requerido)
 *
 * @param mysqli $conn_acceso Conexión a BD acceso
 */
function handleDelete($conn_acceso)
{
    try {
        // Obtener ID
        $id = isset($_GET['id']) ? (int)$_GET['id'] : null;

        if (!$id) {
            ApiResponse::badRequest('ID de empresa no proporcionado');
        }

        // Verificar que existe
        $checkStmt = $conn_acceso->prepare("SELECT id FROM empresas WHERE id = ?");
        if (!$checkStmt) {
            throw new Exception("Error preparando consulta: " . $conn_acceso->error);
        }

        $checkStmt->bind_param("i", $id);
        $checkStmt->execute();
        $checkResult = $checkStmt->get_result();

        if ($checkResult->num_rows === 0) {
            $checkStmt->close();
            ApiResponse::notFound('Empresa no encontrada');
        }

        $checkStmt->close();

        // Ejecutar DELETE
        $deleteStmt = $conn_acceso->prepare("DELETE FROM empresas WHERE id = ?");
        if (!$deleteStmt) {
            throw new Exception("Error preparando consulta: " . $conn_acceso->error);
        }

        $deleteStmt->bind_param("i", $id);

        if (!$deleteStmt->execute()) {
            throw new Exception("Error ejecutando delete: " . $deleteStmt->error);
        }

        $affected = $deleteStmt->affected_rows;
        $deleteStmt->close();

        if ($affected === 0) {
            ApiResponse::serverError('No se pudo eliminar la empresa');
        }

        // Respuesta 204 No Content para DELETE exitoso
        ApiResponse::noContent();

    } catch (Exception $e) {
        ApiResponse::serverError('Error al eliminar empresa: ' . $e->getMessage());
    }
}

/**
 * Enriquece datos de empresa con información del POC desde tabla personal
 *
 * @param array $empresa Datos de empresa
 * @param mysqli $conn_personal Conexión a BD personal
 * @return array Empresa con datos enriquecidos
 */
function enrichEmpresaWithPOC($empresa, $conn_personal)
{
    if (!empty($empresa['poc_rut']) && $conn_personal) {
        $stmt = $conn_personal->prepare("SELECT Grado, Nombres, Paterno, anexo FROM personal WHERE NrRut = ?");
        if ($stmt) {
            $stmt->bind_param("s", $empresa['poc_rut']);
            $stmt->execute();
            $result = $stmt->get_result();
            $person = $result->fetch_assoc();
            $stmt->close();

            if ($person) {
                // Si no hay nombre de POC guardado, construir desde personal
                if (empty($empresa['poc_nombre'])) {
                    $empresa['poc_nombre'] = trim(
                        ($person['Grado'] ?? '') . ' ' .
                        ($person['Nombres'] ?? '') . ' ' .
                        ($person['Paterno'] ?? '')
                    );
                }

                // Si no hay anexo guardado, usar el de personal
                if (empty($empresa['poc_anexo']) && !empty($person['anexo'])) {
                    $empresa['poc_anexo'] = $person['anexo'];
                }
            }
        }
    }

    return $empresa;
}

?>

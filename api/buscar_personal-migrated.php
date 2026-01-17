<?php
/**
 * api/buscar_personal-migrated.php
 * API de búsqueda uniforme de personal en múltiples tablas
 *
 * Migración desde buscar_personal.php original:
 * - Config: database/db_personal.php + db_acceso.php → config/database.php
 * - Respuestas: echo json_encode() → ApiResponse::*()
 * - Estructura: GET con parámetros query y tipo
 *
 * Endpoints:
 * GET /api/buscar_personal.php?query=TEST&tipo=FISCAL
 * GET /api/buscar_personal.php?query=TEST&tipo=FUNCIONARIO
 * GET /api/buscar_personal.php?query=TEST&tipo=RESIDENTE
 * GET /api/buscar_personal.php?query=TEST&tipo=EMPRESA
 * GET /api/buscar_personal.php?query=TEST&tipo=VISITA
 *
 * Tipos soportados: FISCAL, FUNCIONARIO, RESIDENTE, EMPRESA, VISITA
 *
 * @version 2.0 (Migrated)
 */

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/core/ResponseHandler.php';

// Headers
header('Content-Type: application/json');

// Obtener conexiones desde DatabaseConfig singleton
$databaseConfig = DatabaseConfig::getInstance();
$conn_personal = $databaseConfig->getPersonalConnection();
$conn_acceso = $databaseConfig->getAccesoConnection();

if (!$conn_personal || !$conn_acceso) {
    ApiResponse::serverError('Error conectando a base de datos');
}

try {
    // Validar parámetros requeridos
    if (!isset($_GET['query']) || !isset($_GET['tipo'])) {
        ApiResponse::badRequest('Parámetros requeridos: query y tipo');
    }

    $query = trim($_GET['query']);
    $tipo = strtoupper(trim($_GET['tipo']));

    // Validar que query no esté vacío
    if (empty($query)) {
        ApiResponse::badRequest('El parámetro query no puede estar vacío');
    }

    $searchTerm = "%{$query}%";
    $stmt = null;
    $conn = null;
    $resultados = [];

    // Seleccionar BD y consulta según tipo
    switch ($tipo) {
        case 'FISCAL':
        case 'FUNCIONARIO':
        case 'RESIDENTE':
            // Búsqueda en tabla personal
            $sql = "SELECT id, NrRut, Grado, Nombres, Paterno, Materno, Unidad
                    FROM personal
                    WHERE (NrRut LIKE ? OR Nombres LIKE ? OR Paterno LIKE ? OR Materno LIKE ?)";

            // Si es RESIDENTE, agregar condición
            if ($tipo === 'RESIDENTE') {
                $sql .= " AND es_residente = 1";
            }

            $sql .= " LIMIT 10";
            $stmt = $conn_personal->prepare($sql);
            $conn = $conn_personal;

            if (!$stmt) {
                throw new Exception("Error preparando búsqueda personal: " . $conn_personal->error);
            }

            $stmt->bind_param("ssss", $searchTerm, $searchTerm, $searchTerm, $searchTerm);
            break;

        case 'EMPRESA':
            // Búsqueda en tabla empresa_empleados con JOIN a empresas
            $sql = "SELECT ee.id, ee.rut AS NrRut, ee.nombre AS Nombres,
                           ee.paterno AS Paterno, ee.materno AS Materno,
                           e.nombre AS Unidad
                    FROM empresa_empleados ee
                    JOIN empresas e ON ee.empresa_id = e.id
                    WHERE (ee.rut LIKE ? OR ee.nombre LIKE ? OR ee.paterno LIKE ? OR ee.materno LIKE ?)
                    LIMIT 10";
            $stmt = $conn_acceso->prepare($sql);
            $conn = $conn_acceso;

            if (!$stmt) {
                throw new Exception("Error preparando búsqueda empresa: " . $conn_acceso->error);
            }

            $stmt->bind_param("ssss", $searchTerm, $searchTerm, $searchTerm, $searchTerm);
            break;

        case 'VISITA':
            // Búsqueda en tabla visitas (excluye lista negra)
            $sql = "SELECT id, rut AS NrRut, '' AS Grado, nombre AS Nombres,
                           paterno AS Paterno, materno AS Materno,
                           'Visita' AS Unidad
                    FROM visitas
                    WHERE (rut LIKE ? OR nombre LIKE ? OR paterno LIKE ? OR materno LIKE ?)
                          AND en_lista_negra = 0
                    LIMIT 10";
            $stmt = $conn_acceso->prepare($sql);
            $conn = $conn_acceso;

            if (!$stmt) {
                throw new Exception("Error preparando búsqueda visitas: " . $conn_acceso->error);
            }

            $stmt->bind_param("ssss", $searchTerm, $searchTerm, $searchTerm, $searchTerm);
            break;

        default:
            ApiResponse::badRequest('Tipo de búsqueda no válido. Tipos: FISCAL, FUNCIONARIO, RESIDENTE, EMPRESA, VISITA');
    }

    // Ejecutar búsqueda
    if (!$stmt || !$stmt->execute()) {
        $error_msg = $stmt ? $stmt->error : ($conn_acceso->error ?: $conn_personal->error);
        throw new Exception("Error ejecutando búsqueda: " . $error_msg);
    }

    $result = $stmt->get_result();

    // Procesar resultados
    while ($row = $result->fetch_assoc()) {
        $resultados[] = [
            'id' => (int)$row['id'],
            'NrRut' => $row['NrRut'],
            'Grado' => $row['Grado'] ?? '',
            'Nombres' => $row['Nombres'],
            'Paterno' => $row['Paterno'],
            'Materno' => $row['Materno'] ?? '',
            'Unidad' => $row['Unidad'] ?? ''
        ];
    }

    $stmt->close();

    // Retornar resultados o 404 si no hay coincidencias
    if (count($resultados) > 0) {
        ApiResponse::success($resultados);
    } else {
        ApiResponse::notFound('No se encontraron resultados para la búsqueda');
    }

} catch (Exception $e) {
    ApiResponse::serverError('Error en búsqueda: ' . $e->getMessage());
}

?>

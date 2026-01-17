<?php
/**
 * api/vehiculo_historial-migrated.php
 *
 * API de Historial de Vehículos - Versión Migrada
 *
 * Obtiene el historial de cambios de un vehículo específico con:
 * - Detalles de cambios (creación, actualización, cambio de propietario, eliminación)
 * - Propietarios anteriores y nuevos (personal, empleados, visitas)
 * - Usuario que realizó el cambio
 * - Datos actuales del vehículo
 *
 * GET /api/vehiculo_historial-migrated.php?vehiculo_id=1
 *
 * Cambios principales:
 * - Usa config/database.php en lugar de database/db_*.php
 * - Usa ApiResponse para respuestas estandarizadas
 * - Autenticación requerida (requiere sesión válida)
 * - Helpers refactorizadas para lógica de enriquecimiento
 * - Mantenida toda funcionalidad original
 */

require_once '../config/database.php';
require_once '../api/core/ResponseHandler.php';

// ============================================================================
// VALIDACIÓN Y AUTENTICACIÓN
// ============================================================================

if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
    ApiResponse::methodNotAllowed();
}

// Verificar autenticación
if (!isset($_SESSION['logged_in']) || $_SESSION['logged_in'] !== true) {
    ApiResponse::unauthorized('No autorizado');
}

$vehiculo_id = $_GET['vehiculo_id'] ?? null;
if (empty($vehiculo_id)) {
    ApiResponse::badRequest('Parámetro requerido: vehiculo_id');
}

// ============================================================================
// FUNCIONES HELPER - ENRIQUECIMIENTO DE DATOS
// ============================================================================

/**
 * Traduce tipo de cambio a texto descriptivo
 */
function traducirTipoCambio($tipo_cambio) {
    if (empty($tipo_cambio)) {
        return 'Desconocido';
    }

    $translations = [
        'creacion' => 'Creación de vehículo',
        'actualizacion' => 'Actualización de datos',
        'cambio_propietario' => 'Cambio de propietario',
        'eliminacion' => 'Eliminación de vehículo'
    ];

    $tipo_lower = strtolower($tipo_cambio);
    if (isset($translations[$tipo_lower])) {
        return $translations[$tipo_lower];
    }

    return ucfirst(str_replace('_', ' ', $tipo_cambio));
}

/**
 * Formatea un registro de historial con detalles enriquecidos
 */
function formatearRegistroHistorial($row) {
    $row['fecha_cambio_formateada'] = date('d/m/Y H:i:s', strtotime($row['fecha_cambio']));

    // Decodificar detalles si existen
    if (!empty($row['detalles'])) {
        $row['detalles_obj'] = json_decode($row['detalles'], true);
    }

    // Traducir tipo de cambio
    $row['tipo_cambio_texto'] = traducirTipoCambio($row['tipo_cambio']);

    return $row;
}

/**
 * Obtiene el historial de un vehículo específico
 */
function obtenerHistorialVehiculo($conn_acceso, $vehiculo_id) {
    $sql = "SELECT vh.*,
            u.username as usuario_nombre,
            COALESCE(CONCAT_WS(' ', p_ant.Grado, p_ant.Nombres, p_ant.Paterno),
                     CONCAT_WS(' ', ee_ant.nombre, ee_ant.paterno, ee_ant.materno),
                     CONCAT_WS(' ', vis_ant.nombre, vis_ant.paterno, vis_ant.materno)) as propietario_anterior_nombre,
            COALESCE(CONCAT_WS(' ', p_nue.Grado, p_nue.Nombres, p_nue.Paterno),
                     CONCAT_WS(' ', ee_nue.nombre, ee_nue.paterno, ee_nue.materno),
                     CONCAT_WS(' ', vis_nue.nombre, vis_nue.paterno, vis_nue.materno)) as propietario_nuevo_nombre
            FROM acceso_pro_db.vehiculo_historial vh
            LEFT JOIN acceso_pro_db.users u ON vh.usuario_id = u.id
            LEFT JOIN personal_db.personal p_ant ON vh.asociado_id_anterior = p_ant.id
            LEFT JOIN acceso_pro_db.empresa_empleados ee_ant ON vh.asociado_id_anterior = ee_ant.id
            LEFT JOIN acceso_pro_db.visitas vis_ant ON vh.asociado_id_anterior = vis_ant.id
            LEFT JOIN personal_db.personal p_nue ON vh.asociado_id_nuevo = p_nue.id
            LEFT JOIN acceso_pro_db.empresa_empleados ee_nue ON vh.asociado_id_nuevo = ee_nue.id
            LEFT JOIN acceso_pro_db.visitas vis_nue ON vh.asociado_id_nuevo = vis_nue.id
            WHERE vh.vehiculo_id = ?
            ORDER BY vh.fecha_cambio DESC";

    $stmt = $conn_acceso->prepare($sql);
    if (!$stmt) {
        throw new Exception('Error SQL: ' . $conn_acceso->error);
    }

    $stmt->bind_param('i', $vehiculo_id);
    $stmt->execute();
    $result = $stmt->get_result();

    $historial = [];
    while ($row = $result->fetch_assoc()) {
        $historial[] = formatearRegistroHistorial($row);
    }

    $stmt->close();
    return $historial;
}

/**
 * Obtiene los datos actuales de un vehículo con propietario enriquecido
 */
function obtenerVehiculoActual($conn_acceso, $vehiculo_id) {
    $sql = "SELECT v.*,
            CASE
                WHEN v.asociado_tipo IN ('PERSONAL', 'FUNCIONARIO', 'RESIDENTE', 'FISCAL')
                    THEN TRIM(CONCAT_WS(' ', p.Grado, p.Nombres, p.Paterno))
                WHEN v.asociado_tipo IN ('EMPLEADO', 'EMPRESA')
                    THEN TRIM(CONCAT_WS(' ', ee.nombre, ee.paterno, ee.materno))
                WHEN v.asociado_tipo = 'VISITA'
                    THEN TRIM(CONCAT_WS(' ', vis.nombre, vis.paterno, vis.materno))
                ELSE 'N/A'
            END as propietario_actual_nombre
            FROM acceso_pro_db.vehiculos v
            LEFT JOIN personal_db.personal p ON v.asociado_id = p.id
                AND v.asociado_tipo IN ('PERSONAL', 'FUNCIONARIO', 'RESIDENTE', 'FISCAL')
            LEFT JOIN acceso_pro_db.empresa_empleados ee ON v.asociado_id = ee.id
                AND v.asociado_tipo IN ('EMPLEADO', 'EMPRESA')
            LEFT JOIN acceso_pro_db.visitas vis ON v.asociado_id = vis.id
                AND v.asociado_tipo = 'VISITA'
            WHERE v.id = ?";

    $stmt = $conn_acceso->prepare($sql);
    if (!$stmt) {
        throw new Exception('Error SQL: ' . $conn_acceso->error);
    }

    $stmt->bind_param('i', $vehiculo_id);
    $stmt->execute();
    $result = $stmt->get_result();

    $vehiculo = $result->fetch_assoc();
    $stmt->close();

    return $vehiculo;
}

// ============================================================================
// MAIN REQUEST HANDLER
// ============================================================================

try {
    $config = DatabaseConfig::getInstance();
    $conn_acceso = $config->getAccessDB();
    $conn_personal = $config->getPersonalDB();

    // Obtener historial
    $historial = obtenerHistorialVehiculo($conn_acceso, $vehiculo_id);

    // Obtener datos actuales del vehículo
    $vehiculo_actual = obtenerVehiculoActual($conn_acceso, $vehiculo_id);

    if (!$vehiculo_actual) {
        ApiResponse::notFound('Vehículo no encontrado');
    }

    // Retornar respuesta exitosa
    ApiResponse::success([
        'vehiculo' => $vehiculo_actual,
        'historial' => $historial
    ], 'Historial del vehículo obtenido exitosamente');

} catch (Exception $e) {
    ApiResponse::serverError('Error al obtener historial: ' . $e->getMessage());
} finally {
    if (isset($conn_acceso) && $conn_acceso instanceof mysqli) {
        $conn_acceso->close();
    }
    if (isset($conn_personal) && $conn_personal instanceof mysqli) {
        $conn_personal->close();
    }
}

?>

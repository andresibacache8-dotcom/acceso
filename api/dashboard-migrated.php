<?php
/**
 * api/dashboard-migrated.php
 *
 * Dashboard API - Estadísticas y contadores en tiempo real
 * GET-only API para obtener:
 * 1. Contadores agregados (personal, vehículos, visitas, etc.)
 * 2. Detalles por categoría (modales con datos específicos)
 *
 * Métodos:
 * - GET: Obtener contadores (sin ?details) o detalles de categoría (con ?details=CATEGORY)
 */

// ============================================================================
// CONFIGURATION & IMPORTS
// ============================================================================

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/core/ResponseHandler.php';

// Headers
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

// Preflight
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(204);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
    ApiResponse::error('Método no permitido', 405);
}

// ============================================================================
// HELPER FUNCTIONS
// ============================================================================

/**
 * Obtiene contador genérico por tipo de entidad
 */
function get_count_by_type($conn_acceso, $target_type, $join_table = null, $extra_where = '') {
    $sql = "
        SELECT COUNT(DISTINCT T1.target_id) as count
        FROM acceso_pro_db.access_logs T1
        INNER JOIN (
            SELECT target_id, MAX(id) as max_id
            FROM acceso_pro_db.access_logs
            WHERE target_type = ? AND log_status = 'activo'
            GROUP BY target_id
        ) T2 ON T1.target_id = T2.target_id AND T1.id = T2.max_id";

    if ($join_table) {
        $sql .= " JOIN {$join_table} j ON T1.target_id = j.id";
    }

    $sql .= " WHERE T1.action = 'entrada'";

    if (!empty($extra_where)) {
        $sql .= " AND {$extra_where}";
    }

    $stmt = $conn_acceso->prepare($sql);
    if (!$stmt) {
        return 0;
    }

    $stmt->bind_param("s", $target_type);
    $stmt->execute();
    $result = $stmt->get_result();
    $row = $result->fetch_assoc();
    $stmt->close();

    return $row ? (int)$row['count'] : 0;
}

/**
 * Obtiene datos de personal trabajando (desglosado)
 */
function obtener_personal_trabajando($conn_acceso, $conn_personal) {
    $sql = "SELECT p.id, p.Grado, p.Nombres, p.Paterno, p.Materno, p.Unidad, p.movil1, a.log_time as entry_time, 'Trabajando' as tipo, he.fecha_hora_termino
            FROM personal_db.personal p
            JOIN acceso_pro_db.access_logs a ON p.id = a.target_id
            LEFT JOIN acceso_pro_db.horas_extra he ON p.NrRut = he.personal_rut AND he.status = 'activo'
            WHERE a.id IN (
                SELECT MAX(id) FROM acceso_pro_db.access_logs WHERE target_type = 'personal' AND log_status = 'activo' GROUP BY target_id
            ) AND a.action = 'entrada' AND (a.punto_acceso = 'oficina' OR a.punto_acceso = 'control_unidades')
            ORDER BY a.log_time DESC";

    $result = $conn_acceso->query($sql);
    return $result ? $result->fetch_all(MYSQLI_ASSOC) : [];
}

/**
 * Obtiene contadores de personal trabajando por unidad
 */
function obtener_personal_por_unidad($conn_acceso) {
    $sql = "SELECT p.Unidad, COUNT(*) as cantidad
            FROM personal_db.personal p
            JOIN acceso_pro_db.access_logs a ON p.id = a.target_id
            WHERE a.id IN (
                SELECT MAX(id) FROM acceso_pro_db.access_logs WHERE target_type = 'personal' AND log_status = 'activo' GROUP BY target_id
            ) AND a.action = 'entrada' AND (a.punto_acceso = 'oficina' OR a.punto_acceso = 'control_unidades')
            GROUP BY p.Unidad
            ORDER BY p.Unidad";

    $result = $conn_acceso->query($sql);
    return $result ? $result->fetch_all(MYSQLI_ASSOC) : [];
}

/**
 * Obtiene detalles de personal por unidad específica
 */
function obtener_personal_por_unidad_detalle($conn_acceso, $unidad) {
    $sql = "SELECT p.id, p.Grado, p.Nombres, p.Paterno, p.Materno, p.Unidad, p.movil1, a.log_time as entry_time, 'Trabajando' as tipo, he.fecha_hora_termino
            FROM personal_db.personal p
            JOIN acceso_pro_db.access_logs a ON p.id = a.target_id
            LEFT JOIN acceso_pro_db.horas_extra he ON p.NrRut = he.personal_rut AND he.status = 'activo'
            WHERE a.id IN (
                SELECT MAX(id) FROM acceso_pro_db.access_logs WHERE target_type = 'personal' AND log_status = 'activo' GROUP BY target_id
            ) AND a.action = 'entrada' AND (a.punto_acceso = 'oficina' OR a.punto_acceso = 'control_unidades')
            AND p.Unidad = ?
            ORDER BY p.Grado, p.Paterno";

    $stmt = $conn_acceso->prepare($sql);
    if (!$stmt) {
        return [];
    }

    $stmt->bind_param("s", $unidad);
    $stmt->execute();
    $result = $stmt->get_result();
    $data = $result->fetch_all(MYSQLI_ASSOC);
    $stmt->close();

    return $data;
}

/**
 * Obtiene personal residiendo
 */
function obtener_personal_residiendo($conn_acceso) {
    $sql = "SELECT p.id, p.Grado, p.Nombres, p.Paterno, p.Materno, p.movil1, a.log_time as entry_time, 'Residiendo' as tipo
            FROM personal_db.personal p
            JOIN acceso_pro_db.access_logs a ON p.id = a.target_id
            WHERE p.es_residente = 1
            AND a.id IN (
                SELECT MAX(id) FROM acceso_pro_db.access_logs WHERE target_type = 'personal' AND log_status = 'activo' GROUP BY target_id
            ) AND a.action = 'entrada' AND a.punto_acceso = 'residencia'
            ORDER BY a.log_time DESC";

    $result = $conn_acceso->query($sql);
    return $result ? $result->fetch_all(MYSQLI_ASSOC) : [];
}

/**
 * Obtiene personal en otras actividades
 */
function obtener_personal_otras_actividades($conn_acceso) {
    $sql = "SELECT p.id, p.Grado, p.Nombres, p.Paterno, p.Materno, p.movil1, a.log_time as entry_time, a.motivo as motivo, 'Otras Actividades' as tipo
            FROM personal_db.personal p
            JOIN acceso_pro_db.access_logs a ON p.id = a.target_id
            WHERE p.es_residente = 0
            AND a.id IN (
                SELECT MAX(id) FROM acceso_pro_db.access_logs WHERE target_type = 'personal' AND log_status = 'activo' GROUP BY target_id
            ) AND a.action = 'entrada' AND a.punto_acceso = 'portico'
            ORDER BY a.log_time DESC";

    $result = $conn_acceso->query($sql);
    return $result ? $result->fetch_all(MYSQLI_ASSOC) : [];
}

/**
 * Obtiene visitas adentro
 */
function obtener_visitas_adentro($conn_acceso, $conn_personal) {
    $sql = "SELECT v.id,
            v.nombre as nombre_completo,
            v.tipo, a.log_time as entry_time,
            CONCAT_WS(' ', p_poc.Grado, p_poc.Nombres, p_poc.Paterno, p_poc.Materno) as poc_nombre,
            CONCAT_WS(' ', p_fam.Grado, p_fam.Nombres, p_fam.Paterno, p_fam.Materno) as familiar_nombre
            FROM acceso_pro_db.visitas v
            JOIN acceso_pro_db.access_logs a ON v.id = a.target_id
            LEFT JOIN personal_db.personal p_poc ON v.poc_personal_id = p_poc.id
            LEFT JOIN personal_db.personal p_fam ON v.familiar_de_personal_id = p_fam.id
            WHERE a.id IN (
                SELECT MAX(id) FROM acceso_pro_db.access_logs
                WHERE target_type = 'visita' AND log_status = 'activo'
                GROUP BY target_id
            ) AND a.action = 'entrada'
            ORDER BY a.log_time DESC";

    $result = $conn_acceso->query($sql);
    return $result ? $result->fetch_all(MYSQLI_ASSOC) : [];
}

/**
 * Obtiene personal en comisión
 */
function obtener_personal_en_comision($conn_acceso) {
    $sql = "SELECT pc.id, pc.nombre_completo, pc.unidad_origen, pc.poc_nombre, pc.unidad_poc, a.log_time as entry_time
            FROM personal_db.personal_comision pc
            JOIN acceso_pro_db.access_logs a ON pc.id = a.target_id
            WHERE a.id IN (
                SELECT MAX(id) FROM acceso_pro_db.access_logs WHERE target_type = 'personal_comision' AND log_status = 'activo' GROUP BY target_id
            ) AND a.action = 'entrada'
            ORDER BY a.log_time DESC";

    $result = $conn_acceso->query($sql);
    return $result ? $result->fetch_all(MYSQLI_ASSOC) : [];
}

/**
 * Obtiene empleados de empresas adentro
 */
function obtener_empresas_adentro($conn_acceso) {
    $sql = "SELECT ee.id, ee.nombre, ee.paterno, ee.materno, e.nombre as empresa_nombre, a.log_time as entry_time
            FROM acceso_pro_db.empresa_empleados ee
            JOIN acceso_pro_db.empresas e ON ee.empresa_id = e.id
            JOIN acceso_pro_db.access_logs a ON ee.id = a.target_id
            WHERE a.id IN (
                SELECT MAX(id) FROM acceso_pro_db.access_logs WHERE target_type = 'empresa_empleado' AND log_status = 'activo' GROUP BY target_id
            ) AND a.action = 'entrada'
            ORDER BY a.log_time DESC";

    $result = $conn_acceso->query($sql);
    return $result ? $result->fetch_all(MYSQLI_ASSOC) : [];
}

/**
 * Obtiene vehículos por tipo adentro
 */
function obtener_vehiculos_por_tipo($conn_acceso, $tipo_vehiculo) {
    $sql = "SELECT
                v.id,
                v.patente,
                v.marca,
                v.modelo,
                COALESCE(
                    CASE
                        WHEN v.tipo IN ('PERSONAL', 'FUNCIONARIO', 'RESIDENTE', 'FISCAL') THEN CONCAT_WS(' ', p.Grado, p.Nombres, p.Paterno, p.Materno)
                        WHEN v.tipo IN ('EMPLEADO', 'EMPRESA') THEN CONCAT_WS(' ', ee.nombre, ee.paterno, ee.materno)
                        WHEN v.tipo = 'VISITA' THEN vi.nombre
                        ELSE NULL
                    END,
                    'N/A'
                ) AS asociado_nombre,
                a.log_time as entry_time
            FROM acceso_pro_db.vehiculos v
            INNER JOIN (
                SELECT target_id, MAX(id) as latest_id
                FROM acceso_pro_db.access_logs
                WHERE target_type = 'vehiculo' AND log_status = 'activo'
                GROUP BY target_id
            ) latest ON v.id = latest.target_id
            INNER JOIN acceso_pro_db.access_logs a ON a.id = latest.latest_id
            LEFT JOIN personal_db.personal p ON v.asociado_id = p.id AND v.tipo IN ('PERSONAL', 'FUNCIONARIO', 'RESIDENTE', 'FISCAL')
            LEFT JOIN acceso_pro_db.empresa_empleados ee ON v.asociado_id = ee.id AND v.tipo IN ('EMPLEADO', 'EMPRESA')
            LEFT JOIN acceso_pro_db.visitas vi ON v.asociado_id = vi.id AND v.tipo = 'VISITA'
            WHERE v.tipo = ?
            AND a.action = 'entrada'
            ORDER BY a.log_time DESC";

    $stmt = $conn_acceso->prepare($sql);
    if (!$stmt) {
        return [];
    }

    $stmt->bind_param("s", $tipo_vehiculo);
    $stmt->execute();
    $result = $stmt->get_result();
    $data = $result->fetch_all(MYSQLI_ASSOC);
    $stmt->close();

    return $data;
}

/**
 * Obtiene alertas de personal atrasado
 */
function obtener_alertas_atrasado($conn_acceso, $unidad = null) {
    $sql = "SELECT DISTINCT p.id, p.Grado, p.Nombres, p.Paterno, p.Materno, p.Unidad, p.movil1, a.log_time as entry_time, he.fecha_hora_termino, 'Atrasado' as tipo
            FROM personal_db.personal p
            JOIN acceso_pro_db.access_logs a ON p.id = a.target_id
            LEFT JOIN acceso_pro_db.horas_extra he ON p.NrRut = he.personal_rut AND he.status = 'activo'
            WHERE a.target_type = 'personal'
            AND a.action = 'entrada'
            AND a.log_status = 'activo'
            AND he.fecha_hora_termino IS NOT NULL
            AND he.fecha_hora_termino < NOW()
            AND a.id IN (
                SELECT MAX(id) FROM acceso_pro_db.access_logs WHERE target_type = 'personal' AND log_status = 'activo' GROUP BY target_id
            )";

    if ($unidad) {
        $sql .= " AND p.Unidad = ?";
    }

    $sql .= " ORDER BY a.log_time DESC";

    $stmt = $conn_acceso->prepare($sql);
    if (!$stmt) {
        return [];
    }

    if ($unidad) {
        $stmt->bind_param("s", $unidad);
    }

    $stmt->execute();
    $result = $stmt->get_result();
    $data = $result->fetch_all(MYSQLI_ASSOC);
    $stmt->close();

    return $data;
}

/**
 * Obtiene contadores de alertas atrasado por unidad
 */
function obtener_alertas_atrasado_por_unidad($conn_acceso) {
    $sql = "SELECT p.Unidad, COUNT(DISTINCT p.id) as cantidad
            FROM personal_db.personal p
            JOIN acceso_pro_db.access_logs a ON p.id = a.target_id
            LEFT JOIN acceso_pro_db.horas_extra he ON p.NrRut = he.personal_rut AND he.status = 'activo'
            WHERE a.target_type = 'personal'
            AND a.action = 'entrada'
            AND a.log_status = 'activo'
            AND he.fecha_hora_termino IS NOT NULL
            AND he.fecha_hora_termino < NOW()
            AND a.id IN (
                SELECT MAX(id) FROM acceso_pro_db.access_logs WHERE target_type = 'personal' AND log_status = 'activo' GROUP BY target_id
            )
            GROUP BY p.Unidad
            ORDER BY p.Unidad";

    $result = $conn_acceso->query($sql);
    return $result ? $result->fetch_all(MYSQLI_ASSOC) : [];
}

/**
 * Obtiene alertas de personal no autorizado
 */
function obtener_alertas_no_autorizado($conn_acceso, $unidad = null) {
    $sql = "SELECT DISTINCT p.id, p.Grado, p.Nombres, p.Paterno, p.Materno, p.Unidad, p.movil1, a.log_time as entry_time, 'No Autorizado' as tipo
            FROM personal_db.personal p
            JOIN acceso_pro_db.access_logs a ON p.id = a.target_id
            LEFT JOIN acceso_pro_db.horas_extra he ON p.NrRut = he.personal_rut AND he.status = 'activo'
            LEFT JOIN acceso_pro_db.guardia_servicio gs ON p.NrRut = gs.personal_rut AND gs.status = 'ACTIVO'
            WHERE a.target_type = 'personal'
            AND a.action = 'entrada'
            AND a.log_status = 'activo'
            AND p.es_residente = 0
            AND (a.motivo IS NULL OR a.motivo = '' OR a.motivo = 'Trabajo')
            AND he.id IS NULL
            AND gs.id IS NULL
            AND (
                DATE(a.log_time) < CURDATE()
                OR CURTIME() > '17:00:00'
            )
            AND a.id IN (
                SELECT MAX(id) FROM acceso_pro_db.access_logs WHERE target_type = 'personal' AND log_status = 'activo' GROUP BY target_id
            )";

    if ($unidad) {
        $sql .= " AND p.Unidad = ?";
    }

    $sql .= " ORDER BY a.log_time DESC";

    $stmt = $conn_acceso->prepare($sql);
    if (!$stmt) {
        return [];
    }

    if ($unidad) {
        $stmt->bind_param("s", $unidad);
    }

    $stmt->execute();
    $result = $stmt->get_result();
    $data = $result->fetch_all(MYSQLI_ASSOC);
    $stmt->close();

    return $data;
}

/**
 * Obtiene contadores de alertas no autorizado por unidad
 */
function obtener_alertas_no_autorizado_por_unidad($conn_acceso) {
    $sql = "SELECT p.Unidad, COUNT(DISTINCT p.id) as cantidad
            FROM personal_db.personal p
            JOIN acceso_pro_db.access_logs a ON p.id = a.target_id
            LEFT JOIN acceso_pro_db.horas_extra he ON p.NrRut = he.personal_rut AND he.status = 'activo'
            LEFT JOIN acceso_pro_db.guardia_servicio gs ON p.NrRut = gs.personal_rut AND gs.status = 'ACTIVO'
            WHERE a.target_type = 'personal'
            AND a.action = 'entrada'
            AND a.log_status = 'activo'
            AND p.es_residente = 0
            AND (a.motivo IS NULL OR a.motivo = '' OR a.motivo = 'Trabajo')
            AND he.id IS NULL
            AND gs.id IS NULL
            AND (
                DATE(a.log_time) < CURDATE()
                OR CURTIME() > '17:00:00'
            )
            AND a.id IN (
                SELECT MAX(id) FROM acceso_pro_db.access_logs WHERE target_type = 'personal' AND log_status = 'activo' GROUP BY target_id
            )
            GROUP BY p.Unidad
            ORDER BY p.Unidad";

    $result = $conn_acceso->query($sql);
    return $result ? $result->fetch_all(MYSQLI_ASSOC) : [];
}

/**
 * Router de detalles (modales)
 */
function obtener_detalles($category, $conn_acceso, $conn_personal) {
    $data = [];

    switch ($category) {
        case 'personal-trabajando':
            $data = obtener_personal_trabajando($conn_acceso, $conn_personal);
            break;

        case 'personal-trabajando-por-unidad':
            $data = obtener_personal_por_unidad($conn_acceso);
            break;

        case 'personal-trabajando-unidad-detalle':
            $unidad = $_GET['unidad'] ?? '';
            if (!empty($unidad)) {
                $data = obtener_personal_por_unidad_detalle($conn_acceso, $unidad);
            }
            break;

        case 'personal-residiendo':
            $data = obtener_personal_residiendo($conn_acceso);
            break;

        case 'personal-otras-actividades':
            $data = obtener_personal_otras_actividades($conn_acceso);
            break;

        case 'visitas-adentro':
            $data = obtener_visitas_adentro($conn_acceso, $conn_personal);
            break;

        case 'personal-en-comision':
            $data = obtener_personal_en_comision($conn_acceso);
            break;

        case 'empresas-adentro':
            $data = obtener_empresas_adentro($conn_acceso);
            break;

        case 'vehiculos-funcionario-adentro':
        case 'vehiculos-residente-adentro':
        case 'vehiculos-visita-adentro':
        case 'vehiculos-proveedor-adentro':
        case 'vehiculos-fiscal-adentro':
            $tipos = [
                'vehiculos-funcionario-adentro' => 'FUNCIONARIO',
                'vehiculos-residente-adentro' => 'RESIDENTE',
                'vehiculos-visita-adentro' => 'VISITA',
                'vehiculos-proveedor-adentro' => 'EMPRESA',
                'vehiculos-fiscal-adentro' => 'FISCAL'
            ];
            $tipo_vehiculo = $tipos[$category] ?? '';
            if (!empty($tipo_vehiculo)) {
                $data = obtener_vehiculos_por_tipo($conn_acceso, $tipo_vehiculo);
            }
            break;

        case 'alerta-atrasado':
            $data = obtener_alertas_atrasado($conn_acceso);
            break;

        case 'alerta-atrasado-por-unidad':
            $data = obtener_alertas_atrasado_por_unidad($conn_acceso);
            break;

        case 'alerta-atrasado-unidad-detalle':
            $unidad = $_GET['unidad'] ?? '';
            if (!empty($unidad)) {
                $data = obtener_alertas_atrasado($conn_acceso, $unidad);
            }
            break;

        case 'alerta-no-autorizado':
            $data = obtener_alertas_no_autorizado($conn_acceso);
            break;

        case 'alerta-no-autorizado-por-unidad':
            $data = obtener_alertas_no_autorizado_por_unidad($conn_acceso);
            break;

        case 'alerta-no-autorizado-unidad-detalle':
            $unidad = $_GET['unidad'] ?? '';
            if (!empty($unidad)) {
                $data = obtener_alertas_no_autorizado($conn_acceso, $unidad);
            }
            break;
    }

    return $data;
}

// ============================================================================
// MAIN HANDLER
// ============================================================================

function handle_get($conn_acceso, $conn_personal) {
    // Si hay ?details=CATEGORY, retornar detalles del modal
    if (isset($_GET['details'])) {
        $category = $_GET['details'];
        $data = obtener_detalles($category, $conn_acceso, $conn_personal);
        ApiResponse::success($data);
    }

    // Si no, retornar contadores generales
    $counters = [];

    // Personal counters
    $sql_personal = "
        SELECT
            COUNT(CASE WHEN (al.punto_acceso = 'oficina' OR al.punto_acceso = 'control_unidades') THEN 1 END) AS personal_trabajando,
            COUNT(CASE WHEN p.es_residente = 1 AND al.punto_acceso = 'residencia' THEN 1 END) AS personal_residiendo,
            COUNT(CASE WHEN p.es_residente = 0 AND al.punto_acceso = 'portico' THEN 1 END) AS personal_otras_actividades
        FROM
            acceso_pro_db.access_logs al
        INNER JOIN (
            SELECT MAX(id) as max_id
            FROM acceso_pro_db.access_logs
            WHERE target_type = 'personal' AND log_status = 'activo'
            GROUP BY target_id
        ) as latest_logs ON al.id = latest_logs.max_id
        JOIN
            personal_db.personal p ON al.target_id = p.id
        WHERE
            al.action = 'entrada'";

    $result = $conn_acceso->query($sql_personal);
    if ($result && $result->num_rows > 0) {
        $row = $result->fetch_assoc();
        $counters['personal_trabajando'] = (int)($row['personal_trabajando'] ?? 0);
        $counters['personal_residiendo'] = (int)($row['personal_residiendo'] ?? 0);
        $counters['personal_otras_actividades'] = (int)($row['personal_otras_actividades'] ?? 0);
        $counters['personal_total_adentro'] = $counters['personal_trabajando'] + $counters['personal_residiendo'] + $counters['personal_otras_actividades'];
    } else {
        $counters['personal_trabajando'] = 0;
        $counters['personal_residiendo'] = 0;
        $counters['personal_otras_actividades'] = 0;
        $counters['personal_total_adentro'] = 0;
    }

    // Other entity counters
    $counters['personal_en_comision'] = get_count_by_type($conn_acceso, 'personal_comision', 'personal_db.personal_comision');
    $counters['visitas_adentro'] = get_count_by_type($conn_acceso, 'visita', 'acceso_pro_db.visitas');
    $counters['empresa_empleados_adentro'] = get_count_by_type($conn_acceso, 'empresa_empleado', 'acceso_pro_db.empresa_empleados');

    // General total
    $counters['personal_general_adentro'] = $counters['personal_total_adentro'] + $counters['personal_en_comision'] +
                                            $counters['visitas_adentro'] + $counters['empresa_empleados_adentro'];

    // Vehicle counters by type
    $sql_vehicles = "
        SELECT
            v.tipo,
            COUNT(DISTINCT al.target_id) as count
        FROM
            acceso_pro_db.access_logs al
        INNER JOIN (
            SELECT
                target_id,
                MAX(id) as max_id
            FROM
                acceso_pro_db.access_logs
            WHERE
                target_type = 'vehiculo' AND log_status = 'activo'
            GROUP BY
                target_id
        ) as latest_vehicle_logs ON al.id = latest_vehicle_logs.max_id
        JOIN
            acceso_pro_db.vehiculos v ON al.target_id = v.id
        WHERE
            al.action = 'entrada'
        GROUP BY
            v.tipo";

    $result_vehicles = $conn_acceso->query($sql_vehicles);
    $counters['vehiculos'] = [];

    if ($result_vehicles && $result_vehicles->num_rows > 0) {
        while ($row = $result_vehicles->fetch_assoc()) {
            $counters['vehiculos'][$row['tipo']] = (int)$row['count'];
        }
    }

    // Total vehicles
    $counters['vehiculos_total'] = array_sum($counters['vehiculos']);

    // Alerts counters
    $sql_alerts_atrasado = "SELECT COUNT(DISTINCT p.id) as count
                           FROM personal_db.personal p
                           JOIN acceso_pro_db.access_logs a ON p.id = a.target_id
                           LEFT JOIN acceso_pro_db.horas_extra he ON p.NrRut = he.personal_rut AND he.status = 'activo'
                           WHERE a.target_type = 'personal'
                           AND a.action = 'entrada'
                           AND a.log_status = 'activo'
                           AND he.fecha_hora_termino IS NOT NULL
                           AND he.fecha_hora_termino < NOW()
                           AND a.id IN (
                               SELECT MAX(id) FROM acceso_pro_db.access_logs WHERE target_type = 'personal' AND log_status = 'activo' GROUP BY target_id
                           )";

    $result_atrasado = $conn_acceso->query($sql_alerts_atrasado);
    $counters['alertas_atrasado'] = $result_atrasado && $result_atrasado->num_rows > 0
        ? (int)($result_atrasado->fetch_assoc()['count'] ?? 0)
        : 0;

    $sql_alerts_no_autorizado = "SELECT COUNT(DISTINCT p.id) as count
                                FROM personal_db.personal p
                                JOIN acceso_pro_db.access_logs a ON p.id = a.target_id
                                LEFT JOIN acceso_pro_db.horas_extra he ON p.NrRut = he.personal_rut AND he.status = 'activo'
                                LEFT JOIN acceso_pro_db.guardia_servicio gs ON p.NrRut = gs.personal_rut AND gs.status = 'ACTIVO'
                                WHERE a.target_type = 'personal'
                                AND a.action = 'entrada'
                                AND a.log_status = 'activo'
                                AND p.es_residente = 0
                                AND (a.motivo IS NULL OR a.motivo = '' OR a.motivo = 'Trabajo')
                                AND he.id IS NULL
                                AND gs.id IS NULL
                                AND (
                                    DATE(a.log_time) < CURDATE()
                                    OR CURTIME() > '17:00:00'
                                )
                                AND a.id IN (
                                    SELECT MAX(id) FROM acceso_pro_db.access_logs WHERE target_type = 'personal' AND log_status = 'activo' GROUP BY target_id
                                )";

    $result_no_autorizado = $conn_acceso->query($sql_alerts_no_autorizado);
    $counters['alertas_no_autorizado'] = $result_no_autorizado && $result_no_autorizado->num_rows > 0
        ? (int)($result_no_autorizado->fetch_assoc()['count'] ?? 0)
        : 0;

    ApiResponse::success($counters);
}

// ============================================================================
// EXECUTION
// ============================================================================

$conn_acceso = DatabaseConfig::getInstance()->getAccesoConnection();
$conn_personal = DatabaseConfig::getInstance()->getPersonalConnection();

try {
    handle_get($conn_acceso, $conn_personal);
} catch (Exception $e) {
    ApiResponse::serverError($e->getMessage());
} finally {
    $conn_acceso->close();
    $conn_personal->close();
}

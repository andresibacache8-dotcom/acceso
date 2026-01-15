<?php
// api/dashboard.php
require_once 'database/db_acceso.php';
require_once 'database/db_personal.php';

// Habilitar reporte de errores para ayudar en el debugging
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

header('Content-Type: application/json');

if (isset($_GET['details'])) {
    $category = $_GET['details'];
    $data = [];
    $sql = '';

    // --- LÓGICA PARA OBTENER DETALLES (MODALES) ---
    switch ($category) {
        case 'personal-trabajando':
            $sql = "SELECT p.id, p.Grado, p.Nombres, p.Paterno, p.Materno, p.Unidad, p.movil1, a.log_time as entry_time, 'Trabajando' as tipo, he.fecha_hora_termino
                    FROM personal_db.personal p
                    JOIN acceso_pro_db.access_logs a ON p.id = a.target_id
                    LEFT JOIN acceso_pro_db.horas_extra he ON p.NrRut = he.personal_rut AND he.status = 'activo'
                    WHERE a.id IN (
                        SELECT MAX(id) FROM acceso_pro_db.access_logs WHERE target_type = 'personal' AND log_status = 'activo' GROUP BY target_id
                    ) AND a.action = 'entrada' AND (a.punto_acceso = 'oficina' OR a.punto_acceso = 'control_unidades')";
            $result = $conn_acceso->query($sql);
            if ($result) $data = $result->fetch_all(MYSQLI_ASSOC);
            break;

        case 'personal-trabajando-por-unidad':
            // Agrupar personal trabajando por unidad con contadores
            $sql = "SELECT p.Unidad, COUNT(*) as cantidad
                    FROM personal_db.personal p
                    JOIN acceso_pro_db.access_logs a ON p.id = a.target_id
                    WHERE a.id IN (
                        SELECT MAX(id) FROM acceso_pro_db.access_logs WHERE target_type = 'personal' AND log_status = 'activo' GROUP BY target_id
                    ) AND a.action = 'entrada' AND (a.punto_acceso = 'oficina' OR a.punto_acceso = 'control_unidades')
                    GROUP BY p.Unidad
                    ORDER BY p.Unidad";
            $result = $conn_acceso->query($sql);
            if ($result) $data = $result->fetch_all(MYSQLI_ASSOC);
            break;

        case 'personal-trabajando-unidad-detalle':
            // Obtener detalle de personal por unidad específica
            $unidad = $_GET['unidad'] ?? '';
            if (empty($unidad)) {
                $data = [];
                break;
            }
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
            if ($stmt) {
                $stmt->bind_param("s", $unidad);
                $stmt->execute();
                $result = $stmt->get_result();
                $data = $result->fetch_all(MYSQLI_ASSOC);
                $stmt->close();
            }
            break;

        case 'personal-residiendo':
            $sql = "SELECT p.id, p.Grado, p.Nombres, p.Paterno, p.Materno, p.movil1, a.log_time as entry_time, 'Residiendo' as tipo
                    FROM personal_db.personal p
                    JOIN acceso_pro_db.access_logs a ON p.id = a.target_id
                    WHERE p.es_residente = 1
                    AND a.id IN (
                        SELECT MAX(id) FROM acceso_pro_db.access_logs WHERE target_type = 'personal' AND log_status = 'activo' GROUP BY target_id
                    ) AND a.action = 'entrada' AND a.punto_acceso = 'residencia'";
            $result = $conn_acceso->query($sql);
            if ($result) $data = $result->fetch_all(MYSQLI_ASSOC);
            break;

        case 'personal-otras-actividades':
            $sql = "SELECT p.id, p.Grado, p.Nombres, p.Paterno, p.Materno, p.movil1, a.log_time as entry_time, a.motivo as motivo, 'Otras Actividades' as tipo
                    FROM personal_db.personal p
                    JOIN acceso_pro_db.access_logs a ON p.id = a.target_id
                    WHERE p.es_residente = 0
                    AND a.id IN (
                        SELECT MAX(id) FROM acceso_pro_db.access_logs WHERE target_type = 'personal' AND log_status = 'activo' GROUP BY target_id
                    ) AND a.action = 'entrada' AND a.punto_acceso = 'portico'";
            $result = $conn_acceso->query($sql);
            if ($result) $data = $result->fetch_all(MYSQLI_ASSOC);
            break;

        case 'visitas-adentro':
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
                    ) AND a.action = 'entrada'";
            $result = $conn_acceso->query($sql);
            if ($result) $data = $result->fetch_all(MYSQLI_ASSOC);
            break;

        case 'personal-en-comision':
            $sql = "SELECT pc.id, pc.nombre_completo, pc.unidad_origen, pc.poc_nombre, pc.unidad_poc, a.log_time as entry_time
                    FROM personal_db.personal_comision pc
                    JOIN acceso_pro_db.access_logs a ON pc.id = a.target_id
                    WHERE a.id IN (
                        SELECT MAX(id) FROM acceso_pro_db.access_logs WHERE target_type = 'personal_comision' AND log_status = 'activo' GROUP BY target_id
                    ) AND a.action = 'entrada'";
            $result = $conn_acceso->query($sql);
            if ($result) $data = $result->fetch_all(MYSQLI_ASSOC);
            break;

        case 'empresas-adentro':
            $sql = "SELECT ee.id, ee.nombre, ee.paterno, ee.materno, e.nombre as empresa_nombre, a.log_time as entry_time
                    FROM acceso_pro_db.empresa_empleados ee
                    JOIN acceso_pro_db.empresas e ON ee.empresa_id = e.id
                    JOIN acceso_pro_db.access_logs a ON ee.id = a.target_id
                    WHERE a.id IN (
                        SELECT MAX(id) FROM acceso_pro_db.access_logs WHERE target_type = 'empresa_empleado' AND log_status = 'activo' GROUP BY target_id
                    ) AND a.action = 'entrada'";
            $result = $conn_acceso->query($sql);
            if ($result) $data = $result->fetch_all(MYSQLI_ASSOC);
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
            $tipo_vehiculo = $tipos[$category];

            // Query CORRECTO: obtener SOLO vehículos que actualmente están ADENTRO
            // Un vehículo está adentro si su ÚLTIMO registro es una ENTRADA (no salida)
            // El asociado puede ser PERSONAL, EMPRESA_EMPLEADO o VISITA
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
            if($stmt) {
                $stmt->bind_param("s", $tipo_vehiculo);

                if(!$stmt->execute()) {
                    $data = [];
                } else {
                    $result = $stmt->get_result();
                    $data = $result->fetch_all(MYSQLI_ASSOC);
                }
                $stmt->close();
            } else {
                $data = [];
            }
            break;

        case 'alerta-atrasado':
            // Personal autorizado a quedarse pero que pasó su hora de salida
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
                    )
                    ORDER BY a.log_time DESC";
            $result = $conn_acceso->query($sql);
            if ($result) $data = $result->fetch_all(MYSQLI_ASSOC);
            break;

        case 'alerta-atrasado-por-unidad':
            // Agrupar alertas de atrasados por unidad
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
            if ($result) $data = $result->fetch_all(MYSQLI_ASSOC);
            break;

        case 'alerta-atrasado-unidad-detalle':
            // Obtener detalle de alertas atrasados por unidad específica
            $unidad = $_GET['unidad'] ?? '';
            if (empty($unidad)) {
                $data = [];
                break;
            }
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
                    )
                    AND p.Unidad = ?
                    ORDER BY p.Grado, p.Paterno";
            $stmt = $conn_acceso->prepare($sql);
            if ($stmt) {
                $stmt->bind_param("s", $unidad);
                $stmt->execute();
                $result = $stmt->get_result();
                $data = $result->fetch_all(MYSQLI_ASSOC);
                $stmt->close();
            }
            break;

        case 'alerta-no-autorizado':
            // Personal sin autorización: entrada de días anteriores o entrada de hoy después de las 17:00 (excluyendo residentes, motivos especiales y guardias/servicios activos)
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
                    )
                    ORDER BY a.log_time DESC";
            $result = $conn_acceso->query($sql);
            if ($result) $data = $result->fetch_all(MYSQLI_ASSOC);
            break;

        case 'alerta-no-autorizado-por-unidad':
            // Agrupar alertas de no autorizados por unidad: entrada de días anteriores o entrada de hoy después de las 17:00 (excluyendo guardias/servicios activos)
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
            if ($result) $data = $result->fetch_all(MYSQLI_ASSOC);
            break;

        case 'alerta-no-autorizado-unidad-detalle':
            // Obtener detalle de alertas no autorizados por unidad específica: entrada de días anteriores o entrada de hoy después de las 17:00 (excluyendo guardias/servicios activos)
            $unidad = $_GET['unidad'] ?? '';
            if (empty($unidad)) {
                $data = [];
                break;
            }
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
                    )
                    AND p.Unidad = ?
                    ORDER BY p.Grado, p.Paterno";
            $stmt = $conn_acceso->prepare($sql);
            if ($stmt) {
                $stmt->bind_param("s", $unidad);
                $stmt->execute();
                $result = $stmt->get_result();
                $data = $result->fetch_all(MYSQLI_ASSOC);
                $stmt->close();
            }
            break;
    }

    echo json_encode($data);
    if (isset($conn_acceso)) $conn_acceso->close();
    if (isset($conn_personal)) $conn_personal->close();
    exit;
}

// =========================================================================
// --- INICIO DE LA LÓGICA DE CONTADORES ---
// =========================================================================

// 1. Contadores de Personal (CORREGIDO CON INNER JOIN)
$sql_personal_counters = "
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

$personal_trabajando = 0;
$personal_residiendo = 0;
$personal_otras_actividades = 0;

$result_personal_counters = $conn_acceso->query($sql_personal_counters);
if ($result_personal_counters && $result_personal_counters->num_rows > 0) {
    $counters = $result_personal_counters->fetch_assoc();
    if($counters) {
        $personal_trabajando = (int)$counters['personal_trabajando'];
        $personal_residiendo = (int)$counters['personal_residiendo'];
        $personal_otras_actividades = (int)$counters['personal_otras_actividades'];
    }
}
$personal_total_adentro = $personal_trabajando + $personal_residiendo + $personal_otras_actividades;

// 2. Función genérica para contar entidades que existen
function get_count_by_type_with_join($conn, $type, $join_table) {
    $sql = "
        SELECT COUNT(DISTINCT T1.target_id) as count
        FROM acceso_pro_db.access_logs T1
        INNER JOIN (
            SELECT target_id, MAX(id) as max_id
            FROM acceso_pro_db.access_logs
            WHERE target_type = ? AND log_status = 'activo'
            GROUP BY target_id
        ) T2 ON T1.target_id = T2.target_id AND T1.id = T2.max_id
        JOIN {$join_table} j ON T1.target_id = j.id
        WHERE T1.action = 'entrada'";

    $stmt = $conn->prepare($sql);
    if(!$stmt) return 0;
    $stmt->bind_param("s", $type);
    $stmt->execute();
    $result = $stmt->get_result();
    $row = $result->fetch_assoc();
    $stmt->close();
    return $row ? (int)$row['count'] : 0;
}

// 3. Contadores de otras entidades (usando la lógica de JOIN)
$personal_en_comision = get_count_by_type_with_join($conn_acceso, 'personal_comision', 'personal_db.personal_comision');
$visitas_adentro = get_count_by_type_with_join($conn_acceso, 'visita', 'acceso_pro_db.visitas');
$empresa_empleados_adentro = get_count_by_type_with_join($conn_acceso, 'empresa_empleado', 'acceso_pro_db.empresa_empleados');

// 4. Suma General
$personal_general_adentro = $personal_total_adentro + $personal_en_comision + $visitas_adentro + $empresa_empleados_adentro;

// 5. Contadores de Vehículos (LÓGICA RESTAURADA)
$sql_vehiculos = "
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
    ) T2 ON al.target_id = T2.target_id AND al.id = T2.max_id
    JOIN 
        acceso_pro_db.vehiculos v ON al.target_id = v.id
    WHERE 
        al.action = 'entrada'
    GROUP BY 
        v.tipo";

$vehiculos_counters = ['FUNCIONARIO' => 0, 'RESIDENTE' => 0, 'VISITA' => 0, 'EMPRESA' => 0, 'FISCAL' => 0];
$vehiculos_result = $conn_acceso->query($sql_vehiculos);
if ($vehiculos_result) {
    while ($row = $vehiculos_result->fetch_assoc()) {
        if (array_key_exists($row['tipo'], $vehiculos_counters)) {
            $vehiculos_counters[$row['tipo']] = (int)$row['count'];
        }
    }
}

// 6. Contadores de Alertas

// ALERTA 1: Personal AUTORIZADO (con fecha_hora_termino en horas_extra) pero ya pasó su hora
// Muestra en ÁMBAR: Está autorizado a quedarse pero ya pasó su hora de salida autorizada
$sql_atrasado = "
    SELECT COUNT(DISTINCT p.id) as count
    FROM personal_db.personal p
    JOIN acceso_pro_db.access_logs al ON p.id = al.target_id
    LEFT JOIN acceso_pro_db.horas_extra he ON p.NrRut = he.personal_rut AND he.status = 'activo'
    WHERE al.target_type = 'personal'
    AND al.action = 'entrada'
    AND al.log_status = 'activo'
    AND he.fecha_hora_termino IS NOT NULL
    AND he.fecha_hora_termino < NOW()
    AND al.id IN (
        SELECT MAX(id) FROM acceso_pro_db.access_logs
        WHERE target_type = 'personal' AND log_status = 'activo'
        GROUP BY target_id
    )";

$alerta_atrasado_count = 0;
$result_atrasado = $conn_acceso->query($sql_atrasado);
if ($result_atrasado) {
    $row = $result_atrasado->fetch_assoc();
    $alerta_atrasado_count = (int)($row['count'] ?? 0);
} else {
    error_log("Error en SQL atrasado: " . $conn_acceso->error);
}

// ALERTA 2: Personal NO AUTORIZADO pero aún adentro
// Muestra en ROJO: No tiene registro en horas_extra (NO autorizado a quedarse) pero sigue adentro
// Excluyendo: residentes, gente en motivos especiales, guardias/servicios activos
// Solo muestra: entradas de días anteriores O entradas de hoy después de las 17:00
$sql_no_autorizado = "
    SELECT COUNT(DISTINCT p.id) as count
    FROM personal_db.personal p
    JOIN acceso_pro_db.access_logs al ON p.id = al.target_id
    LEFT JOIN acceso_pro_db.horas_extra he ON p.NrRut = he.personal_rut AND he.status = 'activo'
    LEFT JOIN acceso_pro_db.guardia_servicio gs ON p.NrRut = gs.personal_rut AND gs.status = 'ACTIVO'
    WHERE al.target_type = 'personal'
    AND al.action = 'entrada'
    AND al.log_status = 'activo'
    AND p.es_residente = 0
    AND (al.motivo IS NULL OR al.motivo = '' OR al.motivo = 'Trabajo')
    AND he.id IS NULL
    AND gs.id IS NULL
    AND (
        DATE(al.log_time) < CURDATE()
        OR CURTIME() > '17:00:00'
    )
    AND al.id IN (
        SELECT MAX(id) FROM acceso_pro_db.access_logs
        WHERE target_type = 'personal' AND log_status = 'activo'
        GROUP BY target_id
    )";

$alerta_no_autorizado_count = 0;
$result_no_autorizado = $conn_acceso->query($sql_no_autorizado);
if ($result_no_autorizado) {
    $row = $result_no_autorizado->fetch_assoc();
    $alerta_no_autorizado_count = (int)($row['count'] ?? 0);
}

// --- RESPUESTA FINAL ---
$response = [
    "personal_general_adentro" => $personal_general_adentro,
    "personal_trabajando" => $personal_trabajando,
    "personal_residiendo" => $personal_residiendo,
    "personal_otras_actividades" => $personal_otras_actividades,
    "personal_en_comision" => $personal_en_comision,
    "visitas_adentro" => $visitas_adentro,
    "empresas_adentro" => $empresa_empleados_adentro,
    "vehiculos_funcionario_adentro" => $vehiculos_counters['FUNCIONARIO'],
    "vehiculos_residente_adentro" => $vehiculos_counters['RESIDENTE'],
    "vehiculos_visita_adentro" => $vehiculos_counters['VISITA'],
    "vehiculos_proveedor_adentro" => $vehiculos_counters['EMPRESA'],
    "vehiculos_fiscal_adentro" => $vehiculos_counters['FISCAL'],
    "alerta_atrasado_count" => $alerta_atrasado_count,
    "alerta_no_autorizado_count" => $alerta_no_autorizado_count
];

echo json_encode($response);

if (isset($conn_acceso)) $conn_acceso->close();
if (isset($conn_personal)) $conn_personal->close();

?>
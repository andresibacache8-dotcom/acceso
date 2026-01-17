<?php
/**
 * api/reportes-migrated.php
 *
 * API de Reportes - Versión Migrada
 *
 * Soporta 7 tipos de reportes:
 * 1. acceso_personal - Historial de acceso por persona (RUT)
 * 2. horas_extra - Salidas posteriores por período
 * 3. acceso_general - Acceso general (todos los tipos)
 * 4. acceso_vehiculos - Acceso de vehículos por patente
 * 5. acceso_visitas - Acceso de visitas por RUT
 * 6. personal_comision - Personal en comisión
 * 7. salida_no_autorizada - Salidas no autorizadas (después de 17:00)
 *
 * Exporta en JSON (default) o PDF
 *
 * Cambios principales:
 * - Usa config/database.php en lugar de database/db_*.php
 * - Usa ApiResponse para respuestas estandarizadas
 * - Lógica de filtrado centralizada
 * - Helpers refactorizadas por tipo de reporte
 * - PDF generation mantenida pero mejorada
 */

require_once '../config/database.php';
require_once '../api/core/ResponseHandler.php';
require_once '../api/core/AuthMiddleware.php';
require_once '../api/core/AuditLogger.php';
require_once '../api/core/SecurityHeaders.php';
require_once '../../fpdf/fpdf.php';

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

// ============================================================================
// FUNCIONES HELPER - VALIDACIÓN Y FILTRADO
// ============================================================================

/**
 * Valida y procesa fechas de rango
 */
function procesarRangoFechas($fecha_inicio, $fecha_fin) {
    $resultado = ['tipos' => '', 'valores' => [], 'donde' => ''];

    if ($fecha_inicio && $fecha_fin && !empty($fecha_inicio) && !empty($fecha_fin)) {
        try {
            $fecha_fin_obj = new DateTime($fecha_fin);
            $fecha_fin_obj->modify('+1 day');

            $resultado['tipos'] = 'ss';
            $resultado['valores'] = [$fecha_inicio, $fecha_fin_obj->format('Y-m-d')];
            $resultado['donde'] = 'BETWEEN ? AND ?';
        } catch (Exception $e) {
            throw new Exception('Formato de fecha inválido');
        }
    }

    return $resultado;
}

/**
 * Aplica WHERE clause con filtros
 */
function aplicarFiltros($sql, &$types, &$values, $sql_where) {
    if (empty($sql_where)) return $sql;

    $where_str = implode(' AND ', $sql_where);
    if (stripos($sql, 'WHERE') !== false) {
        return $sql . ' AND ' . $where_str;
    }
    return $sql . ' WHERE ' . $where_str;
}

// ============================================================================
// FUNCIONES HELPER - QUERIES POR TIPO DE REPORTE
// ============================================================================

/**
 * Reporte: Acceso por Personal
 */
function obtenerReporteAccesoPersonal($conn_acceso, $conn_personal, $rut, $fecha_inicio, $fecha_fin) {
    if (empty($rut)) throw new Exception('Falta parámetro requerido: rut');

    $sql = "SELECT al.action, al.log_time, al.punto_acceso, p.Grado, p.Nombres, p.Paterno, p.Materno
            FROM acceso_pro_db.access_logs al
            JOIN personal_db.personal p ON al.target_id = p.id
            WHERE al.target_type = 'personal' AND p.NrRut = ?";

    $types = 's';
    $values = [$rut];

    $rango = procesarRangoFechas($fecha_inicio, $fecha_fin);
    if (!empty($rango['donde'])) {
        $sql .= " AND al.log_time " . $rango['donde'];
        $types .= $rango['tipos'];
        $values = array_merge($values, $rango['valores']);
    }

    $sql .= " ORDER BY al.log_time DESC";

    $stmt = $conn_acceso->prepare($sql);
    if (!$stmt) throw new Exception('Error SQL: ' . $conn_acceso->error);

    if (!empty($values)) $stmt->bind_param($types, ...$values);
    $stmt->execute();
    $result = $stmt->get_result();
    $data = $result->fetch_all(MYSQLI_ASSOC);
    $stmt->close();

    return $data;
}

/**
 * Reporte: Horas Extra (Salidas posteriores)
 */
function obtenerReporteHorasExtra($conn_acceso, $conn_personal, $fecha_inicio, $fecha_fin) {
    $sql = "SELECT he.*, CONCAT_WS(' ', p.Grado, p.Nombres, p.Paterno, p.Materno) as personal_nombre_completo
            FROM acceso_pro_db.horas_extra he
            LEFT JOIN personal_db.personal p ON he.personal_rut = p.NrRut";

    $types = '';
    $values = [];

    $rango = procesarRangoFechas($fecha_inicio, $fecha_fin);
    if (!empty($rango['donde'])) {
        $sql .= " WHERE fecha_hora_termino " . $rango['donde'];
        $types = $rango['tipos'];
        $values = $rango['valores'];
    }

    $sql .= " ORDER BY fecha_hora_termino DESC";

    $stmt = $conn_acceso->prepare($sql);
    if (!$stmt) throw new Exception('Error SQL: ' . $conn_acceso->error);

    if (!empty($values)) $stmt->bind_param($types, ...$values);
    $stmt->execute();
    $result = $stmt->get_result();
    $data = $result->fetch_all(MYSQLI_ASSOC);
    $stmt->close();

    return $data;
}

/**
 * Reporte: Acceso General (todos los tipos)
 */
function obtenerReporteAccesoGeneral($conn_acceso, $conn_personal, $access_type, $fecha_inicio, $fecha_fin) {
    $sql = "SELECT al.target_id, al.target_type, al.action, al.log_time,
            (CASE al.target_type
                WHEN 'personal' THEN CONCAT_WS(' ', p.Grado, p.Nombres, p.Paterno, p.Materno)
                WHEN 'visita' THEN v.nombre
                WHEN 'vehiculo' THEN ve.patente
                WHEN 'personal_comision' THEN pc.nombre_completo
                ELSE al.target_id
            END) as name
            FROM acceso_pro_db.access_logs al
            LEFT JOIN personal_db.personal p ON al.target_id = p.id AND al.target_type = 'personal'
            LEFT JOIN acceso_pro_db.visitas v ON al.target_id = v.id AND al.target_type = 'visita'
            LEFT JOIN acceso_pro_db.vehiculos ve ON al.target_id = ve.id AND al.target_type = 'vehiculo'
            LEFT JOIN personal_db.personal_comision pc ON al.target_id = pc.id AND al.target_type = 'personal_comision'";

    $types = '';
    $values = [];
    $sql_where = [];

    if (isset($access_type) && $access_type !== '' && $access_type !== 'undefined') {
        $sql_where[] = "al.target_type = ?";
        $types .= 's';
        $values[] = $access_type;
    }

    $rango = procesarRangoFechas($fecha_inicio, $fecha_fin);
    if (!empty($rango['donde'])) {
        $sql_where[] = "al.log_time " . $rango['donde'];
        $types .= $rango['tipos'];
        $values = array_merge($values, $rango['valores']);
    }

    $sql = aplicarFiltros($sql, $types, $values, $sql_where);
    $sql .= " ORDER BY al.log_time DESC";

    $stmt = $conn_acceso->prepare($sql);
    if (!$stmt) throw new Exception('Error SQL: ' . $conn_acceso->error);

    if (!empty($values)) $stmt->bind_param($types, ...$values);
    $stmt->execute();
    $result = $stmt->get_result();
    $data = $result->fetch_all(MYSQLI_ASSOC);
    $stmt->close();

    return $data;
}

/**
 * Reporte: Acceso de Vehículos
 */
function obtenerReporteAccesoVehiculos($conn_acceso, $conn_personal, $patente, $fecha_inicio, $fecha_fin) {
    $sql = "SELECT al.action, al.log_time, v.id, v.patente, v.marca, v.modelo, v.tipo, v.status,
            CASE WHEN v.asociado_tipo IN ('PERSONAL', 'FUNCIONARIO', 'RESIDENTE', 'FISCAL') THEN TRIM(CONCAT_WS(' ', p.Grado, p.Nombres, p.Paterno, p.Materno))
                 WHEN v.asociado_tipo IN ('EMPLEADO', 'EMPRESA') THEN TRIM(CONCAT_WS(' ', ee.nombre, ee.paterno, ee.materno))
                 WHEN v.asociado_tipo = 'VISITA' THEN TRIM(CONCAT_WS(' ', vis.nombre, vis.paterno, vis.materno))
                 ELSE 'N/A'
            END as personal_nombre_completo
            FROM acceso_pro_db.access_logs al
            JOIN acceso_pro_db.vehiculos v ON al.target_id = v.id
            LEFT JOIN personal_db.personal p ON v.asociado_id = p.id AND v.asociado_tipo IN ('PERSONAL', 'FUNCIONARIO', 'RESIDENTE', 'FISCAL')
            LEFT JOIN acceso_pro_db.empresa_empleados ee ON v.asociado_id = ee.id AND v.asociado_tipo IN ('EMPLEADO', 'EMPRESA')
            LEFT JOIN acceso_pro_db.visitas vis ON v.asociado_id = vis.id AND v.asociado_tipo = 'VISITA'
            WHERE al.target_type = 'vehiculo'";

    $types = '';
    $values = [];
    $sql_where = [];

    if (!empty($patente)) {
        $sql_where[] = "v.patente = ?";
        $types .= 's';
        $values[] = $patente;
    }

    $rango = procesarRangoFechas($fecha_inicio, $fecha_fin);
    if (!empty($rango['donde'])) {
        $sql_where[] = "al.log_time " . $rango['donde'];
        $types .= $rango['tipos'];
        $values = array_merge($values, $rango['valores']);
    }

    $sql = aplicarFiltros($sql, $types, $values, $sql_where);
    $sql .= " ORDER BY al.log_time DESC";

    $stmt = $conn_acceso->prepare($sql);
    if (!$stmt) throw new Exception('Error SQL: ' . $conn_acceso->error);

    if (!empty($values)) $stmt->bind_param($types, ...$values);
    $stmt->execute();
    $result = $stmt->get_result();
    $data = $result->fetch_all(MYSQLI_ASSOC);
    $stmt->close();

    return $data;
}

/**
 * Reporte: Acceso de Visitas
 */
function obtenerReporteAccesoVisitas($conn_acceso, $conn_personal, $rut, $fecha_inicio, $fecha_fin) {
    $sql = "SELECT al.action, al.log_time, v.*
            FROM acceso_pro_db.access_logs al
            JOIN acceso_pro_db.visitas v ON al.target_id = v.id
            WHERE al.target_type = 'visita'";

    $types = '';
    $values = [];
    $sql_where = [];

    if (!empty($rut)) {
        $sql_where[] = "v.rut = ?";
        $types .= 's';
        $values[] = $rut;
    }

    $rango = procesarRangoFechas($fecha_inicio, $fecha_fin);
    if (!empty($rango['donde'])) {
        $sql_where[] = "al.log_time " . $rango['donde'];
        $types .= $rango['tipos'];
        $values = array_merge($values, $rango['valores']);
    }

    $sql = aplicarFiltros($sql, $types, $values, $sql_where);
    $sql .= " ORDER BY al.log_time DESC";

    $stmt = $conn_acceso->prepare($sql);
    if (!$stmt) throw new Exception('Error SQL: ' . $conn_acceso->error);

    if (!empty($values)) $stmt->bind_param($types, ...$values);
    $stmt->execute();
    $result = $stmt->get_result();
    $data = $result->fetch_all(MYSQLI_ASSOC);
    $stmt->close();

    return $data;
}

/**
 * Reporte: Personal en Comisión
 */
function obtenerReportePersonalComision($conn_acceso, $conn_personal, $rut, $fecha_inicio, $fecha_fin) {
    $sql = "SELECT pc.nombre_completo, pc.rut, pc.unidad_origen, pc.fecha_fin, al.action, al.log_time
            FROM personal_db.personal_comision pc
            JOIN acceso_pro_db.access_logs al ON pc.id = al.target_id AND al.target_type = 'personal_comision'";

    $types = '';
    $values = [];
    $sql_where = [];

    if (!empty($rut)) {
        $sql_where[] = "pc.rut = ?";
        $types .= 's';
        $values[] = $rut;
    }

    $rango = procesarRangoFechas($fecha_inicio, $fecha_fin);
    if (!empty($rango['donde'])) {
        $sql_where[] = "al.log_time " . $rango['donde'];
        $types .= $rango['tipos'];
        $values = array_merge($values, $rango['valores']);
    }

    $sql = aplicarFiltros($sql, $types, $values, $sql_where);
    $sql .= " ORDER BY al.log_time DESC";

    $stmt = $conn_acceso->prepare($sql);
    if (!$stmt) throw new Exception('Error SQL: ' . $conn_acceso->error);

    if (!empty($values)) $stmt->bind_param($types, ...$values);
    $stmt->execute();
    $result = $stmt->get_result();
    $data = $result->fetch_all(MYSQLI_ASSOC);
    $stmt->close();

    return $data;
}

/**
 * Reporte: Salidas No Autorizadas
 */
function obtenerReporteSalidaNoAutorizada($conn_acceso, $conn_personal, $fecha_inicio, $fecha_fin) {
    $sql = "SELECT al.log_time, p.Grado, p.Nombres, p.Paterno, p.Materno, p.NrRut
            FROM acceso_pro_db.access_logs al
            JOIN personal_db.personal p ON al.target_id = p.id
            LEFT JOIN acceso_pro_db.horas_extra he ON p.NrRut = he.personal_rut AND DATE(al.log_time) = DATE(he.fecha_hora_termino)
            WHERE al.target_type = 'personal'
            AND al.action = 'salida'
            AND HOUR(al.log_time) > 17
            AND he.id IS NULL";

    $types = '';
    $values = [];

    $rango = procesarRangoFechas($fecha_inicio, $fecha_fin);
    if (!empty($rango['donde'])) {
        $sql .= " AND al.log_time " . $rango['donde'];
        $types = $rango['tipos'];
        $values = $rango['valores'];
    }

    $sql .= " ORDER BY al.log_time DESC";

    $stmt = $conn_acceso->prepare($sql);
    if (!$stmt) throw new Exception('Error SQL: ' . $conn_acceso->error);

    if (!empty($values)) $stmt->bind_param($types, ...$values);
    $stmt->execute();
    $result = $stmt->get_result();
    $data = $result->fetch_all(MYSQLI_ASSOC);
    $stmt->close();

    return $data;
}

/**
 * Obtiene datos del reporte según tipo
 */
function obtenerReporte($conn_acceso, $conn_personal, $report_type, $params) {
    $report_type = strtolower(trim($report_type));

    switch ($report_type) {
        case 'acceso_personal':
            return obtenerReporteAccesoPersonal(
                $conn_acceso, $conn_personal,
                $params['rut'] ?? '',
                $params['fecha_inicio'] ?? null,
                $params['fecha_fin'] ?? null
            );
        case 'horas_extra':
            return obtenerReporteHorasExtra(
                $conn_acceso, $conn_personal,
                $params['fecha_inicio'] ?? null,
                $params['fecha_fin'] ?? null
            );
        case 'acceso_general':
            return obtenerReporteAccesoGeneral(
                $conn_acceso, $conn_personal,
                $params['access_type'] ?? null,
                $params['fecha_inicio'] ?? null,
                $params['fecha_fin'] ?? null
            );
        case 'acceso_vehiculos':
            return obtenerReporteAccesoVehiculos(
                $conn_acceso, $conn_personal,
                $params['patente'] ?? '',
                $params['fecha_inicio'] ?? null,
                $params['fecha_fin'] ?? null
            );
        case 'acceso_visitas':
            return obtenerReporteAccesoVisitas(
                $conn_acceso, $conn_personal,
                $params['rut'] ?? '',
                $params['fecha_inicio'] ?? null,
                $params['fecha_fin'] ?? null
            );
        case 'personal_comision':
            return obtenerReportePersonalComision(
                $conn_acceso, $conn_personal,
                $params['rut'] ?? '',
                $params['fecha_inicio'] ?? null,
                $params['fecha_fin'] ?? null
            );
        case 'salida_no_autorizada':
            return obtenerReporteSalidaNoAutorizada(
                $conn_acceso, $conn_personal,
                $params['fecha_inicio'] ?? null,
                $params['fecha_fin'] ?? null
            );
        default:
            throw new Exception('Tipo de reporte no válido: ' . $report_type);
    }
}

// ============================================================================
// PDF GENERATION
// ============================================================================

class ReportePDF extends FPDF {
    function Header() {
        $this->SetFillColor(52, 152, 219);
        $this->SetTextColor(255, 255, 255);
        $this->SetFont('Arial', 'B', 14);
        $this->Cell(0, 10, utf8_decode('REPORTE DE SISTEMA DE ACCESO'), 0, 1, 'C', true);

        $this->SetTextColor(0, 0, 0);
        $this->SetFont('Arial', '', 9);
        $this->Cell(0, 6, utf8_decode('Generado: ' . date('d/m/Y H:i:s')), 0, 1, 'R');
        $this->Ln(3);
    }

    function Footer() {
        $this->SetY(-15);
        $this->SetTextColor(100, 100, 100);
        $this->SetFont('Arial', 'I', 8);
        $this->Cell(0, 10, utf8_decode('Página ') . $this->PageNo() . '/{nb}', 0, 0, 'C');
    }
}

/**
 * Genera contenido del PDF
 */
function generarContenidoPDF($pdf, $report_type, $data, $filters) {
    $titles = [
        'acceso_personal' => 'Reporte de Historial de Acceso por Persona',
        'horas_extra' => 'Reporte de Salida Posterior por período',
        'acceso_general' => 'Reporte de Acceso General',
        'acceso_vehiculos' => 'Reporte de Acceso de Vehículos',
        'acceso_visitas' => 'Reporte de Acceso de Visitas',
        'personal_comision' => 'Reporte de Personal en Comisión',
        'salida_no_autorizada' => 'Reporte de Salidas No Autorizadas'
    ];

    $pdf->SetTextColor(33, 33, 33);
    $pdf->SetFont('Arial', 'B', 12);
    $pdf->Cell(0, 8, utf8_decode($titles[$report_type] ?? 'Reporte'), 0, 1, 'C');
    $pdf->Ln(1);

    // Filtros aplicados
    $pdf->SetFont('Arial', 'B', 9);
    $pdf->SetTextColor(52, 152, 219);
    $pdf->Cell(0, 6, utf8_decode('Filtros Aplicados:'), 0, 1, 'L');
    $pdf->SetTextColor(0, 0, 0);
    $pdf->SetFont('Arial', '', 8);

    $hasFilters = false;
    $fecha_inicio = $filters['fecha_inicio'] ?? '';
    $fecha_fin = $filters['fecha_fin'] ?? '';
    $rut = $filters['rut'] ?? '';
    $access_type = $filters['access_type'] ?? '';
    $patente = $filters['patente'] ?? '';

    if (!empty($fecha_inicio) && !empty($fecha_fin)) {
        $pdf->Cell(0, 5, utf8_decode("     - Rango de Fechas: Del " . date('d/m/Y', strtotime($fecha_inicio)) . " al " . date('d/m/Y', strtotime($fecha_fin))), 0, 1, 'L');
        $hasFilters = true;
    }
    if (!empty($rut) && $rut !== 'undefined') {
        $pdf->Cell(0, 5, utf8_decode("     - RUT: " . $rut), 0, 1, 'L');
        $hasFilters = true;
    }
    if (!empty($access_type) && $access_type !== 'undefined') {
        $pdf->Cell(0, 5, utf8_decode("     - Tipo de Acceso: " . ucfirst($access_type)), 0, 1, 'L');
        $hasFilters = true;
    }
    if (!empty($patente) && $patente !== 'undefined') {
        $pdf->Cell(0, 5, utf8_decode("     - Patente: " . $patente), 0, 1, 'L');
        $hasFilters = true;
    }

    if (!$hasFilters) {
        $pdf->Cell(0, 5, utf8_decode("     - Ninguno"), 0, 1, 'L');
    }

    $pdf->Ln(5);

    // Table headers según tipo de reporte
    $headers = [];
    $widths = [];

    switch ($report_type) {
        case 'acceso_personal':
            $headers = ['Nombre Completo', 'Acción', 'Punto de Acceso', 'Fecha y Hora'];
            $widths = [80, 25, 40, 55];
            break;
        case 'horas_extra':
            $headers = ['Personal', 'Fecha', 'Motivo', 'Hora Salida Posterior'];
            $widths = [80, 30, 100, 30];
            break;
        case 'acceso_general':
            $headers = ['ID/Nombre', 'Tipo', 'Accion', 'Fecha y Hora'];
            $widths = [100, 40, 30, 60];
            break;
        case 'acceso_vehiculos':
            $headers = ['Patente', 'Marca/Modelo', 'Asociado', 'Accion', 'Fecha y Hora'];
            $widths = [40, 60, 70, 30, 50];
            break;
        case 'acceso_visitas':
            $headers = ['Nombre', 'RUT', 'Tipo', 'Accion', 'Fecha y Hora'];
            $widths = [70, 30, 40, 30, 60];
            break;
        case 'personal_comision':
            $headers = ['Nombre', 'RUT', 'Unidad Origen', 'Inicio', 'Fin'];
            $widths = [80, 30, 60, 30, 30];
            break;
        case 'salida_no_autorizada':
            $headers = ['Nombre', 'RUT', 'Fecha y Hora Salida'];
            $widths = [100, 40, 60];
            break;
    }

    // Headers
    $pdf->SetFillColor(52, 152, 219);
    $pdf->SetTextColor(255, 255, 255);
    $pdf->SetFont('Arial', 'B', 8);
    for ($i = 0; $i < count($headers); $i++) {
        $pdf->Cell($widths[$i], 7, utf8_decode($headers[$i]), 1, 0, 'C', true);
    }
    $pdf->Ln();

    // Data rows
    $pdf->SetTextColor(0, 0, 0);
    $pdf->SetFont('Arial', '', 8);
    foreach ($data as $row) {
        switch ($report_type) {
            case 'acceso_personal':
                $nombre = trim(utf8_decode(($row['Grado'] ?? '') . ' ' . ($row['Nombres'] ?? '') . ' ' . ($row['Paterno'] ?? '') . ' ' . ($row['Materno'] ?? '')));
                $pdf->Cell($widths[0], 6, $nombre, 'LR');
                $pdf->Cell($widths[1], 6, substr(strtoupper($row['action'] ?? ''), 0, 3), 'LR');
                $punto = $row['punto_acceso'] ?? '';
                if (stripos($punto, 'portico') !== false) $punto = 'PORT';
                elseif (stripos($punto, 'control') !== false || stripos($punto, 'unidad') !== false) $punto = 'UNID';
                else $punto = substr(strtoupper($punto), 0, 4);
                $pdf->Cell($widths[2], 6, $punto, 'LR');
                $fecha = (!empty($row['log_time']) && strtotime($row['log_time'])) ? date('d/m/Y H:i', strtotime($row['log_time'])) : 'Sin registro';
                $pdf->Cell($widths[3], 6, $fecha, 'LR');
                break;
            case 'horas_extra':
                $nombre = $row['personal_nombre_completo'] ?? '';
                $pdf->Cell($widths[0], 6, utf8_decode(trim($nombre)), 'LR');
                $fecha = (!empty($row['fecha_hora_termino']) && strtotime($row['fecha_hora_termino'])) ? date('d/m/Y', strtotime($row['fecha_hora_termino'])) : 'Sin registro';
                $pdf->Cell($widths[1], 6, $fecha, 'LR');
                $pdf->Cell($widths[2], 6, utf8_decode($row['motivo'] ?? ''), 'LR');
                $hora = (!empty($row['fecha_hora_termino']) && strtotime($row['fecha_hora_termino'])) ? date('H:i', strtotime($row['fecha_hora_termino'])) : 'Sin registro';
                $pdf->Cell($widths[3], 6, $hora, 'LR');
                break;
            case 'acceso_general':
                $pdf->Cell($widths[0], 6, utf8_decode($row['name'] ?? 'N/A'), 'LR');
                $pdf->Cell($widths[1], 6, $row['target_type'], 'LR');
                $pdf->Cell($widths[2], 6, $row['action'], 'LR');
                $fecha = (!empty($row['log_time']) && strtotime($row['log_time'])) ? date('d/m/Y H:i', strtotime($row['log_time'])) : 'Sin registro';
                $pdf->Cell($widths[3], 6, $fecha, 'LR');
                break;
            case 'acceso_vehiculos':
                $asociado = $row['personal_nombre_completo'] ?? 'N/A';
                $pdf->Cell($widths[0], 6, $row['patente'], 'LR');
                $pdf->Cell($widths[1], 6, utf8_decode(($row['marca'] ?? '') . ' ' . ($row['modelo'] ?? '')), 'LR');
                $pdf->Cell($widths[2], 6, utf8_decode($asociado), 'LR');
                $pdf->Cell($widths[3], 6, $row['action'], 'LR');
                $fecha = (!empty($row['log_time']) && strtotime($row['log_time'])) ? date('d/m/Y H:i', strtotime($row['log_time'])) : 'Sin registro';
                $pdf->Cell($widths[4], 6, $fecha, 'LR');
                break;
            case 'acceso_visitas':
                $pdf->Cell($widths[0], 6, utf8_decode($row['nombre']), 'LR');
                $pdf->Cell($widths[1], 6, $row['rut'], 'LR');
                $pdf->Cell($widths[2], 6, utf8_decode($row['tipo'] ?? 'N/A'), 'LR');
                $pdf->Cell($widths[3], 6, $row['action'], 'LR');
                $fecha = (!empty($row['log_time']) && strtotime($row['log_time'])) ? date('d/m/Y H:i', strtotime($row['log_time'])) : 'Sin registro';
                $pdf->Cell($widths[4], 6, $fecha, 'LR');
                break;
            case 'personal_comision':
                $pdf->Cell($widths[0], 6, utf8_decode($row['nombre_completo']), 'LR');
                $pdf->Cell($widths[1], 6, $row['rut'], 'LR');
                $pdf->Cell($widths[2], 6, utf8_decode($row['unidad_origen']), 'LR');
                $fecha_fin = (!empty($row['fecha_fin']) && strtotime($row['fecha_fin'])) ? date('d/m/Y', strtotime($row['fecha_fin'])) : 'Sin registro';
                $pdf->Cell($widths[3], 6, $fecha_fin, 'LR');
                $pdf->Cell($widths[4], 6, utf8_decode($row['action'] ?? 'N/A'), 'LR');
                break;
            case 'salida_no_autorizada':
                $nombre = trim(utf8_decode(($row['Grado'] ?? '') . ' ' . ($row['Nombres'] ?? '') . ' ' . ($row['Paterno'] ?? '') . ' ' . ($row['Materno'] ?? '')));
                $pdf->Cell($widths[0], 6, $nombre, 'LR');
                $pdf->Cell($widths[1], 6, $row['NrRut'], 'LR');
                $fecha = (!empty($row['log_time']) && strtotime($row['log_time'])) ? date('d/m/Y H:i', strtotime($row['log_time'])) : 'Sin registro';
                $pdf->Cell($widths[2], 6, $fecha, 'LR');
                break;
        }
        $pdf->Ln();
    }

    // Footer
    $pdf->Cell(array_sum($widths), 0, '', 'T');
    $pdf->Ln(5);
    $pdf->SetFont('Arial', '', 8);
    $pdf->SetTextColor(100, 100, 100);
    $pdf->Cell(0, 5, utf8_decode('Total de registros: ' . count($data)), 0, 1, 'L');
    $pdf->Cell(0, 5, utf8_decode('Reporte generado automáticamente por el Sistema de Control de Acceso'), 0, 1, 'L');
}

// ============================================================================
// MAIN REQUEST HANDLER
// ============================================================================

try {
    $config = DatabaseConfig::getInstance();
    $conn_acceso = $config->getAccessDB();
    $conn_personal = $config->getPersonalDB();

    $report_type = $_GET['report_type'] ?? '';
    if (empty($report_type)) {
        ApiResponse::badRequest('Falta parámetro requerido: report_type');
    }

    // Obtener datos del reporte
    $data = obtenerReporte($conn_acceso, $conn_personal, $report_type, $_GET);

    // Determinar formato de salida
    if (isset($_GET['export']) && $_GET['export'] === 'pdf') {
        // Generar PDF
        $pdf = new ReportePDF();
        $pdf->AliasNbPages();
        $pdf->AddPage('L', 'A4');
        $pdf->SetFont('Arial', '', 10);

        generarContenidoPDF($pdf, $report_type, $data, $_GET);
        $pdf->Output();
    } else {
        // Generar JSON
        ApiResponse::success([
            'report_type' => $report_type,
            'total' => count($data),
            'data' => $data
        ], 'Reporte generado exitosamente');
    }

} catch (Exception $e) {
    if (isset($_GET['export']) && $_GET['export'] === 'pdf') {
        // Error en PDF
        $pdf = new ReportePDF();
        $pdf->AddPage();
        $pdf->SetFont('Arial', 'B', 12);
        $pdf->SetTextColor(255, 0, 0);
        $pdf->MultiCell(0, 10, 'Error: ' . utf8_decode($e->getMessage()));
        $pdf->Output();
    } else {
        // Error en JSON
        ApiResponse::serverError('Error al generar reporte: ' . $e->getMessage());
    }
} finally {
    if (isset($conn_acceso) && $conn_acceso instanceof mysqli) {
        $conn_acceso->close();
    }
    if (isset($conn_personal) && $conn_personal instanceof mysqli) {
        $conn_personal->close();
    }
}

?>

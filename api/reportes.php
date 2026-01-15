<?php
require_once 'database/db_acceso.php';
require_once '../../fpdf/fpdf.php';

// Manejador de errores para asegurar que siempre se devuelva JSON o un error de PDF
set_error_handler(function($severity, $message, $file, $line) {
    if (!(error_reporting() & $severity)) {
        return;
    }
    if (isset($_GET['export']) && $_GET['export'] == 'pdf') {
        $pdf = new FPDF();
        $pdf->AddPage();
        $pdf->SetFont('Arial', 'B', 12);
        $pdf->SetTextColor(255, 0, 0);
        $pdf->MultiCell(0, 10, 'Error interno del servidor: ' . $message);
        $pdf->Output();
    } else {
        http_response_code(500);
        if (!headers_sent()) {
            header('Content-Type: application/json');
        }
        echo json_encode([
            'error' => 'Error interno del servidor.',
            'message' => $message,
            'file' => $file,
            'line' => $line
        ]);
    }
    exit;
});

class PDF extends FPDF
{
    // Cabecera de página
    function Header()
    {
        // Fondo de la cabecera
        $this->SetFillColor(52, 152, 219); // Azul
        $this->SetTextColor(255, 255, 255); // Blanco
        $this->SetFont('Arial','B',14);
        $this->Cell(0, 10, utf8_decode('REPORTE DE SISTEMA DE ACCESO'), 0, 1, 'C', true);

        // Información adicional
        $this->SetTextColor(0, 0, 0);
        $this->SetFont('Arial', '', 9);
        $this->Cell(0, 6, utf8_decode('Generado: ' . date('d/m/Y H:i:s')), 0, 1, 'R');
        $this->Ln(3);
    }

    // Pie de página
    function Footer()
    {
        $this->SetY(-15);
        $this->SetTextColor(100, 100, 100);
        $this->SetFont('Arial','I',8);
        $this->Cell(0, 10, utf8_decode('Página ') . $this->PageNo() . '/{nb}', 0, 0, 'C');
    }
}

// --- LÓGICA PRINCIPAL ---
try {
    if (isset($_GET['export']) && $_GET['export'] == 'pdf') {
        // --- GENERACIÓN DE PDF ---
        require_once 'database/db_personal.php'; // Conexión a BD de personal necesaria para los joins

        $report_type = $_GET['report_type'] ?? '';
        
        $pdf = new PDF();
        $pdf->AliasNbPages();
        $pdf->AddPage('L', 'A4'); // Formato horizontal para más espacio
        $pdf->SetFont('Arial', '', 10);

        // Obtener los datos (la misma lógica que para JSON)
        $data = fetchDataForReport($conn_acceso, $conn_personal, $report_type, $_GET);

        // Generar el contenido del PDF
        generatePdfContent($pdf, $report_type, $data, $_GET);

        $pdf->Output();

    } else {
        // --- GENERACIÓN DE JSON ---
        header('Content-Type: application/json');
        require_once 'database/db_personal.php';

        $report_type = $_GET['report_type'] ?? '';
        if (empty($report_type)) {
            http_response_code(400);
            echo json_encode(['error' => 'Falta el parámetro report_type']);
            exit;
        }

        $response = fetchDataForReport($conn_acceso, $conn_personal, $report_type, $_GET);
        echo json_encode($response);
    }

} catch (Exception $e) {
    http_response_code(500);
    if (!headers_sent()) {
        header('Content-Type: application/json');
    }
    echo json_encode([
        'error' => 'Excepción capturada en el script.',
        'message' => $e->getMessage()
    ]);
} finally {
    if (isset($conn_acceso) && $conn_acceso instanceof mysqli) {
        $conn_acceso->close();
    }
    if (isset($conn_personal) && $conn_personal instanceof mysqli) {
        $conn_personal->close();
    }
}

/**
 * Función centralizada para obtener los datos de cualquier reporte.
 */
function fetchDataForReport($conn_acceso, $conn_personal, $report_type, $params_get) {
    $fecha_inicio = $params_get['fecha_inicio'] ?? null;
    $fecha_fin = $params_get['fecha_fin'] ?? null;
    
    $types = '';
    $values = [];
    $sql_where = [];

    $sql = getBaseQueryForReport($report_type);

    switch ($report_type) {
        case 'acceso_personal':
            $rut = $params_get['rut'] ?? '';
            if (empty($rut)) throw new Exception('Falta el parámetro rut.');
            
            $sql_where[] = "al.target_type = 'personal'";
            
            $sql_where[] = "p.NrRut = ?";
            $types .= 's';
            $values[] = $rut;

            if ($fecha_inicio && $fecha_fin && !empty($fecha_inicio) && !empty($fecha_fin)) {
                $sql_where[] = "al.log_time BETWEEN ? AND ?";
                $types .= 'ss';
                $fecha_fin_obj = new DateTime($fecha_fin);
                $fecha_fin_obj->modify('+1 day');
                $values[] = $fecha_inicio;
                $values[] = $fecha_fin_obj->format('Y-m-d');
            }
            break;

        case 'horas_extra':
            if ($fecha_inicio && $fecha_fin && !empty($fecha_inicio) && !empty($fecha_fin)) {
                $sql_where[] = "fecha_hora_termino BETWEEN ? AND ?";
                $types .= 'ss';
                $fecha_fin_obj = new DateTime($fecha_fin);
                $fecha_fin_obj->modify('+1 day');
                $values[] = $fecha_inicio;
                $values[] = $fecha_fin_obj->format('Y-m-d');
            }
            break;

        case 'acceso_general':
            $access_type = $params_get['access_type'] ?? null;
            if (isset($access_type) && $access_type !== '' && $access_type !== 'undefined') {
                $sql_where[] = "al.target_type = ?";
                $types .= 's';
                $values[] = $access_type;
            }
            if ($fecha_inicio && $fecha_fin && !empty($fecha_inicio) && !empty($fecha_fin)) {
                $sql_where[] = "al.log_time BETWEEN ? AND ?";
                $types .= 'ss';
                $fecha_fin_obj = new DateTime($fecha_fin);
                $fecha_fin_obj->modify('+1 day');
                $values[] = $fecha_inicio;
                $values[] = $fecha_fin_obj->format('Y-m-d');
            }
            break;

        case 'acceso_vehiculos':
            $patente = $params_get['patente'] ?? '';
            if (!empty($patente)) {
                $sql_where[] = "v.patente = ?";
                $types .= 's';
                $values[] = $patente;
            }
            if ($fecha_inicio && $fecha_fin && !empty($fecha_inicio) && !empty($fecha_fin)) {
                $sql_where[] = "al.log_time BETWEEN ? AND ?";
                $types .= 'ss';
                $fecha_fin_obj = new DateTime($fecha_fin);
                $fecha_fin_obj->modify('+1 day');
                $values[] = $fecha_inicio;
                $values[] = $fecha_fin_obj->format('Y-m-d');
            }
            break;

        case 'acceso_visitas':
            $rut = $params_get['rut'] ?? '';
            if (!empty($rut)) {
                $sql_where[] = "v.rut = ?";
                $types .= 's';
                $values[] = $rut;
            }
            if ($fecha_inicio && $fecha_fin && !empty($fecha_inicio) && !empty($fecha_fin)) {
                $sql_where[] = "al.log_time BETWEEN ? AND ?";
                $types .= 'ss';
                $fecha_fin_obj = new DateTime($fecha_fin);
                $fecha_fin_obj->modify('+1 day');
                $values[] = $fecha_inicio;
                $values[] = $fecha_fin_obj->format('Y-m-d');
            }
            break;

        case 'personal_comision':
            $rut = $params_get['rut'] ?? '';
            if (!empty($rut)) {
                $sql_where[] = "pc.rut = ?";
                $types .= 's';
                $values[] = $rut;
            }
            if ($fecha_inicio && $fecha_fin && !empty($fecha_inicio) && !empty($fecha_fin)) {
                $sql_where[] = "al.log_time BETWEEN ? AND ?";
                $types .= 'ss';
                $fecha_fin_obj = new DateTime($fecha_fin);
                $fecha_fin_obj->modify('+1 day');
                $values[] = $fecha_inicio;
                $values[] = $fecha_fin_obj->format('Y-m-d');
            }
            break;

        case 'salida_no_autorizada':
            $sql = "SELECT
                        al.log_time,
                        p.Grado,
                        p.Nombres,
                        p.Paterno,
                        p.Materno,
                        p.NrRut
                    FROM
                        acceso_pro_db.access_logs al
                    JOIN
                        personal_db.personal p ON al.target_id = p.id
                    LEFT JOIN
                        acceso_pro_db.horas_extra he ON p.NrRut = he.personal_rut AND DATE(al.log_time) = DATE(he.fecha_hora_termino)
                    WHERE
                        al.target_type = 'personal'
                    AND
                        al.action = 'salida'
                    AND
                        HOUR(al.log_time) > 17
                    AND
                        he.id IS NULL";

            if ($fecha_inicio && $fecha_fin && !empty($fecha_inicio) && !empty($fecha_fin)) {
                $sql .= " AND al.log_time BETWEEN ? AND ?";
                $types .= 'ss';
                $fecha_fin_obj = new DateTime($fecha_fin);
                $fecha_fin_obj->modify('+1 day');
                $values[] = $fecha_inicio;
                $values[] = $fecha_fin_obj->format('Y-m-d');
            }
            $sql .= " ORDER BY al.log_time DESC";
            
            $stmt = $conn_acceso->prepare($sql);
            if ($stmt === false) throw new Exception('Error al preparar la consulta: ' . $conn_acceso->error);
            if (!empty($types)) $stmt->bind_param($types, ...$values);
            $stmt->execute();
            $result = $stmt->get_result();
            $data = $result->fetch_all(MYSQLI_ASSOC);
            $stmt->close();
            return $data;

        default:
            throw new Exception('Tipo de reporte no válido');
    }

    if (!empty($sql_where)) {
        if (stripos($sql, 'WHERE') !== false) {
            $sql .= ' AND ' . implode(' AND ', $sql_where);
        } else {
            $sql .= ' WHERE ' . implode(' AND ', $sql_where);
        }
    }
    $sql .= getOrderByForReport($report_type);

    $stmt = $conn_acceso->prepare($sql);
    if ($stmt === false) {
        throw new Exception('Error al preparar la consulta: ' . $conn_acceso->error . " SQL: " . $sql);
    }
    
    if (!empty($types)) {
        $stmt->bind_param($types, ...$values);
    }
    $stmt->execute();
    $result = $stmt->get_result();
    $data = $result->fetch_all(MYSQLI_ASSOC);
    $stmt->close();
    return $data;
}

function getBaseQueryForReport($report_type) {
    switch ($report_type) {
        case 'acceso_personal':
              return "SELECT al.action, al.log_time, al.punto_acceso, p.Grado, p.Nombres, p.Paterno, p.Materno FROM acceso_pro_db.access_logs al JOIN personal_db.personal p ON al.target_id = p.id WHERE al.target_type = 'personal'";
        case 'horas_extra':
            return "SELECT he.*, CONCAT_WS(' ', p.Grado, p.Nombres, p.Paterno, p.Materno) as personal_nombre_completo FROM acceso_pro_db.horas_extra he LEFT JOIN personal_db.personal p ON he.personal_rut = p.NrRut";
        case 'acceso_general':
            return "SELECT al.target_id, al.target_type, al.action, al.log_time, (CASE al.target_type WHEN 'personal' THEN CONCAT_WS(' ', p.Grado, p.Nombres, p.Paterno, p.Materno) WHEN 'visita' THEN v.nombre WHEN 'vehiculo' THEN ve.patente WHEN 'personal_comision' THEN pc.nombre_completo ELSE al.target_id END) as name, p.Paterno, p.Materno FROM acceso_pro_db.access_logs al LEFT JOIN personal_db.personal p ON al.target_id = p.id AND al.target_type = 'personal' LEFT JOIN acceso_pro_db.visitas v ON al.target_id = v.id AND al.target_type = 'visita' LEFT JOIN acceso_pro_db.vehiculos ve ON al.target_id = ve.id AND al.target_type = 'vehiculo' LEFT JOIN personal_db.personal_comision pc ON al.target_id = pc.id AND al.target_type = 'personal_comision'";
        case 'acceso_vehiculos':
              return "SELECT al.action, al.log_time, v.id, v.patente, v.marca, v.modelo, v.tipo, v.status, v.asociado_tipo, CASE WHEN v.asociado_tipo IN ('PERSONAL', 'FUNCIONARIO', 'RESIDENTE', 'FISCAL') THEN TRIM(CONCAT_WS(' ', p.Grado, p.Nombres, p.Paterno, p.Materno)) WHEN v.asociado_tipo IN ('EMPLEADO', 'EMPRESA') THEN TRIM(CONCAT_WS(' ', ee.nombre, ee.paterno, ee.materno)) WHEN v.asociado_tipo = 'VISITA' THEN TRIM(CONCAT_WS(' ', vis.nombre, vis.paterno, vis.materno)) ELSE 'N/A' END as personal_nombre_completo FROM acceso_pro_db.access_logs al JOIN acceso_pro_db.vehiculos v ON al.target_id = v.id LEFT JOIN personal_db.personal p ON v.asociado_id = p.id AND v.asociado_tipo IN ('PERSONAL', 'FUNCIONARIO', 'RESIDENTE', 'FISCAL') LEFT JOIN acceso_pro_db.empresa_empleados ee ON v.asociado_id = ee.id AND v.asociado_tipo IN ('EMPLEADO', 'EMPRESA') LEFT JOIN acceso_pro_db.visitas vis ON v.asociado_id = vis.id AND v.asociado_tipo = 'VISITA' WHERE al.target_type = 'vehiculo'";
        case 'acceso_visitas':
              return "SELECT al.action, al.log_time, v.* FROM acceso_pro_db.access_logs al JOIN acceso_pro_db.visitas v ON al.target_id = v.id WHERE al.target_type = 'visita'";
        case 'personal_comision':
            return "SELECT pc.nombre_completo, pc.rut, pc.unidad_origen, pc.fecha_fin, al.action, al.log_time FROM personal_db.personal_comision pc JOIN acceso_pro_db.access_logs al ON pc.id = al.target_id AND al.target_type = 'personal_comision'";
        case 'salida_no_autorizada':
            return "SELECT al.action, al.log_time, p.Grado, p.Nombres, p.Paterno, p.Materno, p.NrRut FROM acceso_pro_db.access_logs al JOIN personal_db.personal p ON al.target_id = p.id WHERE al.target_type = 'personal'";
        default: return '';
    }
}
function getOrderByForReport($report_type) {
    switch ($report_type) {
        case 'horas_extra': return " ORDER BY fecha_hora_termino DESC";
        case 'personal_comision': return " ORDER BY al.log_time DESC";
    default: return " ORDER BY al.log_time DESC";
    }
}

function generatePdfContent($pdf, $report_type, $data, $filters) {
    $titles = [
        'acceso_personal' => 'Reporte de Historial de Acceso por Persona',
        'horas_extra' => 'Reporte de Salida Posterior por período',
        'acceso_general' => 'Reporte de Acceso General',
        'acceso_vehiculos' => 'Reporte de Acceso de Vehículos',
        'acceso_visitas' => 'Reporte de Acceso de Visitas',
        'personal_comision' => 'Reporte de Personal en Comisión',
        'salida_no_autorizada' => 'Reporte de Salidas No Autorizadas'
    ];
    // Título del reporte
    $pdf->SetTextColor(33, 33, 33);
    $pdf->SetFont('Arial', 'B', 12);
    $pdf->Cell(0, 8, utf8_decode($titles[$report_type] ?? 'Reporte'), 0, 1, 'C');
    $pdf->Ln(1);

    // --- Filter Criteria ---
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
    // --- End Filter Criteria ---

    $pdf->SetFont('Arial', 'B', 10);

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

    // Headers con fondo azul y texto blanco
    $pdf->SetFillColor(52, 152, 219);
    $pdf->SetTextColor(255, 255, 255);
    $pdf->SetFont('Arial', 'B', 8);
    for ($i = 0; $i < count($headers); $i++) {
        $pdf->Cell($widths[$i], 7, utf8_decode($headers[$i]), 1, 0, 'C', true);
    }
    $pdf->Ln();

    // Reset colors for data rows
    $pdf->SetTextColor(0, 0, 0);
    $pdf->SetFont('Arial', '', 8);
    foreach ($data as $row) {
        switch ($report_type) {
            case 'acceso_personal':
                     // Nombre completo: Grado + Nombres + Paterno + Materno
                     $nombre_completo = trim(utf8_decode(($row['Grado'] ?? '') . ' ' . ($row['Nombres'] ?? '') . ' ' . ($row['Paterno'] ?? '') . ' ' . ($row['Materno'] ?? '')));
                     $pdf->Cell($widths[0], 6, $nombre_completo, 'LR');

                     // Acción con formato
                     $accion = strtoupper($row['action'] ?? '');
                     $pdf->Cell($widths[1], 6, substr($accion, 0, 3), 'LR');

                     // Punto de acceso
                     $punto = 'N/A';
                     if (!empty($row['punto_acceso'])) {
                         if (stripos($row['punto_acceso'], 'portico') !== false) {
                             $punto = 'PORT';
                         } elseif (stripos($row['punto_acceso'], 'control') !== false || stripos($row['punto_acceso'], 'unidad') !== false) {
                             $punto = 'UNID';
                         } else {
                             $punto = substr(strtoupper($row['punto_acceso']), 0, 4);
                         }
                     }
                     $pdf->Cell($widths[2], 6, $punto, 'LR');

                     // Fecha y hora
                     $fecha = (!empty($row['log_time']) && strtotime($row['log_time'])) ? date('d/m/Y H:i', strtotime($row['log_time'])) : 'Sin registro';
                     $pdf->Cell($widths[3], 6, $fecha, 'LR');
                break;
            case 'horas_extra':
                // Nombre del personal
                $nombre = $row['personal_nombre_completo'] ?? $row['personal_nombre'] ?? (($row['Grado'] ?? '') . ' ' . ($row['Nombres'] ?? '') . ' ' . ($row['Paterno'] ?? '') . ' ' . ($row['Materno'] ?? ''));
                $pdf->Cell($widths[0], 6, utf8_decode(trim($nombre)), 'LR');

                // Fecha - usar fecha_hora_termino (es la única fecha disponible)
                $fecha = 'Sin registro';
                if (!empty($row['fecha_hora_termino']) && strtotime($row['fecha_hora_termino'])) {
                    $fecha = date('d/m/Y', strtotime($row['fecha_hora_termino']));
                }
                $pdf->Cell($widths[1], 6, $fecha, 'LR');

                // Motivo
                $pdf->Cell($widths[2], 6, utf8_decode($row['motivo'] ?? ''), 'LR');

                // Hora salida - extraer la hora de fecha_hora_termino
                $hora = 'Sin registro';
                if (!empty($row['fecha_hora_termino']) && strtotime($row['fecha_hora_termino'])) {
                    $hora = date('H:i', strtotime($row['fecha_hora_termino']));
                }
                $pdf->Cell($widths[3], 6, $hora, 'LR');
                break;
            case 'acceso_general':
                 $name = $row['name'];
                 $pdf->Cell($widths[0], 6, utf8_decode($name), 'LR');
                 $pdf->Cell($widths[1], 6, $row['target_type'], 'LR');
                 $pdf->Cell($widths[2], 6, $row['action'], 'LR');
                     $fecha = (!empty($row['log_time']) && strtotime($row['log_time'])) ? date('d/m/Y H:i', strtotime($row['log_time'])) : 'Sin registro';
                     $pdf->Cell($widths[3], 6, $fecha, 'LR');
                break;
            case 'acceso_vehiculos':
                 $asociado = $row['personal_nombre_completo'] ?? 'N/A';
                 $pdf->Cell($widths[0], 6, $row['patente'], 'LR');
                 $pdf->Cell($widths[1], 6, utf8_decode($row['marca'] . ' ' . $row['modelo']), 'LR');
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
                $nombreCompleto = trim(utf8_decode(($row['Grado'] ?? '') . ' ' . ($row['Nombres'] ?? '') . ' ' . ($row['Paterno'] ?? '') . ' ' . ($row['Materno'] ?? '')));
                $pdf->Cell($widths[0], 6, $nombreCompleto, 'LR');
                $pdf->Cell($widths[1], 6, $row['NrRut'], 'LR');
                $fecha = (!empty($row['log_time']) && strtotime($row['log_time'])) ? date('d/m/Y H:i', strtotime($row['log_time'])) : 'Sin registro';
                $pdf->Cell($widths[2], 6, $fecha, 'LR');
                break;
        }
        $pdf->Ln();
    }
    // Línea inferior de la tabla
    $pdf->Cell(array_sum($widths), 0, '', 'T');
    $pdf->Ln(5);

    // Resumen
    $pdf->SetFont('Arial', '', 8);
    $pdf->SetTextColor(100, 100, 100);
    $pdf->Cell(0, 5, utf8_decode('Total de registros: ' . count($data)), 0, 1, 'L');
    $pdf->Cell(0, 5, utf8_decode('Reporte generado automáticamente por el Sistema de Control de Acceso'), 0, 1, 'L');
}

?>
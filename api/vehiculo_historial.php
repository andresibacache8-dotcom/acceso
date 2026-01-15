<?php
// api/vehiculo_historial.php
// Activar la visualización de errores para depuración
ini_set('display_errors', 1);
error_reporting(E_ALL);

// Iniciar sesión para tener acceso al usuario actual
session_start();

require_once 'database/db_acceso.php'; // Conexión a la BD de acceso
require_once 'database/db_personal.php'; // Conexión a la BD de personal
// La línea require_once 'auth.php' fue eliminada para evitar conflictos

header('Content-Type: application/json');
$method = $_SERVER['REQUEST_METHOD'];

// Función para enviar errores
function send_error($code, $message) {
    http_response_code($code);
    echo json_encode(['message' => $message]);
    exit;
}

// Verificar autenticación
if (!isset($_SESSION['logged_in']) || $_SESSION['logged_in'] !== true) {
    send_error(401, 'No autorizado');
}

if ($method === 'GET') {
    $vehiculo_id = $_GET['vehiculo_id'] ?? null;
    
    if (!$vehiculo_id) {
        send_error(400, 'ID de vehículo no proporcionado');
    }
    
    // Consulta para obtener historial del vehículo
    $stmt = $conn_acceso->prepare("
        SELECT vh.*, 
               u.username as usuario_nombre,
               COALESCE(CONCAT_WS(' ', p_ant.Grado, p_ant.Nombres, p_ant.Paterno), CONCAT_WS(' ', ee_ant.nombre, ee_ant.paterno, ee_ant.materno), CONCAT_WS(' ', vis_ant.nombre, vis_ant.paterno, vis_ant.materno)) as propietario_anterior_nombre,
               COALESCE(CONCAT_WS(' ', p_nue.Grado, p_nue.Nombres, p_nue.Paterno), CONCAT_WS(' ', ee_nue.nombre, ee_nue.paterno, ee_nue.materno), CONCAT_WS(' ', vis_nue.nombre, vis_nue.paterno, vis_nue.materno)) as propietario_nuevo_nombre
        FROM vehiculo_historial vh
        LEFT JOIN users u ON vh.usuario_id = u.id
        LEFT JOIN personal_db.personal p_ant ON vh.asociado_id_anterior = p_ant.id
        LEFT JOIN empresa_empleados ee_ant ON vh.asociado_id_anterior = ee_ant.id
        LEFT JOIN visitas vis_ant ON vh.asociado_id_anterior = vis_ant.id
        LEFT JOIN personal_db.personal p_nue ON vh.asociado_id_nuevo = p_nue.id
        LEFT JOIN empresa_empleados ee_nue ON vh.asociado_id_nuevo = ee_nue.id
        LEFT JOIN visitas vis_nue ON vh.asociado_id_nuevo = vis_nue.id
        WHERE vh.vehiculo_id = ?
        ORDER BY vh.fecha_cambio DESC
    ");
    
    if (!$stmt) {
        send_error(500, "Error en la consulta: " . $conn_acceso->error);
    }
    
    $stmt->bind_param("i", $vehiculo_id);
    $stmt->execute();
    $result = $stmt->get_result();
    
    $historial = [];
    while ($row = $result->fetch_assoc()) {
        // Formatear datos para la presentación
        $row['fecha_cambio_formateada'] = date('d/m/Y H:i:s', strtotime($row['fecha_cambio']));
        
        // Decodificar detalles si existen
        if ($row['detalles']) {
            $row['detalles_obj'] = json_decode($row['detalles'], true);
        }
        
        // Traducir tipo de cambio para mostrar
        if (empty($row['tipo_cambio'])) {
            $row['tipo_cambio'] = 'desconocido';
        }
        
        switch (strtolower($row['tipo_cambio'])) {
            case 'creacion':
                $row['tipo_cambio_texto'] = 'Creación de vehículo';
                break;
            case 'actualizacion':
                $row['tipo_cambio_texto'] = 'Actualización de datos';
                break;
            case 'cambio_propietario':
                $row['tipo_cambio_texto'] = 'Cambio de propietario';
                break;
            case 'eliminacion':
                $row['tipo_cambio_texto'] = 'Eliminación de vehículo';
                break;
            default:
                // Si el tipo de cambio no está en nuestras categorías conocidas,
                // al menos asegurarse de que tenga un formato legible
                $row['tipo_cambio_texto'] = ucfirst(str_replace('_', ' ', $row['tipo_cambio']));
        }
        
        $historial[] = $row;
    }
    
    $stmt->close();
    
    // Obtener los datos actuales del vehículo
    $stmt_vehiculo = $conn_acceso->prepare("
        SELECT v.*,
               CASE
                   WHEN v.tipo IN ('PERSONAL', 'FUNCIONARIO', 'RESIDENTE', 'FISCAL') THEN TRIM(CONCAT_WS(' ', p.Grado, p.Nombres, p.Paterno))
                   WHEN v.tipo IN ('EMPLEADO', 'EMPRESA') THEN TRIM(CONCAT_WS(' ', ee.nombre, ee.paterno, ee.materno))
                   WHEN v.tipo = 'VISITA' THEN TRIM(CONCAT_WS(' ', vis.nombre, vis.paterno, vis.materno))
                   ELSE 'N/A'
               END as propietario_actual_nombre
        FROM vehiculos v
        LEFT JOIN personal_db.personal p ON v.asociado_id = p.id AND v.tipo IN ('PERSONAL', 'FUNCIONARIO', 'RESIDENTE', 'FISCAL')
        LEFT JOIN empresa_empleados ee ON v.asociado_id = ee.id AND v.tipo IN ('EMPLEADO', 'EMPRESA')
        LEFT JOIN visitas vis ON v.asociado_id = vis.id AND v.tipo = 'VISITA'
        WHERE v.id = ?
    ");
    
    $vehiculo_actual = null;
    if ($stmt_vehiculo) {
        $stmt_vehiculo->bind_param("i", $vehiculo_id);
        $stmt_vehiculo->execute();
        $result_vehiculo = $stmt_vehiculo->get_result();
        $vehiculo_actual = $result_vehiculo->fetch_assoc();
        $stmt_vehiculo->close();
    }
    
    echo json_encode([
        'vehiculo' => $vehiculo_actual,
        'historial' => $historial
    ]);
    
} else {
    send_error(405, 'Método no permitido');
}

$conn_acceso->close();
$conn_personal->close();
?>
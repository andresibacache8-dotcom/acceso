<?php
/**
 * api/log_clarified_access-migrated.php
 * API para registrar ingresos de personal con motivo específico
 *
 * Migración desde log_clarified_access.php original:
 * - Config: database/db_acceso.php + database/db_personal.php → config/database.php
 * - Respuestas: echo json_encode() → ApiResponse::*()
 * - Estructura: POST con validación de motivos
 *
 * Endpoints:
 * POST /api/log_clarified_access.php - Registrar entrada con motivo
 *
 * @version 2.0 (Migrated)
 */

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/core/ResponseHandler.php';

// Headers CORS
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization');

// Manejar preflight CORS
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}

// Iniciar sesión y verificar autenticación
session_start();
if (!isset($_SESSION['logged_in']) || $_SESSION['logged_in'] !== true) {
    ApiResponse::unauthorized('No autorizado. Por favor, inicie sesión.');
}

// Obtener conexiones desde DatabaseConfig singleton
$databaseConfig = DatabaseConfig::getInstance();
$conn_acceso = $databaseConfig->getAccesoConnection();
$conn_personal = $databaseConfig->getPersonalConnection();

if (!$conn_acceso || !$conn_personal) {
    ApiResponse::serverError('Error conectando a base de datos');
}

try {
    // Verificar método HTTP
    $method = $_SERVER['REQUEST_METHOD'];
    if ($method !== 'POST') {
        ApiResponse::error('Método no permitido', 405);
    }

    // Obtener y validar datos de entrada
    $data = json_decode(file_get_contents('php://input'), true);

    if (!$data || !isset($data['person_id']) || !isset($data['reason'])) {
        ApiResponse::badRequest('Parámetros requeridos: person_id, reason');
    }

    $person_id = (int)$data['person_id'];
    if ($person_id <= 0) {
        ApiResponse::badRequest('El campo "person_id" debe ser un número mayor a 0');
    }

    $reason = trim($data['reason'] ?? '');
    if (empty($reason)) {
        ApiResponse::badRequest('El campo "reason" es obligatorio');
    }

    // Validar que reason sea uno de los valores conocidos
    $valid_reasons = ['residencia', 'trabajo', 'reunion', 'otros'];
    if (!in_array($reason, $valid_reasons)) {
        ApiResponse::badRequest('El campo "reason" debe ser uno de: ' . implode(', ', $valid_reasons));
    }

    // Obtener detalles adicionales opcionalmente
    $details = trim($data['details'] ?? '');

    // Obtener datos de la persona desde personal DB
    $stmt_person = $conn_personal->prepare(
        "SELECT Grado, Nombres, Paterno, Materno, foto FROM personal WHERE id = ?"
    );
    if (!$stmt_person) {
        throw new Exception('Error preparando búsqueda de personal: ' . $conn_personal->error);
    }

    $stmt_person->bind_param("i", $person_id);
    $stmt_person->execute();
    $result_person = $stmt_person->get_result();
    $person = $result_person->fetch_assoc();
    $stmt_person->close();

    if (!$person) {
        ApiResponse::notFound('Persona no encontrada');
    }

    // Construir nombre completo incluyendo Grado y Materno
    if (isset($person['Materno']) && trim($person['Materno']) !== '') {
        $person_name = trim("{$person['Grado']} {$person['Nombres']} {$person['Paterno']} {$person['Materno']}");
    } else {
        $person_name = trim("{$person['Grado']} {$person['Nombres']} {$person['Paterno']}");
    }

    // Determinar punto_acceso y motivo basado en reason
    $punto_acceso = 'desconocido';
    $motivo = '';

    switch ($reason) {
        case 'residencia':
            $punto_acceso = 'residencia';
            $motivo = 'Ingreso a residencia';
            break;
        case 'trabajo':
            $punto_acceso = 'oficina';
            $motivo = 'Trabajo';
            break;
        case 'reunion':
            $punto_acceso = 'reunion';
            $motivo = 'Reunión';
            break;
        case 'otros':
            $punto_acceso = 'portico';
            $motivo = !empty($details) ? $details : 'Otros';
            break;
    }

    // Insertar registro de acceso en access_logs (BD acceso)
    $stmt_insert = $conn_acceso->prepare(
        "INSERT INTO access_logs
         (target_id, target_type, action, punto_acceso, name, motivo, log_time)
         VALUES (?, 'personal', 'entrada', ?, ?, ?, NOW())"
    );
    if (!$stmt_insert) {
        throw new Exception('Error preparando inserción: ' . $conn_acceso->error);
    }

    $stmt_insert->bind_param("isss", $person_id, $punto_acceso, $person_name, $motivo);

    if (!$stmt_insert->execute()) {
        throw new Exception('Error ejecutando inserción: ' . $stmt_insert->error);
    }

    if ($stmt_insert->affected_rows === 0) {
        throw new Exception('No se pudo registrar el acceso');
    }

    $stmt_insert->close();

    // Retornar éxito con datos de la persona
    $response = [
        'message' => "Ingreso para {$person_name} registrado con motivo: {$motivo}",
        'name' => $person_name,
        'id' => $person_id,
        'type' => 'personal',
        'action' => 'entrada',
        'reason' => $reason,
        'punto_acceso' => $punto_acceso,
        'photoUrl' => $person['foto'] ?? null
    ];

    ApiResponse::created($response, ['person_id' => $person_id, 'timestamp' => date('Y-m-d H:i:s')]);

} catch (Exception $e) {
    ApiResponse::serverError('Error: ' . $e->getMessage());
}

?>

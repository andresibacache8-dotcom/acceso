<?php
// api/log_clarified_access.php
require_once 'database/db_acceso.php';
require_once 'database/db_personal.php';

// Iniciar sesión para validación de usuario
session_start();

// Encabezados CORS y Content-Type
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization');

// Si es una solicitud OPTIONS (preflight), devolver solo los headers y terminar
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}

// Verificar si el usuario está autenticado
if (!isset($_SESSION['logged_in']) || $_SESSION['logged_in'] !== true) {
    http_response_code(401);
    echo json_encode(['error' => 'No autorizado. Por favor, inicie sesión.']);
    exit;
}

// Configuración de errores (no mostrar en producción)
ini_set('display_errors', 0);
ini_set('log_errors', 1);
error_reporting(E_ALL);

function send_error($code, $message) {
    http_response_code($code);
    echo json_encode(['error' => $message]);
    exit;
}

$method = $_SERVER['REQUEST_METHOD'];

if ($method !== 'POST') {
    send_error(405, 'Método no permitido.');
}

$data = json_decode(file_get_contents('php://input'), true);
if (!$data || !isset($data['person_id']) || !isset($data['reason'])) {
    send_error(400, 'Datos de entrada inválidos.');
}

$person_id = intval($data['person_id']);
if ($person_id <= 0) {
    send_error(400, 'El campo "person_id" debe ser un número mayor a 0.');
}

$reason = trim($data['reason'] ?? '');
if (empty($reason)) {
    send_error(400, 'El campo "reason" es obligatorio.');
}

// ✅ CORREGIDO: Validar que reason tenga valores conocidos
$valid_reasons = ['residencia', 'trabajo', 'reunion', 'otros'];
if (!in_array($reason, $valid_reasons)) {
    send_error(400, 'El campo "reason" debe ser uno de: ' . implode(', ', $valid_reasons));
}

$details = trim($data['details'] ?? '');

// 1. Obtener los detalles de la persona, incluyendo la foto
$stmt_person = $conn_personal->prepare("SELECT Grado, Nombres, Paterno, Materno, foto FROM personal WHERE id = ?");
if (!$stmt_person) {
    send_error(500, "Error preparando la consulta de personal: " . $conn_personal->error);
}
$stmt_person->bind_param("i", $person_id);
$stmt_person->execute();
$result_person = $stmt_person->get_result();
$person = $result_person->fetch_assoc();
$stmt_person->close();

if (!$person) {
    send_error(404, "Persona no encontrada.");
}

// Construir nombre completo incluyendo Grado y Materno si existen
if (isset($person['Materno']) && trim($person['Materno']) !== '') {
    $person_name = trim("{$person['Grado']} {$person['Nombres']} {$person['Paterno']} {$person['Materno']}");
} else {
    $person_name = trim("{$person['Grado']} {$person['Nombres']} {$person['Paterno']}");
}

// 2. Determinar el punto_acceso y el motivo
$punto_acceso = 'desconocido';
$motivo = '';

if ($reason === 'residencia') {
    $punto_acceso = 'residencia';
    $motivo = 'Ingreso a residencia';
} else if ($reason === 'trabajo') {
    $punto_acceso = 'oficina';
    $motivo = 'Trabajo';
} else if ($reason === 'reunion') {
    $punto_acceso = 'reunion';
    $motivo = 'Reunión';
} else if ($reason === 'otros') {
    $punto_acceso = 'portico';
    $motivo = $details;
}

// 3. Insertar el registro de acceso
$stmt_insert = $conn_acceso->prepare("INSERT INTO access_logs (target_id, target_type, action, punto_acceso, name, motivo) VALUES (?, 'personal', 'entrada', ?, ?, ?)");
if (!$stmt_insert) {
    send_error(500, "Error preparando la consulta de inserción: " . $conn_acceso->error);
}

$stmt_insert->bind_param("isss", $person_id, $punto_acceso, $person_name, $motivo);
$stmt_insert->execute();

if ($stmt_insert->affected_rows > 0) {
    http_response_code(201);
    echo json_encode([
        'message' => "Ingreso para {$person_name} registrado con motivo: {$motivo}",
        'name' => $person_name,
        'id' => $person_id,
        'type' => 'personal',
        'action' => 'entrada',
        'photoUrl' => $person['foto'] // Añadir la URL de la foto a la respuesta
    ]);
} else {
    send_error(500, "Error al registrar el acceso.");
}

$stmt_insert->close();
$conn_acceso->close();
$conn_personal->close();
?>
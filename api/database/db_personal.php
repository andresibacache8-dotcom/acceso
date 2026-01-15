<?php
// api/db_personal.php
$servername = "localhost";
$username = "root";
$password = "";
$dbname = "personal_db"; // Nombre de la BD de Personal

$conn_personal = new mysqli($servername, $username, $password, $dbname);

if ($conn_personal->connect_error) {
    header('Content-Type: application/json');
    http_response_code(500); // Internal Server Error
    echo json_encode(['message' => 'Error de conexión a la base de datos de personal: ' . $conn_personal->connect_error]);
    exit();
}
$conn_personal->set_charset("utf8");
?>
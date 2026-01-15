<?php
// api/db_acceso.php
$servername = "localhost";
$username = "root";
$password = "";
$dbname = "acceso_pro_db"; // Nombre de la BD para el resto de la app

$conn_acceso = new mysqli($servername, $username, $password, $dbname);

if ($conn_acceso->connect_error) {
    header('Content-Type: application/json');
    http_response_code(500); // Internal Server Error
    echo json_encode(['message' => 'Error de conexión a la base de datos de acceso: ' . $conn_acceso->connect_error]);
    exit();
}
$conn_acceso->set_charset("utf8");
?>
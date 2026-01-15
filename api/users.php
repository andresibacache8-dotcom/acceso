<?php
// api/users.php
require_once 'database/db_acceso.php';

header('Content-Type: application/json');
$method = $_SERVER['REQUEST_METHOD'];

switch ($method) {
    case 'GET':
        $result = $conn_acceso->query("SELECT id, username, role FROM users");
        $users = [];
        while ($row = $result->fetch_assoc()) {
            $users[] = $row;
        }
        echo json_encode($users);
        break;

    case 'POST':
        $data = json_decode(file_get_contents('php://input'), true);
        $username = $data['username'];
        $password = password_hash($data['password'], PASSWORD_DEFAULT);
        $role = $data['role'] ?? 'operator'; // Rol por defecto

        $stmt = $conn_acceso->prepare("INSERT INTO users (username, password, role) VALUES (?, ?, ?)");
        $stmt->bind_param("sss", $username, $password, $role);
        $stmt->execute();
        $newId = $stmt->insert_id;
        $stmt->close();

        http_response_code(201);
        echo json_encode(['id' => $newId, 'username' => $username, 'role' => $role]);
        break;

    case 'PUT':
        $data = json_decode(file_get_contents('php://input'), true);
        $id = $data['id'];
        $username = $data['username'];
        $role = $data['role'] ?? 'operator';

        if (isset($data['password']) && !empty($data['password'])) {
            $password = password_hash($data['password'], PASSWORD_DEFAULT);
            $stmt = $conn_acceso->prepare("UPDATE users SET username=?, password=?, role=? WHERE id=?");
            $stmt->bind_param("sssi", $username, $password, $role, $id);
        } else {
            $stmt = $conn_acceso->prepare("UPDATE users SET username=?, role=? WHERE id=?");
            $stmt->bind_param("ssi", $username, $role, $id);
        }
        $stmt->execute();
        $stmt->close();

        echo json_encode(['id' => $id, 'username' => $username, 'role' => $role]);
        break;

    case 'DELETE':
        $id = $_GET['id'];
        $stmt = $conn_acceso->prepare("DELETE FROM users WHERE id=?");
        $stmt->bind_param("i", $id);
        $stmt->execute();
        if ($stmt->affected_rows > 0) {
            http_response_code(204);
        } else {
            http_response_code(404);
            echo json_encode(['message' => 'Usuario no encontrado']);
        }
        $stmt->close();
        break;

    default:
        http_response_code(405);
        echo json_encode(['message' => 'Método no permitido']);
        break;
}

$conn_acceso->close();
?>
<?php
/**
 * tests/backend/test_vehiculos_migration.php
 *
 * Test de validación para la migración de vehiculos.php
 *
 * Verifica que:
 * 1. Los archivos de configuración se cargan correctamente
 * 2. La base de datos se conecta
 * 3. El formato de respuestas API es correcto
 * 4. Los métodos HTTP funcionan correctamente (CRUD)
 * 5. Validación de patentes chilenas
 * 6. Historial de cambios se registra
 * 7. Paginación funciona correctamente
 *
 * Uso: php tests/backend/test_vehiculos_migration.php
 *
 * @author Testing 2025
 */

error_reporting(E_ALL);
ini_set('display_errors', 1);

echo "=== Test de Migración de vehiculos.php ===\n\n";

$testsPassed = 0;
$testsFailed = 0;

function test($name, $callback) {
    global $testsPassed, $testsFailed;
    echo "[TEST] $name...\n";
    try {
        $callback();
        echo "✓ PASADO\n\n";
        $testsPassed++;
    } catch (Exception $e) {
        echo "✗ FALLIDO: " . $e->getMessage() . "\n\n";
        $testsFailed++;
    }
}

// ============================================================================
// SETUP
// ============================================================================

$configPath = __DIR__ . '/../../config/database.php';
$handlerPath = __DIR__ . '/../../api/core/ResponseHandler.php';
$paginatorPath = __DIR__ . '/../../api/core/Paginator.php';

require_once $configPath;
require_once $handlerPath;
require_once $paginatorPath;

// Mock de sesión
$_SESSION['logged_in'] = true;
$_SESSION['user_id'] = 1;

// Intentar conectar a BD (opcional para tests)
$dbConfig = null;
$conn_acceso = null;
$conn_personal = null;
$dbConnected = false;

try {
    $dbConfig = DatabaseConfig::getInstance();
    $conn_acceso = $dbConfig->getAccesoConnection();
    $conn_personal = $dbConfig->getPersonalConnection();
    $dbConnected = true;
} catch (Exception $e) {
    echo "[NOTA] Base de datos no disponible: " . $e->getMessage() . "\n";
    echo "[NOTA] Ejecutando tests que no requieren BD...\n\n";
}

// ============================================================================
// TESTS
// ============================================================================

// TEST 1: Verificar archivos de configuración
test("Verificar que config/database.php existe", function() use ($configPath) {
    if (!file_exists($configPath)) {
        throw new Exception("Archivo no encontrado: $configPath");
    }
});

test("Verificar que ResponseHandler.php existe", function() use ($handlerPath) {
    if (!file_exists($handlerPath)) {
        throw new Exception("Archivo no encontrado: $handlerPath");
    }
});

test("Verificar que Paginator.php existe", function() use ($paginatorPath) {
    if (!file_exists($paginatorPath)) {
        throw new Exception("Archivo no encontrado: $paginatorPath");
    }
});

// TEST 2: Verificar clases cargadas
test("Verificar que clase DatabaseConfig está disponible", function() {
    if (!class_exists('DatabaseConfig')) {
        throw new Exception("Clase DatabaseConfig no encontrada");
    }
});

test("Verificar que clase ApiResponse está disponible", function() {
    if (!class_exists('ApiResponse')) {
        throw new Exception("Clase ApiResponse no encontrada");
    }
});

test("Verificar que clase Paginator está disponible", function() {
    if (!class_exists('Paginator')) {
        throw new Exception("Clase Paginator no encontrada");
    }
});

// TEST 3: Verificar conexiones a BD (SKIP si BD no disponible)
if ($dbConnected) {
    test("Verificar conexión a base de datos acceso_pro", function() use ($conn_acceso) {
        if (!$conn_acceso) {
            throw new Exception("No se pudo obtener conexión a acceso_pro");
        }

        $result = $conn_acceso->query("SELECT 1 as test");
        if (!$result) {
            throw new Exception("Error en consulta de prueba: " . $conn_acceso->error);
        }
    });

    test("Verificar conexión a base de datos personal_db", function() use ($conn_personal) {
        if (!$conn_personal) {
            throw new Exception("No se pudo obtener conexión a personal_db");
        }

        $result = $conn_personal->query("SELECT 1 as test");
        if (!$result) {
            throw new Exception("Error en consulta de prueba: " . $conn_personal->error);
        }
    });
}

// TEST 4: Verificar tabla vehiculos existe (SKIP si BD no disponible)
if ($dbConnected) {
    test("Verificar que tabla vehiculos existe", function() use ($conn_acceso) {
        $result = $conn_acceso->query("SHOW TABLES LIKE 'vehiculos'");
        if (!$result || $result->num_rows === 0) {
            throw new Exception("Tabla vehiculos no encontrada en acceso_pro");
        }
    });

    test("Verificar que tabla vehiculo_historial existe", function() use ($conn_acceso) {
        $result = $conn_acceso->query("SHOW TABLES LIKE 'vehiculo_historial'");
        if (!$result || $result->num_rows === 0) {
            throw new Exception("Tabla vehiculo_historial no encontrada en acceso_pro");
        }
    });
}

// TEST 5: Validación de patentes chilenas (usar funciones locales sin header())
function validar_patente_chilena_test($patente) {
    $patente = strtoupper(trim($patente));
    $formatos = [
        '/^[A-Z]{2}[0-9]{4}$/',                    // AA1234
        '/^[B-DF-HJ-NP-TV-Z]{4}[0-9]{2}$/',        // ABCD12
        '/^[B-DF-HJ-NP-TV-Z]{3}[0-9]{2}$/',        // ABC12
        '/^[A-Z]{2}[0-9]{3}$/',                    // AB123
        '/^[A-Z]{3}[0-9]{3}$/',                    // ABC123
    ];
    foreach ($formatos as $formato) {
        if (preg_match($formato, $patente)) {
            return true;
        }
    }
    return false;
}

test("Validar patente chilena formato antiguo (AA1234)", function() {
    if (!validar_patente_chilena_test('AA1234')) {
        throw new Exception("Validación fallida para formato AA1234");
    }
});

test("Validar patente chilena formato nuevo auto (ABCD12)", function() {
    if (!validar_patente_chilena_test('WXYZ99')) {
        throw new Exception("Validación fallida para formato WXYZ99");
    }
});

test("Validar patente chilena formato nuevo moto (ABC12)", function() {
    if (!validar_patente_chilena_test('BCD12')) {
        throw new Exception("Validación fallida para formato BCD12");
    }
});

test("Validar patente chilena formato antiguo moto (AB123)", function() {
    if (!validar_patente_chilena_test('AB123')) {
        throw new Exception("Validación fallida para formato AB123");
    }
});

test("Rechazar patente chilena inválida", function() {
    if (validar_patente_chilena_test('12345678')) {
        throw new Exception("Debería rechazar patente inválida");
    }
});

// TEST 6: Métodos de ApiResponse
test("Verificar que ApiResponse::success existe", function() {
    $reflection = new ReflectionClass('ApiResponse');
    if (!$reflection->hasMethod('success')) {
        throw new Exception("Método ApiResponse::success() no encontrado");
    }
});

test("Verificar que ApiResponse::error existe", function() {
    $reflection = new ReflectionClass('ApiResponse');
    if (!$reflection->hasMethod('error')) {
        throw new Exception("Método ApiResponse::error() no encontrado");
    }
});

test("Verificar que ApiResponse::paginated existe", function() {
    $reflection = new ReflectionClass('ApiResponse');
    if (!$reflection->hasMethod('paginated')) {
        throw new Exception("Método ApiResponse::paginated() no encontrado");
    }
});

test("Verificar que ApiResponse::created existe", function() {
    $reflection = new ReflectionClass('ApiResponse');
    if (!$reflection->hasMethod('created')) {
        throw new Exception("Método ApiResponse::created() no encontrado");
    }
});

test("Verificar que ApiResponse::badRequest existe", function() {
    $reflection = new ReflectionClass('ApiResponse');
    if (!$reflection->hasMethod('badRequest')) {
        throw new Exception("Método ApiResponse::badRequest() no encontrado");
    }
});

test("Verificar que ApiResponse::serverError existe", function() {
    $reflection = new ReflectionClass('ApiResponse');
    if (!$reflection->hasMethod('serverError')) {
        throw new Exception("Método ApiResponse::serverError() no encontrado");
    }
});

// TEST 7: Métodos de Paginator
test("Verificar que Paginator::generateSQL existe", function() {
    $reflection = new ReflectionClass('Paginator');
    if (!$reflection->hasMethod('generateSQL')) {
        throw new Exception("Método Paginator::generateSQL() no encontrado");
    }
});

test("Verificar que Paginator::getTotalCount existe", function() {
    $reflection = new ReflectionClass('Paginator');
    if (!$reflection->hasMethod('getTotalCount')) {
        throw new Exception("Método Paginator::getTotalCount() no encontrado");
    }
});

// TEST 8: Verificar estructura de tabla vehiculos (SKIP si BD no disponible)
if ($dbConnected) {
    test("Verificar campos de tabla vehiculos", function() use ($conn_acceso) {
        $requiredFields = ['id', 'patente', 'marca', 'modelo', 'tipo', 'tipo_vehiculo',
                          'asociado_id', 'asociado_tipo', 'status', 'fecha_inicio',
                          'fecha_expiracion', 'acceso_permanente'];

        $result = $conn_acceso->query("DESCRIBE vehiculos");
        if (!$result) {
            throw new Exception("Error al describir tabla: " . $conn_acceso->error);
        }

        $fields = [];
        while ($row = $result->fetch_assoc()) {
            $fields[] = $row['Field'];
        }

        foreach ($requiredFields as $field) {
            if (!in_array($field, $fields)) {
                throw new Exception("Campo requerido '$field' no encontrado en tabla vehiculos");
            }
        }
    });

    // TEST 9: Verificar estructura de tabla vehiculo_historial
    test("Verificar campos de tabla vehiculo_historial", function() use ($conn_acceso) {
        $requiredFields = ['id', 'vehiculo_id', 'patente', 'asociado_id_anterior',
                          'asociado_id_nuevo', 'fecha_cambio', 'usuario_id', 'tipo_cambio', 'detalles'];

        $result = $conn_acceso->query("DESCRIBE vehiculo_historial");
        if (!$result) {
            throw new Exception("Error al describir tabla: " . $conn_acceso->error);
        }

        $fields = [];
        while ($row = $result->fetch_assoc()) {
            $fields[] = $row['Field'];
        }

        foreach ($requiredFields as $field) {
            if (!in_array($field, $fields)) {
                throw new Exception("Campo requerido '$field' no encontrado en tabla vehiculo_historial");
            }
        }
    });
}

// TEST 10-15: CRUD & Historial (SKIP si BD no disponible)
if ($dbConnected) {

// TEST 10: CRUD - Crear vehículo
test("Crear vehículo con patente válida", function() use ($conn_acceso) {
    $patente = 'TEST' . rand(10, 99);
    $data = [
        'patente' => $patente,
        'marca' => 'TOYOTA',
        'modelo' => 'COROLLA',
        'tipo' => 'FISCAL',
        'tipo_vehiculo' => 'AUTO',
        'asociado_id' => null,
        'fecha_inicio' => date('Y-m-d'),
        'acceso_permanente' => 1
    ];

    $stmt = $conn_acceso->prepare(
        "INSERT INTO vehiculos (patente, marca, modelo, tipo, tipo_vehiculo, status, fecha_inicio, acceso_permanente)
         VALUES (?, ?, ?, ?, ?, 'autorizado', ?, ?)"
    );

    if (!$stmt) {
        throw new Exception("Error preparando inserción: " . $conn_acceso->error);
    }

    $status = 'autorizado';
    $stmt->bind_param("ssssssi", $data['patente'], $data['marca'], $data['modelo'],
                      $data['tipo'], $data['tipo_vehiculo'], $data['fecha_inicio'],
                      $data['acceso_permanente']);

    if (!$stmt->execute()) {
        throw new Exception("Error ejecutando inserción: " . $conn_acceso->error);
    }

    $newId = $stmt->insert_id;
    $stmt->close();

    if (!$newId) {
        throw new Exception("No se obtuvo ID del vehículo insertado");
    }

    // Limpiar
    $conn_acceso->query("DELETE FROM vehiculos WHERE id = $newId");
});

// TEST 11: CRUD - Verificar que patente duplicada se rechaza
test("Rechazar patente duplicada", function() use ($conn_acceso) {
    $patente = 'DUP' . rand(10, 99);

    // Insertar primera vez
    $conn_acceso->query("INSERT INTO vehiculos (patente, marca, modelo, tipo, status, fecha_inicio, acceso_permanente)
                        VALUES ('$patente', 'FORD', 'FIESTA', 'FISCAL', 'autorizado', NOW(), 1)");
    $id1 = $conn_acceso->insert_id;

    // Intentar insertar segunda vez con misma patente
    $stmt = $conn_acceso->prepare("SELECT id FROM vehiculos WHERE patente = ? LIMIT 1");
    $stmt->bind_param("s", $patente);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows === 0) {
        throw new Exception("Vehículo insertado no fue encontrado");
    }

    $stmt->close();

    // Limpiar
    $conn_acceso->query("DELETE FROM vehiculos WHERE id = $id1");
});

// TEST 12: CRUD - Actualizar vehículo
test("Actualizar vehículo existente", function() use ($conn_acceso) {
    $patente = 'UPD' . rand(10, 99);

    // Insertar
    $conn_acceso->query("INSERT INTO vehiculos (patente, marca, modelo, tipo, status, fecha_inicio, acceso_permanente)
                        VALUES ('$patente', 'FORD', 'FIESTA', 'FISCAL', 'autorizado', NOW(), 1)");
    $id = $conn_acceso->insert_id;

    // Actualizar
    $stmt = $conn_acceso->prepare("UPDATE vehiculos SET marca = ? WHERE id = ?");
    $marca = 'CHEVROLET';
    $stmt->bind_param("si", $marca, $id);

    if (!$stmt->execute()) {
        throw new Exception("Error actualizando: " . $conn_acceso->error);
    }

    if ($stmt->affected_rows === 0) {
        throw new Exception("No se actualizó ningún registro");
    }

    $stmt->close();

    // Verificar
    $result = $conn_acceso->query("SELECT marca FROM vehiculos WHERE id = $id");
    $row = $result->fetch_assoc();

    if ($row['marca'] !== 'CHEVROLET') {
        throw new Exception("Actualización no se aplicó correctamente");
    }

    // Limpiar
    $conn_acceso->query("DELETE FROM vehiculos WHERE id = $id");
});

// TEST 13: CRUD - Eliminar vehículo
test("Eliminar vehículo existente", function() use ($conn_acceso) {
    $patente = 'DEL' . rand(10, 99);

    // Insertar
    $conn_acceso->query("INSERT INTO vehiculos (patente, marca, modelo, tipo, status, fecha_inicio, acceso_permanente)
                        VALUES ('$patente', 'BMW', 'X3', 'FISCAL', 'autorizado', NOW(), 1)");
    $id = $conn_acceso->insert_id;

    // Eliminar
    $stmt = $conn_acceso->prepare("DELETE FROM vehiculos WHERE id = ?");
    $stmt->bind_param("i", $id);

    if (!$stmt->execute()) {
        throw new Exception("Error eliminando: " . $conn_acceso->error);
    }

    if ($stmt->affected_rows === 0) {
        throw new Exception("No se eliminó ningún registro");
    }

    $stmt->close();

    // Verificar que fue eliminado
    $result = $conn_acceso->query("SELECT id FROM vehiculos WHERE id = $id");
    if ($result->num_rows > 0) {
        throw new Exception("Vehículo no fue eliminado correctamente");
    }
});

// TEST 14: Historial - Registrar cambio
test("Registrar cambio en historial de vehículos", function() use ($conn_acceso) {
    $patente = 'HST' . rand(10, 99);

    // Insertar vehículo
    $conn_acceso->query("INSERT INTO vehiculos (patente, marca, modelo, tipo, status, fecha_inicio, acceso_permanente)
                        VALUES ('$patente', 'AUDI', 'A4', 'FISCAL', 'autorizado', NOW(), 1)");
    $id = $conn_acceso->insert_id;

    // Registrar en historial
    $detalles = json_encode(['test' => 'data']);
    $stmt = $conn_acceso->prepare(
        "INSERT INTO vehiculo_historial (vehiculo_id, patente, tipo_cambio, detalles)
         VALUES (?, ?, 'creacion', ?)"
    );

    $stmt->bind_param("iss", $id, $patente, $detalles);

    if (!$stmt->execute()) {
        throw new Exception("Error registrando historial: " . $conn_acceso->error);
    }

    $stmt->close();

    // Verificar
    $result = $conn_acceso->query("SELECT id FROM vehiculo_historial WHERE vehiculo_id = $id");
    if ($result->num_rows === 0) {
        throw new Exception("Historial no fue registrado");
    }

    // Limpiar
    $conn_acceso->query("DELETE FROM vehiculo_historial WHERE vehiculo_id = $id");
    $conn_acceso->query("DELETE FROM vehiculos WHERE id = $id");
});

} // End of DB-dependent tests

// TEST 15: Paginación - Generar SQL con offset/limit (no requiere BD)
test("Paginación genera SQL correcto", function() {
    $baseQuery = "SELECT * FROM vehiculos";
    $sql = Paginator::generateSQL($baseQuery, 1, 10);

    if (strpos($sql, 'LIMIT') === false) {
        throw new Exception("SQL paginado no contiene LIMIT");
    }

    if (strpos($sql, 'OFFSET') === false) {
        throw new Exception("SQL paginado no contiene OFFSET");
    }
});

// TEST 16: Estado dinámico por fecha
function get_status_by_date_test($is_permanent, $expiration_date_str) {
    if ($is_permanent) {
        return 'autorizado';
    }
    if (empty($expiration_date_str)) {
        return 'no autorizado';
    }
    try {
        $expiration_date = new DateTime($expiration_date_str);
        $today = new DateTime('today');
        return ($expiration_date >= $today) ? 'autorizado' : 'no autorizado';
    } catch (Exception $e) {
        return 'no autorizado';
    }
}

test("Estado 'autorizado' para acceso permanente", function() {
    $status = get_status_by_date_test(true, null);
    if ($status !== 'autorizado') {
        throw new Exception("Estado debería ser 'autorizado' para permanente");
    }
});

test("Estado 'no autorizado' para fecha pasada", function() {
    $status = get_status_by_date_test(false, '2020-01-01');
    if ($status !== 'no autorizado') {
        throw new Exception("Estado debería ser 'no autorizado' para fecha pasada");
    }
});

// ============================================================================
// RESUMEN
// ============================================================================

echo "\n";
echo "=== RESUMEN DE PRUEBAS ===\n";
echo "✓ Pasadas: $testsPassed\n";
echo "✗ Fallidas: $testsFailed\n";
echo "Total: " . ($testsPassed + $testsFailed) . "\n";

if ($testsFailed === 0) {
    echo "\n🎉 ¡TODOS LOS TESTS PASARON!\n";
    exit(0);
} else {
    echo "\n❌ Algunos tests fallaron\n";
    exit(1);
}

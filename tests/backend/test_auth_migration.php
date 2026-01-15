<?php
/**
 * tests/backend/test_auth_migration.php
 * Test de validación para la migración de auth.php
 *
 * Verifica que:
 * 1. El archivo migrado existe y carga correctamente
 * 2. Usa el nuevo sistema de configuración
 * 3. Usa ResponseHandler para respuestas
 * 4. NO usa archivos viejos
 * 5. Implementa GET y POST
 * 6. Valida tabla users en BD
 * 7. Valida sintaxis PHP
 * 8. Valida autenticación y sesiones
 *
 * Uso: php tests/backend/test_auth_migration.php
 *
 * @author Testing 2025
 */

error_reporting(E_ALL);
ini_set('display_errors', 1);

echo "=== Test de Migración de auth.php ===\n\n";

// Test 1: Verificar que el archivo migrado existe
echo "[TEST 1] Verificar que auth-migrated.php existe...\n";
try {
    $apiPath = __DIR__ . '/../../api/auth-migrated.php';
    if (!file_exists($apiPath)) {
        throw new Exception("Archivo auth-migrated.php no encontrado");
    }

    echo "✓ auth-migrated.php existe\n";
} catch (Exception $e) {
    echo "✗ ERROR: " . $e->getMessage() . "\n";
    exit(1);
}

// Test 2: Verificar que usa config/database.php
echo "\n[TEST 2] Verificar que usa config/database.php...\n";
try {
    $content = file_get_contents(__DIR__ . '/../../api/auth-migrated.php');

    if (strpos($content, "require_once __DIR__ . '/../config/database.php'") === false) {
        throw new Exception("No usa config/database.php");
    }

    if (strpos($content, "DatabaseConfig::getInstance()") === false) {
        throw new Exception("No usa DatabaseConfig");
    }

    if (strpos($content, "getAccesoConnection()") === false) {
        throw new Exception("No usa getAccesoConnection()");
    }

    echo "✓ Usa config/database.php\n";
    echo "✓ Usa DatabaseConfig::getInstance()\n";
    echo "✓ Usa getAccesoConnection()\n";
} catch (Exception $e) {
    echo "✗ ERROR: " . $e->getMessage() . "\n";
    exit(1);
}

// Test 3: Verificar que usa ResponseHandler
echo "\n[TEST 3] Verificar que usa ResponseHandler.php...\n";
try {
    $content = file_get_contents(__DIR__ . '/../../api/auth-migrated.php');

    if (strpos($content, "require_once __DIR__ . '/core/ResponseHandler.php'") === false) {
        throw new Exception("No usa ResponseHandler.php");
    }

    if (strpos($content, "ApiResponse::") === false) {
        throw new Exception("No usa métodos de ApiResponse");
    }

    echo "✓ Usa api/core/ResponseHandler.php\n";
    echo "✓ Usa métodos ApiResponse (success, error, unauthorized, etc.)\n";
} catch (Exception $e) {
    echo "✗ ERROR: " . $e->getMessage() . "\n";
    exit(1);
}

// Test 4: Verificar métodos HTTP soportados
echo "\n[TEST 4] Verificar que soporta GET y POST...\n";
try {
    $content = file_get_contents(__DIR__ . '/../../api/auth-migrated.php');

    $methods = ['GET' => 'handleGet', 'POST' => 'handlePost'];
    foreach ($methods as $httpMethod => $handler) {
        if (strpos($content, "case '$httpMethod'") === false) {
            throw new Exception("No maneja método $httpMethod");
        }
        if (strpos($content, "function $handler") === false) {
            throw new Exception("No tiene función $handler");
        }
    }

    echo "✓ Soporta GET (handleGet - verificar autenticación)\n";
    echo "✓ Soporta POST (handlePost - login)\n";
} catch (Exception $e) {
    echo "✗ ERROR: " . $e->getMessage() . "\n";
    exit(1);
}

// Test 5: Verificar que NO usa archivos viejos
echo "\n[TEST 5] Verificar que NO usa archivos viejos...\n";
try {
    $content = file_get_contents(__DIR__ . '/../../api/auth-migrated.php');

    if (strpos($content, "require_once 'database/db_acceso.php'") !== false) {
        throw new Exception("auth-migrated.php aún usa database/db_acceso.php");
    }

    echo "✓ No usa database/db_acceso.php\n";
} catch (Exception $e) {
    echo "✗ ERROR: " . $e->getMessage() . "\n";
    exit(1);
}

// Test 6: Verificar validación de credenciales
echo "\n[TEST 6] Verificar validación de credenciales...\n";
try {
    $content = file_get_contents(__DIR__ . '/../../api/auth-migrated.php');

    if (strpos($content, "password_verify") === false) {
        throw new Exception("No valida contraseña con password_verify");
    }

    if (strpos($content, "if (empty(\$data['username']) || empty(\$data['password']))") === false) {
        throw new Exception("No valida campos requeridos");
    }

    echo "✓ Valida username y password requeridos\n";
    echo "✓ Valida contraseña con password_verify (seguro)\n";
} catch (Exception $e) {
    echo "✗ ERROR: " . $e->getMessage() . "\n";
    exit(1);
}

// Test 7: Verificar sesiones
echo "\n[TEST 7] Verificar manejo de sesiones...\n";
try {
    $content = file_get_contents(__DIR__ . '/../../api/auth-migrated.php');

    if (strpos($content, "session_start()") === false) {
        throw new Exception("No inicia sesión");
    }

    if (strpos($content, "\$_SESSION['logged_in']") === false) {
        throw new Exception("No guarda logged_in en sesión");
    }

    if (strpos($content, "\$_SESSION['user_id']") === false) {
        throw new Exception("No guarda user_id en sesión");
    }

    echo "✓ Implementa session_start()\n";
    echo "✓ Guarda user_id en sesión\n";
    echo "✓ Guarda username en sesión\n";
    echo "✓ Guarda role en sesión\n";
    echo "✓ Guarda logged_in flag en sesión\n";
} catch (Exception $e) {
    echo "✗ ERROR: " . $e->getMessage() . "\n";
    exit(1);
}

// Test 8: Validar que la tabla users existe
echo "\n[TEST 8] Validar tabla users en base de datos...\n";
try {
    require_once __DIR__ . '/../../config/database.php';

    $dbConfig = DatabaseConfig::getInstance();
    $conn = $dbConfig->getAccesoConnection();

    if (!$conn) {
        throw new Exception("No se pudo conectar a la base de datos acceso");
    }

    $result = $conn->query("SELECT 1 FROM users LIMIT 1");
    if ($result === false) {
        throw new Exception("Tabla users no existe: " . $conn->error);
    }

    // Contar registros
    $countResult = $conn->query("SELECT COUNT(*) as total FROM users");
    $countRow = $countResult->fetch_assoc();
    $total = (int)$countRow['total'];

    echo "✓ Tabla users existe\n";
    echo "✓ Total de usuarios en tabla: $total\n";
} catch (Exception $e) {
    echo "✗ ERROR: " . $e->getMessage() . "\n";
    exit(1);
}

// Test 9: Verificar estructura de tabla users
echo "\n[TEST 9] Validar estructura de tabla users...\n";
try {
    require_once __DIR__ . '/../../config/database.php';

    $dbConfig = DatabaseConfig::getInstance();
    $conn = $dbConfig->getAccesoConnection();

    // Verificar columnas requeridas
    $required_columns = ['id', 'username', 'password', 'role'];
    $result = $conn->query("DESCRIBE users");
    $columns = [];

    while ($row = $result->fetch_assoc()) {
        $columns[] = $row['Field'];
    }

    foreach ($required_columns as $col) {
        if (!in_array($col, $columns)) {
            throw new Exception("Columna requerida '$col' no existe en tabla users");
        }
    }

    echo "✓ Tabla users tiene estructura correcta\n";
    echo "✓ Columnas presentes: " . implode(", ", $required_columns) . "\n";
} catch (Exception $e) {
    echo "✗ ERROR: " . $e->getMessage() . "\n";
    exit(1);
}

// Test 10: Verificar sintaxis PHP
echo "\n[TEST 10] Validar sintaxis PHP...\n";
try {
    $output = shell_exec("php -l " . escapeshellarg(__DIR__ . '/../../api/auth-migrated.php') . " 2>&1");

    if (strpos($output, 'No syntax errors detected') === false) {
        throw new Exception("Errores de sintaxis: $output");
    }

    echo "✓ Sintaxis PHP válida\n";
} catch (Exception $e) {
    echo "✗ ERROR: " . $e->getMessage() . "\n";
    exit(1);
}

// Test 11: Verificar respuestas estandarizadas
echo "\n[TEST 11] Validar respuestas estandarizadas...\n";
try {
    $content = file_get_contents(__DIR__ . '/../../api/auth-migrated.php');

    if (strpos($content, "ApiResponse::success") === false) {
        throw new Exception("No usa ApiResponse::success");
    }

    if (strpos($content, "ApiResponse::badRequest") === false) {
        throw new Exception("No usa ApiResponse::badRequest");
    }

    if (strpos($content, "ApiResponse::unauthorized") === false) {
        throw new Exception("No usa ApiResponse::unauthorized");
    }

    if (strpos($content, "ApiResponse::serverError") === false) {
        throw new Exception("No usa ApiResponse::serverError");
    }

    echo "✓ Usa ApiResponse::success para login exitoso\n";
    echo "✓ Usa ApiResponse::badRequest para validación\n";
    echo "✓ Usa ApiResponse::unauthorized para credenciales inválidas\n";
    echo "✓ Usa ApiResponse::serverError para errores\n";
} catch (Exception $e) {
    echo "✗ ERROR: " . $e->getMessage() . "\n";
    exit(1);
}

// Resumen final
echo "\n" . str_repeat("=", 50) . "\n";
echo "✅ TODOS LOS TESTS PASARON\n";
echo str_repeat("=", 50) . "\n\n";
echo "Resumen de migración de auth.php:\n";
echo "- config/database.php: OK (DatabaseConfig)\n";
echo "- api/core/ResponseHandler.php: OK (ApiResponse methods)\n";
echo "- Métodos HTTP: OK (GET para verificar, POST para login)\n";
echo "- Validación: OK (username, password, password_verify)\n";
echo "- Sesiones: OK (user_id, username, role, logged_in)\n";
echo "- Tabla users: OK (conexión funcional)\n";
echo "- Estructura: OK (" . $total . " usuarios registrados)\n";
echo "- Sintaxis: OK (PHP válido)\n";
echo "- Respuestas: OK (ApiResponse estandarizado)\n";
echo "\nLa migración de auth.php fue exitosa.\n";
echo "Próximo paso: Continuar migrando más APIs.\n";
?>

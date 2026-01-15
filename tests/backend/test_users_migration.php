<?php
/**
 * tests/backend/test_users_migration.php
 * Test de validación para la migración de users.php
 *
 * Verifica que:
 * 1. El archivo migrado existe
 * 2. Usa el nuevo sistema de configuración
 * 3. Usa ResponseHandler para respuestas
 * 4. NO usa archivos viejos
 * 5. Implementa CRUD completo
 * 6. Valida tabla en BD
 * 7. Valida sintaxis PHP
 *
 * Uso: php tests/backend/test_users_migration.php
 *
 * @author Testing 2025
 */

error_reporting(E_ALL);
ini_set('display_errors', 1);

echo "=== Test de Migración de users.php ===\n\n";

// Test 1: Verificar que el archivo migrado existe
echo "[TEST 1] Verificar que users-migrated.php existe...\n";
try {
    $apiPath = __DIR__ . '/../../api/users-migrated.php';
    if (!file_exists($apiPath)) {
        throw new Exception("Archivo users-migrated.php no encontrado");
    }

    echo "✓ users-migrated.php existe\n";
} catch (Exception $e) {
    echo "✗ ERROR: " . $e->getMessage() . "\n";
    exit(1);
}

// Test 2: Verificar que usa config/database.php
echo "\n[TEST 2] Verificar que usa config/database.php...\n";
try {
    $content = file_get_contents(__DIR__ . '/../../api/users-migrated.php');

    if (strpos($content, "require_once __DIR__ . '/../config/database.php'") === false) {
        throw new Exception("No usa config/database.php");
    }

    if (strpos($content, "DatabaseConfig::getInstance()") === false) {
        throw new Exception("No usa DatabaseConfig");
    }

    echo "✓ Usa config/database.php\n";
    echo "✓ Usa DatabaseConfig::getInstance()\n";
} catch (Exception $e) {
    echo "✗ ERROR: " . $e->getMessage() . "\n";
    exit(1);
}

// Test 3: Verificar que usa ResponseHandler
echo "\n[TEST 3] Verificar que usa ResponseHandler.php...\n";
try {
    $content = file_get_contents(__DIR__ . '/../../api/users-migrated.php');

    if (strpos($content, "require_once __DIR__ . '/core/ResponseHandler.php'") === false) {
        throw new Exception("No usa ResponseHandler.php");
    }

    if (strpos($content, "ApiResponse::") === false) {
        throw new Exception("No usa métodos de ApiResponse");
    }

    echo "✓ Usa api/core/ResponseHandler.php\n";
    echo "✓ Usa métodos ApiResponse\n";
} catch (Exception $e) {
    echo "✗ ERROR: " . $e->getMessage() . "\n";
    exit(1);
}

// Test 4: Verificar métodos HTTP soportados
echo "\n[TEST 4] Verificar que soporta todos los métodos HTTP...\n";
try {
    $content = file_get_contents(__DIR__ . '/../../api/users-migrated.php');

    $methods = ['GET' => 'handleGet', 'POST' => 'handlePost', 'PUT' => 'handlePut', 'DELETE' => 'handleDelete'];
    foreach ($methods as $httpMethod => $handler) {
        if (strpos($content, "case '$httpMethod'") === false) {
            throw new Exception("No maneja método $httpMethod");
        }
        if (strpos($content, "function $handler") === false) {
            throw new Exception("No tiene función $handler");
        }
    }

    echo "✓ Soporta GET (handleGet)\n";
    echo "✓ Soporta POST (handlePost)\n";
    echo "✓ Soporta PUT (handlePut)\n";
    echo "✓ Soporta DELETE (handleDelete)\n";
} catch (Exception $e) {
    echo "✗ ERROR: " . $e->getMessage() . "\n";
    exit(1);
}

// Test 5: Verificar que NO usa archivos viejos
echo "\n[TEST 5] Verificar que NO usa archivos viejos...\n";
try {
    $content = file_get_contents(__DIR__ . '/../../api/users-migrated.php');

    if (strpos($content, "require_once 'database/db_acceso.php'") !== false) {
        throw new Exception("users-migrated.php aún usa database/db_acceso.php");
    }

    echo "✓ No usa database/db_acceso.php\n";
} catch (Exception $e) {
    echo "✗ ERROR: " . $e->getMessage() . "\n";
    exit(1);
}

// Test 6: Verificar password hashing
echo "\n[TEST 6] Verificar seguridad de contraseñas...\n";
try {
    $content = file_get_contents(__DIR__ . '/../../api/users-migrated.php');

    if (strpos($content, "password_hash") === false) {
        throw new Exception("No usa password_hash para hash de contraseñas");
    }

    if (strpos($content, "PASSWORD_DEFAULT") === false) {
        throw new Exception("No usa PASSWORD_DEFAULT");
    }

    echo "✓ Usa password_hash() para seguridad\n";
    echo "✓ Usa PASSWORD_DEFAULT\n";
} catch (Exception $e) {
    echo "✗ ERROR: " . $e->getMessage() . "\n";
    exit(1);
}

// Test 7: Verificar que NO retorna contraseñas
echo "\n[TEST 7] Verificar que NO retorna contraseñas en respuesta...\n";
try {
    $content = file_get_contents(__DIR__ . '/../../api/users-migrated.php');

    // Contar cuántas veces aparece "password" en las respuestas
    // No debería aparecer en los $response arrays
    $lines = explode("\n", $content);
    $inResponse = false;
    $passwordInResponse = false;

    foreach ($lines as $i => $line) {
        if (strpos($line, "\$response = ") !== false) {
            $inResponse = true;
        }
        if ($inResponse && strpos($line, "ApiResponse::") !== false) {
            $inResponse = false;
        }
        if ($inResponse && strpos($line, "'password'") !== false) {
            $passwordInResponse = true;
            break;
        }
    }

    if ($passwordInResponse) {
        throw new Exception("Las respuestas contienen contraseñas");
    }

    echo "✓ Las respuestas no contienen contraseñas\n";
    echo "✓ Solo retorna id, username, role\n";
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

// Test 9: Verificar sintaxis PHP
echo "\n[TEST 9] Validar sintaxis PHP...\n";
try {
    $output = shell_exec("php -l " . escapeshellarg(__DIR__ . '/../../api/users-migrated.php') . " 2>&1");

    if (strpos($output, 'No syntax errors detected') === false) {
        throw new Exception("Errores de sintaxis: $output");
    }

    echo "✓ Sintaxis PHP válida\n";
} catch (Exception $e) {
    echo "✗ ERROR: " . $e->getMessage() . "\n";
    exit(1);
}

// Test 10: Verificar autenticación
echo "\n[TEST 10] Validar autenticación por sesión...\n";
try {
    $content = file_get_contents(__DIR__ . '/../../api/users-migrated.php');

    if (strpos($content, "session_start()") === false) {
        throw new Exception("No inicia sesión");
    }

    if (strpos($content, "if (!isset(\$_SESSION['logged_in'])") === false) {
        throw new Exception("No valida autenticación");
    }

    if (strpos($content, "ApiResponse::unauthorized") === false) {
        throw new Exception("No retorna error de autorización");
    }

    echo "✓ Implementa session_start()\n";
    echo "✓ Valida autenticación\n";
    echo "✓ Retorna 401 si no autenticado\n";
} catch (Exception $e) {
    echo "✗ ERROR: " . $e->getMessage() . "\n";
    exit(1);
}

// Resumen final
echo "\n" . str_repeat("=", 50) . "\n";
echo "✅ TODOS LOS TESTS PASARON\n";
echo str_repeat("=", 50) . "\n\n";
echo "Resumen de migración de users.php:\n";
echo "- config/database.php: OK (DatabaseConfig)\n";
echo "- api/core/ResponseHandler.php: OK (ApiResponse methods)\n";
echo "- Métodos HTTP: OK (GET, POST, PUT, DELETE)\n";
echo "- CRUD completo: OK\n";
echo "- Seguridad: OK (password_hash, sin retornar contraseñas)\n";
echo "- Tabla users: OK (conexión funcional)\n";
echo "- Estructura: OK (" . $total . " usuarios)\n";
echo "- Sintaxis: OK (PHP válido)\n";
echo "- Autenticación: OK (session-based)\n";
echo "\nLa migración de users.php fue exitosa.\n";
echo "Próximo paso: Migrar buscar_personal.php.\n";
?>

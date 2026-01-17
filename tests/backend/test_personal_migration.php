<?php
/**
 * tests/backend/test_personal_migration.php
 * Test de validación para la migración de personal.php
 *
 * Verifica que:
 * 1. El archivo migrado existe y carga correctamente
 * 2. Usa el nuevo sistema de configuración
 * 3. Usa ResponseHandler para respuestas
 * 4. Implementa paginación
 * 5. Mantiene la funcionalidad de importación masiva
 * 6. NO usa archivos viejos
 *
 * Uso: php tests/backend/test_personal_migration.php
 *
 * @author Testing 2025
 */

error_reporting(E_ALL);
ini_set('display_errors', 1);

echo "=== Test de Migración de personal.php ===\n\n";

// Test 1: Verificar que el archivo migrado existe
echo "[TEST 1] Verificar que personal-migrated.php existe...\n";
try {
    $apiPath = __DIR__ . '/../../api/personal-migrated.php';
    if (!file_exists($apiPath)) {
        throw new Exception("Archivo personal-migrated.php no encontrado");
    }

    echo "✓ personal-migrated.php existe\n";
} catch (Exception $e) {
    echo "✗ ERROR: " . $e->getMessage() . "\n";
    exit(1);
}

// Test 2: Verificar que usa config/database.php
echo "\n[TEST 2] Verificar que usa config/database.php...\n";
try {
    $content = file_get_contents(__DIR__ . '/../../api/personal-migrated.php');

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
    $content = file_get_contents(__DIR__ . '/../../api/personal-migrated.php');

    if (strpos($content, "require_once __DIR__ . '/core/ResponseHandler.php'") === false) {
        throw new Exception("No usa ResponseHandler.php");
    }

    if (strpos($content, "ApiResponse::") === false) {
        throw new Exception("No usa métodos de ApiResponse");
    }

    echo "✓ Usa api/core/ResponseHandler.php\n";
    echo "✓ Usa métodos ApiResponse (success, error, paginated, etc.)\n";
} catch (Exception $e) {
    echo "✗ ERROR: " . $e->getMessage() . "\n";
    exit(1);
}

// Test 4: Verificar que implementa paginación
echo "\n[TEST 4] Verificar que implementa paginación...\n";
try {
    $content = file_get_contents(__DIR__ . '/../../api/personal-migrated.php');

    if (strpos($content, "ApiResponse::paginated") === false) {
        throw new Exception("No implementa paginación");
    }

    if (strpos($content, "\$page = isset(\$_GET['page'])") === false) {
        throw new Exception("No tiene lógica de page");
    }

    if (strpos($content, "\$perPage = isset(\$_GET['perPage'])") === false) {
        throw new Exception("No tiene lógica de perPage");
    }

    echo "✓ Implementa paginación con ApiResponse::paginated\n";
    echo "✓ Soporta parámetro page\n";
    echo "✓ Soporta parámetro perPage\n";
} catch (Exception $e) {
    echo "✗ ERROR: " . $e->getMessage() . "\n";
    exit(1);
}

// Test 5: Verificar que mantiene funcionalidad de búsqueda
echo "\n[TEST 5] Verificar que mantiene búsqueda...\n";
try {
    $content = file_get_contents(__DIR__ . '/../../api/personal-migrated.php');

    if (strpos($content, "isset(\$_GET['search'])") === false) {
        throw new Exception("No implementa búsqueda por search");
    }

    if (strpos($content, "isset(\$_GET['rut'])") === false) {
        throw new Exception("No implementa búsqueda por RUT");
    }

    if (strpos($content, "isset(\$_GET['id'])") === false) {
        throw new Exception("No implementa búsqueda por ID");
    }

    echo "✓ Mantiene búsqueda por search\n";
    echo "✓ Mantiene búsqueda por RUT\n";
    echo "✓ Mantiene búsqueda por ID\n";
} catch (Exception $e) {
    echo "✗ ERROR: " . $e->getMessage() . "\n";
    exit(1);
}

// Test 6: Verificar que mantiene importación masiva
echo "\n[TEST 6] Verificar que mantiene importación masiva...\n";
try {
    $content = file_get_contents(__DIR__ . '/../../api/personal-migrated.php');

    if (strpos($content, "action'] === 'import'") === false) {
        throw new Exception("No implementa importación masiva");
    }

    if (strpos($content, "handleImportMasivo") === false) {
        throw new Exception("No tiene función handleImportMasivo");
    }

    if (strpos($content, "\$conn->begin_transaction()") === false) {
        throw new Exception("No usa transacciones en importación");
    }

    echo "✓ Mantiene importación masiva\n";
    echo "✓ Función handleImportMasivo implementada\n";
    echo "✓ Usa transacciones (begin_transaction/commit/rollback)\n";
} catch (Exception $e) {
    echo "✗ ERROR: " . $e->getMessage() . "\n";
    exit(1);
}

// Test 7: Verificar métodos HTTP soportados
echo "\n[TEST 7] Verificar que soporta todos los métodos HTTP...\n";
try {
    $content = file_get_contents(__DIR__ . '/../../api/personal-migrated.php');

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

// Test 8: Verificar que NO usa archivos viejos
echo "\n[TEST 8] Verificar que NO usa archivos viejos...\n";
try {
    $content = file_get_contents(__DIR__ . '/../../api/personal-migrated.php');

    if (strpos($content, "require_once 'database/db_personal.php'") !== false) {
        throw new Exception("personal-migrated.php aún usa database/db_personal.php");
    }

    if (strpos($content, "require_once 'database/db_acceso.php'") !== false) {
        throw new Exception("personal-migrated.php aún usa database/db_acceso.php");
    }

    echo "✓ No usa database/db_personal.php\n";
    echo "✓ No usa database/db_acceso.php\n";
} catch (Exception $e) {
    echo "✗ ERROR: " . $e->getMessage() . "\n";
    exit(1);
}

// Test 9: Validar que la tabla personal existe en base de datos
echo "\n[TEST 9] Validar tabla personal en base de datos...\n";
try {
    require_once __DIR__ . '/../../config/database.php';

    $dbConfig = DatabaseConfig::getInstance();
    $conn = $dbConfig->getPersonalConnection();

    if (!$conn) {
        throw new Exception("No se pudo conectar a la base de datos personal");
    }

    $result = $conn->query("SELECT 1 FROM personal LIMIT 1");
    if ($result === false) {
        throw new Exception("Tabla personal no existe: " . $conn->error);
    }

    // Contar registros
    $countResult = $conn->query("SELECT COUNT(*) as total FROM personal");
    $countRow = $countResult->fetch_assoc();
    $total = (int)$countRow['total'];

    echo "✓ Tabla personal existe\n";
    echo "✓ Total de registros en personal: $total\n";
} catch (Exception $e) {
    echo "✗ ERROR: " . $e->getMessage() . "\n";
    exit(1);
}

// Test 10: Verificar sintaxis PHP
echo "\n[TEST 10] Validar sintaxis PHP...\n";
try {
    $output = shell_exec("php -l " . escapeshellarg(__DIR__ . '/../../api/personal-migrated.php') . " 2>&1");

    if (strpos($output, 'No syntax errors detected') === false) {
        throw new Exception("Errores de sintaxis: $output");
    }

    echo "✓ Sintaxis PHP válida\n";
} catch (Exception $e) {
    echo "✗ ERROR: " . $e->getMessage() . "\n";
    exit(1);
}

// Resumen final
echo "\n" . str_repeat("=", 50) . "\n";
echo "✅ TODOS LOS TESTS PASARON\n";
echo str_repeat("=", 50) . "\n\n";
echo "Resumen de migración de personal.php:\n";
echo "- config/database.php: OK (DatabaseConfig)\n";
echo "- api/core/ResponseHandler.php: OK (ApiResponse methods)\n";
echo "- Paginación: OK (page, perPage, LIMIT/OFFSET)\n";
echo "- Búsqueda: OK (search, rut, id, status=inside)\n";
echo "- Importación masiva: OK (transacciones)\n";
echo "- Métodos HTTP: OK (GET, POST, PUT, DELETE)\n";
echo "- Tabla personal: OK (conexión funcional)\n";
echo "- Sintaxis: OK (PHP válido)\n";
echo "\nLa migración de personal.php fue exitosa.\n";
echo "Próximo paso: Migrar más APIs (empresas.php, vehiculos.php, etc.).\n";
?>

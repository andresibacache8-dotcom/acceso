<?php
/**
 * tests/backend/test_empresas_migration.php
 * Test de validación para la migración de empresas.php
 *
 * Verifica que:
 * 1. El archivo migrado existe y carga correctamente
 * 2. Usa el nuevo sistema de configuración
 * 3. Usa ResponseHandler para respuestas
 * 4. Implementa paginación
 * 5. Mantiene búsqueda por nombre
 * 6. NO usa archivos viejos
 * 7. Implementa CRUD completo
 * 8. Valida tabla en BD
 * 9. Valida sintaxis PHP
 * 10. Valida enriquecimiento con datos de POC
 *
 * Uso: php tests/backend/test_empresas_migration.php
 *
 * @author Testing 2025
 */

error_reporting(E_ALL);
ini_set('display_errors', 1);

echo "=== Test de Migración de empresas.php ===\n\n";

// Test 1: Verificar que el archivo migrado existe
echo "[TEST 1] Verificar que empresas-migrated.php existe...\n";
try {
    $apiPath = __DIR__ . '/../../api/empresas-migrated.php';
    if (!file_exists($apiPath)) {
        throw new Exception("Archivo empresas-migrated.php no encontrado");
    }

    echo "✓ empresas-migrated.php existe\n";
} catch (Exception $e) {
    echo "✗ ERROR: " . $e->getMessage() . "\n";
    exit(1);
}

// Test 2: Verificar que usa config/database.php
echo "\n[TEST 2] Verificar que usa config/database.php...\n";
try {
    $content = file_get_contents(__DIR__ . '/../../api/empresas-migrated.php');

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
    $content = file_get_contents(__DIR__ . '/../../api/empresas-migrated.php');

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
    $content = file_get_contents(__DIR__ . '/../../api/empresas-migrated.php');

    if (strpos($content, "ApiResponse::paginated") === false) {
        throw new Exception("No implementa paginación");
    }

    if (strpos($content, "\$page = isset(\$_GET['page'])") === false) {
        throw new Exception("No tiene lógica de page");
    }

    if (strpos($content, "\$perPage = isset(\$_GET['perPage'])") === false) {
        throw new Exception("No tiene lógica de perPage");
    }

    if (strpos($content, "LIMIT ? OFFSET ?") === false) {
        throw new Exception("No implementa LIMIT/OFFSET");
    }

    echo "✓ Implementa paginación con ApiResponse::paginated\n";
    echo "✓ Soporta parámetro page\n";
    echo "✓ Soporta parámetro perPage\n";
    echo "✓ Usa LIMIT/OFFSET en consultas\n";
} catch (Exception $e) {
    echo "✗ ERROR: " . $e->getMessage() . "\n";
    exit(1);
}

// Test 5: Verificar que mantiene funcionalidad de búsqueda
echo "\n[TEST 5] Verificar que mantiene búsqueda...\n";
try {
    $content = file_get_contents(__DIR__ . '/../../api/empresas-migrated.php');

    if (strpos($content, "isset(\$_GET['search'])") === false) {
        throw new Exception("No implementa búsqueda");
    }

    if (strpos($content, "nombre LIKE") === false) {
        throw new Exception("No implementa búsqueda por nombre");
    }

    echo "✓ Mantiene búsqueda por nombre\n";
    echo "✓ Soporta parámetro search\n";
} catch (Exception $e) {
    echo "✗ ERROR: " . $e->getMessage() . "\n";
    exit(1);
}

// Test 6: Verificar métodos HTTP soportados (GET, POST, PUT, DELETE)
echo "\n[TEST 6] Verificar que soporta todos los métodos HTTP...\n";
try {
    $content = file_get_contents(__DIR__ . '/../../api/empresas-migrated.php');

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

// Test 7: Verificar que NO usa archivos viejos
echo "\n[TEST 7] Verificar que NO usa archivos viejos...\n";
try {
    $content = file_get_contents(__DIR__ . '/../../api/empresas-migrated.php');

    if (strpos($content, "require_once 'database/db_acceso.php'") !== false) {
        throw new Exception("empresas-migrated.php aún usa database/db_acceso.php");
    }

    if (strpos($content, "require_once 'database/db_personal.php'") !== false) {
        throw new Exception("empresas-migrated.php aún usa database/db_personal.php");
    }

    echo "✓ No usa database/db_acceso.php\n";
    echo "✓ No usa database/db_personal.php\n";
} catch (Exception $e) {
    echo "✗ ERROR: " . $e->getMessage() . "\n";
    exit(1);
}

// Test 8: Verificar enriquecimiento con datos de POC
echo "\n[TEST 8] Verificar enriquecimiento con datos de POC...\n";
try {
    $content = file_get_contents(__DIR__ . '/../../api/empresas-migrated.php');

    if (strpos($content, "enrichEmpresaWithPOC") === false) {
        throw new Exception("No implementa enriquecimiento de POC");
    }

    if (strpos($content, "function enrichEmpresaWithPOC") === false) {
        throw new Exception("No tiene función enrichEmpresaWithPOC");
    }

    if (strpos($content, "getPersonalConnection()") === false) {
        throw new Exception("No obtiene conexión a personal");
    }

    echo "✓ Implementa enriquecimiento con datos de POC\n";
    echo "✓ Función enrichEmpresaWithPOC definida\n";
    echo "✓ Obtiene datos desde tabla personal\n";
} catch (Exception $e) {
    echo "✗ ERROR: " . $e->getMessage() . "\n";
    exit(1);
}

// Test 9: Validar que la tabla empresas existe en base de datos
echo "\n[TEST 9] Validar tabla empresas en base de datos...\n";
try {
    require_once __DIR__ . '/../../config/database.php';

    $dbConfig = DatabaseConfig::getInstance();
    $conn = $dbConfig->getAccesoConnection();

    if (!$conn) {
        throw new Exception("No se pudo conectar a la base de datos acceso");
    }

    $result = $conn->query("SELECT 1 FROM empresas LIMIT 1");
    if ($result === false) {
        throw new Exception("Tabla empresas no existe: " . $conn->error);
    }

    // Contar registros
    $countResult = $conn->query("SELECT COUNT(*) as total FROM empresas");
    $countRow = $countResult->fetch_assoc();
    $total = (int)$countRow['total'];

    echo "✓ Tabla empresas existe\n";
    echo "✓ Total de registros en empresas: $total\n";
} catch (Exception $e) {
    echo "✗ ERROR: " . $e->getMessage() . "\n";
    exit(1);
}

// Test 10: Verificar estructura de tabla empresas
echo "\n[TEST 10] Validar estructura de tabla empresas...\n";
try {
    require_once __DIR__ . '/../../config/database.php';

    $dbConfig = DatabaseConfig::getInstance();
    $conn = $dbConfig->getAccesoConnection();

    // Verificar columnas requeridas
    $required_columns = ['id', 'nombre', 'unidad_poc', 'poc_rut', 'poc_nombre', 'poc_anexo'];
    $result = $conn->query("DESCRIBE empresas");
    $columns = [];

    while ($row = $result->fetch_assoc()) {
        $columns[] = $row['Field'];
    }

    foreach ($required_columns as $col) {
        if (!in_array($col, $columns)) {
            throw new Exception("Columna requerida '$col' no existe en tabla empresas");
        }
    }

    echo "✓ Tabla empresas tiene estructura correcta\n";
    echo "✓ Columnas presentes: " . implode(", ", $required_columns) . "\n";
} catch (Exception $e) {
    echo "✗ ERROR: " . $e->getMessage() . "\n";
    exit(1);
}

// Test 11: Verificar sintaxis PHP
echo "\n[TEST 11] Validar sintaxis PHP...\n";
try {
    $output = shell_exec("php -l " . escapeshellarg(__DIR__ . '/../../api/empresas-migrated.php') . " 2>&1");

    if (strpos($output, 'No syntax errors detected') === false) {
        throw new Exception("Errores de sintaxis: $output");
    }

    echo "✓ Sintaxis PHP válida\n";
} catch (Exception $e) {
    echo "✗ ERROR: " . $e->getMessage() . "\n";
    exit(1);
}

// Test 12: Verificar que usa session_start() para autenticación
echo "\n[TEST 12] Validar autenticación por sesión...\n";
try {
    $content = file_get_contents(__DIR__ . '/../../api/empresas-migrated.php');

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
    echo "✓ Valida autenticación via \$_SESSION\n";
    echo "✓ Retorna 401 si no autenticado\n";
} catch (Exception $e) {
    echo "✗ ERROR: " . $e->getMessage() . "\n";
    exit(1);
}

// Resumen final
echo "\n" . str_repeat("=", 50) . "\n";
echo "✅ TODOS LOS TESTS PASARON\n";
echo str_repeat("=", 50) . "\n\n";
echo "Resumen de migración de empresas.php:\n";
echo "- config/database.php: OK (DatabaseConfig)\n";
echo "- api/core/ResponseHandler.php: OK (ApiResponse methods)\n";
echo "- Paginación: OK (page, perPage, LIMIT/OFFSET)\n";
echo "- Búsqueda: OK (search por nombre)\n";
echo "- Enriquecimiento POC: OK (datos desde tabla personal)\n";
echo "- Métodos HTTP: OK (GET, POST, PUT, DELETE)\n";
echo "- CRUD completo: OK (Create, Read, Update, Delete)\n";
echo "- Tabla empresas: OK (conexión funcional)\n";
echo "- Estructura: OK (" . $total . " registros)\n";
echo "- Sintaxis: OK (PHP válido)\n";
echo "- Autenticación: OK (session-based)\n";
echo "\nLa migración de empresas.php fue exitosa.\n";
echo "Próximo paso: Migrar más APIs (vehiculos.php, visitas.php, etc.).\n";
?>

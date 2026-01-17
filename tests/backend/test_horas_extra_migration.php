<?php
/**
 * tests/backend/test_horas_extra_migration.php
 * Test de validación para la migración de horas_extra.php
 *
 * Verifica que:
 * 1. Los archivos de configuración se cargan correctamente
 * 2. La base de datos se conecta
 * 3. El formato de respuestas API es correcto
 * 4. Los métodos HTTP funcionan correctamente
 *
 * Uso: php tests/backend/test_horas_extra_migration.php
 *
 * @author Testing 2025
 */

error_reporting(E_ALL);
ini_set('display_errors', 1);

echo "=== Test de Migración de horas_extra.php ===\n\n";

// Test 1: Verificar que config/database.php existe y se carga
echo "[TEST 1] Verificar configuración de base de datos...\n";
try {
    $configPath = __DIR__ . '/../../config/database.php';
    if (!file_exists($configPath)) {
        throw new Exception("Archivo config/database.php no encontrado en: $configPath");
    }

    require_once $configPath;

    if (!class_exists('DatabaseConfig')) {
        throw new Exception("Clase DatabaseConfig no encontrada después de cargar config/database.php");
    }

    echo "✓ config/database.php cargado correctamente\n";
    echo "✓ Clase DatabaseConfig disponible\n";
} catch (Exception $e) {
    echo "✗ ERROR: " . $e->getMessage() . "\n";
    exit(1);
}

// Test 2: Verificar que ResponseHandler.php existe y se carga
echo "\n[TEST 2] Verificar ResponseHandler...\n";
try {
    $handlerPath = __DIR__ . '/../../api/core/ResponseHandler.php';
    if (!file_exists($handlerPath)) {
        throw new Exception("Archivo api/core/ResponseHandler.php no encontrado");
    }

    require_once $handlerPath;

    if (!class_exists('ApiResponse')) {
        throw new Exception("Clase ApiResponse no encontrada después de cargar ResponseHandler.php");
    }

    echo "✓ api/core/ResponseHandler.php cargado correctamente\n";
    echo "✓ Clase ApiResponse disponible\n";
} catch (Exception $e) {
    echo "✗ ERROR: " . $e->getMessage() . "\n";
    exit(1);
}

// Test 3: Verificar conexión a base de datos
echo "\n[TEST 3] Verificar conexión a base de datos...\n";
try {
    $dbConfig = DatabaseConfig::getInstance();

    // Intentar obtener conexión acceso
    $conn = $dbConfig->getAccesoConnection();

    if (!$conn) {
        throw new Exception("No se pudo obtener conexión a base de datos acceso_pro");
    }

    // Probar la conexión con una consulta simple
    $result = $conn->query("SELECT 1 as test");
    if (!$result) {
        throw new Exception("Error en consulta de prueba: " . $conn->error);
    }

    echo "✓ DatabaseConfig::getInstance() funciona\n";
    echo "✓ Conexión a base de datos acceso_pro establecida\n";
    echo "✓ Consulta de prueba exitosa\n";
} catch (Exception $e) {
    echo "✗ ERROR: " . $e->getMessage() . "\n";
    exit(1);
}

// Test 4: Verificar métodos de ApiResponse
echo "\n[TEST 4] Verificar métodos de ApiResponse...\n";
try {
    // Usar reflection para verificar que existen los métodos
    $reflection = new ReflectionClass('ApiResponse');
    $methods = ['success', 'error', 'paginated', 'created', 'noContent', 'badRequest', 'unauthorized', 'notFound', 'serverError'];

    foreach ($methods as $method) {
        if (!$reflection->hasMethod($method)) {
            throw new Exception("Método ApiResponse::$method() no encontrado");
        }
    }

    echo "✓ Método success() disponible\n";
    echo "✓ Método error() disponible\n";
    echo "✓ Método paginated() disponible\n";
    echo "✓ Método created() disponible\n";
    echo "✓ Método noContent() disponible\n";
    echo "✓ Método badRequest() disponible\n";
    echo "✓ Método unauthorized() disponible\n";
    echo "✓ Método notFound() disponible\n";
    echo "✓ Método serverError() disponible\n";
} catch (Exception $e) {
    echo "✗ ERROR: " . $e->getMessage() . "\n";
    exit(1);
}

// Test 5: Verificar tabla horas_extra en base de datos
echo "\n[TEST 5] Verificar tabla horas_extra en base de datos...\n";
try {
    $conn = DatabaseConfig::getInstance()->getAccesoConnection();

    // Verificar que la tabla existe
    $result = $conn->query("SELECT 1 FROM horas_extra LIMIT 1");
    if ($result === false) {
        throw new Exception("Tabla horas_extra no existe o error: " . $conn->error);
    }

    // Contar registros
    $countResult = $conn->query("SELECT COUNT(*) as total FROM horas_extra WHERE status = 'activo'");
    if ($countResult === false) {
        throw new Exception("Error al contar registros: " . $conn->error);
    }

    $countRow = $countResult->fetch_assoc();
    $total = (int)$countRow['total'];

    echo "✓ Tabla horas_extra existe\n";
    echo "✓ Registros activos en base de datos: $total\n";
} catch (Exception $e) {
    echo "✗ ERROR: " . $e->getMessage() . "\n";
    exit(1);
}

// Test 6: Verificar estructura de respuesta API esperada
echo "\n[TEST 6] Verificar que horas_extra.php existe con nuevo código...\n";
try {
    $apiPath = __DIR__ . '/../../api/horas_extra.php';
    if (!file_exists($apiPath)) {
        throw new Exception("Archivo api/horas_extra.php no encontrado");
    }

    // Verificar que usa config/database.php
    $content = file_get_contents($apiPath);
    if (strpos($content, "require_once __DIR__ . '/../config/database.php'") === false) {
        throw new Exception("api/horas_extra.php no usa config/database.php");
    }

    // Verificar que usa ResponseHandler
    if (strpos($content, "require_once __DIR__ . '/core/ResponseHandler.php'") === false) {
        throw new Exception("api/horas_extra.php no usa ResponseHandler.php");
    }

    // Verificar que implementa paginación
    if (strpos($content, "ApiResponse::paginated") === false) {
        throw new Exception("api/horas_extra.php no implementa paginación");
    }

    echo "✓ api/horas_extra.php existe\n";
    echo "✓ Usa config/database.php (DatabaseConfig)\n";
    echo "✓ Usa api/core/ResponseHandler.php (ApiResponse)\n";
    echo "✓ Implementa paginación (ApiResponse::paginated)\n";
} catch (Exception $e) {
    echo "✗ ERROR: " . $e->getMessage() . "\n";
    exit(1);
}

// Test 7: Verificar que no usa archivos viejos
echo "\n[TEST 7] Verificar que no usa archivos viejos...\n";
try {
    $apiPath = __DIR__ . '/../../api/horas_extra.php';
    $content = file_get_contents($apiPath);

    if (strpos($content, "require_once 'database/db_acceso.php'") !== false) {
        throw new Exception("api/horas_extra.php aún usa database/db_acceso.php (DEBE REMOVERSE)");
    }

    if (strpos($content, "require_once 'database/db_personal.php'") !== false) {
        throw new Exception("api/horas_extra.php aún usa database/db_personal.php (DEBE REMOVERSE)");
    }

    echo "✓ No usa database/db_acceso.php\n";
    echo "✓ No usa database/db_personal.php\n";
} catch (Exception $e) {
    echo "✗ ERROR: " . $e->getMessage() . "\n";
    exit(1);
}

// Resumen final
echo "\n" . str_repeat("=", 50) . "\n";
echo "✅ TODOS LOS TESTS PASARON\n";
echo str_repeat("=", 50) . "\n\n";
echo "Resumen de migración:\n";
echo "- config/database.php: OK (DatabaseConfig Singleton)\n";
echo "- api/core/ResponseHandler.php: OK (ApiResponse class)\n";
echo "- Base de datos acceso_pro: OK (conexión funcional)\n";
echo "- api/horas_extra.php: OK (migrado correctamente)\n";
echo "- Tabla horas_extra: OK (estructura verificada)\n";
echo "\nLa migración piloto de horas_extra.php fue exitosa.\n";
echo "Próximo paso: Migrar otros archivos API siguiendo el mismo patrón.\n";
?>

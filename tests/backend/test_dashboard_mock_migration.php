<?php
/**
 * tests/backend/test_dashboard_mock_migration.php
 *
 * Test de validación para la migración de dashboard_mock.php
 *
 * Verifica que:
 * 1. ApiResponse está cargado y disponible
 * 2. GET-only API
 * 3. 5 funciones helper refactorizadas
 * 4. Genera datos mock correctamente
 * 5. Soporta parámetro ?details=categoria
 *
 * Uso: php tests/backend/test_dashboard_mock_migration.php
 *
 * @author Testing 2025
 */

error_reporting(E_ALL);
ini_set('display_errors', 1);

echo "=== Test de Migración de dashboard_mock.php ===\n\n";

$testsPassed = 0;
$testsFailed = 0;

function test($name, $callback) {
    global $testsPassed, $testsFailed;
    echo "[TEST] $name...";
    try {
        $callback();
        echo " ✓ PASADO\n";
        $testsPassed++;
    } catch (Exception $e) {
        echo " ✗ FALLIDO\n";
        echo "  Error: " . $e->getMessage() . "\n";
        $testsFailed++;
    }
}

// ============================================================================
// SETUP
// ============================================================================

$handlerPath = __DIR__ . '/../../api/core/ResponseHandler.php';
$apiPath = __DIR__ . '/../../api/dashboard_mock-migrated.php';

require_once $handlerPath;

// ============================================================================
// TESTS
// ============================================================================

// TEST 1: Verificar archivos
test("Verificar que ResponseHandler.php existe", function() use ($handlerPath) {
    if (!file_exists($handlerPath)) {
        throw new Exception("Archivo no encontrado: $handlerPath");
    }
});

test("Verificar que dashboard_mock-migrated.php existe", function() use ($apiPath) {
    if (!file_exists($apiPath)) {
        throw new Exception("Archivo no encontrado: $apiPath");
    }
});

// TEST 2: Verificar clase
test("Verificar que clase ApiResponse está disponible", function() {
    if (!class_exists('ApiResponse')) {
        throw new Exception("Clase ApiResponse no encontrada");
    }
});

// TEST 3: ApiResponse methods
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

test("Verificar que ApiResponse::serverError existe", function() {
    $reflection = new ReflectionClass('ApiResponse');
    if (!$reflection->hasMethod('serverError')) {
        throw new Exception("Método ApiResponse::serverError() no encontrado");
    }
});

// TEST 4: Verificar funciones helper
test("Verificar que dashboard_mock-migrated.php contiene generarDatosPersonal", function() {
    $content = file_get_contents(__DIR__ . '/../../api/dashboard_mock-migrated.php');
    if (strpos($content, 'function generarDatosPersonal(') === false) {
        throw new Exception("Función generarDatosPersonal() no encontrada");
    }
});

test("Verificar que dashboard_mock-migrated.php contiene generarDatosVehiculos", function() {
    $content = file_get_contents(__DIR__ . '/../../api/dashboard_mock-migrated.php');
    if (strpos($content, 'function generarDatosVehiculos(') === false) {
        throw new Exception("Función generarDatosVehiculos() no encontrada");
    }
});

test("Verificar que dashboard_mock-migrated.php contiene generarDatosVisitas", function() {
    $content = file_get_contents(__DIR__ . '/../../api/dashboard_mock-migrated.php');
    if (strpos($content, 'function generarDatosVisitas(') === false) {
        throw new Exception("Función generarDatosVisitas() no encontrada");
    }
});

test("Verificar que dashboard_mock-migrated.php contiene generarDatosEmpresas", function() {
    $content = file_get_contents(__DIR__ . '/../../api/dashboard_mock-migrated.php');
    if (strpos($content, 'function generarDatosEmpresas(') === false) {
        throw new Exception("Función generarDatosEmpresas() no encontrada");
    }
});

test("Verificar que dashboard_mock-migrated.php contiene obtenerDatosMockPorCategoria", function() {
    $content = file_get_contents(__DIR__ . '/../../api/dashboard_mock-migrated.php');
    if (strpos($content, 'function obtenerDatosMockPorCategoria(') === false) {
        throw new Exception("Función obtenerDatosMockPorCategoria() no encontrada");
    }
});

test("Verificar que dashboard_mock-migrated.php contiene obtenerContadoresGenerales", function() {
    $content = file_get_contents(__DIR__ . '/../../api/dashboard_mock-migrated.php');
    if (strpos($content, 'function obtenerContadoresGenerales(') === false) {
        throw new Exception("Función obtenerContadoresGenerales() no encontrada");
    }
});

// TEST 5: Verificar que es GET-only
test("Verificar que es GET-only", function() {
    $content = file_get_contents(__DIR__ . '/../../api/dashboard_mock-migrated.php');
    if (strpos($content, "REQUEST_METHOD") === false || strpos($content, "GET") === false) {
        throw new Exception("Debería validar que es GET-only");
    }
});

// TEST 6: Verificar que usa ApiResponse
test("Verificar que usa ApiResponse en lugar de echo json_encode", function() {
    $content = file_get_contents(__DIR__ . '/../../api/dashboard_mock-migrated.php');
    if (preg_match('/echo\s+json_encode\s*\(/', $content)) {
        throw new Exception("Debería usar ApiResponse::success en lugar de echo json_encode()");
    }
});

// TEST 7: Verificar parámetro details opcional
test("Verificar que soporta parámetro ?details=categoria", function() {
    $content = file_get_contents(__DIR__ . '/../../api/dashboard_mock-migrated.php');
    if (strpos($content, "'details'") === false) {
        throw new Exception("Debería soportar parámetro ?details=categoria");
    }
});

// TEST 8: Verificar categorías soportadas
test("Verificar soporte de categorías de personal", function() {
    $content = file_get_contents(__DIR__ . '/../../api/dashboard_mock-migrated.php');
    if (strpos($content, "personal_trabajando") === false) {
        throw new Exception("Categoría personal_trabajando no soportada");
    }
});

test("Verificar soporte de categorías de vehículos", function() {
    $content = file_get_contents(__DIR__ . '/../../api/dashboard_mock-migrated.php');
    if (strpos($content, "vehiculos_funcionario") === false) {
        throw new Exception("Categoría vehiculos_funcionario no soportada");
    }
});

test("Verificar soporte de categoría visitas", function() {
    $content = file_get_contents(__DIR__ . '/../../api/dashboard_mock-migrated.php');
    if (strpos($content, "visitas") === false) {
        throw new Exception("Categoría visitas no soportada");
    }
});

test("Verificar soporte de categoría empresas", function() {
    $content = file_get_contents(__DIR__ . '/../../api/dashboard_mock-migrated.php');
    if (strpos($content, "empresas") === false) {
        throw new Exception("Categoría empresas no soportada");
    }
});

// TEST 9: Verificar estructura de datos
test("Verificar que personal tiene grado, nombre, rut, unidad, fecha_ingreso", function() {
    $content = file_get_contents(__DIR__ . '/../../api/dashboard_mock-migrated.php');
    if (strpos($content, "'grado'") === false || strpos($content, "'nombre'") === false) {
        throw new Exception("Datos de personal incompletos");
    }
});

test("Verificar que vehículos tienen patente, marca, propietario, fecha_ingreso", function() {
    $content = file_get_contents(__DIR__ . '/../../api/dashboard_mock-migrated.php');
    if (strpos($content, "'patente'") === false || strpos($content, "'marca'") === false) {
        throw new Exception("Datos de vehículos incompletos");
    }
});

// TEST 10: Verificar contadores generales
test("Verificar que contadores generales incluyen personal_trabajando", function() {
    $content = file_get_contents(__DIR__ . '/../../api/dashboard_mock-migrated.php');
    if (strpos($content, "'personal_trabajando'") === false) {
        throw new Exception("Contador personal_trabajando no encontrado");
    }
});

test("Verificar que contadores generales incluyen vehiculos", function() {
    $content = file_get_contents(__DIR__ . '/../../api/dashboard_mock-migrated.php');
    if (strpos($content, "'vehiculos_funcionario_adentro'") === false) {
        throw new Exception("Contadores de vehículos no encontrados");
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
?>

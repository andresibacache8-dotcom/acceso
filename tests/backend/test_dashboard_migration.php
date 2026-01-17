<?php
/**
 * tests/backend/test_dashboard_migration.php
 *
 * Test de validación para la migración de dashboard.php
 *
 * Verifica que:
 * 1. Los archivos de configuración se cargan correctamente
 * 2. Respuestas API estandarizadas
 * 3. 16 funciones helpers refactorizadas
 * 4. Eliminación de duplicación de queries
 * 5. Estructura modular y reutilizable
 *
 * Uso: php tests/backend/test_dashboard_migration.php
 *
 * @author Testing 2025
 */

error_reporting(E_ALL);
ini_set('display_errors', 1);

echo "=== Test de Migración de dashboard.php ===\n\n";

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

require_once $configPath;
require_once $handlerPath;

// ============================================================================
// TESTS
// ============================================================================

// TEST 1: Verificar archivos
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

test("Verificar que dashboard-migrated.php existe", function() {
    $file = __DIR__ . '/../../api/dashboard-migrated.php';
    if (!file_exists($file)) {
        throw new Exception("Archivo no encontrado: $file");
    }
});

// TEST 2: Verificar clases
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

// TEST 4: Verificar helpers refactorizadas
test("Verificar que dashboard-migrated.php contiene get_count_by_type", function() {
    $content = file_get_contents(__DIR__ . '/../../api/dashboard-migrated.php');
    if (strpos($content, 'function get_count_by_type(') === false) {
        throw new Exception("Función get_count_by_type() no encontrada");
    }
});

test("Verificar que dashboard-migrated.php contiene obtener_personal_trabajando", function() {
    $content = file_get_contents(__DIR__ . '/../../api/dashboard-migrated.php');
    if (strpos($content, 'function obtener_personal_trabajando(') === false) {
        throw new Exception("Función obtener_personal_trabajando() no encontrada");
    }
});

test("Verificar que dashboard-migrated.php contiene obtener_personal_por_unidad", function() {
    $content = file_get_contents(__DIR__ . '/../../api/dashboard-migrated.php');
    if (strpos($content, 'function obtener_personal_por_unidad(') === false) {
        throw new Exception("Función obtener_personal_por_unidad() no encontrada");
    }
});

test("Verificar que dashboard-migrated.php contiene obtener_personal_por_unidad_detalle", function() {
    $content = file_get_contents(__DIR__ . '/../../api/dashboard-migrated.php');
    if (strpos($content, 'function obtener_personal_por_unidad_detalle(') === false) {
        throw new Exception("Función obtener_personal_por_unidad_detalle() no encontrada");
    }
});

test("Verificar que dashboard-migrated.php contiene obtener_personal_residiendo", function() {
    $content = file_get_contents(__DIR__ . '/../../api/dashboard-migrated.php');
    if (strpos($content, 'function obtener_personal_residiendo(') === false) {
        throw new Exception("Función obtener_personal_residiendo() no encontrada");
    }
});

test("Verificar que dashboard-migrated.php contiene obtener_personal_otras_actividades", function() {
    $content = file_get_contents(__DIR__ . '/../../api/dashboard-migrated.php');
    if (strpos($content, 'function obtener_personal_otras_actividades(') === false) {
        throw new Exception("Función obtener_personal_otras_actividades() no encontrada");
    }
});

test("Verificar que dashboard-migrated.php contiene obtener_visitas_adentro", function() {
    $content = file_get_contents(__DIR__ . '/../../api/dashboard-migrated.php');
    if (strpos($content, 'function obtener_visitas_adentro(') === false) {
        throw new Exception("Función obtener_visitas_adentro() no encontrada");
    }
});

test("Verificar que dashboard-migrated.php contiene obtener_personal_en_comision", function() {
    $content = file_get_contents(__DIR__ . '/../../api/dashboard-migrated.php');
    if (strpos($content, 'function obtener_personal_en_comision(') === false) {
        throw new Exception("Función obtener_personal_en_comision() no encontrada");
    }
});

test("Verificar que dashboard-migrated.php contiene obtener_empresas_adentro", function() {
    $content = file_get_contents(__DIR__ . '/../../api/dashboard-migrated.php');
    if (strpos($content, 'function obtener_empresas_adentro(') === false) {
        throw new Exception("Función obtener_empresas_adentro() no encontrada");
    }
});

test("Verificar que dashboard-migrated.php contiene obtener_vehiculos_por_tipo", function() {
    $content = file_get_contents(__DIR__ . '/../../api/dashboard-migrated.php');
    if (strpos($content, 'function obtener_vehiculos_por_tipo(') === false) {
        throw new Exception("Función obtener_vehiculos_por_tipo() no encontrada");
    }
});

test("Verificar que dashboard-migrated.php contiene obtener_alertas_atrasado", function() {
    $content = file_get_contents(__DIR__ . '/../../api/dashboard-migrated.php');
    if (strpos($content, 'function obtener_alertas_atrasado(') === false) {
        throw new Exception("Función obtener_alertas_atrasado() no encontrada");
    }
});

test("Verificar que dashboard-migrated.php contiene obtener_alertas_atrasado_por_unidad", function() {
    $content = file_get_contents(__DIR__ . '/../../api/dashboard-migrated.php');
    if (strpos($content, 'function obtener_alertas_atrasado_por_unidad(') === false) {
        throw new Exception("Función obtener_alertas_atrasado_por_unidad() no encontrada");
    }
});

test("Verificar que dashboard-migrated.php contiene obtener_alertas_no_autorizado", function() {
    $content = file_get_contents(__DIR__ . '/../../api/dashboard-migrated.php');
    if (strpos($content, 'function obtener_alertas_no_autorizado(') === false) {
        throw new Exception("Función obtener_alertas_no_autorizado() no encontrada");
    }
});

test("Verificar que dashboard-migrated.php contiene obtener_alertas_no_autorizado_por_unidad", function() {
    $content = file_get_contents(__DIR__ . '/../../api/dashboard-migrated.php');
    if (strpos($content, 'function obtener_alertas_no_autorizado_por_unidad(') === false) {
        throw new Exception("Función obtener_alertas_no_autorizado_por_unidad() no encontrada");
    }
});

test("Verificar que dashboard-migrated.php contiene obtener_detalles", function() {
    $content = file_get_contents(__DIR__ . '/../../api/dashboard-migrated.php');
    if (strpos($content, 'function obtener_detalles(') === false) {
        throw new Exception("Función obtener_detalles() no encontrada");
    }
});

// TEST 5: Verificar eliminación de funciones viejas
test("Verificar que NO hay get_count_by_type_with_join (función vieja)", function() {
    $content = file_get_contents(__DIR__ . '/../../api/dashboard-migrated.php');
    if (strpos($content, 'function get_count_by_type_with_join(') !== false) {
        throw new Exception("get_count_by_type_with_join() debería estar eliminada");
    }
});

// TEST 6: Verificar que usa config/database.php
test("Verificar que dashboard-migrated.php require config/database.php", function() {
    $content = file_get_contents(__DIR__ . '/../../api/dashboard-migrated.php');
    if (strpos($content, "config/database.php") === false) {
        throw new Exception("No usa config/database.php");
    }
});

// TEST 7: Verificar que NO usa archivos legacy
test("Verificar que NO usa database/db_acceso.php", function() {
    $content = file_get_contents(__DIR__ . '/../../api/dashboard-migrated.php');
    if (strpos($content, "database/db_acceso.php") !== false) {
        throw new Exception("Debería usar config/database.php, no database/db_acceso.php");
    }
});

test("Verificar que NO usa database/db_personal.php", function() {
    $content = file_get_contents(__DIR__ . '/../../api/dashboard-migrated.php');
    if (strpos($content, "database/db_personal.php") !== false) {
        throw new Exception("Debería usar config/database.php, no database/db_personal.php");
    }
});

// TEST 8: Verificar que usa ApiResponse
test("Verificar que dashboard-migrated.php usa ApiResponse en lugar de echo", function() {
    $content = file_get_contents(__DIR__ . '/../../api/dashboard-migrated.php');

    // Verificar que NO tiene "echo json_encode"
    if (strpos($content, "echo json_encode") !== false) {
        throw new Exception("Debería usar ApiResponse en lugar de echo json_encode()");
    }
});

// TEST 9: Refactorización - Modularidad
test("Verificar refactorización: get_count_by_type centraliza lógica", function() {
    $content = file_get_contents(__DIR__ . '/../../api/dashboard-migrated.php');

    // Contar cuántas veces aparece get_count_by_type
    $count = preg_match_all('/get_count_by_type\(/', $content);

    if ($count < 3) {
        throw new Exception("get_count_by_type() debería usarse múltiples veces");
    }
});

test("Verificar refactorización: obtener_detalles centraliza router", function() {
    $content = file_get_contents(__DIR__ . '/../../api/dashboard-migrated.php');

    // Debe haber solo 1 función obtener_detalles
    $count = preg_match_all('/function obtener_detalles\(/', $content);

    if ($count !== 1) {
        throw new Exception("obtener_detalles() debería existir una sola vez");
    }

    // El switch dentro de obtener_detalles debería manejar todos los casos
    if (strpos($content, "case 'personal-trabajando':") === false) {
        throw new Exception("obtener_detalles() debería contener switch con casos");
    }
});

// TEST 10: Verificar métodos
test("Verificar que dashboard-migrated.php tiene handle_get", function() {
    $content = file_get_contents(__DIR__ . '/../../api/dashboard-migrated.php');
    if (strpos($content, 'function handle_get(') === false) {
        throw new Exception("Función handle_get() no encontrada");
    }
});

// TEST 11: Verificar ruta de ejecución
test("Verificar que dashboard-migrated.php usa DatabaseConfig::getInstance()", function() {
    $content = file_get_contents(__DIR__ . '/../../api/dashboard-migrated.php');
    if (strpos($content, 'DatabaseConfig::getInstance()') === false) {
        throw new Exception("No usa DatabaseConfig::getInstance()");
    }
});

// TEST 12: Verificar que es GET-only
test("Verificar que dashboard-migrated.php es GET-only", function() {
    $content = file_get_contents(__DIR__ . '/../../api/dashboard-migrated.php');
    if (strpos($content, 'REQUEST_METHOD') === false || strpos($content, "'GET'") === false) {
        throw new Exception("Debería validar que es GET-only");
    }
});

// TEST 13: Verificar ruta de detalles (modales)
test("Verificar que soporta ?details=CATEGORY", function() {
    $content = file_get_contents(__DIR__ . '/../../api/dashboard-migrated.php');
    if (strpos($content, "isset(\$_GET['details'])") === false) {
        throw new Exception("Debería soportar ?details=CATEGORY");
    }
});

// TEST 14: Verificar que soporta contadores
test("Verificar que retorna contadores sin ?details", function() {
    $content = file_get_contents(__DIR__ . '/../../api/dashboard-migrated.php');
    if (strpos($content, "'personal_trabajando'") === false) {
        throw new Exception("Debería retornar contadores");
    }
});

// TEST 15: Verificar estructura de alertas
test("Verificar alertas: atrasado y no-autorizado", function() {
    $content = file_get_contents(__DIR__ . '/../../api/dashboard-migrated.php');
    if (strpos($content, "'alertas_atrasado'") === false || strpos($content, "'alertas_no_autorizado'") === false) {
        throw new Exception("Debería contener ambas alertas");
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

<?php
/**
 * tests/backend/test_vehiculo_historial_migration.php
 *
 * Test de validación para la migración de vehiculo_historial.php
 *
 * Verifica que:
 * 1. Los archivos de configuración se cargan correctamente
 * 2. Respuestas API estandarizadas
 * 3. 2 funciones helpers refactorizadas
 * 4. GET-only (sin POST/PUT/DELETE)
 * 5. Autenticación requerida
 * 6. Parámetro vehiculo_id obligatorio
 * 7. Enriquecimiento de datos con propietarios
 *
 * Uso: php tests/backend/test_vehiculo_historial_migration.php
 *
 * @author Testing 2025
 */

error_reporting(E_ALL);
ini_set('display_errors', 1);

echo "=== Test de Migración de vehiculo_historial.php ===\n\n";

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

$configPath = __DIR__ . '/../../config/database.php';
$handlerPath = __DIR__ . '/../../api/core/ResponseHandler.php';
$apiPath = __DIR__ . '/../../api/vehiculo_historial-migrated.php';

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

test("Verificar que vehiculo_historial-migrated.php existe", function() use ($apiPath) {
    if (!file_exists($apiPath)) {
        throw new Exception("Archivo no encontrado: $apiPath");
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

test("Verificar que ApiResponse::badRequest existe", function() {
    $reflection = new ReflectionClass('ApiResponse');
    if (!$reflection->hasMethod('badRequest')) {
        throw new Exception("Método ApiResponse::badRequest() no encontrado");
    }
});

test("Verificar que ApiResponse::notFound existe", function() {
    $reflection = new ReflectionClass('ApiResponse');
    if (!$reflection->hasMethod('notFound')) {
        throw new Exception("Método ApiResponse::notFound() no encontrado");
    }
});

test("Verificar que ApiResponse::unauthorized existe", function() {
    $reflection = new ReflectionClass('ApiResponse');
    if (!$reflection->hasMethod('unauthorized')) {
        throw new Exception("Método ApiResponse::unauthorized() no encontrado");
    }
});

// TEST 4: Verificar funciones helper
test("Verificar que vehiculo_historial-migrated.php contiene traducirTipoCambio", function() {
    $content = file_get_contents(__DIR__ . '/../../api/vehiculo_historial-migrated.php');
    if (strpos($content, 'function traducirTipoCambio(') === false) {
        throw new Exception("Función traducirTipoCambio() no encontrada");
    }
});

test("Verificar que vehiculo_historial-migrated.php contiene formatearRegistroHistorial", function() {
    $content = file_get_contents(__DIR__ . '/../../api/vehiculo_historial-migrated.php');
    if (strpos($content, 'function formatearRegistroHistorial(') === false) {
        throw new Exception("Función formatearRegistroHistorial() no encontrada");
    }
});

test("Verificar que vehiculo_historial-migrated.php contiene obtenerHistorialVehiculo", function() {
    $content = file_get_contents(__DIR__ . '/../../api/vehiculo_historial-migrated.php');
    if (strpos($content, 'function obtenerHistorialVehiculo(') === false) {
        throw new Exception("Función obtenerHistorialVehiculo() no encontrada");
    }
});

test("Verificar que vehiculo_historial-migrated.php contiene obtenerVehiculoActual", function() {
    $content = file_get_contents(__DIR__ . '/../../api/vehiculo_historial-migrated.php');
    if (strpos($content, 'function obtenerVehiculoActual(') === false) {
        throw new Exception("Función obtenerVehiculoActual() no encontrada");
    }
});

// TEST 5: Verificar autenticación
test("Verificar que requiere autenticación (sesión)", function() {
    $content = file_get_contents(__DIR__ . '/../../api/vehiculo_historial-migrated.php');
    if (strpos($content, "logged_in") === false && strpos($content, "unauthorized") === false) {
        throw new Exception("Debería requerir autenticación");
    }
});

// TEST 6: Verificar que es GET-only
test("Verificar que es GET-only", function() {
    $content = file_get_contents(__DIR__ . '/../../api/vehiculo_historial-migrated.php');
    if (strpos($content, "REQUEST_METHOD") === false || strpos($content, "GET") === false) {
        throw new Exception("Debería validar que es GET-only");
    }
});

// TEST 7: Verificar eliminación de funciones legacy
test("Verificar que NO usa send_error()", function() {
    $content = file_get_contents(__DIR__ . '/../../api/vehiculo_historial-migrated.php');
    if (strpos($content, "function send_error(") !== false) {
        throw new Exception("send_error() debería estar eliminado");
    }
    if (preg_match('/\bsend_error\s*\(/', $content)) {
        throw new Exception("send_error() debería ser reemplazado por ApiResponse");
    }
});

// TEST 8: Verificar que usa config/database.php
test("Verificar que usa config/database.php", function() {
    $content = file_get_contents(__DIR__ . '/../../api/vehiculo_historial-migrated.php');
    if (strpos($content, "config/database.php") === false) {
        throw new Exception("No usa config/database.php");
    }
});

// TEST 9: Verificar que NO usa archivos legacy
test("Verificar que NO usa database/db_acceso.php", function() {
    $content = file_get_contents(__DIR__ . '/../../api/vehiculo_historial-migrated.php');
    if (strpos($content, "database/db_acceso.php") !== false) {
        throw new Exception("Debería usar config/database.php, no database/db_acceso.php");
    }
});

test("Verificar que NO usa database/db_personal.php", function() {
    $content = file_get_contents(__DIR__ . '/../../api/vehiculo_historial-migrated.php');
    if (strpos($content, "database/db_personal.php") !== false) {
        throw new Exception("Debería usar config/database.php, no database/db_personal.php");
    }
});

// TEST 10: Verificar parámetro obligatorio
test("Verificar que vehiculo_id es parámetro obligatorio", function() {
    $content = file_get_contents(__DIR__ . '/../../api/vehiculo_historial-migrated.php');
    if (strpos($content, "vehiculo_id") === false || strpos($content, "badRequest") === false) {
        throw new Exception("Debería validar vehiculo_id como obligatorio");
    }
});

// TEST 11: Verificar enriquecimiento de datos
test("Verificar enriquecimiento: propietario anterior y nuevo en historial", function() {
    $content = file_get_contents(__DIR__ . '/../../api/vehiculo_historial-migrated.php');
    if (strpos($content, "propietario_anterior_nombre") === false || strpos($content, "propietario_nuevo_nombre") === false) {
        throw new Exception("Debería enriquecer con propietarios anterior y nuevo");
    }
});

test("Verificar enriquecimiento: propietario actual en vehículo", function() {
    $content = file_get_contents(__DIR__ . '/../../api/vehiculo_historial-migrated.php');
    if (strpos($content, "propietario_actual_nombre") === false) {
        throw new Exception("Debería enriquecer con propietario actual");
    }
});

// TEST 12: Verificar traducción de tipos de cambio
test("Verificar soporte de tipos de cambio estándar", function() {
    $content = file_get_contents(__DIR__ . '/../../api/vehiculo_historial-migrated.php');
    $expected_types = ['creacion', 'actualizacion', 'cambio_propietario', 'eliminacion'];
    foreach ($expected_types as $type) {
        if (strpos($content, "'" . $type . "'") === false) {
            throw new Exception("Tipo de cambio '$type' no soportado");
        }
    }
});

// TEST 13: Verificar JOINs para enriquecimiento multi-tabla
test("Verificar JOINs a personal, empresa_empleados y visitas", function() {
    $content = file_get_contents(__DIR__ . '/../../api/vehiculo_historial-migrated.php');
    if (strpos($content, "personal_db.personal") === false) {
        throw new Exception("Debería JOIN a personal_db.personal");
    }
    if (strpos($content, "empresa_empleados") === false) {
        throw new Exception("Debería JOIN a empresa_empleados");
    }
    if (strpos($content, "visitas") === false) {
        throw new Exception("Debería JOIN a visitas");
    }
});

// TEST 14: Verificar manejo de fecha_cambio
test("Verificar formateo de fecha_cambio", function() {
    $content = file_get_contents(__DIR__ . '/../../api/vehiculo_historial-migrated.php');
    if (strpos($content, "fecha_cambio_formateada") === false) {
        throw new Exception("Debería formatear fecha_cambio");
    }
});

// TEST 15: Verificar decodificación de detalles JSON
test("Verificar decodificación de detalles JSON", function() {
    $content = file_get_contents(__DIR__ . '/../../api/vehiculo_historial-migrated.php');
    if (strpos($content, "json_decode") === false) {
        throw new Exception("Debería decodificar detalles JSON");
    }
});

// TEST 16: Verificar estructura de respuesta
test("Verificar que retorna vehiculo + historial", function() {
    $content = file_get_contents(__DIR__ . '/../../api/vehiculo_historial-migrated.php');
    if (strpos($content, "'vehiculo'") === false || strpos($content, "'historial'") === false) {
        throw new Exception("Debería retornar vehiculo y historial en respuesta");
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

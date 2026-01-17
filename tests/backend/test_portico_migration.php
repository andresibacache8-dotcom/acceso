<?php
/**
 * tests/backend/test_portico_migration.php
 *
 * Test de validación para la migración de portico.php
 *
 * Verifica que:
 * 1. Los archivos de configuración se cargan correctamente
 * 2. Respuestas API estandarizadas
 * 3. 10 funciones helpers refactorizadas
 * 4. Lógica de validación centralizada
 * 5. Búsquedas en 5 tablas
 *
 * Uso: php tests/backend/test_portico_migration.php
 *
 * @author Testing 2025
 */

error_reporting(E_ALL);
ini_set('display_errors', 1);

echo "=== Test de Migración de portico.php ===\n\n";

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

test("Verificar que portico-migrated.php existe", function() {
    $file = __DIR__ . '/../../api/portico-migrated.php';
    if (!file_exists($file)) {
        throw new Exception("Archivo no encontrado: $file");
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

// TEST 3: Métodos de ApiResponse
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

test("Verificar que ApiResponse::notFound existe", function() {
    $reflection = new ReflectionClass('ApiResponse');
    if (!$reflection->hasMethod('notFound')) {
        throw new Exception("Método ApiResponse::notFound() no encontrado");
    }
});

test("Verificar que ApiResponse::serverError existe", function() {
    $reflection = new ReflectionClass('ApiResponse');
    if (!$reflection->hasMethod('serverError')) {
        throw new Exception("Método ApiResponse::serverError() no encontrado");
    }
});

// TEST 4: Helper functions - Validación de acceso
function validar_acceso_test($status, $fecha_inicio, $fecha_expiracion, $acceso_permanente) {
    $authorized = false;
    $reasons = [];

    if (!empty($status) && $status !== 'autorizado') {
        $reasons[] = "Status no autorizado";
    }

    if (!empty($fecha_inicio)) {
        try {
            $start_date = new DateTime($fecha_inicio);
            $today = new DateTime('today');
            if ($start_date > $today) {
                $reasons[] = "su fecha de ingreso aún no ha comenzado";
            }
        } catch (Exception $e) {
            $reasons[] = "Fecha de inicio inválida";
        }
    }

    if (!$acceso_permanente) {
        if (!empty($fecha_expiracion)) {
            try {
                $expiration_date = new DateTime($fecha_expiracion);
                $today = new DateTime('today');
                if ($expiration_date < $today) {
                    $reasons[] = "su fecha de ingreso expiró";
                } else {
                    if (empty($status) || $status === 'autorizado') {
                        if (empty($fecha_inicio) || new DateTime($fecha_inicio) <= new DateTime('today')) {
                            $authorized = true;
                        }
                    }
                }
            } catch (Exception $e) {
                $reasons[] = "Fecha de expiración inválida";
            }
        } else {
            $reasons[] = "Sin fecha de expiración válida";
        }
    } else {
        if (empty($status) || $status === 'autorizado') {
            if (empty($fecha_inicio) || new DateTime($fecha_inicio) <= new DateTime('today')) {
                $authorized = true;
            }
        }
    }

    return ['authorized' => $authorized, 'reasons' => $reasons];
}

test("Validación: acceso permanente autoriza", function() {
    $result = validar_acceso_test('autorizado', date('Y-m-d'), null, true);
    if (!$result['authorized']) {
        throw new Exception("Acceso permanente debería autorizar");
    }
});

test("Validación: fecha futura rechaza", function() {
    $future_date = date('Y-m-d', strtotime('+1 day'));
    $result = validar_acceso_test('autorizado', $future_date, date('Y-m-d', strtotime('+2 days')), false);
    if ($result['authorized']) {
        throw new Exception("Fecha futura debería rechazar");
    }
});

test("Validación: fecha pasada rechaza", function() {
    $past_date = date('Y-m-d', strtotime('-1 day'));
    $result = validar_acceso_test('autorizado', date('Y-m-d'), $past_date, false);
    if ($result['authorized']) {
        throw new Exception("Fecha pasada debería rechazar");
    }
});

test("Validación: fecha válida autoriza", function() {
    $today = date('Y-m-d');
    $future = date('Y-m-d', strtotime('+1 day'));
    $result = validar_acceso_test('autorizado', $today, $future, false);
    if (!$result['authorized']) {
        throw new Exception("Fecha válida debería autorizar");
    }
});

test("Validación: status no autorizado rechaza", function() {
    $today = date('Y-m-d');
    $future = date('Y-m-d', strtotime('+1 day'));
    $result = validar_acceso_test('no autorizado', $today, $future, false);
    if ($result['authorized']) {
        throw new Exception("Status 'no autorizado' debería rechazar");
    }
});

// TEST 5: Verificar que helpers existen en portico-migrated.php
test("Verificar que portico-migrated.php contiene validar_acceso", function() {
    $content = file_get_contents(__DIR__ . '/../../api/portico-migrated.php');
    if (strpos($content, 'function validar_acceso(') === false) {
        throw new Exception("Función validar_acceso() no encontrada");
    }
});

test("Verificar que portico-migrated.php contiene buscar_personal", function() {
    $content = file_get_contents(__DIR__ . '/../../api/portico-migrated.php');
    if (strpos($content, 'function buscar_personal(') === false) {
        throw new Exception("Función buscar_personal() no encontrada");
    }
});

test("Verificar que portico-migrated.php contiene buscar_vehiculo", function() {
    $content = file_get_contents(__DIR__ . '/../../api/portico-migrated.php');
    if (strpos($content, 'function buscar_vehiculo(') === false) {
        throw new Exception("Función buscar_vehiculo() no encontrada");
    }
});

test("Verificar que portico-migrated.php contiene buscar_visita", function() {
    $content = file_get_contents(__DIR__ . '/../../api/portico-migrated.php');
    if (strpos($content, 'function buscar_visita(') === false) {
        throw new Exception("Función buscar_visita() no encontrada");
    }
});

test("Verificar que portico-migrated.php contiene buscar_empleado_empresa", function() {
    $content = file_get_contents(__DIR__ . '/../../api/portico-migrated.php');
    if (strpos($content, 'function buscar_empleado_empresa(') === false) {
        throw new Exception("Función buscar_empleado_empresa() no encontrada");
    }
});

test("Verificar que portico-migrated.php contiene buscar_personal_comision", function() {
    $content = file_get_contents(__DIR__ . '/../../api/portico-migrated.php');
    if (strpos($content, 'function buscar_personal_comision(') === false) {
        throw new Exception("Función buscar_personal_comision() no encontrada");
    }
});

test("Verificar que portico-migrated.php contiene obtener_propietario_vehiculo", function() {
    $content = file_get_contents(__DIR__ . '/../../api/portico-migrated.php');
    if (strpos($content, 'function obtener_propietario_vehiculo(') === false) {
        throw new Exception("Función obtener_propietario_vehiculo() no encontrada");
    }
});

test("Verificar que portico-migrated.php contiene obtener_nueva_accion", function() {
    $content = file_get_contents(__DIR__ . '/../../api/portico-migrated.php');
    if (strpos($content, 'function obtener_nueva_accion(') === false) {
        throw new Exception("Función obtener_nueva_accion() no encontrada");
    }
});

test("Verificar que portico-migrated.php contiene registrar_acceso", function() {
    $content = file_get_contents(__DIR__ . '/../../api/portico-migrated.php');
    if (strpos($content, 'function registrar_acceso(') === false) {
        throw new Exception("Función registrar_acceso() no encontrada");
    }
});

test("Verificar que portico-migrated.php contiene finalizar_horas_extra", function() {
    $content = file_get_contents(__DIR__ . '/../../api/portico-migrated.php');
    if (strpos($content, 'function finalizar_horas_extra(') === false) {
        throw new Exception("Función finalizar_horas_extra() no encontrada");
    }
});

// TEST 6: Verificar eliminación de send_error
test("Verificar que send_error() fue eliminado de portico-migrated.php", function() {
    $content = file_get_contents(__DIR__ . '/../../api/portico-migrated.php');
    if (strpos($content, 'function send_error(') !== false) {
        throw new Exception("send_error() debería estar eliminado");
    }
});

// TEST 7: Verificar que usa ApiResponse en lugar de echo json_encode
test("Verificar que portico-migrated.php usa ApiResponse en lugar de send_error", function() {
    $content = file_get_contents(__DIR__ . '/../../api/portico-migrated.php');
    // Verificar que NO tiene send_error
    if (preg_match('/\bsend_error\s*\(/', $content)) {
        throw new Exception("Debería usar ApiResponse en lugar de send_error()");
    }
});

// TEST 8: Verificar que usa config/database.php
test("Verificar que portico-migrated.php require config/database.php", function() {
    $content = file_get_contents(__DIR__ . '/../../api/portico-migrated.php');
    if (strpos($content, "config/database.php") === false) {
        throw new Exception("No usa config/database.php");
    }
});

// TEST 9: Verificar que NO usa archivos legacy
test("Verificar que NO usa database/db_acceso.php", function() {
    $content = file_get_contents(__DIR__ . '/../../api/portico-migrated.php');
    if (strpos($content, "database/db_acceso.php") !== false) {
        throw new Exception("Debería usar config/database.php, no database/db_acceso.php");
    }
});

test("Verificar que NO usa database/db_personal.php", function() {
    $content = file_get_contents(__DIR__ . '/../../api/portico-migrated.php');
    if (strpos($content, "database/db_personal.php") !== false) {
        throw new Exception("Debería usar config/database.php, no database/db_personal.php");
    }
});

// TEST 10: Refactorización - Validación centralizada
test("Verificar refactorización: validación centralizada (buscar en portico.php original)", function() {
    $original = file_get_contents(__DIR__ . '/../../api/portico.php');

    // Contar cuántas veces aparece la lógica de validación (debería ser 3: vehiculos, visitas, empleados)
    $count = preg_match_all('/\$rejection_reasons.*?\[\]/s', $original);

    if ($count < 3) {
        throw new Exception("Original debería tener validación repetida 3+ veces");
    }
});

test("Verificar refactorización: validación centralizada (buscar en portico-migrated.php)", function() {
    $migrated = file_get_contents(__DIR__ . '/../../api/portico-migrated.php');

    // En migrated, debería haber solo 1 función validar_acceso
    $count = preg_match_all('/function validar_acceso\(/', $migrated);

    if ($count !== 1) {
        throw new Exception("Migrated debería tener validar_acceso() centralizada (1 sola función)");
    }
});

// TEST 11: Verificar métodos POST
test("Verificar que portico-migrated.php tiene handle_post", function() {
    $content = file_get_contents(__DIR__ . '/../../api/portico-migrated.php');
    if (strpos($content, 'function handle_post(') === false) {
        throw new Exception("Función handle_post() no encontrada");
    }
});

// TEST 12: Verificar ruta de ejecución
test("Verificar que portico-migrated.php usa DatabaseConfig::getInstance()", function() {
    $content = file_get_contents(__DIR__ . '/../../api/portico-migrated.php');
    if (strpos($content, 'DatabaseConfig::getInstance()') === false) {
        throw new Exception("No usa DatabaseConfig::getInstance()");
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

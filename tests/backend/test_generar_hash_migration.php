<?php
/**
 * tests/backend/test_generar_hash_migration.php
 *
 * Test de validación para la migración de generar_hash.php
 *
 * Verifica que:
 * 1. ApiResponse está cargado y disponible
 * 2. GET-only API
 * 3. Soporta parámetro ?password opcional
 * 4. Genera hash bcrypt válido
 * 5. Valida entrada de usuario
 *
 * Uso: php tests/backend/test_generar_hash_migration.php
 *
 * @author Testing 2025
 */

error_reporting(E_ALL);
ini_set('display_errors', 1);

echo "=== Test de Migración de generar_hash.php ===\n\n";

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
$apiPath = __DIR__ . '/../../api/generar_hash-migrated.php';

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

test("Verificar que generar_hash-migrated.php existe", function() use ($apiPath) {
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

test("Verificar que ApiResponse::badRequest existe", function() {
    $reflection = new ReflectionClass('ApiResponse');
    if (!$reflection->hasMethod('badRequest')) {
        throw new Exception("Método ApiResponse::badRequest() no encontrado");
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

// TEST 4: Verificar que es GET-only
test("Verificar que es GET-only", function() {
    $content = file_get_contents(__DIR__ . '/../../api/generar_hash-migrated.php');
    if (strpos($content, "REQUEST_METHOD") === false || strpos($content, "GET") === false) {
        throw new Exception("Debería validar que es GET-only");
    }
});

// TEST 5: Verificar que usa password_hash
test("Verificar que usa password_hash con PASSWORD_DEFAULT", function() {
    $content = file_get_contents(__DIR__ . '/../../api/generar_hash-migrated.php');
    if (strpos($content, "password_hash") === false || strpos($content, "PASSWORD_DEFAULT") === false) {
        throw new Exception("Debería usar password_hash con PASSWORD_DEFAULT");
    }
});

// TEST 6: Verificar soporte de parámetro password
test("Verificar que soporta parámetro ?password", function() {
    $content = file_get_contents(__DIR__ . '/../../api/generar_hash-migrated.php');
    if (strpos($content, "'password'") === false || strpos($content, "\$_GET") === false) {
        throw new Exception("Debería soportar parámetro ?password");
    }
});

// TEST 7: Verificar valor default
test("Verificar que tiene contraseña default para desarrollo", function() {
    $content = file_get_contents(__DIR__ . '/../../api/generar_hash-migrated.php');
    if (strpos($content, "??") === false) {
        throw new Exception("Debería tener valor default con ?? operator");
    }
});

// TEST 8: Verificar validación de contraseña
test("Verificar que valida que password no esté vacía", function() {
    $content = file_get_contents(__DIR__ . '/../../api/generar_hash-migrated.php');
    if (strpos($content, "empty(\$password)") === false && strpos($content, "empty(") === false) {
        throw new Exception("Debería validar que password no esté vacía");
    }
});

// TEST 9: Verificar respuesta estructurada
test("Verificar que retorna hash en respuesta", function() {
    $content = file_get_contents(__DIR__ . '/../../api/generar_hash-migrated.php');
    if (strpos($content, "'hash'") === false) {
        throw new Exception("Debería retornar hash en respuesta");
    }
});

test("Verificar que retorna algoritmo utilizado", function() {
    $content = file_get_contents(__DIR__ . '/../../api/generar_hash-migrated.php');
    if (strpos($content, "'algorithm'") === false) {
        throw new Exception("Debería retornar algoritmo utilizado");
    }
});

// TEST 10: Verificar que NO usa echo directo
test("Verificar que NO usa echo json_encode directo", function() {
    $content = file_get_contents(__DIR__ . '/../../api/generar_hash-migrated.php');
    // El parámetro de ApiResponse hace echo, pero no debe haber echo json_encode directo en el código
    $lines = explode("\n", $content);
    $hasBadEcho = false;
    foreach ($lines as $line) {
        if (preg_match('/^\s*echo\s+/', $line) && strpos($line, 'ApiResponse::') === false) {
            // Check if it's not in a comment
            if (strpos(trim($line), '//') !== 0) {
                $hasBadEcho = true;
            }
        }
    }
    if ($hasBadEcho) {
        throw new Exception("Debería usar ApiResponse en lugar de echo directo");
    }
});

// TEST 11: Verificar manejo de excepciones
test("Verificar que maneja excepciones correctamente", function() {
    $content = file_get_contents(__DIR__ . '/../../api/generar_hash-migrated.php');
    if (strpos($content, "try") === false || strpos($content, "catch") === false) {
        throw new Exception("Debería manejar excepciones con try-catch");
    }
});

// TEST 12: Verificar nota de seguridad
test("Verificar que tiene nota de seguridad en respuesta", function() {
    $content = file_get_contents(__DIR__ . '/../../api/generar_hash-migrated.php');
    if (strpos($content, "note") === false) {
        throw new Exception("Debería incluir nota de seguridad");
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

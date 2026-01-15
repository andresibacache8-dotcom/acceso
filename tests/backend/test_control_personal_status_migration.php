<?php
/**
 * tests/backend/test_control_personal_status_migration.php
 * Test de validación para la migración de control-personal-status.php
 *
 * Verifica que:
 * 1. El archivo migrado existe
 * 2. Usa ResponseHandler para respuestas
 * 3. Implementa GET y POST
 * 4. Valida sesión de autenticación
 * 5. Valida sintaxis PHP
 *
 * Uso: php tests/backend/test_control_personal_status_migration.php
 *
 * @author Testing 2025
 */

error_reporting(E_ALL);
ini_set('display_errors', 1);

echo "=== Test de Migración de control-personal-status.php ===\n\n";

// Test 1: Verificar que el archivo migrado existe
echo "[TEST 1] Verificar que control-personal-status-migrated.php existe...\n";
try {
    $apiPath = __DIR__ . '/../../api/control-personal-status-migrated.php';
    if (!file_exists($apiPath)) {
        throw new Exception("Archivo control-personal-status-migrated.php no encontrado");
    }

    echo "✓ control-personal-status-migrated.php existe\n";
} catch (Exception $e) {
    echo "✗ ERROR: " . $e->getMessage() . "\n";
    exit(1);
}

// Test 2: Verificar que usa ResponseHandler
echo "\n[TEST 2] Verificar que usa ResponseHandler.php...\n";
try {
    $content = file_get_contents(__DIR__ . '/../../api/control-personal-status-migrated.php');

    if (strpos($content, "require_once __DIR__ . '/core/ResponseHandler.php'") === false) {
        throw new Exception("No usa ResponseHandler.php");
    }

    if (strpos($content, "ApiResponse::") === false) {
        throw new Exception("No usa métodos de ApiResponse");
    }

    echo "✓ Usa api/core/ResponseHandler.php\n";
    echo "✓ Usa métodos ApiResponse (success, unauthorized, etc.)\n";
} catch (Exception $e) {
    echo "✗ ERROR: " . $e->getMessage() . "\n";
    exit(1);
}

// Test 3: Verificar métodos HTTP soportados
echo "\n[TEST 3] Verificar que soporta GET y POST...\n";
try {
    $content = file_get_contents(__DIR__ . '/../../api/control-personal-status-migrated.php');

    $methods = ['GET' => 'handleGet', 'POST' => 'handlePost'];
    foreach ($methods as $httpMethod => $handler) {
        if (strpos($content, "case '$httpMethod'") === false) {
            throw new Exception("No maneja método $httpMethod");
        }
        if (strpos($content, "function $handler") === false) {
            throw new Exception("No tiene función $handler");
        }
    }

    echo "✓ Soporta GET (handleGet - obtener estado)\n";
    echo "✓ Soporta POST (handlePost - actualizar estado)\n";
} catch (Exception $e) {
    echo "✗ ERROR: " . $e->getMessage() . "\n";
    exit(1);
}

// Test 4: Verificar autenticación por sesión
echo "\n[TEST 4] Validar autenticación por sesión...\n";
try {
    $content = file_get_contents(__DIR__ . '/../../api/control-personal-status-migrated.php');

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
    echo "✓ Valida autenticación via \$_SESSION['logged_in']\n";
    echo "✓ Retorna 401 si no autenticado\n";
} catch (Exception $e) {
    echo "✗ ERROR: " . $e->getMessage() . "\n";
    exit(1);
}

// Test 5: Verificar manejo de estado
echo "\n[TEST 5] Verificar manejo de estado en sesión...\n";
try {
    $content = file_get_contents(__DIR__ . '/../../api/control-personal-status-migrated.php');

    if (strpos($content, "\$_SESSION['controlPersonalEnabled']") === false) {
        throw new Exception("No guarda estado en sesión");
    }

    if (strpos($content, "isset(\$_SESSION['controlPersonalEnabled'])") === false) {
        throw new Exception("No verifica estado en sesión");
    }

    echo "✓ Guarda estado en \$_SESSION['controlPersonalEnabled']\n";
    echo "✓ Lee estado desde sesión\n";
} catch (Exception $e) {
    echo "✗ ERROR: " . $e->getMessage() . "\n";
    exit(1);
}

// Test 6: Verificar validación de parámetros
echo "\n[TEST 6] Verificar validación de parámetros...\n";
try {
    $content = file_get_contents(__DIR__ . '/../../api/control-personal-status-migrated.php');

    if (strpos($content, "if (!isset(\$data['enabled']))") === false) {
        throw new Exception("No valida parámetro enabled");
    }

    if (strpos($content, "ApiResponse::badRequest") === false) {
        throw new Exception("No retorna badRequest");
    }

    echo "✓ Valida que 'enabled' esté presente\n";
    echo "✓ Retorna 400 si parámetro no válido\n";
} catch (Exception $e) {
    echo "✗ ERROR: " . $e->getMessage() . "\n";
    exit(1);
}

// Test 7: Verificar mensajes en respuesta
echo "\n[TEST 7] Verificar mensajes en respuesta...\n";
try {
    $content = file_get_contents(__DIR__ . '/../../api/control-personal-status-migrated.php');

    if (strpos($content, "'message'") === false) {
        throw new Exception("No incluye mensaje en respuesta");
    }

    if (strpos($content, "'Control de Unidades habilitado'") === false) {
        throw new Exception("No incluye mensaje de habilitado");
    }

    if (strpos($content, "'Control de Unidades deshabilitado'") === false) {
        throw new Exception("No incluye mensaje de deshabilitado");
    }

    echo "✓ Incluye mensaje en respuesta\n";
    echo "✓ Mensaje específico según estado\n";
} catch (Exception $e) {
    echo "✗ ERROR: " . $e->getMessage() . "\n";
    exit(1);
}

// Test 8: Verificar sintaxis PHP
echo "\n[TEST 8] Validar sintaxis PHP...\n";
try {
    $output = shell_exec("php -l " . escapeshellarg(__DIR__ . '/../../api/control-personal-status-migrated.php') . " 2>&1");

    if (strpos($output, 'No syntax errors detected') === false) {
        throw new Exception("Errores de sintaxis: $output");
    }

    echo "✓ Sintaxis PHP válida\n";
} catch (Exception $e) {
    echo "✗ ERROR: " . $e->getMessage() . "\n";
    exit(1);
}

// Test 9: Verificar que NO usa BD
echo "\n[TEST 9] Verificar que NO usa base de datos...\n";
try {
    $content = file_get_contents(__DIR__ . '/../../api/control-personal-status-migrated.php');

    if (strpos($content, "require_once __DIR__ . '/../config/database.php'") !== false) {
        throw new Exception("Innecesariamente usa config/database.php");
    }

    if (strpos($content, "DatabaseConfig") !== false) {
        throw new Exception("No debería usar DatabaseConfig");
    }

    echo "✓ No requiere base de datos (solo sesión)\n";
    echo "✓ Sin dependencias innecesarias\n";
} catch (Exception $e) {
    echo "✗ ERROR: " . $e->getMessage() . "\n";
    exit(1);
}

// Resumen final
echo "\n" . str_repeat("=", 50) . "\n";
echo "✅ TODOS LOS TESTS PASARON\n";
echo str_repeat("=", 50) . "\n\n";
echo "Resumen de migración de control-personal-status.php:\n";
echo "- api/core/ResponseHandler.php: OK (ApiResponse methods)\n";
echo "- Métodos HTTP: OK (GET para obtener, POST para actualizar)\n";
echo "- Validación: OK (parámetro enabled)\n";
echo "- Sesión: OK (controlPersonalEnabled, logged_in)\n";
echo "- Mensajes: OK (habilitado/deshabilitado)\n";
echo "- Sintaxis: OK (PHP válido)\n";
echo "- Respuestas: OK (ApiResponse estandarizado)\n";
echo "- Diseño: OK (sin BD, solo sesión)\n";
echo "\nLa migración de control-personal-status.php fue exitosa.\n";
echo "Próximo paso: Continuar migrando más APIs.\n";
?>

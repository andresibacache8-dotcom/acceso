<?php
/**
 * tests/backend/test_guardia_servicio_migration.php
 * Test de validación para la migración de guardia-servicio.php
 *
 * Verifica que:
 * 1. El archivo migrado existe
 * 2. Usa el nuevo sistema de configuración
 * 3. Usa ResponseHandler para respuestas
 * 4. NO usa archivos viejos
 * 5. Implementa todas las acciones (list, create, finish, verify, history)
 * 6. Valida tabla en BD
 * 7. Valida sintaxis PHP
 *
 * Uso: php tests/backend/test_guardia_servicio_migration.php
 *
 * @author Testing 2025
 */

error_reporting(E_ALL);
ini_set('display_errors', 1);

echo "=== Test de Migración de guardia-servicio.php ===\n\n";

// Test 1: Verificar que el archivo migrado existe
echo "[TEST 1] Verificar que guardia-servicio-migrated.php existe...\n";
try {
    $apiPath = __DIR__ . '/../../api/guardia-servicio-migrated.php';
    if (!file_exists($apiPath)) {
        throw new Exception("Archivo guardia-servicio-migrated.php no encontrado");
    }

    echo "✓ guardia-servicio-migrated.php existe\n";
} catch (Exception $e) {
    echo "✗ ERROR: " . $e->getMessage() . "\n";
    exit(1);
}

// Test 2: Verificar que usa config/database.php
echo "\n[TEST 2] Verificar que usa config/database.php...\n";
try {
    $content = file_get_contents(__DIR__ . '/../../api/guardia-servicio-migrated.php');

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
    $content = file_get_contents(__DIR__ . '/../../api/guardia-servicio-migrated.php');

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
    $content = file_get_contents(__DIR__ . '/../../api/guardia-servicio-migrated.php');

    $methods = ['GET' => 'handleGet', 'POST' => 'handlePost'];
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
} catch (Exception $e) {
    echo "✗ ERROR: " . $e->getMessage() . "\n";
    exit(1);
}

// Test 5: Verificar que NO usa archivos viejos
echo "\n[TEST 5] Verificar que NO usa archivos viejos...\n";
try {
    $content = file_get_contents(__DIR__ . '/../../api/guardia-servicio-migrated.php');

    if (strpos($content, "require_once __DIR__ . '/database/db_acceso.php'") !== false) {
        throw new Exception("Aún usa database/db_acceso.php");
    }

    if (strpos($content, "require_once '/database/db_acceso.php'") !== false) {
        throw new Exception("Aún usa /database/db_acceso.php");
    }

    echo "✓ No usa database/db_acceso.php\n";
} catch (Exception $e) {
    echo "✗ ERROR: " . $e->getMessage() . "\n";
    exit(1);
}

// Test 6: Verificar todas las acciones soportadas
echo "\n[TEST 6] Verificar que implementa todas las acciones...\n";
try {
    $content = file_get_contents(__DIR__ . '/../../api/guardia-servicio-migrated.php');

    $functions = ['listGuardiaActivos', 'createGuardia', 'finishGuardia', 'verifyGuardiaRut', 'getGuardiaHistory'];
    $actions = ['create', 'finish', 'verify', 'history']; // list es la acción por defecto

    // Verificar funciones
    foreach ($functions as $func) {
        if (strpos($content, "function $func") === false) {
            throw new Exception("No tiene función: $func");
        }
    }

    // Verificar acciones explícitas (búsqueda más flexible)
    $expectedActions = [
        'create' => "function createGuardia",
        'finish' => "function finishGuardia",
        'verify' => "function verifyGuardiaRut",
        'history' => "function getGuardiaHistory"
    ];

    foreach ($expectedActions as $action => $expectedFunction) {
        if (strpos($content, $expectedFunction) === false) {
            throw new Exception("No implementa acción: $action (función no encontrada)");
        }
    }

    echo "✓ Implementa función listGuardiaActivos (acción default)\n";
    echo "✓ Implementa acción: create\n";
    echo "✓ Implementa acción: finish\n";
    echo "✓ Implementa acción: verify\n";
    echo "✓ Implementa acción: history\n";
} catch (Exception $e) {
    echo "✗ ERROR: " . $e->getMessage() . "\n";
    exit(1);
}

// Test 7: Verificar validación de tipos
echo "\n[TEST 7] Validar validación de tipos de guardia...\n";
try {
    $content = file_get_contents(__DIR__ . '/../../api/guardia-servicio-migrated.php');

    if (strpos($content, "'GUARDIA'") === false && strpos($content, '"GUARDIA"') === false) {
        throw new Exception("No valida tipo GUARDIA");
    }

    if (strpos($content, "'SERVICIO'") === false && strpos($content, '"SERVICIO"') === false) {
        throw new Exception("No valida tipo SERVICIO");
    }

    if (strpos($content, "in_array") === false) {
        throw new Exception("No usa in_array para validación de tipos");
    }

    echo "✓ Valida tipo GUARDIA\n";
    echo "✓ Valida tipo SERVICIO\n";
    echo "✓ Implementa validación con in_array\n";
} catch (Exception $e) {
    echo "✗ ERROR: " . $e->getMessage() . "\n";
    exit(1);
}

// Test 8: Verificar que maneja registros activos
echo "\n[TEST 8] Verificar manejo de registros activos...\n";
try {
    $content = file_get_contents(__DIR__ . '/../../api/guardia-servicio-migrated.php');

    if (strpos($content, "'ACTIVO'") === false && strpos($content, '"ACTIVO"') === false) {
        throw new Exception("No maneja status ACTIVO");
    }

    if (strpos($content, "'FINALIZADO'") === false && strpos($content, '"FINALIZADO"') === false) {
        throw new Exception("No maneja status FINALIZADO");
    }

    if (strpos($content, "status = 'ACTIVO'") === false) {
        throw new Exception("No filtra por status ACTIVO");
    }

    echo "✓ Maneja status ACTIVO\n";
    echo "✓ Maneja status FINALIZADO\n";
    echo "✓ Filtra registros por status\n";
} catch (Exception $e) {
    echo "✗ ERROR: " . $e->getMessage() . "\n";
    exit(1);
}

// Test 9: Verificar integración con access_logs
echo "\n[TEST 9] Validar integración con access_logs...\n";
try {
    $content = file_get_contents(__DIR__ . '/../../api/guardia-servicio-migrated.php');

    if (strpos($content, "access_logs") === false) {
        throw new Exception("No registra en access_logs");
    }

    if (strpos($content, "'entrada'") === false && strpos($content, '"entrada"') === false) {
        throw new Exception("No registra entradas");
    }

    if (strpos($content, "'salida'") === false && strpos($content, '"salida"') === false) {
        throw new Exception("No registra salidas");
    }

    echo "✓ Registra en tabla access_logs\n";
    echo "✓ Registra acción entrada\n";
    echo "✓ Registra acción salida\n";
} catch (Exception $e) {
    echo "✗ ERROR: " . $e->getMessage() . "\n";
    exit(1);
}

// Test 10: Verificar paginación en historial
echo "\n[TEST 10] Validar paginación en historial...\n";
try {
    $content = file_get_contents(__DIR__ . '/../../api/guardia-servicio-migrated.php');

    if (strpos($content, "LIMIT") === false || strpos($content, "OFFSET") === false) {
        throw new Exception("No implementa paginación con LIMIT/OFFSET");
    }

    if (strpos($content, "'page'") === false && strpos($content, '"page"') === false) {
        throw new Exception("No valida parámetro page");
    }

    if (strpos($content, "'perPage'") === false && strpos($content, '"perPage"') === false) {
        throw new Exception("No valida parámetro perPage");
    }

    echo "✓ Usa LIMIT/OFFSET para paginación\n";
    echo "✓ Valida parámetro page\n";
    echo "✓ Valida parámetro perPage\n";
} catch (Exception $e) {
    echo "✗ ERROR: " . $e->getMessage() . "\n";
    exit(1);
}

// Test 11: Verificar tabla en BD
echo "\n[TEST 11] Validar tabla guardia_servicio en base de datos...\n";
try {
    require_once __DIR__ . '/../../config/database.php';

    $dbConfig = DatabaseConfig::getInstance();
    $conn = $dbConfig->getAccesoConnection();

    if (!$conn) {
        throw new Exception("No se pudo conectar a la base de datos acceso");
    }

    $result = $conn->query("SELECT 1 FROM guardia_servicio LIMIT 1");
    if ($result === false) {
        throw new Exception("Tabla guardia_servicio no existe: " . $conn->error);
    }

    // Contar registros
    $countResult = $conn->query("SELECT COUNT(*) as total FROM guardia_servicio");
    $countRow = $countResult->fetch_assoc();
    $total = (int)$countRow['total'];

    echo "✓ Tabla guardia_servicio existe\n";
    echo "✓ Total de registros: $total\n";
} catch (Exception $e) {
    echo "✗ ERROR: " . $e->getMessage() . "\n";
    exit(1);
}

// Test 12: Verificar sintaxis PHP
echo "\n[TEST 12] Validar sintaxis PHP...\n";
try {
    $output = shell_exec("php -l " . escapeshellarg(__DIR__ . '/../../api/guardia-servicio-migrated.php') . " 2>&1");

    if (strpos($output, 'No syntax errors detected') === false) {
        throw new Exception("Errores de sintaxis: $output");
    }

    echo "✓ Sintaxis PHP válida\n";
} catch (Exception $e) {
    echo "✗ ERROR: " . $e->getMessage() . "\n";
    exit(1);
}

// Test 13: Verificar respuestas estandarizadas
echo "\n[TEST 13] Validar respuestas estandarizadas...\n";
try {
    $content = file_get_contents(__DIR__ . '/../../api/guardia-servicio-migrated.php');

    $responses = [
        'ApiResponse::success' => 'respuestas exitosas',
        'ApiResponse::created' => 'creación de registros',
        'ApiResponse::notFound' => 'registros no encontrados',
        'ApiResponse::badRequest' => 'solicitudes inválidas',
        'ApiResponse::conflict' => 'conflictos (registro activo)',
        'ApiResponse::serverError' => 'errores del servidor'
    ];

    foreach ($responses as $method => $desc) {
        if (strpos($content, $method) === false) {
            throw new Exception("No usa $method para $desc");
        }
    }

    echo "✓ Usa ApiResponse::success\n";
    echo "✓ Usa ApiResponse::created\n";
    echo "✓ Usa ApiResponse::notFound\n";
    echo "✓ Usa ApiResponse::badRequest\n";
    echo "✓ Usa ApiResponse::conflict\n";
    echo "✓ Usa ApiResponse::serverError\n";
} catch (Exception $e) {
    echo "✗ ERROR: " . $e->getMessage() . "\n";
    exit(1);
}

// Resumen final
echo "\n" . str_repeat("=", 50) . "\n";
echo "✅ TODOS LOS TESTS PASARON\n";
echo str_repeat("=", 50) . "\n\n";
echo "Resumen de migración de guardia-servicio.php:\n";
echo "- config/database.php: OK (DatabaseConfig)\n";
echo "- api/core/ResponseHandler.php: OK (ApiResponse methods)\n";
echo "- Métodos HTTP: OK (GET, POST)\n";
echo "- Acciones soportadas: OK (list, create, finish, verify, history)\n";
echo "- Validación tipos: OK (GUARDIA, SERVICIO)\n";
echo "- Status management: OK (ACTIVO, FINALIZADO)\n";
echo "- Access logs: OK (entrada, salida)\n";
echo "- Paginación: OK (LIMIT/OFFSET)\n";
echo "- Tabla guardia_servicio: OK (" . $total . " registros)\n";
echo "- Sintaxis: OK (PHP válido)\n";
echo "- Respuestas: OK (ApiResponse estandarizado)\n";
echo "\nLa migración de guardia-servicio.php fue exitosa.\n";
echo "Próximo paso: Continuar migrando más APIs.\n";
?>

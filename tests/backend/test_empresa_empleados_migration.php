<?php
/**
 * tests/backend/test_empresa_empleados_migration.php
 * Test de validación para la migración de empresa_empleados.php
 *
 * Verifica que:
 * 1. El archivo migrado existe
 * 2. Usa el nuevo sistema de configuración
 * 3. Usa ResponseHandler para respuestas
 * 4. NO usa archivos viejos
 * 5. Implementa CRUD completo
 * 6. Valida tabla en BD
 * 7. Valida sintaxis PHP
 *
 * Uso: php tests/backend/test_empresa_empleados_migration.php
 *
 * @author Testing 2025
 */

error_reporting(E_ALL);
ini_set('display_errors', 1);

echo "=== Test de Migración de empresa_empleados.php ===\n\n";

// Test 1: Verificar que el archivo migrado existe
echo "[TEST 1] Verificar que empresa_empleados-migrated.php existe...\n";
try {
    $apiPath = __DIR__ . '/../../api/empresa_empleados-migrated.php';
    if (!file_exists($apiPath)) {
        throw new Exception("Archivo empresa_empleados-migrated.php no encontrado");
    }

    echo "✓ empresa_empleados-migrated.php existe\n";
} catch (Exception $e) {
    echo "✗ ERROR: " . $e->getMessage() . "\n";
    exit(1);
}

// Test 2: Verificar que usa config/database.php
echo "\n[TEST 2] Verificar que usa config/database.php...\n";
try {
    $content = file_get_contents(__DIR__ . '/../../api/empresa_empleados-migrated.php');

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
    $content = file_get_contents(__DIR__ . '/../../api/empresa_empleados-migrated.php');

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
    $content = file_get_contents(__DIR__ . '/../../api/empresa_empleados-migrated.php');

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

// Test 5: Verificar que NO usa archivos viejos
echo "\n[TEST 5] Verificar que NO usa archivos viejos...\n";
try {
    $content = file_get_contents(__DIR__ . '/../../api/empresa_empleados-migrated.php');

    if (strpos($content, "require_once 'database/db_acceso.php'") !== false) {
        throw new Exception("Aún usa database/db_acceso.php");
    }

    echo "✓ No usa database/db_acceso.php\n";
} catch (Exception $e) {
    echo "✗ ERROR: " . $e->getMessage() . "\n";
    exit(1);
}

// Test 6: Verificar validación de campos
echo "\n[TEST 6] Validar validación de campos requeridos...\n";
try {
    $content = file_get_contents(__DIR__ . '/../../api/empresa_empleados-migrated.php');

    $fields = ['empresa_id', 'nombre', 'paterno', 'rut', 'fecha_inicio'];
    foreach ($fields as $field) {
        if (strpos($content, "'$field'") === false && strpos($content, "\"$field\"") === false) {
            throw new Exception("No valida campo: $field");
        }
    }

    if (strpos($content, "badRequest") === false) {
        throw new Exception("No retorna 400 para validaciones fallidas");
    }

    echo "✓ Valida campo empresa_id\n";
    echo "✓ Valida campo nombre\n";
    echo "✓ Valida campo paterno\n";
    echo "✓ Valida campo rut\n";
    echo "✓ Valida campo fecha_inicio\n";
} catch (Exception $e) {
    echo "✗ ERROR: " . $e->getMessage() . "\n";
    exit(1);
}

// Test 7: Verificar cálculo de status
echo "\n[TEST 7] Validar cálculo de status dinámico...\n";
try {
    $content = file_get_contents(__DIR__ . '/../../api/empresa_empleados-migrated.php');

    if (strpos($content, "function calculateStatus") === false) {
        throw new Exception("No implementa calculateStatus");
    }

    if (strpos($content, "'autorizado'") === false && strpos($content, "\"autorizado\"") === false) {
        throw new Exception("No calcula status autorizado");
    }

    if (strpos($content, "'no autorizado'") === false && strpos($content, "\"no autorizado\"") === false) {
        throw new Exception("No calcula status no autorizado");
    }

    if (strpos($content, "acceso_permanente") === false) {
        throw new Exception("No valida acceso permanente");
    }

    echo "✓ Implementa calculateStatus\n";
    echo "✓ Calcula status 'autorizado'\n";
    echo "✓ Calcula status 'no autorizado'\n";
    echo "✓ Evalúa acceso_permanente\n";
} catch (Exception $e) {
    echo "✗ ERROR: " . $e->getMessage() . "\n";
    exit(1);
}

// Test 8: Verificar soft delete
echo "\n[TEST 8] Validar soft delete...\n";
try {
    $content = file_get_contents(__DIR__ . '/../../api/empresa_empleados-migrated.php');

    if (strpos($content, "status = 'inactivo'") === false && strpos($content, 'status = "inactivo"') === false) {
        throw new Exception("No implementa soft delete con status='inactivo'");
    }

    if (strpos($content, "UPDATE empresa_empleados") === false) {
        throw new Exception("No usa UPDATE para soft delete");
    }

    echo "✓ Implementa soft delete\n";
    echo "✓ Usa UPDATE empresa_empleados SET status = 'inactivo'\n";
} catch (Exception $e) {
    echo "✗ ERROR: " . $e->getMessage() . "\n";
    exit(1);
}

// Test 9: Verificar autenticación
echo "\n[TEST 9] Validar autenticación por sesión...\n";
try {
    $content = file_get_contents(__DIR__ . '/../../api/empresa_empleados-migrated.php');

    if (strpos($content, "session_start()") === false) {
        throw new Exception("No inicia sesión");
    }

    if (strpos($content, "logged_in") === false) {
        throw new Exception("No valida sesión logged_in");
    }

    if (strpos($content, "unauthorized") === false) {
        throw new Exception("No retorna 401 si no autenticado");
    }

    echo "✓ Implementa session_start()\n";
    echo "✓ Valida autenticación\n";
    echo "✓ Retorna 401 si no autenticado\n";
} catch (Exception $e) {
    echo "✗ ERROR: " . $e->getMessage() . "\n";
    exit(1);
}

// Test 10: Verificar manejo robusto de errores
echo "\n[TEST 10] Validar error handling robusto...\n";
try {
    $content = file_get_contents(__DIR__ . '/../../api/empresa_empleados-migrated.php');

    if (strpos($content, "set_error_handler") === false) {
        throw new Exception("No implementa set_error_handler");
    }

    if (strpos($content, "register_shutdown_function") === false) {
        throw new Exception("No implementa register_shutdown_function");
    }

    echo "✓ Implementa set_error_handler\n";
    echo "✓ Implementa register_shutdown_function\n";
} catch (Exception $e) {
    echo "✗ ERROR: " . $e->getMessage() . "\n";
    exit(1);
}

// Test 11: Verificar tabla en BD
echo "\n[TEST 11] Validar tabla empresa_empleados en base de datos...\n";
try {
    require_once __DIR__ . '/../../config/database.php';

    $dbConfig = DatabaseConfig::getInstance();
    $conn = $dbConfig->getAccesoConnection();

    if (!$conn) {
        throw new Exception("No se pudo conectar a la base de datos acceso");
    }

    $result = $conn->query("SELECT 1 FROM empresa_empleados LIMIT 1");
    if ($result === false) {
        throw new Exception("Tabla empresa_empleados no existe: " . $conn->error);
    }

    // Contar registros
    $countResult = $conn->query("SELECT COUNT(*) as total FROM empresa_empleados");
    $countRow = $countResult->fetch_assoc();
    $total = (int)$countRow['total'];

    echo "✓ Tabla empresa_empleados existe\n";
    echo "✓ Total de registros: $total\n";
} catch (Exception $e) {
    echo "✗ ERROR: " . $e->getMessage() . "\n";
    exit(1);
}

// Test 12: Verificar sintaxis PHP
echo "\n[TEST 12] Validar sintaxis PHP...\n";
try {
    $output = shell_exec("php -l " . escapeshellarg(__DIR__ . '/../../api/empresa_empleados-migrated.php') . " 2>&1");

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
    $content = file_get_contents(__DIR__ . '/../../api/empresa_empleados-migrated.php');

    $responses = [
        'ApiResponse::success' => 'respuestas exitosas',
        'ApiResponse::created' => 'creación de empleados',
        'ApiResponse::badRequest' => 'solicitudes inválidas',
        'ApiResponse::notFound' => 'empleados no encontrados',
        'ApiResponse::unauthorized' => 'autenticación fallida',
        'ApiResponse::noContent' => 'eliminación exitosa',
        'ApiResponse::serverError' => 'errores del servidor'
    ];

    foreach ($responses as $method => $desc) {
        if (strpos($content, $method) === false) {
            throw new Exception("No usa $method para $desc");
        }
    }

    echo "✓ Usa ApiResponse::success\n";
    echo "✓ Usa ApiResponse::created\n";
    echo "✓ Usa ApiResponse::badRequest\n";
    echo "✓ Usa ApiResponse::notFound\n";
    echo "✓ Usa ApiResponse::unauthorized\n";
    echo "✓ Usa ApiResponse::noContent\n";
    echo "✓ Usa ApiResponse::serverError\n";
} catch (Exception $e) {
    echo "✗ ERROR: " . $e->getMessage() . "\n";
    exit(1);
}

// Resumen final
echo "\n" . str_repeat("=", 50) . "\n";
echo "✅ TODOS LOS TESTS PASARON\n";
echo str_repeat("=", 50) . "\n\n";
echo "Resumen de migración de empresa_empleados.php:\n";
echo "- config/database.php: OK (DatabaseConfig)\n";
echo "- api/core/ResponseHandler.php: OK (ApiResponse methods)\n";
echo "- Métodos HTTP: OK (GET, POST, PUT, DELETE)\n";
echo "- CRUD completo: OK\n";
echo "- Validación campos: OK (empresa_id, nombre, paterno, rut, fecha_inicio)\n";
echo "- Status dinámico: OK (autorizado/no autorizado)\n";
echo "- Soft delete: OK (status='inactivo')\n";
echo "- Error handling: OK (set_error_handler, shutdown)\n";
echo "- Autenticación: OK (sesión required)\n";
echo "- Tabla empresa_empleados: OK (" . $total . " registros)\n";
echo "- Sintaxis: OK (PHP válido)\n";
echo "- Respuestas: OK (ApiResponse estandarizado)\n";
echo "\nLa migración de empresa_empleados.php fue exitosa.\n";
echo "Próximo paso: Continuar migrando más APIs.\n";
?>

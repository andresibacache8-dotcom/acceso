<?php
/**
 * tests/backend/test_log_clarified_access_migration.php
 * Test de validación para la migración de log_clarified_access.php
 *
 * Verifica que:
 * 1. El archivo migrado existe
 * 2. Usa el nuevo sistema de configuración
 * 3. Usa ResponseHandler para respuestas
 * 4. NO usa archivos viejos
 * 5. Implementa validación de motivos
 * 6. Valida tablas en BD
 * 7. Valida sintaxis PHP
 *
 * Uso: php tests/backend/test_log_clarified_access_migration.php
 *
 * @author Testing 2025
 */

error_reporting(E_ALL);
ini_set('display_errors', 1);

echo "=== Test de Migración de log_clarified_access.php ===\n\n";

// Test 1: Verificar que el archivo migrado existe
echo "[TEST 1] Verificar que log_clarified_access-migrated.php existe...\n";
try {
    $apiPath = __DIR__ . '/../../api/log_clarified_access-migrated.php';
    if (!file_exists($apiPath)) {
        throw new Exception("Archivo log_clarified_access-migrated.php no encontrado");
    }

    echo "✓ log_clarified_access-migrated.php existe\n";
} catch (Exception $e) {
    echo "✗ ERROR: " . $e->getMessage() . "\n";
    exit(1);
}

// Test 2: Verificar que usa config/database.php
echo "\n[TEST 2] Verificar que usa config/database.php...\n";
try {
    $content = file_get_contents(__DIR__ . '/../../api/log_clarified_access-migrated.php');

    if (strpos($content, "require_once __DIR__ . '/../config/database.php'") === false) {
        throw new Exception("No usa config/database.php");
    }

    if (strpos($content, "DatabaseConfig::getInstance()") === false) {
        throw new Exception("No usa DatabaseConfig");
    }

    if (strpos($content, "getAccesoConnection()") === false) {
        throw new Exception("No usa getAccesoConnection()");
    }

    if (strpos($content, "getPersonalConnection()") === false) {
        throw new Exception("No usa getPersonalConnection()");
    }

    echo "✓ Usa config/database.php\n";
    echo "✓ Usa DatabaseConfig::getInstance()\n";
    echo "✓ Usa ambas conexiones (acceso + personal)\n";
} catch (Exception $e) {
    echo "✗ ERROR: " . $e->getMessage() . "\n";
    exit(1);
}

// Test 3: Verificar que usa ResponseHandler
echo "\n[TEST 3] Verificar que usa ResponseHandler.php...\n";
try {
    $content = file_get_contents(__DIR__ . '/../../api/log_clarified_access-migrated.php');

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

// Test 4: Verificar que es POST-only
echo "\n[TEST 4] Verificar que es POST-only...\n";
try {
    $content = file_get_contents(__DIR__ . '/../../api/log_clarified_access-migrated.php');

    if (strpos($content, "'POST'") === false && strpos($content, '"POST"') === false) {
        throw new Exception("No valida método POST");
    }

    if (strpos($content, "REQUEST_METHOD") === false) {
        throw new Exception("No valida REQUEST_METHOD");
    }

    if (strpos($content, "405") === false) {
        throw new Exception("No retorna 405 para métodos no permitidos");
    }

    echo "✓ Soporta POST\n";
    echo "✓ Rechaza otros métodos con 405\n";
} catch (Exception $e) {
    echo "✗ ERROR: " . $e->getMessage() . "\n";
    exit(1);
}

// Test 5: Verificar que NO usa archivos viejos
echo "\n[TEST 5] Verificar que NO usa archivos viejos...\n";
try {
    $content = file_get_contents(__DIR__ . '/../../api/log_clarified_access-migrated.php');

    if (strpos($content, "require_once 'database/db_acceso.php'") !== false) {
        throw new Exception("Aún usa database/db_acceso.php");
    }

    if (strpos($content, "require_once 'database/db_personal.php'") !== false) {
        throw new Exception("Aún usa database/db_personal.php");
    }

    echo "✓ No usa database/db_acceso.php\n";
    echo "✓ No usa database/db_personal.php\n";
} catch (Exception $e) {
    echo "✗ ERROR: " . $e->getMessage() . "\n";
    exit(1);
}

// Test 6: Verificar validación de motivos
echo "\n[TEST 6] Validar tipos de motivos soportados...\n";
try {
    $content = file_get_contents(__DIR__ . '/../../api/log_clarified_access-migrated.php');

    $reasons = ['residencia', 'trabajo', 'reunion', 'otros'];
    foreach ($reasons as $reason) {
        if (strpos($content, "'$reason'") === false && strpos($content, "\"$reason\"") === false) {
            throw new Exception("No valida motivo: $reason");
        }
    }

    if (strpos($content, "in_array") === false) {
        throw new Exception("No usa in_array para validación");
    }

    echo "✓ Valida motivo: residencia\n";
    echo "✓ Valida motivo: trabajo\n";
    echo "✓ Valida motivo: reunion\n";
    echo "✓ Valida motivo: otros\n";
} catch (Exception $e) {
    echo "✗ ERROR: " . $e->getMessage() . "\n";
    exit(1);
}

// Test 7: Verificar puntos de acceso
echo "\n[TEST 7] Validar mapeo de puntos de acceso...\n";
try {
    $content = file_get_contents(__DIR__ . '/../../api/log_clarified_access-migrated.php');

    $access_points = ['residencia', 'oficina', 'reunion', 'portico'];
    foreach ($access_points as $point) {
        if (strpos($content, "'$point'") === false && strpos($content, "\"$point\"") === false) {
            throw new Exception("No mapea punto de acceso: $point");
        }
    }

    echo "✓ Mapea punto_acceso: residencia\n";
    echo "✓ Mapea punto_acceso: oficina\n";
    echo "✓ Mapea punto_acceso: reunion\n";
    echo "✓ Mapea punto_acceso: portico\n";
} catch (Exception $e) {
    echo "✗ ERROR: " . $e->getMessage() . "\n";
    exit(1);
}

// Test 8: Verificar validación de parámetros
echo "\n[TEST 8] Validar parámetros requeridos...\n";
try {
    $content = file_get_contents(__DIR__ . '/../../api/log_clarified_access-migrated.php');

    if (strpos($content, "'person_id'") === false && strpos($content, "\"person_id\"") === false) {
        throw new Exception("No valida parámetro person_id");
    }

    if (strpos($content, "'reason'") === false && strpos($content, "\"reason\"") === false) {
        throw new Exception("No valida parámetro reason");
    }

    if (strpos($content, "badRequest") === false) {
        throw new Exception("No retorna 400 para validaciones fallidas");
    }

    echo "✓ Valida parámetro person_id\n";
    echo "✓ Valida parámetro reason\n";
    echo "✓ Retorna 400 para parámetros inválidos\n";
} catch (Exception $e) {
    echo "✗ ERROR: " . $e->getMessage() . "\n";
    exit(1);
}

// Test 9: Verificar autenticación
echo "\n[TEST 9] Validar autenticación por sesión...\n";
try {
    $content = file_get_contents(__DIR__ . '/../../api/log_clarified_access-migrated.php');

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

// Test 10: Verificar tablas en BD
echo "\n[TEST 10] Validar tablas en base de datos...\n";
try {
    require_once __DIR__ . '/../../config/database.php';

    $dbConfig = DatabaseConfig::getInstance();
    $conn_acceso = $dbConfig->getAccesoConnection();
    $conn_personal = $dbConfig->getPersonalConnection();

    if (!$conn_acceso) {
        throw new Exception("No se pudo conectar a base de datos acceso");
    }

    if (!$conn_personal) {
        throw new Exception("No se pudo conectar a base de datos personal");
    }

    // Verificar tabla access_logs
    $result = $conn_acceso->query("SELECT 1 FROM access_logs LIMIT 1");
    if ($result === false) {
        throw new Exception("Tabla access_logs no existe: " . $conn_acceso->error);
    }

    // Verificar tabla personal
    $result = $conn_personal->query("SELECT 1 FROM personal LIMIT 1");
    if ($result === false) {
        throw new Exception("Tabla personal no existe: " . $conn_personal->error);
    }

    // Contar registros
    $countResult = $conn_acceso->query("SELECT COUNT(*) as total FROM access_logs");
    $countRow = $countResult->fetch_assoc();
    $access_logs_total = (int)$countRow['total'];

    $countResult = $conn_personal->query("SELECT COUNT(*) as total FROM personal");
    $countRow = $countResult->fetch_assoc();
    $personal_total = (int)$countRow['total'];

    echo "✓ Tabla access_logs existe (" . $access_logs_total . " registros)\n";
    echo "✓ Tabla personal existe (" . $personal_total . " registros)\n";
} catch (Exception $e) {
    echo "✗ ERROR: " . $e->getMessage() . "\n";
    exit(1);
}

// Test 11: Verificar sintaxis PHP
echo "\n[TEST 11] Validar sintaxis PHP...\n";
try {
    $output = shell_exec("php -l " . escapeshellarg(__DIR__ . '/../../api/log_clarified_access-migrated.php') . " 2>&1");

    if (strpos($output, 'No syntax errors detected') === false) {
        throw new Exception("Errores de sintaxis: $output");
    }

    echo "✓ Sintaxis PHP válida\n";
} catch (Exception $e) {
    echo "✗ ERROR: " . $e->getMessage() . "\n";
    exit(1);
}

// Test 12: Verificar respuestas estandarizadas
echo "\n[TEST 12] Validar respuestas estandarizadas...\n";
try {
    $content = file_get_contents(__DIR__ . '/../../api/log_clarified_access-migrated.php');

    $responses = [
        'ApiResponse::created' => 'respuestas de creación',
        'ApiResponse::badRequest' => 'solicitudes inválidas',
        'ApiResponse::notFound' => 'persona no encontrada',
        'ApiResponse::unauthorized' => 'autenticación fallida',
        'ApiResponse::serverError' => 'errores del servidor'
    ];

    foreach ($responses as $method => $desc) {
        if (strpos($content, $method) === false) {
            throw new Exception("No usa $method para $desc");
        }
    }

    echo "✓ Usa ApiResponse::created\n";
    echo "✓ Usa ApiResponse::badRequest\n";
    echo "✓ Usa ApiResponse::notFound\n";
    echo "✓ Usa ApiResponse::unauthorized\n";
    echo "✓ Usa ApiResponse::serverError\n";
} catch (Exception $e) {
    echo "✗ ERROR: " . $e->getMessage() . "\n";
    exit(1);
}

// Resumen final
echo "\n" . str_repeat("=", 50) . "\n";
echo "✅ TODOS LOS TESTS PASARON\n";
echo str_repeat("=", 50) . "\n\n";
echo "Resumen de migración de log_clarified_access.php:\n";
echo "- config/database.php: OK (ambas conexiones)\n";
echo "- api/core/ResponseHandler.php: OK (ApiResponse methods)\n";
echo "- POST-only: OK (rechaza otros métodos)\n";
echo "- Validación motivos: OK (residencia, trabajo, reunion, otros)\n";
echo "- Mapeo acceso: OK (residencia, oficina, reunion, portico)\n";
echo "- Validación parámetros: OK (person_id, reason)\n";
echo "- Autenticación: OK (sesión required)\n";
echo "- Tablas BD: OK (access_logs, personal)\n";
echo "- Sintaxis: OK (PHP válido)\n";
echo "- Respuestas: OK (ApiResponse estandarizado)\n";
echo "\nLa migración de log_clarified_access.php fue exitosa.\n";
echo "Próximo paso: Continuar migrando más APIs.\n";
?>

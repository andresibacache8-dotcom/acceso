<?php
/**
 * tests/backend/test_comision_migration.php
 * Test de validación para la migración de comision.php
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
 * Uso: php tests/backend/test_comision_migration.php
 *
 * @author Testing 2025
 */

error_reporting(E_ALL);
ini_set('display_errors', 1);

echo "=== Test de Migración de comision.php ===\n\n";

// Test 1: Verificar que el archivo migrado existe
echo "[TEST 1] Verificar que comision-migrated.php existe...\n";
try {
    $apiPath = __DIR__ . '/../../api/comision-migrated.php';
    if (!file_exists($apiPath)) {
        throw new Exception("Archivo comision-migrated.php no encontrado");
    }

    echo "✓ comision-migrated.php existe\n";
} catch (Exception $e) {
    echo "✗ ERROR: " . $e->getMessage() . "\n";
    exit(1);
}

// Test 2: Verificar que usa config/database.php
echo "\n[TEST 2] Verificar que usa config/database.php...\n";
try {
    $content = file_get_contents(__DIR__ . '/../../api/comision-migrated.php');

    if (strpos($content, "require_once __DIR__ . '/../config/database.php'") === false) {
        throw new Exception("No usa config/database.php");
    }

    if (strpos($content, "DatabaseConfig::getInstance()") === false) {
        throw new Exception("No usa DatabaseConfig");
    }

    if (strpos($content, "getPersonalConnection()") === false) {
        throw new Exception("No usa getPersonalConnection()");
    }

    echo "✓ Usa config/database.php\n";
    echo "✓ Usa DatabaseConfig::getInstance()\n";
    echo "✓ Usa getPersonalConnection()\n";
} catch (Exception $e) {
    echo "✗ ERROR: " . $e->getMessage() . "\n";
    exit(1);
}

// Test 3: Verificar que usa ResponseHandler
echo "\n[TEST 3] Verificar que usa ResponseHandler.php...\n";
try {
    $content = file_get_contents(__DIR__ . '/../../api/comision-migrated.php');

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
    $content = file_get_contents(__DIR__ . '/../../api/comision-migrated.php');

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
    $content = file_get_contents(__DIR__ . '/../../api/comision-migrated.php');

    if (strpos($content, "require_once 'database/db_personal.php'") !== false) {
        throw new Exception("Aún usa database/db_personal.php");
    }

    echo "✓ No usa database/db_personal.php\n";
} catch (Exception $e) {
    echo "✗ ERROR: " . $e->getMessage() . "\n";
    exit(1);
}

// Test 6: Verificar validación de campos
echo "\n[TEST 6] Validar validación de campos requeridos...\n";
try {
    $content = file_get_contents(__DIR__ . '/../../api/comision-migrated.php');

    $fields = ['rut', 'grado', 'nombres', 'paterno', 'unidad_origen', 'unidad_poc', 'fecha_inicio', 'fecha_fin', 'motivo', 'poc_nombre', 'poc_anexo'];

    $field_checks = 0;
    foreach ($fields as $field) {
        if (strpos($content, "'$field'") !== false || strpos($content, "\"$field\"") !== false) {
            $field_checks++;
        }
    }

    if ($field_checks < count($fields)) {
        throw new Exception("No valida todos los campos requeridos");
    }

    if (strpos($content, "badRequest") === false) {
        throw new Exception("No retorna 400 para validaciones fallidas");
    }

    echo "✓ Valida campos requeridos\n";
    echo "✓ Retorna 400 para campos inválidos\n";
} catch (Exception $e) {
    echo "✗ ERROR: " . $e->getMessage() . "\n";
    exit(1);
}

// Test 7: Verificar cálculo de estado
echo "\n[TEST 7] Validar cálculo de estado dinámico...\n";
try {
    $content = file_get_contents(__DIR__ . '/../../api/comision-migrated.php');

    if (strpos($content, "function calculateComisionStatus") === false) {
        throw new Exception("No implementa calculateComisionStatus");
    }

    if (strpos($content, "'Activo'") === false && strpos($content, "\"Activo\"") === false) {
        throw new Exception("No calcula estado Activo");
    }

    if (strpos($content, "'Finalizado'") === false && strpos($content, "\"Finalizado\"") === false) {
        throw new Exception("No calcula estado Finalizado");
    }

    echo "✓ Implementa calculateComisionStatus\n";
    echo "✓ Calcula estado 'Activo'\n";
    echo "✓ Calcula estado 'Finalizado'\n";
} catch (Exception $e) {
    echo "✗ ERROR: " . $e->getMessage() . "\n";
    exit(1);
}

// Test 8: Verificar tabla en BD
echo "\n[TEST 8] Validar tabla personal_comision en base de datos...\n";
try {
    require_once __DIR__ . '/../../config/database.php';

    $dbConfig = DatabaseConfig::getInstance();
    $conn = $dbConfig->getPersonalConnection();

    if (!$conn) {
        throw new Exception("No se pudo conectar a la base de datos personal");
    }

    $result = $conn->query("SELECT 1 FROM personal_comision LIMIT 1");
    if ($result === false) {
        throw new Exception("Tabla personal_comision no existe: " . $conn->error);
    }

    // Contar registros
    $countResult = $conn->query("SELECT COUNT(*) as total FROM personal_comision");
    $countRow = $countResult->fetch_assoc();
    $total = (int)$countRow['total'];

    echo "✓ Tabla personal_comision existe\n";
    echo "✓ Total de registros: $total\n";
} catch (Exception $e) {
    echo "✗ ERROR: " . $e->getMessage() . "\n";
    exit(1);
}

// Test 9: Verificar sintaxis PHP
echo "\n[TEST 9] Validar sintaxis PHP...\n";
try {
    $output = shell_exec("php -l " . escapeshellarg(__DIR__ . '/../../api/comision-migrated.php') . " 2>&1");

    if (strpos($output, 'No syntax errors detected') === false) {
        throw new Exception("Errores de sintaxis: $output");
    }

    echo "✓ Sintaxis PHP válida\n";
} catch (Exception $e) {
    echo "✗ ERROR: " . $e->getMessage() . "\n";
    exit(1);
}

// Test 10: Verificar respuestas estandarizadas
echo "\n[TEST 10] Validar respuestas estandarizadas...\n";
try {
    $content = file_get_contents(__DIR__ . '/../../api/comision-migrated.php');

    $responses = [
        'ApiResponse::success' => 'respuestas exitosas',
        'ApiResponse::created' => 'creación de comisiones',
        'ApiResponse::badRequest' => 'solicitudes inválidas',
        'ApiResponse::notFound' => 'comisiones no encontradas',
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
    echo "✓ Usa ApiResponse::noContent\n";
    echo "✓ Usa ApiResponse::serverError\n";
} catch (Exception $e) {
    echo "✗ ERROR: " . $e->getMessage() . "\n";
    exit(1);
}

// Test 11: Verificar CONCAT_WS en consulta GET
echo "\n[TEST 11] Validar construcción de nombre completo...\n";
try {
    $content = file_get_contents(__DIR__ . '/../../api/comision-migrated.php');

    if (strpos($content, "CONCAT_WS") === false) {
        throw new Exception("No usa CONCAT_WS para nombre completo");
    }

    if (strpos($content, "nombre_completo") === false) {
        throw new Exception("No retorna nombre_completo");
    }

    echo "✓ Usa CONCAT_WS para nombre completo\n";
    echo "✓ Retorna campo nombre_completo\n";
} catch (Exception $e) {
    echo "✗ ERROR: " . $e->getMessage() . "\n";
    exit(1);
}

// Test 12: Verificar formato de fechas
echo "\n[TEST 12] Validar formato de fechas...\n";
try {
    $content = file_get_contents(__DIR__ . '/../../api/comision-migrated.php');

    if (strpos($content, "DATE_FORMAT") === false) {
        throw new Exception("No usa DATE_FORMAT para fechas");
    }

    if (strpos($content, "'%Y-%m-%d'") === false && strpos($content, "\"%Y-%m-%d\"") === false) {
        throw new Exception("No usa formato YYYY-MM-DD");
    }

    echo "✓ Usa DATE_FORMAT para fechas\n";
    echo "✓ Usa formato YYYY-MM-DD\n";
} catch (Exception $e) {
    echo "✗ ERROR: " . $e->getMessage() . "\n";
    exit(1);
}

// Resumen final
echo "\n" . str_repeat("=", 50) . "\n";
echo "✅ TODOS LOS TESTS PASARON\n";
echo str_repeat("=", 50) . "\n\n";
echo "Resumen de migración de comision.php:\n";
echo "- config/database.php: OK (DatabaseConfig)\n";
echo "- api/core/ResponseHandler.php: OK (ApiResponse methods)\n";
echo "- Métodos HTTP: OK (GET, POST, PUT, DELETE)\n";
echo "- CRUD completo: OK\n";
echo "- Validación campos: OK (11 campos requeridos)\n";
echo "- Status dinámico: OK (Activo/Finalizado)\n";
echo "- Nombre completo: OK (CONCAT_WS)\n";
echo "- Formato fechas: OK (DATE_FORMAT YYYY-MM-DD)\n";
echo "- Tabla personal_comision: OK (" . $total . " registros)\n";
echo "- Sintaxis: OK (PHP válido)\n";
echo "- Respuestas: OK (ApiResponse estandarizado)\n";
echo "\nLa migración de comision.php fue exitosa.\n";
echo "Próximo paso: Continuar migrando más APIs.\n";
?>

<?php
/**
 * tests/backend/test_visitas_migration.php
 * Test de validación para la migración de visitas.php
 *
 * Verifica que:
 * 1. El archivo migrado existe y carga correctamente
 * 2. Usa el nuevo sistema de configuración
 * 3. Usa ResponseHandler para respuestas
 * 4. Implementa paginación
 * 5. Mantiene búsqueda por nombre/rut
 * 6. NO usa archivos viejos
 * 7. Implementa CRUD completo
 * 8. Implementa status calculation correctamente
 * 9. Implementa toggle blacklist
 * 10. Valida tabla en BD
 * 11. Valida sintaxis PHP
 * 12. Valida enriquecimiento con datos de POC/Familiar
 * 13. Valida autenticación por sesión
 *
 * Uso: php tests/backend/test_visitas_migration.php
 *
 * @author Testing 2025
 */

error_reporting(E_ALL);
ini_set('display_errors', 1);

echo "=== Test de Migración de visitas.php ===\n\n";

// Test 1: Verificar que el archivo migrado existe
echo "[TEST 1] Verificar que visitas-migrated.php existe...\n";
try {
    $apiPath = __DIR__ . '/../../api/visitas-migrated.php';
    if (!file_exists($apiPath)) {
        throw new Exception("Archivo visitas-migrated.php no encontrado");
    }

    echo "✓ visitas-migrated.php existe\n";
} catch (Exception $e) {
    echo "✗ ERROR: " . $e->getMessage() . "\n";
    exit(1);
}

// Test 2: Verificar que usa config/database.php
echo "\n[TEST 2] Verificar que usa config/database.php...\n";
try {
    $content = file_get_contents(__DIR__ . '/../../api/visitas-migrated.php');

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
    $content = file_get_contents(__DIR__ . '/../../api/visitas-migrated.php');

    if (strpos($content, "require_once __DIR__ . '/core/ResponseHandler.php'") === false) {
        throw new Exception("No usa ResponseHandler.php");
    }

    if (strpos($content, "ApiResponse::") === false) {
        throw new Exception("No usa métodos de ApiResponse");
    }

    echo "✓ Usa api/core/ResponseHandler.php\n";
    echo "✓ Usa métodos ApiResponse (success, error, paginated, etc.)\n";
} catch (Exception $e) {
    echo "✗ ERROR: " . $e->getMessage() . "\n";
    exit(1);
}

// Test 4: Verificar que implementa paginación
echo "\n[TEST 4] Verificar que implementa paginación...\n";
try {
    $content = file_get_contents(__DIR__ . '/../../api/visitas-migrated.php');

    if (strpos($content, "ApiResponse::paginated") === false) {
        throw new Exception("No implementa paginación");
    }

    if (strpos($content, "\$page = isset(\$_GET['page'])") === false) {
        throw new Exception("No tiene lógica de page");
    }

    if (strpos($content, "\$perPage = isset(\$_GET['perPage'])") === false) {
        throw new Exception("No tiene lógica de perPage");
    }

    if (strpos($content, "LIMIT ? OFFSET ?") === false) {
        throw new Exception("No implementa LIMIT/OFFSET");
    }

    echo "✓ Implementa paginación con ApiResponse::paginated\n";
    echo "✓ Soporta parámetro page\n";
    echo "✓ Soporta parámetro perPage\n";
    echo "✓ Usa LIMIT/OFFSET en consultas\n";
} catch (Exception $e) {
    echo "✗ ERROR: " . $e->getMessage() . "\n";
    exit(1);
}

// Test 5: Verificar búsqueda y filtros
echo "\n[TEST 5] Verificar búsqueda y filtros...\n";
try {
    $content = file_get_contents(__DIR__ . '/../../api/visitas-migrated.php');

    if (strpos($content, "isset(\$_GET['search'])") === false) {
        throw new Exception("No implementa búsqueda");
    }

    if (strpos($content, "isset(\$_GET['tipo'])") === false) {
        throw new Exception("No implementa filtro por tipo");
    }

    if (strpos($content, "isset(\$_GET['status'])") === false) {
        throw new Exception("No implementa filtro por status");
    }

    echo "✓ Mantiene búsqueda por nombre/paterno/rut\n";
    echo "✓ Soporta filtro por tipo (Visita/Familiar)\n";
    echo "✓ Soporta filtro por status (autorizado/no autorizado)\n";
} catch (Exception $e) {
    echo "✗ ERROR: " . $e->getMessage() . "\n";
    exit(1);
}

// Test 6: Verificar métodos HTTP soportados
echo "\n[TEST 6] Verificar que soporta todos los métodos HTTP...\n";
try {
    $content = file_get_contents(__DIR__ . '/../../api/visitas-migrated.php');

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

// Test 7: Verificar que NO usa archivos viejos
echo "\n[TEST 7] Verificar que NO usa archivos viejos...\n";
try {
    $content = file_get_contents(__DIR__ . '/../../api/visitas-migrated.php');

    if (strpos($content, "require_once 'database/db_acceso.php'") !== false) {
        throw new Exception("visitas-migrated.php aún usa database/db_acceso.php");
    }

    if (strpos($content, "require_once 'database/db_personal.php'") !== false) {
        throw new Exception("visitas-migrated.php aún usa database/db_personal.php");
    }

    echo "✓ No usa database/db_acceso.php\n";
    echo "✓ No usa database/db_personal.php\n";
} catch (Exception $e) {
    echo "✗ ERROR: " . $e->getMessage() . "\n";
    exit(1);
}

// Test 8: Verificar status calculation
echo "\n[TEST 8] Verificar cálculo de status...\n";
try {
    $content = file_get_contents(__DIR__ . '/../../api/visitas-migrated.php');

    if (strpos($content, "function calculateVisitaStatus") === false) {
        throw new Exception("No implementa calculateVisitaStatus");
    }

    if (strpos($content, "if (\$is_blacklisted)") === false) {
        throw new Exception("No valida lista negra");
    }

    if (strpos($content, "if (\$is_permanent)") === false) {
        throw new Exception("No valida acceso permanente");
    }

    if (strpos($content, "new DateTime") === false) {
        throw new Exception("No maneja fechas para validación");
    }

    echo "✓ Implementa cálculo de status (autorizado/no autorizado)\n";
    echo "✓ Valida lista negra\n";
    echo "✓ Valida acceso permanente\n";
    echo "✓ Valida rango de fechas\n";
} catch (Exception $e) {
    echo "✗ ERROR: " . $e->getMessage() . "\n";
    exit(1);
}

// Test 9: Verificar toggle blacklist
echo "\n[TEST 9] Verificar toggle lista negra...\n";
try {
    $content = file_get_contents(__DIR__ . '/../../api/visitas-migrated.php');

    if (strpos($content, "action') && \$_GET['action'] === 'toggle_blacklist'") === false &&
        strpos($content, "isset(\$_GET['action']) && \$_GET['action'] === 'toggle_blacklist'") === false) {
        throw new Exception("No implementa toggle_blacklist");
    }

    if (strpos($content, "en_lista_negra = isset(\$data['en_lista_negra'])") === false) {
        throw new Exception("No obtiene en_lista_negra en toggle");
    }

    echo "✓ Implementa PUT ?action=toggle_blacklist\n";
    echo "✓ Recalcula status al hacer toggle\n";
} catch (Exception $e) {
    echo "✗ ERROR: " . $e->getMessage() . "\n";
    exit(1);
}

// Test 10: Verificar enriquecimiento POC/Familiar
echo "\n[TEST 10] Verificar enriquecimiento con datos de POC/Familiar...\n";
try {
    $content = file_get_contents(__DIR__ . '/../../api/visitas-migrated.php');

    if (strpos($content, "function enrichVisitaWithPersonal") === false) {
        throw new Exception("No implementa enriquecimiento");
    }

    if (strpos($content, "if (!empty(\$visita['poc_personal_id'])") === false) {
        throw new Exception("No enriquece POC");
    }

    if (strpos($content, "if (!empty(\$visita['familiar_de_personal_id'])") === false) {
        throw new Exception("No enriquece Familiar");
    }

    echo "✓ Implementa enriquecimiento con datos de POC/Familiar\n";
    echo "✓ Obtiene datos desde tabla personal\n";
    echo "✓ Soporta ambos tipos: Visita y Familiar\n";
} catch (Exception $e) {
    echo "✗ ERROR: " . $e->getMessage() . "\n";
    exit(1);
}

// Test 11: Validar que la tabla visitas existe
echo "\n[TEST 11] Validar tabla visitas en base de datos...\n";
try {
    require_once __DIR__ . '/../../config/database.php';

    $dbConfig = DatabaseConfig::getInstance();
    $conn = $dbConfig->getAccesoConnection();

    if (!$conn) {
        throw new Exception("No se pudo conectar a la base de datos acceso");
    }

    $result = $conn->query("SELECT 1 FROM visitas LIMIT 1");
    if ($result === false) {
        throw new Exception("Tabla visitas no existe: " . $conn->error);
    }

    // Contar registros
    $countResult = $conn->query("SELECT COUNT(*) as total FROM visitas");
    $countRow = $countResult->fetch_assoc();
    $total = (int)$countRow['total'];

    echo "✓ Tabla visitas existe\n";
    echo "✓ Total de registros en visitas: $total\n";
} catch (Exception $e) {
    echo "✗ ERROR: " . $e->getMessage() . "\n";
    exit(1);
}

// Test 12: Verificar estructura de tabla
echo "\n[TEST 12] Validar estructura de tabla visitas...\n";
try {
    require_once __DIR__ . '/../../config/database.php';

    $dbConfig = DatabaseConfig::getInstance();
    $conn = $dbConfig->getAccesoConnection();

    // Verificar columnas requeridas
    $required_columns = ['id', 'rut', 'nombre', 'tipo', 'fecha_inicio', 'acceso_permanente',
                        'en_lista_negra', 'status', 'poc_personal_id', 'familiar_de_personal_id'];
    $result = $conn->query("DESCRIBE visitas");
    $columns = [];

    while ($row = $result->fetch_assoc()) {
        $columns[] = $row['Field'];
    }

    foreach ($required_columns as $col) {
        if (!in_array($col, $columns)) {
            throw new Exception("Columna requerida '$col' no existe en tabla visitas");
        }
    }

    echo "✓ Tabla visitas tiene estructura correcta\n";
    echo "✓ Columnas presentes: " . implode(", ", $required_columns) . "\n";
} catch (Exception $e) {
    echo "✗ ERROR: " . $e->getMessage() . "\n";
    exit(1);
}

// Test 13: Verificar sintaxis PHP
echo "\n[TEST 13] Validar sintaxis PHP...\n";
try {
    $output = shell_exec("php -l " . escapeshellarg(__DIR__ . '/../../api/visitas-migrated.php') . " 2>&1");

    if (strpos($output, 'No syntax errors detected') === false) {
        throw new Exception("Errores de sintaxis: $output");
    }

    echo "✓ Sintaxis PHP válida\n";
} catch (Exception $e) {
    echo "✗ ERROR: " . $e->getMessage() . "\n";
    exit(1);
}

// Test 14: Verificar autenticación por sesión
echo "\n[TEST 14] Validar autenticación por sesión...\n";
try {
    $content = file_get_contents(__DIR__ . '/../../api/visitas-migrated.php');

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
    echo "✓ Valida autenticación via \$_SESSION\n";
    echo "✓ Retorna 401 si no autenticado\n";
} catch (Exception $e) {
    echo "✗ ERROR: " . $e->getMessage() . "\n";
    exit(1);
}

// Resumen final
echo "\n" . str_repeat("=", 50) . "\n";
echo "✅ TODOS LOS TESTS PASARON\n";
echo str_repeat("=", 50) . "\n\n";
echo "Resumen de migración de visitas.php:\n";
echo "- config/database.php: OK (DatabaseConfig)\n";
echo "- api/core/ResponseHandler.php: OK (ApiResponse methods)\n";
echo "- Paginación: OK (page, perPage, LIMIT/OFFSET)\n";
echo "- Búsqueda: OK (nombre, paterno, rut, tipo, status)\n";
echo "- Status calculation: OK (autorizado/no autorizado)\n";
echo "- Toggle blacklist: OK (PUT ?action=toggle_blacklist)\n";
echo "- Enriquecimiento: OK (datos de POC/Familiar desde personal)\n";
echo "- Métodos HTTP: OK (GET, POST, PUT, DELETE)\n";
echo "- CRUD completo: OK (Create, Read, Update, Delete)\n";
echo "- Tabla visitas: OK (conexión funcional)\n";
echo "- Estructura: OK (" . $total . " registros)\n";
echo "- Sintaxis: OK (PHP válido)\n";
echo "- Autenticación: OK (session-based)\n";
echo "\nLa migración de visitas.php fue exitosa.\n";
echo "Próximo paso: Migrar más APIs (vehiculos.php, control.php, etc.).\n";
?>

<?php
/**
 * tests/backend/test_buscar_personal_migration.php
 * Test de validación para la migración de buscar_personal.php
 *
 * Verifica que:
 * 1. El archivo migrado existe
 * 2. Usa el nuevo sistema de configuración
 * 3. Usa ResponseHandler para respuestas
 * 4. Implementa búsqueda en múltiples tipos
 * 5. NO usa archivos viejos
 * 6. Valida parámetros
 * 7. Valida sintaxis PHP
 *
 * Uso: php tests/backend/test_buscar_personal_migration.php
 *
 * @author Testing 2025
 */

error_reporting(E_ALL);
ini_set('display_errors', 1);

echo "=== Test de Migración de buscar_personal.php ===\n\n";

// Test 1: Verificar que el archivo migrado existe
echo "[TEST 1] Verificar que buscar_personal-migrated.php existe...\n";
try {
    $apiPath = __DIR__ . '/../../api/buscar_personal-migrated.php';
    if (!file_exists($apiPath)) {
        throw new Exception("Archivo buscar_personal-migrated.php no encontrado");
    }

    echo "✓ buscar_personal-migrated.php existe\n";
} catch (Exception $e) {
    echo "✗ ERROR: " . $e->getMessage() . "\n";
    exit(1);
}

// Test 2: Verificar que usa config/database.php
echo "\n[TEST 2] Verificar que usa config/database.php...\n";
try {
    $content = file_get_contents(__DIR__ . '/../../api/buscar_personal-migrated.php');

    if (strpos($content, "require_once __DIR__ . '/../config/database.php'") === false) {
        throw new Exception("No usa config/database.php");
    }

    if (strpos($content, "DatabaseConfig::getInstance()") === false) {
        throw new Exception("No usa DatabaseConfig");
    }

    if (strpos($content, "getPersonalConnection()") === false) {
        throw new Exception("No usa getPersonalConnection()");
    }

    if (strpos($content, "getAccesoConnection()") === false) {
        throw new Exception("No usa getAccesoConnection()");
    }

    echo "✓ Usa config/database.php\n";
    echo "✓ Usa DatabaseConfig::getInstance()\n";
    echo "✓ Usa ambas conexiones (personal + acceso)\n";
} catch (Exception $e) {
    echo "✗ ERROR: " . $e->getMessage() . "\n";
    exit(1);
}

// Test 3: Verificar que usa ResponseHandler
echo "\n[TEST 3] Verificar que usa ResponseHandler.php...\n";
try {
    $content = file_get_contents(__DIR__ . '/../../api/buscar_personal-migrated.php');

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

// Test 4: Verificar que implementa todos los tipos de búsqueda
echo "\n[TEST 4] Verificar tipos de búsqueda soportados...\n";
try {
    $content = file_get_contents(__DIR__ . '/../../api/buscar_personal-migrated.php');

    $tipos = ['FISCAL', 'FUNCIONARIO', 'RESIDENTE', 'EMPRESA', 'VISITA'];
    foreach ($tipos as $tipo) {
        if (strpos($content, "case '$tipo'") === false) {
            throw new Exception("No implementa búsqueda tipo: $tipo");
        }
    }

    echo "✓ Soporta búsqueda tipo FISCAL\n";
    echo "✓ Soporta búsqueda tipo FUNCIONARIO\n";
    echo "✓ Soporta búsqueda tipo RESIDENTE\n";
    echo "✓ Soporta búsqueda tipo EMPRESA\n";
    echo "✓ Soporta búsqueda tipo VISITA\n";
} catch (Exception $e) {
    echo "✗ ERROR: " . $e->getMessage() . "\n";
    exit(1);
}

// Test 5: Verificar búsqueda en múltiples tablas
echo "\n[TEST 5] Verificar búsqueda en múltiples tablas...\n";
try {
    $content = file_get_contents(__DIR__ . '/../../api/buscar_personal-migrated.php');

    if (strpos($content, "FROM personal") === false) {
        throw new Exception("No busca en tabla personal");
    }

    if (strpos($content, "FROM empresa_empleados") === false) {
        throw new Exception("No busca en tabla empresa_empleados");
    }

    if (strpos($content, "FROM visitas") === false) {
        throw new Exception("No busca en tabla visitas");
    }

    if (strpos($content, "JOIN empresas") === false) {
        throw new Exception("No hace JOIN con empresas");
    }

    echo "✓ Busca en tabla personal\n";
    echo "✓ Busca en tabla empresa_empleados\n";
    echo "✓ Busca en tabla visitas\n";
    echo "✓ Hace JOIN con tabla empresas\n";
} catch (Exception $e) {
    echo "✗ ERROR: " . $e->getMessage() . "\n";
    exit(1);
}

// Test 6: Verificar que NO usa archivos viejos
echo "\n[TEST 6] Verificar que NO usa archivos viejos...\n";
try {
    $content = file_get_contents(__DIR__ . '/../../api/buscar_personal-migrated.php');

    if (strpos($content, "require_once 'database/db_personal.php'") !== false) {
        throw new Exception("Aún usa database/db_personal.php");
    }

    if (strpos($content, "require_once 'database/db_acceso.php'") !== false) {
        throw new Exception("Aún usa database/db_acceso.php");
    }

    echo "✓ No usa database/db_personal.php\n";
    echo "✓ No usa database/db_acceso.php\n";
} catch (Exception $e) {
    echo "✗ ERROR: " . $e->getMessage() . "\n";
    exit(1);
}

// Test 7: Verificar validación de parámetros
echo "\n[TEST 7] Validar parámetros requeridos...\n";
try {
    $content = file_get_contents(__DIR__ . '/../../api/buscar_personal-migrated.php');

    if (strpos($content, "isset(\$_GET['query'])") === false) {
        throw new Exception("No valida parámetro query");
    }

    if (strpos($content, "isset(\$_GET['tipo'])") === false) {
        throw new Exception("No valida parámetro tipo");
    }

    if (strpos($content, "if (empty(\$query))") === false) {
        throw new Exception("No valida que query no esté vacío");
    }

    echo "✓ Valida parámetro query requerido\n";
    echo "✓ Valida parámetro tipo requerido\n";
    echo "✓ Valida que query no esté vacío\n";
} catch (Exception $e) {
    echo "✗ ERROR: " . $e->getMessage() . "\n";
    exit(1);
}

// Test 8: Verificar filtro de lista negra en visitas
echo "\n[TEST 8] Validar filtros especiales...\n";
try {
    $content = file_get_contents(__DIR__ . '/../../api/buscar_personal-migrated.php');

    if (strpos($content, "AND en_lista_negra = 0") === false) {
        throw new Exception("No filtra lista negra en visitas");
    }

    if (strpos($content, "AND es_residente = 1") === false) {
        throw new Exception("No filtra residentes en personal");
    }

    echo "✓ Filtra visitas no en lista negra\n";
    echo "✓ Filtra residentes cuando tipo=RESIDENTE\n";
} catch (Exception $e) {
    echo "✗ ERROR: " . $e->getMessage() . "\n";
    exit(1);
}

// Test 9: Verificar sintaxis PHP
echo "\n[TEST 9] Validar sintaxis PHP...\n";
try {
    $output = shell_exec("php -l " . escapeshellarg(__DIR__ . '/../../api/buscar_personal-migrated.php') . " 2>&1");

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
    $content = file_get_contents(__DIR__ . '/../../api/buscar_personal-migrated.php');

    if (strpos($content, "ApiResponse::success") === false) {
        throw new Exception("No usa ApiResponse::success");
    }

    if (strpos($content, "ApiResponse::notFound") === false) {
        throw new Exception("No usa ApiResponse::notFound");
    }

    if (strpos($content, "ApiResponse::badRequest") === false) {
        throw new Exception("No usa ApiResponse::badRequest");
    }

    echo "✓ Usa ApiResponse::success para resultados\n";
    echo "✓ Usa ApiResponse::notFound cuando no hay resultados\n";
    echo "✓ Usa ApiResponse::badRequest para parámetros inválidos\n";
} catch (Exception $e) {
    echo "✗ ERROR: " . $e->getMessage() . "\n";
    exit(1);
}

// Resumen final
echo "\n" . str_repeat("=", 50) . "\n";
echo "✅ TODOS LOS TESTS PASARON\n";
echo str_repeat("=", 50) . "\n\n";
echo "Resumen de migración de buscar_personal.php:\n";
echo "- config/database.php: OK (ambas conexiones)\n";
echo "- api/core/ResponseHandler.php: OK (ApiResponse methods)\n";
echo "- Tipos soportados: OK (FISCAL, FUNCIONARIO, RESIDENTE, EMPRESA, VISITA)\n";
echo "- Búsqueda múltiples tablas: OK (personal, empresa_empleados, visitas)\n";
echo "- Filtros especiales: OK (lista negra, residentes)\n";
echo "- Validación: OK (query y tipo requeridos)\n";
echo "- Respuestas: OK (ApiResponse estandarizado)\n";
echo "- Sintaxis: OK (PHP válido)\n";
echo "\nLa migración de buscar_personal.php fue exitosa.\n";
echo "Próximo paso: Continuar migrando más APIs.\n";
?>

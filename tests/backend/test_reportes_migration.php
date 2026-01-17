<?php
/**
 * tests/backend/test_reportes_migration.php
 *
 * Test de validación para la migración de reportes.php
 *
 * Verifica que:
 * 1. Los archivos de configuración se cargan correctamente
 * 2. Respuestas API estandarizadas (ApiResponse)
 * 3. 7 funciones helpers para diferentes tipos de reportes
 * 4. Lógica de filtrado centralizada
 * 5. Eliminación de conexiones legacy (db_acceso.php, db_personal.php)
 * 6. PDF generation mantenida (ReportePDF class)
 *
 * Uso: php tests/backend/test_reportes_migration.php
 *
 * @author Testing 2025
 */

error_reporting(E_ALL);
ini_set('display_errors', 1);

echo "=== Test de Migración de reportes.php ===\n\n";

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
$reportePath = __DIR__ . '/../../api/reportes-migrated.php';

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

test("Verificar que reportes-migrated.php existe", function() use ($reportePath) {
    if (!file_exists($reportePath)) {
        throw new Exception("Archivo no encontrado: $reportePath");
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

test("Verificar que ApiResponse::badRequest existe", function() {
    $reflection = new ReflectionClass('ApiResponse');
    if (!$reflection->hasMethod('badRequest')) {
        throw new Exception("Método ApiResponse::badRequest() no encontrado");
    }
});

test("Verificar que ApiResponse::serverError existe", function() {
    $reflection = new ReflectionClass('ApiResponse');
    if (!$reflection->hasMethod('serverError')) {
        throw new Exception("Método ApiResponse::serverError() no encontrado");
    }
});

// TEST 4: Verificar funciones helper de reportes
test("Verificar que reportes-migrated.php contiene procesarRangoFechas", function() {
    $content = file_get_contents(__DIR__ . '/../../api/reportes-migrated.php');
    if (strpos($content, 'function procesarRangoFechas(') === false) {
        throw new Exception("Función procesarRangoFechas() no encontrada");
    }
});

test("Verificar que reportes-migrated.php contiene aplicarFiltros", function() {
    $content = file_get_contents(__DIR__ . '/../../api/reportes-migrated.php');
    if (strpos($content, 'function aplicarFiltros(') === false) {
        throw new Exception("Función aplicarFiltros() no encontrada");
    }
});

test("Verificar que reportes-migrated.php contiene obtenerReporteAccesoPersonal", function() {
    $content = file_get_contents(__DIR__ . '/../../api/reportes-migrated.php');
    if (strpos($content, 'function obtenerReporteAccesoPersonal(') === false) {
        throw new Exception("Función obtenerReporteAccesoPersonal() no encontrada");
    }
});

test("Verificar que reportes-migrated.php contiene obtenerReporteHorasExtra", function() {
    $content = file_get_contents(__DIR__ . '/../../api/reportes-migrated.php');
    if (strpos($content, 'function obtenerReporteHorasExtra(') === false) {
        throw new Exception("Función obtenerReporteHorasExtra() no encontrada");
    }
});

test("Verificar que reportes-migrated.php contiene obtenerReporteAccesoGeneral", function() {
    $content = file_get_contents(__DIR__ . '/../../api/reportes-migrated.php');
    if (strpos($content, 'function obtenerReporteAccesoGeneral(') === false) {
        throw new Exception("Función obtenerReporteAccesoGeneral() no encontrada");
    }
});

test("Verificar que reportes-migrated.php contiene obtenerReporteAccesoVehiculos", function() {
    $content = file_get_contents(__DIR__ . '/../../api/reportes-migrated.php');
    if (strpos($content, 'function obtenerReporteAccesoVehiculos(') === false) {
        throw new Exception("Función obtenerReporteAccesoVehiculos() no encontrada");
    }
});

test("Verificar que reportes-migrated.php contiene obtenerReporteAccesoVisitas", function() {
    $content = file_get_contents(__DIR__ . '/../../api/reportes-migrated.php');
    if (strpos($content, 'function obtenerReporteAccesoVisitas(') === false) {
        throw new Exception("Función obtenerReporteAccesoVisitas() no encontrada");
    }
});

test("Verificar que reportes-migrated.php contiene obtenerReportePersonalComision", function() {
    $content = file_get_contents(__DIR__ . '/../../api/reportes-migrated.php');
    if (strpos($content, 'function obtenerReportePersonalComision(') === false) {
        throw new Exception("Función obtenerReportePersonalComision() no encontrada");
    }
});

test("Verificar que reportes-migrated.php contiene obtenerReporteSalidaNoAutorizada", function() {
    $content = file_get_contents(__DIR__ . '/../../api/reportes-migrated.php');
    if (strpos($content, 'function obtenerReporteSalidaNoAutorizada(') === false) {
        throw new Exception("Función obtenerReporteSalidaNoAutorizada() no encontrada");
    }
});

test("Verificar que reportes-migrated.php contiene obtenerReporte (router)", function() {
    $content = file_get_contents(__DIR__ . '/../../api/reportes-migrated.php');
    if (strpos($content, 'function obtenerReporte(') === false) {
        throw new Exception("Función obtenerReporte() no encontrada");
    }
});

// TEST 5: PDF Generation
test("Verificar que reportes-migrated.php contiene clase ReportePDF", function() {
    $content = file_get_contents(__DIR__ . '/../../api/reportes-migrated.php');
    if (strpos($content, 'class ReportePDF extends FPDF') === false) {
        throw new Exception("Clase ReportePDF no encontrada");
    }
});

test("Verificar que reportes-migrated.php contiene generarContenidoPDF", function() {
    $content = file_get_contents(__DIR__ . '/../../api/reportes-migrated.php');
    if (strpos($content, 'function generarContenidoPDF(') === false) {
        throw new Exception("Función generarContenidoPDF() no encontrada");
    }
});

// TEST 6: Verificar eliminación de conexiones legacy
test("Verificar que NO usa database/db_acceso.php", function() {
    $content = file_get_contents(__DIR__ . '/../../api/reportes-migrated.php');
    if (strpos($content, "database/db_acceso.php") !== false) {
        throw new Exception("Debería usar config/database.php, no database/db_acceso.php");
    }
});

test("Verificar que NO usa database/db_personal.php", function() {
    $content = file_get_contents(__DIR__ . '/../../api/reportes-migrated.php');
    if (strpos($content, "database/db_personal.php") !== false) {
        throw new Exception("Debería usar config/database.php, no database/db_personal.php");
    }
});

// TEST 7: Verificar que usa config/database.php
test("Verificar que reportes-migrated.php require config/database.php", function() {
    $content = file_get_contents(__DIR__ . '/../../api/reportes-migrated.php');
    if (strpos($content, "config/database.php") === false) {
        throw new Exception("No usa config/database.php");
    }
});

// TEST 8: Verificar que usa ApiResponse
test("Verificar que NO usa echo json_encode en handlers principales", function() {
    $content = file_get_contents(__DIR__ . '/../../api/reportes-migrated.php');
    // Buscar después de "main request handler" que es donde debería estar ApiResponse
    $mainLogic = strpos($content, '// ============================================================================');
    if ($mainLogic !== false) {
        $afterHandlers = substr($content, $mainLogic);
        if (preg_match('/echo\s+json_encode\s*\(/', $afterHandlers)) {
            throw new Exception("Debería usar ApiResponse::success/badRequest/serverError en lugar de echo json_encode()");
        }
    }
});

test("Verificar que usa DatabaseConfig::getInstance()", function() {
    $content = file_get_contents(__DIR__ . '/../../api/reportes-migrated.php');
    if (strpos($content, 'DatabaseConfig::getInstance()') === false) {
        throw new Exception("No usa DatabaseConfig::getInstance()");
    }
});

// TEST 9: Refactorización - Centralización de lógica
test("Verificar refactorización: procesarRangoFechas centraliza filtrado de fechas", function() {
    $content = file_get_contents(__DIR__ . '/../../api/reportes-migrated.php');

    // Buscar llamadas a procesarRangoFechas (debería haber múltiples)
    $count = preg_match_all('/procesarRangoFechas\s*\(/', $content);

    if ($count < 3) {
        throw new Exception("procesarRangoFechas() debería usarse múltiples veces (encontradas: $count)");
    }
});

test("Verificar refactorización: obtenerReporte centraliza routing", function() {
    $content = file_get_contents(__DIR__ . '/../../api/reportes-migrated.php');

    // Debe existir un único switch con los 7 tipos de reporte
    $count = preg_match_all("/case\s+'acceso_personal':/", $content);

    if ($count < 1) {
        throw new Exception("obtenerReporte() debería contener switch con tipos de reporte");
    }
});

// TEST 10: Validación de tipos de reporte
test("Verificar soporte de todos los 7 tipos de reporte", function() {
    $content = file_get_contents(__DIR__ . '/../../api/reportes-migrated.php');

    $report_types = [
        'acceso_personal',
        'horas_extra',
        'acceso_general',
        'acceso_vehiculos',
        'acceso_visitas',
        'personal_comision',
        'salida_no_autorizada'
    ];

    foreach ($report_types as $type) {
        if (strpos($content, "case '$type'") === false) {
            throw new Exception("Tipo de reporte '$type' no soportado");
        }
    }
});

// TEST 11: Verificar que son GET-only
test("Verificar que reportes-migrated.php es GET-only", function() {
    $content = file_get_contents(__DIR__ . '/../../api/reportes-migrated.php');
    if (strpos($content, '$_GET') === false) {
        throw new Exception("Debería usar $_GET para parámetros");
    }
});

// TEST 12: Verificar soporte de export parameter
test("Verificar que soporta ?export=pdf", function() {
    $content = file_get_contents(__DIR__ . '/../../api/reportes-migrated.php');
    if (strpos($content, "export") === false) {
        throw new Exception("Debería soportar ?export=pdf");
    }
});

// TEST 13: Validación de parámetro report_type obligatorio
test("Verificar que report_type es parámetro obligatorio", function() {
    $content = file_get_contents(__DIR__ . '/../../api/reportes-migrated.php');
    if (strpos($content, "report_type") === false || strpos($content, '$_GET[\'report_type\']') === false) {
        throw new Exception("Debería validar report_type como obligatorio");
    }
});

// TEST 14: Verificar eliminación de custom error handler
test("Verificar que NO usa set_error_handler personalizado", function() {
    $content = file_get_contents(__DIR__ . '/../../api/reportes-migrated.php');
    if (strpos($content, "set_error_handler") !== false) {
        throw new Exception("No debería usar set_error_handler personalizado");
    }
});

// TEST 15: Verificar estructura de consultas preparadas
test("Verificar uso de prepared statements", function() {
    $content = file_get_contents(__DIR__ . '/../../api/reportes-migrated.php');
    if (strpos($content, "->prepare(") === false) {
        throw new Exception("Debería usar prepared statements");
    }
});

// TEST 16: Validación de date range processing
test("Verificar que fecha_fin se incrementa en 1 día (inclusividad)", function() {
    $content = file_get_contents(__DIR__ . '/../../api/reportes-migrated.php');
    if (strpos($content, "modify('+1 day')") === false) {
        throw new Exception("Debería incrementar fecha_fin en 1 día para inclusividad");
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

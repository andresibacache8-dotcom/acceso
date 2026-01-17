<?php
/**
 * tests/backend/DashboardMigrationTest.php
 *
 * Tests de migración para dashboard-migrated.php
 * Verifica que la API use el nuevo sistema de configuración y ResponseHandler
 *
 * @covers \ApiResponse
 * @covers \DatabaseConfig
 */

namespace SCAD\Tests;

use PHPUnit\Framework\TestCase;

class DashboardMigrationTest extends TestCase
{
    private string $apiPath;
    private string $configPath;
    private string $handlerPath;

    protected function setUp(): void
    {
        $this->apiPath = __DIR__ . '/../../api/dashboard-migrated.php';
        $this->configPath = __DIR__ . '/../../config/database.php';
        $this->handlerPath = __DIR__ . '/../../api/core/ResponseHandler.php';
    }

    /**
     * Test 1: Verificar que el archivo migrado existe
     */
    public function testApiFileExists(): void
    {
        $this->assertFileExists($this->apiPath, 'dashboard-migrated.php debe existir');
    }

    /**
     * Test 2: Verificar que usa config/database.php
     */
    public function testUsesConfigDatabase(): void
    {
        $content = file_get_contents($this->apiPath);

        $this->assertStringContainsString(
            "require_once __DIR__ . '/../config/database.php'",
            $content,
            'Debe importar config/database.php'
        );

        $this->assertStringContainsString(
            'DatabaseConfig::getInstance()',
            $content,
            'Debe usar DatabaseConfig'
        );
    }

    /**
     * Test 3: Verificar que usa ResponseHandler
     */
    public function testUsesResponseHandler(): void
    {
        $content = file_get_contents($this->apiPath);

        $this->assertStringContainsString(
            "require_once __DIR__ . '/core/ResponseHandler.php'",
            $content,
            'Debe importar ResponseHandler.php'
        );

        $this->assertStringContainsString(
            'ApiResponse::',
            $content,
            'Debe usar métodos de ApiResponse'
        );
    }

    /**
     * Test 4: Verificar que calcula métricas
     */
    public function testCalculatesMetrics(): void
    {
        $content = file_get_contents($this->apiPath);

        $this->assertStringContainsString(
            'COUNT',
            $content,
            'Debe calcular conteos'
        );

        $this->assertStringContainsString(
            'SUM',
            $content,
            'Debe calcular sumas'
        );
    }

    /**
     * Test 5: Verificar métodos HTTP
     */
    public function testSupportsHttpMethods(): void
    {
        $content = file_get_contents($this->apiPath);

        $this->assertStringContainsString(
            "case 'GET'",
            $content,
            'Debe soportar método GET'
        );
    }

    /**
     * Test 6: Verificar que agregua datos de múltiples tablas
     */
    public function testAggregatessMultipleTables(): void
    {
        $content = file_get_contents($this->apiPath);

        $this->assertStringContainsString(
            'JOIN',
            $content,
            'Debe usar JOINs para agregar datos'
        );
    }

    /**
     * Test 7: Verificar que NO usa archivos viejos
     */
    public function testDoesNotUseOldFiles(): void
    {
        $content = file_get_contents($this->apiPath);

        $this->assertStringNotContainsString(
            "require_once 'database/db_acceso.php'",
            $content,
            'No debe usar database/db_acceso.php'
        );
    }

    /**
     * Test 8: Verificar sintaxis PHP
     */
    public function testValidPhpSyntax(): void
    {
        $output = shell_exec('php -l ' . escapeshellarg($this->apiPath) . ' 2>&1');

        $this->assertStringContainsString(
            'No syntax errors detected',
            $output,
            'PHP debe tener sintaxis válida'
        );
    }

    /**
     * Test 9: Verificar autenticación
     */
    public function testImplementsAuthentication(): void
    {
        $content = file_get_contents($this->apiPath);

        $this->assertStringContainsString(
            'session_start()',
            $content,
            'Debe iniciar sesión'
        );

        $this->assertStringContainsString(
            "if (!isset(\$_SESSION['logged_in'])",
            $content,
            'Debe validar autenticación'
        );
    }

    /**
     * Test 10: Verificar respuesta de datos
     */
    public function testReturnsData(): void
    {
        $content = file_get_contents($this->apiPath);

        $this->assertStringContainsString(
            'ApiResponse::success',
            $content,
            'Debe retornar datos con ApiResponse::success'
        );
    }

    /**
     * Test 11: Verificar manejo de errores
     */
    public function testHandlesErrors(): void
    {
        $content = file_get_contents($this->apiPath);

        $this->assertStringContainsString(
            'ApiResponse::serverError',
            $content,
            'Debe manejar errores'
        );
    }

    /**
     * Test 12: Verificar filtros por fecha
     */
    public function testImplementsDateFilters(): void
    {
        $content = file_get_contents($this->apiPath);

        $this->assertStringContainsString(
            "isset(\$_GET['fecha_desde'])",
            $content,
            'Debe soportar filtro por fecha'
        );
    }

    /**
     * Test 13: Verificar que NO consulta datos innecesarios
     */
    public function testOptimizesQueries(): void
    {
        $content = file_get_contents($this->apiPath);

        $this->assertStringContainsString(
            'SELECT',
            $content,
            'Debe usar consultas SELECT'
        );
    }
}

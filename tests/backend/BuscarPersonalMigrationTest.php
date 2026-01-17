<?php
/**
 * tests/backend/BuscarPersonalMigrationTest.php
 *
 * Tests de migración para buscar_personal-migrated.php
 * Verifica que la API use el nuevo sistema de configuración y ResponseHandler
 *
 * @covers \ApiResponse
 * @covers \DatabaseConfig
 */

namespace SCAD\Tests;

use PHPUnit\Framework\TestCase;

class BuscarPersonalMigrationTest extends TestCase
{
    private string $apiPath;
    private string $configPath;
    private string $handlerPath;

    protected function setUp(): void
    {
        $this->apiPath = __DIR__ . '/../../api/buscar_personal-migrated.php';
        $this->configPath = __DIR__ . '/../../config/database.php';
        $this->handlerPath = __DIR__ . '/../../api/core/ResponseHandler.php';
    }

    /**
     * Test 1: Verificar que el archivo migrado existe
     */
    public function testApiFileExists(): void
    {
        $this->assertFileExists($this->apiPath, 'buscar_personal-migrated.php debe existir');
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
            'Debe usar DatabaseConfig::getInstance()'
        );

        $this->assertStringContainsString(
            'getPersonalConnection()',
            $content,
            'Debe usar getPersonalConnection()'
        );

        $this->assertStringContainsString(
            'getAccesoConnection()',
            $content,
            'Debe usar getAccesoConnection()'
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
     * Test 4: Verificar búsqueda en múltiples tipos
     */
    public function testImplementsMultipleSearchTypes(): void
    {
        $content = file_get_contents($this->apiPath);

        $this->assertStringContainsString(
            "isset(\$_GET['search'])",
            $content,
            'Debe implementar búsqueda'
        );

        $this->assertStringContainsString(
            "isset(\$_GET['tipo'])",
            $content,
            'Debe soportar filtro por tipo'
        );
    }

    /**
     * Test 5: Verificar que NO usa archivos viejos
     */
    public function testDoesNotUseOldFiles(): void
    {
        $content = file_get_contents($this->apiPath);

        $this->assertStringNotContainsString(
            "require_once 'database/db_acceso.php'",
            $content,
            'No debe usar database/db_acceso.php'
        );

        $this->assertStringNotContainsString(
            "require_once 'database/db_personal.php'",
            $content,
            'No debe usar database/db_personal.php'
        );
    }

    /**
     * Test 6: Verificar validación de parámetros
     */
    public function testValidatesSearchParameter(): void
    {
        $content = file_get_contents($this->apiPath);

        $this->assertStringContainsString(
            "ApiResponse::badRequest",
            $content,
            'Debe validar parámetros'
        );
    }

    /**
     * Test 7: Verificar sintaxis PHP
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
     * Test 8: Verificar método GET soportado
     */
    public function testSupportsGetMethod(): void
    {
        $content = file_get_contents($this->apiPath);

        $this->assertStringContainsString(
            "case 'GET'",
            $content,
            'Debe soportar método GET'
        );
    }
}

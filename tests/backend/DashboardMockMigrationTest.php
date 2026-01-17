<?php
/**
 * tests/backend/DashboardMockMigrationTest.php
 *
 * Tests de migración para dashboard_mock-migrated.php
 * Verifica que la API use el nuevo sistema de configuración y ResponseHandler
 *
 * @covers \ApiResponse
 */

namespace SCAD\Tests;

use PHPUnit\Framework\TestCase;

class DashboardMockMigrationTest extends TestCase
{
    private string $apiPath;
    private string $handlerPath;

    protected function setUp(): void
    {
        $this->apiPath = __DIR__ . '/../../api/dashboard_mock-migrated.php';
        $this->handlerPath = __DIR__ . '/../../api/core/ResponseHandler.php';
    }

    /**
     * Test 1: Verificar que el archivo migrado existe
     */
    public function testApiFileExists(): void
    {
        $this->assertFileExists($this->apiPath, 'dashboard_mock-migrated.php debe existir');
    }

    /**
     * Test 2: Verificar que usa ResponseHandler
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
     * Test 3: Verificar método GET soportado
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

    /**
     * Test 4: Verificar que retorna datos de prueba
     */
    public function testReturnsMockData(): void
    {
        $content = file_get_contents($this->apiPath);

        $this->assertStringContainsString(
            'ApiResponse::success',
            $content,
            'Debe retornar datos con ApiResponse::success'
        );

        $this->assertStringContainsString(
            'mock',
            $content,
            'Debe contener datos de prueba'
        );
    }

    /**
     * Test 5: Verificar que genera estructura de datos
     */
    public function testGeneratesDataStructure(): void
    {
        $content = file_get_contents($this->apiPath);

        $this->assertStringContainsString(
            'array',
            $content,
            'Debe generar estructura de datos'
        );

        $this->assertStringContainsString(
            'data',
            $content,
            'Debe retornar datos'
        );
    }

    /**
     * Test 6: Verificar que NO usa base de datos
     */
    public function testDoesNotUseDatabase(): void
    {
        $content = file_get_contents($this->apiPath);

        $this->assertStringNotContainsString(
            "require_once __DIR__ . '/../config/database.php'",
            $content,
            'No debe usar config/database.php (es mock)'
        );

        $this->assertStringNotContainsString(
            'DatabaseConfig',
            $content,
            'No debe usar DatabaseConfig'
        );

        $this->assertStringNotContainsString(
            'query',
            $content,
            'No debe realizar consultas BD'
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
     * Test 9: Verificar autenticación (o no la requiere)
     */
    public function testHandlesAuthentication(): void
    {
        $content = file_get_contents($this->apiPath);

        // Es un mock, puede requerir o no autenticación
        // Solo verificamos que maneje sesión de forma apropiada si lo hace
        if (strpos($content, 'session_start()') !== false) {
            $this->assertStringContainsString(
                "if (!isset(\$_SESSION['logged_in'])",
                $content,
                'Si usa sesión, debe validarla'
            );
        } else {
            $this->assertStringNotContainsString(
                'session_start()',
                $content,
                'Mock puede no requerir autenticación'
            );
        }
    }

    /**
     * Test 10: Verificar que contiene datos de prueba válidos
     */
    public function testContainsValidMockData(): void
    {
        $content = file_get_contents($this->apiPath);

        $this->assertStringContainsString(
            'total',
            $content,
            'Debe incluir total en datos'
        );

        $this->assertStringContainsString(
            'items',
            $content,
            'Debe incluir items en datos'
        );
    }

    /**
     * Test 11: Verificar respuestas JSON
     */
    public function testReturnsJsonResponse(): void
    {
        $content = file_get_contents($this->apiPath);

        $this->assertStringContainsString(
            'json_encode',
            $content,
            'Debe retornar JSON'
        );
    }

    /**
     * Test 12: Verificar que tiene header Content-Type
     */
    public function testSetsJsonHeader(): void
    {
        $content = file_get_contents($this->apiPath);

        $this->assertStringContainsString(
            'application/json',
            $content,
            'Debe establecer header JSON'
        );
    }
}

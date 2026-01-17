<?php
/**
 * tests/backend/ControlPersonalStatusMigrationTest.php
 *
 * Tests de migración para control-personal-status-migrated.php
 * Verifica que la API use el nuevo sistema de configuración y ResponseHandler
 *
 * @covers \ApiResponse
 */

namespace SCAD\Tests;

use PHPUnit\Framework\TestCase;

class ControlPersonalStatusMigrationTest extends TestCase
{
    private string $apiPath;
    private string $handlerPath;

    protected function setUp(): void
    {
        $this->apiPath = __DIR__ . '/../../api/control-personal-status-migrated.php';
        $this->handlerPath = __DIR__ . '/../../api/core/ResponseHandler.php';
    }

    /**
     * Test 1: Verificar que el archivo migrado existe
     */
    public function testApiFileExists(): void
    {
        $this->assertFileExists($this->apiPath, 'control-personal-status-migrated.php debe existir');
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
     * Test 3: Verificar métodos HTTP soportados
     */
    public function testSupportsHttpMethods(): void
    {
        $content = file_get_contents($this->apiPath);

        $methods = ['GET' => 'handleGet', 'POST' => 'handlePost'];
        foreach ($methods as $httpMethod => $handler) {
            $this->assertStringContainsString(
                "case '$httpMethod'",
                $content,
                "Debe manejar método $httpMethod"
            );
            $this->assertStringContainsString(
                "function $handler",
                $content,
                "Debe tener función $handler"
            );
        }
    }

    /**
     * Test 4: Validar autenticación por sesión
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

        $this->assertStringContainsString(
            'ApiResponse::unauthorized',
            $content,
            'Debe retornar error de autorización'
        );
    }

    /**
     * Test 5: Verificar manejo de estado en sesión
     */
    public function testManagesSessionState(): void
    {
        $content = file_get_contents($this->apiPath);

        $this->assertStringContainsString(
            "\$_SESSION['controlPersonalEnabled']",
            $content,
            'Debe guardar estado en sesión'
        );

        $this->assertStringContainsString(
            "isset(\$_SESSION['controlPersonalEnabled'])",
            $content,
            'Debe leer estado desde sesión'
        );
    }

    /**
     * Test 6: Verificar validación de parámetros
     */
    public function testValidatesParameters(): void
    {
        $content = file_get_contents($this->apiPath);

        $this->assertStringContainsString(
            "if (!isset(\$data['enabled'])",
            $content,
            'Debe validar parámetro enabled'
        );

        $this->assertStringContainsString(
            'ApiResponse::badRequest',
            $content,
            'Debe retornar badRequest'
        );
    }

    /**
     * Test 7: Verificar mensajes en respuesta
     */
    public function testResponseMessages(): void
    {
        $content = file_get_contents($this->apiPath);

        $this->assertStringContainsString(
            "'message'",
            $content,
            'Debe incluir mensaje en respuesta'
        );

        $this->assertStringContainsString(
            "'Control de Unidades habilitado'",
            $content,
            'Debe incluir mensaje de habilitado'
        );

        $this->assertStringContainsString(
            "'Control de Unidades deshabilitado'",
            $content,
            'Debe incluir mensaje de deshabilitado'
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
     * Test 9: Verificar que NO usa base de datos
     */
    public function testDoesNotUseDatabaseConfig(): void
    {
        $content = file_get_contents($this->apiPath);

        $this->assertStringNotContainsString(
            "require_once __DIR__ . '/../config/database.php'",
            $content,
            'No debería usar config/database.php'
        );

        $this->assertStringNotContainsString(
            'DatabaseConfig',
            $content,
            'No debería usar DatabaseConfig'
        );
    }
}

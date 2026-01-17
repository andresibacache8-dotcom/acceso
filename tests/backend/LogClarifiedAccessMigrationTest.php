<?php
/**
 * tests/backend/LogClarifiedAccessMigrationTest.php
 *
 * Tests de migración para log_clarified_access-migrated.php
 * Verifica que la API use el nuevo sistema de configuración y ResponseHandler
 *
 * @covers \ApiResponse
 * @covers \DatabaseConfig
 */

namespace SCAD\Tests;

use PHPUnit\Framework\TestCase;

class LogClarifiedAccessMigrationTest extends TestCase
{
    private string $apiPath;
    private string $configPath;
    private string $handlerPath;

    protected function setUp(): void
    {
        $this->apiPath = __DIR__ . '/../../api/log_clarified_access-migrated.php';
        $this->configPath = __DIR__ . '/../../config/database.php';
        $this->handlerPath = __DIR__ . '/../../api/core/ResponseHandler.php';
    }

    /**
     * Test 1: Verificar que el archivo migrado existe
     */
    public function testApiFileExists(): void
    {
        $this->assertFileExists($this->apiPath, 'log_clarified_access-migrated.php debe existir');
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
     * Test 4: Verificar paginación
     */
    public function testImplementsPagination(): void
    {
        $content = file_get_contents($this->apiPath);

        $this->assertStringContainsString(
            'ApiResponse::paginated',
            $content,
            'Debe implementar paginación'
        );
    }

    /**
     * Test 5: Verificar búsqueda y filtros
     */
    public function testImplementsSearchAndFilters(): void
    {
        $content = file_get_contents($this->apiPath);

        $this->assertStringContainsString(
            "isset(\$_GET['search'])",
            $content,
            'Debe implementar búsqueda'
        );

        $this->assertStringContainsString(
            "isset(\$_GET['status'])",
            $content,
            'Debe soportar filtro por status'
        );
    }

    /**
     * Test 6: Verificar métodos HTTP
     */
    public function testSupportsHttpMethods(): void
    {
        $content = file_get_contents($this->apiPath);

        $methods = ['GET' => 'handleGet', 'POST' => 'handlePost', 'PUT' => 'handlePut', 'DELETE' => 'handleDelete'];
        foreach ($methods as $httpMethod => $handler) {
            $this->assertStringContainsString(
                "case '$httpMethod'",
                $content,
                "Debe manejar método $httpMethod"
            );
        }
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
     * Test 10: Verificar tabla existe
     */
    public function testTableExists(): void
    {
        try {
            require_once $this->configPath;

            if (!class_exists('DatabaseConfig')) {
                $this->markTestSkipped('DatabaseConfig no disponible');
            }

            $dbConfig = \DatabaseConfig::getInstance();
            $conn = $dbConfig->getAccesoConnection();

            if (!$conn) {
                $this->markTestSkipped('No se pudo conectar a BD');
            }

            $result = $conn->query("SELECT 1 FROM log_clarified_access LIMIT 1");
            $this->assertNotFalse($result, 'Tabla log_clarified_access debe existir');

        } catch (\Exception $e) {
            $this->markTestSkipped('BD no disponible: ' . $e->getMessage());
        }
    }

    /**
     * Test 11: Verificar validación
     */
    public function testImplementsValidation(): void
    {
        $content = file_get_contents($this->apiPath);

        $this->assertStringContainsString(
            'ApiResponse::badRequest',
            $content,
            'Debe validar parámetros'
        );
    }

    /**
     * Test 12: Verificar respuestas estandarizadas
     */
    public function testUsesStandardizedResponses(): void
    {
        $content = file_get_contents($this->apiPath);

        $this->assertStringContainsString(
            'ApiResponse::success',
            $content,
            'Debe usar ApiResponse::success'
        );
    }
}

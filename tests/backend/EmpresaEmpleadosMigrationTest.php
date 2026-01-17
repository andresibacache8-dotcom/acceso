<?php
/**
 * tests/backend/EmpresaEmpleadosMigrationTest.php
 *
 * Tests de migración para empresa_empleados-migrated.php
 * Verifica que la API use el nuevo sistema de configuración y ResponseHandler
 *
 * @covers \ApiResponse
 * @covers \DatabaseConfig
 */

namespace SCAD\Tests;

use PHPUnit\Framework\TestCase;

class EmpresaEmpleadosMigrationTest extends TestCase
{
    private string $apiPath;
    private string $configPath;
    private string $handlerPath;

    protected function setUp(): void
    {
        $this->apiPath = __DIR__ . '/../../api/empresa_empleados-migrated.php';
        $this->configPath = __DIR__ . '/../../config/database.php';
        $this->handlerPath = __DIR__ . '/../../api/core/ResponseHandler.php';
    }

    /**
     * Test 1: Verificar que el archivo migrado existe
     */
    public function testApiFileExists(): void
    {
        $this->assertFileExists($this->apiPath, 'empresa_empleados-migrated.php debe existir');
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

        $this->assertStringContainsString(
            "isset(\$_GET['page'])",
            $content,
            'Debe soportar parámetro page'
        );
    }

    /**
     * Test 5: Verificar búsqueda
     */
    public function testImplementsSearch(): void
    {
        $content = file_get_contents($this->apiPath);

        $this->assertStringContainsString(
            "isset(\$_GET['search'])",
            $content,
            'Debe implementar búsqueda'
        );
    }

    /**
     * Test 6: Verificar CRUD completo
     */
    public function testImplementsCrudMethods(): void
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
     * Test 7: Verificar relación con empresas
     */
    public function testImplementsRelationshipWithEmpresas(): void
    {
        $content = file_get_contents($this->apiPath);

        $this->assertStringContainsString(
            'empresa_id',
            $content,
            'Debe usar empresa_id'
        );
    }

    /**
     * Test 8: Verificar que NO usa archivos viejos
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
     * Test 9: Verificar sintaxis PHP
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
     * Test 10: Verificar autenticación
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
     * Test 11: Verificar tabla existe
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

            $result = $conn->query("SELECT 1 FROM empresa_empleados LIMIT 1");
            $this->assertNotFalse($result, 'Tabla empresa_empleados debe existir');

        } catch (\Exception $e) {
            $this->markTestSkipped('BD no disponible: ' . $e->getMessage());
        }
    }

    /**
     * Test 12: Verificar validación
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
     * Test 13: Verificar respuestas estandarizadas
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

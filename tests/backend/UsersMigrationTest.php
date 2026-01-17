<?php
/**
 * tests/backend/UsersMigrationTest.php
 *
 * Tests de migración para users-migrated.php
 * Verifica que la API use el nuevo sistema de configuración y ResponseHandler
 *
 * @covers \ApiResponse
 * @covers \DatabaseConfig
 */

namespace SCAD\Tests;

use PHPUnit\Framework\TestCase;

class UsersMigrationTest extends TestCase
{
    private string $apiPath;
    private string $configPath;
    private string $handlerPath;

    protected function setUp(): void
    {
        $this->apiPath = __DIR__ . '/../../api/users-migrated.php';
        $this->configPath = __DIR__ . '/../../config/database.php';
        $this->handlerPath = __DIR__ . '/../../api/core/ResponseHandler.php';
    }

    /**
     * Test 1: Verificar que el archivo migrado existe
     */
    public function testApiFileExists(): void
    {
        $this->assertFileExists($this->apiPath, 'users-migrated.php debe existir');
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
     * Test 4: Verificar métodos HTTP soportados
     */
    public function testSupportsAllHttpMethods(): void
    {
        $content = file_get_contents($this->apiPath);

        $methods = ['GET' => 'handleGet', 'POST' => 'handlePost', 'PUT' => 'handlePut', 'DELETE' => 'handleDelete'];
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
    }

    /**
     * Test 6: Verificar seguridad de contraseñas
     */
    public function testImplementsPasswordSecurity(): void
    {
        $content = file_get_contents($this->apiPath);

        $this->assertStringContainsString(
            'password_hash',
            $content,
            'Debe usar password_hash para hash de contraseñas'
        );

        $this->assertStringContainsString(
            'PASSWORD_DEFAULT',
            $content,
            'Debe usar PASSWORD_DEFAULT'
        );
    }

    /**
     * Test 7: Verificar que NO retorna contraseñas
     */
    public function testDoesNotReturnPasswords(): void
    {
        $content = file_get_contents($this->apiPath);

        // Simplemente verificar que el archivo no contiene lógica de retornar contraseñas
        $this->assertStringNotContainsString(
            "'password' => \$",
            $content,
            'Las respuestas no deben contener contraseñas'
        );
    }

    /**
     * Test 8: Validar tabla users en base de datos
     */
    public function testTableExists(): void
    {
        try {
            require_once $this->configPath;

            if (!class_exists('DatabaseConfig')) {
                $this->markTestSkipped('DatabaseConfig no disponible en ambiente de testing');
            }

            $dbConfig = \DatabaseConfig::getInstance();
            $conn = $dbConfig->getAccesoConnection();

            if (!$conn) {
                $this->markTestSkipped('No se pudo conectar a base de datos');
            }

            $result = $conn->query("SELECT 1 FROM users LIMIT 1");
            $this->assertNotFalse($result, 'Tabla users debe existir');

        } catch (\Exception $e) {
            $this->markTestSkipped('BD no disponible: ' . $e->getMessage());
        }
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

        $this->assertStringContainsString(
            'ApiResponse::unauthorized',
            $content,
            'Debe retornar error de autorización'
        );
    }
}

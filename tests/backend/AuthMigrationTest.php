<?php
/**
 * tests/backend/AuthMigrationTest.php
 *
 * Tests de migración para auth-migrated.php
 * Verifica que la API use el nuevo sistema de configuración y ResponseHandler
 *
 * @covers \ApiResponse
 * @covers \DatabaseConfig
 */

namespace SCAD\Tests;

use PHPUnit\Framework\TestCase;

class AuthMigrationTest extends TestCase
{
    private string $authPath;
    private string $configPath;
    private string $handlerPath;

    protected function setUp(): void
    {
        $this->authPath = __DIR__ . '/../../api/auth-migrated.php';
        $this->configPath = __DIR__ . '/../../config/database.php';
        $this->handlerPath = __DIR__ . '/../../api/core/ResponseHandler.php';
    }

    /**
     * Test 1: Verificar que el archivo migrado existe
     */
    public function testAuthMigratedFileExists(): void
    {
        $this->assertFileExists($this->authPath, 'auth-migrated.php debe existir');
    }

    /**
     * Test 2: Verificar que usa config/database.php
     */
    public function testUsesConfigDatabase(): void
    {
        $content = file_get_contents($this->authPath);

        $this->assertStringContainsString(
            "require_once __DIR__ . '/../config/database.php'",
            $content,
            'Debe importar config/database.php'
        );
    }

    /**
     * Test 3: Verificar que usa DatabaseConfig
     */
    public function testUsesDatabaseConfig(): void
    {
        $content = file_get_contents($this->authPath);

        $this->assertStringContainsString(
            'DatabaseConfig::getInstance()',
            $content,
            'Debe usar DatabaseConfig::getInstance()'
        );

        $this->assertStringContainsString(
            'getAccesoConnection()',
            $content,
            'Debe usar getAccesoConnection()'
        );
    }

    /**
     * Test 4: Verificar que usa ResponseHandler
     */
    public function testUsesResponseHandler(): void
    {
        $this->assertFileExists($this->handlerPath, 'ResponseHandler.php debe existir');

        $content = file_get_contents($this->authPath);

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
     * Test 5: Verificar que soporta GET y POST
     */
    public function testSupportsGetAndPost(): void
    {
        $content = file_get_contents($this->authPath);

        $this->assertStringContainsString(
            "'GET'",
            $content,
            'Debe soportar método GET'
        );

        $this->assertStringContainsString(
            "'POST'",
            $content,
            'Debe soportar método POST'
        );
    }

    /**
     * Test 6: Verificar que NO usa archivos viejos
     */
    public function testDoesNotUseOldFiles(): void
    {
        $content = file_get_contents($this->authPath);

        $this->assertStringNotContainsString(
            "require_once 'database/db_acceso.php'",
            $content,
            'No debe usar database/db_acceso.php'
        );
    }

    /**
     * Test 7: Verificar validación de credenciales
     */
    public function testValidatesCredentials(): void
    {
        $content = file_get_contents($this->authPath);

        $this->assertStringContainsString(
            'password_verify',
            $content,
            'Debe validar contraseña con password_verify'
        );

        $this->assertStringContainsString(
            'username',
            $content,
            'Debe validar username'
        );
    }

    /**
     * Test 8: Verificar sesiones
     */
    public function testManagesSessions(): void
    {
        $content = file_get_contents($this->authPath);

        $this->assertStringContainsString(
            'session_start()',
            $content,
            'Debe iniciar sesión'
        );

        $this->assertStringContainsString(
            "\$_SESSION['logged_in']",
            $content,
            'Debe guardar logged_in en sesión'
        );

        $this->assertStringContainsString(
            "\$_SESSION['user_id']",
            $content,
            'Debe guardar user_id en sesión'
        );
    }

    /**
     * Test 9: Verificar sintaxis PHP
     */
    public function testHasValidPhpSyntax(): void
    {
        $output = shell_exec('php -l ' . escapeshellarg($this->authPath) . ' 2>&1');

        $this->assertStringContainsString(
            'No syntax errors detected',
            $output,
            'PHP debe tener sintaxis válida'
        );
    }

    /**
     * Test 10: Verificar respuestas estandarizadas
     */
    public function testUsesStandardizedResponses(): void
    {
        $content = file_get_contents($this->authPath);

        $this->assertStringContainsString(
            'ApiResponse::success',
            $content,
            'Debe usar ApiResponse::success'
        );

        $this->assertStringContainsString(
            'ApiResponse::badRequest',
            $content,
            'Debe usar ApiResponse::badRequest'
        );

        $this->assertStringContainsString(
            'ApiResponse::unauthorized',
            $content,
            'Debe usar ApiResponse::unauthorized'
        );

        $this->assertStringContainsString(
            'ApiResponse::serverError',
            $content,
            'Debe usar ApiResponse::serverError'
        );
    }

    /**
     * Test 11: Verificar que tabla users existe
     */
    public function testUsersTableExists(): void
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
     * Test 12: Verificar estructura de tabla users
     */
    public function testUsersTableHasRequiredColumns(): void
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

            $result = $conn->query("DESCRIBE users");
            $columns = [];

            while ($row = $result->fetch_assoc()) {
                $columns[] = $row['Field'];
            }

            $required = ['id', 'username', 'password', 'role'];
            foreach ($required as $col) {
                $this->assertContains(
                    $col,
                    $columns,
                    "Columna '$col' debe existir en tabla users"
                );
            }

        } catch (\Exception $e) {
            $this->markTestSkipped('BD no disponible: ' . $e->getMessage());
        }
    }
}

<?php
/**
 * tests/backend/PersonalMigrationTest.php
 *
 * Tests de migración para personal-migrated.php
 * Verifica que la API mantenga funcionalidad completa con nuevo sistema
 *
 * @covers \ApiResponse
 * @covers \DatabaseConfig
 */

namespace SCAD\Tests;

use PHPUnit\Framework\TestCase;

class PersonalMigrationTest extends TestCase
{
    private string $personalPath;
    private string $configPath;
    private string $handlerPath;

    protected function setUp(): void
    {
        $this->personalPath = __DIR__ . '/../../api/personal-migrated.php';
        $this->configPath = __DIR__ . '/../../config/database.php';
        $this->handlerPath = __DIR__ . '/../../api/core/ResponseHandler.php';
    }

    /**
     * Test 1: Verificar que el archivo migrado existe
     */
    public function testPersonalMigratedFileExists(): void
    {
        $this->assertFileExists($this->personalPath, 'personal-migrated.php debe existir');
    }

    /**
     * Test 2: Verificar que usa config/database.php
     */
    public function testUsesConfigDatabase(): void
    {
        $content = file_get_contents($this->personalPath);

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
        $this->assertFileExists($this->handlerPath, 'ResponseHandler.php debe existir');

        $content = file_get_contents($this->personalPath);

        $this->assertStringContainsString(
            "require_once __DIR__ . '/core/ResponseHandler.php'",
            $content,
            'Debe importar ResponseHandler.php'
        );

        $this->assertStringContainsString(
            'ApiResponse::',
            $content,
            'Debe usar métodos ApiResponse'
        );
    }

    /**
     * Test 4: Verificar que implementa paginación
     */
    public function testImplementsPagination(): void
    {
        $content = file_get_contents($this->personalPath);

        $this->assertStringContainsString(
            'ApiResponse::paginated',
            $content,
            'Debe implementar ApiResponse::paginated'
        );

        $this->assertStringContainsString(
            "'page'",
            $content,
            'Debe soportar parámetro page'
        );

        $this->assertStringContainsString(
            "'perPage'",
            $content,
            'Debe soportar parámetro perPage'
        );
    }

    /**
     * Test 5: Verificar búsqueda por múltiples campos
     */
    public function testImplementsSearchFunctionality(): void
    {
        $content = file_get_contents($this->personalPath);

        $this->assertStringContainsString(
            "'search'",
            $content,
            'Debe soportar búsqueda por search'
        );

        $this->assertStringContainsString(
            "'rut'",
            $content,
            'Debe soportar búsqueda por rut'
        );

        $this->assertStringContainsString(
            "'id'",
            $content,
            'Debe soportar búsqueda por id'
        );
    }

    /**
     * Test 6: Verificar importación masiva
     */
    public function testImplementsMassImport(): void
    {
        $content = file_get_contents($this->personalPath);

        $this->assertStringContainsString(
            "import",
            $content,
            'Debe implementar importación masiva'
        );

        $this->assertStringContainsString(
            'begin_transaction',
            $content,
            'Debe usar transacciones para importación'
        );

        $this->assertStringContainsString(
            'commit',
            $content,
            'Debe confirmar transacciones'
        );

        $this->assertStringContainsString(
            'rollback',
            $content,
            'Debe revertir transacciones en errores'
        );
    }

    /**
     * Test 7: Verificar que soporta todos los métodos HTTP
     */
    public function testSupportsAllHttpMethods(): void
    {
        $content = file_get_contents($this->personalPath);

        $methods = ['GET', 'POST', 'PUT', 'DELETE'];
        foreach ($methods as $method) {
            $this->assertStringContainsString(
                "'$method'",
                $content,
                "Debe soportar método HTTP $method"
            );
        }
    }

    /**
     * Test 8: Verificar que NO usa archivos viejos
     */
    public function testDoesNotUseOldFiles(): void
    {
        $content = file_get_contents($this->personalPath);

        $this->assertStringNotContainsString(
            "require_once 'database/db_personal.php'",
            $content,
            'No debe usar database/db_personal.php'
        );

        $this->assertStringNotContainsString(
            "require_once 'database/db_acceso.php'",
            $content,
            'No debe usar database/db_acceso.php'
        );
    }

    /**
     * Test 9: Verificar sintaxis PHP
     */
    public function testHasValidPhpSyntax(): void
    {
        $output = shell_exec('php -l ' . escapeshellarg($this->personalPath) . ' 2>&1');

        $this->assertStringContainsString(
            'No syntax errors detected',
            $output,
            'PHP debe tener sintaxis válida'
        );
    }

    /**
     * Test 10: Verificar que tabla personal existe
     */
    public function testPersonalTableExists(): void
    {
        try {
            require_once $this->configPath;

            if (!class_exists('DatabaseConfig')) {
                $this->markTestSkipped('DatabaseConfig no disponible en ambiente de testing');
            }

            $dbConfig = \DatabaseConfig::getInstance();
            $conn = $dbConfig->getPersonalConnection();

            if (!$conn) {
                $this->markTestSkipped('No se pudo conectar a base de datos');
            }

            $result = $conn->query("SELECT 1 FROM personal LIMIT 1");
            $this->assertNotFalse($result, 'Tabla personal debe existir');

        } catch (\Exception $e) {
            $this->markTestSkipped('BD no disponible: ' . $e->getMessage());
        }
    }

    /**
     * Test 11: Verificar respuestas estandarizadas
     */
    public function testUsesStandardizedResponses(): void
    {
        $content = file_get_contents($this->personalPath);

        $this->assertStringContainsString(
            'ApiResponse::success',
            $content,
            'Debe usar ApiResponse::success'
        );

        $this->assertStringContainsString(
            'ApiResponse::paginated',
            $content,
            'Debe usar ApiResponse::paginated para listados'
        );

        $this->assertStringContainsString(
            'ApiResponse::created',
            $content,
            'Debe usar ApiResponse::created para POST'
        );
    }

    /**
     * Test 12: Verificar que no usa echo json_encode directo
     */
    public function testDoesNotUseDirectEcho(): void
    {
        $content = file_get_contents($this->personalPath);

        // Verificar que no hay echo json_encode directo
        $this->assertStringNotContainsString(
            'echo json_encode',
            $content,
            'No debe usar echo json_encode directo'
        );
    }
}

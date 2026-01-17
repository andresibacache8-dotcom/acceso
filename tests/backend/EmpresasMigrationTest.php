<?php
/**
 * tests/backend/EmpresasMigrationTest.php
 *
 * Tests de migración para empresas-migrated.php
 * Verifica que la API mantenga CRUD y enriquecimiento con nuevo sistema
 *
 * @covers \ApiResponse
 * @covers \DatabaseConfig
 */

namespace SCAD\Tests;

use PHPUnit\Framework\TestCase;

class EmpresasMigrationTest extends TestCase
{
    private string $empresasPath;
    private string $configPath;
    private string $handlerPath;

    protected function setUp(): void
    {
        $this->empresasPath = __DIR__ . '/../../api/empresas-migrated.php';
        $this->configPath = __DIR__ . '/../../config/database.php';
        $this->handlerPath = __DIR__ . '/../../api/core/ResponseHandler.php';
    }

    /**
     * Test 1: Verificar que el archivo migrado existe
     */
    public function testEmpresasMigratedFileExists(): void
    {
        $this->assertFileExists($this->empresasPath, 'empresas-migrated.php debe existir');
    }

    /**
     * Test 2: Verificar que usa config/database.php
     */
    public function testUsesConfigDatabase(): void
    {
        $content = file_get_contents($this->empresasPath);

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
        $this->assertFileExists($this->handlerPath, 'ResponseHandler.php debe existir');

        $content = file_get_contents($this->empresasPath);

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
        $content = file_get_contents($this->empresasPath);

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

        $this->assertStringContainsString(
            'LIMIT',
            $content,
            'Debe usar LIMIT/OFFSET en consultas'
        );
    }

    /**
     * Test 5: Verificar búsqueda por nombre
     */
    public function testImplementsSearchByName(): void
    {
        $content = file_get_contents($this->empresasPath);

        $this->assertStringContainsString(
            "'search'",
            $content,
            'Debe soportar búsqueda'
        );

        $this->assertStringContainsString(
            'LIKE',
            $content,
            'Debe usar búsqueda LIKE por nombre'
        );
    }

    /**
     * Test 6: Verificar que soporta todos los métodos HTTP
     */
    public function testSupportsAllHttpMethods(): void
    {
        $content = file_get_contents($this->empresasPath);

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
     * Test 7: Verificar que NO usa archivos viejos
     */
    public function testDoesNotUseOldFiles(): void
    {
        $content = file_get_contents($this->empresasPath);

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
     * Test 8: Verificar enriquecimiento con datos de POC
     */
    public function testImplementsPOCEnrichment(): void
    {
        $content = file_get_contents($this->empresasPath);

        $this->assertStringContainsString(
            'enrichEmpresaWithPOC',
            $content,
            'Debe implementar enriquecimiento de POC'
        );

        $this->assertStringContainsString(
            'function enrichEmpresaWithPOC',
            $content,
            'Debe tener función enrichEmpresaWithPOC'
        );

        $this->assertStringContainsString(
            'getPersonalConnection()',
            $content,
            'Debe obtener conexión a tabla personal para POC'
        );
    }

    /**
     * Test 9: Verificar sintaxis PHP
     */
    public function testHasValidPhpSyntax(): void
    {
        $output = shell_exec('php -l ' . escapeshellarg($this->empresasPath) . ' 2>&1');

        $this->assertStringContainsString(
            'No syntax errors detected',
            $output,
            'PHP debe tener sintaxis válida'
        );
    }

    /**
     * Test 10: Verificar que tabla empresas existe
     */
    public function testEmpresasTableExists(): void
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

            $result = $conn->query("SELECT 1 FROM empresas LIMIT 1");
            $this->assertNotFalse($result, 'Tabla empresas debe existir');

        } catch (\Exception $e) {
            $this->markTestSkipped('BD no disponible: ' . $e->getMessage());
        }
    }

    /**
     * Test 11: Verificar estructura de tabla empresas
     */
    public function testEmpresasTableHasRequiredColumns(): void
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

            $result = $conn->query("DESCRIBE empresas");
            $columns = [];

            while ($row = $result->fetch_assoc()) {
                $columns[] = $row['Field'];
            }

            $required = ['id', 'nombre', 'unidad_poc', 'poc_rut', 'poc_nombre'];
            foreach ($required as $col) {
                $this->assertContains(
                    $col,
                    $columns,
                    "Columna '$col' debe existir en tabla empresas"
                );
            }

        } catch (\Exception $e) {
            $this->markTestSkipped('BD no disponible: ' . $e->getMessage());
        }
    }

    /**
     * Test 12: Verificar autenticación por sesión
     */
    public function testImplementsSessionAuthentication(): void
    {
        $content = file_get_contents($this->empresasPath);

        $this->assertStringContainsString(
            'session_start()',
            $content,
            'Debe iniciar sesión'
        );

        $this->assertStringContainsString(
            "\$_SESSION['logged_in']",
            $content,
            'Debe validar logged_in en sesión'
        );

        $this->assertStringContainsString(
            'ApiResponse::unauthorized',
            $content,
            'Debe retornar 401 si no autenticado'
        );
    }

    /**
     * Test 13: Verificar respuestas estandarizadas
     */
    public function testUsesStandardizedResponses(): void
    {
        $content = file_get_contents($this->empresasPath);

        $this->assertStringContainsString(
            'ApiResponse::success',
            $content,
            'Debe usar ApiResponse::success'
        );

        $this->assertStringContainsString(
            'ApiResponse::created',
            $content,
            'Debe usar ApiResponse::created para POST'
        );

        $this->assertStringContainsString(
            'ApiResponse::paginated',
            $content,
            'Debe usar ApiResponse::paginated para listados'
        );
    }

    /**
     * Test 14: Verificar que no usa echo json_encode directo (fuera de comentarios)
     */
    public function testDoesNotUseDirectEcho(): void
    {
        $content = file_get_contents($this->empresasPath);
        $lines = explode("\n", $content);

        foreach ($lines as $line) {
            // Ignorar líneas que son comentarios
            if (trim($line) === '' || strpos(trim($line), '//') === 0 || strpos(trim($line), '*') === 0) {
                continue;
            }

            // Verificar que no hay echo json_encode en código real
            if (preg_match('/^\s*echo\s+json_encode\s*\(/', $line)) {
                $this->fail('No debe usar echo json_encode directo en código');
            }
        }

        // Al menos debe tener ApiResponse::
        $this->assertStringContainsString(
            'ApiResponse::',
            $content,
            'Debe usar ApiResponse en lugar de echo json_encode'
        );
    }
}

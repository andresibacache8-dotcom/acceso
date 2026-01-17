<?php
/**
 * tests/backend/VisitasMigrationTest.php
 *
 * Tests de migración para visitas-migrated.php
 * Verifica que la API use el nuevo sistema de configuración y ResponseHandler
 *
 * @covers \ApiResponse
 * @covers \DatabaseConfig
 */

namespace SCAD\Tests;

use PHPUnit\Framework\TestCase;

class VisitasMigrationTest extends TestCase
{
    private string $apiPath;
    private string $configPath;
    private string $handlerPath;

    protected function setUp(): void
    {
        $this->apiPath = __DIR__ . '/../../api/visitas-migrated.php';
        $this->configPath = __DIR__ . '/../../config/database.php';
        $this->handlerPath = __DIR__ . '/../../api/core/ResponseHandler.php';
    }

    /**
     * Test 1: Verificar que el archivo migrado existe
     */
    public function testApiFileExists(): void
    {
        $this->assertFileExists($this->apiPath, 'visitas-migrated.php debe existir');
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
     * Test 4: Verificar que implementa paginación
     */
    public function testImplementsPagination(): void
    {
        $content = file_get_contents($this->apiPath);

        $this->assertStringContainsString(
            'ApiResponse::paginated',
            $content,
            'Debe implementar paginación con ApiResponse::paginated'
        );

        $this->assertStringContainsString(
            "isset(\$_GET['page'])",
            $content,
            'Debe soportar parámetro page'
        );

        $this->assertStringContainsString(
            "isset(\$_GET['perPage'])",
            $content,
            'Debe soportar parámetro perPage'
        );

        $this->assertStringContainsString(
            'LIMIT',
            $content,
            'Debe usar LIMIT en consultas'
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
            "isset(\$_GET['tipo'])",
            $content,
            'Debe soportar filtro por tipo'
        );

        $this->assertStringContainsString(
            "isset(\$_GET['status'])",
            $content,
            'Debe soportar filtro por status'
        );
    }

    /**
     * Test 6: Verificar que soporta todos los métodos HTTP
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

        $this->assertStringNotContainsString(
            "require_once 'database/db_personal.php'",
            $content,
            'No debe usar database/db_personal.php'
        );
    }

    /**
     * Test 8: Verificar cálculo de status
     */
    public function testImplementsStatusCalculation(): void
    {
        $content = file_get_contents($this->apiPath);

        $this->assertStringContainsString(
            'calculateVisitaStatus',
            $content,
            'Debe implementar cálculo de status'
        );

        $this->assertStringContainsString(
            'is_blacklisted',
            $content,
            'Debe validar lista negra'
        );

        $this->assertStringContainsString(
            'is_permanent',
            $content,
            'Debe validar acceso permanente'
        );

        $this->assertStringContainsString(
            'DateTime',
            $content,
            'Debe manejar fechas'
        );
    }

    /**
     * Test 9: Verificar toggle lista negra
     */
    public function testImplementsToggleBlacklist(): void
    {
        $content = file_get_contents($this->apiPath);

        $this->assertTrue(
            (strpos($content, "action') && \$_GET['action'] === 'toggle_blacklist'") !== false ||
            strpos($content, "isset(\$_GET['action']) && \$_GET['action'] === 'toggle_blacklist'") !== false),
            'Debe implementar toggle_blacklist'
        );

        $this->assertStringContainsString(
            'en_lista_negra',
            $content,
            'Debe obtener en_lista_negra en toggle'
        );
    }

    /**
     * Test 10: Verificar enriquecimiento con datos de POC/Familiar
     */
    public function testImplementsDataEnrichment(): void
    {
        $content = file_get_contents($this->apiPath);

        $this->assertStringContainsString(
            'enrichVisitaWithPersonal',
            $content,
            'Debe implementar enriquecimiento'
        );

        $this->assertStringContainsString(
            'poc_personal_id',
            $content,
            'Debe enriquecer POC'
        );

        $this->assertStringContainsString(
            'familiar_de_personal_id',
            $content,
            'Debe enriquecer Familiar'
        );
    }

    /**
     * Test 11: Validar tabla visitas en base de datos
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

            $result = $conn->query("SELECT 1 FROM visitas LIMIT 1");
            $this->assertNotFalse($result, 'Tabla visitas debe existir');

        } catch (\Exception $e) {
            $this->markTestSkipped('BD no disponible: ' . $e->getMessage());
        }
    }

    /**
     * Test 12: Validar estructura de tabla visitas
     */
    public function testTableStructure(): void
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

            $result = $conn->query("DESCRIBE visitas");
            $columns = [];

            while ($row = $result->fetch_assoc()) {
                $columns[] = $row['Field'];
            }

            $required = ['id', 'rut', 'nombre', 'tipo', 'fecha_inicio', 'acceso_permanente',
                        'en_lista_negra', 'status', 'poc_personal_id', 'familiar_de_personal_id'];

            foreach ($required as $col) {
                $this->assertContains(
                    $col,
                    $columns,
                    "Columna '$col' debe existir en tabla visitas"
                );
            }

        } catch (\Exception $e) {
            $this->markTestSkipped('BD no disponible: ' . $e->getMessage());
        }
    }

    /**
     * Test 13: Verificar sintaxis PHP
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
     * Test 14: Verificar autenticación
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

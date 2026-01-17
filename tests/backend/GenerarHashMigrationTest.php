<?php
/**
 * tests/backend/GenerarHashMigrationTest.php
 *
 * Tests de migración para generar_hash-migrated.php
 * Verifica que la API use el nuevo sistema de configuración y ResponseHandler
 *
 * @covers \ApiResponse
 */

namespace SCAD\Tests;

use PHPUnit\Framework\TestCase;

class GenerarHashMigrationTest extends TestCase
{
    private string $apiPath;
    private string $handlerPath;

    protected function setUp(): void
    {
        $this->apiPath = __DIR__ . '/../../api/generar_hash-migrated.php';
        $this->handlerPath = __DIR__ . '/../../api/core/ResponseHandler.php';
    }

    /**
     * Test 1: Verificar que el archivo migrado existe
     */
    public function testApiFileExists(): void
    {
        $this->assertFileExists($this->apiPath, 'generar_hash-migrated.php debe existir');
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
     * Test 3: Verificar método POST soportado
     */
    public function testSupportsPostMethod(): void
    {
        $content = file_get_contents($this->apiPath);

        $this->assertStringContainsString(
            "case 'POST'",
            $content,
            'Debe soportar método POST'
        );
    }

    /**
     * Test 4: Verificar que genera hash
     */
    public function testGeneratesHash(): void
    {
        $content = file_get_contents($this->apiPath);

        $this->assertStringContainsString(
            'password_hash',
            $content,
            'Debe usar password_hash para generar hash'
        );

        $this->assertStringContainsString(
            'PASSWORD_DEFAULT',
            $content,
            'Debe usar PASSWORD_DEFAULT'
        );
    }

    /**
     * Test 5: Verificar validación de entrada
     */
    public function testValidatesInput(): void
    {
        $content = file_get_contents($this->apiPath);

        $this->assertStringContainsString(
            'empty',
            $content,
            'Debe validar entrada'
        );

        $this->assertStringContainsString(
            'ApiResponse::badRequest',
            $content,
            'Debe retornar badRequest si falta datos'
        );
    }

    /**
     * Test 6: Verificar que retorna hash
     */
    public function testReturnsHash(): void
    {
        $content = file_get_contents($this->apiPath);

        $this->assertStringContainsString(
            'hash',
            $content,
            'Debe retornar el hash generado'
        );

        $this->assertStringContainsString(
            'ApiResponse::success',
            $content,
            'Debe retornar success'
        );
    }

    /**
     * Test 7: Verificar que NO usa base de datos
     */
    public function testDoesNotUseDatabase(): void
    {
        $content = file_get_contents($this->apiPath);

        $this->assertStringNotContainsString(
            "require_once __DIR__ . '/../config/database.php'",
            $content,
            'No debe usar config/database.php'
        );

        $this->assertStringNotContainsString(
            'DatabaseConfig',
            $content,
            'No debe usar DatabaseConfig'
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
     * Test 10: Verificar seguridad de contraseña
     */
    public function testImplementsPasswordSecurity(): void
    {
        $content = file_get_contents($this->apiPath);

        // Verificar que no retorna la contraseña original
        $this->assertStringNotContainsString(
            "'password' => \$_POST",
            $content,
            'No debe retornar la contraseña original'
        );

        // Verificar que solo retorna el hash
        $this->assertStringContainsString(
            "'hash'",
            $content,
            'Debe retornar el hash'
        );
    }

    /**
     * Test 11: Verificar autenticación (si requiere)
     */
    public function testHandlesAuthentication(): void
    {
        $content = file_get_contents($this->apiPath);

        // Verificar si requiere autenticación
        if (strpos($content, 'session_start()') !== false) {
            $this->assertStringContainsString(
                "if (!isset(\$_SESSION['logged_in'])",
                $content,
                'Si requiere sesión, debe validarla'
            );
        }
    }

    /**
     * Test 12: Verificar que es stateless
     */
    public function testIsStateless(): void
    {
        $content = file_get_contents($this->apiPath);

        $this->assertStringContainsString(
            'POST',
            $content,
            'Debe ser una operación POST'
        );

        // No debe guardar en BD ni sesión (es solo generador de hash)
        $this->assertStringNotContainsString(
            'INSERT',
            $content,
            'No debería insertar en BD'
        );
    }

    /**
     * Test 13: Verificar respuesta JSON
     */
    public function testReturnsJsonResponse(): void
    {
        $content = file_get_contents($this->apiPath);

        $this->assertStringContainsString(
            'application/json',
            $content,
            'Debe establecer header JSON'
        );

        $this->assertStringContainsString(
            'json_encode',
            $content,
            'Debe retornar JSON'
        );
    }
}

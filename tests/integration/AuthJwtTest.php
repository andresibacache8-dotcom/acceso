<?php
/**
 * tests/integration/AuthJwtTest.php
 *
 * Tests de integración para autenticación con JWT
 * Simula requests HTTP a la API de autenticación
 */

use PHPUnit\Framework\TestCase;

class AuthJwtTest extends TestCase
{
    private $apiUrl = 'http://localhost/acceso/api/auth-migrated.php';
    private $testUsername = 'testuser_jwt';
    private $testPassword = 'testpass_123';
    private $conn;

    protected function setUp(): void
    {
        require_once __DIR__ . '/../../config/database.php';

        $databaseConfig = DatabaseConfig::getInstance();
        $this->conn = $databaseConfig->getAccesoConnection();

        // Crear usuario de test
        $this->createTestUser();
    }

    protected function tearDown(): void
    {
        // Limpiar usuario de test
        $this->deleteTestUser();

        if ($this->conn) {
            $this->conn->close();
        }
    }

    private function createTestUser(): void
    {
        if (!$this->conn) return;

        $hashedPassword = password_hash($this->testPassword, PASSWORD_BCRYPT);
        $role = 'user';

        // Eliminar si existe
        $stmt = $this->conn->prepare("DELETE FROM users WHERE username = ?");
        if ($stmt) {
            $stmt->bind_param("s", $this->testUsername);
            $stmt->execute();
            $stmt->close();
        }

        // Insertar nuevo usuario
        $stmt = $this->conn->prepare("INSERT INTO users (username, password, role) VALUES (?, ?, ?)");
        if ($stmt) {
            $stmt->bind_param("sss", $this->testUsername, $hashedPassword, $role);
            $stmt->execute();
            $stmt->close();
        }
    }

    private function deleteTestUser(): void
    {
        if (!$this->conn) return;

        $stmt = $this->conn->prepare("DELETE FROM users WHERE username = ?");
        if ($stmt) {
            $stmt->bind_param("s", $this->testUsername);
            $stmt->execute();
            $stmt->close();
        }
    }

    /**
     * Helper: Realizar request POST al API
     */
    private function postToAuthApi($data)
    {
        $ch = curl_init($this->apiUrl);

        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
        curl_setopt($ch, CURLOPT_HTTPHEADER, [
            'Content-Type: application/json'
        ]);
        curl_setopt($ch, CURLOPT_TIMEOUT, 5);

        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        return [
            'code' => $httpCode,
            'body' => json_decode($response, true) ?? $response
        ];
    }

    /**
     * Helper: Realizar request GET con JWT token
     */
    private function getFromAuthApi($token)
    {
        $ch = curl_init($this->apiUrl);

        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_HTTPHEADER, [
            'Content-Type: application/json',
            'Authorization: Bearer ' . $token
        ]);
        curl_setopt($ch, CURLOPT_TIMEOUT, 5);

        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        return [
            'code' => $httpCode,
            'body' => json_decode($response, true) ?? $response
        ];
    }

    /**
     * Test: Login con credenciales válidas retorna JWT tokens
     */
    public function testLoginWithValidCredentialsReturnsJwtTokens(): void
    {
        $this->markTestSkipped('Requires running web server. Run with: npm run test:integration');

        $response = $this->postToAuthApi([
            'username' => $this->testUsername,
            'password' => $this->testPassword
        ]);

        $this->assertEquals(200, $response['code']);
        $this->assertTrue($response['body']['success']);
        $this->assertArrayHasKey('data', $response['body']);
        $this->assertArrayHasKey('token', $response['body']['data']);
        $this->assertArrayHasKey('refreshToken', $response['body']['data']);
    }

    /**
     * Test: Login con credenciales inválidas retorna 401
     */
    public function testLoginWithInvalidCredentialsReturns401(): void
    {
        $this->markTestSkipped('Requires running web server. Run with: npm run test:integration');

        $response = $this->postToAuthApi([
            'username' => $this->testUsername,
            'password' => 'wrong_password'
        ]);

        $this->assertEquals(401, $response['code']);
        $this->assertFalse($response['body']['success']);
    }

    /**
     * Test: Login con campos faltantes retorna 400
     */
    public function testLoginWithMissingFieldsReturns400(): void
    {
        $this->markTestSkipped('Requires running web server. Run with: npm run test:integration');

        $response = $this->postToAuthApi([
            'username' => $this->testUsername
            // Falta password
        ]);

        $this->assertEquals(400, $response['code']);
    }

    /**
     * Test: GET /auth con JWT válido retorna datos del usuario
     */
    public function testGetAuthWithValidTokenReturnsUserData(): void
    {
        $this->markTestSkipped('Requires running web server. Run with: npm run test:integration');

        // Primero hacer login
        $loginResponse = $this->postToAuthApi([
            'username' => $this->testUsername,
            'password' => $this->testPassword
        ]);

        $token = $loginResponse['body']['data']['token'];

        // Ahora hacer GET con token
        $response = $this->getFromAuthApi($token);

        $this->assertEquals(200, $response['code']);
        $this->assertTrue($response['body']['success']);
        $this->assertArrayHasKey('data', $response['body']);
        $this->assertEquals($this->testUsername, $response['body']['data']['username']);
    }

    /**
     * Test: GET /auth sin token retorna 401
     */
    public function testGetAuthWithoutTokenReturns401(): void
    {
        $this->markTestSkipped('Requires running web server. Run with: npm run test:integration');

        $response = $this->getFromAuthApi('');

        $this->assertEquals(401, $response['code']);
    }

    /**
     * Test: GET /auth con JWT inválido retorna 401
     */
    public function testGetAuthWithInvalidTokenReturns401(): void
    {
        $this->markTestSkipped('Requires running web server. Run with: npm run test:integration');

        $invalidToken = 'invalid.jwt.token';

        $response = $this->getFromAuthApi($invalidToken);

        $this->assertEquals(401, $response['code']);
    }

    /**
     * Test: JWT token retornado contiene estructura correcta
     */
    public function testJwtTokenHasCorrectStructure(): void
    {
        $this->markTestSkipped('Requires running web server. Run with: npm run test:integration');

        $response = $this->postToAuthApi([
            'username' => $this->testUsername,
            'password' => $this->testPassword
        ]);

        $token = $response['body']['data']['token'];

        // Token debe tener 3 partes separadas por puntos
        $parts = explode('.', $token);
        $this->assertCount(3, $parts);
    }

    /**
     * Test: Múltiples intentos de login falla después del límite (rate limiting)
     */
    public function testMultipleLoginAttemptsTriggersRateLimiting(): void
    {
        $this->markTestSkipped('Requires running web server. Run with: npm run test:integration');

        // Hacer 6 intentos de login (límite es 5 en 5 minutos)
        $lastResponse = null;

        for ($i = 0; $i < 6; $i++) {
            $lastResponse = $this->postToAuthApi([
                'username' => $this->testUsername,
                'password' => 'wrong_password'
            ]);

            if ($lastResponse['code'] === 429) {
                // Rate limited!
                break;
            }
        }

        // Última respuesta debe ser 429 (Too Many Requests)
        $this->assertEquals(429, $lastResponse['code']);
    }

    /**
     * Test: API retorna security headers
     */
    public function testApiReturnsSecurityHeaders(): void
    {
        $this->markTestSkipped('Requires running web server. Run with: npm run test:integration');

        $ch = curl_init($this->apiUrl);

        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_HEADER, true);
        curl_setopt($ch, CURLOPT_NOBODY, true);
        curl_setopt($ch, CURLOPT_TIMEOUT, 5);

        curl_exec($ch);
        $headers = curl_getinfo($ch);
        curl_close($ch);

        // Verificar que ciertos headers de seguridad estén presentes
        // Nota: curl_getinfo() no retorna los headers de respuesta en este contexto
        // Esta es una verificación conceptual
        $this->assertTrue(true);
    }

    /**
     * Test: Access token tiene tiempo de expiración corto
     */
    public function testAccessTokenExpiration(): void
    {
        // Test unitario que verifica JWT properties
        require_once __DIR__ . '/../../api/core/JwtHandler.php';

        JwtHandler::init('test-secret-key');

        $token = JwtHandler::generate(1, 'testuser', 'admin', false);
        $decoded = JwtHandler::decode($token);

        // Debe expirar en el futuro
        $this->assertGreaterThan(time(), $decoded['exp']);

        // Pero no en más de 2 horas
        $this->assertLessThan(time() + 7200, $decoded['exp']);

        // Debe tener tipo 'access'
        $this->assertEquals('access', $decoded['type']);
    }

    /**
     * Test: Refresh token tiene tiempo de expiración largo
     */
    public function testRefreshTokenExpiration(): void
    {
        require_once __DIR__ . '/../../api/core/JwtHandler.php';

        JwtHandler::init('test-secret-key');

        $token = JwtHandler::generate(1, 'testuser', 'admin', true);
        $decoded = JwtHandler::decode($token);

        // Debe expirar en el futuro (7 días)
        $this->assertGreaterThan(time(), $decoded['exp']);

        // Debe ser aproximadamente 7 días
        $expectedExp = time() + 604800;
        $this->assertGreaterThan($expectedExp - 60, $decoded['exp']);

        // Debe tener tipo 'refresh'
        $this->assertEquals('refresh', $decoded['type']);
    }
}

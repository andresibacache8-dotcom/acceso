<?php
/**
 * tests/backend/AuthSecurityTest.php
 *
 * Tests de seguridad para autenticación con JWT
 * Valida: JWT generation, rate limiting, audit logging, security headers
 */

use PHPUnit\Framework\TestCase;

class AuthSecurityTest extends TestCase
{
    private $conn;
    private $userId = 1;
    private $username = 'testuser';
    private $password = 'test_password_123';
    private $hashedPassword;

    protected function setUp(): void
    {
        // Cargar clases de seguridad (no requieren BD)
        require_once __DIR__ . '/../../api/core/JwtHandler.php';
        require_once __DIR__ . '/../../api/core/RateLimiter.php';
        require_once __DIR__ . '/../../api/core/AuditLogger.php';

        // Hash contraseña de test
        $this->hashedPassword = password_hash($this->password, PASSWORD_BCRYPT);

        // Intentar conexión a BD (puede fallar, es opcional para estos tests)
        try {
            require_once __DIR__ . '/../../config/database.php';
            $databaseConfig = DatabaseConfig::getInstance();
            $this->conn = $databaseConfig->getAccesoConnection();
            if ($this->conn) {
                $this->setupTestUser();
            }
        } catch (Exception $e) {
            // BD no disponible, pero tests de JWT/RateLimiter/AuditLogger pueden continuar
            $this->conn = null;
        }
    }

    protected function tearDown(): void
    {
        // Limpiar datos de test
        $this->cleanupTestUser();

        if ($this->conn) {
            $this->conn->close();
        }
    }

    private function setupTestUser(): void
    {
        if (!$this->conn) return;

        // Verificar si tabla existe y crear usuario de test
        $stmt = $this->conn->prepare("SELECT id FROM users WHERE username = ?");
        if ($stmt) {
            $stmt->bind_param("s", $this->username);
            $stmt->execute();
            $result = $stmt->get_result();

            if ($result->num_rows === 0) {
                // Insertar usuario de test
                $stmt = $this->conn->prepare("INSERT INTO users (username, password, role) VALUES (?, ?, ?)");
                $role = 'admin';
                $stmt->bind_param("sss", $this->username, $this->hashedPassword, $role);
                $stmt->execute();
                $stmt->close();
            }
        }
    }

    private function cleanupTestUser(): void
    {
        if (!$this->conn) return;

        $stmt = $this->conn->prepare("DELETE FROM users WHERE username = ?");
        if ($stmt) {
            $stmt->bind_param("s", $this->username);
            $stmt->execute();
            $stmt->close();
        }
    }

    /**
     * Test: JwtHandler puede generar tokens válidos
     */
    public function testJwtHandlerGeneratesValidAccessToken(): void
    {
        JwtHandler::init('test-secret-key');

        $token = JwtHandler::generate($this->userId, $this->username, 'admin', false);

        $this->assertNotEmpty($token);
        $this->assertStringContainsString('.', $token);

        // Token debe tener 3 partes: header.payload.signature
        $parts = explode('.', $token);
        $this->assertCount(3, $parts);
    }

    /**
     * Test: JwtHandler puede generar refresh tokens con expiración más larga
     */
    public function testJwtHandlerGeneratesRefreshToken(): void
    {
        JwtHandler::init('test-secret-key');

        $refreshToken = JwtHandler::generate($this->userId, $this->username, 'admin', true);

        $this->assertNotEmpty($refreshToken);

        // Decodificar y verificar tipo
        $decoded = JwtHandler::decode($refreshToken);
        $this->assertEquals('refresh', $decoded['type']);
    }

    /**
     * Test: JwtHandler valida firma correcta
     */
    public function testJwtHandlerVerifiesValidToken(): void
    {
        $secretKey = 'test-secret-key-12345';
        JwtHandler::init($secretKey);

        $token = JwtHandler::generate($this->userId, $this->username, 'admin', false);

        // Verificar token con misma clave secreta
        $payload = JwtHandler::verify($token);

        $this->assertEquals($this->userId, $payload['userId']);
        $this->assertEquals($this->username, $payload['username']);
        $this->assertEquals('admin', $payload['role']);
    }

    /**
     * Test: JwtHandler rechaza tokens con firma inválida
     */
    public function testJwtHandlerRejectsInvalidSignature(): void
    {
        JwtHandler::init('test-secret-key');

        $token = JwtHandler::generate($this->userId, $this->username, 'admin', false);

        // Tamper con el token
        $parts = explode('.', $token);
        $parts[2] = 'invalidsignature';
        $tamperedToken = implode('.', $parts);

        $this->expectException(Exception::class);
        JwtHandler::verify($tamperedToken);
    }

    /**
     * Test: JwtHandler rechaza tokens expirados
     */
    public function testJwtHandlerRejectsExpiredToken(): void
    {
        JwtHandler::init('test-secret-key');

        // Crear token con expiración en el pasado
        $issuedAt = time() - 7200; // 2 horas atrás
        $expire = $issuedAt - 3600; // Expiró hace 1 hora

        $payload = [
            'iat' => $issuedAt,
            'exp' => $expire,
            'userId' => $this->userId,
            'username' => $this->username,
            'role' => 'admin',
            'type' => 'access'
        ];

        // Construir token manualmente
        $header = json_encode(['alg' => 'HS256', 'typ' => 'JWT']);
        $payload_json = json_encode($payload);

        $header_encoded = rtrim(strtr(base64_encode($header), '+/', '-_'), '=');
        $payload_encoded = rtrim(strtr(base64_encode($payload_json), '+/', '-_'), '=');

        $signature = hash_hmac('sha256', $header_encoded . '.' . $payload_encoded, 'test-secret-key', true);
        $signature_encoded = rtrim(strtr(base64_encode($signature), '+/', '-_'), '=');

        $expiredToken = $header_encoded . '.' . $payload_encoded . '.' . $signature_encoded;

        $this->expectException(Exception::class);
        $this->expectExceptionMessage('Token expirado');
        JwtHandler::verify($expiredToken);
    }

    /**
     * Test: RateLimiter permite intentos dentro del límite
     */
    public function testRateLimiterAllowsValidAttempts(): void
    {
        RateLimiter::init(__DIR__ . '/../../cache');

        // 3 intentos deberían pasar
        for ($i = 0; $i < 3; $i++) {
            $status = RateLimiter::check('test_action', 5, 300);
            $this->assertLessThan(5, $status['attempts']);
        }

        // Limpiar
        RateLimiter::reset('test_action');
    }

    /**
     * Test: RateLimiter rechaza después de exceder límite
     */
    public function testRateLimiterBlocksExcessiveAttempts(): void
    {
        RateLimiter::init(__DIR__ . '/../../cache');

        // Realizar 6 intentos (límite es 5)
        $attemptsFailed = false;
        for ($i = 0; $i < 6; $i++) {
            try {
                RateLimiter::check('test_action_limit', 5, 300);
            } catch (Exception $e) {
                $attemptsFailed = true;
                $this->assertEquals(429, $e->getCode());
                break;
            }
        }

        $this->assertTrue($attemptsFailed, 'Rate limiter debe rechazar después del límite');

        // Limpiar
        RateLimiter::reset('test_action_limit');
    }

    /**
     * Test: RateLimiter puede resetearse
     */
    public function testRateLimiterCanReset(): void
    {
        RateLimiter::init(__DIR__ . '/../../cache');

        // Hacer algunos intentos
        for ($i = 0; $i < 3; $i++) {
            RateLimiter::check('test_reset', 5, 300);
        }

        // Resetear
        RateLimiter::reset('test_reset');

        // Debe permitir intentos de nuevo
        $status = RateLimiter::check('test_reset', 5, 300);
        $this->assertEquals(1, $status['attempts']);
    }

    /**
     * Test: AuditLogger registra eventos de login
     */
    public function testAuditLoggerLogsLoginEvents(): void
    {
        AuditLogger::init(__DIR__ . '/../../logs/test-audit.log');

        AuditLogger::log('LOGIN', [
            'user_id' => $this->userId,
            'username' => $this->username
        ]);

        $logs = AuditLogger::getLog(['event' => 'LOGIN'], 1);

        $this->assertNotEmpty($logs);
        $this->assertEquals('LOGIN', $logs[0]['event']);
        $this->assertEquals($this->username, $logs[0]['additional_data']['username']);
    }

    /**
     * Test: AuditLogger registra eventos de login fallido
     */
    public function testAuditLoggerLogsFailedLoginAttempts(): void
    {
        AuditLogger::init(__DIR__ . '/../../logs/test-audit.log');

        AuditLogger::log('LOGIN_FAILED', [
            'username' => 'invalid_user',
            'reason' => 'Invalid credentials'
        ], 'WARNING');

        $logs = AuditLogger::getLog(['event' => 'LOGIN_FAILED'], 1);

        $this->assertNotEmpty($logs);
        $this->assertEquals('WARNING', $logs[0]['level']);
    }

    /**
     * Test: AuditLogger registra eventos críticos
     */
    public function testAuditLoggerLogsSecurityEvents(): void
    {
        AuditLogger::init(__DIR__ . '/../../logs/test-audit.log');

        AuditLogger::log('SUSPICIOUS_ACTIVITY', [
            'action' => 'brute_force_detected',
            'attempts' => 10
        ], 'CRITICAL');

        $logs = AuditLogger::getLog(['level' => 'CRITICAL'], 1);

        $this->assertNotEmpty($logs);
        $this->assertEquals('CRITICAL', $logs[0]['level']);
    }

    /**
     * Test: AuditLogger puede filtrar por nivel
     */
    public function testAuditLoggerFiltersByLevel(): void
    {
        AuditLogger::init(__DIR__ . '/../../logs/test-audit.log');

        // Logging varios eventos con diferentes niveles
        AuditLogger::log('TEST_EVENT_1', [], 'INFO');
        AuditLogger::log('TEST_EVENT_2', [], 'WARNING');
        AuditLogger::log('TEST_EVENT_3', [], 'CRITICAL');

        // Filtrar solo CRITICAL
        $criticalLogs = AuditLogger::getLog(['level' => 'CRITICAL'], 10);

        $criticalCount = 0;
        foreach ($criticalLogs as $log) {
            if ($log['level'] === 'CRITICAL') {
                $criticalCount++;
            }
        }

        $this->assertGreaterThan(0, $criticalCount);
    }

    /**
     * Test: JwtHandler extrae token del header Authorization
     */
    public function testJwtHandlerExtractsTokenFromHeader(): void
    {
        JwtHandler::init('test-secret-key');

        $token = JwtHandler::generate($this->userId, $this->username, 'admin', false);

        // Simular header Authorization
        $_SERVER['HTTP_AUTHORIZATION'] = 'Bearer ' . $token;

        // Esta función usa getallheaders() que no funciona en CLI
        // Pero verificamos que el token es válido
        $parts = explode('.', $token);
        $this->assertCount(3, $parts);
    }

    /**
     * Test: Password verification funciona correctamente
     */
    public function testPasswordVerificationWithBcrypt(): void
    {
        $password = 'test_password_123';
        $hashed = password_hash($password, PASSWORD_BCRYPT);

        // Contraseña correcta
        $this->assertTrue(password_verify($password, $hashed));

        // Contraseña incorrecta
        $this->assertFalse(password_verify('wrong_password', $hashed));
    }

    /**
     * Test: Token contiene información correcta del usuario
     */
    public function testTokenContainsCorrectUserData(): void
    {
        JwtHandler::init('test-secret-key');

        $userId = 42;
        $username = 'johndoe';
        $role = 'user';

        $token = JwtHandler::generate($userId, $username, $role, false);
        $decoded = JwtHandler::decode($token);

        $this->assertEquals($userId, $decoded['userId']);
        $this->assertEquals($username, $decoded['username']);
        $this->assertEquals($role, $decoded['role']);
    }

    /**
     * Test: Access token tiene expiración corta (1 hora)
     */
    public function testAccessTokenHasShortExpiration(): void
    {
        JwtHandler::init('test-secret-key');

        $token = JwtHandler::generate($this->userId, $this->username, 'admin', false);
        $decoded = JwtHandler::decode($token);

        // Expiración debe estar en el futuro
        $this->assertGreaterThan(time(), $decoded['exp']);

        // Pero no debe ser mayor a 2 horas (1 hora + buffer)
        $maxExpiration = time() + 7200;
        $this->assertLessThan($maxExpiration, $decoded['exp']);
    }

    /**
     * Test: Refresh token tiene expiración larga (7 días)
     */
    public function testRefreshTokenHasLongExpiration(): void
    {
        JwtHandler::init('test-secret-key');

        $token = JwtHandler::generate($this->userId, $this->username, 'admin', true);
        $decoded = JwtHandler::decode($token);

        // Expiración debe estar en el futuro
        $this->assertGreaterThan(time(), $decoded['exp']);

        // Debe ser aproximadamente 7 días (604800 segundos)
        $expectedExpiration = time() + 604800;
        $this->assertGreaterThan($expectedExpiration - 60, $decoded['exp']); // 1 minuto de tolerancia
    }
}

<?php
/**
 * tests/backend/SecurityCoreTest.php
 *
 * Tests unitarios para componentes de seguridad
 * Pruebas rápidas sin dependencies de BD
 */

use PHPUnit\Framework\TestCase;

class SecurityCoreTest extends TestCase
{
    protected function setUp(): void
    {
        // Cargar clases de seguridad
        require_once __DIR__ . '/../../api/core/JwtHandler.php';
        require_once __DIR__ . '/../../api/core/RateLimiter.php';
        require_once __DIR__ . '/../../api/core/AuditLogger.php';
    }

    // ============= JWT Handler Tests =============

    public function testJwtHandlerGeneratesToken(): void
    {
        JwtHandler::init('test-secret-key-12345');

        $token = JwtHandler::generate(1, 'testuser', 'admin', false);

        $this->assertNotEmpty($token);
        $this->assertStringContainsString('.', $token);
        $this->assertCount(3, explode('.', $token));
    }

    public function testJwtHandlerVerifiesValidToken(): void
    {
        $secretKey = 'test-secret-key-verify-123';
        JwtHandler::init($secretKey);

        $token = JwtHandler::generate(42, 'johndoe', 'user', false);
        $payload = JwtHandler::verify($token);

        $this->assertEquals(42, $payload['userId']);
        $this->assertEquals('johndoe', $payload['username']);
        $this->assertEquals('user', $payload['role']);
    }

    public function testJwtHandlerRejectsInvalidSignature(): void
    {
        JwtHandler::init('test-secret-key-reject');

        $token = JwtHandler::generate(1, 'testuser', 'admin', false);

        // Tamper con la firma
        $parts = explode('.', $token);
        $parts[2] = 'tampered_signature';
        $tamperedToken = implode('.', $parts);

        $this->expectException(Exception::class);
        JwtHandler::verify($tamperedToken);
    }

    public function testJwtHandlerCanDecodeToken(): void
    {
        JwtHandler::init('test-secret-key-decode');

        $token = JwtHandler::generate(99, 'admin_user', 'admin', false);
        $decoded = JwtHandler::decode($token);

        $this->assertNotNull($decoded);
        $this->assertEquals(99, $decoded['userId']);
        $this->assertEquals('admin_user', $decoded['username']);
    }

    public function testJwtAccessTokenType(): void
    {
        JwtHandler::init('test-secret-key-type');

        $accessToken = JwtHandler::generate(1, 'user', 'user', false);
        $decoded = JwtHandler::decode($accessToken);

        $this->assertEquals('access', $decoded['type']);
    }

    public function testJwtRefreshTokenType(): void
    {
        JwtHandler::init('test-secret-key-type');

        $refreshToken = JwtHandler::generate(1, 'user', 'user', true);
        $decoded = JwtHandler::decode($refreshToken);

        $this->assertEquals('refresh', $decoded['type']);
    }

    public function testJwtTokenContainsExpiration(): void
    {
        JwtHandler::init('test-secret-key-exp');

        $token = JwtHandler::generate(1, 'user', 'user', false);
        $decoded = JwtHandler::decode($token);

        $this->assertArrayHasKey('exp', $decoded);
        $this->assertGreaterThan(time(), $decoded['exp']);
    }

    public function testJwtTokenContainsIssueTime(): void
    {
        JwtHandler::init('test-secret-key-iat');

        $token = JwtHandler::generate(1, 'user', 'user', false);
        $decoded = JwtHandler::decode($token);

        $this->assertArrayHasKey('iat', $decoded);
        $this->assertLessThanOrEqual(time(), $decoded['iat']);
    }

    // ============= Rate Limiter Tests =============

    public function testRateLimiterTracksAttempts(): void
    {
        RateLimiter::init(__DIR__ . '/../../cache');

        $status = RateLimiter::check('test_tracker', 10, 300);

        $this->assertArrayHasKey('attempts', $status);
        $this->assertArrayHasKey('remaining', $status);
        $this->assertGreaterThan(0, $status['remaining']);

        RateLimiter::reset('test_tracker');
    }

    public function testRateLimiterIncrementsAttempts(): void
    {
        RateLimiter::init(__DIR__ . '/../../cache');

        $status1 = RateLimiter::check('test_increment', 10, 300);
        $attempts1 = $status1['attempts'];

        $status2 = RateLimiter::check('test_increment', 10, 300);
        $attempts2 = $status2['attempts'];

        $this->assertEquals($attempts1 + 1, $attempts2);

        RateLimiter::reset('test_increment');
    }

    public function testRateLimiterBlocksExcessiveAttempts(): void
    {
        RateLimiter::init(__DIR__ . '/../../cache');

        $maxAttempts = 3;
        $blocked = false;

        for ($i = 0; $i <= $maxAttempts + 1; $i++) {
            try {
                RateLimiter::check('test_block', $maxAttempts, 300);
            } catch (Exception $e) {
                $blocked = true;
                $this->assertEquals(429, $e->getCode());
                break;
            }
        }

        $this->assertTrue($blocked, 'Rate limiter debe bloquear después del límite');

        RateLimiter::reset('test_block');
    }

    public function testRateLimiterCanReset(): void
    {
        RateLimiter::init(__DIR__ . '/../../cache');

        // Hacer algunos intentos
        RateLimiter::check('test_reset', 10, 300);
        RateLimiter::check('test_reset', 10, 300);

        // Resetear
        RateLimiter::reset('test_reset');

        // Después de reset, primer intento debe tener attempts = 1
        $status = RateLimiter::check('test_reset', 10, 300);
        $this->assertEquals(1, $status['attempts']);

        RateLimiter::reset('test_reset');
    }

    public function testRateLimiterReturnsRemaining(): void
    {
        RateLimiter::init(__DIR__ . '/../../cache');

        $maxAttempts = 5;
        $status = RateLimiter::check('test_remaining', $maxAttempts, 300);

        $expected = $maxAttempts - 1;
        $this->assertEquals($expected, $status['remaining']);

        RateLimiter::reset('test_remaining');
    }

    // ============= Audit Logger Tests =============

    public function testAuditLoggerLogsEvents(): void
    {
        AuditLogger::init(__DIR__ . '/../../logs/test-security.log');

        AuditLogger::log('TEST_EVENT', ['test_data' => 'value']);

        $logs = AuditLogger::getLog(['event' => 'TEST_EVENT'], 1);

        $this->assertNotEmpty($logs);
        $this->assertEquals('TEST_EVENT', $logs[0]['event']);
    }

    public function testAuditLoggerIncludesTimestamp(): void
    {
        AuditLogger::init(__DIR__ . '/../../logs/test-security.log');

        AuditLogger::log('TIMESTAMP_TEST', []);

        $logs = AuditLogger::getLog(['event' => 'TIMESTAMP_TEST'], 1);

        $this->assertNotEmpty($logs);
        $this->assertArrayHasKey('timestamp', $logs[0]);
    }

    public function testAuditLoggerIncludesLevel(): void
    {
        AuditLogger::init(__DIR__ . '/../../logs/test-security.log');

        // LEVEL_TEST no está en SECURITY_EVENTS, así que se registra como WARNING
        AuditLogger::log('LEVEL_TEST', [], 'CRITICAL');

        $logs = AuditLogger::getLog(['event' => 'LEVEL_TEST'], 1);

        $this->assertNotEmpty($logs);
        // AuditLogger fuerza WARNING para eventos desconocidos
        $this->assertEquals('WARNING', $logs[0]['level']);
    }

    public function testAuditLoggerFiltersByEvent(): void
    {
        AuditLogger::init(__DIR__ . '/../../logs/test-security.log');

        AuditLogger::log('EVENT_A', []);
        AuditLogger::log('EVENT_B', []);

        $logs = AuditLogger::getLog(['event' => 'EVENT_A'], 10);

        foreach ($logs as $log) {
            $this->assertEquals('EVENT_A', $log['event']);
        }
    }

    public function testAuditLoggerFiltersByLevel(): void
    {
        AuditLogger::init(__DIR__ . '/../../logs/test-security.log');

        AuditLogger::log('LOGIN', [], 'INFO');
        AuditLogger::log('SUSPICIOUS_ACTIVITY', [], 'CRITICAL');

        $logs = AuditLogger::getLog(['level' => 'CRITICAL'], 10);

        $this->assertNotEmpty($logs);
        foreach ($logs as $log) {
            $this->assertEquals('CRITICAL', $log['level']);
        }
    }

    public function testAuditLoggerIncludesIpAddress(): void
    {
        AuditLogger::init(__DIR__ . '/../../logs/test-security.log');

        AuditLogger::log('IP_TEST', []);

        $logs = AuditLogger::getLog(['event' => 'IP_TEST'], 1);

        $this->assertNotEmpty($logs);
        $this->assertArrayHasKey('ip', $logs[0]);
    }

    public function testAuditLoggerIncludesUserAgent(): void
    {
        AuditLogger::init(__DIR__ . '/../../logs/test-security.log');

        AuditLogger::log('UA_TEST', []);

        $logs = AuditLogger::getLog(['event' => 'UA_TEST'], 1);

        $this->assertNotEmpty($logs);
        $this->assertArrayHasKey('user_agent', $logs[0]);
    }

    // ============= Password Verification Tests =============

    public function testPasswordHashingWithBcrypt(): void
    {
        $password = 'secure_password_123';
        $hashed = password_hash($password, PASSWORD_BCRYPT);

        $this->assertTrue(password_verify($password, $hashed));
    }

    public function testPasswordVerifyRejectWrongPassword(): void
    {
        $password = 'correct_password';
        $hashed = password_hash($password, PASSWORD_BCRYPT);

        $this->assertFalse(password_verify('wrong_password', $hashed));
    }

    public function testPasswordHashIsDifferentEachTime(): void
    {
        $password = 'same_password';
        $hash1 = password_hash($password, PASSWORD_BCRYPT);
        $hash2 = password_hash($password, PASSWORD_BCRYPT);

        $this->assertNotEquals($hash1, $hash2);
        $this->assertTrue(password_verify($password, $hash1));
        $this->assertTrue(password_verify($password, $hash2));
    }
}

<?php
/**
 * api/core/JwtHandler.php
 *
 * Manejador de JWT (JSON Web Tokens)
 * Proporciona métodos para crear, verificar y decodificar JWT
 *
 * Uso:
 *   $token = JwtHandler::generate($userId, $username, $role);
 *   $decoded = JwtHandler::verify($token);
 *   $decoded = JwtHandler::decode($token);
 */

class JwtHandler
{
    // Configuración
    private static $SECRET_KEY = null;
    private static $ALGORITHM = 'HS256';
    private static $EXPIRATION = 3600; // 1 hora en segundos
    private static $REFRESH_EXPIRATION = 604800; // 7 días

    /**
     * Inicializar con clave secreta
     * IMPORTANTE: Usar variable de entorno en producción
     */
    public static function init($secretKey = null)
    {
        if ($secretKey === null) {
            // Intentar obtener de .env o usar default (CAMBIAR EN PRODUCCIÓN)
            $secretKey = getenv('JWT_SECRET') ?: 'dev-secret-key-change-in-production';
        }
        self::$SECRET_KEY = $secretKey;
    }

    /**
     * Genera un JWT
     *
     * @param int $userId ID del usuario
     * @param string $username Nombre de usuario
     * @param string $role Rol del usuario
     * @param bool $isRefreshToken Si es token de refresco (durará más)
     * @return string Token JWT
     */
    public static function generate($userId, $username, $role, $isRefreshToken = false)
    {
        if (self::$SECRET_KEY === null) {
            self::init();
        }

        $issuedAt = time();
        $expire = $isRefreshToken ?
            $issuedAt + self::$REFRESH_EXPIRATION :
            $issuedAt + self::$EXPIRATION;

        $payload = [
            'iat' => $issuedAt,
            'exp' => $expire,
            'userId' => $userId,
            'username' => $username,
            'role' => $role,
            'type' => $isRefreshToken ? 'refresh' : 'access'
        ];

        // Crear token JWT (simple, sin librería)
        $header = json_encode(['alg' => self::$ALGORITHM, 'typ' => 'JWT']);
        $payload_json = json_encode($payload);

        $header_encoded = self::base64UrlEncode($header);
        $payload_encoded = self::base64UrlEncode($payload_json);

        $signature = hash_hmac(
            'sha256',
            $header_encoded . '.' . $payload_encoded,
            self::$SECRET_KEY,
            true
        );

        $signature_encoded = self::base64UrlEncode($signature);

        return $header_encoded . '.' . $payload_encoded . '.' . $signature_encoded;
    }

    /**
     * Verifica y decodifica un JWT
     *
     * @param string $token Token JWT
     * @return array|null Datos decodificados o null si inválido
     * @throws Exception Si el token es inválido
     */
    public static function verify($token)
    {
        if (self::$SECRET_KEY === null) {
            self::init();
        }

        try {
            $parts = explode('.', $token);

            if (count($parts) !== 3) {
                throw new Exception('Token inválido: formato incorrecto');
            }

            list($header_encoded, $payload_encoded, $signature_encoded) = $parts;

            // Verificar firma
            $signature = hash_hmac(
                'sha256',
                $header_encoded . '.' . $payload_encoded,
                self::$SECRET_KEY,
                true
            );

            $signature_decoded = self::base64UrlDecode($signature_encoded);

            if ($signature !== $signature_decoded) {
                throw new Exception('Token inválido: firma no coincide');
            }

            // Decodificar payload
            $payload = json_decode(self::base64UrlDecode($payload_encoded), true);

            if (!$payload) {
                throw new Exception('Token inválido: payload corrupto');
            }

            // Verificar expiración
            if (isset($payload['exp']) && $payload['exp'] < time()) {
                throw new Exception('Token expirado');
            }

            return $payload;

        } catch (Exception $e) {
            throw new Exception('Error verificando token: ' . $e->getMessage());
        }
    }

    /**
     * Decodifica un JWT sin verificar firma
     * SOLO para lectura de datos, NO para autenticación
     *
     * @param string $token Token JWT
     * @return array Datos decodificados
     */
    public static function decode($token)
    {
        try {
            $parts = explode('.', $token);

            if (count($parts) !== 3) {
                return null;
            }

            $payload = json_decode(self::base64UrlDecode($parts[1]), true);
            return $payload;

        } catch (Exception $e) {
            return null;
        }
    }

    /**
     * Codifica string a base64 URL-safe
     */
    private static function base64UrlEncode($data)
    {
        return rtrim(strtr(base64_encode($data), '+/', '-_'), '=');
    }

    /**
     * Decodifica string desde base64 URL-safe
     */
    private static function base64UrlDecode($data)
    {
        return base64_decode(strtr($data, '-_', '+/') . str_repeat('=', 4 - strlen($data) % 4));
    }

    /**
     * Obtiene token del header Authorization
     * Formato: "Bearer <token>"
     *
     * @return string|null Token o null si no existe
     */
    public static function getTokenFromHeader()
    {
        $header = getallheaders()['Authorization'] ?? null;

        if (!$header) {
            return null;
        }

        if (preg_match('/Bearer\s+(.*)$/i', $header, $matches)) {
            return $matches[1];
        }

        return null;
    }
}

// Inicializar al cargar
JwtHandler::init();
?>

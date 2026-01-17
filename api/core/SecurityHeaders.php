<?php
/**
 * api/core/SecurityHeaders.php
 *
 * Configuración de headers de seguridad HTTP
 * Protege contra XSS, CSRF, clickjacking, y otros ataques
 *
 * Uso:
 *   SecurityHeaders::apply();  // Aplicar todos los headers
 */

class SecurityHeaders
{
    /**
     * Aplicar todos los security headers
     */
    public static function apply()
    {
        // Prevenir clickjacking
        header('X-Frame-Options: DENY');

        // Prevenir MIME sniffing
        header('X-Content-Type-Options: nosniff');

        // Habilitar XSS protection en navegadores antiguos
        header('X-XSS-Protection: 1; mode=block');

        // Content Security Policy (CSP)
        // Restringir fuentes de contenido
        header("Content-Security-Policy: default-src 'self'; script-src 'self'; style-src 'self' 'unsafe-inline'; img-src 'self' data: https:; font-src 'self'");

        // Prevenir referrer leaks
        header('Referrer-Policy: strict-origin-when-cross-origin');

        // Habilitar HSTS (solo en HTTPS)
        if (self::isHttps()) {
            header('Strict-Transport-Security: max-age=31536000; includeSubDomains; preload');
        }

        // Permissions Policy (antes Feature-Policy)
        header('Permissions-Policy: geolocation=(), microphone=(), camera=(), payment=()');

        // Remover header "Server" que expone versión
        header_remove('Server');
        header('Server: SCAD/1.0');

        // X-Powered-By - No exponer tecnología
        header_remove('X-Powered-By');
    }

    /**
     * Verificar si está en HTTPS
     */
    private static function isHttps()
    {
        return (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ||
               (isset($_SERVER['SERVER_PORT']) && $_SERVER['SERVER_PORT'] == 443);
    }

    /**
     * Aplicar headers específicos de API
     */
    public static function applyApiHeaders()
    {
        header('Content-Type: application/json; charset=utf-8');
        header('X-Content-Type-Options: nosniff');

        // CORS headers (configurar según necesidad)
        $allowed_origins = [
            'http://localhost',
            'http://localhost:3000',
            'https://yourdomain.com'
        ];

        $origin = $_SERVER['HTTP_ORIGIN'] ?? null;
        if ($origin && in_array($origin, $allowed_origins)) {
            header('Access-Control-Allow-Origin: ' . $origin);
            header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS');
            header('Access-Control-Allow-Headers: Content-Type, Authorization');
            header('Access-Control-Max-Age: 3600');
            header('Access-Control-Allow-Credentials: true');
        }

        // Aplicar otros headers
        self::apply();
    }

    /**
     * Manejar preflight CORS
     */
    public static function handleCors()
    {
        if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
            http_response_code(200);
            exit();
        }
    }
}

// Aplicar headers al cargar
SecurityHeaders::applyApiHeaders();
?>

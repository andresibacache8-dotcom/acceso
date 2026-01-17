<?php
/**
 * api/core/RateLimiter.php
 *
 * Rate limiting para proteger contra ataques de fuerza bruta
 * Limita número de solicitudes por IP
 *
 * Uso:
 *   RateLimiter::check('login', 5, 300);  // Max 5 intentos en 5 minutos
 *   RateLimiter::check('api', 100, 60);   // Max 100 requests por minuto
 */

class RateLimiter
{
    private static $storage = null;

    /**
     * Inicializar almacenamiento
     */
    public static function init($storageDir = null)
    {
        if ($storageDir === null) {
            $storageDir = __DIR__ . '/../../cache';
            if (!is_dir($storageDir)) {
                mkdir($storageDir, 0755, true);
            }
        }

        self::$storage = $storageDir;
    }

    /**
     * Verificar límite de rate
     *
     * @param string $action Acción a limitar (ej: 'login', 'api')
     * @param int $maxAttempts Máximo de intentos
     * @param int $timeWindow Ventana de tiempo en segundos
     * @param string $identifier Identificador (por defecto IP)
     * @throws Exception Si se excede el límite
     * @return array Estado actual
     */
    public static function check($action, $maxAttempts = 10, $timeWindow = 60, $identifier = null)
    {
        if (self::$storage === null) {
            self::init();
        }

        // Usar IP como identificador por defecto
        if ($identifier === null) {
            $identifier = $_SERVER['REMOTE_ADDR'] ?? 'unknown';
        }

        // Crear key única
        $key = hash('sha256', $action . ':' . $identifier);
        $file = self::$storage . '/rate_' . $key . '.json';

        // Leer datos actuales
        $now = time();
        $data = [
            'attempts' => 0,
            'first_attempt' => $now,
            'last_attempt' => $now,
            'blocked_until' => 0
        ];

        if (file_exists($file)) {
            $content = json_decode(file_get_contents($file), true);
            if ($content && $content['first_attempt'] + $timeWindow > $now) {
                // Ventana de tiempo aún vigente
                $data = $content;
            }
        }

        // Incrementar intentos
        $data['attempts']++;
        $data['last_attempt'] = $now;

        // Guardar
        file_put_contents($file, json_encode($data), LOCK_EX);

        // Verificar límite
        if ($data['attempts'] > $maxAttempts) {
            // Bloquear por 15 minutos
            $data['blocked_until'] = $now + 900;
            file_put_contents($file, json_encode($data), LOCK_EX);

            require_once __DIR__ . '/AuditLogger.php';
            AuditLogger::log('SUSPICIOUS_ACTIVITY', [
                'action' => $action,
                'attempts' => $data['attempts'],
                'max_allowed' => $maxAttempts
            ], 'CRITICAL');

            throw new Exception(
                'Demasiados intentos. Intenta más tarde.',
                429
            );
        }

        return [
            'attempts' => $data['attempts'],
            'remaining' => max(0, $maxAttempts - $data['attempts']),
            'reset_in' => max(0, $data['first_attempt'] + $timeWindow - $now)
        ];
    }

    /**
     * Obtener estado del rate limit
     *
     * @param string $action Acción
     * @param string $identifier Identificador (por defecto IP)
     * @return array Estado
     */
    public static function getStatus($action, $identifier = null)
    {
        if (self::$storage === null) {
            self::init();
        }

        if ($identifier === null) {
            $identifier = $_SERVER['REMOTE_ADDR'] ?? 'unknown';
        }

        $key = hash('sha256', $action . ':' . $identifier);
        $file = self::$storage . '/rate_' . $key . '.json';

        if (!file_exists($file)) {
            return null;
        }

        return json_decode(file_get_contents($file), true);
    }

    /**
     * Reset rate limit
     *
     * @param string $action Acción
     * @param string $identifier Identificador
     */
    public static function reset($action, $identifier = null)
    {
        if (self::$storage === null) {
            self::init();
        }

        if ($identifier === null) {
            $identifier = $_SERVER['REMOTE_ADDR'] ?? 'unknown';
        }

        $key = hash('sha256', $action . ':' . $identifier);
        $file = self::$storage . '/rate_' . $key . '.json';

        if (file_exists($file)) {
            unlink($file);
        }
    }

    /**
     * Limpiar archivos de caché antiguos
     *
     * @param int $hoursToKeep Mantener archivos de últimas N horas
     */
    public static function cleanup($hoursToKeep = 24)
    {
        if (self::$storage === null || !is_dir(self::$storage)) {
            return;
        }

        $cutoff = time() - ($hoursToKeep * 3600);

        foreach (glob(self::$storage . '/rate_*.json') as $file) {
            if (filemtime($file) < $cutoff) {
                unlink($file);
            }
        }
    }
}

// Inicializar
RateLimiter::init();
?>

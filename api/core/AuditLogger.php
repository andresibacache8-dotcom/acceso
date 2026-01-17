<?php
/**
 * api/core/AuditLogger.php
 *
 * Sistema de auditoría
 * Registra todas las acciones importantes para seguridad
 *
 * Uso:
 *   AuditLogger::log('LOGIN', ['user_id' => 1, 'ip' => '127.0.0.1']);
 *   AuditLogger::log('DELETE_USER', ['target_user_id' => 5]);
 *   AuditLogger::getLog($filtros);
 */

class AuditLogger
{
    // Archivo de log
    private static $logFile = null;

    // Eventos críticos de seguridad
    private static $SECURITY_EVENTS = [
        'LOGIN',              // Login exitoso
        'LOGIN_FAILED',       // Login fallido
        'AUTH_FAILED',        // Token inválido
        'AUTH_FORBIDDEN',     // Acceso denegado
        'PERMISSION_DENIED',  // Permiso denegado
        'DATA_ACCESS',        // Acceso a datos
        'DATA_MODIFIED',      // Datos modificados
        'DATA_DELETED',       // Datos eliminados
        'USER_CREATED',       // Nuevo usuario
        'USER_DELETED',       // Usuario eliminado
        'ROLE_CHANGED',       // Cambio de rol
        'PASSWORD_CHANGED',   // Cambio de contraseña
        'SUSPICIOUS_ACTIVITY' // Actividad sospechosa
    ];

    /**
     * Inicializar archivo de log
     */
    public static function init($logFile = null)
    {
        if ($logFile === null) {
            $logDir = __DIR__ . '/../../logs';
            if (!is_dir($logDir)) {
                mkdir($logDir, 0755, true);
            }
            $logFile = $logDir . '/audit-' . date('Y-m-d') . '.log';
        }

        self::$logFile = $logFile;

        // Crear archivo si no existe
        if (!file_exists($logFile)) {
            touch($logFile);
            chmod($logFile, 0600); // Solo lectura/escritura para propietario
        }
    }

    /**
     * Registrar evento de auditoría
     *
     * @param string $event Tipo de evento
     * @param array $data Datos adicionales
     * @param string $level Log level (INFO, WARNING, CRITICAL)
     */
    public static function log($event, $data = [], $level = 'INFO')
    {
        if (self::$logFile === null) {
            self::init();
        }

        // Validar que sea un evento conocido
        if (!in_array($event, self::$SECURITY_EVENTS)) {
            $level = 'WARNING'; // Eventos desconocidos como warning
        }

        // Construir entrada de log
        $logEntry = [
            'timestamp' => date('Y-m-d H:i:s'),
            'level' => $level,
            'event' => $event,
            'ip' => $_SERVER['REMOTE_ADDR'] ?? 'unknown',
            'user_agent' => $_SERVER['HTTP_USER_AGENT'] ?? 'unknown',
            'user_id' => null,
            'additional_data' => $data
        ];

        // Intentar obtener user_id del AuthMiddleware si existe
        if (class_exists('AuthMiddleware') && AuthMiddleware::isAuthenticated()) {
            $logEntry['user_id'] = AuthMiddleware::getUserId();
        }

        // Escribir en archivo de log
        $logLine = json_encode($logEntry, JSON_UNESCAPED_UNICODE) . "\n";

        // Escribir de forma thread-safe
        $fp = fopen(self::$logFile, 'a');
        if ($fp) {
            flock($fp, LOCK_EX);
            fwrite($fp, $logLine);
            flock($fp, LOCK_UN);
            fclose($fp);
        }

        // También registrar eventos críticos en error_log
        if ($level === 'CRITICAL') {
            error_log($logLine);
        }
    }

    /**
     * Obtener logs filtrados
     *
     * @param array $filters Filtros: event, level, user_id, start_date, end_date
     * @param int $limit Límite de resultados
     * @return array Array de logs
     */
    public static function getLog($filters = [], $limit = 100)
    {
        if (self::$logFile === null || !file_exists(self::$logFile)) {
            return [];
        }

        $logs = [];
        $lines = file(self::$logFile);

        foreach (array_reverse($lines) as $line) {
            if (empty(trim($line))) continue;

            $entry = json_decode(trim($line), true);
            if (!$entry) continue;

            // Aplicar filtros
            if (!empty($filters['event']) && $entry['event'] !== $filters['event']) {
                continue;
            }
            if (!empty($filters['level']) && $entry['level'] !== $filters['level']) {
                continue;
            }
            if (!empty($filters['user_id']) && $entry['user_id'] !== $filters['user_id']) {
                continue;
            }

            $logs[] = $entry;

            if (count($logs) >= $limit) {
                break;
            }
        }

        return $logs;
    }

    /**
     * Limpiar logs antiguos
     *
     * @param int $daysToKeep Mantener logs de últimos N días
     */
    public static function cleanup($daysToKeep = 90)
    {
        $logDir = __DIR__ . '/../../logs';
        if (!is_dir($logDir)) return;

        $cutoffDate = date('Y-m-d', strtotime("-$daysToKeep days"));

        foreach (glob($logDir . '/audit-*.log') as $file) {
            // Extraer fecha del nombre de archivo
            if (preg_match('/audit-(\d{4}-\d{2}-\d{2})\.log/', $file, $matches)) {
                $fileDate = $matches[1];
                if ($fileDate < $cutoffDate) {
                    unlink($file);
                }
            }
        }
    }
}

// Inicializar al cargar
AuditLogger::init();
?>

<?php
/**
 * DatabaseConfig - Centralized Database Connection Manager
 *
 * Implements Singleton pattern to manage connections to both databases.
 * Eliminates hardcoded credentials from individual API files.
 *
 * Usage:
 *   $db = DatabaseConfig::getInstance();
 *   $conn = $db->getPersonalConnection();
 *   $conn = $db->getAccesoConnection();
 *
 * @package SCAD
 * @subpackage Config
 */

class DatabaseConfig
{
    /** @var DatabaseConfig Singleton instance */
    private static $instance = null;

    /** @var mysqli Connection to personal_db */
    private $conn_personal = null;

    /** @var mysqli Connection to acceso_pro_db */
    private $conn_acceso = null;

    /** @var array Configuration array */
    private $config = [];

    /**
     * Private constructor to prevent direct instantiation
     * Loads configuration and establishes database connections
     */
    private function __construct()
    {
        // Load configuration from config.php
        $configFile = __DIR__ . '/config.php';

        if (!file_exists($configFile)) {
            throw new Exception(
                "Configuration file not found: {$configFile}\n" .
                "Please copy config.example.php to config.php and update with your settings."
            );
        }

        $this->config = require_once $configFile;

        // Establish connections
        $this->establishConnections();
    }

    /**
     * Get singleton instance
     *
     * @return DatabaseConfig
     * @throws Exception
     */
    public static function getInstance()
    {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    /**
     * Establish database connections
     *
     * @throws Exception
     */
    private function establishConnections()
    {
        // Connect to personal_db
        $personalConfig = $this->config['database']['personal'];
        $this->conn_personal = new mysqli(
            $personalConfig['host'],
            $personalConfig['username'],
            $personalConfig['password'],
            $personalConfig['database'],
            $personalConfig['port'] ?? 3306
        );

        if ($this->conn_personal->connect_error) {
            throw new Exception(
                "Personal database connection failed: " . $this->conn_personal->connect_error
            );
        }

        $this->conn_personal->set_charset($personalConfig['charset'] ?? 'utf8');

        // Connect to acceso_pro_db
        $accesoConfig = $this->config['database']['acceso'];
        $this->conn_acceso = new mysqli(
            $accesoConfig['host'],
            $accesoConfig['username'],
            $accesoConfig['password'],
            $accesoConfig['database'],
            $accesoConfig['port'] ?? 3306
        );

        if ($this->conn_acceso->connect_error) {
            throw new Exception(
                "Acceso database connection failed: " . $this->conn_acceso->connect_error
            );
        }

        $this->conn_acceso->set_charset($accesoConfig['charset'] ?? 'utf8');
    }

    /**
     * Get connection to personal_db
     *
     * @return mysqli
     */
    public function getPersonalConnection()
    {
        if ($this->conn_personal === null || !$this->conn_personal->ping()) {
            throw new Exception("Personal database connection is not active");
        }
        return $this->conn_personal;
    }

    /**
     * Get connection to acceso_pro_db
     *
     * @return mysqli
     */
    public function getAccesoConnection()
    {
        if ($this->conn_acceso === null || !$this->conn_acceso->ping()) {
            throw new Exception("Acceso database connection is not active");
        }
        return $this->conn_acceso;
    }

    /**
     * Get configuration value
     *
     * @param string $key Dot notation key (e.g., 'database.personal.host')
     * @return mixed
     */
    public function getConfig($key)
    {
        $keys = explode('.', $key);
        $value = $this->config;

        foreach ($keys as $k) {
            if (isset($value[$k])) {
                $value = $value[$k];
            } else {
                return null;
            }
        }

        return $value;
    }

    /**
     * Close all connections
     *
     * Called automatically on shutdown via register_shutdown_function
     */
    public function closeConnections()
    {
        if ($this->conn_personal && $this->conn_personal->ping()) {
            $this->conn_personal->close();
        }
        if ($this->conn_acceso && $this->conn_acceso->ping()) {
            $this->conn_acceso->close();
        }
    }

    /**
     * Prevent cloning
     */
    private function __clone()
    {
    }

    /**
     * Prevent serialization
     */
    public function __sleep()
    {
        throw new Exception("Cannot serialize DatabaseConfig");
    }

    /**
     * Prevent unserialization
     */
    public function __wakeup()
    {
        throw new Exception("Cannot unserialize DatabaseConfig");
    }
}

// Register shutdown function to close connections gracefully
register_shutdown_function(function() {
    try {
        DatabaseConfig::getInstance()->closeConnections();
    } catch (Exception $e) {
        // Silently fail on shutdown
    }
});

// Convenience helper to get connections from other files
if (!function_exists('get_db_personal')) {
    function get_db_personal()
    {
        return DatabaseConfig::getInstance()->getPersonalConnection();
    }
}

if (!function_exists('get_db_acceso')) {
    function get_db_acceso()
    {
        return DatabaseConfig::getInstance()->getAccesoConnection();
    }
}
?>

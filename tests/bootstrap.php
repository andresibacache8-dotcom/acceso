<?php
/**
 * tests/bootstrap.php
 *
 * Bootstrap para tests PHPUnit
 * Carga las dependencias necesarias y configura el entorno de testing
 */

// Cargar autoloader de Composer
require_once __DIR__ . '/../vendor/autoload.php';

// Cargar configuraciones principales
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../api/core/ResponseHandler.php';

// Configurar entorno de testing
$_ENV['APP_ENV'] = 'testing';
putenv('APP_ENV=testing');

// Configurar reporte de errores para testing
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Iniciar sesión si es necesario para tests
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Definir ruta base de la aplicación
if (!defined('BASE_PATH')) {
    define('BASE_PATH', dirname(__DIR__));
}

if (!defined('API_PATH')) {
    define('API_PATH', BASE_PATH . '/api');
}

if (!defined('CONFIG_PATH')) {
    define('CONFIG_PATH', BASE_PATH . '/config');
}

// Suprimir headers en tests (para evitar "headers already sent")
if (!function_exists('headers_sent') || !headers_sent()) {
    // Solo se ejecuta si los headers no han sido enviados
}

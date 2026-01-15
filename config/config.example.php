<?php
/**
 * SCAD - Configuration Template
 *
 * This file is a template. Copy this to config.php and update with your environment settings.
 * IMPORTANT: config.php is in .gitignore to prevent credentials from being committed to version control.
 *
 * @package SCAD
 * @subpackage Config
 */

return [
    'environment' => 'development', // development, staging, production

    'database' => [
        'personal' => [
            'host' => 'localhost',
            'username' => 'root',
            'password' => '', // Empty password for local development
            'database' => 'personal_db',
            'charset' => 'utf8mb4',
            'port' => 3306
        ],
        'acceso' => [
            'host' => 'localhost',
            'username' => 'root',
            'password' => '', // Empty password for local development
            'database' => 'acceso_pro_db',
            'charset' => 'utf8mb4',
            'port' => 3306
        ]
    ],

    'api' => [
        'timeout' => 30000, // ms
        'debug' => true
    ],

    'logging' => [
        'enabled' => true,
        'level' => 'debug', // debug, info, warning, error
        'file' => __DIR__ . '/../logs/app.log'
    ]
];
?>

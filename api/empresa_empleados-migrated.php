<?php
/**
 * api/empresa_empleados-migrated.php
 * API para gestión de empleados de empresas asociadas
 *
 * Migración desde empresa_empleados.php original:
 * - Config: database/db_acceso.php → config/database.php
 * - Respuestas: echo json_encode() → ApiResponse::*()
 * - Estructura: métodos HTTP estándar
 *
 * Endpoints:
 * GET    /api/empresa_empleados.php                - Listar todos
 * GET    /api/empresa_empleados.php?empresa_id=1  - Listar por empresa
 * POST   /api/empresa_empleados.php                - Crear empleado
 * PUT    /api/empresa_empleados.php                - Actualizar empleado
 * DELETE /api/empresa_empleados.php?id=1           - Eliminar (soft delete)
 *
 * @version 2.0 (Migrated)
 */

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/core/ResponseHandler.php';
require_once __DIR__ . '/core/AuthMiddleware.php';
require_once __DIR__ . '/core/AuditLogger.php';
require_once __DIR__ . '/core/SecurityHeaders.php';

// Configurar manejo de errores robusto
set_error_handler(function($errno, $errstr, $errfile, $errline) {
    ApiResponse::serverError("Error PHP [$errno]: $errstr en $errfile:$errline");
});

register_shutdown_function(function() {
    $error = error_get_last();
    if ($error !== null && in_array($error['type'], [E_ERROR, E_PARSE, E_CORE_ERROR, E_COMPILE_ERROR])) {
        ApiResponse::serverError("Error Fatal: " . $error['message']);
    }
});

// Headers CORS
// Aplicar security headers
SecurityHeaders::applyApiHeaders();

// Manejar preflight CORS
SecurityHeaders::handleCors();// Manejar preflight CORS

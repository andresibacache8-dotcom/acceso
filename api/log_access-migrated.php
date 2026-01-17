<?php
/**
 * api/log_access-migrated.php
 * API para registro de acceso con soporte multi-tipo (personal, vehículos, visitas, empleados)
 *
 * Migración desde log_access.php original:
 * - Config: database/db_*.php → config/database.php
 * - Respuestas: echo json_encode() → ApiResponse::*()
 * - Estructura: métodos HTTP estándar (GET, POST, DELETE)
 *
 * Endpoints:
 * GET    /api/log_access.php?target_type=personal       - Listar logs del día actual
 * POST   /api/log_access.php                              - Registrar nuevo acceso
 * DELETE /api/log_access.php?id=123                       - Cancelar acceso (soft delete)
 *
 * @version 2.0 (Migrated)
 */

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/core/ResponseHandler.php';
require_once __DIR__ . '/core/AuthMiddleware.php';
require_once __DIR__ . '/core/AuditLogger.php';
require_once __DIR__ . '/core/SecurityHeaders.php';

// Headers CORS
// Aplicar security headers
SecurityHeaders::applyApiHeaders();

// Manejar preflight CORS
SecurityHeaders::handleCors();// Manejar preflight CORS

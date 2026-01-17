<?php
/**
 * api/log_clarified_access-migrated.php
 * API para registrar ingresos de personal con motivo específico
 *
 * Migración desde log_clarified_access.php original:
 * - Config: database/db_acceso.php + database/db_personal.php → config/database.php
 * - Respuestas: echo json_encode() → ApiResponse::*()
 * - Estructura: POST con validación de motivos
 *
 * Endpoints:
 * POST /api/log_clarified_access.php - Registrar entrada con motivo
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

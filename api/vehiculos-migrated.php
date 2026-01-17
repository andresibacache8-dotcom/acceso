<?php
/**
 * api/vehiculos-migrated.php
 *
 * API RESTful para gestión de vehículos
 * CRUD completo + validación de patentes + historial de cambios
 *
 * Métodos:
 * - GET    : Listar vehículos (paginado)
 * - POST   : Crear vehículo
 * - PUT    : Actualizar vehículo
 * - DELETE : Eliminar vehículo
 */

// ============================================================================
// CONFIGURATION & IMPORTS
// ============================================================================

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/core/ResponseHandler.php';
require_once __DIR__ . '/core/AuthMiddleware.php';
require_once __DIR__ . '/core/AuditLogger.php';
require_once __DIR__ . '/core/SecurityHeaders.php';
require_once __DIR__ . '/core/Paginator.php';

// Headers
// Aplicar security headers
SecurityHeaders::applyApiHeaders();

// Manejar preflight CORS
SecurityHeaders::handleCors();// Session

// Preflight

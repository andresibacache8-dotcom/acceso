<?php
/**
 * api/dashboard-migrated.php
 *
 * Dashboard API - Estadísticas y contadores en tiempo real
 * GET-only API para obtener:
 * 1. Contadores agregados (personal, vehículos, visitas, etc.)
 * 2. Detalles por categoría (modales con datos específicos)
 *
 * Métodos:
 * - GET: Obtener contadores (sin ?details) o detalles de categoría (con ?details=CATEGORY)
 */

// ============================================================================
// CONFIGURATION & IMPORTS
// ============================================================================

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/core/ResponseHandler.php';
require_once __DIR__ . '/core/AuthMiddleware.php';
require_once __DIR__ . '/core/AuditLogger.php';
require_once __DIR__ . '/core/SecurityHeaders.php';

// Headers
// Aplicar security headers
SecurityHeaders::applyApiHeaders();

// Manejar preflight CORS
SecurityHeaders::handleCors();// Preflight

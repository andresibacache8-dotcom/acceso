<?php
/**
 * api/portico-migrated.php
 *
 * Control de escaneo de pórtico - Validación de acceso
 * POST-only API para procesar entradas/salidas
 *
 * Busca en 5 tablas:
 * 1. Personal (personal_db)
 * 2. Vehículos (acceso_pro_db)
 * 3. Visitas (acceso_pro_db)
 * 4. Empleados de Empresas (acceso_pro_db)
 * 5. Personal en Comisión (personal_db)
 *
 * Método:
 * - POST: Procesar escaneo (RUT o patente)
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

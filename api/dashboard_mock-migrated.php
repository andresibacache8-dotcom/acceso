<?php
/**
 * api/dashboard_mock-migrated.php
 *
 * Mock Dashboard API - Versión Migrada
 *
 * Retorna datos simulados para desarrollo y testing:
 * - Contadores generales del dashboard
 * - Detalles de modales con datos ficticios
 *
 * GET /api/dashboard_mock-migrated.php - Retorna contadores
 * GET /api/dashboard_mock-migrated.php?details=personal - Retorna detalles por categoría
 *
 * Cambios principales:
 * - Usa ApiResponse para respuestas estandarizadas
 * - GET-only API
 * - Sin dependencia de base de datos (mock data)
 * - Mantiene estructura compatible con dashboard-migrated.php
 */

require_once '../api/core/ResponseHandler.php';
require_once '../api/core/AuthMiddleware.php';
require_once '../api/core/AuditLogger.php';
require_once '../api/core/SecurityHeaders.php';

// Aplicar security headers
SecurityHeaders::applyApiHeaders();

// Manejar preflight CORS
SecurityHeaders::handleCors();

// Verificar autenticación con JWT
try {
    $user = AuthMiddleware::requireAuth();
} catch (Exception $e) {
    ApiResponse::unauthorized($e->getMessage());
}

// ============================================================================
// VALIDACIÓN DE MÉTODO
// ============================================================================

if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
    ApiResponse::error('Método no permitido', 405);
}

// ============================================================================
// FUNCIONES HELPER - GENERACIÓN DE DATOS MOCK
// ============================================================================

/**
 * Genera datos ficticios de personal
 */
function generarDatosPersonal() {
    return [
        [
            'grado' => 'CAP',
            'nombre' => 'Juan Pérez Gómez',
            'rut' => '12.345.678-9',
            'unidad' => 'Operaciones',
            'fecha_ingreso' => date('Y-m-d H:i:s', strtotime('-3 hours'))
        ],
        [
            'grado' => 'TTE',
            'nombre' => 'María López Hernández',
            'rut' => '11.222.333-4',
            'unidad' => 'Logística',
            'fecha_ingreso' => date('Y-m-d H:i:s', strtotime('-2 hours'))
        ],
        [
            'grado' => 'SOM',
            'nombre' => 'Pedro González Martínez',
            'rut' => '10.987.654-3',
            'unidad' => 'Administración',
            'fecha_ingreso' => date('Y-m-d H:i:s', strtotime('-1 hour'))
        ],
        [
            'grado' => 'CRL',
            'nombre' => 'Ana Rodríguez Sánchez',
            'rut' => '9.876.543-2',
            'unidad' => 'Dirección',
            'fecha_ingreso' => date('Y-m-d H:i:s', strtotime('-30 minutes'))
        ]
    ];
}

/**
 * Genera datos ficticios de vehículos
 */
function generarDatosVehiculos() {
    return [
        [
            'patente' => 'AB-1234',
            'marca' => 'Toyota Corolla',
            'propietario' => 'Juan Pérez',
            'fecha_ingreso' => date('Y-m-d H:i:s', strtotime('-3 hours'))
        ],
        [
            'patente' => 'CD-5678',
            'marca' => 'Nissan Versa',
            'propietario' => 'María López',
            'fecha_ingreso' => date('Y-m-d H:i:s', strtotime('-2 hours'))
        ],
        [
            'patente' => 'EF-9012',
            'marca' => 'Chevrolet Sail',
            'propietario' => 'Pedro González',
            'fecha_ingreso' => date('Y-m-d H:i:s', strtotime('-1 hour'))
        ],
        [
            'patente' => 'GH-3456',
            'marca' => 'Kia Rio',
            'propietario' => 'Ana Rodríguez',
            'fecha_ingreso' => date('Y-m-d H:i:s', strtotime('-30 minutes'))
        ]
    ];
}

/**
 * Genera datos ficticios de visitas
 */
function generarDatosVisitas() {
    return [
        [
            'nombre' => 'Carlos Meneses',
            'rut' => '15.234.567-8',
            'tipo' => 'Visita',
            'poc' => 'Juan Pérez',
            'fecha_ingreso' => date('Y-m-d H:i:s', strtotime('-3 hours'))
        ],
        [
            'nombre' => 'Alejandra Torres',
            'rut' => '14.321.654-9',
            'tipo' => 'Familiar',
            'poc' => 'María López',
            'fecha_ingreso' => date('Y-m-d H:i:s', strtotime('-2 hours'))
        ],
        [
            'nombre' => 'Roberto Fuentes',
            'rut' => '16.789.012-3',
            'tipo' => 'Empresa',
            'poc' => 'Pedro González',
            'fecha_ingreso' => date('Y-m-d H:i:s', strtotime('-1 hour'))
        ]
    ];
}

/**
 * Genera datos ficticios de empresas
 */
function generarDatosEmpresas() {
    return [
        [
            'nombre_empresa' => 'Constructora XYZ',
            'representante' => 'Roberto Fuentes',
            'trabajadores' => 3,
            'fecha_ingreso' => date('Y-m-d H:i:s', strtotime('-3 hours'))
        ],
        [
            'nombre_empresa' => 'Servicios ABC',
            'representante' => 'Camila Vega',
            'trabajadores' => 2,
            'fecha_ingreso' => date('Y-m-d H:i:s', strtotime('-2 hours'))
        ],
        [
            'nombre_empresa' => 'Mantención DEF',
            'representante' => 'Jorge Silva',
            'trabajadores' => 4,
            'fecha_ingreso' => date('Y-m-d H:i:s', strtotime('-1 hour'))
        ]
    ];
}

/**
 * Obtiene datos mock según categoría
 */
function obtenerDatosMockPorCategoria($category) {
    switch ($category) {
        case 'personal':
        case 'personal_trabajando':
        case 'personal_residiendo':
        case 'personal_comision':
        case 'personal_actividades':
            return generarDatosPersonal();

        case 'vehiculos_funcionario':
        case 'vehiculos_residente':
        case 'vehiculos_visita':
        case 'vehiculos_fiscal':
            return generarDatosVehiculos();

        case 'visitas':
            return generarDatosVisitas();

        case 'empresas':
            return generarDatosEmpresas();

        default:
            return [];
    }
}

/**
 * Retorna contadores generales del dashboard
 */
function obtenerContadoresGenerales() {
    return [
        'personal_general_adentro' => 42,
        'personal_trabajando' => 35,
        'personal_residiendo' => 12,
        'personal_otras_actividades' => 8,
        'personal_en_comision' => 5,
        'empresas_adentro' => 3,
        'visitas_adentro' => 7,
        'vehiculos_funcionario_adentro' => 18,
        'vehiculos_residente_adentro' => 10,
        'vehiculos_visita_adentro' => 4,
        'vehiculos_proveedor_adentro' => 2,
        'vehiculos_fiscal_adentro' => 3
    ];
}

// ============================================================================
// MAIN REQUEST HANDLER
// ============================================================================

try {
    $details = $_GET['details'] ?? null;

    if (!empty($details)) {
        // Retornar detalles de una categoría específica
        $data = obtenerDatosMockPorCategoria($details);
        ApiResponse::success($data, "Datos mock de $details obtenidos exitosamente");
    } else {
        // Retornar contadores generales
        $data = obtenerContadoresGenerales();
        ApiResponse::success($data, 'Contadores del dashboard obtenidos exitosamente');
    }

} catch (Exception $e) {
    ApiResponse::serverError('Error al obtener datos mock: ' . $e->getMessage());
}

?>

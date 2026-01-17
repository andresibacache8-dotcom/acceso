<?php
/**
 * api/generar_hash-migrated.php
 *
 * Utilidad para Generación de Hash de Contraseña - Versión Migrada
 *
 * Genera un hash seguro de contraseña usando PASSWORD_DEFAULT (bcrypt).
 * Utilizado principalmente como script de utilidad para crear contraseñas iniciales.
 *
 * GET /api/generar_hash-migrated.php?password=your_password
 * GET /api/generar_hash-migrated.php - Usa contraseña default para desarrollo
 *
 * Cambios principales:
 * - Usa ApiResponse para respuestas estandarizadas
 * - GET-only API
 * - Soporte para parámetro ?password opcional
 * - Respuesta estructurada con éxito
 */

require_once '../api/core/ResponseHandler.php';

// ============================================================================
// VALIDACIÓN DE MÉTODO
// ============================================================================

if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
    ApiResponse::error('Método no permitido', 405);
}

// ============================================================================
// MAIN REQUEST HANDLER
// ============================================================================

try {
    // Obtener contraseña del parámetro o usar default para desarrollo
    $password = $_GET['password'] ?? 'password';

    // Validar que no esté vacía
    if (empty($password)) {
        ApiResponse::badRequest('El parámetro password no puede estar vacío');
    }

    // Generar hash seguro con PASSWORD_DEFAULT (bcrypt)
    $hash = password_hash($password, PASSWORD_DEFAULT);

    // Retornar respuesta exitosa con el hash
    ApiResponse::success([
        'password' => $password,
        'hash' => $hash,
        'algorithm' => 'bcrypt (PASSWORD_DEFAULT)',
        'info' => 'Usa este hash para almacenar en la base de datos'
    ], 200, [
        'note' => 'Este es un script de utilidad. En producción, no expongas las contraseñas en la respuesta.'
    ]);

} catch (Exception $e) {
    ApiResponse::serverError('Error al generar hash: ' . $e->getMessage());
}

?>

<?php
/**
 * ApiResponse - Standardized Response Handler for all API endpoints
 *
 * This class provides a consistent response format across all API endpoints,
 * eliminating the ~145 inconsistent echo json_encode() statements.
 *
 * Response Format:
 * - Success: { success: true, data: mixed, meta?: object }
 * - Error: { success: false, error: { message: string, code: int, details?: any } }
 *
 * Usage:
 *   ApiResponse::success($data);
 *   ApiResponse::success($data, 201, ['pagination' => [...]]);
 *   ApiResponse::error('Not found', 404);
 *   ApiResponse::error('Validation error', 400, ['field' => 'email']);
 *   ApiResponse::paginated($data, $page, $perPage, $total);
 *
 * @package SCAD
 * @subpackage API
 */

class ApiResponse
{
    /**
     * Send successful response
     *
     * @param mixed $data The data to return
     * @param int $code HTTP status code (default: 200)
     * @param array $meta Optional metadata (pagination, etc.)
     *
     * @return void Exits execution
     */
    public static function success($data = null, $code = 200, $meta = [])
    {
        http_response_code($code);
        self::setHeaders();

        $response = [
            'success' => true,
            'data' => $data
        ];

        if (!empty($meta)) {
            $response['meta'] = $meta;
        }

        echo json_encode($response, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        exit;
    }

    /**
     * Send error response
     *
     * @param string $message User-friendly error message
     * @param int $code HTTP status code (default: 400)
     * @param array $details Optional technical details (only in debug mode)
     *
     * @return void Exits execution
     */
    public static function error($message, $code = 400, $details = [])
    {
        http_response_code($code);
        self::setHeaders();

        // Include details only in development mode
        $includeDetails = isset($_ENV['APP_ENV']) && $_ENV['APP_ENV'] === 'development';

        $error = [
            'message' => $message,
            'code' => $code
        ];

        if (!empty($details) && $includeDetails) {
            $error['details'] = $details;
        }

        $response = [
            'success' => false,
            'error' => $error
        ];

        echo json_encode($response, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        exit;
    }

    /**
     * Send paginated response
     *
     * @param array $data Array of data items
     * @param int $page Current page (1-indexed)
     * @param int $perPage Items per page
     * @param int $total Total number of items
     *
     * @return void Exits execution
     */
    public static function paginated($data, $page, $perPage, $total)
    {
        $totalPages = ceil($total / $perPage);

        $meta = [
            'pagination' => [
                'current_page' => (int)$page,
                'per_page' => (int)$perPage,
                'total' => (int)$total,
                'total_pages' => (int)$totalPages,
                'has_next' => ($page * $perPage) < $total,
                'has_prev' => $page > 1
            ]
        ];

        self::success($data, 200, $meta);
    }

    /**
     * Send created response (201)
     *
     * @param mixed $data The created resource
     * @param array $meta Optional metadata
     *
     * @return void Exits execution
     */
    public static function created($data = null, $meta = [])
    {
        self::success($data, 201, $meta);
    }

    /**
     * Send no content response (204)
     *
     * @return void Exits execution
     */
    public static function noContent()
    {
        http_response_code(204);
        self::setHeaders();
        exit;
    }

    /**
     * Send bad request error (400)
     *
     * @param string $message Error message
     * @param array $details Validation details
     *
     * @return void Exits execution
     */
    public static function badRequest($message = 'Bad request', $details = [])
    {
        self::error($message, 400, $details);
    }

    /**
     * Send unauthorized error (401)
     *
     * @param string $message Error message
     *
     * @return void Exits execution
     */
    public static function unauthorized($message = 'Unauthorized')
    {
        self::error($message, 401);
    }

    /**
     * Send forbidden error (403)
     *
     * @param string $message Error message
     *
     * @return void Exits execution
     */
    public static function forbidden($message = 'Forbidden')
    {
        self::error($message, 403);
    }

    /**
     * Send not found error (404)
     *
     * @param string $message Error message
     *
     * @return void Exits execution
     */
    public static function notFound($message = 'Not found')
    {
        self::error($message, 404);
    }

    /**
     * Send conflict error (409)
     *
     * @param string $message Error message
     * @param array $details Conflict details
     *
     * @return void Exits execution
     */
    public static function conflict($message = 'Conflict', $details = [])
    {
        self::error($message, 409, $details);
    }

    /**
     * Send unprocessable entity error (422)
     *
     * @param string $message Error message
     * @param array $details Validation details
     *
     * @return void Exits execution
     */
    public static function unprocessable($message = 'Unprocessable entity', $details = [])
    {
        self::error($message, 422, $details);
    }

    /**
     * Send internal server error (500)
     *
     * @param string $message Error message
     * @param array $details Error details
     *
     * @return void Exits execution
     */
    public static function serverError($message = 'Internal server error', $details = [])
    {
        self::error($message, 500, $details);
    }

    /**
     * Send service unavailable error (503)
     *
     * @param string $message Error message
     *
     * @return void Exits execution
     */
    public static function unavailable($message = 'Service unavailable')
    {
        self::error($message, 503);
    }

    /**
     * Set standard response headers
     *
     * @return void
     */
    private static function setHeaders()
    {
        header('Content-Type: application/json; charset=utf-8');
        header('Access-Control-Allow-Origin: *');
        header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS');
        header('Access-Control-Allow-Headers: Content-Type, Authorization');
        header('Cache-Control: no-cache, no-store, must-revalidate');
    }

    /**
     * Handle preflight OPTIONS requests
     *
     * @return void
     */
    public static function handleOptions()
    {
        self::setHeaders();
        http_response_code(200);
        exit;
    }
}

// Handle OPTIONS requests automatically
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    ApiResponse::handleOptions();
}
?>

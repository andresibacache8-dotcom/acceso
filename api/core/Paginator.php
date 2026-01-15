<?php
/**
 * Paginator - Pagination utility for database queries
 *
 * Handles pagination of database results to prevent loading
 * all records into memory at once. Generates SQL with LIMIT/OFFSET
 * and provides metadata about pagination state.
 *
 * Usage:
 *   $page = $_GET['page'] ?? 1;
 *   $perPage = $_GET['per_page'] ?? 50;
 *
 *   // Build base query without LIMIT
 *   $baseQuery = "SELECT * FROM personal WHERE active = 1 ORDER BY Nombres";
 *
 *   // Get paginated SQL
 *   $sql = Paginator::generateSQL($baseQuery, $page, $perPage);
 *
 *   // Get total count
 *   $countSql = "SELECT COUNT(*) as total FROM personal WHERE active = 1";
 *   $total = Paginator::getTotalCount($conn, $countSql);
 *
 *   // Execute and return with metadata
 *   $result = $conn->query($sql);
 *   $data = [];
 *   while ($row = $result->fetch_assoc()) {
 *       $data[] = $row;
 *   }
 *
 *   ApiResponse::paginated($data, $page, $perPage, $total);
 *
 * @package SCAD
 * @subpackage API
 */

class Paginator
{
    const DEFAULT_PER_PAGE = 50;
    const MAX_PER_PAGE = 500; // Safety limit
    const MIN_PER_PAGE = 1;

    /**
     * Generate paginated SQL query
     *
     * @param string $baseQuery Base SELECT query (without LIMIT)
     * @param int $page Page number (1-indexed)
     * @param int $perPage Items per page
     *
     * @return string SQL query with LIMIT and OFFSET
     */
    public static function generateSQL($baseQuery, $page = 1, $perPage = self::DEFAULT_PER_PAGE)
    {
        // Validate and sanitize parameters
        $page = max(1, (int)$page);
        $perPage = max(self::MIN_PER_PAGE, min(self::MAX_PER_PAGE, (int)$perPage));

        // Calculate offset
        $offset = ($page - 1) * $perPage;

        // Append LIMIT and OFFSET to query
        return trim($baseQuery) . " LIMIT {$perPage} OFFSET {$offset}";
    }

    /**
     * Get total count of records matching query
     *
     * @param mysqli $conn Database connection
     * @param string $countQuery COUNT query (SELECT COUNT(*) as total FROM ...)
     *
     * @return int Total number of records
     */
    public static function getTotalCount($conn, $countQuery)
    {
        try {
            $result = $conn->query($countQuery);

            if ($result === false) {
                throw new Exception("Count query failed: " . $conn->error);
            }

            $row = $result->fetch_assoc();
            return (int)($row['total'] ?? 0);
        } catch (Exception $e) {
            error_log("Paginator::getTotalCount error: " . $e->getMessage());
            return 0;
        }
    }

    /**
     * Execute paginated query and return data + metadata
     *
     * Convenience method that handles:
     * - Executing the base query with pagination
     * - Getting total count
     * - Building result array
     *
     * @param mysqli $conn Database connection
     * @param string $baseQuery Base SELECT query (without LIMIT)
     * @param string $countQuery COUNT query
     * @param int $page Page number
     * @param int $perPage Items per page
     *
     * @return array ['data' => [], 'pagination' => [...]]
     */
    public static function paginate(
        $conn,
        $baseQuery,
        $countQuery,
        $page = 1,
        $perPage = self::DEFAULT_PER_PAGE
    ) {
        try {
            // Get paginated SQL
            $sql = self::generateSQL($baseQuery, $page, $perPage);

            // Get total count
            $total = self::getTotalCount($conn, $countQuery);

            // Execute query
            $result = $conn->query($sql);

            if ($result === false) {
                throw new Exception("Query failed: " . $conn->error);
            }

            // Fetch results
            $data = [];
            while ($row = $result->fetch_assoc()) {
                $data[] = $row;
            }

            // Calculate pagination metadata
            $page = max(1, (int)$page);
            $perPage = max(1, min(self::MAX_PER_PAGE, (int)$perPage));
            $totalPages = ceil($total / $perPage);

            return [
                'data' => $data,
                'pagination' => [
                    'current_page' => $page,
                    'per_page' => $perPage,
                    'total' => $total,
                    'total_pages' => $totalPages,
                    'has_next' => ($page * $perPage) < $total,
                    'has_prev' => $page > 1,
                    'offset' => ($page - 1) * $perPage
                ]
            ];
        } catch (Exception $e) {
            error_log("Paginator::paginate error: " . $e->getMessage());
            throw $e;
        }
    }

    /**
     * Validate pagination parameters
     *
     * @param int $page Page number to validate
     * @param int $perPage Items per page to validate
     *
     * @return array ['page' => int, 'perPage' => int]
     */
    public static function validateParams($page = 1, $perPage = self::DEFAULT_PER_PAGE)
    {
        return [
            'page' => max(1, (int)$page),
            'perPage' => max(self::MIN_PER_PAGE, min(self::MAX_PER_PAGE, (int)$perPage))
        ];
    }

    /**
     * Calculate offset from page and perPage
     *
     * @param int $page Page number (1-indexed)
     * @param int $perPage Items per page
     *
     * @return int Offset for LIMIT clause
     */
    public static function calculateOffset($page = 1, $perPage = self::DEFAULT_PER_PAGE)
    {
        $page = max(1, (int)$page);
        $perPage = max(self::MIN_PER_PAGE, min(self::MAX_PER_PAGE, (int)$perPage));
        return ($page - 1) * $perPage;
    }

    /**
     * Calculate total pages
     *
     * @param int $total Total number of items
     * @param int $perPage Items per page
     *
     * @return int Total number of pages
     */
    public static function calculateTotalPages($total, $perPage = self::DEFAULT_PER_PAGE)
    {
        $perPage = max(self::MIN_PER_PAGE, (int)$perPage);
        return (int)ceil($total / $perPage);
    }
}
?>

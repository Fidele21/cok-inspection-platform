<?php
/**
 * Database Configuration — reads credentials from .env (outside web root).
 *
 * CHANGE FROM PREVIOUS VERSION:
 *   Credentials are no longer hardcoded. They come from the .env file.
 *   Connection error messages are no longer echoed to the browser
 *   (they leaked the DB name and host).
 */

require_once __DIR__ . '/env.php';

define('DB_HOST', env('DB_HOST', 'localhost'));
define('DB_NAME', env('DB_NAME', ''));
define('DB_USER', env('DB_USER', ''));
define('DB_PASS', env('DB_PASS', ''));
define('APP_DEBUG', env('APP_DEBUG', 'false') === 'true');

/**
 * Get database connection
 * @return PDO
 */
function getDB()
{
    static $pdo = null;
    if ($pdo instanceof PDO) {
        return $pdo;
    }

    if (DB_NAME === '' || DB_USER === '') {
        error_log('FATAL: database credentials missing. Check .env location.');
        http_response_code(500);
        header('Content-Type: application/json');
        die(json_encode([
            'success' => false,
            'error'   => 'Server configuration error. Contact the administrator.',
        ]));
    }

    try {
        $pdo = new PDO(
            'mysql:host=' . DB_HOST . ';dbname=' . DB_NAME . ';charset=utf8mb4',
            DB_USER,
            DB_PASS,
            [
                PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                PDO::ATTR_EMULATE_PREPARES   => false,
            ]
        );
        return $pdo;
    } catch (PDOException $e) {
        // Log the detail, show the user nothing useful to an attacker.
        error_log('DB connection failed: ' . $e->getMessage());
        http_response_code(500);
        header('Content-Type: application/json');
        die(json_encode([
            'success' => false,
            'error'   => APP_DEBUG
                ? 'Database connection failed: ' . $e->getMessage()
                : 'Database temporarily unavailable. Please try again.',
        ]));
    }
}

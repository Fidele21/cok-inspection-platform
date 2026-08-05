<?php
/**
 * API Guard — include this at the TOP of every file in /api/.
 *
 * Closes the hole where api/*.php was reachable without a session.
 *
 * Usage:
 *     require_once __DIR__ . '/../includes/api_guard.php';
 *     api_boot();                          // JSON headers + login required
 *     api_require_role('Chief Inspector'); // optional, for privileged calls
 */

require_once __DIR__ . '/../config/auth.php';

/**
 * Standard JSON response headers. Note: no wildcard CORS.
 * The front-end is same-origin, so it needs no CORS header at all.
 */
function api_headers()
{
    header('Content-Type: application/json; charset=utf-8');
    header('X-Content-Type-Options: nosniff');
    header('Cache-Control: no-store, no-cache, must-revalidate');
    header('Referrer-Policy: same-origin');
}

function api_fail($message, $status = 400, $code = null)
{
    http_response_code($status);
    $payload = ['success' => false, 'error' => $message];
    if ($code !== null) {
        $payload['code'] = $code;
    }
    echo json_encode($payload);
    exit;
}

/**
 * Require an authenticated session. Returns the current user array.
 */
function api_require_login()
{
    if (!isLoggedIn()) {
        api_fail('Authentication required', 401, 'AUTH_REQUIRED');
    }
    return getCurrentUser();
}

/**
 * Require a minimum role level.
 */
function api_require_role($minRole)
{
    api_require_login();
    if (!hasRoleLevel($minRole)) {
        api_fail('You do not have permission to perform this action', 403, 'FORBIDDEN');
    }
    return getCurrentUser();
}

/**
 * Restrict an endpoint to specific HTTP methods.
 */
function api_require_method($methods)
{
    $methods = (array) $methods;
    $current = $_SERVER['REQUEST_METHOD'] ?? 'GET';
    if (!in_array($current, $methods, true)) {
        header('Allow: ' . implode(', ', $methods));
        api_fail('Method not allowed', 405, 'METHOD_NOT_ALLOWED');
    }
}

/**
 * One-line boot for a normal endpoint.
 */
function api_boot($minRole = null)
{
    api_headers();
    if ($minRole === null) {
        return api_require_login();
    }
    return api_require_role($minRole);
}

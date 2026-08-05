<?php
/**
 * Environment loader — City of Kigali Digital Inspection Platform
 *
 * Reads secrets from a .env file kept OUTSIDE the web root so that no
 * credential ever sits in a file a browser could request.
 *
 * Search order (first hit wins):
 *   1. /home4/<account>/.env.staging        <- preferred, above public_html
 *   2. /home4/<account>/public_html/.env.staging
 *   3. <app>/.env                              <- fallback, blocked by .htaccess
 *
 * Never commit any of these files to Git.
 */

function env_all()
{
    static $vars = null;
    if ($vars !== null) {
        return $vars;
    }

    $candidates = [
        dirname(__DIR__, 3) . '/.env.staging',
        dirname(__DIR__, 2) . '/.env.staging',
        dirname(__DIR__) . '/.env',
    ];

    $vars = [];
    foreach ($candidates as $file) {
        if (is_readable($file)) {
            $parsed = parse_ini_file($file, false, INI_SCANNER_RAW);
            if (is_array($parsed)) {
                $vars = $parsed;
                $vars['__ENV_FILE__'] = $file;
                break;
            }
        }
    }

    return $vars;
}

/**
 * Read a single environment value.
 */
function env($key, $default = null)
{
    $vars = env_all();
    if (!array_key_exists($key, $vars)) {
        return $default;
    }
    $value = trim($vars[$key], " \t\n\r\0\x0B\"'");
    return $value === '' ? $default : $value;
}

/**
 * Which .env file was actually loaded (for the diagnostic script only).
 */
function env_source()
{
    $vars = env_all();
    return $vars['__ENV_FILE__'] ?? null;
}

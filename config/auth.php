<?php
/**
 * Authentication — City of Kigali Digital Inspection Platform
 *
 * CHANGES FROM PREVIOUS VERSION:
 *   1. Passwords are hashed with password_hash() / password_verify().
 *   2. TRANSPARENT MIGRATION: existing plain-text passwords still work on
 *      first login, and are silently re-saved as a hash at that moment.
 *      No user has to change their password. Nobody is locked out.
 *   3. Session cookie hardened (HttpOnly, SameSite, Secure when on HTTPS).
 *   4. Session ID regenerated on login (prevents session fixation).
 *   5. Idle timeout after 8 hours.
 *   6. Simple login throttling: 5 failures per username per 15 minutes.
 */

require_once __DIR__ . '/database.php';

/* ------------------------------------------------------------------
   Session hardening — must run BEFORE session_start()
   ------------------------------------------------------------------ */
if (session_status() === PHP_SESSION_NONE) {
    $https = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off')
        || (($_SERVER['HTTP_X_FORWARDED_PROTO'] ?? '') === 'https');

    ini_set('session.use_strict_mode', '1');
    ini_set('session.cookie_httponly', '1');
    ini_set('session.use_only_cookies', '1');

    if (PHP_VERSION_ID >= 70300) {
        session_set_cookie_params([
            'lifetime' => 0,
            'path'     => '/',
            'httponly' => true,
            'secure'   => $https,
            'samesite' => 'Lax',
        ]);
    } else {
        ini_set('session.cookie_secure', $https ? '1' : '0');
    }

    session_start();
}

define('SESSION_IDLE_TIMEOUT', 8 * 60 * 60); // 8 hours
define('LOGIN_MAX_ATTEMPTS', 5);
define('LOGIN_LOCKOUT_SECONDS', 15 * 60);

function getAuthDB()
{
    return getDB();
}

/* ------------------------------------------------------------------
   Password helpers
   ------------------------------------------------------------------ */

/**
 * Is this stored value already a modern password hash?
 */
function isHashedPassword($stored)
{
    if (!is_string($stored) || strlen($stored) < 20) {
        return false;
    }
    $info = password_get_info($stored);
    return !empty($info['algo']);
}

/**
 * Hash a password for storage.
 */
function hashPassword($plain)
{
    return password_hash($plain, PASSWORD_DEFAULT);
}

/* ------------------------------------------------------------------
   Login throttling (session-based; good enough for a single app server)
   ------------------------------------------------------------------ */

function loginAttemptsKey($username)
{
    return 'login_attempts_' . strtolower($username);
}

function isLoginLocked($username)
{
    $key = loginAttemptsKey($username);
    if (empty($_SESSION[$key])) {
        return false;
    }
    $record = $_SESSION[$key];
    if ((time() - $record['first']) > LOGIN_LOCKOUT_SECONDS) {
        unset($_SESSION[$key]);
        return false;
    }
    return $record['count'] >= LOGIN_MAX_ATTEMPTS;
}

function recordLoginFailure($username)
{
    $key = loginAttemptsKey($username);
    if (empty($_SESSION[$key]) || (time() - $_SESSION[$key]['first']) > LOGIN_LOCKOUT_SECONDS) {
        $_SESSION[$key] = ['count' => 1, 'first' => time()];
    } else {
        $_SESSION[$key]['count']++;
    }
}

function clearLoginFailures($username)
{
    unset($_SESSION[loginAttemptsKey($username)]);
}

/* ------------------------------------------------------------------
   Registration
   ------------------------------------------------------------------ */

function registerUser($username, $password, $full_name, $email, $role = 'Inspector')
{
    if (strlen($password) < 8) {
        return ['success' => false, 'error' => 'Password must be at least 8 characters'];
    }

    try {
        $pdo = getAuthDB();
        $stmt = $pdo->prepare(
            'INSERT INTO users (username, password, full_name, email, role)
             VALUES (?, ?, ?, ?, ?)'
        );
        $stmt->execute([$username, hashPassword($password), $full_name, $email, $role]);
        return ['success' => true, 'message' => 'User registered successfully'];
    } catch (PDOException $e) {
        if ($e->getCode() == 23000) {
            return ['success' => false, 'error' => 'Username or email already exists'];
        }
        error_log('Register error: ' . $e->getMessage());
        return ['success' => false, 'error' => 'Registration failed'];
    }
}

/* ------------------------------------------------------------------
   Authentication
   ------------------------------------------------------------------ */

function authenticate($username, $password)
{
    if (isLoginLocked($username)) {
        return false;
    }

    try {
        $pdo = getAuthDB();
        $stmt = $pdo->prepare('SELECT * FROM users WHERE username = ? AND is_active = 1');
        $stmt->execute([$username]);
        $user = $stmt->fetch();

        if (!$user) {
            // Constant-ish time: still burn a hash cycle so timing doesn't leak
            // whether the username exists.
            password_verify($password, '$2y$10$usesomesillystringforsalt0000000000000000000000000000000');
            recordLoginFailure($username);
            return false;
        }

        $stored = $user['password'];
        $ok     = false;
        $needsRehash = false;

        if (isHashedPassword($stored)) {
            $ok = password_verify($password, $stored);
            if ($ok && password_needs_rehash($stored, PASSWORD_DEFAULT)) {
                $needsRehash = true;
            }
        } else {
            // LEGACY plain-text password. Accept it once, then upgrade it.
            $ok = hash_equals((string) $stored, (string) $password);
            if ($ok) {
                $needsRehash = true;
            }
        }

        if (!$ok) {
            recordLoginFailure($username);
            return false;
        }

        if ($needsRehash) {
            try {
                $up = $pdo->prepare('UPDATE users SET password = ? WHERE id = ?');
                $up->execute([hashPassword($password), $user['id']]);
                error_log('Password upgraded to hash for user id ' . $user['id']);
            } catch (PDOException $e) {
                // Login still succeeds; just log that the upgrade failed.
                error_log('Password rehash failed: ' . $e->getMessage());
            }
        }

        clearLoginFailures($username);

        // Prevent session fixation
        session_regenerate_id(true);

        $_SESSION['user_id']    = $user['id'];
        $_SESSION['username']   = $user['username'];
        $_SESSION['full_name']  = $user['full_name'];
        $_SESSION['role']       = $user['role'];
        $_SESSION['email']      = $user['email'];
        $_SESSION['logged_in']  = true;
        $_SESSION['login_time'] = time();
        $_SESSION['last_seen']  = time();

        return true;
    } catch (PDOException $e) {
        error_log('Auth error: ' . $e->getMessage());
        return false;
    }
}

/* ------------------------------------------------------------------
   Session state
   ------------------------------------------------------------------ */

function isLoggedIn()
{
    if (empty($_SESSION['logged_in']) || $_SESSION['logged_in'] !== true) {
        return false;
    }

    // Idle timeout
    if (isset($_SESSION['last_seen']) && (time() - $_SESSION['last_seen']) > SESSION_IDLE_TIMEOUT) {
        logoutUser();
        return false;
    }
    $_SESSION['last_seen'] = time();

    return true;
}

function getCurrentUser()
{
    if (!isLoggedIn()) {
        return null;
    }
    return [
        'id'        => $_SESSION['user_id']   ?? null,
        'username'  => $_SESSION['username']  ?? null,
        'full_name' => $_SESSION['full_name'] ?? null,
        'role'      => $_SESSION['role']      ?? 'Inspector',
        'email'     => $_SESSION['email']     ?? null,
    ];
}

function getCurrentUserId()
{
    return $_SESSION['user_id'] ?? null;
}

function getCurrentUsername()
{
    return $_SESSION['username'] ?? 'Guest';
}

function getCurrentRole()
{
    return $_SESSION['role'] ?? 'Inspector';
}

function hasRole($role)
{
    return getCurrentRole() === $role;
}

/**
 * Role ladder.
 *
 * BUG FIX: 'Admin' exists in the users.role ENUM but was missing from this
 * map, so an Admin scored 0 and had FEWER rights than an Inspector.
 *
 * NOTE: this ladder is a stopgap. The new platform should use discrete
 * permissions scoped by district and inspection type, not a single ladder.
 */
function roleLevels()
{
    return [
        'Inspector'        => 1,
        'Senior Inspector' => 2,
        'Chief Inspector'  => 3,
        'Director'         => 4,
        'Admin'            => 5,
    ];
}

function hasRoleLevel($minRole)
{
    $levels        = roleLevels();
    $currentLevel  = $levels[getCurrentRole()] ?? 0;
    $requiredLevel = $levels[$minRole] ?? 99;
    return $currentLevel >= $requiredLevel;
}

/* ------------------------------------------------------------------
   CSRF
   ------------------------------------------------------------------ */

function csrfToken()
{
    if (empty($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}

function csrfValid($token)
{
    return !empty($_SESSION['csrf_token'])
        && is_string($token)
        && hash_equals($_SESSION['csrf_token'], $token);
}

/* ------------------------------------------------------------------
   Logout
   ------------------------------------------------------------------ */

function logoutUser()
{
    $_SESSION = [];
    if (ini_get('session.use_cookies')) {
        $params = session_get_cookie_params();
        setcookie(
            session_name(),
            '',
            time() - 42000,
            $params['path'],
            $params['domain'],
            $params['secure'],
            $params['httponly']
        );
    }
    session_destroy();
}

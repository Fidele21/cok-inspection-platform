<?php
/**
 * Audit trail — records who did what, to which record, and when.
 *
 * For an enforcement system this is not optional: when a station owner
 * disputes a finding two years from now, this table is the answer.
 *
 * Writes here must NEVER break the calling operation, so every failure
 * is swallowed and logged instead of thrown.
 */

require_once __DIR__ . '/../config/database.php';

/**
 * @param string   $action     e.g. 'inspection.create', 'inspection.delete'
 * @param string   $entityType e.g. 'inspection', 'entity', 'user'
 * @param int|null $entityId
 * @param array    $meta       Any extra context (will be JSON encoded)
 */
function audit_log($action, $entityType = null, $entityId = null, array $meta = [])
{
    try {
        $pdo = getDB();
        $stmt = $pdo->prepare(
            'INSERT INTO audit_log
                (user_id, username, action, entity_type, entity_id, meta, ip_address, user_agent)
             VALUES (?, ?, ?, ?, ?, ?, ?, ?)'
        );
        $stmt->execute([
            $_SESSION['user_id']  ?? null,
            $_SESSION['username'] ?? 'system',
            $action,
            $entityType,
            $entityId,
            $meta ? json_encode($meta, JSON_UNESCAPED_UNICODE) : null,
            $_SERVER['REMOTE_ADDR']     ?? null,
            substr($_SERVER['HTTP_USER_AGENT'] ?? '', 0, 255),
        ]);
    } catch (Throwable $e) {
        error_log('audit_log failed: ' . $e->getMessage());
    }
}

<?php
/**
 * Archive (soft-delete) an inspection.
 *
 * CHANGES FROM PREVIOUS VERSION:
 *   1. Requires an authenticated session (was completely open — anyone
 *      who knew the URL could permanently destroy an inspection).
 *   2. Requires Senior Inspector or above.
 *   3. SOFT DELETE. The old version physically removed the inspection,
 *      its answers, its team, its reports — and the entity too. For a
 *      legal enforcement record that is not acceptable. We now stamp
 *      deleted_at and hide it from the lists instead.
 *   4. Writes an audit entry naming who archived what.
 *
 * The response format is UNCHANGED, so app.js keeps working.
 */

require_once __DIR__ . '/../includes/api_guard.php';
require_once __DIR__ . '/../includes/audit.php';

$user = api_boot('Senior Inspector');
api_require_method(['DELETE', 'POST']);

$id = isset($_GET['id']) ? (int) $_GET['id'] : 0;
if ($id <= 0) {
    api_fail('Inspection ID required');
}

$reason = isset($_GET['reason']) ? substr(trim($_GET['reason']), 0, 255) : null;

try {
    $pdo = getDB();

    $stmt = $pdo->prepare(
        'SELECT i.id, i.entity_id, e.name AS entity_name
         FROM inspections i
         JOIN entities e ON e.id = i.entity_id
         WHERE i.id = ? AND i.deleted_at IS NULL'
    );
    $stmt->execute([$id]);
    $row = $stmt->fetch();

    if (!$row) {
        api_fail('Inspection not found or already archived', 404, 'NOT_FOUND');
    }

    $stmt = $pdo->prepare(
        'UPDATE inspections
         SET deleted_at = NOW(), deleted_by = ?, delete_reason = ?, status = "archived"
         WHERE id = ?'
    );
    $stmt->execute([$user['id'], $reason, $id]);

    audit_log('inspection.archive', 'inspection', $id, [
        'entity_id'   => $row['entity_id'],
        'entity_name' => $row['entity_name'],
        'reason'      => $reason,
    ]);

    echo json_encode(['success' => true]);
} catch (Exception $e) {
    error_log('delete_inspection failed: ' . $e->getMessage());
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'error'   => APP_DEBUG ? $e->getMessage() : 'Could not archive the inspection.',
    ]);
}

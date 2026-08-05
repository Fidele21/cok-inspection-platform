<?php
/**
 * Save (create or update) an inspection.
 *
 * CHANGES FROM PREVIOUS VERSION:
 *   1. Requires an authenticated session (was completely open).
 *   2. ENTITY DEDUPLICATION — the old version INSERTed a brand new
 *      entity row on every single inspection. That is why
 *      "Lake Petroleum Jabana" exists twice (ids 26 and 27), why the
 *      "Total Entities" figure is wrong, and why you cannot ask
 *      "has this station improved since last time?".
 *      We now match on UPI first, then on name + district + sector.
 *   3. Records created_by / inspector from the session, not the payload.
 *   4. error_reporting(0) removed — errors go to the log, not the void.
 *   5. Writes an audit entry.
 *
 * The request and response formats are UNCHANGED, so assets/js/app.js
 * keeps working with no modification.
 */

require_once __DIR__ . '/../includes/api_guard.php';
require_once __DIR__ . '/../includes/audit.php';

$user = api_boot();                 // JSON headers + login required
api_require_method(['POST']);

ini_set('display_errors', '0');     // never leak stack traces to the browser
ini_set('log_errors', '1');

$input = file_get_contents('php://input');
$data  = json_decode($input, true);

if (!$data || !is_array($data)) {
    api_fail('Invalid data');
}
if (empty($data['name'])) {
    api_fail('Entity name is required');
}
if (empty($data['date'])) {
    api_fail('Inspection date is required');
}
if (!isset($data['answers']) || !is_array($data['answers'])) {
    $data['answers'] = [];
}

/**
 * Find an existing entity, or create one. This is the deduplication fix.
 *
 * Matching order:
 *   1. UPI (the authoritative parcel identifier) within the same type
 *   2. name + district + sector within the same type
 */
function findOrCreateEntity(PDO $pdo, array $d)
{
    $typeStmt = $pdo->prepare('SELECT id FROM entity_types WHERE code = ?');
    $typeStmt->execute([$d['entityType'] ?? 'building']);
    $typeId = $typeStmt->fetchColumn();

    if (!$typeId) {
        throw new Exception('Unknown entity type: ' . ($d['entityType'] ?? ''));
    }

    $upi = trim((string) ($d['upi'] ?? ''));
    $existingId = null;

    if ($upi !== '') {
        $stmt = $pdo->prepare(
            'SELECT id FROM entities
             WHERE entity_type_id = ? AND upi = ? AND deleted_at IS NULL
             ORDER BY id LIMIT 1'
        );
        $stmt->execute([$typeId, $upi]);
        $existingId = $stmt->fetchColumn() ?: null;
    }

    if (!$existingId) {
        $stmt = $pdo->prepare(
            'SELECT id FROM entities
             WHERE entity_type_id = ?
               AND LOWER(TRIM(name)) = LOWER(TRIM(?))
               AND COALESCE(district, "") = COALESCE(?, "")
               AND COALESCE(sector, "")   = COALESCE(?, "")
               AND deleted_at IS NULL
             ORDER BY id LIMIT 1'
        );
        $stmt->execute([
            $typeId,
            $d['name'],
            $d['district'] ?? '',
            $d['sector'] ?? '',
        ]);
        $existingId = $stmt->fetchColumn() ?: null;
    }

    $fields = [
        $d['name'],
        $d['owner']     ?? '',
        $d['tel']       ?? '',
        $d['email']     ?? '',
        $d['use']       ?? '',
        $upi,
        $d['district']  ?? '',
        $d['sector']    ?? '',
        $d['cell']      ?? '',
        $d['zoning']    ?? '',
        $d['latitude']  !== '' ? ($d['latitude']  ?? null) : null,
        $d['longitude'] !== '' ? ($d['longitude'] ?? null) : null,
    ];

    if ($existingId) {
        // Refresh the entity's details with what the inspector saw today.
        $stmt = $pdo->prepare(
            'UPDATE entities SET
                name = ?, owner = ?, telephone = ?, email = ?, use_type = ?, upi = ?,
                district = ?, sector = ?, cell = ?, zoning = ?, latitude = ?, longitude = ?
             WHERE id = ?'
        );
        $stmt->execute(array_merge($fields, [$existingId]));
        return ['id' => (int) $existingId, 'created' => false];
    }

    $stmt = $pdo->prepare(
        'INSERT INTO entities
            (entity_type_id, name, owner, telephone, email, use_type, upi,
             district, sector, cell, zoning, latitude, longitude)
         VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)'
    );
    $stmt->execute(array_merge([$typeId], $fields));

    return ['id' => (int) $pdo->lastInsertId(), 'created' => true];
}

try {
    $pdo = getDB();
    $pdo->beginTransaction();

    // Validate submitted checklist item IDs against the catalogue.
    $itemIds = array_keys($data['answers']);
    if (!empty($itemIds)) {
        $placeholders = implode(',', array_fill(0, count($itemIds), '?'));
        $stmt = $pdo->prepare("SELECT id FROM checklist_items WHERE id IN ($placeholders)");
        $stmt->execute($itemIds);
        $existing = $stmt->fetchAll(PDO::FETCH_COLUMN);
        $missing  = array_diff($itemIds, $existing);
        if (!empty($missing)) {
            throw new Exception('Invalid item IDs: ' . implode(', ', $missing));
        }
    }

    $isUpdate = !empty($data['inspectionId']);

    if ($isUpdate) {
        $inspectionId = (int) $data['inspectionId'];

        $chk = $pdo->prepare('SELECT entity_id FROM inspections WHERE id = ? AND deleted_at IS NULL');
        $chk->execute([$inspectionId]);
        $entityId = $chk->fetchColumn();
        if (!$entityId) {
            throw new Exception('Inspection not found');
        }

        $stmt = $pdo->prepare(
            'UPDATE entities SET
                name = ?, owner = ?, telephone = ?, email = ?, use_type = ?, upi = ?,
                district = ?, sector = ?, cell = ?, zoning = ?, latitude = ?, longitude = ?
             WHERE id = ?'
        );
        $stmt->execute([
            $data['name'], $data['owner'] ?? '', $data['tel'] ?? '', $data['email'] ?? '',
            $data['use'] ?? '', $data['upi'] ?? '',
            $data['district'] ?? '', $data['sector'] ?? '', $data['cell'] ?? '',
            $data['zoning'] ?? '',
            $data['latitude'] ?? null, $data['longitude'] ?? null,
            $entityId,
        ]);

        $stmt = $pdo->prepare(
            'UPDATE inspections SET
                inspection_date = ?, observations = ?, recommendations = ?,
                owner_recommendations = ?, owner_rep_name = ?
             WHERE id = ?'
        );
        $stmt->execute([
            $data['date'],
            $data['observations']    ?? '',
            $data['recommendations'] ?? '',
            $data['ownerRec']        ?? '',
            $data['ownerRepName']    ?? '',
            $inspectionId,
        ]);

        $pdo->prepare('DELETE FROM inspection_answers WHERE inspection_id = ?')->execute([$inspectionId]);
        $pdo->prepare('DELETE FROM inspection_team    WHERE inspection_id = ?')->execute([$inspectionId]);
        $pdo->prepare('DELETE FROM inspection_photos  WHERE inspection_id = ?')->execute([$inspectionId]);
    } else {
        $entity   = findOrCreateEntity($pdo, $data);
        $entityId = $entity['id'];

        $stmt = $pdo->prepare(
            'INSERT INTO inspections
                (entity_id, inspection_date, inspector_name, status,
                 observations, recommendations, owner_recommendations, owner_rep_name,
                 created_by, checklist_version)
             VALUES (?, ?, ?, "completed", ?, ?, ?, ?, ?, "v1")'
        );
        $stmt->execute([
            $entityId,
            $data['date'],
            // Inspector identity comes from the SESSION, never the payload.
            $user['full_name'] ?: $user['username'],
            $data['observations']    ?? '',
            $data['recommendations'] ?? '',
            $data['ownerRec']        ?? '',
            $data['ownerRepName']    ?? '',
            $user['id'],
        ]);
        $inspectionId = (int) $pdo->lastInsertId();
    }

    // ---- Answers ----
    $stmt = $pdo->prepare(
        'INSERT INTO inspection_answers (inspection_id, item_id, status, comment)
         VALUES (?, ?, ?, ?)'
    );
    foreach ($data['answers'] as $itemId => $ans) {
        $stmt->execute([
            $inspectionId,
            (int) $itemId,
            $ans['status']  ?? '',
            $ans['comment'] ?? '',
        ]);
    }

    // ---- Team ----
    if (!empty($data['team']) && is_array($data['team'])) {
        $stmt = $pdo->prepare(
            'INSERT INTO inspection_team (inspection_id, name, institution, signature)
             VALUES (?, ?, ?, ?)'
        );
        foreach ($data['team'] as $member) {
            if (!empty($member['name'])) {
                $stmt->execute([
                    $inspectionId,
                    $member['name'],
                    $member['institution'] ?? '',
                    $member['signature']   ?? '',
                ]);
            }
        }
    }

    // ---- Photos ----
    if (!empty($data['photos']) && is_array($data['photos'])) {
        $photosToSave = array_slice($data['photos'], 0, 3);
        $stmt = $pdo->prepare(
            'INSERT INTO inspection_photos (inspection_id, photo_data, photo_name)
             VALUES (?, ?, ?)'
        );
        foreach ($photosToSave as $index => $photoData) {
            if (is_string($photoData) && strpos($photoData, 'data:image') === 0) {
                $stmt->execute([$inspectionId, $photoData, 'photo_' . ($index + 1) . '.jpg']);
            }
        }
    }

    $pdo->commit();

    audit_log(
        $isUpdate ? 'inspection.update' : 'inspection.create',
        'inspection',
        $inspectionId,
        [
            'entity_id'    => $entityId,
            'entity_name'  => $data['name'],
            'answer_count' => count($data['answers']),
            'new_entity'   => $isUpdate ? false : ($entity['created'] ?? null),
        ]
    );

    echo json_encode(['success' => true, 'inspectionId' => $inspectionId]);
} catch (Exception $e) {
    if (isset($pdo) && $pdo->inTransaction()) {
        $pdo->rollBack();
    }
    error_log('save_inspection failed: ' . $e->getMessage());
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'error'   => APP_DEBUG ? $e->getMessage() : 'Could not save the inspection. Please try again.',
    ]);
}

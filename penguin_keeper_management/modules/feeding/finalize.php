<?php
require_once __DIR__ . '/../../includes/auth.php';

requireLogin();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: index.php');
    exit;
}

if (empty($_SESSION['csrf_token']) ||
    !isset($_POST['csrf_token']) ||
    !hash_equals($_SESSION['csrf_token'], $_POST['csrf_token'])) {

    $_SESSION['feeding_error'] = 'Invalid request. Please try again.';
    header('Location: index.php');
    exit;
}

$feedingId = filter_input(INPUT_POST, 'feeding_id', FILTER_VALIDATE_INT);

if (!$feedingId || $feedingId < 1) {
    $_SESSION['feeding_error'] = 'Invalid feeding record.';
    header('Location: index.php');
    exit;
}

$userId = (int) $_SESSION['user_id'];
$isSenior = ($_SESSION['role'] ?? '') === 'Senior Keeper';

try {
    $pdo->beginTransaction();

    // Lock the record while checking its status and ownership.
    $stmt = $pdo->prepare("
        SELECT feeding_id, keeper_id, status
        FROM feeding_records
        WHERE feeding_id = ?
        FOR UPDATE
    ");
    $stmt->execute([$feedingId]);

    $record = $stmt->fetch();

    if (!$record || $record['status'] !== 'Draft') {
        throw new RuntimeException(
            'Record not found, or it has already been finalized.'
        );
    }

    // A Junior Keeper can finalize only their own Draft.
    if (!$isSenior && (int) $record['keeper_id'] !== $userId) {
        throw new RuntimeException(
            'You are not authorised to finalize this record.'
        );
    }

    $update = $pdo->prepare("
        UPDATE feeding_records
        SET status = 'Finalized'
        WHERE feeding_id = ?
          AND status = 'Draft'
    ");
    $update->execute([$feedingId]);

    if ($update->rowCount() !== 1) {
        throw new RuntimeException(
            'Unable to finalize the feeding record.'
        );
    }

    // Record the finalization in the audit log.
    $audit = $pdo->prepare("
        INSERT INTO audit_logs (
            user_id,
            action,
            entity_type,
            entity_id,
            old_values,
            new_values,
            description
        )
        VALUES (?, ?, ?, ?, ?, ?, ?)
    ");

    $audit->execute([
        $userId,
        'FINALIZE',
        'feeding_records',
        $feedingId,
        json_encode(['status' => 'Draft']),
        json_encode(['status' => 'Finalized']),
        'Feeding record finalized.'
    ]);

    $pdo->commit();

    $_SESSION['feeding_success'] =
        'Feeding record finalized successfully.';

} catch (RuntimeException $e) {

    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }

    $_SESSION['feeding_error'] = $e->getMessage();

} catch (Throwable $e) {

    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }

    error_log('Feeding finalization error: ' . $e->getMessage());

    $_SESSION['feeding_error'] =
        'Unable to finalize the feeding record. Please try again.';
}

header('Location: index.php');
exit;
?>
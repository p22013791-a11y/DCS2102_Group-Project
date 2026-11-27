<?php
require_once __DIR__ . '/../../includes/auth.php';

requireLogin();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: index.php');
    exit;
}

if (
    empty($_SESSION['csrf_token']) ||
    !isset($_POST['csrf_token']) ||
    !hash_equals($_SESSION['csrf_token'], $_POST['csrf_token'])
) {
    $_SESSION['feeding_error'] = 'Invalid request. Please try again.';
    header('Location: index.php');
    exit;
}

$feedingId = filter_input(INPUT_POST, 'feeding_id', FILTER_VALIDATE_INT);
$userId = (int) $_SESSION['user_id'];

if (!$feedingId || $feedingId < 1) {
    $_SESSION['feeding_error'] = 'Invalid feeding record.';
    header('Location: index.php');
    exit;
}

try {
    $pdo->beginTransaction();

    // Lock and retrieve the record before checking ownership and status.
    $stmt = $pdo->prepare("
        SELECT *
        FROM feeding_records
        WHERE feeding_id = ?
        FOR UPDATE
    ");
    $stmt->execute([$feedingId]);

    $record = $stmt->fetch();

    if (!$record || $record['status'] !== 'Draft') {
        throw new RuntimeException(
            'Record not found, or it is no longer available for deletion.'
        );
    }

    // Keep deletion limited to the record's creator.
    if ((int) $record['keeper_id'] !== $userId) {
        throw new RuntimeException(
            'You are not authorised to delete this Draft record.'
        );
    }

    // Preserve the original record in the audit log.
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
        'DELETE',
        'feeding_records',
        $feedingId,
        json_encode($record, JSON_UNESCAPED_UNICODE),
        null,
        'Draft feeding record deleted by its creator.'
    ]);

    // Delete only if the record is still the creator's Draft.
    $delete = $pdo->prepare("
        DELETE FROM feeding_records
        WHERE feeding_id = ?
          AND keeper_id = ?
          AND status = 'Draft'
    ");

    $delete->execute([$feedingId, $userId]);

    if ($delete->rowCount() !== 1) {
        throw new RuntimeException(
            'Unable to delete the feeding record.'
        );
    }

    $pdo->commit();

    $_SESSION['feeding_success'] =
        'Draft feeding record deleted successfully.';

} catch (RuntimeException $e) {

    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }

    $_SESSION['feeding_error'] = $e->getMessage();

} catch (Throwable $e) {

    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }

    error_log('Feeding deletion error: ' . $e->getMessage());

    $_SESSION['feeding_error'] =
        'Unable to delete the feeding record. Please try again.';
}

header('Location: index.php');
exit;
?>
<?php
require_once __DIR__ . '/../../includes/auth.php';

requireRole('Senior Keeper');

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

if (!$feedingId || $feedingId < 1) {
    $_SESSION['feeding_error'] = 'Invalid feeding record.';
    header('Location: index.php');
    exit;
}

$userId = (int) $_SESSION['user_id'];

try {
    $pdo->beginTransaction();

    $stmt = $pdo->prepare("
        SELECT feeding_id, status
        FROM feeding_records
        WHERE feeding_id = ?
        FOR UPDATE
    ");
    $stmt->execute([$feedingId]);

    $record = $stmt->fetch();

    if (!$record || $record['status'] !== 'Finalized') {
        throw new RuntimeException(
            'Record not found, or it is not currently Finalized.'
        );
    }

    $update = $pdo->prepare("
        UPDATE feeding_records
        SET status = 'Draft'
        WHERE feeding_id = ?
          AND status = 'Finalized'
    ");
    $update->execute([$feedingId]);

    if ($update->rowCount() !== 1) {
        throw new RuntimeException(
            'Unable to reopen the feeding record.'
        );
    }

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
        'REOPEN',
        'feeding_records',
        $feedingId,
        json_encode(['status' => 'Finalized']),
        json_encode(['status' => 'Draft']),
        'Senior Keeper reopened a finalized feeding record for correction.'
    ]);

    $pdo->commit();

    $_SESSION['feeding_success'] =
        'Feeding record reopened successfully.';

} catch (RuntimeException $e) {

    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }

    $_SESSION['feeding_error'] = $e->getMessage();

} catch (Throwable $e) {

    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }

    error_log('Feeding reopen error: ' . $e->getMessage());

    $_SESSION['feeding_error'] =
        'Unable to reopen the feeding record. Please try again.';
}

header('Location: index.php');
exit;
?>
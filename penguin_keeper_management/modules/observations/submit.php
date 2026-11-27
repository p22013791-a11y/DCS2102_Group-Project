<?php
require_once __DIR__ . '/../../includes/auth.php';

requireLogin();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: index.php');
    exit;
}

$submittedToken = $_POST['csrf_token'] ?? '';

if (
    !is_string($submittedToken) ||
    empty($_SESSION['csrf_token']) ||
    !hash_equals($_SESSION['csrf_token'], $submittedToken)
) {
    $_SESSION['observation_error'] =
        'Invalid request. Please refresh the page.';

    header('Location: index.php');
    exit;
}

$observationId = filter_var(
    $_POST['observation_id'] ?? '',
    FILTER_VALIDATE_INT
);

if (!$observationId || $observationId < 1) {
    $_SESSION['observation_error'] = 'Invalid observation record.';
    header('Location: index.php');
    exit;
}

try {
    $pdo->beginTransaction();

    $stmt = $pdo->prepare("
        SELECT *
        FROM husbandry_observations
        WHERE observation_id = ?
          AND keeper_id = ?
          AND status = 'Draft'
        FOR UPDATE
    ");

    $stmt->execute([
        $observationId,
        (int)$_SESSION['user_id']
    ]);

    $oldRecord = $stmt->fetch();

    if (!$oldRecord) {
        throw new RuntimeException(
            'You can only submit your own Draft observations.'
        );
    }

    $updateStmt = $pdo->prepare("
        UPDATE husbandry_observations
        SET status = 'Submitted'
        WHERE observation_id = ?
          AND keeper_id = ?
          AND status = 'Draft'
    ");

    $updateStmt->execute([
        $observationId,
        (int)$_SESSION['user_id']
    ]);

    if ($updateStmt->rowCount() !== 1) {
        throw new RuntimeException(
            'The observation could not be submitted.'
        );
    }

    $newRecord = $oldRecord;
    $newRecord['status'] = 'Submitted';

    $auditStmt = $pdo->prepare("
        INSERT INTO audit_logs (
            user_id,
            action,
            entity_type,
            entity_id,
            old_values,
            new_values,
            description
        ) VALUES (?, 'SUBMIT', 'husbandry_observations', ?, ?, ?, ?)
    ");

    $auditStmt->execute([
        (int)$_SESSION['user_id'],
        $observationId,
        json_encode($oldRecord, JSON_UNESCAPED_UNICODE),
        json_encode($newRecord, JSON_UNESCAPED_UNICODE),
        'Submitted own Draft husbandry observation for review.'
    ]);

    $pdo->commit();

    $_SESSION['observation_success'] =
        'Observation submitted successfully for review.';

} catch (Throwable $e) {

    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }

    error_log('Observation submission error: ' . $e->getMessage());

    $_SESSION['observation_error'] =
        $e instanceof RuntimeException
            ? $e->getMessage()
            : 'Unable to submit the observation. Please try again.';
}

header('Location: index.php');
exit;

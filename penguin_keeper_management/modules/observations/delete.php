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
            'You can only delete your own Draft observations.'
        );
    }

    $auditStmt = $pdo->prepare("
        INSERT INTO audit_logs (
            user_id,
            action,
            entity_type,
            entity_id,
            old_values,
            new_values,
            description
        ) VALUES (?, 'DELETE', 'husbandry_observations', ?, ?, NULL, ?)
    ");

    $auditStmt->execute([
        (int)$_SESSION['user_id'],
        $observationId,
        json_encode($oldRecord, JSON_UNESCAPED_UNICODE),
        'Deleted own Draft husbandry observation.'
    ]);

    $deleteStmt = $pdo->prepare("
        DELETE FROM husbandry_observations
        WHERE observation_id = ?
          AND keeper_id = ?
          AND status = 'Draft'
    ");

    $deleteStmt->execute([
        $observationId,
        (int)$_SESSION['user_id']
    ]);

    if ($deleteStmt->rowCount() !== 1) {
        throw new RuntimeException(
            'The observation could not be deleted.'
        );
    }

    $pdo->commit();

    $_SESSION['observation_success'] =
        'Draft observation deleted successfully.';

} catch (Throwable $e) {

    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }

    error_log('Observation deletion error: ' . $e->getMessage());

    $_SESSION['observation_error'] =
        $e instanceof RuntimeException
            ? $e->getMessage()
            : 'Unable to delete the observation. Please try again.';
}

header('Location: index.php');
exit;

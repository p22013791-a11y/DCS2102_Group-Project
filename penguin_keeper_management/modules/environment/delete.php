<?php
require_once __DIR__ . '/../../includes/auth.php';

requireLogin();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: index.php');
    exit;
}

$readingId = filter_input(INPUT_POST, 'id', FILTER_VALIDATE_INT);
$csrfToken = $_POST['csrf_token'] ?? '';

$_SESSION['environment_error'] = '';

if (
    !$readingId ||
    !isset($_SESSION['csrf_token']) ||
    !hash_equals($_SESSION['csrf_token'], $csrfToken)
) {
    $_SESSION['environment_error'] =
        'Invalid request. Please try again.';
    header('Location: index.php');
    exit;
}

$userId = (int) $_SESSION['user_id'];
$isSenior = ($_SESSION['role'] ?? '') === 'Senior Keeper';

try {
    $pdo->beginTransaction();

    $stmt = $pdo->prepare("
        SELECT *
        FROM environmental_readings
        WHERE reading_id = ?
        FOR UPDATE
    ");
    $stmt->execute([$readingId]);

    $reading = $stmt->fetch();

    if (!$reading) {
        throw new RuntimeException('Environmental reading not found.');
    }

    if (!$isSenior && (int) $reading['keeper_id'] !== $userId) {
        throw new RuntimeException(
            'You are not authorised to delete this reading.'
        );
    }

    $oldValues = json_encode(
        $reading,
        JSON_UNESCAPED_UNICODE
    );

    $audit = $pdo->prepare("
        INSERT INTO audit_logs (
            user_id,
            action,
            entity_type,
            entity_id,
            old_values,
            description
        ) VALUES (?, 'DELETE', 'environmental_readings', ?, ?, ?)
    ");

    $audit->execute([
        $userId,
        $readingId,
        $oldValues,
        'Deleted environmental reading #' . $readingId
    ]);

    $delete = $pdo->prepare("
        DELETE FROM environmental_readings
        WHERE reading_id = ?
    ");
    $delete->execute([$readingId]);

    if ($delete->rowCount() !== 1) {
        throw new RuntimeException(
            'Unable to delete the environmental reading.'
        );
    }

    $pdo->commit();

    $_SESSION['environment_success'] =
        'Environmental reading deleted successfully.';

} catch (RuntimeException $e) {
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }

    $_SESSION['environment_error'] = $e->getMessage();

} catch (Throwable $e) {
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }

    error_log('Environment delete error: ' . $e->getMessage());

    $_SESSION['environment_error'] =
        'A database error occurred. Please try again.';
}

header('Location: index.php');
exit;

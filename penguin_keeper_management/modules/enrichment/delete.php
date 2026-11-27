<?php

require_once __DIR__ . '/../../includes/auth.php';

requireLogin();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    exit('Method not allowed.');
}

if (
    !isset($_POST['csrf_token']) ||
    !isset($_SESSION['csrf_token']) ||
    !hash_equals($_SESSION['csrf_token'], $_POST['csrf_token'])
) {
    $_SESSION['enrichment_error'] = 'Invalid request. Please try again.';
    header('Location: index.php');
    exit;
}

$recordId = filter_var(
    $_POST['enrichment_id'] ?? null,
    FILTER_VALIDATE_INT
);

if (!$recordId || $recordId <= 0) {
    $_SESSION['enrichment_error'] = 'Invalid enrichment record ID.';
    header('Location: index.php');
    exit;
}

$currentUserId = (int)$_SESSION['user_id'];
$isSenior = ($_SESSION['role'] ?? '') === 'Senior Keeper';

try {
    $pdo->beginTransaction();

    // Lock the record before checking permission and deleting.
    $stmt = $pdo->prepare("
        SELECT *
        FROM enrichment_records
        WHERE enrichment_id = ?
        FOR UPDATE
    ");
    $stmt->execute([$recordId]);

    $record = $stmt->fetch();

    if (!$record) {
        throw new RuntimeException('Enrichment record not found.');
    }

    if (
        !$isSenior &&
        (int)$record['keeper_id'] !== $currentUserId
    ) {
        throw new RuntimeException(
            'You are not authorised to delete this record.'
        );
    }

    $oldValues = [
        'enrichment_id' => $record['enrichment_id'],
        'penguin_id' => $record['penguin_id'],
        'keeper_id' => $record['keeper_id'],
        'activity_date' => $record['activity_date'],
        'activity_time' => $record['activity_time'],
        'enrichment_type' => $record['enrichment_type'],
        'activity_description' => $record['activity_description'],
        'observed_response' => $record['observed_response'],
        'notes' => $record['notes']
    ];

    // Record the original data before deleting it.
    $auditStmt = $pdo->prepare("
        INSERT INTO audit_logs (
            user_id,
            action,
            entity_type,
            entity_id,
            old_values,
            new_values,
            description,
            created_at
        )
        VALUES (?, ?, ?, ?, ?, ?, ?, NOW())
    ");

    $auditStmt->execute([
        $currentUserId,
        'DELETE',
        'enrichment_records',
        $recordId,
        json_encode($oldValues, JSON_UNESCAPED_UNICODE),
        null,
        'Deleted enrichment record #' . $recordId
    ]);

    $deleteStmt = $pdo->prepare("
        DELETE FROM enrichment_records
        WHERE enrichment_id = ?
    ");
    $deleteStmt->execute([$recordId]);

    $pdo->commit();

    $_SESSION['enrichment_success'] =
        'Enrichment record deleted successfully.';

} catch (Throwable $e) {

    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }

    error_log('Enrichment deletion error: ' . $e->getMessage());

    $_SESSION['enrichment_error'] =
        $e instanceof RuntimeException
            ? $e->getMessage()
            : 'Unable to delete the enrichment record. Please try again.';
}

header('Location: index.php');
exit;

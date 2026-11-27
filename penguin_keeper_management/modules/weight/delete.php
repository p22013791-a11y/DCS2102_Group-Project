<?php
require_once __DIR__ . '/../../config/db.php';
require_once __DIR__ . '/../../includes/auth.php';

requireLogin();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: index.php');
    exit;
}

if (
    !isset($_POST['csrf_token'], $_SESSION['csrf_token']) ||
    !hash_equals($_SESSION['csrf_token'], $_POST['csrf_token'])
) {
    $_SESSION['weight_error'] = 'Invalid request. Please try again.';
    header('Location: index.php');
    exit;
}

$weightId = filter_input(INPUT_POST, 'weight_id', FILTER_VALIDATE_INT);

if (!$weightId || $weightId <= 0) {
    $_SESSION['weight_error'] = 'Invalid weight record.';
    header('Location: index.php');
    exit;
}

try {
    $pdo->beginTransaction();

    // Retrieve and lock the record.
    // A keeper can delete only their own record.
    $stmt = $pdo->prepare("
        SELECT *
        FROM weight_records
        WHERE weight_id = :weight_id
          AND keeper_id = :keeper_id
        FOR UPDATE
    ");

    $stmt->execute([
        ':weight_id' => $weightId,
        ':keeper_id' => $_SESSION['user_id']
    ]);

    $record = $stmt->fetch();

    if (!$record) {
        $pdo->rollBack();

        $_SESSION['weight_error'] =
            'Record not found or you are not authorised to delete it.';

        header('Location: index.php');
        exit;
    }

    // Record the deletion in the audit log before deleting.
    $oldValues = json_encode($record);

    $audit = $pdo->prepare("
        INSERT INTO audit_logs
            (user_id, action, entity_type, entity_id,
             old_values, description)
        VALUES
            (:user_id, 'DELETE', 'weight_records', :entity_id,
             :old_values, :description)
    ");

    $audit->execute([
        ':user_id' => $_SESSION['user_id'],
        ':entity_id' => $weightId,
        ':old_values' => $oldValues,
        ':description' => 'Deleted own weight record.'
    ]);

    // Delete the weight record.
    $delete = $pdo->prepare("
        DELETE FROM weight_records
        WHERE weight_id = :weight_id
          AND keeper_id = :keeper_id
    ");

    $delete->execute([
        ':weight_id' => $weightId,
        ':keeper_id' => $_SESSION['user_id']
    ]);

    if ($delete->rowCount() !== 1) {
        throw new Exception('Unable to delete the weight record.');
    }

    $pdo->commit();

    $_SESSION['weight_success'] = 'Weight record deleted successfully.';

} catch (Exception $e) {
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }

    error_log('Weight record deletion error: ' . $e->getMessage());
    $_SESSION['weight_error'] = 'Unable to delete the weight record.';
}

header('Location: index.php');
exit;
?>
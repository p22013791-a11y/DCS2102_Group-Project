<?php
require_once __DIR__ . '/../../includes/auth.php';

// Only Senior Keepers can archive penguin profiles.
requireRole('Senior Keeper');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    exit("Method not allowed.");
}

// Validate CSRF token.
$submittedToken = $_POST['csrf_token'] ?? '';

if (
    !isset($_SESSION['csrf_token']) ||
    !is_string($submittedToken) ||
    !hash_equals($_SESSION['csrf_token'], $submittedToken)
) {
    http_response_code(403);
    exit("Invalid request. Please refresh the page.");
}

// Validate penguin ID.
$penguinId = filter_input(INPUT_POST, 'penguin_id', FILTER_VALIDATE_INT);

if (!$penguinId || $penguinId < 1) {
    http_response_code(400);
    exit("Invalid penguin ID.");
}

try {
    $pdo->beginTransaction();

    // Lock the selected record while checking its archive status.
    $stmt = $pdo->prepare("
        SELECT penguin_id, animal_code, name, status,
               archived_at, archived_by
        FROM penguins
        WHERE penguin_id = ?
        FOR UPDATE
    ");
    $stmt->execute([$penguinId]);

    $penguin = $stmt->fetch();

    if (!$penguin) {
        $pdo->rollBack();
        http_response_code(404);
        exit("Penguin record not found.");
    }

    if ($penguin['archived_at'] !== null) {
        $pdo->rollBack();

        $_SESSION['penguin_error'] =
            "This penguin has already been archived.";

        header("Location: index.php?show_archived=1");
        exit();
    }

    $oldValues = [
        'animal_code' => $penguin['animal_code'],
        'name' => $penguin['name'],
        'status' => $penguin['status'],
        'archived_at' => null,
        'archived_by' => null
    ];

    // Archive the profile without deleting its data or changing its status.
    $updateStmt = $pdo->prepare("
        UPDATE penguins
        SET archived_at = CURRENT_TIMESTAMP,
            archived_by = ?
        WHERE penguin_id = ?
          AND archived_at IS NULL
    ");
    $updateStmt->execute([
        $_SESSION['user_id'],
        $penguinId
    ]);

    $newValues = [
        'animal_code' => $penguin['animal_code'],
        'name' => $penguin['name'],
        'status' => $penguin['status'],
        'archived_at' => 'CURRENT_TIMESTAMP',
        'archived_by' => (int) $_SESSION['user_id']
    ];

    // Record the archive action.
    $auditStmt = $pdo->prepare("
        INSERT INTO audit_logs (
            user_id,
            action,
            entity_type,
            entity_id,
            old_values,
            new_values,
            description
        ) VALUES (
            :user_id,
            :action,
            :entity_type,
            :entity_id,
            :old_values,
            :new_values,
            :description
        )
    ");

    $auditStmt->execute([
        'user_id' => $_SESSION['user_id'],
        'action' => 'ARCHIVE',
        'entity_type' => 'penguins',
        'entity_id' => $penguinId,
        'old_values' => json_encode($oldValues),
        'new_values' => json_encode($newValues),
        'description' => 'Archived penguin profile: ' .
            $penguin['animal_code']
    ]);

    $pdo->commit();

    $_SESSION['penguin_success'] =
        "Penguin " . $penguin['animal_code'] .
        " was archived successfully.";

    header("Location: index.php");
    exit();

} catch (Throwable $e) {

    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }

    error_log("Penguin archive error: " . $e->getMessage());

    $_SESSION['penguin_error'] =
        "Unable to archive the penguin. Please try again.";

    header("Location: index.php");
    exit();
}

<?php
require_once __DIR__ . '/../../includes/auth.php';

requireRole('Senior Keeper');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    exit("Method not allowed.");
}

$csrfToken = $_POST['csrf_token'] ?? '';

if (
    empty($_SESSION['csrf_token']) ||
    !hash_equals($_SESSION['csrf_token'], $csrfToken)
) {
    $_SESSION['enclosure_error'] =
        "Invalid request. Please refresh the page.";

    header("Location: index.php");
    exit();
}

$enclosureId = filter_input(INPUT_POST, 'enclosure_id', FILTER_VALIDATE_INT);

if (!$enclosureId || $enclosureId < 1) {
    $_SESSION['enclosure_error'] = "Invalid enclosure ID.";
    header("Location: index.php");
    exit();
}

try {
    $pdo->beginTransaction();

    // Lock the enclosure while checking and archiving it.
    $stmt = $pdo->prepare("
        SELECT *
        FROM enclosures
        WHERE enclosure_id = ?
        FOR UPDATE
    ");
    $stmt->execute([$enclosureId]);
    $enclosure = $stmt->fetch();

    if (!$enclosure) {
        throw new RuntimeException("Enclosure not found.");
    }

    if ($enclosure['archived_at'] !== null) {
        throw new RuntimeException("This enclosure is already archived.");
    }

    // An enclosure cannot be archived while it contains current penguins.
    $countStmt = $pdo->prepare("
        SELECT COUNT(*)
        FROM penguins
        WHERE enclosure_id = ?
          AND archived_at IS NULL
    ");
    $countStmt->execute([$enclosureId]);

    $penguinCount = (int)$countStmt->fetchColumn();

    if ($penguinCount > 0) {
        throw new RuntimeException(
            "This enclosure still contains " . $penguinCount .
            " non-archived penguin(s). Please reassign them before archiving."
        );
    }

    $archiveStmt = $pdo->prepare("
        UPDATE enclosures
        SET
            archived_at = CURRENT_TIMESTAMP,
            archived_by = ?
        WHERE enclosure_id = ?
          AND archived_at IS NULL
    ");

    $archiveStmt->execute([
        $_SESSION['user_id'],
        $enclosureId
    ]);

    if ($archiveStmt->rowCount() !== 1) {
        throw new RuntimeException(
            "The enclosure could not be archived. Please try again."
        );
    }

    $newValues = [
        'enclosure_code' => $enclosure['enclosure_code'],
        'enclosure_name' => $enclosure['enclosure_name'],
        'status' => $enclosure['status'],
        'archived_at' => date('Y-m-d H:i:s'),
        'archived_by' => $_SESSION['user_id']
    ];

    $auditStmt = $pdo->prepare("
        INSERT INTO audit_logs (
            user_id,
            action,
            entity_type,
            entity_id,
            old_values,
            new_values,
            description
        )
        VALUES (?, 'ARCHIVE', 'enclosures', ?, ?, ?, ?)
    ");

    $auditStmt->execute([
        $_SESSION['user_id'],
        $enclosureId,
        json_encode($enclosure),
        json_encode($newValues),
        "Archived enclosure " . $enclosure['enclosure_code']
    ]);

    $pdo->commit();

    $_SESSION['enclosure_success'] =
        "Enclosure " . $enclosure['enclosure_code'] .
        " was archived successfully.";

} catch (Throwable $e) {
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }

    if ($e instanceof RuntimeException) {
        $_SESSION['enclosure_error'] = $e->getMessage();
    } else {
        error_log("Enclosure archive error: " . $e->getMessage());

        $_SESSION['enclosure_error'] =
            "Unable to archive the enclosure. Please try again.";
    }
}

header("Location: index.php");
exit();

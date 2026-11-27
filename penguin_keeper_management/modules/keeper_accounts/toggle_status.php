<?php
require_once __DIR__ . '/../../includes/auth.php';
require_once __DIR__ . '/../../config/db.php';

/** @var \PDO $pdo */

requireRole('Senior Keeper');

// Only accept POST requests.
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header("Location: index.php");
    exit();
}

// Verify CSRF token.
if (
    !isset($_POST['csrf_token']) ||
    !hash_equals($_SESSION['csrf_token'] ?? '', $_POST['csrf_token'])
) {
    exit("Invalid request. Please try again.");
}

$userId = filter_input(INPUT_POST, 'user_id', FILTER_VALIDATE_INT);
$action = $_POST['action'] ?? '';

if (!$userId || !in_array($action, ['activate', 'deactivate'], true)) {
    exit("Invalid account request.");
}

// Prevent changing your own account status.
if ($userId === (int)$_SESSION['user_id']) {
    $_SESSION['account_message'] = "You cannot change your own account status.";
    header("Location: index.php");
    exit();
}

$lockAcquired = false;

try {
    // Serialize account status changes.
    $lockResult = $pdo->query(
        "SELECT GET_LOCK('pkms_account_status_change', 10)"
    )->fetchColumn();

    if ((int)$lockResult !== 1) {
        throw new Exception("Unable to process the request. Please try again.");
    }

    $lockAcquired = true;

    $pdo->beginTransaction();

    // Find the selected account.
    $stmt = $pdo->prepare("
        SELECT user_id, staff_code, role, status
        FROM users
        WHERE user_id = ?
        FOR UPDATE
    ");

    $stmt->execute([$userId]);
    $account = $stmt->fetch();

    if (!$account) {
        throw new Exception("Keeper account not found.");
    }

    $newStatus = ($action === 'activate') ? 'Active' : 'Inactive';

    if ($account['status'] === $newStatus) {
        throw new Exception("The account already has this status.");
    }

    // Prevent deactivating the last active Senior Keeper.
    if (
        $action === 'deactivate' &&
        $account['role'] === 'Senior Keeper' &&
        $account['status'] === 'Active'
    ) {
        $stmt = $pdo->query("
            SELECT COUNT(*)
            FROM users
            WHERE role = 'Senior Keeper'
            AND status = 'Active'
        ");

        $activeSeniorCount = (int)$stmt->fetchColumn();

        if ($activeSeniorCount <= 1) {
            throw new Exception(
                "Cannot deactivate the last active Senior Keeper."
            );
        }
    }

    // Update the account status.
    $stmt = $pdo->prepare("
        UPDATE users
        SET status = ?
        WHERE user_id = ?
    ");

    $stmt->execute([$newStatus, $userId]);

    $pdo->commit();

    $_SESSION['account_message'] =
        "Account " . htmlspecialchars($account['staff_code']) .
        " has been " . strtolower($newStatus) . " successfully.";

} catch (Exception $e) {

    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }

    error_log("Account status update error: " . $e->getMessage());

    $_SESSION['account_message'] = $e->getMessage();

} finally {

    if ($lockAcquired) {
        $pdo->query(
            "SELECT RELEASE_LOCK('pkms_account_status_change')"
        );
    }
}

header("Location: index.php");
exit();
?>

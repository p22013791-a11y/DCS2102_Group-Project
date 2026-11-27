<?php

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Load database connection.
require_once __DIR__ . '/../config/db.php';

// Check whether the user is logged in AND still has an active account.
function requireLogin()
{
    global $pdo;

    if (!isset($_SESSION['user_id'])) {
        header("Location: /penguin_keeper_management/index.php");
        exit();
    }

    try {
        // Verify the account against the latest database status.
        $stmt = $pdo->prepare("
            SELECT user_id, staff_code, full_name, role, status
            FROM users
            WHERE user_id = ?
        ");

        $stmt->execute([$_SESSION['user_id']]);
        $user = $stmt->fetch();

        // Account does not exist or has been deactivated.
        if (!$user || $user['status'] !== 'Active') {

            $_SESSION = [];

            if (ini_get("session.use_cookies")) {
                $params = session_get_cookie_params();

                setcookie(
                    session_name(),
                    '',
                    time() - 42000,
                    $params['path'],
                    $params['domain'],
                    $params['secure'],
                    $params['httponly']
                );
            }

            session_destroy();

            header(
                "Location: /penguin_keeper_management/index.php?error=account_inactive"
            );
            exit();
        }

        // Refresh session information from the database.
        $_SESSION['user_id'] = $user['user_id'];
        $_SESSION['staff_code'] = $user['staff_code'];
        $_SESSION['full_name'] = $user['full_name'];
        $_SESSION['role'] = $user['role'];

    } catch (PDOException $e) {

        error_log("Session validation error: " . $e->getMessage());

        // Fail closed if the database cannot verify the account.
        http_response_code(503);
        exit("Unable to verify your account. Please try again later.");
    }
}

// Check the required role.
function requireRole(string $requiredRole): void
{
    requireLogin();

    if ($_SESSION['role'] !== $requiredRole) {
        http_response_code(403);
        exit("Access denied. You do not have permission to access this page.");
    }
}

?>

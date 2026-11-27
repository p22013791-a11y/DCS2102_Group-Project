<?php

// Configure secure session cookie settings
session_set_cookie_params([
    'httponly' => true,
    'secure' => !empty($_SERVER['HTTPS']),
    'samesite' => 'Strict'
]);

session_start();

require_once __DIR__ . '/config/db.php';

/** @var \PDO $pdo */

// Redirect users who are already logged in
if (isset($_SESSION['user_id'])) {
    header("Location: dashboard.php");
    exit;
}

// Generate CSRF token
if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

$error = "";

// Process login
if ($_SERVER["REQUEST_METHOD"] === "POST") {

    $staffCode = trim($_POST['staff_code'] ?? "");
    $password = $_POST['password'] ?? "";

    // Validate CSRF token
    if (
        !isset($_POST['csrf_token']) ||
        !hash_equals($_SESSION['csrf_token'], $_POST['csrf_token'])
    ) {

        $error = "Invalid request. Please refresh the page.";

    } elseif ($staffCode === "" || $password === "") {

        $error = "Please enter your Staff ID and password.";

    } else {

        try {

            // Find the account using Staff ID
            $stmt = $pdo->prepare(
                "SELECT user_id, staff_code, full_name,
                        password_hash, role, status
                 FROM users
                 WHERE staff_code = ?
                 LIMIT 1"
            );

            $stmt->execute([$staffCode]);

            $user = $stmt->fetch();

            // Verify password and account status
            if (
                $user &&
                $user['status'] === 'Active' &&
                password_verify($password, $user['password_hash'])
            ) {

                // Prevent session fixation
                session_regenerate_id(true);

                // Store authenticated account information
                $_SESSION['user_id'] = $user['user_id'];
                $_SESSION['staff_code'] = $user['staff_code'];
                $_SESSION['full_name'] = $user['full_name'];
                $_SESSION['role'] = $user['role'];

                // Redirect to dashboard
                header("Location: dashboard.php");
                exit;

            } else {

                $error = "Invalid Staff ID or password, or your account is inactive.";

            }

        } catch (PDOException $e) {

            error_log("Login error: " . $e->getMessage());

            $error = "Unable to process login. Please try again later.";
        }
    }
}

?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>PKMS Login</title>

    <style>

        * {
            box-sizing: border-box;
        }

        body {
            margin: 0;
            font-family: Arial, sans-serif;
            background: #eaf0f6;
            min-height: 100vh;
            display: flex;
            justify-content: center;
            align-items: center;
            padding: 25px;
        }

        .login-container {
            width: 100%;
            max-width: 950px;
            min-height: 520px;
            background: white;
            display: grid;
            grid-template-columns: 1fr 1fr;
            border-radius: 14px;
            overflow: hidden;
            box-shadow: 0 8px 30px rgba(0,0,0,0.10);
        }

        .welcome-panel {
            background: #173b53;
            color: white;
            padding: 55px 40px;
            display: flex;
            flex-direction: column;
            justify-content: center;
        }

        .welcome-panel h1 {
            font-size: 32px;
            margin-bottom: 18px;
        }

        .welcome-panel p {
            line-height: 1.7;
            color: #dbe8ef;
        }

        .category-list {
            margin-top: 25px;
            line-height: 2.2;
        }

        .form-panel {
            padding: 55px 45px;
            display: flex;
            flex-direction: column;
            justify-content: center;
        }

        .form-panel h2 {
            color: #173b53;
            margin-bottom: 10px;
        }

        .subtitle {
            color: #64748b;
            margin-bottom: 30px;
            line-height: 1.5;
        }

        label {
            display: block;
            margin-bottom: 8px;
            margin-top: 18px;
            font-weight: bold;
            color: #334155;
        }

        input {
            width: 100%;
            padding: 13px;
            border: 1px solid #cbd5e1;
            border-radius: 6px;
            font-size: 15px;
        }

        input:focus {
            outline: none;
            border-color: #2563eb;
        }

        button {
            width: 100%;
            margin-top: 28px;
            padding: 14px;
            background: #2563eb;
            color: white;
            border: none;
            border-radius: 6px;
            font-size: 16px;
            cursor: pointer;
        }

        button:hover {
            background: #1d4ed8;
        }

        .error-message {
            background: #fee2e2;
            color: #991b1b;
            padding: 12px;
            border-radius: 6px;
            margin-bottom: 15px;
        }

        @media (max-width: 700px) {

            .login-container {
                grid-template-columns: 1fr;
            }

            .welcome-panel {
                padding: 35px 25px;
            }

            .form-panel {
                padding: 35px 25px;
            }

        }

    </style>

</head>

<body>

<div class="login-container">

    <!-- Left welcome section -->
    <section class="welcome-panel">

        <h1>Penguin Keeper Management System</h1>

        <p>
            Welcome to PKMS, a centralised information
            management system designed to support penguin
            husbandry activities and daily facility operations.
        </p>

        <div class="category-list">
            <div>• Penguin Management</div>
            <div>• Feeding and Weight Records</div>
            <div>• Health and Behaviour Observations</div>
            <div>• Enclosure Monitoring</div>
        </div>

    </section>


    <!-- Right login section -->
    <section class="form-panel">

        <h2>Keeper Login</h2>

        <p class="subtitle">
            Enter your staff credentials to access PKMS.
        </p>

        <?php if (isset($_GET['error']) && $_GET['error'] === 'account_inactive'): ?>

            <p class="error-message">
                Your account has been deactivated.
                Please contact a Senior Keeper for assistance.
            </p>

        <?php endif; ?>

        <?php if ($error !== ""): ?>

            <div class="error-message">
                <?= htmlspecialchars($error) ?>
            </div>

        <?php endif; ?>

        <form method="POST" action="">

            <input
                type="hidden"
                name="csrf_token"
                value="<?= htmlspecialchars($_SESSION['csrf_token']) ?>"
            >

            <label for="staff_code">Staff ID</label>

            <input
                type="text"
                id="staff_code"
                name="staff_code"
                placeholder="Example: SK-001"
                maxlength="20"
                autocomplete="username"
                required
            >

            <label for="password">Password</label>

            <input
                type="password"
                id="password"
                name="password"
                placeholder="Enter your password"
                autocomplete="current-password"
                required
            >

            <button type="submit">
                Login
            </button>

        </form>

    </section>

</div>

</body>
</html>

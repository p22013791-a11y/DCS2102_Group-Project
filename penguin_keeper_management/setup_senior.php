<?php
session_start();

require_once "config/db.php";

// Generate a CSRF token for form protection
if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

$message = "";
$messageType = "";
$success = false;

// Check whether a Senior Keeper already exists
function seniorKeeperExists(PDO $pdo): bool {
    $stmt = $pdo->prepare(
        "SELECT COUNT(*) FROM users WHERE role = ?"
    );

    $stmt->execute(['Senior Keeper']);

    return (int)$stmt->fetchColumn() > 0;
}

$setupCompleted = seniorKeeperExists($pdo);

// Process registration form
if ($_SERVER["REQUEST_METHOD"] === "POST" && !$setupCompleted) {

    // Verify CSRF token
    if (
        !isset($_POST['csrf_token']) ||
        !hash_equals($_SESSION['csrf_token'], $_POST['csrf_token'])
    ) {
        $message = "Invalid form request. Please refresh the page.";
        $messageType = "error";

    } else {

        $fullName = trim($_POST['full_name'] ?? "");
        $email = trim($_POST['email'] ?? "");
        $password = $_POST['password'] ?? "";
        $confirmPassword = $_POST['confirm_password'] ?? "";

        // Validate form fields
        if ($fullName === "" || $email === "" ||
            $password === "" || $confirmPassword === "") {

            $message = "Please complete all fields.";
            $messageType = "error";

        } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {

            $message = "Please enter a valid email address.";
            $messageType = "error";

        } elseif (strlen($password) < 12) {

            $message = "Password must contain at least 12 characters.";
            $messageType = "error";

        } elseif ($password !== $confirmPassword) {

            $message = "Passwords do not match.";
            $messageType = "error";

        } else {

            $lockAcquired = false;

            try {
                // Prevent two simultaneous first-time registrations
                $lockStmt = $pdo->query(
                    "SELECT GET_LOCK('pkms_initial_senior_setup', 10)"
                );

                $lockAcquired = ((int)$lockStmt->fetchColumn() === 1);

                if (!$lockAcquired) {
                    throw new RuntimeException(
                        "Setup is currently busy. Please try again."
                    );
                }

                $pdo->beginTransaction();

                // Check again while holding the setup lock
                if (seniorKeeperExists($pdo)) {

                    $pdo->rollBack();

                    $message = "Initial setup has already been completed.";
                    $messageType = "error";
                    $setupCompleted = true;

                } else {

                    // Hash the password before storing it
                    $passwordHash = password_hash(
                        $password,
                        PASSWORD_DEFAULT
                    );

                    // Create the first Senior Keeper account
                    $stmt = $pdo->prepare(
                        "INSERT INTO users
                        (staff_code, full_name, email,
                         password_hash, role, status)
                        VALUES (?, ?, ?, ?, ?, ?)"
                    );

                    $stmt->execute([
                        'SK-001',
                        $fullName,
                        $email,
                        $passwordHash,
                        'Senior Keeper',
                        'Active'
                    ]);

                    $pdo->commit();

                    $success = true;
                    $setupCompleted = true;

                    // Refresh the CSRF token
                    $_SESSION['csrf_token'] =
                        bin2hex(random_bytes(32));

                    $message = "Senior Keeper account created successfully!";
                    $messageType = "success";
                }

            } catch (Throwable $e) {

                if ($pdo->inTransaction()) {
                    $pdo->rollBack();
                }

                error_log("Initial setup error: " . $e->getMessage());

                if ($e instanceof RuntimeException) {
                    $message = $e->getMessage();
                } else {
                    $message = "Unable to create the account. Please check the details and try again.";
                }

                $messageType = "error";

            } finally {

                if ($lockAcquired) {
                    $pdo->query(
                        "SELECT RELEASE_LOCK('pkms_initial_senior_setup')"
                    );
                }
            }
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>Initial Senior Keeper Setup - PKMS</title>

    <style>
        body {
            font-family: Arial, sans-serif;
            background: #f1f5f9;
            margin: 0;
            padding: 40px 15px;
        }

        .container {
            max-width: 450px;
            margin: auto;
            background: white;
            padding: 30px;
            border-radius: 12px;
            box-shadow: 0 4px 15px rgba(0,0,0,0.08);
        }

        h1 {
            color: #1e293b;
            margin-bottom: 8px;
        }

        .description {
            color: #64748b;
            line-height: 1.5;
            margin-bottom: 25px;
        }

        label {
            display: block;
            margin-top: 16px;
            margin-bottom: 6px;
            font-weight: bold;
            color: #334155;
        }

        input {
            width: 100%;
            box-sizing: border-box;
            padding: 11px;
            border: 1px solid #cbd5e1;
            border-radius: 6px;
        }

        button {
            width: 100%;
            margin-top: 24px;
            padding: 12px;
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

        .message {
            padding: 12px;
            border-radius: 6px;
            margin-bottom: 18px;
        }

        .success {
            background: #dcfce7;
            color: #166534;
        }

        .error {
            background: #fee2e2;
            color: #991b1b;
        }

        .info {
            background: #e0f2fe;
            color: #075985;
            padding: 14px;
            border-radius: 6px;
            margin-top: 15px;
        }

        .account-info {
            background: #f1f5f9;
            padding: 12px;
            border-radius: 6px;
            margin: 15px 0;
        }

        .account-info p {
            margin: 6px 0;
        }
    </style>
</head>

<body>

<div class="container">

    <h1>Initial Senior Keeper Setup</h1>

    <p class="description">
        Create the first administrator account for the
        Penguin Keeper Management System.
    </p>

    <?php if ($message !== ""): ?>
        <div class="message <?= htmlspecialchars($messageType) ?>">
            <?= htmlspecialchars($message) ?>
        </div>
    <?php endif; ?>

    <?php if ($success): ?>

        <div class="info">
            Your first Senior Keeper account has been created.
            The initial setup page is now locked.
        </div>

        <div class="account-info">
            <p><strong>Staff ID:</strong> SK-001</p>
            <p><strong>Role:</strong> Senior Keeper</p>
            <p><strong>Status:</strong> Active</p>
        </div>

        <p>You can proceed to the login page once it has been implemented.</p>

    <?php elseif ($setupCompleted): ?>

        <div class="info">
            Initial setup has already been completed.
            New keeper accounts must be registered by an authorised
            Senior Keeper after logging in.
        </div>

    <?php else: ?>

        <div class="account-info">
            <p><strong>Staff ID:</strong> SK-001</p>
            <p><strong>Role:</strong> Senior Keeper</p>
            <p><strong>Account status:</strong> Active</p>
        </div>

        <form method="POST" action="">

            <input
                type="hidden"
                name="csrf_token"
                value="<?= htmlspecialchars($_SESSION['csrf_token']) ?>"
            >

            <label for="full_name">Full Name</label>
            <input
                type="text"
                id="full_name"
                name="full_name"
                maxlength="100"
                required
            >

            <label for="email">Email Address</label>
            <input
                type="email"
                id="email"
                name="email"
                maxlength="100"
                required
            >

            <label for="password">Password</label>
            <input
                type="password"
                id="password"
                name="password"
                minlength="12"
                autocomplete="new-password"
                required
            >

            <label for="confirm_password">Confirm Password</label>
            <input
                type="password"
                id="confirm_password"
                name="confirm_password"
                minlength="12"
                autocomplete="new-password"
                required
            >

            <button type="submit">
                Create Senior Keeper Account
            </button>

        </form>

    <?php endif; ?>

</div>

</body>
</html>

<?php
require_once __DIR__ . '/../../includes/auth.php';
require_once __DIR__ . '/../../config/db.php';

/** @var \PDO $pdo */

// Only Senior Keepers may register accounts.
requireRole('Senior Keeper');

$error = '';
$success = '';

// Generate a CSRF token.
if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    // Verify CSRF token.
    if (
        !isset($_POST['csrf_token']) ||
        !hash_equals($_SESSION['csrf_token'], $_POST['csrf_token'])
    ) {
        $error = 'Invalid request. Please refresh the page and try again.';
    } else {

        $fullName = trim($_POST['full_name'] ?? '');
        $email = trim($_POST['email'] ?? '');
        $role = $_POST['role'] ?? '';
        $password = $_POST['password'] ?? '';
        $confirmPassword = $_POST['confirm_password'] ?? '';

        // Validate form information.
        if ($fullName === '' || $email === '' || $password === '') {
            $error = 'Please complete all fields.';
        } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $error = 'Please enter a valid email address.';
        } elseif (!in_array($role, ['Senior Keeper', 'Junior Keeper'], true)) {
            $error = 'Please select a valid keeper role.';
        } elseif (strlen($password) < 12) {
            $error = 'Password must contain at least 12 characters.';
        } elseif ($password !== $confirmPassword) {
            $error = 'Passwords do not match.';
        } else {

            // Determine staff ID prefix.
            $prefix = ($role === 'Senior Keeper') ? 'SK' : 'JK';

            $lockAcquired = false;

            try {
                // Prevent simultaneous registrations generating the same ID.
                $lockResult = $pdo->query(
                    "SELECT GET_LOCK('pkms_staff_code_generation', 10)"
                )->fetchColumn();

                if ((int)$lockResult !== 1) {
                    throw new Exception(
                        'Unable to generate staff ID. Please try again.'
                    );
                }

                $lockAcquired = true;

                // Find the highest existing number for this role.
                $stmt = $pdo->prepare("
                    SELECT MAX(CAST(SUBSTRING(staff_code, 4) AS UNSIGNED))
                    FROM users
                    WHERE staff_code LIKE ?
                ");

                $stmt->execute([$prefix . '-%']);

                $lastNumber = (int)$stmt->fetchColumn();
                $nextNumber = $lastNumber + 1;

                $staffCode = $prefix . '-' . str_pad(
                    $nextNumber,
                    3,
                    '0',
                    STR_PAD_LEFT
                );

                // Hash the password before storing it.
                $passwordHash = password_hash(
                    $password,
                    PASSWORD_DEFAULT
                );

                // Insert the new keeper account.
                $stmt = $pdo->prepare("
                    INSERT INTO users
                    (staff_code, full_name, email, password_hash, role, status)
                    VALUES (?, ?, ?, ?, ?, 'Active')
                ");

                $stmt->execute([
                    $staffCode,
                    $fullName,
                    $email,
                    $passwordHash,
                    $role
                ]);

                $success = "Keeper account created successfully. Staff ID: "
                    . $staffCode;

                // Clear the form after successful registration.
                $_POST = [];

                // Generate a new token after registration.
                $_SESSION['csrf_token'] = bin2hex(random_bytes(32));

            } catch (PDOException $e) {
                error_log("Keeper registration error: " . $e->getMessage());

                if ($e->getCode() === '23000') {
                    $error = 'This email address or staff ID already exists.';
                } else {
                    $error = 'Unable to create the account. Please try again.';
                }

            } catch (Exception $e) {
                $error = $e->getMessage();

            } finally {
                if ($lockAcquired) {
                    $pdo->query(
                        "SELECT RELEASE_LOCK('pkms_staff_code_generation')"
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
    <title>Register Keeper | PKMS</title>

    <link rel="stylesheet"
          href="/penguin_keeper_management/assets/css/style.css">
</head>

<body>

<div class="app-layout">

    <?php include __DIR__ . '/../../includes/sidebar.php'; ?>

    <main class="main-content">

        <?php
        $breadcrumbs = ['Keeper Accounts', 'Register Keeper'];
        include __DIR__ . '/../../includes/topbar.php';
        ?>

        <div class="page-content">

            <div class="page-header">
                <div>
                    <h1>Register New Keeper</h1>
                    <p>Create a Senior Keeper or Junior Keeper account.</p>
                </div>
            </div>

            <?php if ($error !== ''): ?>
                <div class="error-message">
                    <?= htmlspecialchars($error, ENT_QUOTES, 'UTF-8') ?>
                </div>
            <?php endif; ?>

            <?php if ($success !== ''): ?>
                <div class="success-message">
                    <?= htmlspecialchars($success, ENT_QUOTES, 'UTF-8') ?>
                </div>
            <?php endif; ?>

            <section class="form-section">

                <form method="POST" action="create.php">

                    <input
                        type="hidden"
                        name="csrf_token"
                        value="<?= htmlspecialchars($_SESSION['csrf_token'], ENT_QUOTES, 'UTF-8') ?>"
                    >

                    <div class="form-group">
                        <label for="full_name">Full Name *</label>
                        <input
                            type="text"
                            id="full_name"
                            name="full_name"
                            required
                            maxlength="100"
                            value="<?= htmlspecialchars($_POST['full_name'] ?? '', ENT_QUOTES, 'UTF-8') ?>"
                            placeholder="Enter keeper's full name"
                        >
                    </div>

                    <div class="form-group">
                        <label for="email">Email Address *</label>
                        <input
                            type="email"
                            id="email"
                            name="email"
                            required
                            maxlength="100"
                            value="<?= htmlspecialchars($_POST['email'] ?? '', ENT_QUOTES, 'UTF-8') ?>"
                            placeholder="Enter email address"
                        >
                    </div>

                    <div class="form-group">
                        <label for="role">Keeper Role *</label>
                        <select id="role" name="role" required>
                            <option value="">Select a role</option>
                            <option value="Senior Keeper"
                                <?= ($_POST['role'] ?? '') === 'Senior Keeper' ? 'selected' : '' ?>>
                                Senior Keeper
                            </option>
                            <option value="Junior Keeper"
                                <?= ($_POST['role'] ?? '') === 'Junior Keeper' ? 'selected' : '' ?>>
                                Junior Keeper
                            </option>
                        </select>
                    </div>

                    <div class="form-group">
                        <label for="password">Initial Password *</label>
                        <input
                            type="password"
                            id="password"
                            name="password"
                            required
                            minlength="12"
                            placeholder="Minimum 12 characters"
                        >
                    </div>

                    <div class="form-group">
                        <label for="confirm_password">Confirm Password *</label>
                        <input
                            type="password"
                            id="confirm_password"
                            name="confirm_password"
                            required
                            minlength="12"
                            placeholder="Re-enter the initial password"
                        >
                    </div>

                    <div class="form-actions">
                        <button type="submit" class="btn-primary">
                            Register Keeper
                        </button>

                        <a href="index.php" class="btn-secondary">
                            Cancel
                        </a>
                    </div>

                </form>

            </section>

        </div>

    </main>

</div>

</body>
</html>
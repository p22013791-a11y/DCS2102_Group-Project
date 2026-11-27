<?php
require_once __DIR__ . '/../../includes/auth.php';

requireRole('Senior Keeper');

$errors = [];

$enclosureName = '';
$capacity = '';
$status = 'Active';
$notes = '';

if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    $enclosureName = trim($_POST['enclosure_name'] ?? '');
    $capacity = trim($_POST['capacity'] ?? '');
    $status = $_POST['status'] ?? 'Active';
    $notes = trim($_POST['notes'] ?? '');
    $csrfToken = $_POST['csrf_token'] ?? '';

    if (!hash_equals($_SESSION['csrf_token'], $csrfToken)) {
        $errors[] = "Invalid request. Please refresh the page.";
    }

    if ($enclosureName === '') {
        $errors[] = "Please enter the enclosure name.";
    } elseif (mb_strlen($enclosureName) > 100) {
        $errors[] = "Enclosure name must not exceed 100 characters.";
    }

    if ($capacity === '' || filter_var($capacity, FILTER_VALIDATE_INT) === false) {
        $errors[] = "Please enter a valid enclosure capacity.";
    } elseif ((int)$capacity < 1 || (int)$capacity > 10000) {
        $errors[] = "Capacity must be between 1 and 10000.";
    }

    if (!in_array($status, ['Active', 'Maintenance'], true)) {
        $errors[] = "Invalid enclosure status.";
    }

    if (mb_strlen($notes) > 5000) {
        $errors[] = "Notes must not exceed 5000 characters.";
    }

    if (empty($errors)) {
        try {
            $lockStmt = $pdo->query(
                "SELECT GET_LOCK('pkms_enclosure_code_generation', 10)"
            );

            $lockAcquired = (int)$lockStmt->fetchColumn();

            if ($lockAcquired !== 1) {
                throw new RuntimeException(
                    "Unable to generate an enclosure code. Please try again."
                );
            }

            try {
                $pdo->beginTransaction();

                $codeStmt = $pdo->query("
                    SELECT MAX(
                        CAST(SUBSTRING(enclosure_code, 3) AS UNSIGNED)
                    )
                    FROM enclosures
                    WHERE enclosure_code REGEXP '^E-[0-9]+$'
                ");

                $lastNumber = (int)$codeStmt->fetchColumn();
                $nextNumber = $lastNumber + 1;

                $enclosureCode = 'E-' . str_pad(
                    (string)$nextNumber,
                    2,
                    '0',
                    STR_PAD_LEFT
                );

                $insertStmt = $pdo->prepare("
                    INSERT INTO enclosures (
                        enclosure_code,
                        enclosure_name,
                        capacity,
                        status,
                        notes
                    )
                    VALUES (?, ?, ?, ?, ?)
                ");

                $insertStmt->execute([
                    $enclosureCode,
                    $enclosureName,
                    (int)$capacity,
                    $status,
                    $notes !== '' ? $notes : null
                ]);

                $pdo->commit();

                $_SESSION['enclosure_success'] =
                    "Enclosure " . $enclosureCode . " was created successfully.";

                header("Location: index.php");
                exit();

            } catch (Throwable $e) {
                if ($pdo->inTransaction()) {
                    $pdo->rollBack();
                }

                throw $e;

            } finally {
                $pdo->query(
                    "SELECT RELEASE_LOCK('pkms_enclosure_code_generation')"
                );
            }

        } catch (Throwable $e) {
            error_log("Enclosure creation error: " . $e->getMessage());

            if ($e instanceof RuntimeException) {
                $errors[] = $e->getMessage();
            } else {
                $errors[] = "Unable to create the enclosure. Please try again.";
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
    <title>Add Enclosure | PKMS</title>

    <link rel="stylesheet"
          href="/penguin_keeper_management/assets/css/style.css">
</head>
<body>

<div class="app-layout">

    <?php include __DIR__ . '/../../includes/sidebar.php'; ?>

    <main class="main-content">

        <?php
        $breadcrumbs = ['Enclosures', 'Add Enclosure'];
        include __DIR__ . '/../../includes/topbar.php';
        ?>

        <div class="page-content">

            <div class="page-header">
                <div>
                    <h1>Add New Enclosure</h1>
                    <p>Register a new penguin enclosure.</p>
                </div>

            </div>

            <?php if (!empty($errors)): ?>
                <div class="error-message">
                    <ul>
                        <?php foreach ($errors as $error): ?>
                            <li><?= htmlspecialchars($error) ?></li>
                        <?php endforeach; ?>
                    </ul>
                </div>
            <?php endif; ?>

            <section class="form-section">

                <form method="POST" action="create.php">

                    <input type="hidden"
                           name="csrf_token"
                           value="<?= htmlspecialchars($_SESSION['csrf_token']) ?>">

                    <div class="form-group">
                        <label>Enclosure Code</label>
                        <input type="text"
                               value="Automatically generated"
                               disabled>
                        <small>
                            The system will generate the enclosure code.
                        </small>
                    </div>

                    <div class="form-group">
                        <label for="enclosure_name">
                            Enclosure Name
                        </label>

                        <input
                            type="text"
                            id="enclosure_name"
                            name="enclosure_name"
                            maxlength="100"
                            required
                            value="<?= htmlspecialchars($enclosureName) ?>"
                            placeholder="Enter enclosure name"
                        >
                    </div>

                    <div class="form-group">
                        <label for="capacity">
                            Maximum Capacity
                        </label>

                        <input
                            type="number"
                            id="capacity"
                            name="capacity"
                            min="1"
                            max="10000"
                            required
                            value="<?= htmlspecialchars($capacity) ?>"
                            placeholder="Enter maximum capacity"
                        >
                    </div>

                    <div class="form-group">
                        <label for="status">Status</label>

                        <select id="status" name="status" required>
                            <option value="Active"
                                <?= $status === 'Active' ? 'selected' : '' ?>>
                                Active
                            </option>

                            <option value="Maintenance"
                                <?= $status === 'Maintenance' ? 'selected' : '' ?>>
                                Maintenance
                            </option>
                        </select>
                    </div>

                    <div class="form-group">
                        <label for="notes">Notes</label>

                        <textarea
                            id="notes"
                            name="notes"
                            rows="4"
                            maxlength="5000"
                            placeholder="Enter additional enclosure information"
                        ><?= htmlspecialchars($notes) ?></textarea>
                    </div>

                    <div class="form-actions">
                        <button type="submit" class="btn-primary">
                            Create Enclosure
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

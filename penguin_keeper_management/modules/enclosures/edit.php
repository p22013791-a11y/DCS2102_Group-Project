<?php
require_once __DIR__ . '/../../includes/auth.php';

requireRole('Senior Keeper');

$errors = [];
$enclosure = null;
$enclosureId = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT);

if (!$enclosureId || $enclosureId < 1) {
    http_response_code(400);
    exit("Invalid enclosure ID.");
}

if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

try {
    $stmt = $pdo->prepare("
        SELECT
            e.enclosure_id,
            e.enclosure_code,
            e.enclosure_name,
            e.capacity,
            e.status,
            e.notes,
            COUNT(p.penguin_id) AS penguin_count
        FROM enclosures e
        LEFT JOIN penguins p
            ON p.enclosure_id = e.enclosure_id
            AND p.archived_at IS NULL
        WHERE e.enclosure_id = ?
            AND e.archived_at IS NULL
        GROUP BY
            e.enclosure_id,
            e.enclosure_code,
            e.enclosure_name,
            e.capacity,
            e.status,
            e.notes
    ");

    $stmt->execute([$enclosureId]);
    $enclosure = $stmt->fetch();

    if (!$enclosure) {
        http_response_code(404);
        exit("Enclosure not found.");
    }

} catch (PDOException $e) {
    error_log("Enclosure loading error: " . $e->getMessage());
    http_response_code(500);
    exit("Unable to load enclosure information.");
}

$enclosureName = $enclosure['enclosure_name'];
$capacity = (string)$enclosure['capacity'];
$status = $enclosure['status'] ?? '';
$notes = $enclosure['notes'] ?? '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    $enclosureName = trim($_POST['enclosure_name'] ?? '');
    $capacity = trim($_POST['capacity'] ?? '');
    $status = $_POST['status'] ?? '';
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
        $errors[] = "Please select a valid enclosure status.";
    }

    if (mb_strlen($notes) > 5000) {
        $errors[] = "Notes must not exceed 5000 characters.";
    }

    if (empty($errors)) {
        try {
            $pdo->beginTransaction();

            $lockStmt = $pdo->prepare("
                SELECT *
                FROM enclosures
                WHERE enclosure_id = ?
                FOR UPDATE
            ");
            $lockStmt->execute([$enclosureId]);
            $oldEnclosure = $lockStmt->fetch();

            if (!$oldEnclosure) {
                throw new RuntimeException("Enclosure not found.");
            }

            $countStmt = $pdo->prepare("
                SELECT COUNT(*)
                FROM penguins
                WHERE enclosure_id = ?
                  AND archived_at IS NULL
            ");
            $countStmt->execute([$enclosureId]);
            $penguinCount = (int)$countStmt->fetchColumn();

            if ((int)$capacity < $penguinCount) {
                throw new RuntimeException(
                    "Capacity cannot be lower than the current number of penguins (" .
                    $penguinCount . ")."
                );
            }

            $updateStmt = $pdo->prepare("
                UPDATE enclosures
                SET
                    enclosure_name = ?,
                    capacity = ?,
                    status = ?,
                    notes = ?
                WHERE enclosure_id = ?
            ");

            $updateStmt->execute([
                $enclosureName,
                (int)$capacity,
                $status,
                $notes !== '' ? $notes : null,
                $enclosureId
            ]);

            $newValues = [
                'enclosure_code' => $oldEnclosure['enclosure_code'],
                'enclosure_name' => $enclosureName,
                'capacity' => (int)$capacity,
                'status' => $status,
                'notes' => $notes !== '' ? $notes : null
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
                VALUES (?, 'UPDATE', 'enclosures', ?, ?, ?, ?)
            ");

            $auditStmt->execute([
                $_SESSION['user_id'],
                $enclosureId,
                json_encode($oldEnclosure),
                json_encode($newValues),
                "Updated enclosure " . $oldEnclosure['enclosure_code']
            ]);

            $pdo->commit();

            $_SESSION['enclosure_success'] =
                "Enclosure " . $oldEnclosure['enclosure_code'] .
                " was updated successfully.";

            header("Location: index.php");
            exit();

        } catch (Throwable $e) {
            if ($pdo->inTransaction()) {
                $pdo->rollBack();
            }

            if ($e instanceof RuntimeException) {
                $errors[] = $e->getMessage();
            } else {
                error_log("Enclosure update error: " . $e->getMessage());
                $errors[] = "Unable to update the enclosure. Please try again.";
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
    <title>Edit Enclosure | PKMS</title>
    <link rel="stylesheet"
          href="/penguin_keeper_management/assets/css/style.css">
</head>
<body>

<div class="app-layout">

    <?php include __DIR__ . '/../../includes/sidebar.php'; ?>

    <main class="main-content">

        <?php
        $breadcrumbs = ['Enclosures', 'Edit Enclosure'];
        include __DIR__ . '/../../includes/topbar.php';
        ?>

        <div class="page-content">

            <div class="page-header">
                <div>
                    <h1>Edit Enclosure</h1>
                    <p>Update enclosure information.</p>
                </div>

                <a href="index.php" class="btn-secondary">
                    Back to Enclosures
                </a>
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

                <form method="POST"
                      action="edit.php?id=<?= (int)$enclosureId ?>">

                    <input type="hidden"
                           name="csrf_token"
                           value="<?= htmlspecialchars($_SESSION['csrf_token']) ?>">

                    <div class="form-group">
                        <label for="enclosure_code">Enclosure Code</label>
                        <input
                            type="text"
                            id="enclosure_code"
                            value="<?= htmlspecialchars($enclosure['enclosure_code']) ?>"
                            disabled
                        >
                        <small>Enclosure codes cannot be changed.</small>
                    </div>

                    <div class="form-group">
                        <label for="enclosure_name">Enclosure Name</label>
                        <input
                            type="text"
                            id="enclosure_name"
                            name="enclosure_name"
                            maxlength="100"
                            required
                            value="<?= htmlspecialchars($enclosureName) ?>"
                        >
                    </div>

                    <div class="form-group">
                        <label for="capacity">Maximum Capacity</label>
                        <input
                            type="number"
                            id="capacity"
                            name="capacity"
                            min="1"
                            max="10000"
                            required
                            value="<?= htmlspecialchars($capacity) ?>"
                        >
                        <small>
                            Current penguin count:
                            <?= (int)$enclosure['penguin_count'] ?>
                        </small>
                    </div>

                    <div class="form-group">
                        <label for="status">Status</label>
                        <select id="status" name="status" required>
                            <option value="">Select status</option>

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
                        ><?= htmlspecialchars($notes) ?></textarea>
                    </div>

                    <div class="form-actions">
                        <button type="submit" class="btn-primary">
                            Save Changes
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

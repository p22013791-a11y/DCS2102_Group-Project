<?php
require_once __DIR__ . '/../../includes/auth.php';

requireLogin();

$feedingId = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT);

if (!$feedingId || $feedingId < 1) {
    $_SESSION['feeding_error'] = 'Invalid feeding record.';
    header('Location: index.php');
    exit;
}

$keeperId = (int) $_SESSION['user_id'];
$errors = [];
$penguins = [];

try {
    // Only the creator can open their own Draft record.
    $stmt = $pdo->prepare("
        SELECT
            feeding_id,
            penguin_id,
            feeding_date,
            feeding_time,
            food_type,
            planned_grams,
            consumed_grams,
            notes
        FROM feeding_records
        WHERE feeding_id = ?
          AND keeper_id = ?
          AND status = 'Draft'
        LIMIT 1
    ");
    $stmt->execute([$feedingId, $keeperId]);
    $feeding = $stmt->fetch();

    if (!$feeding) {
        $_SESSION['feeding_error'] =
            'Record not found, or it is no longer available for editing.';
        header('Location: index.php');
        exit;
    }

    // Load eligible penguins for the selection list.
    $penguinStmt = $pdo->query("
        SELECT penguin_id, animal_code, name
        FROM penguins
        WHERE archived_at IS NULL
          AND status IN ('Active', 'Quarantine')
        ORDER BY animal_code ASC
    ");
    $penguins = $penguinStmt->fetchAll();

    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        if (
            empty($_SESSION['csrf_token']) ||
            !isset($_POST['csrf_token']) ||
            !hash_equals($_SESSION['csrf_token'], $_POST['csrf_token'])
        ) {
            $errors[] = 'Invalid request. Please refresh the page.';
        }

        $penguinId = filter_input(
            INPUT_POST,
            'penguin_id',
            FILTER_VALIDATE_INT
        );

        $feedingDate = trim($_POST['feeding_date'] ?? '');
        $feedingTime = trim($_POST['feeding_time'] ?? '');
        $foodType = trim($_POST['food_type'] ?? '');
        $plannedGrams = trim($_POST['planned_grams'] ?? '');
        $consumedGrams = trim($_POST['consumed_grams'] ?? '');
        $notes = trim($_POST['notes'] ?? '');

        $dateObject = DateTime::createFromFormat('!Y-m-d', $feedingDate);
        $validDate = $dateObject &&
            $dateObject->format('Y-m-d') === $feedingDate;

        $validTime = preg_match(
            '/^(?:[01]\d|2[0-3]):[0-5]\d$/',
            $feedingTime
        );

        if (!$penguinId) {
            $errors[] = 'Please select a valid penguin.';
        }

        if (!$validDate) {
            $errors[] = 'Please enter a valid feeding date.';
        }

        if (!$validTime) {
            $errors[] = 'Please enter a valid feeding time.';
        }

        if ($foodType === '' || mb_strlen($foodType) > 100) {
            $errors[] = 'Please enter a food type (maximum 100 characters).';
        }

        if (
            $plannedGrams === '' ||
            !is_numeric($plannedGrams) ||
            (float) $plannedGrams < 0
        ) {
            $errors[] = 'Planned quantity must be zero or greater.';
        }

        if (
            $consumedGrams === '' ||
            !is_numeric($consumedGrams) ||
            (float) $consumedGrams < 0
        ) {
            $errors[] = 'Consumed quantity must be zero or greater.';
        }

        if (mb_strlen($notes) > 1000) {
            $errors[] = 'Notes cannot exceed 1000 characters.';
        }

        // Confirm that the selected penguin is still eligible.
        if ($penguinId) {
            $checkPenguin = $pdo->prepare("
                SELECT penguin_id
                FROM penguins
                WHERE penguin_id = ?
                  AND archived_at IS NULL
                  AND status IN ('Active', 'Quarantine')
                LIMIT 1
            ");
            $checkPenguin->execute([$penguinId]);

            if (!$checkPenguin->fetch()) {
                $errors[] = 'The selected penguin is not available.';
            }
        }

        if (empty($errors)) {
            try {
                $pdo->beginTransaction();

                // Update only the creator's Draft record.
                $update = $pdo->prepare("
                    UPDATE feeding_records
                    SET
                        penguin_id = ?,
                        feeding_date = ?,
                        feeding_time = ?,
                        food_type = ?,
                        planned_grams = ?,
                        consumed_grams = ?,
                        notes = ?
                    WHERE feeding_id = ?
                    AND keeper_id = ?
                    AND status = 'Draft'
                ");

                $update->execute([
                    $penguinId,
                    $feedingDate,
                    $feedingTime,
                    $foodType,
                    $plannedGrams,
                    $consumedGrams,
                    $notes,
                    $feedingId,
                    $keeperId
                ]);

                // Confirm the record is still owned by this keeper and is Draft.
                $check = $pdo->prepare("
                    SELECT feeding_id
                    FROM feeding_records
                    WHERE feeding_id = ?
                    AND keeper_id = ?
                    AND status = 'Draft'
                ");

                $check->execute([$feedingId, $keeperId]);

                if (!$check->fetch()) {
                    $pdo->rollBack();
                    $errors[] = 'Record is no longer available for editing.';
                } else {
                    // Store the original and updated values in the audit log.
                    $oldValues = [
                        'penguin_id' => $feeding['penguin_id'],
                        'feeding_date' => $feeding['feeding_date'],
                        'feeding_time' => $feeding['feeding_time'],
                        'food_type' => $feeding['food_type'],
                        'planned_grams' => $feeding['planned_grams'],
                        'consumed_grams' => $feeding['consumed_grams'],
                        'notes' => $feeding['notes']
                    ];

                    $newValues = [
                        'penguin_id' => $penguinId,
                        'feeding_date' => $feedingDate,
                        'feeding_time' => $feedingTime,
                        'food_type' => $foodType,
                        'planned_grams' => $plannedGrams,
                        'consumed_grams' => $consumedGrams,
                        'notes' => $notes
                    ];

                    $audit = $pdo->prepare("
                        INSERT INTO audit_logs (
                            user_id,
                            action,
                            entity_type,
                            entity_id,
                            old_values,
                            new_values,
                            description
                        )
                        VALUES (?, ?, ?, ?, ?, ?, ?)
                    ");

                    $audit->execute([
                        $keeperId,
                        'UPDATE',
                        'feeding_records',
                        $feedingId,
                        json_encode($oldValues, JSON_UNESCAPED_UNICODE),
                        json_encode($newValues, JSON_UNESCAPED_UNICODE),
                        'Draft feeding record updated.'
                    ]);

                    $pdo->commit();

                    $_SESSION['feeding_success'] =
                        'Draft feeding record updated successfully.';

                    header('Location: index.php');
                    exit;
                }

            } catch (Throwable $e) {
                if ($pdo->inTransaction()) {
                    $pdo->rollBack();
                }

                error_log('Feeding edit update error: ' . $e->getMessage());
                $errors[] = 'Unable to update the feeding record. Please try again.';
            }
        }

        // Keep submitted values on the form if validation fails.
        $feeding = array_merge($feeding, [
            'penguin_id' => $penguinId ?: '',
            'feeding_date' => $feedingDate,
            'feeding_time' => $feedingTime,
            'food_type' => $foodType,
            'planned_grams' => $plannedGrams,
            'consumed_grams' => $consumedGrams,
            'notes' => $notes
        ]);
    }

} catch (PDOException $e) {
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }
    error_log('Feeding edit error: ' . $e->getMessage());
    $errors[] = 'A database error occurred. Please try again.';

}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Edit Draft Feeding Record | PKMS</title>
    <link rel="stylesheet"
          href="/penguin_keeper_management/assets/css/style.css">
</head>
<body>

<div class="app-layout">

    <?php include __DIR__ . '/../../includes/sidebar.php'; ?>

    <main class="main-content">

        <?php
        $breadcrumbs = ['Feeding Records', 'Edit Draft'];
        include __DIR__ . '/../../includes/topbar.php';
        ?>

        <div class="page-content">

            <div class="page-header">
                <div>
                    <h1>Edit Draft Feeding Record</h1>
                    <p>Only your own Draft records can be edited.</p>
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
                <form method="POST" action="edit.php?id=<?= (int) $feedingId ?>">

                    <input type="hidden"
                           name="csrf_token"
                           value="<?= htmlspecialchars($_SESSION['csrf_token']) ?>">

                    <div class="form-group">
                        <label for="penguin_id">Penguin</label>
                        <select id="penguin_id" name="penguin_id" required>
                            <option value="">Select Penguin</option>

                            <?php foreach ($penguins as $penguin): ?>
                                <option
                                    value="<?= (int) $penguin['penguin_id'] ?>"
                                    <?= (string) $feeding['penguin_id'] ===
                                        (string) $penguin['penguin_id']
                                        ? 'selected' : '' ?>>
                                    <?= htmlspecialchars(
                                        $penguin['animal_code'] . ' - ' .
                                        $penguin['name']
                                    ) ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <div class="form-group">
                        <label for="feeding_date">Feeding Date</label>
                        <input type="date"
                               id="feeding_date"
                               name="feeding_date"
                               value="<?= htmlspecialchars($feeding['feeding_date']) ?>"
                               required>
                    </div>

                    <div class="form-group">
                        <label for="feeding_time">Feeding Time</label>
                        <input type="time"
                               id="feeding_time"
                               name="feeding_time"
                               value="<?= htmlspecialchars(substr($feeding['feeding_time'], 0, 5)) ?>"
                               required>
                    </div>

                    <div class="form-group">
                        <label for="food_type">Food Type</label>
                        <input type="text"
                               id="food_type"
                               name="food_type"
                               maxlength="100"
                               value="<?= htmlspecialchars($feeding['food_type']) ?>"
                               required>
                    </div>

                    <div class="form-group">
                        <label for="planned_grams">Planned Quantity (grams)</label>
                        <input type="number"
                               id="planned_grams"
                               name="planned_grams"
                               min="0"
                               step="0.01"
                               value="<?= htmlspecialchars((string) $feeding['planned_grams']) ?>"
                               required>
                    </div>

                    <div class="form-group">
                        <label for="consumed_grams">Consumed Quantity (grams)</label>
                        <input type="number"
                               id="consumed_grams"
                               name="consumed_grams"
                               min="0"
                               step="0.01"
                               value="<?= htmlspecialchars((string) $feeding['consumed_grams']) ?>"
                               required>
                    </div>

                    <div class="form-group">
                        <label for="notes">Notes</label>
                        <textarea id="notes"
                                  name="notes"
                                  maxlength="1000"><?= htmlspecialchars($feeding['notes'] ?? '') ?></textarea>
                    </div>

                    <div class="form-actions">
                        <button type="submit" class="btn-primary">
                            Save Draft Changes
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

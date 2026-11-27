<?php
require_once __DIR__ . '/../../includes/auth.php';

requireLogin();

if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

$weightId = filter_var(
    $_GET['id'] ?? $_POST['weight_id'] ?? '',
    FILTER_VALIDATE_INT
);

if (!$weightId || $weightId < 1) {
    $_SESSION['weight_error'] = 'Invalid weight record.';
    header('Location: index.php');
    exit;
}

$errors = [];
$timezone = new DateTimeZone('Asia/Kuala_Lumpur');

$stmt = $pdo->prepare("
    SELECT *
    FROM weight_records
    WHERE weight_id = ?
      AND keeper_id = ?
");

$stmt->execute([
    $weightId,
    (int)$_SESSION['user_id']
]);

$record = $stmt->fetch();

if (!$record) {
    $_SESSION['weight_error'] =
        'You can only edit your own weight records.';

    header('Location: index.php');
    exit;
}

$penguinId = (string)$record['penguin_id'];
$measurementDate = $record['measurement_date'];
$measurementTime = substr($record['measurement_time'], 0, 5);
$weightKg = (string)$record['weight_kg'];
$notes = $record['notes'] ?? '';

$penguinStmt = $pdo->query("
    SELECT penguin_id, animal_code, name
    FROM penguins
    WHERE archived_at IS NULL
      AND status IN ('Active', 'Quarantine')
    ORDER BY animal_code
");

$penguins = $penguinStmt->fetchAll();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    $submittedToken = $_POST['csrf_token'] ?? '';

    $penguinId = trim($_POST['penguin_id'] ?? '');
    $measurementDate = trim($_POST['measurement_date'] ?? '');
    $measurementTime = trim($_POST['measurement_time'] ?? '');
    $weightKg = trim($_POST['weight_kg'] ?? '');
    $notes = trim($_POST['notes'] ?? '');

    if (
        !is_string($submittedToken) ||
        !hash_equals($_SESSION['csrf_token'], $submittedToken)
    ) {
        $errors[] = 'Invalid request. Please refresh the page.';
    }

    $validatedPenguinId = filter_var(
        $penguinId,
        FILTER_VALIDATE_INT
    );

    if (!$validatedPenguinId || $validatedPenguinId < 1) {
        $errors[] = 'Please select a valid penguin.';
    }

    $parsedDate = DateTime::createFromFormat(
        '!Y-m-d',
        $measurementDate
    );

    if (
        !$parsedDate ||
        $parsedDate->format('Y-m-d') !== $measurementDate
    ) {
        $errors[] = 'Please enter a valid measurement date.';
    }

    if (
        !preg_match(
            '/^(?:[01]\d|2[0-3]):[0-5]\d$/',
            $measurementTime
        )
    ) {
        $errors[] = 'Please enter a valid measurement time.';
    }

    if (
        $weightKg === '' ||
        !is_numeric($weightKg) ||
        !is_finite((float)$weightKg) ||
        (float)$weightKg <= 0 ||
        (float)$weightKg > 100
    ) {
        $errors[] =
            'Please enter a valid weight greater than 0 and not exceeding 100 kg.';
    }

    if (strlen($notes) > 2000) {
        $errors[] = 'Notes cannot exceed 2000 characters.';
    }

    if (empty($errors)) {
        try {
            $checkPenguin = $pdo->prepare("
                SELECT penguin_id
                FROM penguins
                WHERE penguin_id = ?
                  AND archived_at IS NULL
                  AND status IN ('Active', 'Quarantine')
            ");

            $checkPenguin->execute([$validatedPenguinId]);

            if (!$checkPenguin->fetch()) {
                $errors[] = 'The selected penguin is unavailable.';
            }

            if (empty($errors)) {
                $pdo->beginTransaction();

                $lockStmt = $pdo->prepare("
                    SELECT *
                    FROM weight_records
                    WHERE weight_id = ?
                      AND keeper_id = ?
                    FOR UPDATE
                ");

                $lockStmt->execute([
                    $weightId,
                    (int)$_SESSION['user_id']
                ]);

                $oldRecord = $lockStmt->fetch();

                if (!$oldRecord) {
                    throw new RuntimeException(
                        'You can only edit your own weight records.'
                    );
                }

                $updateStmt = $pdo->prepare("
                    UPDATE weight_records
                    SET
                        penguin_id = ?,
                        measurement_date = ?,
                        measurement_time = ?,
                        weight_kg = ?,
                        notes = ?
                    WHERE weight_id = ?
                      AND keeper_id = ?
                ");

                $updateStmt->execute([
                    $validatedPenguinId,
                    $measurementDate,
                    $measurementTime,
                    $weightKg,
                    $notes !== '' ? $notes : null,
                    $weightId,
                    (int)$_SESSION['user_id']
                ]);

                $newRecord = $oldRecord;
                $newRecord['penguin_id'] = $validatedPenguinId;
                $newRecord['measurement_date'] = $measurementDate;
                $newRecord['measurement_time'] = $measurementTime;
                $newRecord['weight_kg'] = $weightKg;
                $newRecord['notes'] = $notes !== '' ? $notes : null;

                $auditStmt = $pdo->prepare("
                    INSERT INTO audit_logs (
                        user_id,
                        action,
                        entity_type,
                        entity_id,
                        old_values,
                        new_values,
                        description
                    ) VALUES (?, 'UPDATE', 'weight_records', ?, ?, ?, ?)
                ");

                $auditStmt->execute([
                    (int)$_SESSION['user_id'],
                    $weightId,
                    json_encode($oldRecord, JSON_UNESCAPED_UNICODE),
                    json_encode($newRecord, JSON_UNESCAPED_UNICODE),
                    'Updated own penguin weight measurement record.'
                ]);

                $pdo->commit();

                $_SESSION['weight_success'] =
                    'Weight record updated successfully.';

                header('Location: index.php');
                exit;
            }

        } catch (Throwable $e) {
            if ($pdo->inTransaction()) {
                $pdo->rollBack();
            }

            error_log('Weight record update error: ' . $e->getMessage());

            $errors[] = $e instanceof RuntimeException
                ? $e->getMessage()
                : 'Unable to update the weight record. Please try again.';
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Edit Weight Record | PKMS</title>
    <link rel="stylesheet"
          href="/penguin_keeper_management/assets/css/style.css">
</head>
<body>

<div class="app-layout">

    <?php include __DIR__ . '/../../includes/sidebar.php'; ?>

    <main class="main-content">

        <?php
        $breadcrumbs = ['Weight Records', 'Edit Weight Record'];
        include __DIR__ . '/../../includes/topbar.php';
        ?>

        <div class="page-content">

            <div class="page-header">
                <div>
                    <h1>Edit Weight Record</h1>
                    <p>Update your weight measurement record.</p>
                </div>
            </div>

            <?php if (!empty($errors)): ?>
                <div class="error-message">
                    <?php foreach ($errors as $error): ?>
                        <p><?= htmlspecialchars($error) ?></p>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>

            <section class="form-section">

                <form method="POST" action="edit.php?id=<?= (int)$weightId ?>">

                    <input
                        type="hidden"
                        name="csrf_token"
                        value="<?= htmlspecialchars($_SESSION['csrf_token']) ?>"
                    >

                    <input
                        type="hidden"
                        name="weight_id"
                        value="<?= (int)$weightId ?>"
                    >

                    <div class="form-group">
                        <label for="penguin_id">Penguin *</label>

                        <select id="penguin_id" name="penguin_id" required>
                            <option value="">Select Penguin</option>

                            <?php foreach ($penguins as $penguin): ?>
                                <option
                                    value="<?= (int)$penguin['penguin_id'] ?>"
                                    <?= (string)$penguinId === (string)$penguin['penguin_id'] ? 'selected' : '' ?>
                                >
                                    <?= htmlspecialchars(
                                        $penguin['animal_code'] . ' - ' . $penguin['name']
                                    ) ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <div class="form-group">
                        <label for="measurement_date">Measurement Date *</label>
                        <input
                            type="date"
                            id="measurement_date"
                            name="measurement_date"
                            value="<?= htmlspecialchars($measurementDate) ?>"
                            required
                        >
                    </div>

                    <div class="form-group">
                        <label for="measurement_time">Measurement Time *</label>
                        <input
                            type="time"
                            id="measurement_time"
                            name="measurement_time"
                            value="<?= htmlspecialchars($measurementTime) ?>"
                            required
                        >
                    </div>

                    <div class="form-group">
                        <label for="weight_kg">Weight (kg) *</label>
                        <input
                            type="number"
                            id="weight_kg"
                            name="weight_kg"
                            min="0.01"
                            max="100"
                            step="0.01"
                            value="<?= htmlspecialchars($weightKg) ?>"
                            required
                        >
                    </div>

                    <div class="form-group">
                        <label for="notes">Notes (Optional)</label>
                        <textarea
                            id="notes"
                            name="notes"
                            rows="4"
                            maxlength="2000"
                        ><?= htmlspecialchars($notes) ?></textarea>
                    </div>

                    <div class="form-actions">
                        <a href="index.php" class="btn-secondary">
                            Cancel
                        </a>

                        <button type="submit" class="btn-primary">
                            Save Changes
                        </button>
                    </div>

                </form>

            </section>

        </div>

    </main>

</div>

</body>
</html>

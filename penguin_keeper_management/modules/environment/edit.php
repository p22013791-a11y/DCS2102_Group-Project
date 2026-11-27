<?php
require_once __DIR__ . '/../../includes/auth.php';
requireLogin();

date_default_timezone_set('Asia/Kuala_Lumpur');

$errors = [];

$readingId = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT);

if (!$readingId || $readingId <= 0) {
    $_SESSION['environment_error'] = 'Invalid environmental reading ID.';
    header('Location: index.php');
    exit;
}

if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

$isSenior = ($_SESSION['role'] ?? '') === 'Senior Keeper';

try {
    $stmt = $pdo->prepare("
        SELECT *
        FROM environmental_readings
        WHERE reading_id = ?
    ");
    $stmt->execute([$readingId]);
    $reading = $stmt->fetch();

    if (!$reading) {
        $_SESSION['environment_error'] = 'Environmental reading not found.';
        header('Location: index.php');
        exit;
    }

    if (
        !$isSenior &&
        (int)$reading['keeper_id'] !== (int)$_SESSION['user_id']
    ) {
        http_response_code(403);
        exit('You are not authorised to edit this record.');
    }

} catch (PDOException $e) {
    error_log('Environmental reading retrieval error: ' . $e->getMessage());
    exit('Unable to load the environmental reading.');
}

$enclosureId = (string)$reading['enclosure_id'];
$readingDate = $reading['reading_date'] ?? '';
$readingTime = $reading['reading_time'] ?? '';
$poolTemperature = $reading['pool_temperature'] ?? '';
$ph = $reading['ph'] ?? '';
$salinity = $reading['salinity'] ?? '';
$chlorine = $reading['chlorine'] ?? '';
$notes = $reading['notes'] ?? '';

$enclosuresStmt = $pdo->query("
    SELECT enclosure_id, enclosure_code, enclosure_name
    FROM enclosures
    WHERE status = 'Active'
      AND archived_at IS NULL
    ORDER BY enclosure_code
");

$enclosures = $enclosuresStmt->fetchAll();

// Include the current enclosure even if it is no longer active.
$currentEnclosureExists = false;

foreach ($enclosures as $enclosure) {
    if ((int)$enclosure['enclosure_id'] === (int)$reading['enclosure_id']) {
        $currentEnclosureExists = true;
        break;
    }
}

if (!$currentEnclosureExists) {
    $currentStmt = $pdo->prepare("
        SELECT enclosure_id, enclosure_code, enclosure_name
        FROM enclosures
        WHERE enclosure_id = ?
    ");
    $currentStmt->execute([(int)$reading['enclosure_id']]);
    $currentEnclosure = $currentStmt->fetch();

    if ($currentEnclosure) {
        array_unshift($enclosures, $currentEnclosure);
    }
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    if (
        !isset($_POST['csrf_token']) ||
        !hash_equals($_SESSION['csrf_token'], $_POST['csrf_token'])
    ) {
        $errors[] = 'Invalid request. Please refresh the page.';
    }

    $enclosureId = trim($_POST['enclosure_id'] ?? '');
    $readingDate = trim($_POST['reading_date'] ?? '');
    $readingTime = trim($_POST['reading_time'] ?? '');
    $poolTemperature = trim($_POST['pool_temperature'] ?? '');
    $ph = trim($_POST['ph'] ?? '');
    $salinity = trim($_POST['salinity'] ?? '');
    $chlorine = trim($_POST['chlorine'] ?? '');
    $notes = trim($_POST['notes'] ?? '');

    if (!filter_var($enclosureId, FILTER_VALIDATE_INT) || (int)$enclosureId <= 0) {
        $errors[] = 'Please select a valid enclosure.';
    }

    $parsedDate = DateTime::createFromFormat('!Y-m-d', $readingDate);

    if (
        !$parsedDate ||
        $parsedDate->format('Y-m-d') !== $readingDate
    ) {
        $errors[] = 'Please enter a valid reading date.';
    }

    if (!preg_match('/^(?:[01]\d|2[0-3]):[0-5]\d(?::[0-5]\d)?$/', $readingTime)) {
        $errors[] = 'Please enter a valid reading time.';
    }

    $measurements = [
        'Pool temperature' => $poolTemperature,
        'pH' => $ph,
        'Salinity' => $salinity,
        'Chlorine' => $chlorine
    ];

    foreach ($measurements as $label => $value) {
        if (
            $value !== '' &&
            (!is_numeric($value) || !is_finite((float)$value))
        ) {
            $errors[] = "$label must be a valid number.";
        }
    }

    if ($ph !== '' && ((float)$ph < 0 || (float)$ph > 14)) {
        $errors[] = 'pH must be between 0 and 14.';
    }

    if (strlen($notes) > 2000) {
        $errors[] = 'Notes must not exceed 2000 characters.';
    }

    if (empty($errors)) {
        $checkEnclosure = $pdo->prepare("
            SELECT enclosure_id
            FROM enclosures
            WHERE enclosure_id = ?
              AND (
                    (
                        status = 'Active'
                        AND archived_at IS NULL
                    )
                    OR enclosure_id = ?
              )
        ");

        $checkEnclosure->execute([
            (int)$enclosureId,
            (int)$reading['enclosure_id']
        ]);

        if (!$checkEnclosure->fetch()) {
            $errors[] = 'The selected enclosure is unavailable.';
        }
    }

    if (empty($errors)) {
        try {
            $pdo->beginTransaction();

            $lockStmt = $pdo->prepare("
                SELECT *
                FROM environmental_readings
                WHERE reading_id = ?
                FOR UPDATE
            ");
            $lockStmt->execute([$readingId]);
            $currentReading = $lockStmt->fetch();

            if (!$currentReading) {
                throw new RuntimeException('Environmental reading no longer exists.');
            }

            if (
                !$isSenior &&
                (int)$currentReading['keeper_id'] !== (int)$_SESSION['user_id']
            ) {
                throw new RuntimeException('You are not authorised to edit this record.');
            }

            $oldValues = $currentReading;

            $updateStmt = $pdo->prepare("
                UPDATE environmental_readings
                SET
                    enclosure_id = ?,
                    reading_date = ?,
                    reading_time = ?,
                    pool_temperature = ?,
                    ph = ?,
                    salinity = ?,
                    chlorine = ?,
                    notes = ?
                WHERE reading_id = ?
            ");

            $updateStmt->execute([
                (int)$enclosureId,
                $readingDate,
                $readingTime,
                $poolTemperature !== '' ? $poolTemperature : null,
                $ph !== '' ? $ph : null,
                $salinity !== '' ? $salinity : null,
                $chlorine !== '' ? $chlorine : null,
                $notes !== '' ? $notes : null,
                $readingId
            ]);

            $newValues = [
                'enclosure_id' => (int)$enclosureId,
                'reading_date' => $readingDate,
                'reading_time' => $readingTime,
                'pool_temperature' => $poolTemperature,
                'ph' => $ph,
                'salinity' => $salinity,
                'chlorine' => $chlorine,
                'notes' => $notes
            ];

            $audit = $pdo->prepare("
                INSERT INTO audit_logs
                (
                    user_id,
                    action,
                    entity_type,
                    entity_id,
                    old_values,
                    new_values,
                    description
                )
                VALUES (?, 'UPDATE', 'environmental_readings', ?, ?, ?, ?)
            ");

            $audit->execute([
                (int)$_SESSION['user_id'],
                $readingId,
                json_encode($oldValues),
                json_encode($newValues),
                'Updated environmental reading record'
            ]);

            $pdo->commit();

            $_SESSION['environment_success'] =
                'Environmental reading updated successfully.';

            header('Location: index.php');
            exit;

        } catch (Throwable $e) {
            if ($pdo->inTransaction()) {
                $pdo->rollBack();
            }

            error_log('Environmental reading update error: ' . $e->getMessage());

            $errors[] = 'Unable to update the environmental reading. Please try again.';
        }
    }
}

$pageTitle = 'Edit Environmental Reading';
$breadcrumbs = ['Environmental Readings', 'Edit Reading'];
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title><?= htmlspecialchars($pageTitle) ?> | PKMS</title>

    <link
        rel="stylesheet"
        href="/penguin_keeper_management/assets/css/style.css"
    >
</head>
<body>

<div class="app-layout">

    <?php include __DIR__ . '/../../includes/sidebar.php'; ?>

    <main class="main-content">

        <?php include __DIR__ . '/../../includes/topbar.php'; ?>

        <div class="page-content">

            <div class="page-header">
                <div>
                    <h1>Edit Environmental Reading</h1>
                    <p>Update the recorded enclosure measurements.</p>
                </div>
            </div>

            <?php if (!empty($errors)): ?>
                <div class="error-message">
                    <?php foreach ($errors as $error): ?>
                        <p><?= htmlspecialchars($error) ?></p>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>

            <form method="POST">

                <input
                    type="hidden"
                    name="csrf_token"
                    value="<?= htmlspecialchars($_SESSION['csrf_token']) ?>"
                >

                <div class="form-group">
                    <label for="enclosure_id">Enclosure *</label>

                    <select name="enclosure_id" id="enclosure_id" required>
                        <option value="">Select enclosure</option>

                        <?php foreach ($enclosures as $enclosure): ?>
                            <option
                                value="<?= (int)$enclosure['enclosure_id'] ?>"
                                <?= (string)$enclosureId === (string)$enclosure['enclosure_id'] ? 'selected' : '' ?>
                            >
                                <?= htmlspecialchars(
                                    $enclosure['enclosure_code'] .
                                    ' - ' .
                                    $enclosure['enclosure_name']
                                ) ?>
                            </option>
                        <?php endforeach; ?>

                    </select>
                </div>

                <div class="form-group">
                    <label for="reading_date">Reading Date *</label>

                    <input
                        type="date"
                        name="reading_date"
                        id="reading_date"
                        value="<?= htmlspecialchars($readingDate) ?>"
                        required
                    >
                </div>

                <div class="form-group">
                    <label for="reading_time">Reading Time *</label>

                    <input
                        type="time"
                        name="reading_time"
                        id="reading_time"
                        value="<?= htmlspecialchars($readingTime) ?>"
                        required
                    >
                </div>

                <div class="form-group">
                    <label for="pool_temperature">Pool Temperature</label>

                    <input
                        type="number"
                        step="0.01"
                        name="pool_temperature"
                        id="pool_temperature"
                        value="<?= htmlspecialchars((string)$poolTemperature) ?>"
                    >
                </div>

                <div class="form-group">
                    <label for="ph">pH</label>

                    <input
                        type="number"
                        step="0.01"
                        min="0"
                        max="14"
                        name="ph"
                        id="ph"
                        value="<?= htmlspecialchars((string)$ph) ?>"
                    >
                </div>

                <div class="form-group">
                    <label for="salinity">Salinity</label>

                    <input
                        type="number"
                        step="0.01"
                        name="salinity"
                        id="salinity"
                        value="<?= htmlspecialchars((string)$salinity) ?>"
                    >
                </div>

                <div class="form-group">
                    <label for="chlorine">Chlorine</label>

                    <input
                        type="number"
                        step="0.01"
                        name="chlorine"
                        id="chlorine"
                        value="<?= htmlspecialchars((string)$chlorine) ?>"
                    >
                </div>

                <div class="form-group">
                    <label for="notes">Notes</label>

                    <textarea
                        name="notes"
                        id="notes"
                        maxlength="2000"
                    ><?= htmlspecialchars((string)$notes) ?></textarea>
                </div>

                <button type="submit" class="btn-primary">
                    Save Changes
                </button>

                <a href="index.php" class="btn-secondary">
                    Cancel
                </a>

            </form>

        </div>

    </main>

</div>

</body>
</html>

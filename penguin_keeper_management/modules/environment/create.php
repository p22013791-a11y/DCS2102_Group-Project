<?php
require_once "../../includes/auth.php";
requireLogin();

date_default_timezone_set('Asia/Kuala_Lumpur');

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

$errors = [];

$readingDate = date('Y-m-d');
$readingTime = date('H:i');
$enclosureId = '';
$poolTemperature = '';
$ph = '';
$salinity = '';
$chlorine = '';
$notes = '';

$enclosuresStmt = $pdo->query("
    SELECT enclosure_id, enclosure_code, enclosure_name
    FROM enclosures
    WHERE status = 'Active'
      AND archived_at IS NULL
    ORDER BY enclosure_code
");
$enclosures = $enclosuresStmt->fetchAll();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (
        !isset($_POST['csrf_token']) ||
        !hash_equals($_SESSION['csrf_token'], $_POST['csrf_token'])
    ) {
        $errors[] = "Invalid request. Please refresh the page.";
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
        $errors[] = "Please select a valid enclosure.";
    }

    if (
        !DateTime::createFromFormat('Y-m-d', $readingDate) ||
        DateTime::createFromFormat('Y-m-d', $readingDate)->format('Y-m-d') !== $readingDate
    ) {
        $errors[] = "Please enter a valid reading date.";
    }

    if (!preg_match('/^(?:[01]\d|2[0-3]):[0-5]\d$/', $readingTime)) {
        $errors[] = "Please enter a valid reading time.";
    }

    $measurements = [
        'Pool temperature' => $poolTemperature,
        'pH' => $ph,
        'Salinity' => $salinity,
        'Chlorine' => $chlorine
    ];

    foreach ($measurements as $label => $value) {
        if ($value !== '' && (!is_numeric($value) || !is_finite((float)$value))) {
            $errors[] = "$label must be a valid number.";
        }
    }

    if ($ph !== '' && ((float)$ph < 0 || (float)$ph > 14)) {
        $errors[] = "pH must be between 0 and 14.";
    }

    if (strlen($notes) > 2000) {
        $errors[] = "Notes must not exceed 2000 characters.";
    }

    if (!$errors) {
        $checkEnclosure = $pdo->prepare("
            SELECT enclosure_id
            FROM enclosures
            WHERE enclosure_id = ?
              AND status = 'Active'
              AND archived_at IS NULL
        ");
        $checkEnclosure->execute([(int)$enclosureId]);

        if (!$checkEnclosure->fetch()) {
            $errors[] = "The selected enclosure is unavailable.";
        }
    }

    if (!$errors) {
        try {
            $pdo->beginTransaction();

            $stmt = $pdo->prepare("
                INSERT INTO environmental_readings
                (
                    enclosure_id,
                    keeper_id,
                    reading_date,
                    reading_time,
                    pool_temperature,
                    ph,
                    salinity,
                    chlorine,
                    notes
                )
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)
            ");

            $stmt->execute([
                (int)$enclosureId,
                (int)$_SESSION['user_id'],
                $readingDate,
                $readingTime,
                $poolTemperature !== '' ? $poolTemperature : null,
                $ph !== '' ? $ph : null,
                $salinity !== '' ? $salinity : null,
                $chlorine !== '' ? $chlorine : null,
                $notes !== '' ? $notes : null
            ]);

            $readingId = (int)$pdo->lastInsertId();

            $audit = $pdo->prepare("
                INSERT INTO audit_logs
                (user_id, action, entity_type, entity_id, new_values, description)
                VALUES (?, 'CREATE', 'environmental_readings', ?, ?, ?)
            ");

            $audit->execute([
                (int)$_SESSION['user_id'],
                $readingId,
                json_encode([
                    'enclosure_id' => (int)$enclosureId,
                    'reading_date' => $readingDate,
                    'reading_time' => $readingTime,
                    'pool_temperature' => $poolTemperature,
                    'ph' => $ph,
                    'salinity' => $salinity,
                    'chlorine' => $chlorine,
                    'notes' => $notes
                ]),
                'Created environmental reading record'
            ]);

            $pdo->commit();

            $_SESSION['environment_success'] = "Environmental reading recorded successfully.";
            header("Location: index.php");
            exit;

        } catch (PDOException $e) {
            if ($pdo->inTransaction()) {
                $pdo->rollBack();
            }

            error_log("Environmental reading creation failed: " . $e->getMessage());
            $errors[] = "Unable to save the environmental reading. Please try again.";
        }
    }
}


?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Record Environmental Reading | PKMS</title>
    <link rel="stylesheet" href="/penguin_keeper_management/assets/css/style.css">
</head>

<body>

<div class="app-layout">

    <?php
    $currentPage = 'environment';
    include __DIR__ . '/../../includes/sidebar.php';
    ?>

    <main class="main-content">

        <?php
        $breadcrumbs = ['Environment', 'Record Reading'];
        include __DIR__ . '/../../includes/topbar.php';
        ?>

        <div class="page-content">

            <div class="page-header">
                <div>
                    <h1>Record Environmental Reading</h1>
                    <p>Record environmental measurements for an enclosure.</p>
                </div>
            </div>

            <?php if (!empty($errors)): ?>
                <div class="error-message">
                    <strong>Please correct the following:</strong>
                    <?php foreach ($errors as $error): ?>
                        <p><?= htmlspecialchars($error) ?></p>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>

            <section class="form-section">

                <form method="POST" action="create.php">

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
                                        $enclosure['enclosure_code'] . ' - ' . $enclosure['enclosure_name']
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
                            value="<?= htmlspecialchars($poolTemperature) ?>"
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
                            value="<?= htmlspecialchars($ph) ?>"
                        >
                    </div>

                    <div class="form-group">
                        <label for="salinity">Salinity</label>
                        <input
                            type="number"
                            step="0.01"
                            name="salinity"
                            id="salinity"
                            value="<?= htmlspecialchars($salinity) ?>"
                        >
                    </div>

                    <div class="form-group">
                        <label for="chlorine">Chlorine</label>
                        <input
                            type="number"
                            step="0.01"
                            name="chlorine"
                            id="chlorine"
                            value="<?= htmlspecialchars($chlorine) ?>"
                        >
                    </div>

                    <div class="form-group">
                        <label for="notes">Notes</label>
                        <textarea
                            name="notes"
                            id="notes"
                            rows="4"
                            maxlength="2000"
                        ><?= htmlspecialchars($notes) ?></textarea>
                    </div>

                    <div class="form-actions">

                        <button type="submit" class="btn-primary">
                            Save Reading
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

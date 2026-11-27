<?php
require_once __DIR__ . '/../../includes/auth.php';

requireLogin();

$errors = [];

$penguinId = '';
$feedingDate = '';
$feedingTime = '';
$foodType = '';
$plannedGrams = '';
$consumedGrams = '';
$notes = '';

$timezone = new DateTimeZone('Asia/Kuala_Lumpur');
$now = new DateTimeImmutable('now', $timezone);

$feedingDate = $now->format('Y-m-d');
$feedingTime = $now->format('H:i');

$penguins = [];

if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

// Load penguins that can receive new feeding records.
try {
    $penguinStmt = $pdo->query("
        SELECT
            penguin_id,
            animal_code,
            name,
            status
        FROM penguins
        WHERE archived_at IS NULL
          AND status IN ('Active', 'Quarantine')
        ORDER BY animal_code ASC
    ");

    $penguins = $penguinStmt->fetchAll();

} catch (PDOException $e) {
    error_log("Feeding penguin list error: " . $e->getMessage());
    $errors[] = "Unable to load penguin information.";
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    $penguinId = trim($_POST['penguin_id'] ?? '');
    $feedingDate = trim($_POST['feeding_date'] ?? '');
    $feedingTime = trim($_POST['feeding_time'] ?? '');
    $foodType = trim($_POST['food_type'] ?? '');
    $plannedGrams = trim($_POST['planned_grams'] ?? '');
    $consumedGrams = trim($_POST['consumed_grams'] ?? '');
    $notes = trim($_POST['notes'] ?? '');
    $csrfToken = $_POST['csrf_token'] ?? '';

    if (!hash_equals($_SESSION['csrf_token'], $csrfToken)) {
        $errors[] = "Invalid request. Please refresh the page.";
    }

    if (
        $penguinId === '' ||
        filter_var($penguinId, FILTER_VALIDATE_INT) === false ||
        (int)$penguinId < 1
    ) {
        $errors[] = "Please select a valid penguin.";
    }

    $dateObject = DateTime::createFromFormat('!Y-m-d', $feedingDate);

    if (
        !$dateObject ||
        $dateObject->format('Y-m-d') !== $feedingDate
    ) {
        $errors[] = "Please enter a valid feeding date.";
    }

    if (
        !preg_match('/^(?:[01]\d|2[0-3]):[0-5]\d$/', $feedingTime)
    ) {
        $errors[] = "Please enter a valid feeding time.";
    }

    if ($foodType === '') {
        $errors[] = "Please enter the food type.";
    } elseif (mb_strlen($foodType) > 100) {
        $errors[] = "Food type must not exceed 100 characters.";
    }

    foreach ([
        'Planned quantity' => $plannedGrams,
        'Consumed quantity' => $consumedGrams
    ] as $label => $quantity) {

        if ($quantity === '' || !is_numeric($quantity)) {
            $errors[] = "Please enter a valid " . strtolower($label) . ".";
        } elseif ((float)$quantity < 0) {
            $errors[] = $label . " cannot be negative.";
        }
    }

    if (mb_strlen($notes) > 5000) {
        $errors[] = "Notes must not exceed 5000 characters.";
    }

    if (empty($errors)) {
        try {
            // Confirm the selected penguin is still eligible.
            $checkStmt = $pdo->prepare("
                SELECT penguin_id
                FROM penguins
                WHERE penguin_id = ?
                  AND archived_at IS NULL
                  AND status IN ('Active', 'Quarantine')
            ");

            $checkStmt->execute([(int)$penguinId]);

            if (!$checkStmt->fetch()) {
                throw new RuntimeException(
                    "The selected penguin is unavailable for new feeding records."
                );
            }

            $insertStmt = $pdo->prepare("
                INSERT INTO feeding_records (
                    penguin_id,
                    keeper_id,
                    feeding_date,
                    feeding_time,
                    food_type,
                    planned_grams,
                    consumed_grams,
                    notes
                )
                VALUES (?, ?, ?, ?, ?, ?, ?, ?)
            ");

            $insertStmt->execute([
                (int)$penguinId,
                $_SESSION['user_id'],
                $feedingDate,
                $feedingTime . ':00',
                $foodType,
                $plannedGrams,
                $consumedGrams,
                $notes !== '' ? $notes : null
            ]);

            $_SESSION['feeding_success'] =
                "Feeding record created successfully.";

            header("Location: index.php");
            exit();

        } catch (Throwable $e) {

            if ($e instanceof RuntimeException) {
                $errors[] = $e->getMessage();
            } else {
                error_log("Feeding record creation error: " . $e->getMessage());
                $errors[] = "Unable to create the feeding record. Please try again.";
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

    <title>Record Feeding | PKMS</title>

    <link rel="stylesheet"
          href="/penguin_keeper_management/assets/css/style.css">
</head>
<body>

<div class="app-layout">

    <?php include __DIR__ . '/../../includes/sidebar.php'; ?>

    <main class="main-content">

        <?php
        $breadcrumbs = ['Feeding Records', 'Record Feeding'];
        include __DIR__ . '/../../includes/topbar.php';
        ?>

        <div class="page-content">

            <div class="page-header">
                <div>
                    <h1>Record Feeding</h1>
                    <p>Record a penguin feeding session.</p>
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

                <?php if (empty($penguins)): ?>

                    <p class="empty-message">
                        No eligible penguins are currently available.
                    </p>

                <?php else: ?>

                    <form method="POST" action="create.php">

                        <input
                            type="hidden"
                            name="csrf_token"
                            value="<?= htmlspecialchars($_SESSION['csrf_token']) ?>"
                        >

                        <div class="form-group">
                            <label for="penguin_id">Penguin</label>

                            <select
                                id="penguin_id"
                                name="penguin_id"
                                required
                            >
                                <option value="">Select penguin</option>

                                <?php foreach ($penguins as $penguin): ?>

                                    <option
                                        value="<?= (int)$penguin['penguin_id'] ?>"
                                        <?= (string)$penguinId === (string)$penguin['penguin_id'] ? 'selected' : '' ?>
                                    >
                                        <?= htmlspecialchars(
                                            $penguin['animal_code'] . ' - ' .
                                            $penguin['name'] . ' (' .
                                            $penguin['status'] . ')'
                                        ) ?>
                                    </option>

                                <?php endforeach; ?>

                            </select>
                        </div>

                        <div class="form-group">
                            <label for="feeding_date">Feeding Date</label>

                            <input
                                type="date"
                                id="feeding_date"
                                name="feeding_date"
                                required
                                value="<?= htmlspecialchars($feedingDate) ?>"
                            >
                        </div>

                        <div class="form-group">
                            <label for="feeding_time">Feeding Time</label>

                            <input
                                type="time"
                                id="feeding_time"
                                name="feeding_time"
                                required
                                value="<?= htmlspecialchars($feedingTime) ?>"
                            >
                        </div>

                        <div class="form-group">
                            <label for="food_type">Food Type</label>

                            <input
                                type="text"
                                id="food_type"
                                name="food_type"
                                maxlength="100"
                                required
                                value="<?= htmlspecialchars($foodType) ?>"
                                placeholder="Enter food type"
                            >
                        </div>

                        <div class="form-group">
                            <label for="planned_grams">
                                Planned Quantity (grams)
                            </label>

                            <input
                                type="number"
                                id="planned_grams"
                                name="planned_grams"
                                min="0"
                                step="0.01"
                                required
                                value="<?= htmlspecialchars($plannedGrams) ?>"
                            >
                        </div>

                        <div class="form-group">
                            <label for="consumed_grams">
                                Consumed Quantity (grams)
                            </label>

                            <input
                                type="number"
                                id="consumed_grams"
                                name="consumed_grams"
                                min="0"
                                step="0.01"
                                required
                                value="<?= htmlspecialchars($consumedGrams) ?>"
                            >
                        </div>

                        <div class="form-group">
                            <label for="notes">Notes</label>

                            <textarea
                                id="notes"
                                name="notes"
                                rows="4"
                                maxlength="5000"
                                placeholder="Enter additional observations"
                            ><?= htmlspecialchars($notes) ?></textarea>
                        </div>

                        <div class="form-actions">
                            <button type="submit" class="btn-primary">
                                Save Feeding Record
                            </button>

                            <a href="index.php" class="btn-secondary">
                                Cancel
                            </a>
                        </div>

                    </form>

                <?php endif; ?>

            </section>

        </div>

    </main>

</div>

</body>
</html>

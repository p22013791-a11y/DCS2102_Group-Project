<?php
require_once __DIR__ . '/../../includes/auth.php';
requireLogin();

date_default_timezone_set('Asia/Kuala_Lumpur');

if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

$errors = [];

$penguinId = '';
$activityDate = date('Y-m-d');
$activityTime = date('H:i');
$enrichmentType = '';
$activityDescription = '';
$observedResponse = '';
$notes = '';

// Load eligible penguins.
$penguinsStmt = $pdo->query("
    SELECT penguin_id, animal_code, name
    FROM penguins
    WHERE archived_at IS NULL
      AND status IN ('Active', 'Quarantine')
    ORDER BY animal_code
");

$penguins = $penguinsStmt->fetchAll();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    if (
        !isset($_POST['csrf_token']) ||
        !hash_equals($_SESSION['csrf_token'], $_POST['csrf_token'])
    ) {
        $errors[] = 'Invalid request. Please refresh the page.';
    }

    $penguinId = trim($_POST['penguin_id'] ?? '');
    $activityDate = trim($_POST['activity_date'] ?? '');
    $activityTime = trim($_POST['activity_time'] ?? '');
    $enrichmentType = trim($_POST['enrichment_type'] ?? '');
    $activityDescription = trim($_POST['activity_description'] ?? '');
    $observedResponse = trim($_POST['observed_response'] ?? '');
    $notes = trim($_POST['notes'] ?? '');

    if (
        !filter_var($penguinId, FILTER_VALIDATE_INT) ||
        (int)$penguinId <= 0
    ) {
        $errors[] = 'Please select a valid penguin.';
    }

    $parsedDate = DateTime::createFromFormat('!Y-m-d', $activityDate);

    if (
        !$parsedDate ||
        $parsedDate->format('Y-m-d') !== $activityDate
    ) {
        $errors[] = 'Please enter a valid activity date.';
    }

    if (!preg_match('/^(?:[01]\d|2[0-3]):[0-5]\d$/', $activityTime)) {
        $errors[] = 'Please enter a valid activity time.';
    }

    if ($enrichmentType === '' || strlen($enrichmentType) > 100) {
        $errors[] = 'Please enter an enrichment type (maximum 100 characters).';
    }

    if ($activityDescription === '') {
        $errors[] = 'Please enter an activity description.';
    }

    if (strlen($activityDescription) > 5000) {
        $errors[] = 'Activity description must not exceed 5000 characters.';
    }

    $allowedResponses = ['Positive', 'Neutral', 'Negative'];

    if (
        $observedResponse !== '' &&
        !in_array($observedResponse, $allowedResponses, true)
    ) {
        $errors[] = 'Please select a valid observed response.';
    }

    if (strlen($notes) > 2000) {
        $errors[] = 'Notes must not exceed 2000 characters.';
    }

    // Confirm that the selected penguin is still eligible.
    if (empty($errors)) {
        $checkPenguin = $pdo->prepare("
            SELECT penguin_id
            FROM penguins
            WHERE penguin_id = ?
              AND archived_at IS NULL
              AND status IN ('Active', 'Quarantine')
        ");

        $checkPenguin->execute([(int)$penguinId]);

        if (!$checkPenguin->fetch()) {
            $errors[] = 'The selected penguin is unavailable.';
        }
    }

    if (empty($errors)) {
        try {
            $pdo->beginTransaction();

            $stmt = $pdo->prepare("
                INSERT INTO enrichment_records
                (
                    penguin_id,
                    keeper_id,
                    activity_date,
                    activity_time,
                    enrichment_type,
                    activity_description,
                    observed_response,
                    notes
                )
                VALUES (?, ?, ?, ?, ?, ?, ?, ?)
            ");

            $stmt->execute([
                (int)$penguinId,
                (int)$_SESSION['user_id'],
                $activityDate,
                $activityTime,
                $enrichmentType,
                $activityDescription,
                $observedResponse !== '' ? $observedResponse : null,
                $notes !== '' ? $notes : null
            ]);

            $recordId = (int)$pdo->lastInsertId();

            $newValues = [
                'penguin_id' => (int)$penguinId,
                'activity_date' => $activityDate,
                'activity_time' => $activityTime,
                'enrichment_type' => $enrichmentType,
                'activity_description' => $activityDescription,
                'observed_response' => $observedResponse,
                'notes' => $notes
            ];

            $audit = $pdo->prepare("
                INSERT INTO audit_logs
                (
                    user_id,
                    action,
                    entity_type,
                    entity_id,
                    new_values,
                    description
                )
                VALUES (?, 'CREATE', 'enrichment_records', ?, ?, ?)
            ");

            $audit->execute([
                (int)$_SESSION['user_id'],
                $recordId,
                json_encode($newValues, JSON_UNESCAPED_UNICODE),
                'Created enrichment activity record'
            ]);

            $pdo->commit();

            $_SESSION['enrichment_success'] =
                'Enrichment activity recorded successfully.';

            header('Location: index.php');
            exit;

        } catch (Throwable $e) {
            if ($pdo->inTransaction()) {
                $pdo->rollBack();
            }

            error_log('Enrichment record creation error: ' . $e->getMessage());

            $errors[] = 'Unable to save the enrichment activity. Please try again.';
        }
    }
}

$pageTitle = 'Record Enrichment Activity';
$breadcrumbs = ['Enrichment Records', 'Record Activity'];
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
                    <h1>Record Enrichment Activity</h1>
                    <p>Record an enrichment activity and the penguin's observed response.</p>
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
                    <label for="penguin_id">Penguin *</label>

                    <select name="penguin_id" id="penguin_id" required>
                        <option value="">Select penguin</option>

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
                    <label for="activity_date">Activity Date *</label>
                    <input
                        type="date"
                        name="activity_date"
                        id="activity_date"
                        value="<?= htmlspecialchars($activityDate) ?>"
                        required
                    >
                </div>

                <div class="form-group">
                    <label for="activity_time">Activity Time *</label>
                    <input
                        type="time"
                        name="activity_time"
                        id="activity_time"
                        value="<?= htmlspecialchars($activityTime) ?>"
                        required
                    >
                </div>

                <div class="form-group">
                    <label for="enrichment_type">Enrichment Type *</label>
                    <input
                        type="text"
                        name="enrichment_type"
                        id="enrichment_type"
                        maxlength="100"
                        value="<?= htmlspecialchars($enrichmentType) ?>"
                        placeholder="e.g. Puzzle feeder, novel object"
                        required
                    >
                </div>

                <div class="form-group">
                    <label for="activity_description">Activity Description *</label>
                    <textarea
                        name="activity_description"
                        id="activity_description"
                        maxlength="5000"
                        required
                    ><?= htmlspecialchars($activityDescription) ?></textarea>
                </div>

                <div class="form-group">
                    <label for="observed_response">Observed Response</label>

                    <select
                        name="observed_response"
                        id="observed_response"
                    >
                        <option value="">-- Not recorded --</option>

                        <?php foreach (['Positive', 'Neutral', 'Negative'] as $option): ?>
                            <option
                                value="<?= htmlspecialchars($option) ?>"
                                <?= $observedResponse === $option ? 'selected' : '' ?>
                            >
                                <?= htmlspecialchars($option) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div class="form-group">
                    <label for="notes">Additional Notes</label>
                    <textarea
                        name="notes"
                        id="notes"
                        maxlength="2000"
                    ><?= htmlspecialchars($notes) ?></textarea>
                </div>

                <button type="submit" class="btn-primary">
                    Save Activity
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

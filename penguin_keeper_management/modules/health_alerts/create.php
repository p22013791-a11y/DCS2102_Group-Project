<?php
require_once __DIR__ . '/../../config/db.php';
require_once __DIR__ . '/../../includes/auth.php';

requireLogin();

if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

$errors = [];

$timezone = new DateTimeZone('Asia/Kuala_Lumpur');
$today = (new DateTimeImmutable('now', $timezone))->format('Y-m-d');

$penguins = [];

try {
    $stmt = $pdo->query("
        SELECT penguin_id, animal_code, name, status
        FROM penguins
        WHERE archived_at IS NULL
          AND status IN ('Active', 'Quarantine')
        ORDER BY animal_code ASC
    ");

    $penguins = $stmt->fetchAll();

} catch (PDOException $e) {
    error_log('Health alert penguin loading error: ' . $e->getMessage());
    $errors[] = 'Unable to load the penguin list.';
}

$penguinId = '';
$reportDate = $today;
$reportTime = (new DateTimeImmutable('now', $timezone))->format('H:i');
$priority = 'Routine';
$observation = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $penguinId = trim($_POST['penguin_id'] ?? '');
    $reportDate = trim($_POST['report_date'] ?? '');
    $reportTime = trim($_POST['report_time'] ?? '');
    $priority = trim($_POST['priority'] ?? '');
    $observation = trim($_POST['observation'] ?? '');

    if (
        empty($_SESSION['csrf_token']) ||
        !isset($_POST['csrf_token']) ||
        !hash_equals($_SESSION['csrf_token'], $_POST['csrf_token'])
    ) {
        $errors[] = 'Invalid request. Please refresh the page.';
    }

    if (
        $penguinId === '' ||
        filter_var($penguinId, FILTER_VALIDATE_INT) === false ||
        (int)$penguinId < 1
    ) {
        $errors[] = 'Please select a valid penguin.';
    }

    $dateObject = DateTime::createFromFormat('!Y-m-d', $reportDate);
    $validDate = $dateObject &&
        $dateObject->format('Y-m-d') === $reportDate;
    $validTime = preg_match(
        '/^(?:[01]\d|2[0-3]):[0-5]\d$/',
        $reportTime
    );

    if (!$validTime) {
        $errors[] = 'Please enter a valid report time.';
    }
    if (!$validDate) {
        $errors[] = 'Please enter a valid report date.';
    }

    if (!in_array($priority, ['Routine', 'Attention', 'Urgent'], true)) {
        $errors[] = 'Please select a valid priority.';
    }

    if ($observation === '') {
        $errors[] = 'Please describe the observed concern.';
    } elseif (mb_strlen($observation) > 5000) {
        $errors[] = 'The observation must not exceed 5000 characters.';
    }

    if (empty($errors)) {
        try {
            // Confirm that the selected penguin is still eligible.
            $check = $pdo->prepare("
                SELECT penguin_id
                FROM penguins
                WHERE penguin_id = ?
                  AND archived_at IS NULL
                  AND status IN ('Active', 'Quarantine')
                LIMIT 1
            ");

            $check->execute([(int)$penguinId]);

            if (!$check->fetch()) {
                $errors[] = 'The selected penguin is not available.';
            } else {
                $pdo->beginTransaction();

                $insert = $pdo->prepare("
                    INSERT INTO health_alerts
                        (penguin_id, reported_by, report_date, report_time,
                         priority, observation, status)
                    VALUES
                        (?, ?, ?, ?, ?, ?, 'Pending')
                ");

                $insert->execute([
                    (int)$penguinId,
                    (int)$_SESSION['user_id'],
                    $reportDate,
                    $reportTime,
                    $priority,
                    $observation
                ]);

                $alertId = (int)$pdo->lastInsertId();

                $audit = $pdo->prepare("
                    INSERT INTO audit_logs
                        (user_id, action, entity_type, entity_id,
                         new_values, description)
                    VALUES
                        (?, 'CREATE', 'health_alerts', ?, ?, ?)
                ");

                $audit->execute([
                    (int)$_SESSION['user_id'],
                    $alertId,
                    json_encode([
                        'penguin_id' => (int)$penguinId,
                        'report_date' => $reportDate,
                        'report_time' => $reportTime,
                        'priority' => $priority,
                        'observation' => $observation,
                        'status' => 'Pending'
                    ]),
                    'Reported a health or behaviour concern.'
                ]);

                $pdo->commit();

                $_SESSION['health_alert_success'] =
                    'Health alert submitted successfully.';

                header('Location: index.php');
                exit;
            }

        } catch (PDOException $e) {
            if ($pdo->inTransaction()) {
                $pdo->rollBack();
            }

            error_log('Health alert creation error: ' . $e->getMessage());
            $errors[] = 'Unable to submit the health alert. Please try again.';

        } catch (Exception $e) {
            if ($pdo->inTransaction()) {
                $pdo->rollBack();
            }

            error_log('Health alert creation error: ' . $e->getMessage());
            $errors[] = 'Unable to submit the health alert. Please try again.';
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Report Health Alert | PKMS</title>
    <link rel="stylesheet"
          href="/penguin_keeper_management/assets/css/style.css">
</head>
<body>

<div class="app-layout">

    <?php include __DIR__ . '/../../includes/sidebar.php'; ?>

    <main class="main-content">

        <?php
        $breadcrumbs = ['Health Alerts', 'Report Concern'];
        include __DIR__ . '/../../includes/topbar.php';
        ?>

        <div class="page-content">

            <div class="page-header">
                <div>
                    <h1>Report Health Alert</h1>
                    <p>Record a health or behaviour concern for Senior Keeper review.</p>
                </div>
            </div>

            <?php if (!empty($errors)): ?>
                <div class="alert alert-error">
                    <?php foreach ($errors as $error): ?>
                        <p><?= htmlspecialchars($error) ?></p>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>

            <div class="form-section">

                <form method="POST" action="">

                    <input type="hidden"
                           name="csrf_token"
                           value="<?= htmlspecialchars($_SESSION['csrf_token']) ?>">

                    <div class="form-group">
                        <label for="penguin_id">Penguin *</label>

                        <select id="penguin_id" name="penguin_id" required>
                            <option value="">Select Penguin</option>

                            <?php foreach ($penguins as $penguin): ?>
                                <option
                                    value="<?= (int)$penguin['penguin_id'] ?>"
                                    <?= (string)$penguinId ===
                                        (string)$penguin['penguin_id']
                                        ? 'selected' : '' ?>>

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
                        <label for="report_date">Report Date *</label>

                        <input
                            type="date"
                            id="report_date"
                            name="report_date"
                            value="<?= htmlspecialchars($reportDate) ?>"
                            required>
                    </div>

                    <div class="form-group">
                        <label for="report_time">Report Time *</label>

                        <input
                            type="time"
                            id="report_time"
                            name="report_time"
                            value="<?= htmlspecialchars($reportTime) ?>"
                            required>
                    </div>

                    <div class="form-group">
                        <label for="priority">Priority *</label>

                        <select id="priority" name="priority" required>
                            <option value="Routine"
                                <?= $priority === 'Routine' ? 'selected' : '' ?>>
                                Routine
                            </option>

                            <option value="Attention"
                                <?= $priority === 'Attention' ? 'selected' : '' ?>>
                                Attention
                            </option>

                            <option value="Urgent"
                                <?= $priority === 'Urgent' ? 'selected' : '' ?>>
                                Urgent
                            </option>
                        </select>
                    </div>

                    <div class="form-group">
                        <label for="observation">Observed Concern *</label>

                        <textarea
                            id="observation"
                            name="observation"
                            rows="5"
                            maxlength="5000"
                            required><?= htmlspecialchars($observation) ?></textarea>

                        <small>
                            Describe what was observed. Do not enter a diagnosis
                            or treatment instruction.
                        </small>
                    </div>

                    <div class="form-actions">
                        <button type="submit" class="btn btn-primary">
                            Submit Health Alert
                        </button>

                        <a href="index.php" class="btn btn-secondary">
                            Cancel
                        </a>
                    </div>

                </form>

            </div>

        </div>

    </main>
</div>

</body>
</html>

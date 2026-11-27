<?php
require_once __DIR__ . '/../../config/db.php';
require_once __DIR__ . '/../../includes/auth.php';

requireRole('Senior Keeper');

if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

$alertId = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT);

if (!$alertId || $alertId < 1) {
    $_SESSION['health_alert_error'] = 'Invalid health alert.';
    header('Location: index.php');
    exit;
}

$errors = [];
$alert = null;
$reviewNotes = '';

try {
    $stmt = $pdo->prepare("
        SELECT
            h.*,
            p.animal_code,
            p.name AS penguin_name,
            p.species,
            u.staff_code AS reporter_code,
            u.full_name AS reporter_name,
            reviewer.full_name AS reviewer_name
        FROM health_alerts h
        INNER JOIN penguins p
            ON h.penguin_id = p.penguin_id
        INNER JOIN users u
            ON h.reported_by = u.user_id
        LEFT JOIN users reviewer
            ON h.reviewed_by = reviewer.user_id
        WHERE h.alert_id = ?
        LIMIT 1
    ");

    $stmt->execute([$alertId]);
    $alert = $stmt->fetch();

    if (!$alert) {
        $_SESSION['health_alert_error'] = 'Health alert not found.';
        header('Location: index.php');
        exit;
    }

    $reviewNotes = $alert['review_notes'] ?? '';

    if ($_SERVER['REQUEST_METHOD'] === 'POST') {

        $reviewNotes = trim($_POST['review_notes'] ?? '');
        $newStatus = trim($_POST['status'] ?? '');

        if (
            empty($_SESSION['csrf_token']) ||
            !isset($_POST['csrf_token']) ||
            !hash_equals($_SESSION['csrf_token'], $_POST['csrf_token'])
        ) {
            $errors[] = 'Invalid request. Please refresh the page.';
        }

        if (!in_array($newStatus, ['Reviewed', 'Resolved'], true)) {
            $errors[] = 'Please select a valid review action.';
        }

        if (mb_strlen($reviewNotes) > 5000) {
            $errors[] = 'Review notes must not exceed 5000 characters.';
        }

        if ($alert['status'] === 'Resolved') {
            $errors[] = 'This alert has already been resolved.';
        }

        if (empty($errors)) {
            $pdo->beginTransaction();

            // Lock the alert and recheck its current status.
            $lock = $pdo->prepare("
                SELECT *
                FROM health_alerts
                WHERE alert_id = ?
                FOR UPDATE
            ");

            $lock->execute([$alertId]);
            $currentAlert = $lock->fetch();

            if (
                !$currentAlert ||
                !in_array($currentAlert['status'], ['Pending', 'Reviewed'], true)
            ) {
                throw new Exception(
                    'This alert is no longer available for review.'
                );
            }

            $oldValues = json_encode($currentAlert);

            if ($newStatus === 'Resolved') {
                $update = $pdo->prepare("
                    UPDATE health_alerts
                    SET
                        status = 'Resolved',
                        reviewed_by = ?,
                        review_notes = ?,
                        resolved_at = NOW()
                    WHERE alert_id = ?
                ");
            } else {
                $update = $pdo->prepare("
                    UPDATE health_alerts
                    SET
                        status = 'Reviewed',
                        reviewed_by = ?,
                        review_notes = ?,
                        resolved_at = NULL
                    WHERE alert_id = ?
                ");
            }

            $update->execute([
                (int)$_SESSION['user_id'],
                $reviewNotes,
                $alertId
            ]);

            $newValues = json_encode([
                'status' => $newStatus,
                'reviewed_by' => (int)$_SESSION['user_id'],
                'review_notes' => $reviewNotes,
                'resolved_at' => $newStatus === 'Resolved'
                    ? date('Y-m-d H:i:s')
                    : null
            ]);

            $audit = $pdo->prepare("
                INSERT INTO audit_logs
                    (user_id, action, entity_type, entity_id,
                     old_values, new_values, description)
                VALUES
                    (?, 'REVIEW', 'health_alerts', ?, ?, ?, ?)
            ");

            $audit->execute([
                (int)$_SESSION['user_id'],
                $alertId,
                $oldValues,
                $newValues,
                $newStatus === 'Resolved'
                    ? 'Resolved a health alert.'
                    : 'Reviewed a health alert.'
            ]);

            $pdo->commit();

            $_SESSION['health_alert_success'] =
                $newStatus === 'Resolved'
                    ? 'Health alert resolved successfully.'
                    : 'Health alert reviewed successfully.';

            header('Location: index.php');
            exit;
        }
    }

} catch (Exception $e) {
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }

    error_log('Health alert review error: ' . $e->getMessage());
    $errors[] = 'Unable to process the health alert. Please try again.';
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>Review Health Alert | PKMS</title>

    <link rel="stylesheet"
          href="/penguin_keeper_management/assets/css/style.css">
</head>
<body>

<div class="app-layout">

    <?php include __DIR__ . '/../../includes/sidebar.php'; ?>

    <main class="main-content">

        <?php
        $breadcrumbs = ['Health Alerts', 'Review Alert'];
        include __DIR__ . '/../../includes/topbar.php';
        ?>

        <div class="page-content">

            <div class="page-header">
                <div>
                    <h1>Review Health Alert</h1>
                    <p>Review the reported concern and record your findings.</p>
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

            <?php if ($alert): ?>

                <section class="form-container">

                    <h2>Reported Information</h2>

                    <p>
                        <strong>Penguin:</strong>
                        <?= htmlspecialchars(
                            $alert['animal_code'] . ' - ' .
                            $alert['penguin_name']
                        ) ?>
                    </p>

                    <p>
                        <strong>Species:</strong>
                        <?= htmlspecialchars($alert['species']) ?>
                    </p>

                    <p>
                        <strong>Reported By:</strong>
                        <?= htmlspecialchars(
                            $alert['reporter_code'] . ' - ' .
                            $alert['reporter_name']
                        ) ?>
                    </p>

                    <p>
                        <strong>Report Date:</strong>
                        <?= htmlspecialchars($alert['report_date']) ?>
                    </p>

                    <p>
                        <strong>Report Time:</strong>
                        <?= !empty($alert['report_time'])
                            ? htmlspecialchars(substr($alert['report_time'], 0, 5))
                            : '-' ?>
                    </p>

                    <p>
                        <strong>Priority:</strong>
                        <?= htmlspecialchars($alert['priority']) ?>
                    </p>

                    <p>
                        <strong>Current Status:</strong>
                        <?= htmlspecialchars($alert['status']) ?>
                    </p>

                    <p><strong>Reported Concern:</strong></p>

                    <div>
                        <?= nl2br(htmlspecialchars($alert['observation'])) ?>
                    </div>

                    <?php if (!empty($alert['reviewer_name'])): ?>
                        <p>
                            <strong>Previously Reviewed By:</strong>
                            <?= htmlspecialchars($alert['reviewer_name']) ?>
                        </p>
                    <?php endif; ?>

                    <hr>

                    <h2>Senior Keeper Review</h2>

                    <?php if ($alert['status'] === 'Resolved'): ?>

                        <p>This alert has already been resolved.</p>

                        <p>
                            <strong>Review Notes:</strong>
                            <?= nl2br(htmlspecialchars(
                                $alert['review_notes'] ?? ''
                            )) ?>
                        </p>

                    <?php else: ?>

                        <form method="POST" action="">

                            <input
                                type="hidden"
                                name="csrf_token"
                                value="<?= htmlspecialchars($_SESSION['csrf_token']) ?>">

                            <div class="form-group">
                                <label for="review_notes">Review Notes</label>

                                <textarea
                                    id="review_notes"
                                    name="review_notes"
                                    rows="5"
                                    maxlength="5000"><?= htmlspecialchars($reviewNotes) ?></textarea>
                            </div>

                            <div class="form-group">
                                <label for="status">Review Action *</label>

                                <select id="status" name="status" required>
                                    <option value="Reviewed">
                                        Mark as Reviewed
                                    </option>

                                    <option value="Resolved">
                                        Mark as Resolved
                                    </option>
                                </select>
                            </div>

                            <div class="form-actions">
                                <button
                                    type="submit"
                                    class="btn-primary"
                                    onclick="return confirm('Confirm this health alert review action?');">
                                    Save Review
                                </button>

                                <a href="index.php" class="btn-secondary">
                                    Cancel
                                </a>
                            </div>

                        </form>

                    <?php endif; ?>

                </section>

            <?php endif; ?>

        </div>

    </main>
</div>

</body>
</html>

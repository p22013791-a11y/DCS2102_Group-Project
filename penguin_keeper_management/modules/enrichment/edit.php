
<?php

require_once __DIR__ . '/../../includes/auth.php';

requireLogin();

date_default_timezone_set('Asia/Kuala_Lumpur');

$errors = [];

$recordId = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT);

if (!$recordId || $recordId <= 0) {
    $_SESSION['enrichment_error'] = 'Invalid enrichment record ID.';
    header('Location: index.php');
    exit;
}

if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

$isSenior = ($_SESSION['role'] ?? '') === 'Senior Keeper';
$currentUserId = (int)$_SESSION['user_id'];

function validEnrichmentDate(string $date): bool
{
    $parsed = DateTime::createFromFormat('!Y-m-d', $date);

    return $parsed && $parsed->format('Y-m-d') === $date;
}

function validEnrichmentTime(string $time): bool
{
    $parsed = DateTime::createFromFormat('!H:i', $time);

    return $parsed && $parsed->format('H:i') === $time;
}

try {
    $stmt = $pdo->prepare("
        SELECT *
        FROM enrichment_records
        WHERE enrichment_id = ?
    ");
    $stmt->execute([$recordId]);

    $record = $stmt->fetch();

    if (!$record) {
        $_SESSION['enrichment_error'] = 'Enrichment record not found.';
        header('Location: index.php');
        exit;
    }

    if (
        !$isSenior &&
        (int)$record['keeper_id'] !== $currentUserId
    ) {
        http_response_code(403);
        exit('You are not authorised to edit this record.');
    }

} catch (PDOException $e) {
    error_log('Enrichment record retrieval error: ' . $e->getMessage());
    exit('Unable to load the enrichment record.');
}

$penguinId = (string)$record['penguin_id'];
$activityDate = $record['activity_date'] ?? '';
$activityTime = $record['activity_time'] ?? '';
$enrichmentType = $record['enrichment_type'] ?? '';
$activityDescription = $record['activity_description'] ?? '';
$observedResponse = $record['observed_response'] ?? '';
$notes = $record['notes'] ?? '';

try {
    $penguinsStmt = $pdo->query("
        SELECT penguin_id, animal_code, name, status
        FROM penguins
        WHERE archived_at IS NULL
          AND status IN ('Active', 'Quarantine')
        ORDER BY animal_code
    ");

    $penguins = $penguinsStmt->fetchAll();

    // Keep the currently assigned penguin available in the form.
    $currentPenguinExists = false;

    foreach ($penguins as $penguin) {
        if ((int)$penguin['penguin_id'] === (int)$record['penguin_id']) {
            $currentPenguinExists = true;
            break;
        }
    }

    if (!$currentPenguinExists) {
        $currentPenguinStmt = $pdo->prepare("
            SELECT penguin_id, animal_code, name, status
            FROM penguins
            WHERE penguin_id = ?
        ");
        $currentPenguinStmt->execute([(int)$record['penguin_id']]);

        $currentPenguin = $currentPenguinStmt->fetch();

        if ($currentPenguin) {
            array_unshift($penguins, $currentPenguin);
        }
    }

} catch (PDOException $e) {
    error_log('Enrichment penguin list error: ' . $e->getMessage());
    exit('Unable to load penguin information.');
}

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
        filter_var($penguinId, FILTER_VALIDATE_INT) === false ||
        (int)$penguinId <= 0
    ) {
        $errors[] = 'Please select a valid penguin.';
    }

    if (!validEnrichmentDate($activityDate)) {
        $errors[] = 'Please enter a valid activity date.';
    }

    if (!validEnrichmentTime($activityTime)) {
        $errors[] = 'Please enter a valid activity time.';
    }

    if ($enrichmentType === '') {
        $errors[] = 'Please enter the enrichment type.';
    } elseif (mb_strlen($enrichmentType) > 100) {
        $errors[] = 'Enrichment type must not exceed 100 characters.';
    }

    if ($activityDescription === '') {
        $errors[] = 'Please enter the activity description.';
    } elseif (mb_strlen($activityDescription) > 2000) {
        $errors[] = 'Activity description must not exceed 2000 characters.';
    }

    $allowedResponses = ['Positive', 'Neutral', 'Negative'];

    if (
        $observedResponse !== '' &&
        !in_array($observedResponse, $allowedResponses, true)
    ) {
        $errors[] = 'Please select a valid observed response.';
    }

    if (mb_strlen($notes) > 2000) {
        $errors[] = 'Notes must not exceed 2000 characters.';
    }

    if (empty($errors)) {
        try {
            $pdo->beginTransaction();

            // Lock the record and recheck permission before updating.
            $lockStmt = $pdo->prepare("
                SELECT *
                FROM enrichment_records
                WHERE enrichment_id = ?
                FOR UPDATE
            ");
            $lockStmt->execute([$recordId]);

            $oldRecord = $lockStmt->fetch();

            if (!$oldRecord) {
                throw new RuntimeException('Enrichment record not found.');
            }

            if (
                !$isSenior &&
                (int)$oldRecord['keeper_id'] !== $currentUserId
            ) {
                throw new RuntimeException(
                    'You are not authorised to edit this record.'
                );
            }

            // The selected penguin must be eligible.
            // The existing penguin remains valid for this record.
            $penguinStmt = $pdo->prepare("
                SELECT penguin_id
                FROM penguins
                WHERE penguin_id = ?
                  AND archived_at IS NULL
                  AND status IN ('Active', 'Quarantine')
            ");
            $penguinStmt->execute([(int)$penguinId]);

            $eligiblePenguin = $penguinStmt->fetch();

            if (!$eligiblePenguin) {
                throw new RuntimeException(
                    'The selected penguin is not available for new enrichment records.'
                );
            }

            $newValues = [
                'penguin_id' => (int)$penguinId,
                'activity_date' => $activityDate,
                'activity_time' => $activityTime,
                'enrichment_type' => $enrichmentType,
                'activity_description' => $activityDescription,
                'observed_response' => $observedResponse !== ''
                    ? $observedResponse : null,
                'notes' => $notes !== '' ? $notes : null
            ];

            $updateStmt = $pdo->prepare("
                UPDATE enrichment_records
                SET
                    penguin_id = ?,
                    activity_date = ?,
                    activity_time = ?,
                    enrichment_type = ?,
                    activity_description = ?,
                    observed_response = ?,
                    notes = ?
                WHERE enrichment_id = ?
            ");

            $updateStmt->execute([
                $newValues['penguin_id'],
                $newValues['activity_date'],
                $newValues['activity_time'],
                $newValues['enrichment_type'],
                $newValues['activity_description'],
                $newValues['observed_response'],
                $newValues['notes'],
                $recordId
            ]);

            $oldValues = [
                'penguin_id' => $oldRecord['penguin_id'],
                'activity_date' => $oldRecord['activity_date'],
                'activity_time' => $oldRecord['activity_time'],
                'enrichment_type' => $oldRecord['enrichment_type'],
                'activity_description' => $oldRecord['activity_description'],
                'observed_response' => $oldRecord['observed_response'],
                'notes' => $oldRecord['notes']
            ];

            // Use the confirmed audit_logs column names.
            $auditStmt = $pdo->prepare("
                INSERT INTO audit_logs (
                    user_id,
                    action,
                    entity_type,
                    entity_id,
                    old_values,
                    new_values,
                    description,
                    created_at
                )
                VALUES (?, ?, ?, ?, ?, ?, ?, NOW())
            ");

            $auditStmt->execute([
                $currentUserId,
                'UPDATE',
                'enrichment_records',
                $recordId,
                json_encode($oldValues, JSON_UNESCAPED_UNICODE),
                json_encode($newValues, JSON_UNESCAPED_UNICODE),
                'Updated enrichment record #' . $recordId
            ]);

            $pdo->commit();

            $_SESSION['enrichment_success'] =
                'Enrichment record updated successfully.';

            header('Location: index.php');
            exit;

        } catch (Throwable $e) {
            if ($pdo->inTransaction()) {
                $pdo->rollBack();
            }

            error_log('Enrichment update error: ' . $e->getMessage());

            $errors[] = $e instanceof RuntimeException
                ? $e->getMessage()
                : 'Unable to update the enrichment record. Please try again.';
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>Edit Enrichment Record | PKMS</title>

    <link rel="stylesheet"
          href="/penguin_keeper_management/assets/css/style.css">
</head>
<body>

<div class="app-layout">

    <?php include __DIR__ . '/../../includes/sidebar.php'; ?>

    <main class="main-content">

        <?php
        $breadcrumbs = ['Enrichment Records', 'Edit Record'];
        include __DIR__ . '/../../includes/topbar.php';
        ?>

        <div class="page-content">

            <div class="page-header">
                <div>
                    <h1>Edit Enrichment Record</h1>
                    <p>Update the recorded enrichment activity.</p>
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

                <form method="POST" action="edit.php?id=<?= (int)$recordId ?>">

                    <input
                        type="hidden"
                        name="csrf_token"
                        value="<?= htmlspecialchars($_SESSION['csrf_token']) ?>"
                    >

                    <div class="form-group">
                        <label for="penguin_id">Penguin *</label>

                        <select id="penguin_id" name="penguin_id" required>
                            <option value="">Select Penguin</option>

                            <?php foreach ($penguins as $penguin): ?>
                                <option
                                    value="<?= (int)$penguin['penguin_id'] ?>"
                                    <?= (string)$penguinId === (string)$penguin['penguin_id']
                                        ? 'selected' : '' ?>
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
                        <label for="activity_date">Activity Date *</label>
                        <input
                            type="date"
                            id="activity_date"
                            name="activity_date"
                            value="<?= htmlspecialchars($activityDate) ?>"
                            required
                        >
                    </div>

                    <div class="form-group">
                        <label for="activity_time">Activity Time *</label>
                        <input
                            type="time"
                            id="activity_time"
                            name="activity_time"
                            value="<?= htmlspecialchars(substr($activityTime, 0, 5)) ?>"
                            required
                        >
                    </div>

                    <div class="form-group">
                        <label for="enrichment_type">Enrichment Type *</label>
                        <input
                            type="text"
                            id="enrichment_type"
                            name="enrichment_type"
                            maxlength="100"
                            value="<?= htmlspecialchars($enrichmentType) ?>"
                            required
                        >
                    </div>

                    <div class="form-group">
                        <label for="activity_description">Activity Description *</label>
                        <textarea
                            id="activity_description"
                            name="activity_description"
                            rows="4"
                            maxlength="2000"
                            required
                        ><?= htmlspecialchars($activityDescription) ?></textarea>
                    </div>

                    <div class="form-group">
                        <label for="observed_response">Observed Response</label>

                        <select
                            id="observed_response"
                            name="observed_response"
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
                            id="notes"
                            name="notes"
                            rows="4"
                            maxlength="2000"
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

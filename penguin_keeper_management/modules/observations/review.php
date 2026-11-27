<?php
require_once __DIR__ . '/../../includes/auth.php';

requireRole('Senior Keeper');

if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

$observationId = filter_var(
    $_GET['id'] ?? $_POST['observation_id'] ?? '',
    FILTER_VALIDATE_INT
);

if (!$observationId || $observationId < 1) {
    $_SESSION['observation_error'] = 'Invalid observation record.';
    header('Location: index.php');
    exit;
}

$errors = [];
$reviewNotes = '';

try {
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {

        $submittedToken = $_POST['csrf_token'] ?? '';
        $reviewNotes = trim($_POST['review_notes'] ?? '');

        if (
            !is_string($submittedToken) ||
            !hash_equals($_SESSION['csrf_token'], $submittedToken)
        ) {
            $errors[] = 'Invalid request. Please refresh the page.';
        }

        if (strlen($reviewNotes) > 5000) {
            $errors[] = 'Review notes cannot exceed 5000 characters.';
        }

        if (empty($errors)) {
            $pdo->beginTransaction();

            $stmt = $pdo->prepare("
                SELECT *
                FROM husbandry_observations
                WHERE observation_id = ?
                  AND status = 'Submitted'
                FOR UPDATE
            ");

            $stmt->execute([$observationId]);
            $oldRecord = $stmt->fetch();

            if (!$oldRecord) {
                throw new RuntimeException(
                    'This observation is no longer awaiting review.'
                );
            }

            $updateStmt = $pdo->prepare("
                UPDATE husbandry_observations
                SET
                    status = 'Reviewed',
                    reviewed_by = ?,
                    reviewed_at = NOW(),
                    review_notes = ?
                WHERE observation_id = ?
                  AND status = 'Submitted'
            ");

            $updateStmt->execute([
                (int)$_SESSION['user_id'],
                $reviewNotes !== '' ? $reviewNotes : null,
                $observationId
            ]);

            if ($updateStmt->rowCount() !== 1) {
                throw new RuntimeException(
                    'The observation could not be reviewed.'
                );
            }

            $newStmt = $pdo->prepare("
                SELECT *
                FROM husbandry_observations
                WHERE observation_id = ?
            ");

            $newStmt->execute([$observationId]);
            $newRecord = $newStmt->fetch();

            $auditStmt = $pdo->prepare("
                INSERT INTO audit_logs (
                    user_id,
                    action,
                    entity_type,
                    entity_id,
                    old_values,
                    new_values,
                    description
                ) VALUES (?, 'REVIEW', 'husbandry_observations', ?, ?, ?, ?)
            ");

            $auditStmt->execute([
                (int)$_SESSION['user_id'],
                $observationId,
                json_encode($oldRecord, JSON_UNESCAPED_UNICODE),
                json_encode($newRecord, JSON_UNESCAPED_UNICODE),
                'Reviewed a submitted husbandry observation.'
            ]);

            $pdo->commit();

            $_SESSION['observation_success'] =
                'Observation reviewed successfully.';

            header('Location: index.php');
            exit;
        }
    }

    $stmt = $pdo->prepare("
        SELECT
            o.*,
            p.animal_code,
            p.name AS penguin_name,
            u.full_name AS keeper_name,
            u.staff_code
        FROM husbandry_observations o
        INNER JOIN penguins p
            ON o.penguin_id = p.penguin_id
        INNER JOIN users u
            ON o.keeper_id = u.user_id
        WHERE o.observation_id = ?
          AND o.status = 'Submitted'
    ");

    $stmt->execute([$observationId]);
    $observation = $stmt->fetch();

    if (!$observation) {
        $_SESSION['observation_error'] =
            'Observation not found or it is no longer awaiting review.';

        header('Location: index.php');
        exit;
    }

} catch (Throwable $e) {

    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }

    error_log('Observation review error: ' . $e->getMessage());

    if ($e instanceof RuntimeException) {
        $_SESSION['observation_error'] = $e->getMessage();
        header('Location: index.php');
        exit;
    }

    $errors[] = 'Unable to process the review. Please try again.';
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Review Observation | PKMS</title>
    <link rel="stylesheet"
          href="/penguin_keeper_management/assets/css/style.css">
</head>
<body>

<div class="app-layout">

    <?php include __DIR__ . '/../../includes/sidebar.php'; ?>

    <main class="main-content">

        <?php
        $breadcrumbs = ['Husbandry Observations', 'Review Observation'];
        include __DIR__ . '/../../includes/topbar.php';
        ?>

        <div class="page-content">

            <div class="page-header">
                <div>
                    <h1>Review Observation</h1>
                    <p>
                        Review the submitted husbandry observation
                        before marking it as reviewed.
                    </p>
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

                <h2>Observation Details</h2>

                <div class="form-group">
                    <label>Penguin</label>
                    <p>
                        <?= htmlspecialchars($observation['animal_code']) ?>
                        -
                        <?= htmlspecialchars($observation['penguin_name']) ?>
                    </p>
                </div>

                <div class="form-group">
                    <label>Submitted By</label>
                    <p>
                        <?= htmlspecialchars($observation['keeper_name']) ?>
                        (<?= htmlspecialchars($observation['staff_code']) ?>)
                    </p>
                </div>

                <div class="form-group">
                    <label>Observation Date and Time</label>
                    <p>
                        <?= htmlspecialchars($observation['observation_date']) ?>
                        <?= htmlspecialchars($observation['observation_time']) ?>
                    </p>
                </div>

                <div class="form-group">
                    <label>Appetite</label>
                    <p><?= htmlspecialchars($observation['appetite'] ?? '-') ?></p>
                </div>

                <div class="form-group">
                    <label>Activity Level</label>
                    <p><?= htmlspecialchars($observation['activity_level'] ?? '-') ?></p>
                </div>

                <div class="form-group">
                    <label>Behaviour</label>
                    <p><?= htmlspecialchars($observation['behaviour'] ?? '-') ?></p>
                </div>

                <div class="form-group">
                    <label>Physical Concern</label>
                    <p><?= htmlspecialchars($observation['physical_concern'] ?? '-') ?></p>
                </div>

                <div class="form-group">
                    <label>Keeper's Notes</label>
                    <p><?= nl2br(htmlspecialchars($observation['notes'] ?? '-')) ?></p>
                </div>

                <form method="POST" action="review.php?id=<?= (int)$observationId ?>">

                    <input
                        type="hidden"
                        name="csrf_token"
                        value="<?= htmlspecialchars($_SESSION['csrf_token']) ?>"
                    >

                    <input
                        type="hidden"
                        name="observation_id"
                        value="<?= (int)$observationId ?>"
                    >

                    <div class="form-group">
                        <label for="review_notes">
                            Senior Keeper Review Notes (Optional)
                        </label>

                        <textarea
                            id="review_notes"
                            name="review_notes"
                            rows="5"
                            maxlength="5000"
                            placeholder="Enter review comments..."
                        ><?= htmlspecialchars($reviewNotes) ?></textarea>
                    </div>

                    <div class="form-actions">

                        <a href="index.php" class="btn-secondary">
                            Cancel
                        </a>

                        <button
                            type="submit"
                            class="btn-primary"
                            onclick="return confirm('Confirm that you have reviewed this observation?');"
                        >
                            Mark as Reviewed
                        </button>

                    </div>

                </form>

            </section>

        </div>

    </main>

</div>

</body>
</html>

<?php
require_once __DIR__ . '/../../includes/auth.php';

requireLogin();

if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}
$timezone = new DateTimeZone('Asia/Kuala_Lumpur');
$now = new DateTimeImmutable('now', $timezone);
$errors = [];
$penguins = [];

$observation = [
    'penguin_id' => '',
    'observation_date' => $now->format('Y-m-d'),
    'observation_time' => $now->format('H:i'),
    'appetite' => '',
    'activity_level' => '',
    'behaviour' => '',
    'physical_concern' => '',
    'notes' => ''
];

try {
    $stmt = $pdo->query("
        SELECT penguin_id, animal_code, name
        FROM penguins
        WHERE archived_at IS NULL
          AND status IN ('Active', 'Quarantine')
        ORDER BY animal_code ASC
    ");

    $penguins = $stmt->fetchAll();

} catch (PDOException $e) {
    error_log('Observation penguin list error: ' . $e->getMessage());
    $errors[] = 'Unable to load penguin information. Please try again.';
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    $observation['penguin_id'] = trim($_POST['penguin_id'] ?? '');
    $observation['observation_date'] = trim($_POST['observation_date'] ?? '');
    $observation['observation_time'] = trim($_POST['observation_time'] ?? '');
    $observation['appetite'] = trim($_POST['appetite'] ?? '');
    $observation['activity_level'] = trim($_POST['activity_level'] ?? '');
    $observation['behaviour'] = trim($_POST['behaviour'] ?? '');
    $observation['physical_concern'] = trim($_POST['physical_concern'] ?? '');
    $observation['notes'] = trim($_POST['notes'] ?? '');

    $submittedToken = $_POST['csrf_token'] ?? '';

    if (
        !is_string($submittedToken) ||
        !hash_equals($_SESSION['csrf_token'], $submittedToken)
    ) {
        $errors[] = 'Invalid request. Please refresh the page.';
    }

    if (
        !ctype_digit($observation['penguin_id']) ||
        (int)$observation['penguin_id'] < 1
    ) {
        $errors[] = 'Please select a valid penguin.';
    }

    $parsedDate = DateTime::createFromFormat(
        '!Y-m-d',
        $observation['observation_date']
    );

    if (
        !$parsedDate ||
        $parsedDate->format('Y-m-d') !== $observation['observation_date']
    ) {
        $errors[] = 'Please enter a valid observation date.';
    }

    if (
        !preg_match(
            '/^(?:[01]\d|2[0-3]):[0-5]\d$/',
            $observation['observation_time']
        )
    ) {
        $errors[] = 'Please enter a valid observation time.';
    }

   $allowedEvaluations = [
    'appetite' => ['Good', 'Moderate', 'Poor'],
    'activity_level' => ['Vibrant', 'Moderate', 'Poor'],
    'behaviour' => ['Good', 'Moderate', 'Poor']
];

foreach ($allowedEvaluations as $field => $allowedValues) {
    if (
        $observation[$field] !== '' &&
        !in_array($observation[$field], $allowedValues, true)
    ) {
        $errors[] = 'Please select a valid ' .
            str_replace('_', ' ', $field) . ' value.';
    }
}

if (mb_strlen($observation['physical_concern']) > 255) {
    $errors[] = 'Physical Concern cannot exceed 255 characters.';
}


    if (mb_strlen($observation['notes']) > 2000) {
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
                LIMIT 1
            ");

            $checkPenguin->execute([
                (int)$observation['penguin_id']
            ]);

            if (!$checkPenguin->fetch()) {
                $errors[] = 'The selected penguin is not available.';
            }

        } catch (PDOException $e) {
            error_log('Observation penguin validation error: ' . $e->getMessage());
            $errors[] = 'Unable to verify the selected penguin.';
        }
    }

    if (empty($errors)) {
        try {
            $stmt = $pdo->prepare("
                INSERT INTO husbandry_observations (
                    penguin_id,
                    keeper_id,
                    observation_date,
                    observation_time,
                    appetite,
                    activity_level,
                    behaviour,
                    physical_concern,
                    notes,
                    status
                ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, 'Draft')
            ");

            $stmt->execute([
                (int)$observation['penguin_id'],
                (int)$_SESSION['user_id'],
                $observation['observation_date'],
                $observation['observation_time'],
                $observation['appetite'] !== '' ? $observation['appetite'] : null,
                $observation['activity_level'] !== '' ? $observation['activity_level'] : null,
                $observation['behaviour'] !== '' ? $observation['behaviour'] : null,
                $observation['physical_concern'] !== '' ? $observation['physical_concern'] : null,
                $observation['notes'] !== '' ? $observation['notes'] : null
            ]);

            $_SESSION['observation_success'] =
                'Observation saved as Draft successfully.';

            header('Location: index.php');
            exit;

        } catch (PDOException $e) {
            error_log('Observation creation error: ' . $e->getMessage());
            $errors[] = 'Unable to save the observation. Please try again.';
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Add Observation | PKMS</title>
    <link rel="stylesheet"
          href="/penguin_keeper_management/assets/css/style.css">
</head>
<body>

<div class="app-layout">

    <?php include __DIR__ . '/../../includes/sidebar.php'; ?>

    <main class="main-content">

        <?php
        $breadcrumbs = ['Husbandry Observations', 'Add Observation'];
        include __DIR__ . '/../../includes/topbar.php';
        ?>

        <div class="page-content">

            <div class="page-header">
                <div>
                    <h1>Add Husbandry Observation</h1>
                    <p>Record routine penguin behaviour and observations.</p>
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

                <form method="POST" action="create.php">

                    <input
                        type="hidden"
                        name="csrf_token"
                        value="<?= htmlspecialchars($_SESSION['csrf_token']) ?>"
                    >

                    <div class="form-group">
                        <label for="penguin_id">Penguin *</label>

                        <select id="penguin_id" name="penguin_id" required>
                            <option value="">Select a penguin</option>

                            <?php foreach ($penguins as $penguin): ?>
                                <option
                                    value="<?= (int)$penguin['penguin_id'] ?>"
                                    <?= (string)$observation['penguin_id'] === (string)$penguin['penguin_id'] ? 'selected' : '' ?>
                                >
                                    <?= htmlspecialchars(
                                        $penguin['animal_code'] . ' - ' . $penguin['name']
                                    ) ?>
                                </option>
                            <?php endforeach; ?>

                        </select>
                    </div>

                    <div class="form-group">
                        <label for="observation_date">Observation Date *</label>
                        <input
                            type="date"
                            id="observation_date"
                            name="observation_date"
                            value="<?= htmlspecialchars($observation['observation_date']) ?>"
                            required
                        >
                    </div>

                    <div class="form-group">
                        <label for="observation_time">Observation Time *</label>
                        <input
                            type="time"
                            id="observation_time"
                            name="observation_time"
                            value="<?= htmlspecialchars($observation['observation_time']) ?>"
                            required
                        >
                    </div>

                    <div class="form-group">
                        <label for="appetite">Appetite</label>

                        <select id="appetite" name="appetite">
                            <option value="">-- Not recorded --</option>

                            <?php foreach (['Good', 'Moderate', 'Poor'] as $option): ?>
                                <option
                                    value="<?= htmlspecialchars($option) ?>"
                                    <?= $observation['appetite'] === $option ? 'selected' : '' ?>
                                >
                                    <?= htmlspecialchars($option) ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <div class="form-group">
                        <label for="activity_level">Activity Level</label>

                        <select id="activity_level" name="activity_level">
                            <option value="">-- Not recorded --</option>

                            <?php foreach (['Vibrant', 'Moderate', 'Poor'] as $option): ?>
                                <option
                                    value="<?= htmlspecialchars($option) ?>"
                                    <?= $observation['activity_level'] === $option ? 'selected' : '' ?>
                                >
                                    <?= htmlspecialchars($option) ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <div class="form-group">
                        <label for="behaviour">Behaviour</label>

                        <select id="behaviour" name="behaviour">
                            <option value="">-- Not recorded --</option>

                            <?php foreach (['Good', 'Moderate', 'Poor'] as $option): ?>
                                <option
                                    value="<?= htmlspecialchars($option) ?>"
                                    <?= $observation['behaviour'] === $option ? 'selected' : '' ?>
                                >
                                    <?= htmlspecialchars($option) ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <div class="form-group">
                        <label for="physical_concern">Physical Concern</label>
                        <textarea
                            id="physical_concern"
                            name="physical_concern"
                            maxlength="255"
                            rows="3"
                            placeholder="Record observable concerns only, if any."
                        ><?= htmlspecialchars($observation['physical_concern']) ?></textarea>
                    </div>

                    <div class="form-group">
                        <label for="notes">Additional Notes</label>
                        <textarea
                            id="notes"
                            name="notes"
                            maxlength="2000"
                            rows="4"
                        ><?= htmlspecialchars($observation['notes']) ?></textarea>
                    </div>

                    <div class="form-actions">
                        <button type="submit" class="btn-primary">
                            Save as Draft
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

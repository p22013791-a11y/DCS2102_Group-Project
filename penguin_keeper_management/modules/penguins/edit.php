<?php
require_once __DIR__ . '/../../includes/auth.php';

// Only Senior Keepers can edit penguin profiles.
requireRole('Senior Keeper');

$errors = [];

$penguinId = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT);

if (!$penguinId || $penguinId < 1) {
    http_response_code(400);
    exit("Invalid penguin ID.");
}

$penguin = null;
$enclosures = [];

try {
    $stmt = $pdo->prepare("
        SELECT *
        FROM penguins
        WHERE penguin_id = ?
            AND archived_at IS NULL
    ");
    $stmt->execute([$penguinId]);
    $penguin = $stmt->fetch();

    if (!$penguin) {
        http_response_code(404);
        exit("Penguin record not found.");
    }

    // Include active enclosures and the penguin's current enclosure.
    $stmt = $pdo->prepare("
        SELECT enclosure_id, enclosure_code, enclosure_name, status
        FROM enclosures
        WHERE status = 'Active'
           OR enclosure_id = ?
        ORDER BY enclosure_code
    ");
    $stmt->execute([$penguin['enclosure_id']]);
    $enclosures = $stmt->fetchAll();

} catch (PDOException $e) {
    error_log("Penguin edit loading error: " . $e->getMessage());
    http_response_code(500);
    exit("Unable to load the penguin record.");
}

// Validate optional dates.
function isValidEditDate(string $date): bool
{
    if ($date === '') {
        return true;
    }

    $parsedDate = DateTime::createFromFormat('!Y-m-d', $date);

    return $parsedDate !== false
        && $parsedDate->format('Y-m-d') === $date;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    // CSRF validation.
    $submittedToken = $_POST['csrf_token'] ?? '';

    if (
        !isset($_SESSION['csrf_token']) ||
        !is_string($submittedToken) ||
        !hash_equals($_SESSION['csrf_token'], $submittedToken)
    ) {
        $errors[] = "Invalid request. Please refresh the page.";
    }

    // Collect form values.
    $name = trim($_POST['name'] ?? '');
    $species = trim($_POST['species'] ?? '');
    $sex = $_POST['sex'] ?? '';
    $hatchDate = $_POST['hatch_date'] ?? '';
    $originType = trim($_POST['origin_type'] ?? '');
    $arrivalDate = $_POST['arrival_date'] ?? '';
    $enclosureId = $_POST['enclosure_id'] ?? '';
    $status = $_POST['status'] ?? '';
    $notes = trim($_POST['notes'] ?? '');

    // Validate required fields.
    if ($name === '' || strlen($name) > 60) {
        $errors[] = "Enter a penguin name (maximum 60 characters).";
    }

    if ($species === '' || strlen($species) > 80) {
        $errors[] = "Enter a species (maximum 80 characters).";
    }

    if (!in_array($sex, ['Male', 'Female', 'Unknown'], true)) {
        $errors[] = "Select a valid sex.";
    }

    if ($originType !== '' && strlen($originType) > 50) {
        $errors[] = "Origin type must not exceed 50 characters.";
    }

    if (!isValidEditDate($hatchDate)) {
        $errors[] = "Enter a valid hatch date.";
    }

    if (!isValidEditDate($arrivalDate)) {
        $errors[] = "Enter a valid arrival date.";
    }

    if (!in_array(
        $status,
        ['Active', 'Quarantine', 'Transferred', 'Deceased'],
        true
    )) {
        $errors[] = "Select a valid penguin status.";
    }

    // Validate enclosure selection.
    $validatedEnclosureId = null;

    if ($enclosureId !== '') {

        if (
            !ctype_digit((string) $enclosureId) ||
            (int) $enclosureId < 1
        ) {
            $errors[] = "Select a valid enclosure.";
        } else {
            $stmt = $pdo->prepare("
                SELECT enclosure_id
                FROM enclosures
                WHERE enclosure_id = ?
                  AND (
                      status = 'Active'
                      OR enclosure_id = ?
                  )
            ");

            $stmt->execute([
                (int) $enclosureId,
                $penguin['enclosure_id']
            ]);

            $validatedEnclosureId = $stmt->fetchColumn();

            if ($validatedEnclosureId === false) {
                $errors[] = "The selected enclosure is unavailable.";
            }
        }
    }

    if (empty($errors)) {

        $newValues = [
            'name' => $name,
            'species' => $species,
            'sex' => $sex,
            'hatch_date' => $hatchDate !== '' ? $hatchDate : null,
            'origin_type' => $originType !== '' ? $originType : null,
            'arrival_date' => $arrivalDate !== '' ? $arrivalDate : null,
            'enclosure_id' => $validatedEnclosureId,
            'status' => $status,
            'notes' => $notes !== '' ? $notes : null
        ];

        $oldValues = [
            'name' => $penguin['name'],
            'species' => $penguin['species'],
            'sex' => $penguin['sex'],
            'hatch_date' => $penguin['hatch_date'],
            'origin_type' => $penguin['origin_type'],
            'arrival_date' => $penguin['arrival_date'],
            'enclosure_id' => $penguin['enclosure_id'],
            'status' => $penguin['status'],
            'notes' => $penguin['notes']
        ];

        try {
            $pdo->beginTransaction();

            $updateStmt = $pdo->prepare("
                UPDATE penguins
                SET
                    name = :name,
                    species = :species,
                    sex = :sex,
                    hatch_date = :hatch_date,
                    origin_type = :origin_type,
                    arrival_date = :arrival_date,
                    enclosure_id = :enclosure_id,
                    status = :status,
                    notes = :notes
                WHERE penguin_id = :penguin_id
            ");

            $updateStmt->execute([
                'name' => $newValues['name'],
                'species' => $newValues['species'],
                'sex' => $newValues['sex'],
                'hatch_date' => $newValues['hatch_date'],
                'origin_type' => $newValues['origin_type'],
                'arrival_date' => $newValues['arrival_date'],
                'enclosure_id' => $newValues['enclosure_id'],
                'status' => $newValues['status'],
                'notes' => $newValues['notes'],
                'penguin_id' => $penguinId
            ]);

            // Record the profile modification in the audit log.
            $auditStmt = $pdo->prepare("
                INSERT INTO audit_logs (
                    user_id,
                    action,
                    entity_type,
                    entity_id,
                    old_values,
                    new_values,
                    description
                ) VALUES (
                    :user_id,
                    :action,
                    :entity_type,
                    :entity_id,
                    :old_values,
                    :new_values,
                    :description
                )
            ");

            $auditStmt->execute([
                'user_id' => $_SESSION['user_id'],
                'action' => 'UPDATE',
                'entity_type' => 'penguins',
                'entity_id' => $penguinId,
                'old_values' => json_encode($oldValues),
                'new_values' => json_encode($newValues),
                'description' => 'Updated penguin profile: ' .
                    $penguin['animal_code']
            ]);

            $pdo->commit();

            $_SESSION['penguin_success'] =
                "Penguin " . $penguin['animal_code'] .
                " was updated successfully.";

            header("Location: index.php");
            exit();

        } catch (Throwable $e) {

            if ($pdo->inTransaction()) {
                $pdo->rollBack();
            }

            error_log("Penguin update error: " . $e->getMessage());

            $errors[] =
                "Unable to update the penguin. Please try again.";

        }
    }

    // Keep submitted values visible if validation fails.
    $penguin = array_merge($penguin, [
        'name' => $name,
        'species' => $species,
        'sex' => $sex,
        'hatch_date' => $hatchDate,
        'origin_type' => $originType,
        'arrival_date' => $arrivalDate,
        'enclosure_id' => $enclosureId,
        'status' => $status,
        'notes' => $notes
    ]);
}
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>Edit Penguin | PKMS</title>

    <link rel="stylesheet"
          href="/penguin_keeper_management/assets/css/style.css">
</head>

<body>

<div class="app-layout">

    <?php
    $currentPage = 'penguins';
    require_once __DIR__ . '/../../includes/sidebar.php';
    ?>

    <div class="main-area">

        <?php
        $breadcrumbs = [
            'Penguins',
            'Edit Penguin',
            $penguin['animal_code']
        ];
        require_once __DIR__ . '/../../includes/topbar.php';
        ?>

        <main class="main-content">

            <div class="page-heading">
                <div>
                    <h2>Edit Penguin Profile</h2>
                    <p>
                        Update information for
                        <?= htmlspecialchars($penguin['animal_code']) ?>.
                    </p>
                </div>
            </div>

            <?php if (!empty($errors)): ?>
                <div class="form-errors">
                    <strong>Please correct the following:</strong>
                    <ul>
                        <?php foreach ($errors as $error): ?>
                            <li><?= htmlspecialchars($error) ?></li>
                        <?php endforeach; ?>
                    </ul>
                </div>
            <?php endif; ?>

            <form method="POST"
                  action="edit.php?id=<?= (int) $penguinId ?>"
                  class="penguin-form">

                <input
                    type="hidden"
                    name="csrf_token"
                    value="<?= htmlspecialchars($_SESSION['csrf_token'] ?? '') ?>"
                >

                <div class="form-field">
                    <label>Penguin ID</label>
                    <input
                        type="text"
                        value="<?= htmlspecialchars($penguin['animal_code']) ?>"
                        readonly
                    >
                </div>

                <div class="form-field">
                    <label for="name">Penguin Name *</label>
                    <input
                        type="text"
                        id="name"
                        name="name"
                        maxlength="60"
                        required
                        value="<?= htmlspecialchars($penguin['name']) ?>"
                    >
                </div>

                <div class="form-field">
                    <label for="species">Species *</label>
                    <input
                        type="text"
                        id="species"
                        name="species"
                        maxlength="80"
                        required
                        value="<?= htmlspecialchars($penguin['species']) ?>"
                    >
                </div>

                <div class="form-field">
                    <label for="sex">Sex *</label>
                    <select id="sex" name="sex" required>
                        <?php foreach (['Unknown', 'Male', 'Female'] as $option): ?>
                            <option
                                value="<?= $option ?>"
                                <?= $penguin['sex'] === $option ? 'selected' : '' ?>
                            >
                                <?= $option ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div class="form-field">
                    <label for="hatch_date">Hatch Date</label>
                    <input
                        type="date"
                        id="hatch_date"
                        name="hatch_date"
                        value="<?= htmlspecialchars($penguin['hatch_date'] ?? '') ?>"
                    >
                </div>

                <div class="form-field">
                    <label for="origin_type">Origin Type</label>
                    <input
                        type="text"
                        id="origin_type"
                        name="origin_type"
                        maxlength="50"
                        value="<?= htmlspecialchars($penguin['origin_type'] ?? '') ?>"
                    >
                </div>

                <div class="form-field">
                    <label for="arrival_date">Arrival Date</label>
                    <input
                        type="date"
                        id="arrival_date"
                        name="arrival_date"
                        value="<?= htmlspecialchars($penguin['arrival_date'] ?? '') ?>"
                    >
                </div>

                <div class="form-field">
                    <label for="enclosure_id">Enclosure</label>
                    <select id="enclosure_id" name="enclosure_id">
                        <option value="">Not assigned</option>

                        <?php foreach ($enclosures as $enclosure): ?>
                            <option
                                value="<?= (int) $enclosure['enclosure_id'] ?>"
                                <?= (string) $penguin['enclosure_id'] ===
                                    (string) $enclosure['enclosure_id']
                                    ? 'selected' : '' ?>
                            >
                                <?= htmlspecialchars(
                                    $enclosure['enclosure_code'] .
                                    ' - ' . $enclosure['enclosure_name']
                                ) ?>
                                <?= $enclosure['status'] === 'Maintenance'
                                    ? ' (Maintenance)' : '' ?>
                            </option>
                        <?php endforeach; ?>

                    </select>
                </div>

                <div class="form-field">
                    <label for="status">Status *</label>
                    <select id="status" name="status" required>
                        <?php foreach (
                            ['Active', 'Quarantine', 'Transferred', 'Deceased']
                            as $option
                        ): ?>
                            <option
                                value="<?= $option ?>"
                                <?= $penguin['status'] === $option ? 'selected' : '' ?>
                            >
                                <?= $option ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div class="form-field full-width">
                    <label for="notes">Notes</label>
                    <textarea
                        id="notes"
                        name="notes"
                        rows="4"
                    ><?= htmlspecialchars($penguin['notes'] ?? '') ?></textarea>
                </div>

                <div class="form-actions full-width">
                    <button type="submit">Save Changes</button>

                    <a href="index.php" class="reset-search">
                        Cancel
                    </a>
                </div>

            </form>

        </main>

    </div>

</div>

</body>
</html>

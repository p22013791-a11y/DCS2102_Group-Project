<?php
require_once __DIR__ . '/../../includes/auth.php';

// Only Senior Keepers can create penguin profiles.
requireRole('Senior Keeper');

$errors = [];
$success = false;

// Form values.
$name = '';
$species = '';
$sex = 'Unknown';
$hatchDate = '';
$originType = '';
$arrivalDate = '';
$enclosureId = '';
$status = 'Active';
$notes = '';

$enclosures = [];

try {
    $stmt = $pdo->query("
        SELECT enclosure_id, enclosure_code, enclosure_name
        FROM enclosures
        WHERE status = 'Active'
        ORDER BY enclosure_code
    ");
    $enclosures = $stmt->fetchAll();
} catch (PDOException $e) {
    error_log("Enclosure loading error: " . $e->getMessage());
    $errors[] = "Unable to load enclosure information.";
}

// Validate date in YYYY-MM-DD format.
function isValidOptionalDate(string $date): bool
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

    // Collect and trim form values.
    $name = trim($_POST['name'] ?? '');
    $species = trim($_POST['species'] ?? '');
    $sex = $_POST['sex'] ?? '';
    $hatchDate = $_POST['hatch_date'] ?? '';
    $originType = trim($_POST['origin_type'] ?? '');
    $arrivalDate = $_POST['arrival_date'] ?? '';
    $enclosureId = $_POST['enclosure_id'] ?? '';
    $status = $_POST['status'] ?? '';
    $notes = trim($_POST['notes'] ?? '');

    // Required field validation.
    if ($name === '' || strlen($name) > 60) {
        $errors[] = "Enter a penguin name (maximum 60 characters).";
    }

    if ($species === '' || strlen($species) > 80) {
        $errors[] = "Enter a species (maximum 80 characters).";
    }

    if (!in_array($sex, ['Male', 'Female', 'Unknown'], true)) {
        $errors[] = "Select a valid sex.";
    }

    if (
        $originType !== '' &&
        strlen($originType) > 50
    ) {
        $errors[] = "Origin type must not exceed 50 characters.";
    }

    if (!isValidOptionalDate($hatchDate)) {
        $errors[] = "Enter a valid hatch date.";
    }

    if (!isValidOptionalDate($arrivalDate)) {
        $errors[] = "Enter a valid arrival date.";
    }

    if (!in_array(
        $status,
        ['Active', 'Quarantine', 'Transferred', 'Deceased'],
        true
    )) {
        $errors[] = "Select a valid penguin status.";
    }

    // Validate the selected enclosure.
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
                  AND status = 'Active'
            ");
            $stmt->execute([(int) $enclosureId]);

            $validatedEnclosureId = $stmt->fetchColumn();

            if ($validatedEnclosureId === false) {
                $errors[] = "The selected enclosure is unavailable.";
            }
        }
    }

    // Insert the new penguin when validation succeeds.
    if (empty($errors)) {

        $lockAcquired = false;

        try {
            // Prevent two users generating the same penguin code.
            $lockStmt = $pdo->query("
                SELECT GET_LOCK('pkms_penguin_code_generation', 10)
            ");

            $lockAcquired = ((int) $lockStmt->fetchColumn() === 1);

            if (!$lockAcquired) {
                throw new RuntimeException(
                    "Unable to generate a penguin ID. Please try again."
                );
            }

            $pdo->beginTransaction();

            // Find the highest existing P-number.
            $codeStmt = $pdo->query("
                SELECT COALESCE(
                    MAX(CAST(SUBSTRING(animal_code, 3) AS UNSIGNED)),
                    0
                )
                FROM penguins
                WHERE animal_code REGEXP '^P-[0-9]+$'
            ");

            $nextNumber = (int) $codeStmt->fetchColumn() + 1;
            $animalCode = 'P-' . str_pad(
                (string) $nextNumber,
                3,
                '0',
                STR_PAD_LEFT
            );

            $insertStmt = $pdo->prepare("
                INSERT INTO penguins (
                    animal_code,
                    name,
                    species,
                    sex,
                    hatch_date,
                    origin_type,
                    arrival_date,
                    enclosure_id,
                    status,
                    notes
                ) VALUES (
                    :animal_code,
                    :name,
                    :species,
                    :sex,
                    :hatch_date,
                    :origin_type,
                    :arrival_date,
                    :enclosure_id,
                    :status,
                    :notes
                )
            ");

            $insertStmt->execute([
                'animal_code' => $animalCode,
                'name' => $name,
                'species' => $species,
                'sex' => $sex,
                'hatch_date' => $hatchDate !== '' ? $hatchDate : null,
                'origin_type' => $originType !== '' ? $originType : null,
                'arrival_date' => $arrivalDate !== '' ? $arrivalDate : null,
                'enclosure_id' => $validatedEnclosureId,
                'status' => $status,
                'notes' => $notes !== '' ? $notes : null
            ]);

            $pdo->commit();

            $_SESSION['penguin_success'] =
                "Penguin {$animalCode} was created successfully.";

            header("Location: index.php");
            exit();

        } catch (Throwable $e) {

            if ($pdo->inTransaction()) {
                $pdo->rollBack();
            }

            error_log("Penguin creation error: " . $e->getMessage());

            if ($e instanceof RuntimeException) {
                $errors[] = $e->getMessage();
            } else {
                $errors[] =
                    "Unable to create the penguin. Please check the information and try again.";
            }

        } finally {

            if ($lockAcquired) {
                $pdo->query("
                    SELECT RELEASE_LOCK('pkms_penguin_code_generation')
                ");
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

    <title>Add Penguin | PKMS</title>

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
        $breadcrumbs = ['Penguins', 'Add Penguin'];
        require_once __DIR__ . '/../../includes/topbar.php';
        ?>

        <main class="main-content">

            <div class="page-heading">
                <div>
                    <h2>Add New Penguin</h2>
                    <p>Register a new penguin profile.</p>
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

            <form method="POST" action="create.php"
                  class="penguin-form">

                <input
                    type="hidden"
                    name="csrf_token"
                    value="<?= htmlspecialchars($_SESSION['csrf_token'] ?? '') ?>"
                >

                <div class="form-field">
                    <label for="name">Penguin Name *</label>
                    <input
                        type="text"
                        id="name"
                        name="name"
                        maxlength="60"
                        required
                        value="<?= htmlspecialchars($name) ?>"
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
                        value="<?= htmlspecialchars($species) ?>"
                    >
                </div>

                <div class="form-field">
                    <label for="sex">Sex *</label>
                    <select id="sex" name="sex" required>
                        <option value="Unknown" <?= $sex === 'Unknown' ? 'selected' : '' ?>>Unknown</option>
                        <option value="Male" <?= $sex === 'Male' ? 'selected' : '' ?>>Male</option>
                        <option value="Female" <?= $sex === 'Female' ? 'selected' : '' ?>>Female</option>
                    </select>
                </div>

                <div class="form-field">
                    <label for="hatch_date">Hatch Date</label>
                    <input
                        type="date"
                        id="hatch_date"
                        name="hatch_date"
                        value="<?= htmlspecialchars($hatchDate) ?>"
                    >
                </div>

                <div class="form-field">
                    <label for="origin_type">Origin Type</label>
                    <input
                        type="text"
                        id="origin_type"
                        name="origin_type"
                        maxlength="50"
                        placeholder="e.g. Captive-bred"
                        value="<?= htmlspecialchars($originType) ?>"
                    >
                </div>

                <div class="form-field">
                    <label for="arrival_date">Arrival Date</label>
                    <input
                        type="date"
                        id="arrival_date"
                        name="arrival_date"
                        value="<?= htmlspecialchars($arrivalDate) ?>"
                    >
                </div>

                <div class="form-field">
                    <label for="enclosure_id">Enclosure</label>
                    <select id="enclosure_id" name="enclosure_id">
                        <option value="">Not assigned</option>

                        <?php foreach ($enclosures as $enclosure): ?>
                            <option
                                value="<?= (int) $enclosure['enclosure_id'] ?>"
                                <?= (string) $enclosureId === (string) $enclosure['enclosure_id'] ? 'selected' : '' ?>
                            >
                                <?= htmlspecialchars($enclosure['enclosure_code'] . ' - ' . $enclosure['enclosure_name']) ?>
                            </option>
                        <?php endforeach; ?>

                    </select>
                </div>

                <div class="form-field">
                    <label for="status">Status *</label>
                    <select id="status" name="status" required>
                        <?php foreach (
                            ['Active', 'Quarantine', 'Transferred', 'Deceased']
                            as $availableStatus
                        ): ?>
                            <option
                                value="<?= htmlspecialchars($availableStatus) ?>"
                                <?= $status === $availableStatus ? 'selected' : '' ?>
                            >
                                <?= htmlspecialchars($availableStatus) ?>
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
                    ><?= htmlspecialchars($notes) ?></textarea>
                </div>

                <div class="form-actions full-width">
                    <button type="submit">Save Penguin</button>

                    <a href="index.php" class="btn-secondary">
                        Cancel
                    </a>
                </div>

            </form>

        </main>

    </div>

</div>

</body>
</html>

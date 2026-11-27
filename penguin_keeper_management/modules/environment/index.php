<?php
require_once __DIR__ . '/../../includes/auth.php';
requireLogin();

if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

date_default_timezone_set('Asia/Kuala_Lumpur');

$search = trim($_GET['search'] ?? '');
$dateFrom = trim($_GET['date_from'] ?? '');
$dateTo = trim($_GET['date_to'] ?? date('Y-m-d'));

$errors = [];
$readings = [];
$pageError = false;

// Validate date values.
function validEnvironmentDate(string $date): bool
{
    if ($date === '') {
        return true;
    }

    $parsed = DateTime::createFromFormat('!Y-m-d', $date);

    return $parsed !== false &&
           $parsed->format('Y-m-d') === $date;
}

if (!validEnvironmentDate($dateFrom)) {
    $errors[] = 'Invalid start date.';
}

if (!validEnvironmentDate($dateTo)) {
    $errors[] = 'Invalid end date.';
}

if (
    empty($errors) &&
    $dateFrom !== '' &&
    $dateTo !== '' &&
    $dateFrom > $dateTo
) {
    $errors[] = 'Start date cannot be later than end date.';
}

// Retrieve environmental readings.
if (empty($errors)) {
    try {
        $sql = "
            SELECT
                r.reading_id,
                r.reading_date,
                r.reading_time,
                r.pool_temperature,
                r.ph,
                r.salinity,
                r.chlorine,
                r.notes,
                e.enclosure_code,
                e.enclosure_name,
                r.keeper_id,
                u.staff_code,
                u.full_name
            FROM environmental_readings r
            INNER JOIN enclosures e
                ON r.enclosure_id = e.enclosure_id
            INNER JOIN users u
                ON r.keeper_id = u.user_id
            WHERE 1 = 1
        ";

        $params = [];

        if ($search !== '') {
            $sql .= "
                AND (
                    e.enclosure_code LIKE ?
                    OR e.enclosure_name LIKE ?
                    OR u.staff_code LIKE ?
                    OR u.full_name LIKE ?
                    OR r.notes LIKE ?
                )
            ";

            $term = '%' . $search . '%';

            $params[] = $term;
            $params[] = $term;
            $params[] = $term;
            $params[] = $term;
            $params[] = $term;
        }

        if ($dateFrom !== '') {
            $sql .= " AND r.reading_date >= ?";
            $params[] = $dateFrom;
        }

        if ($dateTo !== '') {
            $sql .= " AND r.reading_date <= ?";
            $params[] = $dateTo;
        }

        $sql .= "
            ORDER BY
                r.reading_date DESC,
                r.reading_time DESC,
                r.reading_id DESC
        ";

        $stmt = $pdo->prepare($sql);
        $stmt->execute($params);

        $readings = $stmt->fetchAll();

    } catch (PDOException $e) {
        error_log('Environmental reading listing error: ' . $e->getMessage());
        $pageError = true;
    }
}

$successMessage = $_SESSION['environment_success'] ?? '';
$errorMessage = $_SESSION['environment_error'] ?? '';

unset($_SESSION['environment_success']);
unset($_SESSION['environment_error']);

// Prepare page layout.
$pageTitle = 'Environmental Readings';
$breadcrumbs = ['Environmental Readings'];
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
                    <h1>Environmental Readings</h1>
                    <p>
                        View and search environmental measurements
                        recorded for each enclosure.
                    </p>
                </div>

                <div class="search-actions">
                    <a href="create.php" class="btn-primary">
                        Add Environmental Reading
                    </a>
                </div>
            </div>

            <?php if ($successMessage !== ''): ?>
                <div class="success-message">
                    <?= htmlspecialchars($successMessage) ?>
                </div>
            <?php endif; ?>

            <?php if (!empty($errors)): ?>
                <div class="error-message">
                    <?php foreach ($errors as $error): ?>
                        <p><?= htmlspecialchars($error) ?></p>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>

            <?php if ($pageError): ?>
                <div class="error-message">
                    Unable to load environmental readings.
                    Please try again.
                </div>
            <?php endif; ?>

            <?php if ($errorMessage !== ''): ?>
                <div class="error-message">
                    <?= htmlspecialchars($errorMessage) ?>
                </div>
            <?php endif; ?>

            <section class="search-panel">

                <form
                    method="GET"
                    action="index.php"
                    class="search-form"
                >

                    <div class="form-group">
                        <label for="search">Search</label>

                        <input
                            type="text"
                            id="search"
                            name="search"
                            placeholder="Enclosure or keeper"
                            value="<?= htmlspecialchars($search) ?>"
                        >
                    </div>

                    <div class="form-group">
                        <label for="date_from">Date From</label>

                        <input
                            type="date"
                            id="date_from"
                            name="date_from"
                            value="<?= htmlspecialchars($dateFrom) ?>"
                        >
                    </div>

                    <div class="form-group">
                        <label for="date_to">Date To</label>

                        <input
                            type="date"
                            id="date_to"
                            name="date_to"
                            value="<?= htmlspecialchars($dateTo) ?>"
                        >
                    </div>

                    <div class="search-actions">
                        <button type="submit" class="btn-primary">
                            Search
                        </button>

                        <a href="index.php" class="btn-secondary">
                            Reset
                        </a>
                    </div>

                </form>

            </section>

            <section class="table-section">

                <div class="table-header">
                    <h2>Environmental Reading Records</h2>

                    <span>
                        <?= count($readings) ?> result(s)
                    </span>
                </div>

                <?php if ($pageError): ?>

                    <p class="error-message">
                        The records could not be displayed.
                    </p>

                <?php elseif (empty($readings)): ?>

                    <p class="empty-message">
                        No environmental readings found.
                    </p>

                <?php else: ?>

                    <div class="table-responsive">

                        <table class="data-table">

                            <thead>
                                <tr>
                                    <th>Date</th>
                                    <th>Time</th>
                                    <th>Enclosure</th>
                                    <th>Temperature</th>
                                    <th>pH</th>
                                    <th>Salinity</th>
                                    <th>Chlorine</th>
                                    <th>Keeper</th>
                                    <th>Notes</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>

                            <tbody>

                                <?php foreach ($readings as $reading): ?>

                                    <tr>

                                        <td>
                                            <?= htmlspecialchars(
                                                $reading['reading_date'] ?? '-'
                                            ) ?>
                                        </td>

                                        <td>
                                            <?= !empty($reading['reading_time'])
                                                ? htmlspecialchars(
                                                    substr($reading['reading_time'], 0, 5)
                                                )
                                                : '-' ?>
                                        </td>

                                        <td>
                                            <?= htmlspecialchars(
                                                $reading['enclosure_code'] .
                                                ' - ' .
                                                $reading['enclosure_name']
                                            ) ?>
                                        </td>

                                        <td>
                                            <?= htmlspecialchars(
                                                $reading['pool_temperature'] ?? '-'
                                            ) ?>
                                        </td>

                                        <td>
                                            <?= htmlspecialchars(
                                                $reading['ph'] ?? '-'
                                            ) ?>
                                        </td>

                                        <td>
                                            <?= htmlspecialchars(
                                                $reading['salinity'] ?? '-'
                                            ) ?>
                                        </td>

                                        <td>
                                            <?= htmlspecialchars(
                                                $reading['chlorine'] ?? '-'
                                            ) ?>
                                        </td>

                                        <td>
                                            <?= htmlspecialchars(
                                                $reading['full_name']
                                            ) ?>

                                            <br>

                                            <small>
                                                <?= htmlspecialchars(
                                                    $reading['staff_code']
                                                ) ?>
                                            </small>
                                        </td>

                                        <td>
                                            <?= htmlspecialchars(
                                                $reading['notes'] ?? '-'
                                            ) ?>
                                        </td>

                                        <td>
                                            <?php if (
                                                (int)$reading['keeper_id'] === (int)$_SESSION['user_id'] ||
                                                ($_SESSION['role'] ?? '') === 'Senior Keeper'
                                            ): ?>

                                                <a
                                                    href="edit.php?id=<?= (int)$reading['reading_id'] ?>"
                                                    class="btn-secondary"
                                                >
                                                    Edit
                                                </a>

                                                <form
                                                    method="POST"
                                                    action="delete.php"
                                                    style="display:inline;"
                                                    onsubmit="return confirm('Are you sure you want to delete this environmental reading?');"
                                                >
                                                    <input
                                                        type="hidden"
                                                        name="id"
                                                        value="<?= (int)$reading['reading_id'] ?>"
                                                    >

                                                    <input
                                                        type="hidden"
                                                        name="csrf_token"
                                                        value="<?= htmlspecialchars($_SESSION['csrf_token']) ?>"
                                                    >

                                                    <button type="submit" class="btn-danger">
                                                        Delete
                                                    </button>
                                                </form>

                                            <?php else: ?>
                                                -
                                            <?php endif; ?>
                                        </td>

                                    </tr>

                                <?php endforeach; ?>

                            </tbody>

                        </table>

                    </div>

                <?php endif; ?>

            </section>

        </div>

    </main>

</div>

</body>
</html>

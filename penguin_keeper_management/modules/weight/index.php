<?php
require_once __DIR__ . '/../../includes/auth.php';

requireLogin();

$search = trim($_GET['search'] ?? '');
$dateFrom = $_GET['date_from'] ?? '';
$today = new DateTimeImmutable(
    'now',
    new DateTimeZone('Asia/Kuala_Lumpur')
);

$dateTo = isset($_GET['date_to'])
    ? $_GET['date_to']
    : $today->format('Y-m-d');
$errors = [];

foreach (['date_from' => $dateFrom, 'date_to' => $dateTo] as $field => $date) {
    if ($date !== '') {
        $parsedDate = DateTime::createFromFormat('!Y-m-d', $date);

        if (!$parsedDate || $parsedDate->format('Y-m-d') !== $date) {
            $errors[] = 'Please enter valid dates.';
            break;
        }
    }
}

if ($dateFrom !== '' && $dateTo !== '' && $dateFrom > $dateTo) {
    $errors[] = 'The start date cannot be later than the end date.';
}

$weightRecords = [];
$pageError = false;

if (empty($errors)) {
    try {
        $sql = "
            SELECT
                w.weight_id,
                w.keeper_id,
                w.measurement_date,
                w.measurement_time,
                w.weight_kg,
                w.notes,
                p.animal_code,
                p.name AS penguin_name,
                u.staff_code,
                u.full_name AS keeper_name
            FROM weight_records w
            INNER JOIN penguins p
                ON w.penguin_id = p.penguin_id
            INNER JOIN users u
                ON w.keeper_id = u.user_id
            WHERE 1 = 1
        ";

        $params = [];

        if ($search !== '') {
            $sql .= "
                AND (
                    p.animal_code LIKE ?
                    OR p.name LIKE ?
                    OR u.staff_code LIKE ?
                    OR u.full_name LIKE ?
                )
            ";

            $term = '%' . $search . '%';

            $params[] = $term;
            $params[] = $term;
            $params[] = $term;
            $params[] = $term;
        }

        if ($dateFrom !== '') {
            $sql .= " AND w.measurement_date >= ?";
            $params[] = $dateFrom;
        }

        if ($dateTo !== '') {
            $sql .= " AND w.measurement_date <= ?";
            $params[] = $dateTo;
        }

        $sql .= "
            ORDER BY
                w.measurement_date DESC,
                w.measurement_time DESC,
                w.weight_id DESC
        ";

        $stmt = $pdo->prepare($sql);
        $stmt->execute($params);
        $weightRecords = $stmt->fetchAll();

    } catch (PDOException $e) {
        error_log('Weight record listing error: ' . $e->getMessage());
        $pageError = true;
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Weight Records | PKMS</title>
    <link rel="stylesheet"
          href="/penguin_keeper_management/assets/css/style.css">
</head>
<body>

<div class="app-layout">

    <?php include __DIR__ . '/../../includes/sidebar.php'; ?>

    <main class="main-content">

        <?php
        $breadcrumbs = ['Weight Records'];
        include __DIR__ . '/../../includes/topbar.php';
        ?>

        <div class="page-content">

            <div class="page-header">
                <div>
                    <h1>Weight Records</h1>
                    <p>
                        View and search penguin weight measurement
                        history.
                    </p>
                </div>

                <div class="search-actions">
                    <a href="create.php" class="btn-primary">
                        Add Weight Record
                    </a>
                </div>
            </div>

            <?php if (!empty($_SESSION['weight_success'])): ?>
                <div class="success-message">
                    <?= htmlspecialchars($_SESSION['weight_success']) ?>
                </div>
                <?php unset($_SESSION['weight_success']); ?>
            <?php endif; ?>

            <?php if (!empty($_SESSION['weight_error'])): ?>
                <div class="error-message">
                    <?= htmlspecialchars($_SESSION['weight_error']) ?>
                </div>
                <?php unset($_SESSION['weight_error']); ?>
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
                    Unable to load weight records. Please try again.
                </div>
            <?php endif; ?>

            <section class="search-panel">

                <form method="GET" action="index.php" class="search-form">

                    <div class="form-group">
                        <label for="search">Search</label>
                        <input
                            type="text"
                            id="search"
                            name="search"
                            placeholder="Penguin or keeper"
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
                    <h2>Weight Measurement History</h2>
                    <span>
                        <?= count($weightRecords) ?> result(s)
                    </span>
                </div>

                <div class="table-responsive">
                    <table class="data-table">
                        <thead>
                            <tr>
                                <th>Date</th>
                                <th>Time</th>
                                <th>Penguin</th>
                                <th>Weight (kg)</th>
                                <th>Keeper</th>
                                <th>Notes</th>
                                <th>Actions</th>
                            </tr>
                        </thead>

                        <tbody>

                            <?php if (empty($weightRecords)): ?>
                                <tr>
                                    <td colspan="7">
                                        No weight records found.
                                    </td>
                                </tr>
                            <?php else: ?>

                                <?php foreach ($weightRecords as $record): ?>
                                    <tr>
                                        <td>
                                            <?= htmlspecialchars($record['measurement_date']) ?>
                                        </td>

                                        <td>
                                            <?= htmlspecialchars($record['measurement_time']) ?>
                                        </td>

                                        <td>
                                            <?= htmlspecialchars($record['animal_code']) ?>
                                            -
                                            <?= htmlspecialchars($record['penguin_name']) ?>
                                        </td>

                                        <td>
                                            <?= htmlspecialchars((string)$record['weight_kg']) ?>
                                        </td>

                                        <td>
                                            <?= htmlspecialchars($record['keeper_name']) ?>
                                            <br>
                                            <small>
                                                <?= htmlspecialchars($record['staff_code']) ?>
                                            </small>
                                        </td>

                                        <td>
                                            <?= htmlspecialchars($record['notes'] ?? '-') ?>
                                        </td>

                                        <td>
                                            <?php if ((int)$record['keeper_id'] === (int)$_SESSION['user_id']): ?>
                                                <a href="edit.php?id=<?= (int)$record['weight_id'] ?>"
                                                    class="btn-secondary"
                                                >       
                                                    Edit
                                                </a>

                                                <form method="POST"
                                                    action="delete.php"
                                                    style="display:inline;"
                                                    onsubmit="return confirm('Are you sure you want to delete this weight record?');">

                                                    <input type="hidden"
                                                        name="csrf_token"
                                                        value="<?= htmlspecialchars($_SESSION['csrf_token']) ?>">

                                                    <input type="hidden"
                                                        name="weight_id"
                                                        value="<?= (int)$record['weight_id'] ?>">

                                                    <button type="submit" class="btn-danger">Delete</button>
                                                </form>
                                            <?php else: ?>
                                                -
                                            <?php endif; ?>
                                        </td>

                                    </tr>
                                <?php endforeach; ?>

                            <?php endif; ?>

                        </tbody>
                    </table>
                </div>

            </section>

        </div>

    </main>

</div>

</body>
</html>

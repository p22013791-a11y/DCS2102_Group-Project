<?php
require_once __DIR__ . '/../../includes/auth.php';

requireLogin();

if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

$search = trim($_GET['search'] ?? '');
$statusFilter = $_GET['status'] ?? '';
$dateFrom = $_GET['date_from'] ?? '';
$today = new DateTimeImmutable(
    'now',
    new DateTimeZone('Asia/Kuala_Lumpur')
);

$dateTo = isset($_GET['date_to'])
    ? $_GET['date_to']
    : $today->format('Y-m-d');

$allowedStatuses = ['Draft', 'Submitted', 'Reviewed'];

if (!in_array($statusFilter, $allowedStatuses, true)) {
    $statusFilter = '';
}

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

$observations = [];
$pageError = false;

if (empty($errors)) {
    try {
        $sql = "
            SELECT
                o.observation_id,
                o.keeper_id,
                o.observation_date,
                o.observation_time,
                o.appetite,
                o.activity_level,
                o.behaviour,
                o.physical_concern,
                o.notes,
                o.status,
                p.animal_code,
                p.name AS penguin_name,
                u.staff_code,
                u.full_name AS keeper_name
            FROM husbandry_observations o
            INNER JOIN penguins p
                ON o.penguin_id = p.penguin_id
            INNER JOIN users u
                ON o.keeper_id = u.user_id
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

        if ($statusFilter !== '') {
            $sql .= " AND o.status = ?";
            $params[] = $statusFilter;
        }

        if ($dateFrom !== '') {
            $sql .= " AND o.observation_date >= ?";
            $params[] = $dateFrom;
        }

        if ($dateTo !== '') {
            $sql .= " AND o.observation_date <= ?";
            $params[] = $dateTo;
        }

        $sql .= "
            ORDER BY
                o.observation_date DESC,
                o.observation_time DESC,
                o.observation_id DESC
        ";

        $stmt = $pdo->prepare($sql);
        $stmt->execute($params);
        $observations = $stmt->fetchAll();

    } catch (PDOException $e) {
        error_log('Observation listing error: ' . $e->getMessage());
        $pageError = true;
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Husbandry Observations | PKMS</title>
    <link rel="stylesheet"
          href="/penguin_keeper_management/assets/css/style.css">
</head>
<body>

<div class="app-layout">

    <?php include __DIR__ . '/../../includes/sidebar.php'; ?>

    <main class="main-content">

        <?php
        $breadcrumbs = ['Husbandry Observations'];
        include __DIR__ . '/../../includes/topbar.php';
        ?>

        <div class="page-content">

            <div class="page-header">
                <div>
                    <h1>Husbandry Observations</h1>
                    <p>
                        View and search penguin behaviour and routine
                        observation records.
                    </p>
                </div>

                <div class="search-actions">
                    <a href="create.php" class="btn-primary">
                        Add Observation
                    </a>
                </div>
            </div>

            <?php if (!empty($_SESSION['observation_success'])): ?>
                <div class="success-message">
                    <?= htmlspecialchars($_SESSION['observation_success']) ?>
                </div>
                <?php unset($_SESSION['observation_success']); ?>
            <?php endif; ?>

            <?php if (!empty($_SESSION['observation_error'])): ?>
                <div class="error-message">
                    <?= htmlspecialchars($_SESSION['observation_error']) ?>
                </div>
                <?php unset($_SESSION['observation_error']); ?>
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
                    Unable to load observation records. Please try again.
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
                        <label for="status">Observation Status</label>
                        <select id="status" name="status">
                            <option value="">All Statuses</option>

                            <?php foreach ($allowedStatuses as $status): ?>
                                <option value="<?= htmlspecialchars($status) ?>"
                                    <?= $statusFilter === $status ? 'selected' : '' ?>>
                                    <?= htmlspecialchars($status) ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
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
                    <h2>Observation Records</h2>
                    <span>
                        <?= count($observations) ?> result(s)
                    </span>
                </div>

                <div class="table-responsive">
                    <table class="data-table">
                        <thead>
                            <tr>
                                <th>Date</th>
                                <th>Time</th>
                                <th>Penguin</th>
                                <th>Keeper</th>
                                <th>Appetite</th>
                                <th>Activity</th>
                                <th>Behaviour</th>
                                <th>Physical Concern</th>
                                <th>Status</th>
                                <th>Actions</th>
                            </tr>
                        </thead>

                        <tbody>

                            <?php if (empty($observations)): ?>
                                <tr>
                                    <td colspan="10">
                                        No observation records found.
                                    </td>
                                </tr>
                            <?php else: ?>

                                <?php foreach ($observations as $observation): ?>
                                    <tr>
                                        <td>
                                            <?= htmlspecialchars($observation['observation_date']) ?>
                                        </td>

                                        <td>
                                            <?= htmlspecialchars($observation['observation_time']) ?>
                                        </td>

                                        <td>
                                            <?= htmlspecialchars($observation['animal_code']) ?>
                                            -
                                            <?= htmlspecialchars($observation['penguin_name']) ?>
                                        </td>

                                        <td>
                                            <?= htmlspecialchars($observation['keeper_name']) ?>
                                            <br>
                                            <small>
                                                <?= htmlspecialchars($observation['staff_code']) ?>
                                            </small>
                                        </td>

                                        <td>
                                            <?= htmlspecialchars($observation['appetite'] ?? '-') ?>
                                        </td>

                                        <td>
                                            <?= htmlspecialchars($observation['activity_level'] ?? '-') ?>
                                        </td>

                                        <td>
                                            <?= htmlspecialchars($observation['behaviour'] ?? '-') ?>
                                        </td>

                                        <td>
                                            <?= htmlspecialchars($observation['physical_concern'] ?? '-') ?>
                                        </td>

                                        <td>
                                            <?= htmlspecialchars($observation['status']) ?>
                                        </td>

                                        
                                        <td>

                                            <?php if (
                                                $observation['status'] === 'Submitted' &&
                                                $_SESSION['role'] === 'Senior Keeper'
                                            ): ?>

                                                <a
                                                    href="review.php?id=<?= (int)$observation['observation_id'] ?>"
                                                    class="btn-primary"
                                                >
                                                    Review
                                                </a>

                                            <?php elseif (
                                                $observation['status'] === 'Draft' &&
                                                (int)$observation['keeper_id'] === (int)$_SESSION['user_id']
                                            ): ?>

                                                <a
                                                    href="edit.php?id=<?= (int)$observation['observation_id'] ?>"
                                                    class="btn-secondary"
                                                >
                                                    Edit
                                                </a>

                                                <form
                                                    method="POST"
                                                    action="submit.php"
                                                    style="display:inline;"
                                                    onsubmit="return confirm('Submit this observation for Senior Keeper review? You will no longer be able to edit or delete it.');"
                                                >
                                                    <input
                                                        type="hidden"
                                                        name="csrf_token"
                                                        value="<?= htmlspecialchars($_SESSION['csrf_token']) ?>"
                                                    >

                                                    <input
                                                        type="hidden"
                                                        name="observation_id"
                                                        value="<?= (int)$observation['observation_id'] ?>"
                                                    >

                                                    <button type="submit" class="btn-primary">
                                                        Submit
                                                    </button>
                                                </form>

                                                <form
                                                    method="POST"
                                                    action="delete.php"
                                                    style="display:inline;"
                                                    onsubmit="return confirm('Are you sure you want to delete this Draft observation?');"
                                                >
                                                    <input
                                                        type="hidden"
                                                        name="csrf_token"
                                                        value="<?= htmlspecialchars($_SESSION['csrf_token']) ?>"
                                                    >

                                                    <input
                                                        type="hidden"
                                                        name="observation_id"
                                                        value="<?= (int)$observation['observation_id'] ?>"
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

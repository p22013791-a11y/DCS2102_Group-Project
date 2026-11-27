<?php
require_once __DIR__ . '/../../includes/auth.php';

requireLogin();

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

$allowedStatuses = ['Pending', 'Reviewed', 'Resolved'];

if (!in_array($statusFilter, $allowedStatuses, true)) {
    $statusFilter = '';
}

$errors = [];

foreach ([
    'Start date' => $dateFrom,
    'End date' => $dateTo
] as $label => $dateValue) {
    if ($dateValue !== '') {
        $dateObject = DateTime::createFromFormat('!Y-m-d', $dateValue);

        if (
            !$dateObject ||
            $dateObject->format('Y-m-d') !== $dateValue
        ) {
            $errors[] = 'Please enter valid dates.';
            break;
        }
    }
}

if (
    $dateFrom !== '' &&
    $dateTo !== '' &&
    $dateFrom > $dateTo
) {
    $errors[] = 'Start date cannot be later than end date.';
}

$alerts = [];
$pageError = false;

if (empty($errors)) {
    try {
        $sql = "
            SELECT
                h.alert_id,
                h.report_date,
                h.report_time,
                h.priority,
                h.observation,
                h.status,
                p.animal_code,
                p.name AS penguin_name,
                u.staff_code,
                u.full_name AS keeper_name
            FROM health_alerts h
            INNER JOIN penguins p
                ON h.penguin_id = p.penguin_id
            INNER JOIN users u
                ON h.reported_by = u.user_id
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
                    OR h.observation LIKE ?
                )
            ";

            $term = '%' . $search . '%';

            $params[] = $term;
            $params[] = $term;
            $params[] = $term;
            $params[] = $term;
            $params[] = $term;
        }

        if ($statusFilter !== '') {
            $sql .= " AND h.status = ?";
            $params[] = $statusFilter;
        }

        if ($dateFrom !== '') {
            $sql .= " AND h.report_date >= ?";
            $params[] = $dateFrom;
        }

        if ($dateTo !== '') {
            $sql .= " AND h.report_date <= ?";
            $params[] = $dateTo;
        }

        $sql .= "
            ORDER BY
                h.report_date DESC,
                h.report_time DESC,
                h.alert_id DESC
        ";

        $stmt = $pdo->prepare($sql);
        $stmt->execute($params);
        $alerts = $stmt->fetchAll();

    } catch (PDOException $e) {
        error_log('Health alert listing error: ' . $e->getMessage());
        $pageError = true;
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>Health Alerts | PKMS</title>

    <link rel="stylesheet"
          href="/penguin_keeper_management/assets/css/style.css">
</head>
<body>

<div class="app-layout">

    <?php include __DIR__ . '/../../includes/sidebar.php'; ?>

    <main class="main-content">

        <?php
        $breadcrumbs = ['Health Alerts'];
        include __DIR__ . '/../../includes/topbar.php';
        ?>

        <div class="page-content">

            <?php if (!empty($_SESSION['health_alert_success'])): ?>
                <div class="success-message">
                    <?= htmlspecialchars($_SESSION['health_alert_success']) ?>
                </div>
                <?php unset($_SESSION['health_alert_success']); ?>
            <?php endif; ?>

            <div class="page-header">
                <div>
                    <h1>Health Alerts</h1>
                    <p>
                        View reported penguin health and behaviour concerns.
                    </p>
                </div>

                <a href="create.php" class="btn-primary">
                    Report Concern
                </a>
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

            <?php if ($pageError): ?>
                <div class="error-message">
                    Unable to load health alerts. Please try again later.
                </div>
            <?php endif; ?>

            <section class="search-panel">

                <form method="GET"
                      action="index.php"
                      class="search-form">

                    <div class="form-group">
                        <label for="search">Search</label>

                        <input
                            type="text"
                            id="search"
                            name="search"
                            value="<?= htmlspecialchars($search) ?>"
                            placeholder="Penguin, keeper or observation">
                    </div>

                    <div class="form-group">
                        <label for="status">Status</label>

                        <select id="status" name="status">
                            <option value="">All Statuses</option>

                            <?php foreach ($allowedStatuses as $status): ?>
                                <option
                                    value="<?= htmlspecialchars($status) ?>"
                                    <?= $statusFilter === $status ? 'selected' : '' ?>>
                                    <?= htmlspecialchars($status) ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <div class="form-group">
                        <label for="date_from">Start Date</label>

                        <input
                            type="date"
                            id="date_from"
                            name="date_from"
                            value="<?= htmlspecialchars($dateFrom) ?>">
                    </div>

                    <div class="form-group">
                        <label for="date_to">End Date</label>

                        <input
                            type="date"
                            id="date_to"
                            name="date_to"
                            value="<?= htmlspecialchars($dateTo) ?>">
                    </div>

                    <div class="form-actions">
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
                    <h2>Health Alert Records</h2>
                     <span>
                        <?= count($alerts) ?> result(s)
                    </span>
                </div>

                <div class="table-responsive">
                    <table class="data-table">
                
                    <thead>
                        <tr>
                            <th>Date</th>
                            <th>Time</th>
                            <th>Penguin</th>
                            <th>Reported By</th>
                            <th>Priority</th>
                            <th>Observation</th>
                            <th>Status</th>
                            <?php if (($_SESSION['role'] ?? '') === 'Senior Keeper'): ?>
                                <th>Action</th>
                            <?php endif; ?>
                        </tr>
                    </thead>

                    <tbody>

                        <?php if (empty($alerts)): ?>
                            <tr>
                                <td colspan="<?= ($_SESSION['role'] ?? '') === 'Senior Keeper' ? 8 : 7 ?>">
                                    No health alerts found.
                                </td>
                            </tr>
                        <?php else: ?>

                            <?php foreach ($alerts as $alert): ?>
                                <tr>
                                    <td>
                                        <?= htmlspecialchars($alert['report_date']) ?>
                                    </td>

                                    <td>
                                        <?= !empty($alert['report_time'])
                                            ? htmlspecialchars(substr($alert['report_time'], 0, 5))
                                            : '-' ?>
                                    </td>

                                    <td>
                                        <?= htmlspecialchars(
                                            $alert['animal_code'] . ' - ' .
                                            $alert['penguin_name']
                                        ) ?>
                                    </td>

                                    <td>
                                        <?= htmlspecialchars(
                                            $alert['staff_code'] . ' - ' .
                                            $alert['keeper_name']
                                        ) ?>
                                    </td>

                                    <td>
                                        <?= htmlspecialchars($alert['priority']) ?>
                                    </td>

                                    <td>
                                        <?= nl2br(htmlspecialchars($alert['observation'])) ?>
                                    </td>

                                    <td>
                                        <?= htmlspecialchars($alert['status']) ?>
                                    </td>

                                    <?php if (($_SESSION['role'] ?? '') === 'Senior Keeper'): ?>
                                        <td>
                                            <?php if ($alert['status'] !== 'Resolved'): ?>
                                                <a href="review.php?id=<?= (int) $alert['alert_id'] ?>"
                                                class="btn-secondary">
                                                    Review
                                                </a>
                                            <?php else: ?>
                                                <span>Completed</span>
                                            <?php endif; ?>
                                        </td>
                                    <?php endif; ?>

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

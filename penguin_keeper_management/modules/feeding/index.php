<?php
require_once __DIR__ . '/../../includes/auth.php';

requireLogin();

if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

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
$feedingRecords = [];
$pageError = false;

// Validate date filters.
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
            $errors[] = "Invalid " . strtolower($label) . ".";
        }
    }
}

if (
    $dateFrom !== '' &&
    $dateTo !== '' &&
    $dateFrom > $dateTo
) {
    $errors[] = "Start date cannot be later than end date.";
}

if (empty($errors)) {
    try {
        $sql = "
            SELECT
                f.feeding_id,
                f.keeper_id,
                f.feeding_date,
                f.feeding_time,
                f.food_type,
                f.planned_grams,
                f.consumed_grams,
                f.notes,
                p.animal_code,
                p.name AS penguin_name,
                u.staff_code,
                u.full_name AS keeper_name,
                f.status
            FROM feeding_records f
            INNER JOIN penguins p
                ON f.penguin_id = p.penguin_id
            INNER JOIN users u
                ON f.keeper_id = u.user_id
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
                    OR f.food_type LIKE ?
                )
            ";

            $searchTerm = '%' . $search . '%';

            $params[] = $searchTerm;
            $params[] = $searchTerm;
            $params[] = $searchTerm;
            $params[] = $searchTerm;
            $params[] = $searchTerm;
        }

        if ($dateFrom !== '') {
            $sql .= " AND f.feeding_date >= ?";
            $params[] = $dateFrom;
        }

        if ($dateTo !== '') {
            $sql .= " AND f.feeding_date <= ?";
            $params[] = $dateTo;
        }

        $sql .= "
            ORDER BY
                f.feeding_date DESC,
                f.feeding_time DESC,
                f.feeding_id DESC
        ";

        $stmt = $pdo->prepare($sql);
        $stmt->execute($params);

        $feedingRecords = $stmt->fetchAll();

    } catch (PDOException $e) {
        error_log("Feeding record listing error: " . $e->getMessage());
        $pageError = true;
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>Feeding Records | PKMS</title>

    <link rel="stylesheet"
          href="/penguin_keeper_management/assets/css/style.css">
</head>
<body>

<div class="app-layout">

    <?php include __DIR__ . '/../../includes/sidebar.php'; ?>

    <main class="main-content">

        <?php
        $breadcrumbs = ['Feeding Records'];
        include __DIR__ . '/../../includes/topbar.php';
        ?>

        <div class="page-content">
            
            <?php if (!empty($_SESSION['feeding_success'])): ?>
                <div class="success-message">
                    <?= htmlspecialchars($_SESSION['feeding_success']) ?>
                </div>
                <?php unset($_SESSION['feeding_success']); ?>
            <?php endif; ?>

            <?php if (!empty($_SESSION['feeding_error'])): ?>
                <div class="error-message">
                    <?= htmlspecialchars($_SESSION['feeding_error']) ?>
                </div>
                <?php unset($_SESSION['feeding_error']); ?>
            <?php endif; ?>

            <div class="page-header">
                <div>
                    <h1>Feeding Records</h1>
                    <p>
                        View and search historical penguin feeding records.
                    </p>
                </div>

                <a href="create.php" class="btn-primary">
                    Record Feeding
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

            <section class="search-panel">

                <form method="GET"
                      action="index.php"
                      class="search-form">

                    <div class="form-group">
                        <label for="search">
                            Search Records
                        </label>

                        <input
                            type="text"
                            id="search"
                            name="search"
                            value="<?= htmlspecialchars($search) ?>"
                            placeholder="Penguin, keeper or food type"
                        >
                    </div>

                    <div class="form-group">
                        <label for="date_from">Start Date</label>

                        <input
                            type="date"
                            id="date_from"
                            name="date_from"
                            value="<?= htmlspecialchars($dateFrom) ?>"
                        >
                    </div>

                    <div class="form-group">
                        <label for="date_to">End Date</label>

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
                    <h2>Feeding History</h2>

                    <span>
                        <?= count($feedingRecords) ?> result(s)
                    </span>
                </div>

                <?php if ($pageError): ?>

                    <p class="error-message">
                        Unable to load feeding records.
                        Please try again.
                    </p>

                <?php elseif (empty($feedingRecords)): ?>

                    <p class="empty-message">
                        No feeding records found.
                    </p>

                <?php else: ?>

                    <div class="table-responsive">

                        <table class="data-table">

                            <thead>
                                <tr>
                                    <th>Date</th>
                                    <th>Time</th>
                                    <th>Penguin</th>
                                    <th>Food Type</th>
                                    <th>Planned (g)</th>
                                    <th>Consumed (g)</th>
                                    <th>Keeper</th>
                                    <th>Notes</th>
                                    <th>Status</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>

                            <tbody>

                                <?php foreach ($feedingRecords as $record): ?>

                                    <tr>

                                        <td>
                                            <?= htmlspecialchars($record['feeding_date']) ?>
                                        </td>

                                        <td>
                                            <?= htmlspecialchars($record['feeding_time']) ?>
                                        </td>

                                        <td>
                                            <strong>
                                                <?= htmlspecialchars($record['animal_code']) ?>
                                            </strong>
                                            <br>
                                            <?= htmlspecialchars($record['penguin_name']) ?>
                                        </td>

                                        <td>
                                            <?= htmlspecialchars($record['food_type']) ?>
                                        </td>

                                        <td>
                                            <?= htmlspecialchars((string)$record['planned_grams']) ?>
                                        </td>

                                        <td>
                                            <?= htmlspecialchars((string)$record['consumed_grams']) ?>
                                        </td>

                                        <td>
                                            <?= htmlspecialchars($record['keeper_name']) ?>
                                            <br>
                                            <small>
                                                <?= htmlspecialchars($record['staff_code']) ?>
                                            </small>
                                        </td>

                                        <td>
                                            <?= htmlspecialchars($record['notes'] ?? '') ?>
                                        </td>

                                        <td>
                                            <?php if ($record['status'] === 'Draft'): ?>
                                                <span class="status-draft">Draft</span>
                                            <?php else: ?>
                                                <span class="status-finalized">Finalized</span>
                                            <?php endif; ?>
                                        </td>

                                        <td>
                                            <?php if ($record['status'] === 'Draft'): ?>

                                                <?php if (
                                                    (int)$record['keeper_id'] === (int)$_SESSION['user_id']): ?>
                                                    
                                                    <a href="edit.php?id=<?= (int)$record['feeding_id'] ?>"
                                                       class="btn-secondary" 
                                                    >
                                                        Edit
                                                    </a>

                                                    <form method="POST"
                                                        action="delete.php"
                                                        onsubmit="return confirm('Delete this Draft feeding record? This action cannot be undone.');">

                                                        <input type="hidden"
                                                            name="csrf_token"
                                                            value="<?= htmlspecialchars($_SESSION['csrf_token']) ?>">

                                                        <input type="hidden"
                                                            name="feeding_id"
                                                            value="<?= (int)$record['feeding_id'] ?>">

                                                        <button type="submit" class="btn-secondary">
                                                            Delete
                                                        </button>
                                                    </form>

                                                <?php endif; ?>

                                                <?php if (
                                                    (int)$record['keeper_id'] === (int)$_SESSION['user_id']
                                                    || ($_SESSION['role'] ?? '') === 'Senior Keeper' ): ?>
                                               
                                                    <form method="POST"
                                                        action="finalize.php"
                                                        onsubmit="return confirm('Finalize this feeding record? It will become read-only.');">

                                                        <input type="hidden"
                                                            name="csrf_token"
                                                            value="<?= htmlspecialchars($_SESSION['csrf_token']) ?>">

                                                        <input type="hidden"
                                                            name="feeding_id"
                                                            value="<?= (int)$record['feeding_id'] ?>">

                                                        <button type="submit" class="btn-primary">
                                                            Finalize
                                                        </button>
                                                    </form>
                                                <?php endif; ?>

                                            <?php else: ?>

                                                <?php if (($_SESSION['role'] ?? '') === 'Senior Keeper'): ?>

                                                    <form method="POST"
                                                        action="reopen.php"
                                                        onsubmit="return confirm('Reopen this Finalized record for correction?');">

                                                        <input type="hidden"
                                                            name="csrf_token"
                                                            value="<?= htmlspecialchars($_SESSION['csrf_token']) ?>">

                                                        <input type="hidden"
                                                            name="feeding_id"
                                                            value="<?= (int)$record['feeding_id'] ?>">

                                                        <button type="submit" class="btn-secondary">
                                                            Reopen
                                                        </button>
                                                    </form>

                                                <?php else: ?>
                                                    <span>-</span>
                                                <?php endif; ?>

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

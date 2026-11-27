<?php
require_once __DIR__ . '/../../includes/auth.php';
requireLogin();

date_default_timezone_set('Asia/Kuala_Lumpur');

if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

$search = trim($_GET['search'] ?? '');
$dateFrom = trim($_GET['date_from'] ?? '');
$dateTo = trim($_GET['date_to'] ?? date('Y-m-d'));

$errors = [];
$records = [];
$pageError = false;

function validEnrichmentDate(string $date): bool
{
    if ($date === '') {
        return true;
    }

    $parsed = DateTime::createFromFormat('!Y-m-d', $date);

    return $parsed !== false &&
           $parsed->format('Y-m-d') === $date;
}

if (!validEnrichmentDate($dateFrom)) {
    $errors[] = 'Invalid start date.';
}

if (!validEnrichmentDate($dateTo)) {
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

if (empty($errors)) {
    try {
        $sql = "
            SELECT
                er.enrichment_id,
                er.penguin_id,
                er.keeper_id,
                er.activity_date,
                er.activity_time,
                er.enrichment_type,
                er.activity_description,
                er.observed_response,
                er.notes,
                p.animal_code,
                p.name AS penguin_name,
                u.staff_code,
                u.full_name
            FROM enrichment_records er
            INNER JOIN penguins p
                ON er.penguin_id = p.penguin_id
            INNER JOIN users u
                ON er.keeper_id = u.user_id
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
                    OR er.enrichment_type LIKE ?
                    OR er.activity_description LIKE ?
                    OR er.observed_response LIKE ?
                    OR er.notes LIKE ?
                )
            ";

            $term = '%' . $search . '%';

            for ($i = 0; $i < 8; $i++) {
                $params[] = $term;
            }
        }

        if ($dateFrom !== '') {
            $sql .= " AND er.activity_date >= ?";
            $params[] = $dateFrom;
        }

        if ($dateTo !== '') {
            $sql .= " AND er.activity_date <= ?";
            $params[] = $dateTo;
        }

        $sql .= "
            ORDER BY
                er.activity_date DESC,
                er.activity_time DESC,
                er.enrichment_id DESC
        ";

        $stmt = $pdo->prepare($sql);
        $stmt->execute($params);

        $records = $stmt->fetchAll();

    } catch (PDOException $e) {
        error_log('Enrichment listing error: ' . $e->getMessage());
        $pageError = true;
    }
}

$successMessage = $_SESSION['enrichment_success'] ?? '';
$errorMessage = $_SESSION['enrichment_error'] ?? '';

unset($_SESSION['enrichment_success']);
unset($_SESSION['enrichment_error']);

$pageTitle = 'Enrichment Records';
$breadcrumbs = ['Enrichment Records'];
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
                    <h1>Enrichment Records</h1>
                    <p>
                        View and search enrichment activities
                        and their observed responses.
                    </p>
                </div>

                <div class="search-actions">
                    <a href="create.php" class="btn-primary">
                        Add Enrichment Activity
                    </a>
                </div>
            </div>

            <?php if ($successMessage !== ''): ?>
                <div class="success-message">
                    <?= htmlspecialchars($successMessage) ?>
                </div>
            <?php endif; ?>

            <?php if ($errorMessage !== ''): ?>
                <div class="error-message">
                    <?= htmlspecialchars($errorMessage) ?>
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
                    Unable to load enrichment records. Please try again.
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
                            placeholder="Penguin, keeper or activity"
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
                    <h2>Enrichment Activity History</h2>

                    <span>
                        <?= count($records) ?> result(s)
                    </span>
                </div>

                <?php if ($pageError): ?>

                    <p class="error-message">
                        The records could not be displayed.
                    </p>

                <?php elseif (empty($records)): ?>

                    <p class="empty-message">
                        No enrichment records found.
                    </p>

                <?php else: ?>

                    <div class="table-responsive">

                        <table class="data-table">

                            <thead>
                                <tr>
                                    <th>Date</th>
                                    <th>Time</th>
                                    <th>Penguin</th>
                                    <th>Enrichment Type</th>
                                    <th>Activity Description</th>
                                    <th>Observed Response</th>
                                    <th>Notes</th>
                                    <th>Keeper</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>

                            <tbody>

                                <?php foreach ($records as $record): ?>

                                    <tr>

                                        <td>
                                            <?= htmlspecialchars(
                                                $record['activity_date'] ?? '-'
                                            ) ?>
                                        </td>

                                        <td>
                                            <?= !empty($record['activity_time'])
                                                ? htmlspecialchars(
                                                    substr($record['activity_time'], 0, 5)
                                                )
                                                : '-' ?>
                                        </td>

                                        <td>
                                            <?= htmlspecialchars(
                                                $record['animal_code'] . ' - ' .
                                                $record['penguin_name']
                                            ) ?>
                                        </td>

                                        <td>
                                            <?= htmlspecialchars(
                                                $record['enrichment_type']
                                            ) ?>
                                        </td>

                                        <td>
                                            <?= nl2br(htmlspecialchars(
                                                $record['activity_description']
                                            )) ?>
                                        </td>

                                        <td>
                                            <?= $record['observed_response'] !== null &&
                                                $record['observed_response'] !== ''
                                                ? nl2br(htmlspecialchars(
                                                    $record['observed_response']
                                                ))
                                                : '-' ?>
                                        </td>

                                        <td>
                                            <?= $record['notes'] !== null &&
                                                $record['notes'] !== ''
                                                ? nl2br(htmlspecialchars($record['notes']))
                                                : '-' ?>
                                        </td>

                                        <td>
                                            <?= htmlspecialchars($record['full_name']) ?>
                                            <br>
                                            <small>
                                                <?= htmlspecialchars($record['staff_code']) ?>
                                            </small>
                                        </td>

                                        
                                        <td>
                                            <?php
                                            $canManageRecord =
                                                ($_SESSION['role'] ?? '') === 'Senior Keeper' ||
                                                (int)$record['keeper_id'] === (int)$_SESSION['user_id'];
                                            ?>

                                            <?php if ($canManageRecord): ?>

                                                <a
                                                    href="edit.php?id=<?= (int)$record['enrichment_id'] ?>"
                                                    class="btn-secondary"
                                                >
                                                    Edit
                                                </a>

                                                <form
                                                    method="POST"
                                                    action="delete.php"
                                                    style="display:inline;"
                                                    onsubmit="return confirm('Are you sure you want to delete this enrichment record?');"
                                                >
                                                    <input
                                                        type="hidden"
                                                        name="enrichment_id"
                                                        value="<?= (int)$record['enrichment_id'] ?>"
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

<?php
require_once __DIR__ . '/../../includes/auth.php';

requireLogin();

$search = trim($_GET['search'] ?? '');
$statusFilter = $_GET['status'] ?? '';
$showArchived = (
    $_SESSION['role'] === 'Senior Keeper' &&
    ($_GET['show_archived'] ?? '') === '1'
);

$allowedStatuses = ['Active', 'Maintenance'];

if (!in_array($statusFilter, $allowedStatuses, true)) {
    $statusFilter = '';
}

$enclosures = [];
$pageError = false;

try {
    $sql = "
        SELECT
            e.enclosure_id,
            e.enclosure_code,
            e.enclosure_name,
            e.capacity,
            e.status,
            e.notes,
            e.archived_at,
            e.archived_by,
            COUNT(p.penguin_id) AS penguin_count
        FROM enclosures e
        LEFT JOIN penguins p
            ON p.enclosure_id = e.enclosure_id
            AND p.archived_at IS NULL
        WHERE 1 = 1
    ";

    $sql .= $showArchived
        ? " AND e.archived_at IS NOT NULL"
        : " AND e.archived_at IS NULL";

    $params = [];

    if ($search !== '') {
        $sql .= "
            AND (
                e.enclosure_code LIKE ?
                OR e.enclosure_name LIKE ?
            )
        ";

        $searchTerm = '%' . $search . '%';
        $params[] = $searchTerm;
        $params[] = $searchTerm;
    }

    if ($statusFilter !== '') {
        $sql .= " AND e.status = ?";
        $params[] = $statusFilter;
    }

    $sql .= "
        GROUP BY
            e.enclosure_id,
            e.enclosure_code,
            e.enclosure_name,
            e.capacity,
            e.status,
            e.notes,
            e.archived_at,
            e.archived_by
        ORDER BY e.enclosure_code ASC
    ";

    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    $enclosures = $stmt->fetchAll();

} catch (PDOException $e) {
    error_log("Enclosure listing error: " . $e->getMessage());
    $pageError = true;
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Enclosures | PKMS</title>
    <link rel="stylesheet" href="/penguin_keeper_management/assets/css/style.css">
</head>
<body>

<div class="app-layout">

    <?php include __DIR__ . '/../../includes/sidebar.php'; ?>

    <main class="main-content">

        <?php
        $breadcrumbs = ['Enclosures'];
        include __DIR__ . '/../../includes/topbar.php';
        ?>

        <div class="page-content">
            <?php if (!empty($_SESSION['enclosure_success'])): ?>
                <div class="success-message">
                    <?= htmlspecialchars($_SESSION['enclosure_success']) ?>
                </div>
                <?php unset($_SESSION['enclosure_success']); ?>
            <?php endif; ?>

            <?php if (!empty($_SESSION['enclosure_error'])): ?>
                <div class="error-message">
                    <?= htmlspecialchars($_SESSION['enclosure_error']) ?>
                </div>
                <?php unset($_SESSION['enclosure_error']); ?>
            <?php endif; ?>

            <div class="page-header">
                <div>
                    <h1>Enclosure Management</h1>
                    <p>View and search penguin enclosure information.</p>
                </div>

                <div class="search-actions">

                    <?php if ($_SESSION['role'] === 'Senior Keeper'): ?>

                        <?php if ($showArchived): ?>

                            <a href="index.php" class="btn-secondary">
                                View Current Enclosures
                            </a>

                        <?php else: ?>

                            <a href="index.php?show_archived=1" class="btn-secondary">
                                View Archived Enclosures
                            </a>

                            <a href="create.php" class="btn-primary">
                                Add New Enclosure
                            </a>

                        <?php endif; ?>

                    <?php endif; ?>

                </div>
            </div>

            <section class="search-panel">
                <form method="GET" action="index.php" class="search-form">

                    <div class="form-group">
                        <label for="search">Search Enclosures</label>
                        <input
                            type="text"
                            id="search"
                            name="search"
                            placeholder="Enter enclosure code or name"
                            value="<?= htmlspecialchars($search) ?>"
                        >
                    </div>

                    <div class="form-group">
                        <label for="status">Filter by Status</label>
                        <select id="status" name="status">
                            <option value="">All Statuses</option>

                            <option value="Active"
                                <?= $statusFilter === 'Active' ? 'selected' : '' ?>>
                                Active
                            </option>

                            <option value="Maintenance"
                                <?= $statusFilter === 'Maintenance' ? 'selected' : '' ?>>
                                Maintenance
                            </option>
                        </select>
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
                    <h2>Enclosure List</h2>
                    <span>
                        <?= count($enclosures) ?>
                        result(s)
                    </span>
                </div>

                <?php if ($pageError): ?>

                    <p class="error-message">
                        Unable to load enclosure information. Please try again.
                    </p>

                <?php elseif (empty($enclosures)): ?>

                    <p class="empty-message">
                        No enclosures found.
                    </p>

                <?php else: ?>

                    <div class="table-responsive">
                        <table class="data-table">
                            <thead>
                                <tr>
                                    <th>Enclosure Code</th>
                                    <th>Enclosure Name</th>
                                    <th>Capacity</th>
                                    <th>Penguins</th>
                                    <th>Status</th>

                                    <?php if ($_SESSION['role'] === 'Senior Keeper'): ?>
                                        <th>Actions</th>
                                    <?php endif; ?>
                                </tr>
                            </thead>

                            <tbody>
                                <?php foreach ($enclosures as $enclosure): ?>

                                    <tr>
                                        <td>
                                            <?= htmlspecialchars($enclosure['enclosure_code']) ?>
                                        </td>

                                        <td>
                                            <?= htmlspecialchars($enclosure['enclosure_name']) ?>
                                        </td>

                                        <td>
                                            <?= (int) $enclosure['capacity'] ?>
                                        </td>

                                        <td>
                                            <?= (int) $enclosure['penguin_count'] ?>
                                        </td>

                                        <td>
                                            <span class="status-badge">
                                                <?= htmlspecialchars($enclosure['status']) ?>
                                            </span>
                                        </td>

                                        <?php if ($_SESSION['role'] === 'Senior Keeper'): ?>
                                            <td>

                                                <?php if (!$showArchived): ?>

                                                    <a
                                                        href="edit.php?id=<?= (int)$enclosure['enclosure_id'] ?>"
                                                        class="btn-secondary"
                                                    >
                                                        Edit
                                                    </a>

                                                    <form
                                                        method="POST"
                                                        action="archive.php"
                                                        style="display:inline;"
                                                        onsubmit="return confirm('Are you sure you want to archive this enclosure?');"
                                                    >
                                                        <input
                                                            type="hidden"
                                                            name="csrf_token"
                                                            value="<?= htmlspecialchars($_SESSION['csrf_token']) ?>"
                                                        >

                                                        <input
                                                            type="hidden"
                                                            name="enclosure_id"
                                                            value="<?= (int)$enclosure['enclosure_id'] ?>"
                                                        >

                                                        <button type="submit" class="btn-danger">
                                                            Archive
                                                        </button>
                                                    </form>

                                                <?php else: ?>

                                                    <span class="status-badge">Archived</span>

                                                    <small>
                                                        <?= htmlspecialchars($enclosure['archived_at'] ?? '') ?>
                                                    </small>

                                                <?php endif; ?>

                                            </td>
                                        <?php endif; ?>

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

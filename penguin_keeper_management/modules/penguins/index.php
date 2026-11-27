<?php
require_once __DIR__ . '/../../includes/auth.php';

// Verify login and current account status.
requireLogin();

$fullName = $_SESSION['full_name'];
$role = $_SESSION['role'];

// Read search and status filters.
$search = trim($_GET['search'] ?? '');
$statusFilter = trim($_GET['status'] ?? '');

$showArchived = (
    $role === 'Senior Keeper' &&
    ($_GET['show_archived'] ?? '') === '1'
);

$allowedStatuses = [
    'Active',
    'Quarantine',
    'Transferred',
    'Deceased'
];

if (!in_array($statusFilter, $allowedStatuses, true)) {
    $statusFilter = '';
}

$penguins = [];
$loadError = false;

try {
    $sql = "
        SELECT
            p.penguin_id,
            p.animal_code,
            p.name,
            p.species,
            p.sex,
            p.hatch_date,
            p.arrival_date,
            p.status,
            p.archived_at,
            p.archived_by,
            e.enclosure_code,
            e.enclosure_name
        FROM penguins p
        LEFT JOIN enclosures e
            ON p.enclosure_id = e.enclosure_id
    ";

    $conditions = [];
    $params = [];

    // Normal view excludes archived penguins.
    // Senior Keepers can choose to view archived profiles.
    if ($showArchived) {
        $conditions[] = "p.archived_at IS NOT NULL";
    } else {
        $conditions[] = "p.archived_at IS NULL";
    }

    // Search by penguin code, name, species or enclosure.
    if ($search !== '') {
        $conditions[] = "(
            p.animal_code LIKE :code
            OR p.name LIKE :penguin_name
            OR p.species LIKE :species
            OR e.enclosure_code LIKE :enclosure
        )";

        $searchTerm = '%' . $search . '%';

        $params['code'] = $searchTerm;
        $params['penguin_name'] = $searchTerm;
        $params['species'] = $searchTerm;
        $params['enclosure'] = $searchTerm;
    }

    // Filter by penguin status.
    if ($statusFilter !== '') {
        $conditions[] = "p.status = :status";
        $params['status'] = $statusFilter;
    }

    if (!empty($conditions)) {
        $sql .= " WHERE " . implode(" AND ", $conditions);
    }

    $sql .= " ORDER BY p.animal_code ASC";

    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);

    $penguins = $stmt->fetchAll();

} catch (PDOException $e) {
    error_log("Penguin listing error: " . $e->getMessage());
    $loadError = true;
}
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>Penguin Management | PKMS</title>

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
        $breadcrumbs = ['Penguins'];
        require_once __DIR__ . '/../../includes/topbar.php';
        ?>

        <main class="main-content">

            <div class="page-heading">
                <div>
                    <h2>Penguin Management</h2>
                    <p>
                        <?= $showArchived
                            ? 'Viewing archived penguin profiles.'
                            : 'View and search current penguin profiles.' ?>
                    </p>
                </div>

                <?php if ($role === 'Senior Keeper'): ?>

                    <div class="penguin-heading-actions">

                        <?php if ($showArchived): ?>

                            <a href="index.php" class="btn-secondary">
                                View Current Penguins
                            </a>

                        <?php else: ?>

                            <a href="index.php?show_archived=1"
                            class="btn-secondary">
                                View Archived Penguins
                            </a>

                            <a href="create.php" class="add-penguin-button">
                                + Add Penguin
                            </a>

                        <?php endif; ?>

                    </div>

                <?php endif; ?>
            </div>

            <form method="GET"
                  action="index.php"
                  class="penguin-search-form">

                <div class="search-field">
                    <label for="search">Search Penguins</label>

                    <input
                        type="text"
                        id="search"
                        name="search"
                        placeholder="Enter penguin code, name, species or enclosure"
                        value="<?= htmlspecialchars($search) ?>"
                    >
                </div>

                <div class="search-field">
                    <label for="status">Penguin Status</label>

                    <select id="status" name="status">
                        <option value="">All Statuses</option>

                        <?php foreach ($allowedStatuses as $status): ?>
                            <option
                                value="<?= htmlspecialchars($status) ?>"
                                <?= $statusFilter === $status ? 'selected' : '' ?>
                            >
                                <?= htmlspecialchars($status) ?>
                            </option>
                        <?php endforeach; ?>

                    </select>
                </div>

                <div class="search-actions">
                    <button type="submit">Search</button>

                    <a href="index.php" class="reset-search">
                        Reset
                    </a>
                </div>

            </form>

            <?php if ($loadError): ?>

                <p class="dashboard-error">
                    Unable to load penguin records.
                    Please try again later.
                </p>

            <?php else: ?>

            <?php if (isset($_SESSION['penguin_success'])): ?>
                <p class="form-success">
                    <?= htmlspecialchars($_SESSION['penguin_success']) ?>
                </p>
                <?php unset($_SESSION['penguin_success']); ?>
            <?php endif; ?>

            <?php if (isset($_SESSION['penguin_error'])): ?>
                <p class="form-errors">
                    <?= htmlspecialchars($_SESSION['penguin_error']) ?>
                </p>
                <?php unset($_SESSION['penguin_error']); ?>
            <?php endif; ?>

            <section class="table-section">

                <div class="table-header">
                    <h2>
                        <?= $showArchived ? 'Archived Penguin Profiles' : 'Penguin List' ?>
                    </h2>

                    <span>
                        <?= count($penguins) ?> result(s)
                    </span>
                </div>

                <div class="table-responsive">

                    <table class="data-table">

                        <thead>
                            <tr>
                                <th>Penguin ID</th>
                                <th>Name</th>
                                <th>Species</th>
                                <th>Sex</th>
                                <th>Hatch Date</th>
                                <th>Arrival Date</th>
                                <th>Enclosure</th>
                                <th>Status</th>
                                <th>Actions</th>
                                
                            </tr>
                        </thead>

                        <tbody>

                            <?php if (count($penguins) > 0): ?>

                                <?php foreach ($penguins as $penguin): ?>

                                    <tr>
                                        <td>
                                            <?= htmlspecialchars($penguin['animal_code']) ?>
                                        </td>

                                        <td>
                                            <?= htmlspecialchars($penguin['name']) ?>
                                        </td>

                                        <td>
                                            <?= htmlspecialchars($penguin['species']) ?>
                                        </td>

                                        <td>
                                            <?= htmlspecialchars($penguin['sex']) ?>
                                        </td>

                                        <td>
                                            <?= htmlspecialchars(
                                                $penguin['hatch_date'] ?? 'Not recorded'
                                            ) ?>
                                        </td>

                                        <td>
                                            <?= htmlspecialchars(
                                                $penguin['arrival_date'] ?? 'Not recorded'
                                            ) ?>
                                        </td>

                                        <td>
                                            <?php if ($penguin['enclosure_code']): ?>
                                                <?= htmlspecialchars($penguin['enclosure_code']) ?>
                                                -
                                                <?= htmlspecialchars($penguin['enclosure_name']) ?>
                                            <?php else: ?>
                                                Not assigned
                                            <?php endif; ?>
                                        </td>

                                        <td>
                                            <?= htmlspecialchars($penguin['status']) ?>
                                        </td>
                                        <td>
                                            <?php if (!$showArchived): ?>
                                            <a
                                                href="view.php?id=<?= (int)$penguin['penguin_id'] ?>"
                                                class="btn-secondary"
                                            >
                                                View
                                            </a>
                                            <?php endif; ?>

                                            <?php if ($role === 'Senior Keeper'): ?>
                                                

                                                    <?php if (!$showArchived): ?>

                                                        

                                                        <a href="edit.php?id=<?= (int) $penguin['penguin_id'] ?>"
                                                            class="btn-secondary"
                                                        >
                                                            Edit
                                                        </a>

                                                        <form
                                                            method="POST"
                                                            action="archive.php"
                                                            class="archive-form"
                                                            onsubmit="return confirm('Are you sure you want to archive this penguin profile?');"
                                                        >
                                                            <input
                                                                type="hidden"
                                                                name="csrf_token"
                                                                value="<?= htmlspecialchars($_SESSION['csrf_token'] ?? '') ?>"
                                                            >

                                                            <input
                                                                type="hidden"
                                                                name="penguin_id"
                                                                value="<?= (int) $penguin['penguin_id'] ?>"
                                                            >

                                                            <button type="submit" class="btn-danger">
                                                                Archive
                                                            </button>
                                                        </form>

                                                    <?php else: ?>

                                                        <span>Archived</span>

                                                        <small>
                                                            <?= htmlspecialchars($penguin['archived_at']) ?>
                                                        </small>

                                                    <?php endif; ?>

                                                
                                            <?php endif; ?>
                                        </td>
                                    </tr>

                                <?php endforeach; ?>

                            <?php else: ?>

                                <tr>
                                    <td colspan="<?= $role === 'Senior Keeper' ? '9' : '8' ?>" class="empty-table-message">
                                        No penguin records found.
                                    </td>
                                </tr>

                            <?php endif; ?>

                        </tbody>

                    </table>

                </div>
            </section>
            <?php endif; ?>

        </main>

    </div>

</div>

</body>
</html>

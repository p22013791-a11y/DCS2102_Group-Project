<?php

require_once __DIR__ . '/../../includes/auth.php';
require_once __DIR__ . '/../../config/db.php';

/** @var PDO $pdo */

requireRole('Senior Keeper');

// Generate CSRF token.
if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

// Display account status messages.
$accountMessage = $_SESSION['account_message'] ?? '';
unset($_SESSION['account_message']);

$search = trim($_GET['search'] ?? '');

$sql = "
    SELECT user_id, staff_code, full_name, email, role, status, created_at
    FROM users
";

$params = [];

if ($search !== '') {
    $sql .= "
        WHERE staff_code LIKE ?
        OR full_name LIKE ?
        OR email LIKE ?
        OR role LIKE ?
        OR status LIKE ?
    ";

    $keyword = "%$search%";

    $params = [
        $keyword,
        $keyword,
        $keyword,
        $keyword,
        $keyword
    ];
}

$sql .= " ORDER BY user_id ASC";

$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$keepers = $stmt->fetchAll();

?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Keeper Accounts | PKMS</title>
    <link rel="stylesheet" href="/penguin_keeper_management/assets/css/style.css">
</head>

<body>

<div class="app-layout">

    <?php include __DIR__ . '/../../includes/sidebar.php'; ?>

    <main class="main-content">

        <?php
        $breadcrumbs = ['Keeper Accounts'];
        include __DIR__ . '/../../includes/topbar.php';
        ?>

        <div class="page-content">

            <?php if ($accountMessage !== ''): ?>
                <div class="success-message">
                    <?= htmlspecialchars($accountMessage) ?>
                </div>
            <?php endif; ?>

            <div class="page-header">
                <div>
                    <h1>Keeper Account Management</h1>
                    <p>Manage registered Senior Keeper and Junior Keeper accounts.</p>
                </div>

                <div class="search-actions">
                    <a href="create.php" class="btn-primary">
                        Register New Keeper
                    </a>
                </div>
            </div>

            <section class="search-panel">

                <form method="GET" action="index.php" class="search-form">

                    <div class="form-group">
                        <label for="search">Search Keeper Accounts</label>
                        <input
                            type="search"
                            id="search"
                            name="search"
                            placeholder="Staff ID, name, email or role"
                            value="<?= htmlspecialchars($search) ?>"
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
                    <h2>Registered Keeper Accounts</h2>
                    <span><?= count($keepers) ?> result(s)</span>
                </div>

                <?php if (empty($keepers)): ?>

                    <p class="empty-message">
                        No keeper accounts found.
                    </p>

                <?php else: ?>

                    <div class="table-responsive">

                        <table class="data-table">

                            <thead>
                                <tr>
                                    <th>Staff ID</th>
                                    <th>Full Name</th>
                                    <th>Email</th>
                                    <th>Role</th>
                                    <th>Status</th>
                                    <th>Date Registered</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>

                            <tbody>

                                <?php foreach ($keepers as $keeper): ?>

                                    <tr>

                                        <td>
                                            <?= htmlspecialchars($keeper['staff_code']) ?>
                                        </td>

                                        <td>
                                            <?= htmlspecialchars($keeper['full_name']) ?>
                                        </td>

                                        <td>
                                            <?= htmlspecialchars($keeper['email'] ?? '-') ?>
                                        </td>

                                        <td>
                                            <?= htmlspecialchars($keeper['role']) ?>
                                        </td>

                                        <td>
                                            <span class="status-badge">
                                                <?= htmlspecialchars($keeper['status']) ?>
                                            </span>
                                        </td>

                                        <td>
                                            <?= htmlspecialchars($keeper['created_at']) ?>
                                        </td>

                                        <td>

                                            <?php if ((int)$keeper['user_id'] === (int)$_SESSION['user_id']): ?>

                                                <span class="status-badge">
                                                    Current Account
                                                </span>

                                            <?php elseif ($keeper['status'] === 'Active'): ?>

                                                <form
                                                    method="POST"
                                                    action="toggle_status.php"
                                                    style="display: inline;"
                                                    onsubmit="return confirm('Are you sure you want to deactivate this account?');"
                                                >
                                                    <input
                                                        type="hidden"
                                                        name="csrf_token"
                                                        value="<?= htmlspecialchars($_SESSION['csrf_token']) ?>"
                                                    >

                                                    <input
                                                        type="hidden"
                                                        name="user_id"
                                                        value="<?= (int)$keeper['user_id'] ?>"
                                                    >

                                                    <input
                                                        type="hidden"
                                                        name="action"
                                                        value="deactivate"
                                                    >

                                                    <button type="submit" class="btn-danger">
                                                        Deactivate
                                                    </button>
                                                </form>

                                            <?php else: ?>

                                                <form
                                                    method="POST"
                                                    action="toggle_status.php"
                                                    style="display: inline;"
                                                    onsubmit="return confirm('Are you sure you want to activate this account?');"
                                                >
                                                    <input
                                                        type="hidden"
                                                        name="csrf_token"
                                                        value="<?= htmlspecialchars($_SESSION['csrf_token']) ?>"
                                                    >

                                                    <input
                                                        type="hidden"
                                                        name="user_id"
                                                        value="<?= (int)$keeper['user_id'] ?>"
                                                    >

                                                    <input
                                                        type="hidden"
                                                        name="action"
                                                        value="activate"
                                                    >

                                                    <button type="submit" class="btn-primary">
                                                        Activate
                                                    </button>
                                                </form>

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

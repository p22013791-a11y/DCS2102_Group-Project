<?php
require_once __DIR__ . '/../../includes/auth.php';
requireLogin();

require_once __DIR__ . '/../../config/db.php';

$pageTitle = 'Notifications';
$userId = (int) $_SESSION['user_id'];

if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

$search = trim($_GET['search'] ?? '');

$sql = "
    SELECT
        notification_id,
        notification_type,
        title,
        message,
        related_type,
        related_id,
        created_at,
        read_at
    FROM notifications
    WHERE user_id = ?
";

$params = [$userId];

if ($search !== '') {
    $sql .= " AND (
        title LIKE ?
        OR message LIKE ?
        OR notification_type LIKE ?
    )";

    $term = '%' . $search . '%';
    $params[] = $term;
    $params[] = $term;
    $params[] = $term;
}

$sql .= " ORDER BY
    (read_at IS NULL) DESC,
    created_at DESC,
    notification_id DESC
";

$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$notifications = $stmt->fetchAll();

$countStmt = $pdo->prepare("
    SELECT
        COUNT(*) AS total_count,
        SUM(read_at IS NULL) AS unread_count
    FROM notifications
    WHERE user_id = ?
");
$countStmt->execute([$userId]);
$counts = $countStmt->fetch();

$totalCount = (int) ($counts['total_count'] ?? 0);
$unreadCount = (int) ($counts['unread_count'] ?? 0);

$successMessage = $_SESSION['notification_success'] ?? '';
$errorMessage = $_SESSION['notification_error'] ?? '';

unset(
    $_SESSION['notification_success'],
    $_SESSION['notification_error']
);
?>

<div class="app-layout">
    <?php include __DIR__ . '/../../includes/sidebar.php'; ?>

    <main class="main-content">
        <?php
        $breadcrumb = 'Notifications';
        include __DIR__ . '/../../includes/topbar.php';
        ?>

        <div class="page-content">
            <div class="page-header">
                <div>
                    <h1>Notifications</h1>
                    <p>View your system notifications and updates.</p>
                </div>
            </div>

            <?php if ($successMessage): ?>
                <div class="alert alert-success">
                    <?= htmlspecialchars($successMessage) ?>
                </div>
            <?php endif; ?>

            <?php if ($errorMessage): ?>
                <div class="alert alert-danger">
                    <?= htmlspecialchars($errorMessage) ?>
                </div>
            <?php endif; ?>

            <div class="stats-grid">
                <div class="stat-card">
                    <h3>Total Notifications</h3>
                    <p><?= $totalCount ?></p>
                </div>

                <div class="stat-card">
                    <h3>Unread Notifications</h3>
                    <p><?= $unreadCount ?></p>
                </div>
            </div>

            <form method="GET" class="filter-form">
                <input
                    type="text"
                    name="search"
                    placeholder="Search notifications..."
                    value="<?= htmlspecialchars($search) ?>"
                >

                <button type="submit">Search</button>

                <a href="index.php">Reset</a>
            </form>

            <div class="table-container">
                <table>
                    <thead>
                        <tr>
                            <th>Status</th>
                            <th>Type</th>
                            <th>Title</th>
                            <th>Message</th>
                            <th>Date</th>
                            <th>Related Record</th>
                            <th>Action</th>
                        </tr>
                    </thead>

                    <tbody>
                    <?php if (empty($notifications)): ?>
                        <tr>
                            <td colspan="7">No notifications found.</td>
                        </tr>
                    <?php else: ?>
                        <?php foreach ($notifications as $notification): ?>
                            <tr>
                                <td>
                                    <?php if ($notification['read_at'] === null): ?>
                                        <strong>Unread</strong>
                                    <?php else: ?>
                                        Read
                                    <?php endif; ?>
                                </td>

                                <td>
                                    <?= htmlspecialchars($notification['notification_type']) ?>
                                </td>

                                <td>
                                    <?= htmlspecialchars($notification['title']) ?>
                                </td>

                                <td>
                                    <?= nl2br(htmlspecialchars($notification['message'])) ?>
                                </td>

                                <td>
                                    <?= htmlspecialchars($notification['created_at']) ?>
                                </td>

                                <td>
                                    <?php if (
                                        $notification['related_type'] !== null
                                        && $notification['related_id'] !== null
                                    ): ?>
                                        <?= htmlspecialchars($notification['related_type']) ?>
                                        #<?= (int) $notification['related_id'] ?>
                                    <?php else: ?>
                                        -
                                    <?php endif; ?>
                                </td>

                                <td>
                                    <?php if ($notification['read_at'] === null): ?>
                                        <form
                                            method="POST"
                                            action="mark_read.php"
                                            onsubmit="return confirm('Mark this notification as read?');"
                                        >
                                            <input
                                                type="hidden"
                                                name="notification_id"
                                                value="<?= (int) $notification['notification_id'] ?>"
                                            >

                                            <input
                                                type="hidden"
                                                name="csrf_token"
                                                value="<?= htmlspecialchars($_SESSION['csrf_token']) ?>"
                                            >

                                            <button type="submit">Mark as Read</button>
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
        </div>
    </main>
</div>

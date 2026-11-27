<?php
require_once __DIR__ . '/../../includes/auth.php';
requireLogin();

$keeperId = (int) $_SESSION['user_id'];

// Initialise CSRF token.
if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

$view = $_GET['view'] ?? 'inbox';
if (!in_array($view, ['inbox', 'sent'], true)) {
    $view = 'inbox';
}

$search = trim($_GET['search'] ?? '');
$showArchived = ($_GET['show_archived'] ?? '') === '1';

$messages = [];
$pageError = false;

// Select the correct archive column for the current mailbox.
$archiveColumn = ($view === 'inbox')
    ? 'm.recipient_archived_at'
    : 'm.sender_archived_at';

try {
    if ($view === 'inbox') {
        $sql = "
            SELECT
                m.message_id,
                m.subject,
                m.message_body,
                m.sent_at,
                m.read_at,
                m.recipient_archived_at,
                u.full_name AS contact_name,
                u.staff_code AS contact_code
            FROM mailbox_messages m
            INNER JOIN users u ON m.sender_id = u.user_id
            WHERE m.recipient_id = ?
              AND m.status = 'Active'
        ";
    } else {
        $sql = "
            SELECT
                m.message_id,
                m.subject,
                m.message_body,
                m.sent_at,
                m.read_at,
                m.sender_archived_at,
                u.full_name AS contact_name,
                u.staff_code AS contact_code
            FROM mailbox_messages m
            INNER JOIN users u ON m.recipient_id = u.user_id
            WHERE m.sender_id = ?
              AND m.status = 'Active'
        ";
    }

    $params = [$keeperId];

    // Show either normal messages or archived messages.
    if ($showArchived) {
        $sql .= " AND $archiveColumn IS NOT NULL";
    } else {
        $sql .= " AND $archiveColumn IS NULL";
    }

    if ($search !== '') {
        $sql .= "
            AND (
                m.subject LIKE ?
                OR m.message_body LIKE ?
                OR u.full_name LIKE ?
                OR u.staff_code LIKE ?
            )
        ";

        $keyword = '%' . $search . '%';
        array_push($params, $keyword, $keyword, $keyword, $keyword);
    }

    $sql .= " ORDER BY m.sent_at DESC, m.message_id DESC";

    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    $messages = $stmt->fetchAll();

} catch (PDOException $e) {
    error_log('Mailbox listing error: ' . $e->getMessage());
    $pageError = true;
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Mailbox | PKMS</title>
    <link rel="stylesheet" href="/penguin_keeper_management/assets/css/style.css">
</head>
<body>

<div class="app-layout">

    <?php include __DIR__ . '/../../includes/sidebar.php'; ?>

    <main class="main-content">

        <?php
        $breadcrumbs = ['Mailbox'];
        include __DIR__ . '/../../includes/topbar.php';
        ?>

        <div class="page-content">

            <?php if (!empty($_SESSION['mailbox_success'])): ?>
                <div class="success-message">
                    <?= htmlspecialchars($_SESSION['mailbox_success']) ?>
                </div>
                <?php unset($_SESSION['mailbox_success']); ?>
            <?php endif; ?>

            <?php if (!empty($_SESSION['mailbox_error'])): ?>
                <div class="error-message">
                    <?= htmlspecialchars($_SESSION['mailbox_error']) ?>
                </div>
                <?php unset($_SESSION['mailbox_error']); ?>
            <?php endif; ?>

            <div class="page-header">
                <div>
                    <h1>Mailbox</h1>
                    <p>View and manage your messages with other keepers.</p>
                </div>

                <div class="search-actions mailbox-navigation">
                    <?php if (!$showArchived): ?>
                        <a href="compose.php" class="btn-primary">
                            Compose Message
                        </a>
                    <?php endif; ?>
                </div>
            </div>

            <?php if ($pageError): ?>

                <div class="error-message">
                    Unable to load mailbox messages. Please try again.
                </div>

            <?php else: ?>

                <section class="search-panel">

                    <div class="search-actions mailbox-navigation">

                        <a
                            href="index.php?view=inbox"
                            class="<?= $view === 'inbox' && !$showArchived ? 'btn-primary' : 'btn-secondary' ?>"
                        >
                            Inbox
                        </a>

                        <a
                            href="index.php?view=sent"
                            class="<?= $view === 'sent' && !$showArchived ? 'btn-primary' : 'btn-secondary' ?>"
                        >
                            Sent
                        </a>

                        <a
                            href="index.php?view=<?= htmlspecialchars($view) ?>&show_archived=1"
                            class="<?= $showArchived ? 'btn-primary' : 'btn-secondary' ?>"
                        >
                            View Archived
                        </a>

                        <?php if ($showArchived): ?>
                            <a
                                href="index.php?view=<?= htmlspecialchars($view) ?>"
                                class="btn-secondary"
                            >
                                Back to Current Messages
                            </a>
                        <?php endif; ?>

                    </div>

                    <form method="GET" action="index.php" class="search-form">

                        <input
                            type="hidden"
                            name="view"
                            value="<?= htmlspecialchars($view) ?>"
                        >

                        <?php if ($showArchived): ?>
                            <input type="hidden" name="show_archived" value="1">
                        <?php endif; ?>

                        <div class="form-group">
                            <label for="search">Search Messages</label>
                            <input
                                type="text"
                                id="search"
                                name="search"
                                placeholder="Search subject, message or keeper"
                                value="<?= htmlspecialchars($search) ?>"
                            >
                        </div>

                        <div class="search-actions mailbox-navigation">
                            <button type="submit" class="btn-primary">
                                Search
                            </button>

                            <a
                                href="index.php?view=<?= htmlspecialchars($view) ?><?= $showArchived ? '&show_archived=1' : '' ?>"
                                class="btn-secondary"
                            >
                                Reset
                            </a>
                        </div>

                    </form>

                </section>

                <section class="table-section">

                    <div class="table-header">
                        <h2>
                            <?php if ($showArchived): ?>
                                Archived <?= $view === 'inbox' ? 'Inbox' : 'Sent' ?> Messages
                            <?php else: ?>
                                <?= $view === 'inbox' ? 'Inbox Messages' : 'Sent Messages' ?>
                            <?php endif; ?>
                        </h2>

                        <span>
                            <?= count($messages) ?> result(s)
                        </span>
                    </div>

                    <div class="table-responsive">
                        <table class="data-table">
                            <thead>
                                <tr>
                                    <th>
                                        <?= $view === 'inbox' ? 'From' : 'To' ?>
                                    </th>
                                    <th>Subject</th>
                                    <th>Message Preview</th>
                                    <th>Date Sent</th>

                                    <?php if ($view === 'inbox'): ?>
                                        <th>Status</th>
                                    <?php endif; ?>

                                    <th>Action</th>
                                </tr>
                            </thead>

                            <tbody>

                                <?php if (empty($messages)): ?>

                                    <tr>
                                        <td colspan="<?= $view === 'inbox' ? '6' : '5' ?>">
                                            No messages found.
                                        </td>
                                    </tr>

                                <?php else: ?>

                                    <?php foreach ($messages as $message): ?>

                                        <tr>
                                            <td>
                                                <?= htmlspecialchars($message['contact_name']) ?>
                                                <br>
                                                <small>
                                                    <?= htmlspecialchars($message['contact_code']) ?>
                                                </small>
                                            </td>

                                            <td>
                                                <?= htmlspecialchars($message['subject']) ?>
                                            </td>

                                            <td>
                                                <?= htmlspecialchars(
                                                    mb_strimwidth(
                                                        $message['message_body'],
                                                        0,
                                                        80,
                                                        '...'
                                                    )
                                                ) ?>
                                            </td>

                                            <td>
                                                <?= htmlspecialchars(
                                                    date(
                                                        'd M Y, h:i A',
                                                        strtotime($message['sent_at'])
                                                    )
                                                ) ?>
                                            </td>

                                            <?php if ($view === 'inbox'): ?>
                                                <td>
                                                    <?php if ($message['read_at'] === null): ?>
                                                        <strong>Unread</strong>
                                                    <?php else: ?>
                                                        Read
                                                    <?php endif; ?>
                                                </td>
                                            <?php endif; ?>

                                            <td>
                                                <a
                                                    href="view.php?id=<?= (int) $message['message_id'] ?>"
                                                    class="btn-secondary"
                                                >
                                                    View
                                                </a>

                                                <?php if (!$showArchived): ?>
                                                    <form
                                                        method="POST"
                                                        action="archive.php"
                                                        style="display:inline;"
                                                        onsubmit="return confirm('Archive this message?');"
                                                    >
                                                        <input
                                                            type="hidden"
                                                            name="message_id"
                                                            value="<?= (int) $message['message_id'] ?>"
                                                        >

                                                        <input
                                                            type="hidden"
                                                            name="csrf_token"
                                                            value="<?= htmlspecialchars($_SESSION['csrf_token']) ?>"
                                                        >

                                                        <button
                                                            type="submit"
                                                            class="btn-secondary"
                                                        >
                                                            Archive
                                                        </button>
                                                    </form>
                                                <?php endif; ?>
                                            </td>
                                        </tr>

                                    <?php endforeach; ?>

                                <?php endif; ?>

                            </tbody>
                        </table>
                    </div>

                </section>

            <?php endif; ?>

        </div>

    </main>

</div>

</body>
</html>

<?php
require_once __DIR__ . '/../../includes/auth.php';
requireLogin();

date_default_timezone_set('Asia/Kuala_Lumpur');

$currentUserId = (int) $_SESSION['user_id'];

$messageId = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT);

if (!$messageId || $messageId <= 0) {
    header('Location: index.php?view=inbox');
    exit;
}

try {
    // Retrieve the message only if the current keeper is its sender or recipient.
    $stmt = $pdo->prepare("
        SELECT
            m.message_id,
            m.sender_id,
            m.recipient_id,
            m.subject,
            m.message_body,
            m.sent_at,
            m.read_at,
            m.status,
            sender.full_name AS sender_name,
            sender.staff_code AS sender_code,
            recipient.full_name AS recipient_name,
            recipient.staff_code AS recipient_code
        FROM mailbox_messages m
        INNER JOIN users sender
            ON m.sender_id = sender.user_id
        INNER JOIN users recipient
            ON m.recipient_id = recipient.user_id
        WHERE m.message_id = ?
          AND m.status = 'Active'
          AND (m.sender_id = ? OR m.recipient_id = ?)
        LIMIT 1
    ");

    $stmt->execute([$messageId, $currentUserId, $currentUserId]);
    $message = $stmt->fetch();

    if (!$message) {
        $_SESSION['mailbox_error'] = 'Message not found or you do not have permission to view it.';
        header('Location: index.php?view=inbox');
        exit;
    }

    // Mark the message as read only when opened by its recipient.
    if (
        (int) $message['recipient_id'] === $currentUserId &&
        $message['read_at'] === null
    ) {
        $updateStmt = $pdo->prepare("
            UPDATE mailbox_messages
            SET read_at = NOW()
            WHERE message_id = ?
              AND recipient_id = ?
              AND read_at IS NULL
        ");

        $updateStmt->execute([$messageId, $currentUserId]);

        // Refresh the displayed read timestamp.
        $message['read_at'] = date('Y-m-d H:i:s');
    }

} catch (PDOException $e) {
    error_log('Mailbox view error: ' . $e->getMessage());
    $_SESSION['mailbox_error'] = 'Unable to load this message. Please try again.';
    header('Location: index.php?view=inbox');
    exit;
}

$isRecipient = (int) $message['recipient_id'] === $currentUserId;

function escapeMailboxView(string $value): string
{
    return htmlspecialchars($value, ENT_QUOTES, 'UTF-8');
}

$pageTitle = 'View Message';
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= escapeMailboxView($pageTitle) ?> | PKMS</title>
    <link rel="stylesheet" href="../../assets/css/style.css">
<style>
    .message-detail-card {
        background: #ffffff;
        border: 1px solid #dce6e2;
        border-radius: 12px;
        overflow: hidden;
        margin-bottom: 22px;
    }

    .message-detail-header {
        padding: 22px 25px;
        background: #f4f8f6;
        border-bottom: 1px solid #dce6e2;
    }

    .message-detail-header h2 {
        margin: 0;
        color: #173b45;
        font-size: 1.2rem;
        overflow-wrap: anywhere;
    }

    .message-detail-header p {
        margin: 8px 0 0;
        color: #687b7b;
        font-size: 0.85rem;
    }

    .message-meta-grid {
        display: grid;
        grid-template-columns: repeat(2, minmax(0, 1fr));
        gap: 20px;
        padding: 23px 25px;
    }

    .message-meta-item {
        min-width: 0;
    }

    .message-meta-item label {
        display: block;
        margin-bottom: 6px;
        color: #71817f;
        font-size: 0.78rem;
        font-weight: 700;
        text-transform: uppercase;
        letter-spacing: 0.04em;
    }

    .message-meta-item p {
        margin: 0;
        color: #263f46;
        font-size: 0.9rem;
        overflow-wrap: anywhere;
    }

    .message-body-panel {
        margin: 0 25px 25px;
        padding: 22px;
        border: 1px solid #e0e9e5;
        border-radius: 9px;
        background: #fbfdfc;
        color: #263f46;
        line-height: 1.8;
        font-size: 0.92rem;
        white-space: pre-wrap;
        overflow-wrap: anywhere;
        min-height: 160px;
    }

    .message-status {
        display: inline-block;
        padding: 5px 11px;
        border-radius: 20px;
        background: #e5f3ed;
        color: #246747;
        font-size: 0.8rem;
        font-weight: 700;
    }

    @media (max-width: 600px) {
        .message-meta-grid {
            grid-template-columns: 1fr;
            gap: 16px;
            padding: 18px;
        }

        .message-detail-header {
            padding: 18px;
        }

        .message-body-panel {
            margin: 0 18px 18px;
            padding: 17px;
        }
    }
</style>
</head>
<body>

<div class="app-layout">

    <?php include __DIR__ . '/../../includes/sidebar.php'; ?>

    <main class="main-content">

        <?php
        $breadcrumb = 'Mailbox';
        include __DIR__ . '/../../includes/topbar.php';
        ?>

        <div class="page-content">

            <div class="page-header">
                <div>
                    <h1>View Message</h1>
                    <p>Read the message details and communication history.</p>
                </div>

                <div class="search-actions">
                    <a
                        href="index.php?view=<?= $isRecipient ? 'inbox' : 'sent' ?>"
                        class="btn-secondary"
                    >
                        Back to <?= $isRecipient ? 'Inbox' : 'Sent Messages' ?>
                    </a>
                </div>
            </div>

            <section class="message-detail-card">

                <div class="message-detail-header">
                    <h2><?= escapeMailboxView($message['subject']) ?></h2>

                    <p>
                        Sent on
                        <?= escapeMailboxView(
                            date(
                                'd M Y, h:i A',
                                strtotime($message['sent_at'])
                            )
                        ) ?>
                    </p>
                </div>

                <div class="message-meta-grid">

                    <div class="message-meta-item">
                        <label>From</label>
                        <p>
                            <?= escapeMailboxView($message['sender_name']) ?>
                            (<?= escapeMailboxView($message['sender_code']) ?>)
                        </p>
                    </div>

                    <div class="message-meta-item">
                        <label>To</label>
                        <p>
                            <?= escapeMailboxView($message['recipient_name']) ?>
                            (<?= escapeMailboxView($message['recipient_code']) ?>)
                        </p>
                    </div>

                    <div class="message-meta-item">
                        <label>Date Sent</label>
                        <p>
                            <?= escapeMailboxView(
                                date(
                                    'd M Y, h:i A',
                                    strtotime($message['sent_at'])
                                )
                            ) ?>
                        </p>
                    </div>

                    <?php if ($isRecipient): ?>
                        <div class="message-meta-item">
                            <label>Read Status</label>
                            <p>
                                <?php if ($message['read_at'] !== null): ?>
                                    <span class="message-status">
                                        Read
                                    </span>

                                    <br>
                                    <small>
                                        <?= escapeMailboxView(
                                            date(
                                                'd M Y, h:i A',
                                                strtotime($message['read_at'])
                                            )
                                        ) ?>
                                    </small>
                                <?php else: ?>
                                    <span class="message-status">
                                        Unread
                                    </span>
                                <?php endif; ?>
                            </p>
                        </div>
                    <?php endif; ?>

                </div>

                <div class="message-body-panel"><?= escapeMailboxView($message['message_body']) ?></div>

            </section>

        </div>

    </main>

</div>

</body>
</html>

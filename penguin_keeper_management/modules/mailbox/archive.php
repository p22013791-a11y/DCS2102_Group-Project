<?php
require_once __DIR__ . '/../../includes/auth.php';
requireLogin();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: index.php?view=inbox');
    exit;
}

$currentUserId = (int) $_SESSION['user_id'];

if (
    empty($_SESSION['csrf_token']) ||
    !isset($_POST['csrf_token']) ||
    !hash_equals($_SESSION['csrf_token'], $_POST['csrf_token'])
) {
    $_SESSION['mailbox_error'] = 'Invalid request. Please refresh the page.';
    header('Location: index.php?view=inbox');
    exit;
}

$messageId = filter_input(INPUT_POST, 'message_id', FILTER_VALIDATE_INT);

if (!$messageId || $messageId <= 0) {
    $_SESSION['mailbox_error'] = 'Invalid message.';
    header('Location: index.php?view=inbox');
    exit;
}

try {
    $pdo->beginTransaction();

    $stmt = $pdo->prepare("
        SELECT *
        FROM mailbox_messages
        WHERE message_id = ?
          AND status = 'Active'
          AND (sender_id = ? OR recipient_id = ?)
        FOR UPDATE
    ");

    $stmt->execute([$messageId, $currentUserId, $currentUserId]);
    $message = $stmt->fetch();

    if (!$message) {
        throw new RuntimeException('Message not found or access denied.');
    }

    if ((int) $message['recipient_id'] === $currentUserId) {
        $archiveColumn = 'recipient_archived_at';
        $mailboxView = 'inbox';
    } elseif ((int) $message['sender_id'] === $currentUserId) {
        $archiveColumn = 'sender_archived_at';
        $mailboxView = 'sent';
    } else {
        throw new RuntimeException('You cannot archive this message.');
    }

    if ($message[$archiveColumn] !== null) {
        throw new RuntimeException('This message is already archived.');
    }

    // Archive only the current keeper's copy.
    $update = $pdo->prepare("
        UPDATE mailbox_messages
        SET $archiveColumn = NOW()
        WHERE message_id = ?
    ");

    $update->execute([$messageId]);

    $oldValues = [
        $archiveColumn => null
    ];

    $newValues = [
        $archiveColumn => date('Y-m-d H:i:s')
    ];

    $audit = $pdo->prepare("
        INSERT INTO audit_logs (
            user_id,
            action,
            entity_type,
            entity_id,
            old_values,
            new_values,
            description,
            created_at
        ) VALUES (?, ?, ?, ?, ?, ?, ?, NOW())
    ");

    $audit->execute([
        $currentUserId,
        'ARCHIVE',
        'mailbox_messages',
        $messageId,
        json_encode($oldValues),
        json_encode($newValues),
        'Keeper archived their own mailbox copy.'
    ]);

    $pdo->commit();

    $_SESSION['mailbox_success'] = 'Message archived successfully.';
    header('Location: index.php?view=' . $mailboxView);
    exit;

} catch (Throwable $e) {
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }

    error_log('Mailbox archive error: ' . $e->getMessage());

    $_SESSION['mailbox_error'] = $e instanceof RuntimeException
        ? $e->getMessage()
        : 'Unable to archive the message. Please try again.';

    header('Location: index.php?view=inbox');
    exit;
}

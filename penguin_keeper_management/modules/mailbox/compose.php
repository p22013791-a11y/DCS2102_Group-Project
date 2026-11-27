<?php
require_once __DIR__ . '/../../includes/auth.php';
requireLogin();

date_default_timezone_set('Asia/Kuala_Lumpur');

if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

$senderId = (int) $_SESSION['user_id'];

$recipients = [];
$errors = [];

$recipientId = '';
$subject = '';
$messageBody = '';

try {
    $stmt = $pdo->prepare("
        SELECT user_id, staff_code, full_name, role
        FROM users
        WHERE status = 'Active'
          AND user_id != ?
        ORDER BY full_name ASC
    ");
    $stmt->execute([$senderId]);
    $recipients = $stmt->fetchAll();

} catch (PDOException $e) {
    error_log('Mailbox recipient loading error: ' . $e->getMessage());
    $errors[] = 'Unable to load recipients. Please try again.';
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    if (
        empty($_SESSION['csrf_token']) ||
        !isset($_POST['csrf_token']) ||
        !hash_equals($_SESSION['csrf_token'], $_POST['csrf_token'])
    ) {
        $errors[] = 'Invalid request. Please refresh the page.';
    }

    $recipientId = trim($_POST['recipient_id'] ?? '');
    $subject = trim($_POST['subject'] ?? '');
    $messageBody = trim($_POST['message_body'] ?? '');

    if (
        $recipientId === '' ||
        filter_var($recipientId, FILTER_VALIDATE_INT) === false ||
        (int) $recipientId < 1
    ) {
        $errors[] = 'Please select a valid recipient.';
    } elseif ((int) $recipientId === $senderId) {
        $errors[] = 'You cannot send a message to yourself.';
    }

    if ($subject === '' || mb_strlen($subject) > 150) {
        $errors[] = 'Please enter a subject (maximum 150 characters).';
    }

    if ($messageBody === '' || mb_strlen($messageBody) > 10000) {
        $errors[] = 'Please enter a message (maximum 10,000 characters).';
    }

    if (empty($errors)) {
        try {
            // Verify the recipient is still an active keeper.
            $checkRecipient = $pdo->prepare("
                SELECT user_id
                FROM users
                WHERE user_id = ?
                  AND status = 'Active'
                  AND user_id != ?
                LIMIT 1
            ");

            $checkRecipient->execute([
                (int) $recipientId,
                $senderId
            ]);

            if (!$checkRecipient->fetch()) {
                $errors[] = 'The selected recipient is unavailable.';
            } else {

                $pdo->beginTransaction();

                // Insert the message. sent_at uses the database default.
                $insert = $pdo->prepare("
                    INSERT INTO mailbox_messages (
                        sender_id,
                        recipient_id,
                        subject,
                        message_body
                    )
                    VALUES (?, ?, ?, ?)
                ");

                $insert->execute([
                    $senderId,
                    (int) $recipientId,
                    $subject,
                    $messageBody
                ]);

                $messageId = (int) $pdo->lastInsertId();

                // Record the sending action in the audit log.
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
                    )
                    VALUES (?, ?, ?, ?, ?, ?, ?, NOW())
                ");

                $audit->execute([
                    $senderId,
                    'SEND',
                    'mailbox_messages',
                    $messageId,
                    null,
                    json_encode([
                        'recipient_id' => (int) $recipientId,
                        'subject' => $subject
                    ]),
                    'Keeper sent a mailbox message.'
                ]);

                $pdo->commit();

                $_SESSION['mailbox_success'] =
                    'Message sent successfully.';

                header('Location: index.php?view=sent');
                exit;
            }

        } catch (Throwable $e) {

            if ($pdo->inTransaction()) {
                $pdo->rollBack();
            }

            error_log('Mailbox send error: ' . $e->getMessage());

            $errors[] = 'Unable to send the message. Please try again.';
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Compose Message | PKMS</title>
    <link rel="stylesheet" href="/penguin_keeper_management/assets/css/style.css">
</head>
<body>

<div class="app-layout">

    <?php include __DIR__ . '/../../includes/sidebar.php'; ?>

    <main class="main-content">

        <?php
        $breadcrumbs = ['Mailbox', 'Compose Message'];
        include __DIR__ . '/../../includes/topbar.php';
        ?>

        <div class="page-content">

            <div class="page-header">
                <div>
                    <h1>Compose Message</h1>
                    <p>Send an internal message to another active keeper.</p>
                </div>

            </div>

            <?php if (!empty($errors)): ?>
                <div class="error-message">
                    <?php foreach ($errors as $error): ?>
                        <p><?= htmlspecialchars($error) ?></p>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>

            <section class="form-section">

                <form method="POST" action="compose.php">

                    <input
                        type="hidden"
                        name="csrf_token"
                        value="<?= htmlspecialchars($_SESSION['csrf_token']) ?>"
                    >

                    <div class="form-group">
                        <label for="recipient_id">Recipient *</label>

                        <select
                            id="recipient_id"
                            name="recipient_id"
                            required
                        >
                            <option value="">Select a keeper</option>

                            <?php foreach ($recipients as $recipient): ?>
                                <option
                                    value="<?= (int) $recipient['user_id'] ?>"
                                    <?= (string) $recipientId === (string) $recipient['user_id'] ? 'selected' : '' ?>
                                >
                                    <?= htmlspecialchars(
                                        $recipient['full_name'] .
                                        ' (' . $recipient['staff_code'] .
                                        ') - ' . $recipient['role']
                                    ) ?>
                                </option>
                            <?php endforeach; ?>

                        </select>
                    </div>

                    <div class="form-group">
                        <label for="subject">Subject *</label>

                        <input
                            type="text"
                            id="subject"
                            name="subject"
                            maxlength="150"
                            value="<?= htmlspecialchars($subject) ?>"
                            required
                        >
                    </div>

                    <div class="form-group">
                        <label for="message_body">Message *</label>

                        <textarea
                            id="message_body"
                            name="message_body"
                            rows="8"
                            maxlength="10000"
                            required
                        ><?= htmlspecialchars($messageBody) ?></textarea>
                    </div>

                    <div class="form-actions">
                        <button type="submit" class="btn-primary">
                            Send Message
                        </button>

                        <a href="index.php" class="btn-secondary">
                            Cancel
                        </a>
                    </div>

                </form>

            </section>

        </div>

    </main>

</div>

</body>
</html>

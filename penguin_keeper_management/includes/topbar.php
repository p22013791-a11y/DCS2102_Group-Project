<?php
// Use the breadcrumb supplied by the current page.
$breadcrumbs = $breadcrumbs ?? ['Dashboard'];
?>

<header class="top-header">

    <div class="breadcrumb">
        <a href="/penguin_keeper_management/dashboard.php">PKMS</a>

        <?php foreach ($breadcrumbs as $crumb): ?>
            <span class="breadcrumb-separator">&gt;</span>
            <span class="breadcrumb-current">
                <?= htmlspecialchars($crumb) ?>
            </span>
        <?php endforeach; ?>
    </div>

    <div class="top-header-right">

        <div class="topbar-datetime">
            <span id="pkms-date">Loading date...</span>
            <span id="pkms-time">Loading time...</span>
        </div>

        <form
            action="/penguin_keeper_management/logout.php"
            method="POST"
            class="topbar-logout-form"
        >
            <input
                type="hidden"
                name="csrf_token"
                value="<?= htmlspecialchars($_SESSION['csrf_token'] ?? '') ?>"
            >
            <button
                type="submit"
                class="topbar-mailbox topbar-logout"
                aria-label="Log out of PKMS"
                title="Log out"
            >
                <span class="mailbox-icon" aria-hidden="true">&#10162;</span>
                <span>Log out</span>
            </button>
        </form>

        <div class="topbar-user">
            <strong>
                <?= htmlspecialchars($_SESSION['full_name'] ?? '') ?>
            </strong>

            <span>
                <?= htmlspecialchars($_SESSION['staff_code'] ?? '') ?>
            </span>

            <span>
                <?= htmlspecialchars($_SESSION['role'] ?? '') ?>
            </span>
        </div>

    </div>

</header>

<script
    src="/penguin_keeper_management/assets/js/topbar.js"
    defer
></script>

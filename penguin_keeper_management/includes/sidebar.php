<?php
// Determine the active navigation section from the current request path.
// An explicitly supplied $currentPage takes precedence, except for the
// old default value 'dashboard' when the request is inside a module.
$requestPath = parse_url($_SERVER['REQUEST_URI'] ?? '', PHP_URL_PATH) ?: '';
$pathParts = array_values(array_filter(explode('/', trim($requestPath, '/'))));

$detectedPage = 'dashboard';
$modulePosition = array_search('modules', $pathParts, true);

if ($modulePosition !== false && isset($pathParts[$modulePosition + 1])) {
    $detectedPage = $pathParts[$modulePosition + 1];
} elseif (!empty($pathParts)) {
    $currentFile = basename($requestPath);
    if ($currentFile === 'dashboard.php' || $currentFile === 'index.php') {
        $detectedPage = 'dashboard';
    }
}

if (!isset($currentPage) || ($currentPage === 'dashboard' && $detectedPage !== 'dashboard')) {
    $currentPage = $detectedPage;
}

// Retrieve the logged-in user's role.
$userRole = $_SESSION['role'] ?? '';

// Shared navigation sections.
$commonNavigation = [
    [
        'label' => 'Dashboard',
        'url' => '/penguin_keeper_management/dashboard.php',
        'page' => 'dashboard'
    ],
    [
        'label' => 'Penguin',
        'url' => '/penguin_keeper_management/modules/penguins/index.php',
        'page' => 'penguins'
    ],
    [
        'label' => 'Enclosure',
        'url' => '/penguin_keeper_management/modules/enclosures/index.php',
        'page' => 'enclosures'
    ],
    [
        'label' => 'Health Alerts',
        'url' => '/penguin_keeper_management/modules/health_alerts/index.php',
        'page' => 'health_alerts'
    ],
    [
        'label' => 'Feeding Records',
        'url' => '/penguin_keeper_management/modules/feeding/index.php',
        'page' => 'feeding'
    ],
    [
        'label' => 'Weight Records',
        'url' => '/penguin_keeper_management/modules/weight/index.php',
        'page' => 'weight'
    ],
    [
        'label' => 'Observations',
        'url' => '/penguin_keeper_management/modules/observations/index.php',
        'page' => 'observations'
    ],
    [
        'label' => 'Enrichment',
        'url' => '/penguin_keeper_management/modules/enrichment/index.php',
        'page' => 'enrichment'
    ],
    [
        'label' => 'Environment',
        'url' => '/penguin_keeper_management/modules/environment/index.php',
        'page' => 'environment'
    ],
    [
        'label' => 'Mailbox',
        'url' => '/penguin_keeper_management/modules/mailbox/index.php',
        'page' => 'mailbox'
    ]
];

// Senior Keeper-only navigation.
$seniorNavigation = [
    [
        'label' => 'Reports',
        'url' => '/penguin_keeper_management/modules/reports/index.php',
        'page' => 'reports'
    ],
    [
        'label' => 'Keeper Accounts',
        'url' => '/penguin_keeper_management/modules/keeper_accounts/index.php',
        'page' => 'keeper_accounts'
    ]
];

// Build navigation according to role.
$navigation = $commonNavigation;

if ($userRole === 'Senior Keeper') {
    // Insert Senior-only sections .
    array_splice($navigation, 1, 0, $seniorNavigation);
}
?>

<aside class="sidebar">

    <div class="sidebar-brand">
        <div class="brand-icon">🐧</div>
        <div>
            <h2>PKMS</h2>
            <span>Penguin Keeper System</span>
        </div>
    </div>

    <div class="sidebar-divider"></div>

    <nav class="sidebar-navigation">

        <p class="sidebar-heading">MAIN MENU</p>

        <?php foreach ($navigation as $item): ?>
            <a
                href="<?= htmlspecialchars($item['url']) ?>"
                class="sidebar-link <?= ($currentPage === $item['page']) ? 'active' : '' ?>"
                <?= ($currentPage === $item['page']) ? 'aria-current="page"' : '' ?>
            >
                <span><?= htmlspecialchars($item['label']) ?></span>
            </a>
        <?php endforeach; ?>

    </nav>

    <div class="sidebar-footer">
        <span>Penguin Keeper Management System</span>
        <small>PKMS</small>
    </div>

</aside>

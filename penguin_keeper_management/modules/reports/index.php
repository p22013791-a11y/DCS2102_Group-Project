<?php
require_once __DIR__ . '/../../includes/auth.php';
requireRole('Senior Keeper');

date_default_timezone_set('Asia/Kuala_Lumpur');

$dateFrom = trim($_GET['date_from'] ?? '');
$today = new DateTimeImmutable(
    'now',
    new DateTimeZone('Asia/Kuala_Lumpur')
);

$dateTo = isset($_GET['date_to'])
    ? $_GET['date_to']
    : $today->format('Y-m-d');

$errors = [];
$pageError = false;
$reportRows = [];
$activePenguins = 0;
$activeEnclosures = 0;

function validReportDate(string $date): bool
{
    $parsed = DateTime::createFromFormat('!Y-m-d', $date);

    return $parsed !== false
        && $parsed->format('Y-m-d') === $date;
}

if ($dateFrom !== '' && !validReportDate($dateFrom)) {
    $errors[] = 'Please enter a valid start date.';
}

if ($dateTo !== '' && !validReportDate($dateTo)) {
    $errors[] = 'Please enter a valid end date.';
}

if (
    $dateFrom !== ''
    && $dateTo !== ''
    && validReportDate($dateFrom)
    && validReportDate($dateTo)
    && $dateFrom > $dateTo
) {
    $errors[] = 'The start date cannot be later than the end date.';
}

if (empty($errors)) {
    try {
        // Current active master-data totals.
        $stmt = $pdo->query("
            SELECT COUNT(*)
            FROM penguins
            WHERE archived_at IS NULL
              AND status IN ('Active', 'Quarantine')
        ");
        $activePenguins = (int) $stmt->fetchColumn();

        $stmt = $pdo->query("
            SELECT COUNT(*)
            FROM enclosures
            WHERE archived_at IS NULL
              AND status = 'Active'
        ");
        $activeEnclosures = (int) $stmt->fetchColumn();

        // Operational record totals.
        $reportDefinitions = [
            [
                'label' => 'Feeding Records',
                'table' => 'feeding_records',
                'date_column' => 'feeding_date'
            ],
            [
                'label' => 'Husbandry Observations',
                'table' => 'husbandry_observations',
                'date_column' => 'observation_date'
            ],
            [
                'label' => 'Weight Records',
                'table' => 'weight_records',
                'date_column' => 'measurement_date'
            ],
            [
                'label' => 'Health Alerts',
                'table' => 'health_alerts',
                'date_column' => 'report_date'
            ],
            [
                'label' => 'Environmental Readings',
                'table' => 'environmental_readings',
                'date_column' => 'reading_date'
            ],
            [
                'label' => 'Enrichment Records',
                'table' => 'enrichment_records',
                'date_column' => 'activity_date'
            ]
        ];

        foreach ($reportDefinitions as $definition) {
            $sql = "
                SELECT COUNT(*)
                FROM {$definition['table']}
                WHERE 1 = 1
            ";

            $params = [];

            if ($dateFrom !== '') {
                $sql .= " AND {$definition['date_column']} >= ?";
                $params[] = $dateFrom;
            }

            if ($dateTo !== '') {
                $sql .= " AND {$definition['date_column']} <= ?";
                $params[] = $dateTo;
            }

            $stmt = $pdo->prepare($sql);
            $stmt->execute($params);

            $reportRows[] = [
                'label' => $definition['label'],
                'total' => (int) $stmt->fetchColumn()
            ];
        }

    } catch (PDOException $e) {
        error_log('Reports dashboard error: ' . $e->getMessage());
        $pageError = true;
    }
}

$pageTitle = 'Reports';
$breadcrumbs = ['Reports'];
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= htmlspecialchars($pageTitle) ?> | PKMS</title>

    <link
        rel="stylesheet"
        href="/penguin_keeper_management/assets/css/style.css"
    >
</head>
<body>

<div class="app-layout">

    <?php include __DIR__ . '/../../includes/sidebar.php'; ?>

    <main class="main-content">

        <?php include __DIR__ . '/../../includes/topbar.php'; ?>

        <div class="page-content">

            <div class="page-header">
                <div>
                    <h1>Reports</h1>
                    <p>
                        Review operational record totals and
                        current penguin and enclosure figures.
                    </p>
                </div>
            </div>

            <?php if (!empty($errors)): ?>
                <div class="error-message">
                    <?php foreach ($errors as $error): ?>
                        <p><?= htmlspecialchars($error) ?></p>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>

            <?php if ($pageError): ?>
                <div class="error-message">
                    Unable to load report information.
                    Please try again.
                </div>
            <?php endif; ?>

            <section class="table-section">
                <div class="table-header">
                    <h2>Current System Overview</h2>
                </div>

                <?php if (!$pageError): ?>
                    <div class="table-responsive">
                        <table class="data-table">
                            <tbody>
                                <tr>
                                    <th>Active / Quarantined Penguins</th>
                                    <td><?= $activePenguins ?></td>
                                </tr>
                                <tr>
                                    <th>Active Enclosures</th>
                                    <td><?= $activeEnclosures ?></td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                <?php endif; ?>
            </section>

            <section class="search-panel">
                <h2>Operational Record Summary</h2>

                <form
                    method="GET"
                    action="index.php"
                    class="search-form"
                >
                    <div class="form-group">
                        <label for="date_from">Date From</label>
                        <input
                            type="date"
                            id="date_from"
                            name="date_from"
                            value="<?= htmlspecialchars($dateFrom) ?>"
                        >
                    </div>

                    <div class="form-group">
                        <label for="date_to">Date To</label>
                        <input
                            type="date"
                            id="date_to"
                            name="date_to"
                            value="<?= htmlspecialchars($dateTo) ?>"
                        >
                    </div>

                    <div class="search-actions">
                        <button type="submit" class="btn-primary">
                            Generate Summary
                        </button>

                        <a href="index.php" class="btn-secondary">
                            Reset
                        </a>
                    </div>
                </form>
            </section>

            <section class="table-section">
                <div class="table-header">
                    <h2>Record Totals</h2>
                </div>

                <?php if ($pageError): ?>

                    <p class="error-message">
                        Report totals could not be displayed.
                    </p>

                <?php elseif (!empty($errors)): ?>

                    <p class="empty-message">
                        Correct the date filters to generate the summary.
                    </p>

                <?php else: ?>

                    <div class="table-responsive">
                        <table class="data-table">
                            <thead>
                                <tr>
                                    <th>Record Category</th>
                                    <th>Total Records</th>
                                </tr>
                            </thead>

                            <tbody>
                                <?php foreach ($reportRows as $row): ?>
                                    <tr>
                                        <td>
                                            <?= htmlspecialchars($row['label']) ?>
                                        </td>
                                        <td><?= $row['total'] ?></td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>

                <?php endif; ?>
            </section>

            <section class="table-section">
                <div class="table-header">
                    <h2>Detailed Reports</h2>
                </div>

                <div class="dashboard-grid">

                    <div class="dashboard-card">
                        <h3>Feeding Records</h3>
                        <p>Review feeding activities, planned quantities and consumption.</p>
                        <a href="feeding.php" class="btn-primary">View Report</a>
                    </div>

                    <div class="dashboard-card">
                        <h3>Weight Records</h3>
                        <p>Review historical penguin weight measurements.</p>
                        <a href="weight.php" class="btn-primary">View Report</a>
                    </div>

                    <div class="dashboard-card">
                        <h3>Health Alerts</h3>
                        <p>Review reported concerns and follow-up status.</p>
                        <a href="health_alerts.php" class="btn-primary">View Report</a>
                    </div>

                    <div class="dashboard-card">
                        <h3>Husbandry Observations</h3>
                        <p>Review penguin observations and keeper submissions.</p>
                        <a href="observations.php" class="btn-primary">View Report</a>
                    </div>

                    <div class="dashboard-card">
                        <h3>Enrichment Records</h3>
                        <p>Review enrichment activities and penguin responses.</p>
                        <a href="enrichment.php" class="btn-primary">View Report</a>
                    </div>

                    <div class="dashboard-card">
                        <h3>Environmental Readings</h3>
                        <p>Review recorded environmental measurements.</p>
                        <a href="environment.php" class="btn-primary">View Report</a>
                    </div>

                    <div class="dashboard-card">
                        <h3>Audit Logs</h3>
                        <p>Review keeper actions and system activity history.</p>
                        <a href="audit_logs.php" class="btn-primary">View Report</a>
                    </div>

                </div>
            </section>

        </div>
    </main>
</div>

</body>
</html>

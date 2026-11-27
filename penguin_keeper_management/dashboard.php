
<?php

require_once __DIR__ . '/includes/auth.php';
requireLogin();

date_default_timezone_set('Asia/Kuala_Lumpur');

$fullName = $_SESSION['full_name'] ?? 'Keeper';
$staffCode = $_SESSION['staff_code'] ?? '';
$role = $_SESSION['role'] ?? '';
$isSenior = ($role === 'Senior Keeper');

$currentPage = 'dashboard';
$today = date('Y-m-d');
$todayLabel = date('l, d F Y');

// --------------------------------------------------
// Helper functions
// --------------------------------------------------

function dashboardEscape(mixed $value): string
{
    return htmlspecialchars((string)($value ?? ''), ENT_QUOTES, 'UTF-8');
}

function dashboardPercent(mixed $completed, mixed $expected): int|float
{
    if ($expected <= 0) {
        return 0;
    }

    return min(100, round(($completed / $expected) * 100));
}

function dashboardMeasurement(mixed $value, string $suffix = ''): string
{
    if ($value === null || $value === '') {
        return '—';
    }

    return number_format((float)$value, 1) . $suffix;
}

// --------------------------------------------------
// Initialise dashboard data
// --------------------------------------------------

$dashboardError = false;

$penguinStats = [
    'Active' => 0,
    'Quarantine' => 0,
    'Deceased' => 0
];

$penguinTotal = 0;
$eligiblePenguins = 0;
$enclosures = [];

$feeding = [
    'records' => 0,
    'completed_slots' => 0,
    'expected' => 0,
    'percentage' => 0
];

$observations = [
    'records' => 0,
    'completed_slots' => 0,
    'expected' => 0,
    'percentage' => 0
];

$pendingAlerts = [];
$pendingAlertCount = 0;
$activeKeeperCount = 0;
$observationsAwaitingReview = 0;
$dailyExpected = 0;

// --------------------------------------------------
// Retrieve dashboard information
// --------------------------------------------------

try {

    // 1. Penguin statistics

    $stmt = $pdo->query("
        SELECT
            status,
            COUNT(*) AS total
        FROM penguins
        WHERE archived_at IS NULL
          AND status IN ('Active', 'Quarantine', 'Deceased')
        GROUP BY status
    ");

    while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
        $penguinStats[$row['status']] = (int)$row['total'];
    }

    $penguinTotal = array_sum($penguinStats);

    // 2. Eligible penguins for daily feeding and observations

    $eligiblePenguins = (int)$pdo->query("
        SELECT COUNT(*)
        FROM penguins
        WHERE archived_at IS NULL
          AND status IN ('Active', 'Quarantine')
    ")->fetchColumn();

    $dailyExpected = $eligiblePenguins * 3;

    // 3. Today's feeding records

    $stmt = $pdo->prepare("
        SELECT
            COUNT(*) AS record_count,
            COUNT(
                DISTINCT CONCAT(
                    f.penguin_id,
                    '-',
                    TIME(f.feeding_time)
                )
            ) AS completed_slots
        FROM feeding_records f
        INNER JOIN penguins p
            ON p.penguin_id = f.penguin_id
        WHERE f.feeding_date = ?
          AND p.archived_at IS NULL
          AND p.status IN ('Active', 'Quarantine')
    ");

    $stmt->execute([$today]);
    $feedingResult = $stmt->fetch(PDO::FETCH_ASSOC);

    $feeding['records'] = (int)$feedingResult['record_count'];
    $feeding['completed_slots'] = (int)$feedingResult['completed_slots'];
    $feeding['expected'] = $dailyExpected;
    $feeding['percentage'] = dashboardPercent(
        $feeding['completed_slots'],
        $feeding['expected']
    );

    // 4. Today's observation records

    $stmt = $pdo->prepare("
        SELECT
            COUNT(*) AS record_count,
            COUNT(
                DISTINCT CONCAT(
                    o.penguin_id,
                    '-',
                    TIME(o.observation_time)
                )
            ) AS completed_slots
        FROM husbandry_observations o
        INNER JOIN penguins p
            ON p.penguin_id = o.penguin_id
        WHERE o.observation_date = ?
          AND p.archived_at IS NULL
          AND p.status IN ('Active', 'Quarantine')
    ");

    $stmt->execute([$today]);
    $observationResult = $stmt->fetch(PDO::FETCH_ASSOC);

    $observations['records'] = (int)$observationResult['record_count'];
    $observations['completed_slots'] = (int)$observationResult['completed_slots'];
    $observations['expected'] = $dailyExpected;
    $observations['percentage'] = dashboardPercent(
        $observations['completed_slots'],
        $observations['expected']
    );

    // 5. Active enclosures and latest environmental readings

    $stmt = $pdo->query("
        SELECT
            e.enclosure_id,
            e.enclosure_code,
            e.enclosure_name,
            e.capacity,
            e.status,

            er.reading_date,
            er.reading_time,
            er.pool_temperature,
            er.ph,
            er.salinity,
            er.chlorine,

            (
                SELECT COUNT(*)
                FROM penguins p
                WHERE p.enclosure_id = e.enclosure_id
                  AND p.archived_at IS NULL
                  AND p.status IN ('Active', 'Quarantine')
            ) AS penguin_count

        FROM enclosures e

        LEFT JOIN environmental_readings er
            ON er.reading_id = (
                SELECT er2.reading_id
                FROM environmental_readings er2
                WHERE er2.enclosure_id = e.enclosure_id
                ORDER BY
                    er2.reading_date DESC,
                    er2.reading_time DESC,
                    er2.reading_id DESC
                LIMIT 1
            )

        WHERE e.status = 'Active'
          AND e.archived_at IS NULL

        ORDER BY e.enclosure_code
    ");

    $enclosures = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // 6. Pending health alerts

    $stmt = $pdo->query("
        SELECT
            h.alert_id,
            h.report_date,
            h.report_time,
            h.priority,
            h.observation,

            p.penguin_id,
            p.animal_code,
            p.name AS penguin_name,

            e.enclosure_code,
            e.enclosure_name,

            u.full_name AS reporter_name,
            u.staff_code AS reporter_code

        FROM health_alerts h

        INNER JOIN penguins p
            ON p.penguin_id = h.penguin_id

        LEFT JOIN enclosures e
            ON e.enclosure_id = p.enclosure_id

        LEFT JOIN users u
            ON u.user_id = h.reported_by

        WHERE h.status = 'Pending'

        ORDER BY
            CASE h.priority
                WHEN 'Urgent' THEN 1
                WHEN 'Attention' THEN 2
                ELSE 3
            END,
            h.report_date DESC,
            h.report_time DESC,
            h.alert_id DESC
    ");

    $pendingAlerts = $stmt->fetchAll(PDO::FETCH_ASSOC);
    $pendingAlertCount = count($pendingAlerts);

    // 7. Active keeper count

    $activeKeeperCount = (int)$pdo->query("
        SELECT COUNT(*)
        FROM users
        WHERE status = 'Active'
    ")->fetchColumn();

    // 8. Observations awaiting review

    $observationsAwaitingReview = (int)$pdo->query("
        SELECT COUNT(*)
        FROM husbandry_observations
        WHERE status = 'Submitted'
    ")->fetchColumn();

} catch (PDOException $e) {

    error_log('Dashboard retrieval error: ' . $e->getMessage());
    $dashboardError = true;

}

// --------------------------------------------------
// Penguin distribution chart calculations
// --------------------------------------------------

$activePercent = dashboardPercent(
    $penguinStats['Active'],
    max(1, $penguinTotal)
);

$quarantinePercent = dashboardPercent(
    $penguinStats['Quarantine'],
    max(1, $penguinTotal)
);

$deceasedPercent = dashboardPercent(
    $penguinStats['Deceased'],
    max(1, $penguinTotal)
);

$activeDegree = ($activePercent / 100) * 360;
$quarantineDegree = $activeDegree + ($quarantinePercent / 100) * 360;

$penguinChartStyle = sprintf(
    'background: conic-gradient(#168b83 0deg %.2fdeg, #e7a94b %.2fdeg %.2fdeg, #c66d76 %.2fdeg 360deg);',
    $activeDegree,
    $activeDegree,
    $quarantineDegree,
    $quarantineDegree
);

?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>PKMS Dashboard</title>

    <link rel="stylesheet" href="assets/css/style.css">

    <style>
        /* =========================================
           DASHBOARD DESIGN
        ========================================= */

        .dashboard-page {
            max-width: 1500px;
            margin: 0 auto;
            padding-bottom: 35px;
            color: #243746;
        }

        .dashboard-page * {
            box-sizing: border-box;
        }

        .dashboard-heading {
            display: flex;
            justify-content: space-between;
            align-items: flex-start;
            gap: 20px;
            margin-bottom: 25px;
        }

        .dashboard-heading h2 {
            margin: 0 0 7px;
            color: #173b53;
            font-size: 27px;
        }

        .dashboard-heading p {
            margin: 0;
            color: #718096;
            font-size: 14px;
        }

        .dashboard-date {
            background: #ffffff;
            border: 1px solid #e2e8f0;
            border-radius: 10px;
            padding: 11px 16px;
            color: #425466;
            font-size: 13px;
            white-space: nowrap;
        }

        .dashboard-section {
            margin-top: 25px;
        }

        .section-heading {
            display: flex;
            justify-content: space-between;
            align-items: center;
            gap: 15px;
            margin-bottom: 15px;
        }

        .section-heading h3 {
            margin: 0;
            color: #173b53;
            font-size: 18px;
        }

        .section-description {
            color: #8492a6;
            font-size: 12px;
            margin-top: 4px;
        }

        .dashboard-card {
            background: #ffffff;
            border: 1px solid #e8edf2;
            border-radius: 13px;
            padding: 20px;
            box-shadow: 0 3px 12px rgba(23, 59, 83, 0.045);
            min-width: 0;
        }

        /* =========================================
           FIVE KPI CARDS
        ========================================= */

        .dashboard-kpi-grid {
            display: grid;
            grid-template-columns: repeat(5, minmax(0, 1fr));
            gap: 13px;
        }

        .kpi-card {
            position: relative;
            overflow: hidden;
            min-height: 135px;
            padding: 17px;
        }

        .kpi-card::after {
            content: "";
            position: absolute;
            right: -20px;
            bottom: -37px;
            width: 95px;
            height: 95px;
            border-radius: 50%;
            background: rgba(22, 139, 131, 0.055);
        }

        .kpi-label {
            display: flex;
            justify-content: space-between;
            align-items: center;
            gap: 7px;
        }

        .kpi-card h4 {
            margin: 0;
            color: #637487;
            font-size: 12px;
            font-weight: 650;
            line-height: 1.4;
        }

        .kpi-icon {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            width: 32px;
            height: 32px;
            flex: 0 0 32px;
            border-radius: 9px;
            background: #edf7f6;
            color: #168b83;
            font-size: 16px;
        }

        .kpi-card.alert-kpi .kpi-icon {
            background: #fff3ed;
            color: #d97745;
        }

        .kpi-card.review-kpi .kpi-icon {
            background: #fff7e9;
            color: #c18b2e;
        }

        .dashboard-number {
            font-size: 32px;
            line-height: 1.2;
            font-weight: 800;
            color: #173b53;
            margin: 15px 0 5px;
        }

        .dashboard-muted {
            color: #8a98a8;
            font-size: 11px;
            line-height: 1.5;
        }

        /* =========================================
           SECOND LAYER
           Penguin population + Health alerts
        ========================================= */

        .dashboard-main-grid {
            display: grid;
            grid-template-columns: minmax(0, 1fr) minmax(0, 1fr);
            gap: 17px;
            align-items: stretch;
        }

        /* Penguin donut chart */

        .penguin-chart-layout {
            display: flex;
            align-items: center;
            gap: 28px;
            min-height: 215px;
        }

        .penguin-donut {
            width: 165px;
            height: 165px;
            flex: 0 0 165px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            position: relative;
        }

        .penguin-donut::before {
            content: "";
            position: absolute;
            width: 108px;
            height: 108px;
            border-radius: 50%;
            background: #ffffff;
        }

        .donut-centre {
            z-index: 1;
            text-align: center;
        }

        .donut-centre strong {
            display: block;
            font-size: 30px;
            color: #173b53;
        }

        .donut-centre span {
            color: #8492a6;
            font-size: 11px;
        }

        .chart-legend {
            display: grid;
            gap: 17px;
            width: 100%;
        }

        .legend-item {
            display: flex;
            justify-content: space-between;
            align-items: center;
            gap: 10px;
            font-size: 13px;
        }

        .legend-label {
            display: flex;
            align-items: center;
            gap: 9px;
            color: #536477;
        }

        .legend-dot {
            width: 11px;
            height: 11px;
            border-radius: 50%;
            flex: 0 0 11px;
        }

        .legend-value {
            font-weight: 750;
            color: #263e50;
            white-space: nowrap;
        }

        /* Health alerts */

        .alert-card {
            display: flex;
            flex-direction: column;
        }

        .alert-list {
            display: grid;
            gap: 10px;
            max-height: 300px;
            overflow-y: auto;
            padding-right: 3px;
        }

        .alert-item {
            border: 1px solid #edf0f3;
            border-left: 4px solid #e3a23e;
            border-radius: 9px;
            padding: 12px 13px;
            background: #ffffff;
        }

        .alert-item.priority-urgent {
            border-left-color: #c95757;
            background: #fffafa;
        }

        .alert-item.priority-attention {
            border-left-color: #e3a23e;
        }

        .alert-item.priority-routine {
            border-left-color: #4b83bd;
        }

        .alert-top {
            display: flex;
            justify-content: space-between;
            align-items: flex-start;
            gap: 12px;
        }

        .alert-penguin {
            color: #173b53;
            font-size: 13px;
            font-weight: 750;
        }

        .alert-enclosure {
            color: #8492a6;
            font-size: 11px;
            margin-top: 4px;
        }

        .priority-label {
            font-size: 10px;
            font-weight: 750;
            text-transform: uppercase;
            letter-spacing: 0.4px;
            white-space: nowrap;
        }

        .priority-label.urgent {
            color: #bd4848;
        }

        .priority-label.attention {
            color: #bd842a;
        }

        .priority-label.routine {
            color: #4b83bd;
        }

        .alert-description {
            margin: 9px 0 6px;
            color: #536477;
            font-size: 12px;
            line-height: 1.5;
        }

        .alert-meta {
            color: #98a3af;
            font-size: 10px;
        }

        .alert-count {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            min-width: 28px;
            height: 28px;
            padding: 0 8px;
            border-radius: 20px;
            background: #fff0e9;
            color: #c46d3c;
            font-size: 13px;
            font-weight: 800;
        }

        /* =========================================
           THIRD LAYER
           FULL-WIDTH DAILY CARE COMPLETION
        ========================================= */

        .care-card {
            padding: 24px;
        }

        .care-summary {
            display: grid;
            grid-template-columns: repeat(2, minmax(0, 1fr));
            gap: 25px;
            margin-top: 8px;
        }

        .care-module {
            border: 1px solid #e9eef2;
            border-radius: 12px;
            padding: 22px;
            min-width: 0;
        }

        .care-module-header {
            display: flex;
            justify-content: space-between;
            align-items: flex-start;
            gap: 15px;
            margin-bottom: 20px;
        }

        .care-module-title {
            color: #425466;
            font-size: 14px;
            font-weight: 750;
        }

        .care-module-subtitle {
            color: #91a0ae;
            font-size: 11px;
            margin-top: 5px;
        }

        .care-percentage {
            color: #168b83;
            font-size: 34px;
            font-weight: 850;
            line-height: 1;
            white-space: nowrap;
        }

        .care-module.observation-module .care-percentage {
            color: #4b83bd;
        }

        .care-number-row {
            display: flex;
            align-items: baseline;
            gap: 8px;
            margin-bottom: 17px;
        }

        .care-number {
            color: #173b53;
            font-size: 39px;
            font-weight: 850;
            line-height: 1;
        }

        .care-total {
            color: #8291a0;
            font-size: 15px;
            font-weight: 600;
        }

        .care-progress-track {
            height: 13px;
            width: 100%;
            border-radius: 20px;
            overflow: hidden;
            background: #edf1f5;
        }

        .care-progress-fill {
            height: 100%;
            border-radius: inherit;
            transition: width 0.3s ease;
        }

        .feeding-fill {
            background: linear-gradient(90deg, #168b83, #39b3a4);
        }

        .observation-fill {
            background: linear-gradient(90deg, #4b83bd, #75a9d5);
        }

        .care-footer {
            display: flex;
            justify-content: space-between;
            gap: 12px;
            margin-top: 12px;
            color: #8795a4;
            font-size: 11px;
        }

        .care-explanation {
            margin-top: 20px;
            padding: 13px 15px;
            background: #f6f9fb;
            border-radius: 9px;
            color: #718096;
            font-size: 12px;
            line-height: 1.6;
        }

        /* =========================================
           FINAL LAYER
           INTEGRATED ENCLOSURE ENVIRONMENT
        ========================================= */

        .environment-card {
            padding: 0;
            overflow: hidden;
        }

        .environment-header {
            padding: 20px 22px;
            border-bottom: 1px solid #e9eef2;
            display: flex;
            justify-content: space-between;
            align-items: center;
            gap: 15px;
        }

        .environment-header h3 {
            margin: 0;
            color: #173b53;
            font-size: 18px;
        }

        .environment-header p {
            margin: 5px 0 0;
            color: #8492a6;
            font-size: 12px;
        }

        .environment-total {
            color: #168b83;
            background: #edf7f6;
            border-radius: 20px;
            padding: 7px 12px;
            font-size: 11px;
            font-weight: 750;
            white-space: nowrap;
        }

        .environment-list {
            display: grid;
        }

        .environment-row {
            display: grid;
            grid-template-columns: minmax(180px, 1.2fr) minmax(110px, 0.65fr) minmax(0, 2.3fr);
            align-items: center;
            gap: 20px;
            padding: 19px 22px;
            border-bottom: 1px solid #edf1f4;
        }

        .environment-row:last-child {
            border-bottom: 0;
        }

        .environment-identity {
            min-width: 0;
        }

        .environment-code {
            color: #168b83;
            font-size: 11px;
            font-weight: 800;
            letter-spacing: 0.6px;
            margin-bottom: 5px;
        }

        .environment-name {
            color: #334e62;
            font-size: 13px;
            font-weight: 750;
            line-height: 1.45;
        }

        .environment-population {
            background: #f5f8fa;
            border-radius: 9px;
            padding: 11px 12px;
            text-align: center;
        }

        .environment-population strong {
            display: block;
            color: #173b53;
            font-size: 19px;
            font-weight: 800;
        }

        .environment-population span {
            display: block;
            color: #8795a4;
            font-size: 10px;
            margin-top: 3px;
        }

        .environment-measurements {
            display: grid;
            grid-template-columns: repeat(4, minmax(0, 1fr));
            gap: 9px;
        }

        .environment-measurement {
            min-width: 0;
            padding: 10px 9px;
            background: #fbfcfd;
            border: 1px solid #edf1f4;
            border-radius: 8px;
        }

        .environment-measurement span {
            display: block;
            color: #8794a3;
            font-size: 10px;
            margin-bottom: 6px;
        }

        .environment-measurement strong {
            color: #334e62;
            font-size: 14px;
            overflow-wrap: anywhere;
        }

        .environment-reading-date {
            grid-column: 2 / -1;
            color: #96a2ae;
            font-size: 10px;
            margin-top: -10px;
        }

        /* =========================================
           EMPTY AND ERROR STATES
        ========================================= */

        .empty-state {
            padding: 25px 15px;
            text-align: center;
            color: #8a98a8;
            font-size: 12px;
            background: #fafcfd;
            border-radius: 9px;
        }

        .dashboard-error {
            padding: 16px;
            border-radius: 9px;
            border: 1px solid #f1cccc;
            background: #fff5f5;
            color: #a33c3c;
            font-size: 13px;
        }

        /* =========================================
           RESPONSIVE LAYOUT
        ========================================= */

        @media (max-width: 1200px) {
            .dashboard-kpi-grid {
                grid-template-columns: repeat(3, minmax(0, 1fr));
            }

            .environment-row {
                grid-template-columns: minmax(150px, 1fr) minmax(100px, 0.6fr);
            }

            .environment-measurements {
                grid-column: 1 / -1;
            }

            .environment-reading-date {
                grid-column: 1 / -1;
                margin-top: -10px;
            }
        }

        @media (max-width: 850px) {
            .dashboard-main-grid {
                grid-template-columns: 1fr;
            }

            .care-summary {
                grid-template-columns: 1fr;
            }
        }

        @media (max-width: 600px) {
            .dashboard-heading {
                flex-direction: column;
            }

            .dashboard-date {
                white-space: normal;
            }

            .dashboard-kpi-grid {
                grid-template-columns: repeat(2, minmax(0, 1fr));
                gap: 10px;
            }

            .kpi-card {
                padding: 14px;
                min-height: 120px;
            }

            .dashboard-number {
                font-size: 27px;
            }

            .penguin-chart-layout {
                flex-direction: column;
                gap: 20px;
            }

            .chart-legend {
                width: 100%;
            }

            .care-card {
                padding: 15px;
            }

            .care-module {
                padding: 17px;
            }

            .care-percentage {
                font-size: 28px;
            }

            .care-number {
                font-size: 33px;
            }

            .environment-header {
                align-items: flex-start;
                flex-direction: column;
            }

            .environment-row {
                grid-template-columns: 1fr 100px;
                gap: 13px;
                padding: 16px;
            }

            .environment-measurements {
                grid-column: 1 / -1;
            }

            .environment-reading-date {
                grid-column: 1 / -1;
                margin-top: -5px;
            }
        }

        @media (max-width: 380px) {
            .dashboard-kpi-grid {
                grid-template-columns: 1fr;
            }

            .environment-measurements {
                grid-template-columns: repeat(2, minmax(0, 1fr));
            }
        }

    </style>
</head>

<body>

<div class="app-layout">

    <?php
    require_once __DIR__ . '/includes/sidebar.php';
    ?>

    <div class="main-area">

        <?php
        $breadcrumbs = ['Dashboard'];
        require_once __DIR__ . '/includes/topbar.php';
        ?>

        <main class="main-content">

            <div class="dashboard-page">

                <!-- =================================
                     PAGE HEADING
                ================================== -->

                <div class="dashboard-heading">

                    <div>
                        <h2>Dashboard Overview</h2>

                        <p>
                            Welcome back,
                            <strong><?= dashboardEscape($fullName) ?></strong>.
                            Here is the current husbandry overview.
                        </p>
                    </div>

                    <div class="dashboard-date">
                        <?= dashboardEscape($todayLabel) ?>
                    </div>

                </div>

                <?php if ($dashboardError): ?>

                    <div class="dashboard-error">
                        Dashboard information is temporarily unavailable.
                        Please try again later.
                    </div>

                <?php else: ?>

                    <!-- =================================
                         FIRST LAYER: FIVE KPI CARDS
                    ================================== -->

                    <section class="dashboard-section">

                        <div class="section-heading">
                            <div>
                                <h3>Operational Summary</h3>

                                <div class="section-description">
                                    Current population, resources and outstanding work
                                </div>
                            </div>
                        </div>

                        <div class="dashboard-kpi-grid">

                            <!-- Total Penguins -->

                            <div class="dashboard-card kpi-card">

                                <div class="kpi-label">
                                    <h4>Total Penguins</h4>
                                    <span class="kpi-icon">🐧</span>
                                </div>

                                <div class="dashboard-number">
                                    <?= $penguinTotal ?>
                                </div>

                                <div class="dashboard-muted">
                                    Active, and Quarantined
                                </div>

                            </div>

                            <!-- Active Enclosures -->

                            <div class="dashboard-card kpi-card">

                                <div class="kpi-label">
                                    <h4>Active Enclosures</h4>
                                    <span class="kpi-icon">⌂</span>
                                </div>

                                <div class="dashboard-number">
                                    <?= count($enclosures) ?>
                                </div>

                                <div class="dashboard-muted">
                                    Currently active enclosures
                                </div>

                            </div>

                            <!-- Pending Health Alerts -->

                            <div class="dashboard-card kpi-card alert-kpi">

                                <div class="kpi-label">
                                    <h4>Pending Health Alerts</h4>
                                    <span class="kpi-icon">!</span>
                                </div>

                                <div class="dashboard-number">
                                    <?= $pendingAlertCount ?>
                                </div>

                                <div class="dashboard-muted">
                                    Awaiting Senior Keeper attention
                                </div>

                            </div>

                            <!-- Active Keepers -->

                            <div class="dashboard-card kpi-card">

                                <div class="kpi-label">
                                    <h4>Active Keepers</h4>
                                    <span class="kpi-icon">♙</span>
                                </div>

                                <div class="dashboard-number">
                                    <?= $activeKeeperCount ?>
                                </div>

                                <div class="dashboard-muted">
                                    Active staff accounts
                                </div>

                            </div>

                            <!-- Observations Awaiting Review -->

                            <div class="dashboard-card kpi-card review-kpi">

                                <div class="kpi-label">
                                    <h4>Observations Awaiting Review</h4>
                                    <span class="kpi-icon">◷</span>
                                </div>

                                <div class="dashboard-number">
                                    <?= $observationsAwaitingReview ?>
                                </div>

                                <div class="dashboard-muted">
                                    Submitted observations
                                </div>

                            </div>

                        </div>

                    </section>

                    <!-- =================================
                         SECOND LAYER:
                         POPULATION + HEALTH ALERTS
                    ================================== -->

                    <section class="dashboard-section">

                        <div class="dashboard-main-grid">

                            <!-- Penguin Population -->

                            <div class="dashboard-card">

                                <div class="section-heading">

                                    <div>
                                        <h3>Penguin Population</h3>

                                        <div class="section-description">
                                            Distribution by current status
                                        </div>
                                    </div>

                                </div>

                                <div class="penguin-chart-layout">

                                    <div
                                        class="penguin-donut"
                                        style="<?= dashboardEscape($penguinChartStyle) ?>"
                                    >
                                        <div class="donut-centre">
                                            <strong><?= $penguinTotal ?></strong>
                                            <span>Total penguins</span>
                                        </div>
                                    </div>

                                    <div class="chart-legend">

                                        <div class="legend-item">

                                            <div class="legend-label">
                                                <span
                                                    class="legend-dot"
                                                    style="background:#168b83"
                                                ></span>

                                                Active
                                            </div>

                                            <span class="legend-value">
                                                <?= $penguinStats['Active'] ?>
                                                (<?= $activePercent ?>%)
                                            </span>

                                        </div>

                                        <div class="legend-item">

                                            <div class="legend-label">
                                                <span
                                                    class="legend-dot"
                                                    style="background:#e7a94b"
                                                ></span>

                                                Quarantine
                                            </div>

                                            <span class="legend-value">
                                                <?= $penguinStats['Quarantine'] ?>
                                                (<?= $quarantinePercent ?>%)
                                            </span>

                                        </div>

                                    </div>

                                </div>

                            </div>

                            <!-- Pending Health Alerts -->

                            <div class="dashboard-card alert-card">

                                <div class="section-heading">

                                    <div>
                                        <h3>Pending Health Alerts</h3>

                                        <div class="section-description">
                                            Penguins requiring Senior Keeper review
                                        </div>
                                    </div>

                                    <span class="alert-count">
                                        <?= $pendingAlertCount ?>
                                    </span>

                                </div>

                                <?php if (empty($pendingAlerts)): ?>

                                    <div class="empty-state">
                                        No pending health alerts.
                                    </div>

                                <?php else: ?>

                                    <div class="alert-list">

                                        <?php foreach ($pendingAlerts as $alert): ?>

                                            <?php
                                            $priorityClass = strtolower(
                                                $alert['priority'] ?? 'routine'
                                            );
                                            ?>

                                            <div class="alert-item priority-<?= dashboardEscape($priorityClass) ?>">

                                                <div class="alert-top">

                                                    <div>
                                                        <div class="alert-penguin">
                                                            <?= dashboardEscape($alert['animal_code']) ?>
                                                            — <?= dashboardEscape($alert['penguin_name']) ?>
                                                        </div>

                                                        <div class="alert-enclosure">
                                                            <?= dashboardEscape(
                                                                $alert['enclosure_code'] ?? 'No enclosure'
                                                            ) ?>

                                                            <?php if (!empty($alert['enclosure_name'])): ?>
                                                                · <?= dashboardEscape(
                                                                    $alert['enclosure_name']
                                                                ) ?>
                                                            <?php endif; ?>
                                                        </div>
                                                    </div>

                                                    <span class="priority-label <?= dashboardEscape($priorityClass) ?>">
                                                        <?= dashboardEscape($alert['priority']) ?>
                                                    </span>

                                                </div>

                                                <p class="alert-description">
                                                    <?= dashboardEscape($alert['observation']) ?>
                                                </p>

                                                <div class="alert-meta">

                                                    Reported:
                                                    <?= dashboardEscape($alert['report_date'] ?? '—') ?>

                                                    <?php if (!empty($alert['report_time'])): ?>
                                                        at <?= dashboardEscape(
                                                            substr($alert['report_time'], 0, 5)
                                                        ) ?>
                                                    <?php endif; ?>

                                                    <?php if (!empty($alert['reporter_code'])): ?>
                                                        · <?= dashboardEscape($alert['reporter_code']) ?>
                                                    <?php endif; ?>

                                                </div>

                                            </div>

                                        <?php endforeach; ?>

                                    </div>

                                <?php endif; ?>

                            </div>

                        </div>

                    </section>

                    <!-- =================================
                         THIRD LAYER:
                         FULL-WIDTH CARE COMPLETION
                    ================================== -->

                    <section class="dashboard-section">

                        <div class="dashboard-card care-card">

                            <div class="section-heading">

                                <div>
                                    <h3>Today's Care Completion</h3>

                                    <div class="section-description">
                                        Daily feeding and husbandry observation progress
                                    </div>
                                </div>

                            </div>

                            <div class="care-summary">

                                <!-- Feeding -->

                                <div class="care-module">

                                    <div class="care-module-header">

                                        <div>
                                            <div class="care-module-title">
                                                Feeding Records
                                            </div>

                                            <div class="care-module-subtitle">
                                                Three scheduled feeding sessions per penguin
                                            </div>
                                        </div>

                                        <div class="care-percentage">
                                            <?= $feeding['percentage'] ?>%
                                        </div>

                                    </div>

                                    <div class="care-number-row">
                                        <span class="care-number">
                                            <?= $feeding['records'] ?>
                                        </span>

                                        <span class="care-total">
                                            / <?= $feeding['expected'] ?> expected records
                                        </span>
                                    </div>

                                    <div class="care-progress-track">
                                        <div
                                            class="care-progress-fill feeding-fill"
                                            style="width:<?= $feeding['percentage'] ?>%"
                                        ></div>
                                    </div>

                                    <div class="care-footer">
                                        <span>
                                            <?= $feeding['completed_slots'] ?> completed slots
                                        </span>

                                        <span>
                                            <?= max(
                                                0,
                                                $feeding['expected'] - $feeding['completed_slots']
                                            ) ?> remaining
                                        </span>
                                    </div>

                                </div>

                                <!-- Observations -->

                                <div class="care-module observation-module">

                                    <div class="care-module-header">

                                        <div>
                                            <div class="care-module-title">
                                                Husbandry Observations
                                            </div>

                                            <div class="care-module-subtitle">
                                                Three scheduled observation sessions per penguin
                                            </div>
                                        </div>

                                        <div class="care-percentage">
                                            <?= $observations['percentage'] ?>%
                                        </div>

                                    </div>

                                    <div class="care-number-row">
                                        <span class="care-number">
                                            <?= $observations['records'] ?>
                                        </span>

                                        <span class="care-total">
                                            / <?= $observations['expected'] ?> expected records
                                        </span>
                                    </div>

                                    <div class="care-progress-track">
                                        <div
                                            class="care-progress-fill observation-fill"
                                            style="width:<?= $observations['percentage'] ?>%"
                                        ></div>
                                    </div>

                                    <div class="care-footer">
                                        <span>
                                            <?= $observations['completed_slots'] ?> completed slots
                                        </span>

                                        <span>
                                            <?= max(
                                                0,
                                                $observations['expected'] - $observations['completed_slots']
                                            ) ?> remaining
                                        </span>
                                    </div>

                                </div>

                            </div>

                            <div class="care-explanation">
                                <strong>Completion calculation:</strong>
                                <?= $eligiblePenguins ?> eligible penguins
                                (Active + Quarantine) × 3 daily sessions =
                                <strong><?= $dailyExpected ?></strong> expected records
                                for each module.
                            </div>

                        </div>

                    </section>

                    <!-- =================================
                         FINAL LAYER:
                         INTEGRATED ENVIRONMENT OVERVIEW
                    ================================== -->

                    <section class="dashboard-section">

                        <div class="dashboard-card environment-card">

                            <div class="environment-header">

                                <div>
                                    <h3>Enclosure Environment</h3>

                                    <p>
                                        Latest recorded environmental readings and enclosure population
                                    </p>
                                </div>

                                <span class="environment-total">
                                    <?= count($enclosures) ?> Active Enclosures
                                </span>

                            </div>

                            <?php if (empty($enclosures)): ?>

                                <div class="empty-state" style="margin:18px;">
                                    No active enclosures are currently available.
                                </div>

                            <?php else: ?>

                                <div class="environment-list">

                                    <?php foreach ($enclosures as $enclosure): ?>

                                        <div class="environment-row">

                                            <!-- Enclosure identity -->

                                            <div class="environment-identity">

                                                <div class="environment-code">
                                                    <?= dashboardEscape($enclosure['enclosure_code']) ?>
                                                </div>

                                                <div class="environment-name">
                                                    <?= dashboardEscape($enclosure['enclosure_name']) ?>
                                                </div>

                                            </div>

                                            <!-- Penguin population -->

                                            <div class="environment-population">

                                                <strong>
                                                    <?= (int)$enclosure['penguin_count'] ?>
                                                    / <?= (int)$enclosure['capacity'] ?>
                                                </strong>

                                                <span>Penguins / Capacity</span>

                                            </div>

                                            <!-- Environmental measurements -->

                                            <div class="environment-measurements">

                                                <div class="environment-measurement">
                                                    <span>Temperature</span>

                                                    <strong>
                                                        <?= dashboardMeasurement(
                                                            $enclosure['pool_temperature'],
                                                            '°C'
                                                        ) ?>
                                                    </strong>
                                                </div>

                                                <div class="environment-measurement">
                                                    <span>pH</span>

                                                    <strong>
                                                        <?= dashboardMeasurement($enclosure['ph']) ?>
                                                    </strong>
                                                </div>

                                                <div class="environment-measurement">
                                                    <span>Salinity</span>

                                                    <strong>
                                                        <?= dashboardMeasurement(
                                                            $enclosure['salinity'],
                                                            ' ppt'
                                                        ) ?>
                                                    </strong>
                                                </div>

                                                <div class="environment-measurement">
                                                    <span>Chlorine</span>

                                                    <strong>
                                                        <?= dashboardMeasurement(
                                                            $enclosure['chlorine'],
                                                            ' mg/L'
                                                        ) ?>
                                                    </strong>
                                                </div>

                                            </div>

                                            <!-- Reading timestamp -->

                                            <div class="environment-reading-date">

                                                <?php if (!empty($enclosure['reading_date'])): ?>

                                                    Latest reading:
                                                    <?= dashboardEscape($enclosure['reading_date']) ?>

                                                    <?php if (!empty($enclosure['reading_time'])): ?>
                                                        at <?= dashboardEscape(
                                                            substr($enclosure['reading_time'], 0, 5)
                                                        ) ?>
                                                    <?php endif; ?>

                                                <?php else: ?>

                                                    No environmental readings recorded

                                                <?php endif; ?>

                                            </div>

                                        </div>

                                    <?php endforeach; ?>

                                </div>

                            <?php endif; ?>

                        </div>

                    </section>

                <?php endif; ?>

            </div>

        </main>

    </div>

</div>

</body>
</html>

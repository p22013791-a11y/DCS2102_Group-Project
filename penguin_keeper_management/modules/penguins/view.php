<?php

require_once __DIR__ . '/../../includes/auth.php';
requireLogin();

date_default_timezone_set('Asia/Kuala_Lumpur');

$penguinId = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT);

if (!$penguinId || $penguinId < 1) {
    header('Location: index.php');
    exit;
}

$isSenior = ($_SESSION['role'] ?? '') === 'Senior Keeper';
$currentUserId = (int)($_SESSION['user_id'] ?? 0);

$today = new DateTimeImmutable('today', new DateTimeZone('Asia/Kuala_Lumpur'));

$dateFrom = trim($_GET['date_from'] ?? '');
$dateTo = trim($_GET['date_to'] ?? $today->format('Y-m-d'));

$errors = [];
$pageError = false;

$penguin = null;
$feedingRecords = [];
$weightRecords = [];
$weightChartRecords = [];
$observations = [];
$healthAlerts = [];
$enrichmentRecords = [];

function profileEscape(mixed $value): string
{
    if ($value === null || $value === '') {
        return '-';
    }

    return htmlspecialchars((string)$value, ENT_QUOTES, 'UTF-8');
}

function profileDate(?string $date): string
{
    if (!$date) {
        return 'Not recorded';
    }

    $timestamp = strtotime($date);

    return $timestamp ? date('d M Y', $timestamp) : 'Not recorded';
}

function profileTime(?string $time): string
{
    return $time ? substr($time, 0, 5) : '-';
}

function profileAge(?string $hatchDate): string
{
    if (!$hatchDate) {
        return 'Not recorded';
    }

    try {
        $birth = new DateTimeImmutable($hatchDate);
        $today = new DateTimeImmutable(
            'today',
            new DateTimeZone('Asia/Kuala_Lumpur')
        );

        if ($birth > $today) {
            return 'Invalid hatch date';
        }

        $age = $birth->diff($today);

        if ($age->y > 0) {
            return $age->y . ' year(s), ' . $age->m . ' month(s)';
        }

        return $age->m . ' month(s)';
    } catch (Throwable $e) {
        return 'Not recorded';
    }
}

function profileStatusClass(string $status): string
{
    return match (strtolower($status)) {
        'active', 'finalized', 'reviewed', 'resolved',
        'positive' => 'status-good',

        'quarantine', 'pending', 'submitted',
        'attention', 'neutral' => 'status-warning',

        'urgent', 'deceased', 'negative' => 'status-danger',

        default => 'status-neutral'
    };
}

function profileCountValues(array $records, string $field, array $allowed): array
{
    $counts = array_fill_keys($allowed, 0);

    foreach ($records as $record) {
        $value = $record[$field] ?? null;

        if ($value !== null && isset($counts[$value])) {
            $counts[$value]++;
        }
    }

    return $counts;
}

function profileBarChart(array $counts, int $total): void
{
    if ($total === 0) {
        echo '<div class="empty-state">No recorded data for this date range.</div>';
        return;
    }

    foreach ($counts as $label => $count) {
        $percentage = ($count / $total) * 100;
        ?>
        <div class="bar-item">
            <div class="bar-heading">
                <span><?= profileEscape($label) ?></span>
                <strong><?= (int)$count ?> (<?= number_format($percentage, 1) ?>%)</strong>
            </div>
            <div class="bar-track">
                <div class="bar-fill" style="width: <?= number_format($percentage, 2, '.', '') ?>%;"></div>
            </div>
        </div>
        <?php
    }
}

function profileDateIsValid(string $date): bool
{
    $parsed = DateTime::createFromFormat('!Y-m-d', $date);

    return $parsed !== false && $parsed->format('Y-m-d') === $date;
}

function profileDateFilter(string $column, string $dateFrom, string $dateTo, array &$params): string
{
    $sql = '';

    if ($dateFrom !== '') {
        $sql .= " AND {$column} >= ?";
        $params[] = $dateFrom;
    }

    if ($dateTo !== '') {
        $sql .= " AND {$column} <= ?";
        $params[] = $dateTo;
    }

    return $sql;
}

// --------------------------------------------------
// Validate the shared date range
// --------------------------------------------------

if ($dateFrom !== '' && !profileDateIsValid($dateFrom)) {
    $errors[] = 'Please enter a valid start date.';
}

if ($dateTo !== '' && !profileDateIsValid($dateTo)) {
    $errors[] = 'Please enter a valid end date.';
}

if (
    $dateFrom !== '' &&
    $dateTo !== '' &&
    $dateFrom > $dateTo
) {
    $errors[] = 'The start date cannot be later than the end date.';
}

$canAddRecords = false;

try {
    // --------------------------------------------------
    // Penguin master profile
    // --------------------------------------------------

    $stmt = $pdo->prepare("
        SELECT
            p.penguin_id,
            p.animal_code,
            p.name,
            p.species,
            p.sex,
            p.hatch_date,
            p.origin_type,
            p.arrival_date,
            p.status,
            p.notes,
            p.archived_at,
            p.archived_by,
            e.enclosure_code,
            e.enclosure_name
        FROM penguins p
        LEFT JOIN enclosures e
            ON p.enclosure_id = e.enclosure_id
        WHERE p.penguin_id = ?
        LIMIT 1
    ");

    $stmt->execute([$penguinId]);
    $penguin = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$penguin) {
        http_response_code(404);
        exit('Penguin profile not found.');
    }

    if ($penguin['archived_at'] !== null && !$isSenior) {
        http_response_code(403);
        exit('You are not authorised to view this archived profile.');
    }

    $canAddRecords = (
        $penguin['archived_at'] === null &&
        in_array($penguin['status'], ['Active', 'Quarantine'], true)
    );

    // --------------------------------------------------
    // Load records using the same date range
    // --------------------------------------------------

    if (empty($errors)) {

        // Feeding
        $params = [$penguinId];

        $sql = "
            SELECT
                f.feeding_id,
                f.feeding_date,
                f.feeding_time,
                f.food_type,
                f.planned_grams,
                f.consumed_grams,
                f.notes,
                f.status,
                u.staff_code,
                u.full_name AS keeper_name
            FROM feeding_records f
            INNER JOIN users u ON f.keeper_id = u.user_id
            WHERE f.penguin_id = ?
        ";

        $sql .= profileDateFilter('f.feeding_date', $dateFrom, $dateTo, $params);

        $sql .= "
            ORDER BY f.feeding_date DESC, f.feeding_time DESC, f.feeding_id DESC
        ";

        $stmt = $pdo->prepare($sql);
        $stmt->execute($params);
        $feedingRecords = $stmt->fetchAll(PDO::FETCH_ASSOC);

        // Weight history: selected date range
        $params = [$penguinId];

        $sql = "
            SELECT
                w.weight_id,
                w.measurement_date,
                w.measurement_time,
                w.weight_kg,
                w.notes,
                u.staff_code,
                u.full_name AS keeper_name
            FROM weight_records w
            INNER JOIN users u ON w.keeper_id = u.user_id
            WHERE w.penguin_id = ?
        ";

        $sql .= profileDateFilter('w.measurement_date', $dateFrom, $dateTo, $params);

        $sql .= "
            ORDER BY w.measurement_date DESC, w.measurement_time DESC, w.weight_id DESC
        ";

        $stmt = $pdo->prepare($sql);
        $stmt->execute($params);
        $weightRecords = $stmt->fetchAll(PDO::FETCH_ASSOC);

        // Weight chart: independent six-month period
        $sixMonthStart = $today
            ->modify('first day of this month')
            ->modify('-5 months')
            ->format('Y-m-d');

        $stmt = $pdo->prepare("
            SELECT
                measurement_date,
                weight_kg
            FROM weight_records
            WHERE penguin_id = ?
              AND measurement_date >= ?
              AND measurement_date <= ?
            ORDER BY measurement_date ASC, measurement_time ASC, weight_id ASC
        ");

        $stmt->execute([
            $penguinId,
            $sixMonthStart,
            $today->format('Y-m-d')
        ]);

        $weightChartRecords = $stmt->fetchAll(PDO::FETCH_ASSOC);

        // Husbandry observations
        $params = [$penguinId];

        $sql = "
            SELECT
                o.observation_id,
                o.observation_date,
                o.observation_time,
                o.appetite,
                o.activity_level,
                o.behaviour,
                o.physical_concern,
                o.notes,
                o.status,
                o.reviewed_at,
                o.review_notes,
                u.staff_code,
                u.full_name AS keeper_name,
                reviewer.full_name AS reviewer_name
            FROM husbandry_observations o
            INNER JOIN users u ON o.keeper_id = u.user_id
            LEFT JOIN users reviewer ON o.reviewed_by = reviewer.user_id
            WHERE o.penguin_id = ?
        ";

        $sql .= profileDateFilter('o.observation_date', $dateFrom, $dateTo, $params);

        $sql .= "
            ORDER BY o.observation_date DESC, o.observation_time DESC, o.observation_id DESC
        ";

        $stmt = $pdo->prepare($sql);
        $stmt->execute($params);
        $observations = $stmt->fetchAll(PDO::FETCH_ASSOC);

        // Health alerts
        $params = [$penguinId];

        $sql = "
            SELECT
                h.alert_id,
                h.report_date,
                h.report_time,
                h.priority,
                h.observation,
                h.status,
                h.review_notes,
                h.resolved_at,
                reporter.staff_code AS reporter_code,
                reporter.full_name AS reporter_name,
                reviewer.full_name AS reviewer_name
            FROM health_alerts h
            INNER JOIN users reporter ON h.reported_by = reporter.user_id
            LEFT JOIN users reviewer ON h.reviewed_by = reviewer.user_id
            WHERE h.penguin_id = ?
        ";

        $sql .= profileDateFilter('h.report_date', $dateFrom, $dateTo, $params);

        $sql .= "
            ORDER BY h.report_date DESC, h.report_time DESC, h.alert_id DESC
        ";

        $stmt = $pdo->prepare($sql);
        $stmt->execute($params);
        $healthAlerts = $stmt->fetchAll(PDO::FETCH_ASSOC);

        // Enrichment
        $params = [$penguinId];

        $sql = "
            SELECT
                er.enrichment_id,
                er.activity_date,
                er.activity_time,
                er.enrichment_type,
                er.activity_description,
                er.observed_response,
                er.notes,
                u.staff_code,
                u.full_name AS keeper_name
            FROM enrichment_records er
            INNER JOIN users u ON er.keeper_id = u.user_id
            WHERE er.penguin_id = ?
        ";

        $sql .= profileDateFilter('er.activity_date', $dateFrom, $dateTo, $params);

        $sql .= "
            ORDER BY er.activity_date DESC, er.activity_time DESC, er.enrichment_id DESC
        ";

        $stmt = $pdo->prepare($sql);
        $stmt->execute($params);
        $enrichmentRecords = $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

} catch (Throwable $e) {
    error_log('Penguin profile loading error: ' . $e->getMessage());
    $pageError = true;
}

// --------------------------------------------------
// Statistics for the selected date range
// --------------------------------------------------

$totalFeedings = count($feedingRecords);
$totalWeights = count($weightRecords);
$totalObservations = count($observations);
$totalAlerts = count($healthAlerts);
$totalEnrichment = count($enrichmentRecords);

$plannedTotal = array_sum(array_map(
    fn($r) => (float)$r['planned_grams'],
    $feedingRecords
));

$consumedTotal = array_sum(array_map(
    fn($r) => (float)$r['consumed_grams'],
    $feedingRecords
));

$finalizedFeedings = count(array_filter(
    $feedingRecords,
    fn($r) => $r['status'] === 'Finalized'
));

$draftFeedings = count(array_filter(
    $feedingRecords,
    fn($r) => $r['status'] === 'Draft'
));

$reviewedObservations = count(array_filter(
    $observations,
    fn($r) => $r['status'] === 'Reviewed'
));

$awaitingObservations = count(array_filter(
    $observations,
    fn($r) => $r['status'] === 'Submitted'
));

$pendingAlerts = count(array_filter(
    $healthAlerts,
    fn($r) => $r['status'] === 'Pending'
));

$urgentAlerts = count(array_filter(
    $healthAlerts,
    fn($r) => $r['priority'] === 'Urgent'
));

$recordedResponses = count(array_filter(
    $enrichmentRecords,
    fn($r) => !empty($r['observed_response'])
));

$missingResponses = $totalEnrichment - $recordedResponses;

$responseCounts = profileCountValues(
    $enrichmentRecords,
    'observed_response',
    ['Positive', 'Neutral', 'Negative']
);

$appetiteCounts = profileCountValues(
    $observations,
    'appetite',
    ['Good', 'Moderate', 'Poor']
);

$activityCounts = profileCountValues(
    $observations,
    'activity_level',
    ['Vibrant', 'Moderate', 'Poor']
);

$behaviourCounts = profileCountValues(
    $observations,
    'behaviour',
    ['Good', 'Moderate', 'Poor']
);

$alertPriorityCounts = profileCountValues(
    $healthAlerts,
    'priority',
    ['Routine', 'Attention', 'Urgent']
);

$latestWeight = $weightRecords[0] ?? null;
$previousWeight = $weightRecords[1] ?? null;

$weightDifference = null;

if ($latestWeight && $previousWeight) {
    $weightDifference =
        (float)$latestWeight['weight_kg'] -
        (float)$previousWeight['weight_kg'];
}

// --------------------------------------------------
// Prepare six-month weight chart
// --------------------------------------------------

$monthlyWeights = [];

for ($i = 5; $i >= 0; $i--) {
    $month = $today->modify("first day of this month")->modify("-{$i} months");

    $key = $month->format('Y-m');

    $monthlyWeights[$key] = [
        'label' => $month->format('M Y'),
        'total' => 0,
        'count' => 0,
        'average' => 0
    ];
}

foreach ($weightChartRecords as $record) {
    $key = date('Y-m', strtotime($record['measurement_date']));

    if (isset($monthlyWeights[$key])) {
        $monthlyWeights[$key]['total'] += (float)$record['weight_kg'];
        $monthlyWeights[$key]['count']++;
    }
}

foreach ($monthlyWeights as &$monthData) {
    if ($monthData['count'] > 0) {
        $monthData['average'] =
            $monthData['total'] / $monthData['count'];
    }
}
unset($monthData);

$chartMaxWeight = 0;

foreach ($monthlyWeights as $monthData) {
    $chartMaxWeight = max($chartMaxWeight, $monthData['average']);
}

$chartMaxWeight = $chartMaxWeight > 0 ? $chartMaxWeight : 1;

$breadcrumbs = [
    'Penguins',
    $penguin['animal_code'] ?? 'Profile'
];

?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>
        Penguin Profile |
        <?= profileEscape($penguin['animal_code'] ?? '') ?> |
        PKMS
    </title>

    <link
        rel="stylesheet"
        href="/penguin_keeper_management/assets/css/style.css"
    >

    <style>
        .profile-page {
            padding-bottom: 35px;
        }

        .profile-hero,
        .profile-card,
        .profile-stat-card {
            background: #fff;
            border: 1px solid #e5eaf0;
            border-radius: 11px;
            box-shadow: 0 2px 8px rgba(25,45,65,.035);
        }

        .profile-hero {
            display: flex;
            justify-content: space-between;
            align-items: flex-start;
            gap: 24px;
            padding: 25px;
            margin-bottom: 20px;
        }

        .profile-identity {
            display: flex;
            align-items: center;
            gap: 18px;
        }

        .profile-avatar {
            display: flex;
            align-items: center;
            justify-content: center;
            width: 72px;
            height: 72px;
            flex-shrink: 0;
            border-radius: 15px;
            background: #eaf2f8;
            font-size: 35px;
        }

        .profile-identity h1 {
            margin: 0 0 6px;
            color: #203447;
            font-size: 25px;
        }

        .profile-code {
            color: #647789;
            font-size: 14px;
            font-weight: 600;
        }

        .profile-subtitle {
            margin-top: 7px;
            color: #718096;
            font-size: 13px;
        }

        .profile-actions,
        .profile-record-actions {
            display: flex;
            flex-wrap: wrap;
            gap: 9px;
        }

        .profile-status {
            display: inline-block;
            padding: 5px 10px;
            border-radius: 20px;
            font-size: 11px;
            font-weight: 700;
        }

        .status-good {
            color: #216746;
            background: #e6f5ec;
        }

        .status-warning {
            color: #8b6015;
            background: #fff3d9;
        }

        .status-danger {
            color: #a33434;
            background: #fde9e9;
        }

        .status-neutral {
            color: #536475;
            background: #edf1f5;
        }

        .date-filter {
            display: flex;
            flex-wrap: wrap;
            align-items: end;
            gap: 14px;
            padding: 18px;
            margin-bottom: 20px;
            background: #fff;
            border: 1px solid #e5eaf0;
            border-radius: 10px;
        }

        .date-filter .form-group {
            min-width: 180px;
            margin: 0;
        }

        .date-filter-actions {
            display: flex;
            gap: 8px;
        }

        .filter-caption {
            width: 100%;
            color: #78899a;
            font-size: 12px;
        }

        .profile-stat-grid {
            display: grid;
            grid-template-columns: repeat(5, minmax(0, 1fr));
            gap: 13px;
            margin-bottom: 21px;
        }

        .profile-stat-card {
            padding: 17px;
        }

        .stat-label {
            color: #718096;
            font-size: 12px;
            font-weight: 600;
        }

        .stat-number {
            display: block;
            margin-top: 8px;
            color: #243b53;
            font-size: 27px;
            font-weight: 700;
        }

        .stat-note {
            display: block;
            margin-top: 5px;
            color: #8795a3;
            font-size: 11px;
        }

        .profile-tabs {
            display: flex;
            gap: 5px;
            overflow-x: auto;
            padding: 7px;
            margin-bottom: 20px;
            background: #edf2f7;
            border: 1px solid #e2e8f0;
            border-radius: 10px;
        }

        .profile-tab {
            flex: 0 0 auto;
            padding: 11px 16px;
            border: 0;
            border-radius: 7px;
            background: transparent;
            color: #536579;
            font: inherit;
            font-size: 13px;
            font-weight: 600;
            cursor: pointer;
        }

        .profile-tab.active {
            color: #24577c;
            background: #fff;
            box-shadow: 0 1px 4px rgba(20,40,60,.12);
        }

        .profile-panel {
            display: none;
        }

        .profile-panel.active {
            display: block;
        }

        .profile-card {
            padding: 21px;
            margin-bottom: 18px;
        }

        .profile-card-heading {
            display: flex;
            justify-content: space-between;
            align-items: center;
            gap: 14px;
            margin-bottom: 17px;
        }

        .profile-card-heading h2 {
            margin: 0;
            color: #263e52;
            font-size: 17px;
        }

        .profile-card-heading p {
            margin: 5px 0 0;
            color: #8190a0;
            font-size: 12px;
        }

        .profile-info-grid {
            display: grid;
            grid-template-columns: repeat(3, minmax(0, 1fr));
            gap: 13px;
        }

        .profile-info-item {
            padding: 13px;
            background: #f7f9fb;
            border: 1px solid #edf1f4;
            border-radius: 8px;
        }

        .profile-info-label {
            display: block;
            margin-bottom: 6px;
            color: #8291a0;
            font-size: 10px;
            font-weight: 700;
            text-transform: uppercase;
        }

        .profile-info-value {
            color: #344b60;
            font-size: 13px;
            font-weight: 600;
            overflow-wrap: anywhere;
        }

        .summary-grid {
            display: grid;
            grid-template-columns: repeat(3, minmax(0, 1fr));
            gap: 13px;
            margin-bottom: 18px;
        }

        .summary-box {
            padding: 15px;
            background: #f7f9fb;
            border: 1px solid #e9eef2;
            border-radius: 8px;
        }

        .summary-box h3 {
            margin: 0 0 8px;
            color: #52677a;
            font-size: 12px;
        }

        .summary-value {
            color: #263e52;
            font-size: 23px;
            font-weight: 700;
        }

        .summary-caption {
            margin-top: 5px;
            color: #8492a0;
            font-size: 11px;
        }

        .chart-grid {
            display: grid;
            grid-template-columns: repeat(2, minmax(0, 1fr));
            gap: 16px;
        }

        .chart-box {
            padding: 17px;
            background: #fbfcfd;
            border: 1px solid #e9eef2;
            border-radius: 9px;
        }

        .chart-box h3 {
            margin: 0 0 14px;
            color: #40586d;
            font-size: 13px;
        }

        .bar-item {
            margin-bottom: 13px;
        }

        .bar-heading {
            display: flex;
            justify-content: space-between;
            gap: 10px;
            margin-bottom: 6px;
            color: #65788a;
            font-size: 11px;
        }

        .bar-heading strong {
            color: #344b60;
        }

        .bar-track {
            height: 10px;
            overflow: hidden;
            background: #e9eef3;
            border-radius: 10px;
        }

        .bar-fill {
            height: 100%;
            min-width: 0;
            background: #5488a8;
            border-radius: 10px;
        }

        .metric-comparison {
            display: grid;
            grid-template-columns: repeat(2, minmax(0, 1fr));
            gap: 12px;
        }

        .metric-number {
            color: #263e52;
            font-size: 23px;
            font-weight: 700;
        }

        .metric-label {
            margin-top: 4px;
            color: #8190a0;
            font-size: 11px;
        }

        .profile-table-wrap {
            width: 100%;
            overflow-x: auto;
        }

        .profile-table {
            width: 100%;
            border-collapse: collapse;
            font-size: 12px;
        }

        .profile-table th {
            padding: 12px;
            color: #617487;
            background: #f4f7fa;
            border-bottom: 1px solid #e3eaf0;
            text-align: left;
            white-space: nowrap;
        }

        .profile-table td {
            padding: 12px;
            color: #43576a;
            border-bottom: 1px solid #edf1f4;
            vertical-align: top;
        }

        .profile-table tbody tr:hover {
            background: #fafcfd;
        }

        .profile-table small {
            display: block;
            margin-top: 4px;
            color: #8795a3;
        }

        .record-text {
            min-width: 150px;
            max-width: 320px;
            overflow-wrap: anywhere;
            line-height: 1.5;
        }

        .empty-state {
            padding: 25px 15px;
            color: #8190a0;
            text-align: center;
            background: #f8fafc;
            border: 1px dashed #dce4eb;
            border-radius: 8px;
            font-size: 12px;
        }

        .notice {
            padding: 12px 15px;
            margin-bottom: 15px;
            color: #596f82;
            background: #eef5fa;
            border: 1px solid #dceaf3;
            border-radius: 8px;
            font-size: 12px;
            line-height: 1.6;
        }

        .alert-item {
            padding: 15px;
            margin-bottom: 12px;
            border: 1px solid #e6ebf0;
            border-left: 4px solid #a7b4c0;
            border-radius: 8px;
        }

        .alert-item.priority-urgent {
            border-left-color: #c54d4d;
        }

        .alert-item.priority-attention {
            border-left-color: #d5a13b;
        }

        .alert-item.priority-routine {
            border-left-color: #6c9bb4;
        }

        .alert-top {
            display: flex;
            justify-content: space-between;
            flex-wrap: wrap;
            gap: 8px;
            margin-bottom: 10px;
        }

        .alert-description {
            color: #43576a;
            font-size: 13px;
            line-height: 1.6;
            white-space: pre-wrap;
            overflow-wrap: anywhere;
        }

        .alert-meta {
            margin-top: 9px;
            color: #8190a0;
            font-size: 11px;
        }

        .weight-month-grid {
            display: grid;
            grid-template-columns: repeat(6, minmax(0, 1fr));
            align-items: end;
            gap: 12px;
            min-height: 190px;
            padding: 18px 10px 5px;
            border-bottom: 1px solid #dce4eb;
        }

        .weight-month {
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: end;
            height: 160px;
            gap: 7px;
        }

        .weight-month-bar-area {
            display: flex;
            align-items: end;
            justify-content: center;
            width: 100%;
            height: 125px;
        }

        .weight-month-bar {
            width: min(42px, 75%);
            min-height: 2px;
            background: #5488a8;
            border-radius: 6px 6px 0 0;
        }

        .weight-month-value {
            color: #43576a;
            font-size: 10px;
            font-weight: 700;
        }

        .weight-month-label {
            color: #8190a0;
            font-size: 10px;
            text-align: center;
        }

        @media (max-width: 1050px) {
            .profile-stat-grid {
                grid-template-columns: repeat(3, minmax(0, 1fr));
            }
        }

        @media (max-width: 700px) {
            .profile-hero {
                flex-direction: column;
            }

            .profile-stat-grid {
                grid-template-columns: repeat(2, minmax(0, 1fr));
            }

            .profile-info-grid,
            .summary-grid,
            .chart-grid {
                grid-template-columns: 1fr;
            }

            .profile-card {
                padding: 15px;
            }

            .profile-card-heading {
                align-items: flex-start;
                flex-direction: column;
            }

            .weight-month-grid {
                gap: 5px;
            }
        }
    </style>
</head>

<body>

<div class="app-layout">

    <?php
    $currentPage = 'penguins';
    require_once __DIR__ . '/../../includes/sidebar.php';
    ?>

    <div class="main-area">

        <?php require_once __DIR__ . '/../../includes/topbar.php'; ?>

        <main class="main-content">
            <div class="page-content profile-page">

                <?php if ($pageError): ?>

                    <div class="error-message">
                        Unable to load this penguin's profile and records.
                        Please check the database connection and try again.
                    </div>

                <?php elseif (!empty($errors)): ?>

                    <div class="error-message">
                        <?php foreach ($errors as $error): ?>
                            <p><?= profileEscape($error) ?></p>
                        <?php endforeach; ?>
                    </div>

                    <a href="view.php?id=<?= (int)$penguinId ?>" class="btn-secondary">
                        Return to Penguin Profile
                    </a>

                <?php else: ?>

                    <!-- Profile heading -->

                    <div class="page-header">
                        <div>
                            <h1>Penguin Profile</h1>
                            <p>Individual profile and husbandry history.</p>
                        </div>

                        <div class="profile-record-actions">
                            <a href="index.php" class="btn-secondary">Back to Penguins</a>

                            <?php if ($isSenior && !$penguin['archived_at']): ?>
                                <a href="edit.php?id=<?= (int)$penguinId ?>" class="btn-secondary">
                                    Edit Profile
                                </a>
                            <?php endif; ?>
                        </div>
                    </div>

                    <!-- Penguin identity -->

                    <section class="profile-hero">

                        <div class="profile-identity">
                            <div class="profile-avatar" aria-hidden="true">🐧</div>

                            <div>
                                <h1><?= profileEscape($penguin['name']) ?></h1>

                                <div class="profile-code">
                                    <?= profileEscape($penguin['animal_code']) ?>
                                    · <?= profileEscape($penguin['species']) ?>
                                </div>

                                <div class="profile-subtitle">
                                    <?= profileEscape($penguin['sex']) ?>
                                    · <?= profileEscape($penguin['enclosure_code'] ?? null) ?>

                                    <?php if (!empty($penguin['enclosure_name'])): ?>
                                        - <?= profileEscape($penguin['enclosure_name']) ?>
                                    <?php endif; ?>
                                </div>

                                <div style="margin-top: 11px;">
                                    <span class="profile-status <?= profileStatusClass($penguin['status']) ?>">
                                        <?= profileEscape($penguin['status']) ?>
                                    </span>

                                    <?php if ($penguin['archived_at']): ?>
                                        <span class="profile-status status-neutral">Archived</span>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </div>

                        <div class="profile-actions">
                            <?php if ($canAddRecords): ?>
                                <a href="../feeding/create.php" class="btn-primary">Record Feeding</a>
                                <a href="../weight/create.php" class="btn-secondary">Add Weight</a>
                                <a href="../observations/create.php" class="btn-secondary">Record Observation</a>
                                <a href="../health_alerts/create.php" class="btn-secondary">Report Concern</a>
                                <a href="../enrichment/create.php" class="btn-secondary">Add Enrichment</a>
                            <?php endif; ?>
                        </div>

                    </section>

                    <!-- Single shared date filter -->

                    <form method="GET" action="view.php" class="date-filter">

                        <input type="hidden" name="id" value="<?= (int)$penguinId ?>">

                        <div class="form-group">
                            <label for="date_from">Date From</label>
                            <input
                                type="date"
                                id="date_from"
                                name="date_from"
                                value="<?= profileEscape($dateFrom) ?>"
                            >
                        </div>

                        <div class="form-group">
                            <label for="date_to">Date To</label>
                            <input
                                type="date"
                                id="date_to"
                                name="date_to"
                                value="<?= profileEscape($dateTo) ?>"
                            >
                        </div>

                        <div class="date-filter-actions">
                            <button type="submit" class="btn-primary">Apply Date Range</button>
                            <a href="view.php?id=<?= (int)$penguinId ?>" class="btn-secondary">Reset</a>
                        </div>

                        <div class="filter-caption">
                            The selected date range controls all record statistics, summaries and history tables.
                            The weight trend chart always displays the latest six months.
                        </div>

                    </form>

                    <!-- Top statistics -->

                    <section class="profile-stat-grid">

                        <div class="profile-stat-card">
                            <span class="stat-label">Feeding Records</span>
                            <strong class="stat-number"><?= $totalFeedings ?></strong>
                            <span class="stat-note">
                                <?= $finalizedFeedings ?> finalized · <?= $draftFeedings ?> draft
                            </span>
                        </div>

                        <div class="profile-stat-card">
                            <span class="stat-label">Weight Measurements</span>
                            <strong class="stat-number"><?= $totalWeights ?></strong>
                            <span class="stat-note">
                                <?= $latestWeight
                                    ? 'Latest: ' . number_format((float)$latestWeight['weight_kg'], 2) . ' kg'
                                    : 'No measurements in this range' ?>
                            </span>
                        </div>

                        <div class="profile-stat-card">
                            <span class="stat-label">Observations</span>
                            <strong class="stat-number"><?= $totalObservations ?></strong>
                            <span class="stat-note">
                                <?= $reviewedObservations ?> reviewed · <?= $awaitingObservations ?> awaiting review
                            </span>
                        </div>

                        <div class="profile-stat-card">
                            <span class="stat-label">Health Alerts</span>
                            <strong class="stat-number"><?= $totalAlerts ?></strong>
                            <span class="stat-note">
                                <?= $pendingAlerts ?> pending · <?= $urgentAlerts ?> urgent
                            </span>
                        </div>

                        <div class="profile-stat-card">
                            <span class="stat-label">Enrichment Activities</span>
                            <strong class="stat-number"><?= $totalEnrichment ?></strong>
                            <span class="stat-note">
                                <?= $recordedResponses ?> responses recorded
                            </span>
                        </div>

                    </section>

                    <!-- Profile navigation -->

                    <nav class="profile-tabs" aria-label="Penguin profile sections">

                        <?php
                        $tabs = [
                            'overview' => 'Overview',
                            'feeding' => 'Feeding',
                            'weight' => 'Weight',
                            'observations' => 'Observations',
                            'health' => 'Health Alerts',
                            'enrichment' => 'Enrichment'
                        ];
                        ?>

                        <?php foreach ($tabs as $key => $label): ?>
                            <button
                                type="button"
                                class="profile-tab <?= $key === 'overview' ? 'active' : '' ?>"
                                data-tab="<?= profileEscape($key) ?>"
                                aria-selected="<?= $key === 'overview' ? 'true' : 'false' ?>"
                            >
                                <?= profileEscape($label) ?>
                            </button>
                        <?php endforeach; ?>

                    </nav>

                    <!-- =====================================
                         OVERVIEW
                    ====================================== -->

                    <section class="profile-panel active" id="panel-overview">

                        <div class="profile-card">

                            <div class="profile-card-heading">
                                <div>
                                    <h2>Penguin Information</h2>
                                    <p>Master profile details</p>
                                </div>
                            </div>

                            <div class="profile-info-grid">

                                <div class="profile-info-item">
                                    <span class="profile-info-label">Animal Code</span>
                                    <span class="profile-info-value"><?= profileEscape($penguin['animal_code']) ?></span>
                                </div>

                                <div class="profile-info-item">
                                    <span class="profile-info-label">Penguin Name</span>
                                    <span class="profile-info-value"><?= profileEscape($penguin['name']) ?></span>
                                </div>

                                <div class="profile-info-item">
                                    <span class="profile-info-label">Species</span>
                                    <span class="profile-info-value"><?= profileEscape($penguin['species']) ?></span>
                                </div>

                                <div class="profile-info-item">
                                    <span class="profile-info-label">Sex</span>
                                    <span class="profile-info-value"><?= profileEscape($penguin['sex']) ?></span>
                                </div>

                                <div class="profile-info-item">
                                    <span class="profile-info-label">Hatch Date</span>
                                    <span class="profile-info-value"><?= profileDate($penguin['hatch_date']) ?></span>
                                </div>

                                <div class="profile-info-item">
                                    <span class="profile-info-label">Recorded Age</span>
                                    <span class="profile-info-value"><?= profileAge($penguin['hatch_date']) ?></span>
                                </div>

                                <div class="profile-info-item">
                                    <span class="profile-info-label">Arrival Date</span>
                                    <span class="profile-info-value"><?= profileDate($penguin['arrival_date']) ?></span>
                                </div>

                                <div class="profile-info-item">
                                    <span class="profile-info-label">Origin</span>
                                    <span class="profile-info-value"><?= profileEscape($penguin['origin_type']) ?></span>
                                </div>

                                <div class="profile-info-item">
                                    <span class="profile-info-label">Enclosure</span>
                                    <span class="profile-info-value">
                                        <?= !empty($penguin['enclosure_code'])
                                            ? profileEscape($penguin['enclosure_code'] . ' - ' . $penguin['enclosure_name'])
                                            : 'Not assigned' ?>
                                    </span>
                                </div>

                                <div class="profile-info-item">
                                    <span class="profile-info-label">Current Status</span>
                                    <span class="profile-info-value"><?= profileEscape($penguin['status']) ?></span>
                                </div>

                                <div class="profile-info-item">
                                    <span class="profile-info-label">Archive Date</span>
                                    <span class="profile-info-value"><?= profileDate($penguin['archived_at']) ?></span>
                                </div>

                            </div>

                            <?php if (!empty($penguin['notes'])): ?>
                                <div class="notice" style="margin-top:16px;">
                                    <strong>Profile Notes</strong><br>
                                    <?= nl2br(profileEscape($penguin['notes'])) ?>
                                </div>
                            <?php endif; ?>

                        </div>

                        <div class="profile-card">
                            <div class="profile-card-heading">
                                <div>
                                    <h2>Selected Period at a Glance</h2>
                                    <p>Record totals for the date range above</p>
                                </div>
                            </div>

                            <div class="summary-grid">
                                <div class="summary-box">
                                    <h3>Total Feeding Quantity Planned</h3>
                                    <div class="summary-value"><?= number_format($plannedTotal, 2) ?> g</div>
                                </div>

                                <div class="summary-box">
                                    <h3>Total Feeding Quantity Consumed</h3>
                                    <div class="summary-value"><?= number_format($consumedTotal, 2) ?> g</div>
                                </div>

                                <div class="summary-box">
                                    <h3>Enrichment Response Rate</h3>
                                    <div class="summary-value">
                                        <?= $totalEnrichment > 0
                                            ? number_format(($recordedResponses / $totalEnrichment) * 100, 1) . '%'
                                            : '0.0%' ?>
                                    </div>
                                    <div class="summary-caption">
                                        <?= $recordedResponses ?> of <?= $totalEnrichment ?> activities have a response recorded
                                    </div>
                                </div>
                            </div>

                        </div>

                    </section>

                    <!-- =====================================
                         FEEDING
                    ====================================== -->

                    <section class="profile-panel" id="panel-feeding">

                        <div class="profile-card">

                            <div class="profile-card-heading">
                                <div>
                                    <h2>Feeding Summary</h2>
                                    <p>Planned and consumed quantities within the selected date range</p>
                                </div>

                                <?php if ($canAddRecords): ?>
                                    <a href="../feeding/create.php" class="btn-primary">Record Feeding</a>
                                <?php endif; ?>
                            </div>

                            <div class="summary-grid">

                                <div class="summary-box">
                                    <h3>Total Feeding Records</h3>
                                    <div class="summary-value"><?= $totalFeedings ?></div>
                                </div>

                                <div class="summary-box">
                                    <h3>Finalized</h3>
                                    <div class="summary-value"><?= $finalizedFeedings ?></div>
                                </div>

                                <div class="summary-box">
                                    <h3>Draft</h3>
                                    <div class="summary-value"><?= $draftFeedings ?></div>
                                </div>

                            </div>

                            <div class="chart-box">
                                <h3>Planned vs Consumed Feeding Quantity</h3>

                                <div class="metric-comparison">
                                    <div>
                                        <div class="metric-number"><?= number_format($plannedTotal, 2) ?> g</div>
                                        <div class="metric-label">Total planned</div>
                                    </div>

                                    <div>
                                        <div class="metric-number"><?= number_format($consumedTotal, 2) ?> g</div>
                                        <div class="metric-label">Total consumed</div>
                                    </div>
                                </div>

                                <?php
                                $feedingMax = max($plannedTotal, $consumedTotal, 1);
                                $plannedWidth = ($plannedTotal / $feedingMax) * 100;
                                $consumedWidth = ($consumedTotal / $feedingMax) * 100;
                                ?>

                                <div class="bar-item" style="margin-top:18px;">
                                    <div class="bar-heading">
                                        <span>Planned</span>
                                        <strong><?= number_format($plannedTotal, 2) ?> g</strong>
                                    </div>
                                    <div class="bar-track">
                                        <div class="bar-fill" style="width:<?= $plannedWidth ?>%;"></div>
                                    </div>
                                </div>

                                <div class="bar-item">
                                    <div class="bar-heading">
                                        <span>Consumed</span>
                                        <strong><?= number_format($consumedTotal, 2) ?> g</strong>
                                    </div>
                                    <div class="bar-track">
                                        <div class="bar-fill" style="width:<?= $consumedWidth ?>%;"></div>
                                    </div>
                                </div>

                                <div class="summary-caption">
                                    Quantities are summed from the feeding records in the selected period.
                                </div>
                            </div>

                        </div>

                        <div class="profile-card">

                            <div class="profile-card-heading">
                                <div>
                                    <h2>Feeding History</h2>
                                    <p>Records within the selected date range</p>
                                </div>
                            </div>

                            <?php if (empty($feedingRecords)): ?>
                                <div class="empty-state">No feeding records found for this date range.</div>
                            <?php else: ?>

                                <div class="profile-table-wrap">
                                    <table class="profile-table">
                                        <thead>
                                            <tr>
                                                <th>Date</th>
                                                <th>Time</th>
                                                <th>Food Type</th>
                                                <th>Planned (g)</th>
                                                <th>Consumed (g)</th>
                                                <th>Status</th>
                                                <th>Keeper</th>
                                                <th>Notes</th>
                                            </tr>
                                        </thead>

                                        <tbody>
                                            <?php foreach ($feedingRecords as $record): ?>
                                                <tr>
                                                    <td><?= profileDate($record['feeding_date']) ?></td>
                                                    <td><?= profileTime($record['feeding_time']) ?></td>
                                                    <td><?= profileEscape($record['food_type']) ?></td>
                                                    <td><?= number_format((float)$record['planned_grams'], 2) ?></td>
                                                    <td><?= number_format((float)$record['consumed_grams'], 2) ?></td>
                                                    <td>
                                                        <span class="profile-status <?= profileStatusClass($record['status']) ?>">
                                                            <?= profileEscape($record['status']) ?>
                                                        </span>
                                                    </td>
                                                    <td>
                                                        <?= profileEscape($record['keeper_name']) ?>
                                                        <small><?= profileEscape($record['staff_code']) ?></small>
                                                    </td>
                                                    <td class="record-text"><?= nl2br(profileEscape($record['notes'])) ?></td>
                                                </tr>
                                            <?php endforeach; ?>
                                        </tbody>
                                    </table>
                                </div>

                            <?php endif; ?>

                        </div>

                    </section>

                    <!-- =====================================
                         WEIGHT
                    ====================================== -->

                    <section class="profile-panel" id="panel-weight">

                        <div class="profile-card">

                            <div class="profile-card-heading">
                                <div>
                                    <h2>Weight Summary</h2>
                                    <p>Statistics follow the selected date range</p>
                                </div>

                                <?php if ($canAddRecords): ?>
                                    <a href="../weight/create.php" class="btn-primary">Add Weight Record</a>
                                <?php endif; ?>
                            </div>

                            <div class="summary-grid">

                                <div class="summary-box">
                                    <h3>Measurements in Selected Range</h3>
                                    <div class="summary-value"><?= $totalWeights ?></div>
                                </div>

                                <div class="summary-box">
                                    <h3>Latest Weight in Selected Range</h3>
                                    <div class="summary-value">
                                        <?= $latestWeight
                                            ? number_format((float)$latestWeight['weight_kg'], 2) . ' kg'
                                            : '-' ?>
                                    </div>
                                    <div class="summary-caption">
                                        <?= $latestWeight ? profileDate($latestWeight['measurement_date']) : 'No measurement in this range' ?>
                                    </div>
                                </div>

                                <div class="summary-box">
                                    <h3>Change from Previous Measurement</h3>
                                    <div class="summary-value">
                                        <?php if ($weightDifference !== null): ?>
                                            <?= $weightDifference > 0 ? '+' : '' ?>
                                            <?= number_format($weightDifference, 2) ?> kg
                                        <?php else: ?>
                                            -
                                        <?php endif; ?>
                                    </div>
                                    <div class="summary-caption">
                                        Compared with the next earlier measurement in this selected range
                                    </div>
                                </div>

                            </div>

                        </div>

                        <div class="profile-card">

                            <div class="profile-card-heading">
                                <div>
                                    <h2>Six-Month Weight Trend</h2>
                                    <p>Monthly average weight from the latest six calendar months</p>
                                </div>
                            </div>

                            <?php if (empty($weightChartRecords)): ?>

                                <div class="empty-state">
                                    No weight measurements are available for the six-month chart period.
                                </div>

                            <?php else: ?>

                                <div class="weight-month-grid">

                                    <?php foreach ($monthlyWeights as $month): ?>

                                        <div class="weight-month">

                                            <div class="weight-month-value">
                                                <?= $month['count'] > 0
                                                    ? number_format($month['average'], 2) . ' kg'
                                                    : '-' ?>
                                            </div>

                                            <div class="weight-month-bar-area">
                                                <?php
                                                $height = $month['average'] > 0
                                                    ? max(4, ($month['average'] / $chartMaxWeight) * 115)
                                                    : 2;
                                                ?>
                                                <div
                                                    class="weight-month-bar"
                                                    style="height:<?= number_format($height, 2, '.', '') ?>px;"
                                                    title="<?= profileEscape($month['label']) ?>: <?= $month['count'] ?> measurement(s)"
                                                ></div>
                                            </div>

                                            <div class="weight-month-label">
                                                <?= profileEscape($month['label']) ?>
                                            </div>

                                        </div>

                                    <?php endforeach; ?>

                                </div>

                                <div class="summary-caption" style="margin-top:12px;">
                                    Each bar represents the average recorded weight for that calendar month.
                                    Months without measurements are shown as empty.
                                </div>

                            <?php endif; ?>

                        </div>

                        <div class="profile-card">

                            <div class="profile-card-heading">
                                <div>
                                    <h2>Weight Measurement History</h2>
                                    <p>History table follows the selected date range</p>
                                </div>
                            </div>

                            <?php if (empty($weightRecords)): ?>
                                <div class="empty-state">No weight records found for this date range.</div>
                            <?php else: ?>

                                <div class="profile-table-wrap">
                                    <table class="profile-table">
                                        <thead>
                                            <tr>
                                                <th>Date</th>
                                                <th>Time</th>
                                                <th>Weight (kg)</th>
                                                <th>Keeper</th>
                                                <th>Notes</th>
                                            </tr>
                                        </thead>

                                        <tbody>
                                            <?php foreach ($weightRecords as $record): ?>
                                                <tr>
                                                    <td><?= profileDate($record['measurement_date']) ?></td>
                                                    <td><?= profileTime($record['measurement_time']) ?></td>
                                                    <td><?= number_format((float)$record['weight_kg'], 2) ?></td>
                                                    <td>
                                                        <?= profileEscape($record['keeper_name']) ?>
                                                        <small><?= profileEscape($record['staff_code']) ?></small>
                                                    </td>
                                                    <td class="record-text"><?= nl2br(profileEscape($record['notes'])) ?></td>
                                                </tr>
                                            <?php endforeach; ?>
                                        </tbody>
                                    </table>
                                </div>

                            <?php endif; ?>

                        </div>

                    </section>

                    <!-- =====================================
                         OBSERVATIONS
                    ====================================== -->

                    <section class="profile-panel" id="panel-observations">

                        <div class="profile-card">

                            <div class="profile-card-heading">
                                <div>
                                    <h2>Husbandry Observation Summary</h2>
                                    <p>Observation records and review status within the selected period</p>
                                </div>

                                <?php if ($canAddRecords): ?>
                                    <a href="../observations/create.php" class="btn-primary">Record Observation</a>
                                <?php endif; ?>
                            </div>

                            <div class="summary-grid">

                                <div class="summary-box">
                                    <h3>Total Observations</h3>
                                    <div class="summary-value"><?= $totalObservations ?></div>
                                </div>

                                <div class="summary-box">
                                    <h3>Reviewed</h3>
                                    <div class="summary-value"><?= $reviewedObservations ?></div>
                                </div>

                                <div class="summary-box">
                                    <h3>Awaiting Review</h3>
                                    <div class="summary-value"><?= $awaitingObservations ?></div>
                                </div>

                            </div>

                            <div class="chart-grid">

                                <div class="chart-box">
                                    <h3>Appetite Distribution</h3>
                                    <?php profileBarChart($appetiteCounts, $totalObservations); ?>
                                </div>

                                <div class="chart-box">
                                    <h3>Activity Level Distribution</h3>
                                    <?php profileBarChart($activityCounts, $totalObservations); ?>
                                </div>

                                <div class="chart-box">
                                    <h3>Behaviour Distribution</h3>
                                    <?php profileBarChart($behaviourCounts, $totalObservations); ?>
                                </div>

                                <div class="chart-box">
                                    <h3>Observation Review Status</h3>
                                    <?php
                                    profileBarChart([
                                        'Reviewed' => $reviewedObservations,
                                        'Awaiting Review' => $awaitingObservations,
                                        'Draft' => $totalObservations - $reviewedObservations - $awaitingObservations
                                    ], $totalObservations);
                                    ?>
                                </div>

                            </div>

                            <div class="notice" style="margin-top:16px;">
                                Physical Concern remains a descriptive free-text field.
                                It is displayed in the history table but is not included
                                in the category distribution charts.
                            </div>

                        </div>

                        <div class="profile-card">

                            <div class="profile-card-heading">
                                <div>
                                    <h2>Observation History</h2>
                                    <p>All observation records within the selected date range</p>
                                </div>
                            </div>

                            <?php if (empty($observations)): ?>
                                <div class="empty-state">No observation records found for this date range.</div>
                            <?php else: ?>

                                <div class="profile-table-wrap">
                                    <table class="profile-table">
                                        <thead>
                                            <tr>
                                                <th>Date</th>
                                                <th>Time</th>
                                                <th>Appetite</th>
                                                <th>Activity</th>
                                                <th>Behaviour</th>
                                                <th>Physical Concern</th>
                                                <th>Status</th>
                                                <th>Keeper</th>
                                                <th>Reviewed By</th>
                                                <th>Review Notes</th>
                                            </tr>
                                        </thead>

                                        <tbody>
                                            <?php foreach ($observations as $record): ?>
                                                <tr>
                                                    <td><?= profileDate($record['observation_date']) ?></td>
                                                    <td><?= profileTime($record['observation_time']) ?></td>
                                                    <td><?= profileEscape($record['appetite']) ?></td>
                                                    <td><?= profileEscape($record['activity_level']) ?></td>
                                                    <td><?= profileEscape($record['behaviour']) ?></td>
                                                    <td class="record-text"><?= nl2br(profileEscape($record['physical_concern'])) ?></td>
                                                    <td>
                                                        <span class="profile-status <?= profileStatusClass($record['status']) ?>">
                                                            <?= profileEscape($record['status']) ?>
                                                        </span>
                                                    </td>
                                                    <td>
                                                        <?= profileEscape($record['keeper_name']) ?>
                                                        <small><?= profileEscape($record['staff_code']) ?></small>
                                                    </td>
                                                    <td><?= profileEscape($record['reviewer_name']) ?></td>
                                                    <td class="record-text"><?= nl2br(profileEscape($record['review_notes'])) ?></td>
                                                </tr>
                                            <?php endforeach; ?>
                                        </tbody>
                                    </table>
                                </div>

                            <?php endif; ?>

                        </div>

                    </section>

                    <!-- =====================================
                         HEALTH ALERTS
                    ====================================== -->

                    <section class="profile-panel" id="panel-health">

                        <div class="profile-card">

                            <div class="profile-card-heading">
                                <div>
                                    <h2>Health Alert Summary</h2>
                                    <p>Reported alerts within the selected date range</p>
                                </div>

                                <?php if ($canAddRecords): ?>
                                    <a href="../health_alerts/create.php" class="btn-primary">Report Concern</a>
                                <?php endif; ?>
                            </div>

                            <div class="summary-grid">

                                <div class="summary-box">
                                    <h3>Total Alerts</h3>
                                    <div class="summary-value"><?= $totalAlerts ?></div>
                                </div>

                                <div class="summary-box">
                                    <h3>Pending Alerts</h3>
                                    <div class="summary-value"><?= $pendingAlerts ?></div>
                                </div>

                                <div class="summary-box">
                                    <h3>Urgent Priority</h3>
                                    <div class="summary-value"><?= $urgentAlerts ?></div>
                                </div>

                            </div>

                            <div class="chart-box">
                                <h3>Alert Priority Distribution</h3>
                                <?php profileBarChart($alertPriorityCounts, $totalAlerts); ?>
                            </div>

                            <div class="notice" style="margin-top:15px;">
                                Health alerts represent concerns reported by keepers.
                                This profile records the reported information and review
                                status; it does not diagnose conditions or recommend treatment.
                            </div>

                        </div>

                        <div class="profile-card">

                            <div class="profile-card-heading">
                                <div>
                                    <h2>Health Alert History</h2>
                                    <p>Alerts within the selected date range</p>
                                </div>
                            </div>

                            <?php if (empty($healthAlerts)): ?>
                                <div class="empty-state">No health alerts found for this date range.</div>
                            <?php else: ?>

                                <?php foreach ($healthAlerts as $alert): ?>

                                    <article class="alert-item priority-<?= strtolower(profileEscape($alert['priority'])) ?>">

                                        <div class="alert-top">
                                            <div>
                                                <strong><?= profileEscape($alert['priority']) ?> Priority</strong>
                                                &nbsp;
                                                <span class="profile-status <?= profileStatusClass($alert['status']) ?>">
                                                    <?= profileEscape($alert['status']) ?>
                                                </span>
                                            </div>

                                            <div class="alert-meta">
                                                <?= profileDate($alert['report_date']) ?>
                                                <?= profileTime($alert['report_time']) ?>
                                            </div>
                                        </div>

                                        <div class="alert-description">
                                            <?= profileEscape($alert['observation']) ?>
                                        </div>

                                        <div class="alert-meta">
                                            Reported by:
                                            <?= profileEscape($alert['reporter_name']) ?>
                                            (<?= profileEscape($alert['reporter_code']) ?>)
                                        </div>

                                        <?php if (!empty($alert['reviewer_name'])): ?>
                                            <div class="alert-meta">
                                                Reviewed by: <?= profileEscape($alert['reviewer_name']) ?>
                                            </div>
                                        <?php endif; ?>

                                        <?php if (!empty($alert['review_notes'])): ?>
                                            <div class="notice" style="margin-top:10px;">
                                                <strong>Review Notes</strong><br>
                                                <?= nl2br(profileEscape($alert['review_notes'])) ?>
                                            </div>
                                        <?php endif; ?>

                                        <?php if (!empty($alert['resolved_at'])): ?>
                                            <div class="alert-meta">
                                                Resolved: <?= profileDate($alert['resolved_at']) ?>
                                            </div>
                                        <?php endif; ?>

                                    </article>

                                <?php endforeach; ?>

                            <?php endif; ?>

                        </div>

                    </section>

                    <!-- =====================================
                         ENRICHMENT
                    ====================================== -->

                    <section class="profile-panel" id="panel-enrichment">

                        <div class="profile-card">

                            <div class="profile-card-heading">
                                <div>
                                    <h2>Enrichment Summary</h2>
                                    <p>Observed responses within the selected date range</p>
                                </div>

                                <?php if ($canAddRecords): ?>
                                    <a href="../enrichment/create.php" class="btn-primary">Add Enrichment</a>
                                <?php endif; ?>
                            </div>

                            <div class="summary-grid">

                                <div class="summary-box">
                                    <h3>Total Activities</h3>
                                    <div class="summary-value"><?= $totalEnrichment ?></div>
                                </div>

                                <div class="summary-box">
                                    <h3>Responses Recorded</h3>
                                    <div class="summary-value"><?= $recordedResponses ?></div>
                                </div>

                                <div class="summary-box">
                                    <h3>Response Rate</h3>
                                    <div class="summary-value">
                                        <?= $totalEnrichment > 0
                                            ? number_format(($recordedResponses / $totalEnrichment) * 100, 1) . '%'
                                            : '0.0%' ?>
                                    </div>
                                    <div class="summary-caption">
                                        <?= $missingResponses ?> activity record(s) without a response
                                    </div>
                                </div>

                            </div>

                            <div class="chart-box">
                                <h3>Observed Response Distribution</h3>
                                <?php profileBarChart($responseCounts, $recordedResponses); ?>
                                <div class="summary-caption" style="margin-top:12px;">
                                    The distribution compares Positive, Neutral and Negative
                                    responses among activities with a recorded response.
                                </div>
                            </div>

                        </div>

                        <div class="profile-card">

                            <div class="profile-card-heading">
                                <div>
                                    <h2>Enrichment Activity History</h2>
                                    <p>Activities within the selected date range</p>
                                </div>
                            </div>

                            <?php if (empty($enrichmentRecords)): ?>
                                <div class="empty-state">No enrichment records found for this date range.</div>
                            <?php else: ?>

                                <div class="profile-table-wrap">
                                    <table class="profile-table">
                                        <thead>
                                            <tr>
                                                <th>Date</th>
                                                <th>Time</th>
                                                <th>Activity Type</th>
                                                <th>Description</th>
                                                <th>Observed Response</th>
                                                <th>Keeper</th>
                                                <th>Notes</th>
                                            </tr>
                                        </thead>

                                        <tbody>
                                            <?php foreach ($enrichmentRecords as $record): ?>
                                                <tr>
                                                    <td><?= profileDate($record['activity_date']) ?></td>
                                                    <td><?= profileTime($record['activity_time']) ?></td>
                                                    <td><?= profileEscape($record['enrichment_type']) ?></td>
                                                    <td class="record-text"><?= nl2br(profileEscape($record['activity_description'])) ?></td>
                                                    <td>
                                                        <?php if (!empty($record['observed_response'])): ?>
                                                            <span class="profile-status <?= profileStatusClass($record['observed_response']) ?>">
                                                                <?= profileEscape($record['observed_response']) ?>
                                                            </span>
                                                        <?php else: ?>
                                                            -
                                                        <?php endif; ?>
                                                    </td>
                                                    <td>
                                                        <?= profileEscape($record['keeper_name']) ?>
                                                        <small><?= profileEscape($record['staff_code']) ?></small>
                                                    </td>
                                                    <td class="record-text"><?= nl2br(profileEscape($record['notes'])) ?></td>
                                                </tr>
                                            <?php endforeach; ?>
                                        </tbody>
                                    </table>
                                </div>

                            <?php endif; ?>

                        </div>

                    </section>

                <?php endif; ?>

            </div>
        </main>

    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function () {

    const buttons = document.querySelectorAll('.profile-tab');
    const panels = document.querySelectorAll('.profile-panel');

    function activateTab(tabName) {

        buttons.forEach(function (button) {
            const active = button.dataset.tab === tabName;

            button.classList.toggle('active', active);
            button.setAttribute('aria-selected', active ? 'true' : 'false');
        });

        panels.forEach(function (panel) {
            panel.classList.toggle(
                'active',
                panel.id === 'panel-' + tabName
            );
        });
    }

    buttons.forEach(function (button) {
        button.addEventListener('click', function () {
            activateTab(button.dataset.tab);
        });
    });

});
</script>

</body>
</html>

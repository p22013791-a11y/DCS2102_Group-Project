<?php
require_once __DIR__ . '/../../includes/auth.php';
requireRole('Senior Keeper');
require_once __DIR__ . '/report_helpers.php';

[$dateFrom, $dateTo, $error] = reportReadDateRange();
$search = trim((string)($_GET['search'] ?? ''));
$records = [];
$pageError = false;

if ($error === '') {
    try {
        $sql = "
            SELECT o.observation_id, o.observation_date, o.observation_time,
                   o.appetite, o.activity_level, o.behaviour, o.physical_concern,
                   o.notes, o.status, o.reviewed_at, o.review_notes,
                   p.penguin_id, p.animal_code, p.name AS penguin_name,
                   u.staff_code, u.full_name AS keeper_name,
                   reviewer.full_name AS reviewer_name
            FROM husbandry_observations o
            INNER JOIN penguins p ON p.penguin_id = o.penguin_id
            INNER JOIN users u ON u.user_id = o.keeper_id
            LEFT JOIN users reviewer ON reviewer.user_id = o.reviewed_by
            WHERE 1 = 1
        ";
        $params = [];
        $sql .= reportDateConditions('o.observation_date', $dateFrom, $dateTo, $params);
        if ($search !== '') {
            $sql .= " AND (p.animal_code LIKE ? OR p.name LIKE ? OR u.staff_code LIKE ? OR u.full_name LIKE ? OR o.appetite LIKE ? OR o.activity_level LIKE ? OR o.behaviour LIKE ? OR o.physical_concern LIKE ? OR o.notes LIKE ? OR o.review_notes LIKE ?)";
            $keyword = "%{$search}%";
            for ($i = 0; $i < 10; $i++) $params[] = $keyword;
        }
        $sql .= " ORDER BY o.observation_date DESC, o.observation_time DESC, o.observation_id DESC";
        $stmt = $pdo->prepare($sql);
        $stmt->execute($params);
        $records = $stmt->fetchAll(PDO::FETCH_ASSOC);
    } catch (PDOException $e) {
        error_log('Observation report error: ' . $e->getMessage());
        $pageError = true;
    }
}

$scoreMap = ['Good' => 3, 'Moderate' => 2, 'Poor' => 1, 'Vibrant' => 3];
$sums = ['appetite' => 0, 'activity_level' => 0, 'behaviour' => 0];
$counts = ['appetite' => 0, 'activity_level' => 0, 'behaviour' => 0];
$population = [];
$statusCounts = ['Draft' => 0, 'Submitted' => 0, 'Reviewed' => 0];

foreach ($records as $r) {
    $population[(int)$r['penguin_id']] = true;
    if (isset($statusCounts[$r['status']])) $statusCounts[$r['status']]++;
    foreach (['appetite', 'activity_level', 'behaviour'] as $field) {
        $value = $r[$field];
        if ($value !== null && $value !== '') {
            $score = $scoreMap[$value] ?? null;
            if ($score !== null) {
                $sums[$field] += $score;
                $counts[$field]++;
            }
        }
    }
}
$averages = [];
foreach ($sums as $field => $sum) {
    $averages[$field] = $counts[$field] ? $sum / $counts[$field] : null;
}

reportRenderStart('Husbandry Observations Report', 'Review appetite, activity and behaviour patterns alongside submission and review progress.', ['Reports', 'Husbandry Observations Report']);
if ($error !== ''): ?><div class="report-error"><?= reportEscape($error) ?></div><?php endif; ?>
<?php reportRenderFilter('observations.php', $search, $dateFrom, $dateTo, 'Penguin, keeper or observation...'); ?>
<?php if ($pageError): ?><div class="report-error">Unable to load observation records. Please try again.</div><?php else: ?>
<section class="report-panel">
    <div class="report-panel-heading"><div><h2>Observation Overview</h2><div class="report-muted">Averages use the selected date range. Physical Concern is intentionally excluded from all evaluation scores.</div></div></div>
    <div class="report-stats">
        <?php reportStat('Penguin Population', (string)count($population), 'Distinct penguins with observation records'); ?>
        <?php reportStat('Average Appetite', $averages['appetite'] === null ? '—' : number_format($averages['appetite'], 2) . ' / 3', 'Good 3 · Moderate 2 · Poor 1'); ?>
        <?php reportStat('Average Activity', $averages['activity_level'] === null ? '—' : number_format($averages['activity_level'], 2) . ' / 3', 'Vibrant 3 · Moderate 2 · Poor 1'); ?>
        <?php reportStat('Average Behaviour', $averages['behaviour'] === null ? '—' : number_format($averages['behaviour'], 2) . ' / 3', 'Good 3 · Moderate 2 · Poor 1'); ?>
        <?php reportStat('Submitted', (string)$statusCounts['Submitted'], 'Awaiting Senior Keeper review'); ?>
        <?php reportStat('Reviewed', (string)$statusCounts['Reviewed'], 'Reviewed observations'); ?>
    </div>
    <div class="report-chart-list">
        <?php foreach (['appetite' => 'Appetite', 'activity_level' => 'Activity', 'behaviour' => 'Behaviour'] as $field => $label): ?>
            <?php $average = $averages[$field]; ?>
            <?php reportBar($label, $average === null ? 0 : ($average / 3) * 100, $average === null ? 'No data' : number_format($average, 2) . ' / 3'); ?>
        <?php endforeach; ?>
    </div>
    <div class="report-legend">Scoring key: 1 = Poor, 2 = Moderate, 3 = Good (Activity uses Vibrant for 3). These are summaries of keeper-entered categories, not veterinary assessments.</div>
</section>
<section class="report-panel">
    <div class="report-panel-heading"><h2>Observation Record Details</h2><span class="report-muted"><?= count($records) ?> record(s)</span></div>
    <?php if (!$records): ?><div class="report-empty">No observation records found for this period.</div><?php else: ?>
    <div class="report-table-wrap"><table class="data-table">
        <thead><tr><th>ID</th><th>Date</th><th>Time</th><th>Penguin</th><th>Keeper</th><th>Appetite</th><th>Activity</th><th>Behaviour</th><th>Physical Concern</th><th>Status</th><th>Reviewed By</th><th>Reviewed At</th><th>Review Notes</th></tr></thead>
        <tbody><?php foreach ($records as $r): ?><tr>
            <td><?= (int)$r['observation_id'] ?></td><td><?= reportEscape($r['observation_date']) ?></td><td><?= reportEscape($r['observation_time'] ?: '-') ?></td>
            <td><?= reportEscape($r['animal_code'] . ' - ' . $r['penguin_name']) ?></td><td><?= reportEscape($r['staff_code'] . ' - ' . $r['keeper_name']) ?></td>
            <td><?= reportEscape($r['appetite'] ?: '-') ?></td><td><?= reportEscape($r['activity_level'] ?: '-') ?></td><td><?= reportEscape($r['behaviour'] ?: '-') ?></td>
            <td><?= reportEscape($r['physical_concern'] ?: '-') ?></td><td><?= reportEscape($r['status']) ?></td>
            <td><?= reportEscape($r['reviewer_name'] ?: '-') ?></td><td><?= reportEscape($r['reviewed_at'] ?: '-') ?></td><td><?= reportEscape($r['review_notes'] ?: '-') ?></td>
        </tr><?php endforeach; ?></tbody>
    </table></div><?php endif; ?>
</section>
<?php endif; ?>
<?php reportRenderEnd(); ?>

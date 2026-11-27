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
            SELECT er.enrichment_id, er.activity_date, er.activity_time,
                   er.enrichment_type, er.activity_description, er.observed_response, er.notes,
                   p.penguin_id, p.animal_code, p.name AS penguin_name,
                   u.staff_code, u.full_name AS keeper_name
            FROM enrichment_records er
            INNER JOIN penguins p ON p.penguin_id = er.penguin_id
            INNER JOIN users u ON u.user_id = er.keeper_id
            WHERE 1 = 1
        ";
        $params = [];
        $sql .= reportDateConditions('er.activity_date', $dateFrom, $dateTo, $params);
        if ($search !== '') {
            $sql .= " AND (p.animal_code LIKE ? OR p.name LIKE ? OR u.staff_code LIKE ? OR u.full_name LIKE ? OR er.enrichment_type LIKE ? OR er.activity_description LIKE ? OR er.observed_response LIKE ? OR er.notes LIKE ?)";
            $keyword = "%{$search}%";
            for ($i = 0; $i < 8; $i++) $params[] = $keyword;
        }
        $sql .= " ORDER BY er.activity_date DESC, er.activity_time DESC, er.enrichment_id DESC";
        $stmt = $pdo->prepare($sql);
        $stmt->execute($params);
        $records = $stmt->fetchAll(PDO::FETCH_ASSOC);
    } catch (PDOException $e) {
        error_log('Enrichment report error: ' . $e->getMessage());
        $pageError = true;
    }
}

$scoreMap = ['Positive' => 3, 'Neutral' => 2, 'Negative' => 1];
$counts = ['Positive' => 0, 'Neutral' => 0, 'Negative' => 0];
$population = [];
$totalScore = 0;
$scoredCount = 0;
foreach ($records as $r) {
    $population[(int)$r['penguin_id']] = true;
    $response = $r['observed_response'];
    if (isset($counts[$response])) {
        $counts[$response]++;
        $totalScore += $scoreMap[$response];
        $scoredCount++;
    }
}
$average = $scoredCount ? $totalScore / $scoredCount : null;
$positiveRate = $scoredCount ? ($counts['Positive'] / $scoredCount) * 100 : 0;
$totalResponses = array_sum($counts);

reportRenderStart('Enrichment Records Report', 'Summarise penguin participation and recorded responses to enrichment activities.', ['Reports', 'Enrichment Records Report']);
if ($error !== ''): ?><div class="report-error"><?= reportEscape($error) ?></div><?php endif; ?>
<?php reportRenderFilter('enrichment.php', $search, $dateFrom, $dateTo, 'Penguin, keeper or activity...'); ?>
<?php if ($pageError): ?><div class="report-error">Unable to load enrichment records. Please try again.</div><?php else: ?>
<section class="report-panel">
    <div class="report-panel-heading"><div><h2>Enrichment Overview</h2><div class="report-muted">Response scoring: Positive 3, Neutral 2, Negative 1. Blank responses are excluded from the average.</div></div></div>
    <div class="report-stats">
        <?php reportStat('Penguin Population', (string)count($population), 'Distinct penguins with enrichment records'); ?>
        <?php reportStat('Average Observed Response', $average === null ? '—' : number_format($average, 2) . ' / 3', 'Average score across recorded responses'); ?>
        <?php reportStat('Positive Response Rate', $scoredCount ? number_format($positiveRate, 1) . '%' : '—', $counts['Positive'] . ' positive response(s)'); ?>
        <?php reportStat('Recorded Responses', (string)$scoredCount, 'Non-blank observed responses'); ?>
    </div>
    <div class="report-chart-list">
        <?php foreach ($counts as $response => $count): ?>
            <?php $rate = $totalResponses ? ($count / $totalResponses) * 100 : 0; ?>
            <?php reportBar($response, $rate, number_format($rate, 1) . '% (' . $count . ')', $response === 'Positive' ? '' : ($response === 'Neutral' ? 'amber' : 'red')); ?>
        <?php endforeach; ?>
    </div>
</section>
<section class="report-panel">
    <div class="report-panel-heading"><h2>Enrichment Activity Details</h2><span class="report-muted"><?= count($records) ?> record(s)</span></div>
    <?php if (!$records): ?><div class="report-empty">No enrichment records found for this period.</div><?php else: ?>
    <div class="report-table-wrap"><table class="data-table">
        <thead><tr><th>ID</th><th>Date</th><th>Time</th><th>Penguin</th><th>Keeper</th><th>Enrichment Type</th><th>Activity Description</th><th>Observed Response</th><th>Notes</th></tr></thead>
        <tbody><?php foreach ($records as $r): ?><tr>
            <td><?= (int)$r['enrichment_id'] ?></td><td><?= reportEscape($r['activity_date']) ?></td><td><?= reportEscape($r['activity_time'] ?: '-') ?></td>
            <td><?= reportEscape($r['animal_code'] . ' - ' . $r['penguin_name']) ?></td><td><?= reportEscape($r['staff_code'] . ' - ' . $r['keeper_name']) ?></td>
            <td><?= reportEscape($r['enrichment_type']) ?></td><td><?= reportEscape($r['activity_description']) ?></td>
            <td><?= reportEscape($r['observed_response'] ?: '-') ?></td><td><?= reportEscape($r['notes'] ?: '-') ?></td>
        </tr><?php endforeach; ?></tbody>
    </table></div><?php endif; ?>
</section>
<?php endif; ?>
<?php reportRenderEnd(); ?>

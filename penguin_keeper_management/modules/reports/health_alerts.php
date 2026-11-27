<?php
require_once __DIR__ . '/../../includes/auth.php';
requireRole('Senior Keeper');
require_once __DIR__ . '/report_helpers.php';

[$dateFrom, $dateTo, $error] = reportReadDateRange();
$search = trim((string)($_GET['search'] ?? ''));
$status = trim((string)($_GET['status'] ?? ''));
$allowedStatuses = ['Pending', 'Reviewed', 'Resolved'];
if ($error === '' && $status !== '' && !in_array($status, $allowedStatuses, true)) {
    $error = 'Invalid status filter.';
}
$records = [];
$pageError = false;

if ($error === '') {
    try {
        $sql = "
            SELECT h.alert_id, h.report_date, h.report_time, h.priority, h.observation,
                   h.status, h.review_notes, h.resolved_at,
                   p.penguin_id, p.animal_code, p.name AS penguin_name,
                   e.enclosure_code, reporter.staff_code AS reporter_code,
                   reporter.full_name AS reporter_name, reviewer.full_name AS reviewer_name
            FROM health_alerts h
            INNER JOIN penguins p ON p.penguin_id = h.penguin_id
            INNER JOIN users reporter ON reporter.user_id = h.reported_by
            LEFT JOIN users reviewer ON reviewer.user_id = h.reviewed_by
            LEFT JOIN enclosures e ON e.enclosure_id = p.enclosure_id
            WHERE 1 = 1
        ";
        $params = [];
        $sql .= reportDateConditions('h.report_date', $dateFrom, $dateTo, $params);
        if ($status !== '') {
            $sql .= " AND h.status = ?";
            $params[] = $status;
        }
        if ($search !== '') {
            $sql .= " AND (p.animal_code LIKE ? OR p.name LIKE ? OR reporter.staff_code LIKE ? OR reporter.full_name LIKE ? OR h.observation LIKE ? OR h.review_notes LIKE ? OR e.enclosure_code LIKE ?)";
            $keyword = "%{$search}%";
            array_push($params, $keyword, $keyword, $keyword, $keyword, $keyword, $keyword, $keyword);
        }
        $sql .= " ORDER BY h.report_date DESC, h.report_time DESC, h.alert_id DESC";
        $stmt = $pdo->prepare($sql);
        $stmt->execute($params);
        $records = $stmt->fetchAll(PDO::FETCH_ASSOC);
    } catch (PDOException $e) {
        error_log('Health alert report error: ' . $e->getMessage());
        $pageError = true;
    }
}

$statusCounts = ['Pending' => 0, 'Reviewed' => 0, 'Resolved' => 0];
$priorityCounts = ['Urgent' => 0, 'Attention' => 0, 'Routine' => 0];
$penguinBreakdown = [];
foreach ($records as $record) {
    if (isset($statusCounts[$record['status']])) $statusCounts[$record['status']]++;
    if (isset($priorityCounts[$record['priority']])) $priorityCounts[$record['priority']]++;
    $id = (int)$record['penguin_id'];
    if (!isset($penguinBreakdown[$id])) {
        $penguinBreakdown[$id] = [
            'code' => $record['animal_code'], 'name' => $record['penguin_name'],
            'enclosure' => $record['enclosure_code'] ?: '-', 'total' => 0,
            'Urgent' => 0, 'Attention' => 0, 'Routine' => 0
        ];
    }
    $penguinBreakdown[$id]['total']++;
    if (isset($penguinBreakdown[$id][$record['priority']])) $penguinBreakdown[$id][$record['priority']]++;
}
$priorityTotal = array_sum($priorityCounts);
$priorityColors = ['Urgent' => 'red', 'Attention' => 'amber', 'Routine' => 'blue'];
$extra = '<div class="form-group"><label for="status">Status</label><select id="status" name="status"><option value="">All Statuses</option>';
foreach ($allowedStatuses as $item) {
    $extra .= '<option value="' . reportEscape($item) . '"' . ($status === $item ? ' selected' : '') . '>' . reportEscape($item) . '</option>';
}
$extra .= '</select></div>';

reportRenderStart('Health Alerts Report', 'Monitor alert status, priority distribution and penguins requiring follow-up.', ['Reports', 'Health Alerts Report']);
if ($error !== ''): ?><div class="report-error"><?= reportEscape($error) ?></div><?php endif; ?>
<?php reportRenderFilter('health_alerts.php', $search, $dateFrom, $dateTo, 'Penguin, keeper, enclosure or concern...', $extra); ?>
<?php if ($pageError): ?><div class="report-error">Unable to load health alerts. Please try again.</div><?php else: ?>
<section class="report-panel">
    <div class="report-panel-heading"><h2>Alert Status Overview</h2><span class="report-muted"><?= count($records) ?> alert(s) in selected period</span></div>
    <div class="report-stats">
        <?php reportStat('Pending', (string)$statusCounts['Pending'], 'Awaiting review'); ?>
        <?php reportStat('Reviewed', (string)$statusCounts['Reviewed'], 'Reviewed and awaiting further action or closure'); ?>
        <?php reportStat('Resolved', (string)$statusCounts['Resolved'], 'Marked as resolved'); ?>
    </div>
</section>
<div class="report-two-col">
    <section class="report-panel">
        <div class="report-panel-heading"><h2>Priority Distribution</h2></div>
        <div class="report-chart-list">
            <?php foreach ($priorityCounts as $priority => $count): ?>
                <?php $rate = $priorityTotal ? ($count / $priorityTotal) * 100 : 0; ?>
                <?php reportBar($priority, $rate, number_format($rate, 1) . '% (' . $count . ')', $priorityColors[$priority]); ?>
            <?php endforeach; ?>
        </div>
        <div class="report-legend">Priority rate is each priority category’s share of all alerts in the selected date range.</div>
    </section>
    <section class="report-panel">
        <div class="report-panel-heading"><h2>Penguins by Alert Priority</h2></div>
        <?php if (!$penguinBreakdown): ?><div class="report-empty">No penguin alerts in this period.</div><?php else: ?>
        <div class="report-table-wrap"><table class="data-table">
            <thead><tr><th>Penguin</th><th>Enclosure</th><th>Total</th><th>Urgent</th><th>Attention</th><th>Routine</th></tr></thead>
            <tbody><?php foreach ($penguinBreakdown as $penguin): ?><tr>
                <td><?= reportEscape($penguin['code'] . ' - ' . $penguin['name']) ?></td>
                <td><?= reportEscape($penguin['enclosure']) ?></td><td><?= $penguin['total'] ?></td>
                <td><?= $penguin['Urgent'] ?></td><td><?= $penguin['Attention'] ?></td><td><?= $penguin['Routine'] ?></td>
            </tr><?php endforeach; ?></tbody>
        </table></div><?php endif; ?>
    </section>
</div>
<section class="report-panel">
    <div class="report-panel-heading"><h2>Health Alert Details</h2></div>
    <?php if (!$records): ?><div class="report-empty">No health alerts found for this period.</div><?php else: ?>
    <div class="report-table-wrap"><table class="data-table">
        <thead><tr><th>ID</th><th>Date</th><th>Time</th><th>Penguin</th><th>Enclosure</th><th>Reported By</th><th>Priority</th><th>Observation</th><th>Status</th><th>Reviewed By</th><th>Review Notes</th><th>Resolved At</th></tr></thead>
        <tbody><?php foreach ($records as $r): ?><tr>
            <td><?= (int)$r['alert_id'] ?></td><td><?= reportEscape($r['report_date']) ?></td><td><?= reportEscape($r['report_time'] ?: '-') ?></td>
            <td><?= reportEscape($r['animal_code'] . ' - ' . $r['penguin_name']) ?></td><td><?= reportEscape($r['enclosure_code'] ?: '-') ?></td>
            <td><?= reportEscape($r['reporter_code'] . ' - ' . $r['reporter_name']) ?></td>
            <td><span class="report-chip <?= strtolower(reportEscape($r['priority'])) ?>"><?= reportEscape($r['priority']) ?></span></td>
            <td><?= reportEscape($r['observation']) ?></td><td><?= reportEscape($r['status']) ?></td>
            <td><?= reportEscape($r['reviewer_name'] ?: '-') ?></td><td><?= reportEscape($r['review_notes'] ?: '-') ?></td><td><?= reportEscape($r['resolved_at'] ?: '-') ?></td>
        </tr><?php endforeach; ?></tbody>
    </table></div><?php endif; ?>
</section>
<?php endif; ?>
<?php reportRenderEnd(); ?>

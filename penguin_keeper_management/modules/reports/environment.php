<?php
require_once __DIR__ . '/../../includes/auth.php';
requireRole('Senior Keeper');
require_once __DIR__ . '/report_helpers.php';

[$dateFrom, $dateTo, $error] = reportReadDateRange();
$search = trim((string)($_GET['search'] ?? ''));
$records = [];
$pageError = false;
$activeEnclosures = 0;

if ($error === '') {
    try {
        $stmt = $pdo->query("SELECT COUNT(*) FROM enclosures WHERE archived_at IS NULL AND status = 'Active'");
        $activeEnclosures = (int)$stmt->fetchColumn();

        $sql = "
            SELECT r.reading_id, r.reading_date, r.reading_time,
                   r.pool_temperature, r.ph, r.salinity, r.chlorine, r.notes,
                   e.enclosure_id, e.enclosure_code, e.enclosure_name,
                   u.staff_code, u.full_name AS keeper_name
            FROM environmental_readings r
            INNER JOIN enclosures e ON e.enclosure_id = r.enclosure_id
            INNER JOIN users u ON u.user_id = r.keeper_id
            WHERE 1 = 1
        ";
        $params = [];
        $sql .= reportDateConditions('r.reading_date', $dateFrom, $dateTo, $params);
        if ($search !== '') {
            $sql .= " AND (e.enclosure_code LIKE ? OR e.enclosure_name LIKE ? OR u.staff_code LIKE ? OR u.full_name LIKE ? OR r.notes LIKE ?)";
            $keyword = "%{$search}%";
            for ($i = 0; $i < 5; $i++) $params[] = $keyword;
        }
        $sql .= " ORDER BY r.reading_date DESC, r.reading_time DESC, r.reading_id DESC";
        $stmt = $pdo->prepare($sql);
        $stmt->execute($params);
        $records = $stmt->fetchAll(PDO::FETCH_ASSOC);
    } catch (PDOException $e) {
        error_log('Environment report error: ' . $e->getMessage());
        $pageError = true;
    }
}

$fields = [
    'pool_temperature' => ['label' => 'Average Temperature', 'unit' => ' °C'],
    'ph' => ['label' => 'Average pH', 'unit' => ''],
    'salinity' => ['label' => 'Average Salinity', 'unit' => ' ppt'],
    'chlorine' => ['label' => 'Average Chlorine', 'unit' => ' mg/L']
];
$totals = array_fill_keys(array_keys($fields), 0.0);
$counts = array_fill_keys(array_keys($fields), 0);
foreach ($records as $r) {
    foreach ($fields as $field => $meta) {
        if ($r[$field] !== null && $r[$field] !== '') {
            $totals[$field] += (float)$r[$field];
            $counts[$field]++;
        }
    }
}
$averages = [];
foreach ($fields as $field => $meta) {
    $averages[$field] = $counts[$field] ? $totals[$field] / $counts[$field] : null;
}
$enclosurePopulation = [];
foreach ($records as $r) {
    $enclosurePopulation[(int)$r['enclosure_id']] = true;
}

reportRenderStart('Environmental Readings Report', 'Review enclosure coverage and average environmental measurements for the selected period.', ['Reports', 'Environmental Readings Report']);
if ($error !== ''): ?><div class="report-error"><?= reportEscape($error) ?></div><?php endif; ?>
<?php reportRenderFilter('environment.php', $search, $dateFrom, $dateTo, 'Enclosure, keeper or notes...'); ?>
<?php if ($pageError): ?><div class="report-error">Unable to load environmental readings. Please try again.</div><?php else: ?>
<section class="report-panel">
    <div class="report-panel-heading"><div><h2>Environment Overview</h2><div class="report-muted">Averages exclude blank measurements and use all matching readings in the selected date range.</div></div></div>
    <div class="report-stats">
        <?php reportStat('Active Enclosures', (string)$activeEnclosures, 'Current non-archived active enclosures'); ?>
        <?php foreach ($fields as $field => $meta): ?>
            <?php reportStat($meta['label'], $averages[$field] === null ? '—' : number_format($averages[$field], 2) . $meta['unit'], $counts[$field] . ' measurement(s) included'); ?>
        <?php endforeach; ?>
    </div>
    <div class="report-legend">Enclosures represented in this report: <?= count($enclosurePopulation) ?>. Facility-approved operating ranges should be used when interpreting environmental readings.</div>
</section>
<section class="report-panel">
    <div class="report-panel-heading"><h2>Environmental Reading Details</h2><span class="report-muted"><?= count($records) ?> record(s)</span></div>
    <?php if (!$records): ?><div class="report-empty">No environmental readings found for this period.</div><?php else: ?>
    <div class="report-table-wrap"><table class="data-table">
        <thead><tr><th>ID</th><th>Date</th><th>Time</th><th>Enclosure</th><th>Temperature (°C)</th><th>pH</th><th>Salinity (ppt)</th><th>Chlorine (mg/L)</th><th>Keeper</th><th>Notes</th></tr></thead>
        <tbody><?php foreach ($records as $r): ?><tr>
            <td><?= (int)$r['reading_id'] ?></td><td><?= reportEscape($r['reading_date'] ?: '-') ?></td><td><?= reportEscape($r['reading_time'] ?: '-') ?></td>
            <td><?= reportEscape($r['enclosure_code'] . ' - ' . $r['enclosure_name']) ?></td>
            <td><?= $r['pool_temperature'] === null ? '-' : number_format((float)$r['pool_temperature'], 2) ?></td>
            <td><?= $r['ph'] === null ? '-' : number_format((float)$r['ph'], 2) ?></td>
            <td><?= $r['salinity'] === null ? '-' : number_format((float)$r['salinity'], 2) ?></td>
            <td><?= $r['chlorine'] === null ? '-' : number_format((float)$r['chlorine'], 2) ?></td>
            <td><?= reportEscape($r['staff_code'] . ' - ' . $r['keeper_name']) ?></td><td><?= reportEscape($r['notes'] ?: '-') ?></td>
        </tr><?php endforeach; ?></tbody>
    </table></div><?php endif; ?>
</section>
<?php endif; ?>
<?php reportRenderEnd(); ?>

<?php
require_once __DIR__ . '/../../includes/auth.php';
requireRole('Senior Keeper');
require_once __DIR__ . '/report_helpers.php';

[$dateFrom, $dateTo, $error] = reportReadDateRange();
$search = trim((string)($_GET['search'] ?? ''));
$pageError = false;
$allRecords = [];
$records = [];
$latestByPenguin = [];
$previousWeights = [];

if ($error === '') {
    try {
        // Fetch earlier measurements as well, so each penguin can be compared
        // with its immediately preceding measurement before the latest reading
        // in the selected date range.
        $sql = "
            SELECT w.weight_id, w.penguin_id, w.measurement_date, w.measurement_time,
                   w.weight_kg, w.notes, p.animal_code, p.name AS penguin_name,
                   u.staff_code, u.full_name AS keeper_name
            FROM weight_records w
            INNER JOIN penguins p ON p.penguin_id = w.penguin_id
            INNER JOIN users u ON u.user_id = w.keeper_id
            WHERE 1 = 1
        ";
        $params = [];
        if ($dateTo !== '') {
            $sql .= " AND w.measurement_date <= ?";
            $params[] = $dateTo;
        }
        if ($search !== '') {
            $sql .= " AND (p.animal_code LIKE ? OR p.name LIKE ? OR u.staff_code LIKE ? OR u.full_name LIKE ? OR w.notes LIKE ?)";
            $keyword = "%{$search}%";
            array_push($params, $keyword, $keyword, $keyword, $keyword, $keyword);
        }
        $sql .= " ORDER BY w.penguin_id, w.measurement_date ASC, w.measurement_time ASC, w.weight_id ASC";
        $stmt = $pdo->prepare($sql);
        $stmt->execute($params);
        $allRecords = $stmt->fetchAll(PDO::FETCH_ASSOC);

        $grouped = [];
        foreach ($allRecords as $row) {
            $grouped[(int)$row['penguin_id']][] = $row;
            $inRange = ($dateFrom === '' || $row['measurement_date'] >= $dateFrom)
                && ($dateTo === '' || $row['measurement_date'] <= $dateTo);
            if ($inRange) {
                $records[] = $row;
            }
        }

        foreach ($grouped as $penguinId => $penguinRows) {
            $selectedIndexes = [];
            foreach ($penguinRows as $index => $row) {
                $inRange = ($dateFrom === '' || $row['measurement_date'] >= $dateFrom)
                    && ($dateTo === '' || $row['measurement_date'] <= $dateTo);
                if ($inRange) {
                    $selectedIndexes[] = $index;
                }
            }
            if (!$selectedIndexes) {
                continue;
            }
            $latestIndex = end($selectedIndexes);
            $latestByPenguin[$penguinId] = (float)$penguinRows[$latestIndex]['weight_kg'];
            if ($latestIndex > 0) {
                $previousWeights[] = (float)$penguinRows[$latestIndex - 1]['weight_kg'];
            }
        }

        usort($records, static function (array $a, array $b): int {
            return [$b['measurement_date'], $b['measurement_time'], $b['weight_id']]
                <=> [$a['measurement_date'], $a['measurement_time'], $a['weight_id']];
        });
    } catch (PDOException $e) {
        error_log('Weight report error: ' . $e->getMessage());
        $pageError = true;
    }
}

$population = count($latestByPenguin);
$averageWeight = $population ? array_sum($latestByPenguin) / $population : null;
$previousAverage = count($previousWeights) ? array_sum($previousWeights) / count($previousWeights) : null;
$change = ($averageWeight !== null && $previousAverage !== null)
    ? $averageWeight - $previousAverage : null;

reportRenderStart('Weight Records Report', 'Compare each penguin’s latest weight in the selected period with its previous measurement.', ['Reports', 'Weight Records Report']);
if ($error !== ''): ?><div class="report-error"><?= reportEscape($error) ?></div><?php endif; ?>
<?php reportRenderFilter('weight.php', $search, $dateFrom, $dateTo, 'Penguin, keeper or notes...'); ?>
<?php if ($pageError): ?><div class="report-error">Unable to load weight records. Please try again.</div><?php else: ?>
<section class="report-panel">
    <div class="report-panel-heading"><div><h2>Weight Overview</h2><div class="report-muted">One latest measurement per penguin is used in the averages to avoid over-representing penguins measured more frequently.</div></div></div>
    <div class="report-stats">
        <?php reportStat('Penguin Population', (string)$population, 'Distinct penguins measured in this date range'); ?>
        <?php reportStat('Average Weight', $averageWeight === null ? '—' : number_format($averageWeight, 2) . ' kg', 'Average of latest in-range measurement for each penguin'); ?>
        <?php reportStat('Previous Average Weight', $previousAverage === null ? '—' : number_format($previousAverage, 2) . ' kg', 'Average of each represented penguin’s immediately preceding measurement'); ?>
    </div>
    <?php if ($change !== null): ?>
        <div class="report-legend">Change from previous measurements:
            <strong><?= $change > 0 ? '+' : '' ?><?= number_format($change, 2) ?> kg</strong>
            <?= $change > 0 ? 'average increase' : ($change < 0 ? 'average decrease' : 'no average change') ?>.
        </div>
    <?php else: ?>
        <div class="report-legend">Previous average is unavailable when none of the penguins in this period has an earlier measurement.</div>
    <?php endif; ?>
</section>
<section class="report-panel">
    <div class="report-panel-heading"><h2>Weight Measurement Details</h2><span class="report-muted"><?= count($records) ?> record(s)</span></div>
    <?php if (!$records): ?><div class="report-empty">No weight records found for this period.</div><?php else: ?>
    <div class="report-table-wrap"><table class="data-table">
        <thead><tr><th>ID</th><th>Date</th><th>Time</th><th>Penguin</th><th>Weight (kg)</th><th>Keeper</th><th>Notes</th></tr></thead>
        <tbody><?php foreach ($records as $record): ?><tr>
            <td><?= (int)$record['weight_id'] ?></td><td><?= reportEscape($record['measurement_date']) ?></td>
            <td><?= reportEscape($record['measurement_time'] ?: '-') ?></td>
            <td><?= reportEscape($record['animal_code'] . ' - ' . $record['penguin_name']) ?></td>
            <td><?= number_format((float)$record['weight_kg'], 2) ?></td>
            <td><?= reportEscape($record['staff_code'] . ' - ' . $record['keeper_name']) ?></td>
            <td><?= reportEscape($record['notes'] ?: '-') ?></td>
        </tr><?php endforeach; ?></tbody>
    </table></div><?php endif; ?>
</section>
<?php endif; ?>
<?php reportRenderEnd(); ?>

<?php

require_once __DIR__ . '/../../includes/auth.php';
requireRole('Senior Keeper');
require_once __DIR__ . '/report_helpers.php';

[$dateFrom, $dateTo, $error] = reportReadDateRange();

$records = [];
$pageError = false;

$summary = [
    'total_records' => 0,
    'penguin_count' => 0,
    'finalized' => 0,
    'draft' => 0,
    'total_planned' => 0.0,
    'total_consumed' => 0.0,
    'average_consumed' => 0.0
];

$foodTypeCounts = [];

if ($error === '') {
    try {
        $sql = "
            SELECT
                f.feeding_id,
                f.penguin_id,
                f.feeding_date,
                f.feeding_time,
                f.food_type,
                f.planned_grams,
                f.consumed_grams,
                f.notes,
                f.status,
                p.animal_code,
                p.name AS penguin_name,
                u.staff_code,
                u.full_name AS keeper_name
            FROM feeding_records f
            INNER JOIN penguins p
                ON p.penguin_id = f.penguin_id
            INNER JOIN users u
                ON u.user_id = f.keeper_id
            WHERE 1 = 1
        ";

        $params = [];

        if ($dateFrom !== '') {
            $sql .= " AND f.feeding_date >= ?";
            $params[] = $dateFrom;
        }

        if ($dateTo !== '') {
            $sql .= " AND f.feeding_date <= ?";
            $params[] = $dateTo;
        }

        $sql .= "
            ORDER BY
                f.feeding_date DESC,
                f.feeding_time DESC,
                f.feeding_id DESC
        ";

        $stmt = $pdo->prepare($sql);
        $stmt->execute($params);
        $records = $stmt->fetchAll(PDO::FETCH_ASSOC);

        $penguins = [];

        foreach ($records as $record) {
            $summary['total_records']++;

            $penguins[(int)$record['penguin_id']] = true;

            $planned = (float)($record['planned_grams'] ?? 0);
            $consumed = (float)($record['consumed_grams'] ?? 0);

            $summary['total_planned'] += $planned;
            $summary['total_consumed'] += $consumed;

            if ($record['status'] === 'Finalized') {
                $summary['finalized']++;
            } elseif ($record['status'] === 'Draft') {
                $summary['draft']++;
            }

            $foodType = trim((string)($record['food_type'] ?? ''));
            if ($foodType === '') {
                $foodType = 'Unspecified';
            }

            if (!isset($foodTypeCounts[$foodType])) {
                $foodTypeCounts[$foodType] = [
                    'records' => 0,
                    'planned' => 0.0,
                    'consumed' => 0.0
                ];
            }

            $foodTypeCounts[$foodType]['records']++;
            $foodTypeCounts[$foodType]['planned'] += $planned;
            $foodTypeCounts[$foodType]['consumed'] += $consumed;
        }

        $summary['penguin_count'] = count($penguins);

        $summary['average_consumed'] = $summary['total_records'] > 0
            ? $summary['total_consumed'] / $summary['total_records']
            : 0.0;

        uasort(
            $foodTypeCounts,
            static fn(array $a, array $b): int =>
                $b['records'] <=> $a['records']
        );

    } catch (PDOException $e) {
        error_log('Feeding report error: ' . $e->getMessage());
        $pageError = true;
    }
}

$consumptionRate = $summary['total_planned'] > 0
    ? ($summary['total_consumed'] / $summary['total_planned']) * 100
    : null;

$finalizationRate = $summary['total_records'] > 0
    ? ($summary['finalized'] / $summary['total_records']) * 100
    : 0;

$foodTypeTotal = array_sum(
    array_column($foodTypeCounts, 'records')
);

$breadcrumbs = ['Reports', 'Feeding Records Report'];

reportRenderStart(
    'Feeding Records Report',
    'Review feeding completion, food consumption and keeper-entered feeding records.',
    $breadcrumbs
);

?>
<style>
    /* Keep the feeding overview KPIs in a balanced four-card desktop row. */
    .feeding-overview-stats {
        grid-template-columns: repeat(4, minmax(0, 1fr));
        align-items: stretch;
    }

    .feeding-overview-stats .report-stat {
        min-height: 126px;
        display: flex;
        flex-direction: column;
        justify-content: space-between;
    }

    @media (max-width: 1050px) {
        .feeding-overview-stats {
            grid-template-columns: repeat(2, minmax(0, 1fr));
        }
    }

    @media (max-width: 520px) {
        .feeding-overview-stats {
            grid-template-columns: 1fr;
        }
    }
</style>
<?php

if ($error !== ''):
?>
    <div class="report-error"><?= reportEscape($error) ?></div>
<?php endif; ?>

<section class="report-panel">
    <form method="GET" action="feeding.php" class="report-filter">
        <div class="form-group">
            <label for="date_from">Date From</label>
            <input
                type="date"
                id="date_from"
                name="date_from"
                value="<?= reportEscape($dateFrom) ?>"
            >
        </div>

        <div class="form-group">
            <label for="date_to">Date To</label>
            <input
                type="date"
                id="date_to"
                name="date_to"
                value="<?= reportEscape($dateTo) ?>"
            >
        </div>

        <div class="report-filter-actions">
            <button type="submit" class="btn btn-primary">
                Generate Report
            </button>

            <a href="feeding.php" class="btn btn-secondary">
                Reset
            </a>
        </div>
    </form>
</section>

<?php if ($pageError): ?>

    <div class="report-error">
        Unable to load feeding records. Please try again.
    </div>

<?php elseif ($error === ''): ?>

    <!-- FEEDING SUMMARY -->
    <section class="report-panel">

        <div class="report-panel-heading">
            <div>
                <h2>Feeding Overview</h2>
                <div class="report-muted">
                    Summary of feeding records within the selected date range.
                </div>
            </div>

            <span class="report-muted">
                <?= number_format($summary['total_records']) ?> record(s)
            </span>
        </div>

        <div class="report-stats feeding-overview-stats">

            <?php
            reportStat(
                'Feeding Records',
                (string)$summary['total_records'],
                'Total feeding entries in this period'
            );

            reportStat(
                'Penguins Fed',
                (string)$summary['penguin_count'],
                'Distinct penguins with feeding records'
            );

            reportStat(
                'Finalized Records',
                (string)$summary['finalized'],
                number_format($finalizationRate, 1) . '% of all feeding entries'
            );

            reportStat(
                'Draft Records',
                (string)$summary['draft'],
                'Saved entries not yet finalized'
            );

            reportStat(
                'Total Planned Food',
                number_format($summary['total_planned'], 2) . ' g',
                'Combined planned food quantity'
            );

            reportStat(
                'Total Consumed Food',
                number_format($summary['total_consumed'], 2) . ' g',
                'Combined recorded consumed quantity'
            );

            reportStat(
                'Consumption Rate',
                $consumptionRate === null
                    ? '—'
                    : number_format($consumptionRate, 1) . '%',
                'Consumed quantity compared with planned quantity'
            );

            reportStat(
                'Average Consumed',
                number_format($summary['average_consumed'], 2) . ' g',
                'Average consumed quantity per feeding record'
            );
            ?>

        </div>

        <div class="report-two-col">

            <div>
                <div class="report-panel-heading">
                    <h2>Food Consumption Performance</h2>
                </div>

                <div class="report-chart-list">

                    <?php
                    reportBar(
                        'Consumed',
                        $consumptionRate === null ? 0 : min(100, $consumptionRate),
                        $consumptionRate === null
                            ? 'No planned quantity'
                            : number_format($consumptionRate, 1) . '%'
                    );
                    ?>

                    <?php
                    reportBar(
                        'Finalized',
                        $finalizationRate,
                        number_format($finalizationRate, 1) . '%',
                        'blue'
                    );
                    ?>

                </div>

                <div class="report-legend">
                    Consumption rate = total consumed grams ÷ total planned grams.
                    Finalization rate = finalized feeding records ÷ all feeding records.
                    Draft records are included in food quantity totals because they
                    are saved feeding entries, but are shown separately from finalized
                    records.
                </div>
            </div>

            <div>
                <div class="report-panel-heading">
                    <h2>Record Status</h2>
                </div>

                <div class="report-chart-list">

                    <?php
                    $statusTotal = $summary['finalized'] + $summary['draft'];

                    $draftRate = $statusTotal > 0
                        ? ($summary['draft'] / $statusTotal) * 100
                        : 0;
                    ?>

                    <?php
                    reportBar(
                        'Finalized',
                        $finalizationRate,
                        (string)$summary['finalized'],
                        'blue'
                    );
                    ?>

                    <?php
                    reportBar(
                        'Draft',
                        $draftRate,
                        (string)$summary['draft'],
                        'amber'
                    );
                    ?>

                </div>

                <div class="report-legend">
                    Status distribution is based on the records returned for
                    the selected period.
                </div>
            </div>

        </div>

    </section>

    <!-- FOOD TYPE BREAKDOWN -->
    <section class="report-panel">

        <div class="report-panel-heading">
            <div>
                <h2>Food Type Breakdown</h2>
                <div class="report-muted">
                    Compare how frequently each food type appears and its recorded
                    planned and consumed quantities.
                </div>
            </div>
        </div>

        <?php if (empty($foodTypeCounts)): ?>

            <div class="report-empty">
                No food type information is available for this period.
            </div>

        <?php else: ?>

            <div class="report-chart-list">

                <?php foreach ($foodTypeCounts as $foodType => $data): ?>

                    <?php
                    $foodRate = $foodTypeTotal > 0
                        ? ($data['records'] / $foodTypeTotal) * 100
                        : 0;
                    ?>

                    <?php
                    reportBar(
                        $foodType,
                        $foodRate,
                        number_format($foodRate, 1) . '% (' . $data['records'] . ')'
                    );
                    ?>

                <?php endforeach; ?>

            </div>

            <div class="report-table-wrap" style="margin-top:22px;">
                <table class="data-table">
                    <thead>
                        <tr>
                            <th>Food Type</th>
                            <th>Feeding Records</th>
                            <th>Planned (g)</th>
                            <th>Consumed (g)</th>
                            <th>Consumption Rate</th>
                        </tr>
                    </thead>

                    <tbody>

                        <?php foreach ($foodTypeCounts as $foodType => $data): ?>

                            <?php
                            $typeRate = $data['planned'] > 0
                                ? ($data['consumed'] / $data['planned']) * 100
                                : null;
                            ?>

                            <tr>
                                <td><?= reportEscape($foodType) ?></td>

                                <td>
                                    <?= number_format($data['records']) ?>
                                </td>

                                <td>
                                    <?= number_format($data['planned'], 2) ?>
                                </td>

                                <td>
                                    <?= number_format($data['consumed'], 2) ?>
                                </td>

                                <td>
                                    <?= $typeRate === null
                                        ? '—'
                                        : number_format($typeRate, 1) . '%' ?>
                                </td>
                            </tr>

                        <?php endforeach; ?>

                    </tbody>
                </table>
            </div>

        <?php endif; ?>

    </section>

    <!-- FEEDING RECORD DETAILS -->
    <section class="report-panel">

        <div class="report-panel-heading">
            <div>
                <h2>Feeding Record Details</h2>
                <div class="report-muted">
                    Individual feeding entries included in this report.
                </div>
            </div>

            <span class="report-muted">
                <?= number_format(count($records)) ?> record(s)
            </span>
        </div>

        <?php if (empty($records)): ?>

            <div class="report-empty">
                No feeding records found for this period.
            </div>

        <?php else: ?>

            <div class="report-table-wrap">

                <table class="data-table">

                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Date</th>
                            <th>Time</th>
                            <th>Penguin</th>
                            <th>Food Type</th>
                            <th>Planned (g)</th>
                            <th>Consumed (g)</th>
                            <th>Keeper</th>
                            <th>Status</th>
                            <th>Notes</th>
                        </tr>
                    </thead>

                    <tbody>

                        <?php foreach ($records as $record): ?>

                            <tr>
                                <td>
                                    <?= (int)$record['feeding_id'] ?>
                                </td>

                                <td>
                                    <?= reportEscape($record['feeding_date']) ?>
                                </td>

                                <td>
                                    <?= reportEscape($record['feeding_time'] ?: '-') ?>
                                </td>

                                <td>
                                    <?= reportEscape(
                                        $record['animal_code'] . ' - ' .
                                        $record['penguin_name']
                                    ) ?>
                                </td>

                                <td>
                                    <?= reportEscape($record['food_type']) ?>
                                </td>

                                <td>
                                    <?= number_format(
                                        (float)$record['planned_grams'],
                                        2
                                    ) ?>
                                </td>

                                <td>
                                    <?= number_format(
                                        (float)$record['consumed_grams'],
                                        2
                                    ) ?>
                                </td>

                                <td>
                                    <?= reportEscape(
                                        $record['staff_code'] . ' - ' .
                                        $record['keeper_name']
                                    ) ?>
                                </td>

                                <td>
                                    <span class="report-chip <?= $record['status'] === 'Finalized' ? 'blue' : 'amber' ?>">
                                        <?= reportEscape($record['status']) ?>
                                    </span>
                                </td>

                                <td>
                                    <?= reportEscape($record['notes'] ?: '-') ?>
                                </td>
                            </tr>

                        <?php endforeach; ?>

                    </tbody>

                </table>

            </div>

        <?php endif; ?>

    </section>

<?php endif; ?>

<?php reportRenderEnd(); ?>

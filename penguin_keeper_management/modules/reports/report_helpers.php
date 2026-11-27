<?php
// Shared presentation and date-filter helpers for PKMS Senior Keeper reports.

function reportEscape(mixed $value): string
{
    return htmlspecialchars((string)($value ?? ''), ENT_QUOTES, 'UTF-8');
}

function reportValidDate(string $date): bool
{
    $parsed = DateTime::createFromFormat('!Y-m-d', $date);
    return $parsed !== false && $parsed->format('Y-m-d') === $date;
}

function reportReadDateRange(): array
{
    date_default_timezone_set('Asia/Kuala_Lumpur');

    $from = trim((string)($_GET['date_from'] ?? ''));
    $to = isset($_GET['date_to'])
        ? trim((string)$_GET['date_to'])
        : date('Y-m-d');

    $error = '';

    if ($from !== '' && !reportValidDate($from)) {
        $error = 'Please enter a valid start date.';
    } elseif ($to !== '' && !reportValidDate($to)) {
        $error = 'Please enter a valid end date.';
    } elseif ($from !== '' && $to !== '' && $from > $to) {
        $error = 'The start date cannot be later than the end date.';
    }

    return [$from, $to, $error];
}

function reportDateConditions(string $column, string $from, string $to, array &$params): string
{
    $conditions = '';

    if ($from !== '') {
        $conditions .= " AND {$column} >= ?";
        $params[] = $from;
    }

    if ($to !== '') {
        $conditions .= " AND {$column} <= ?";
        $params[] = $to;
    }

    return $conditions;
}

function reportRenderStart(string $title, string $subtitle, array $breadcrumbs): void
{
    ?>
    <!DOCTYPE html>
    <html lang="en">
    <head>
        <meta charset="UTF-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <title><?= reportEscape($title) ?> | PKMS</title>
        <link rel="stylesheet" href="../../assets/css/style.css">
        <style>
            .report-page{max-width:1600px;margin:0 auto;padding-bottom:35px}
            .report-page .page-header{display:flex;justify-content:space-between;align-items:flex-start;gap:18px;margin-bottom:22px}
            .report-page .page-header h1{margin:0 0 7px;color:#173b53}
            .report-page .page-header p{margin:0;color:#718096}
            .report-panel{background:#fff;border:1px solid #e5ebf0;border-radius:13px;padding:20px;margin:20px 0;box-shadow:0 3px 12px rgba(23,59,83,.04)}
            .report-panel-heading{display:flex;justify-content:space-between;align-items:center;gap:12px;margin-bottom:16px}
            .report-panel-heading h2{font-size:18px;color:#173b53;margin:0}
            .report-muted{font-size:12px;color:#8291a3;margin-top:5px}
            .report-filter{display:flex;align-items:flex-end;gap:14px;flex-wrap:wrap}
            .report-filter .form-group{min-width:175px;flex:1}
            .report-filter label{display:block;margin-bottom:7px;font-size:13px;font-weight:600;color:#405568}
            .report-filter input,.report-filter select{width:100%;min-height:42px;padding:9px 11px;border:1px solid #cbd5df;border-radius:7px;background:#fff}
            .report-filter-actions{display:flex;gap:9px;align-items:center}
            .report-filter .btn{white-space:nowrap}
            .report-stats{display:grid;grid-template-columns:repeat(auto-fit,minmax(165px,1fr));gap:14px;margin:18px 0}
            .report-stat{background:#fff;border:1px solid #e4ebf1;border-radius:12px;padding:17px 18px;min-width:0;box-shadow:0 2px 8px rgba(23,59,83,.035)}
            .report-stat-label{font-size:12px;font-weight:700;letter-spacing:.03em;text-transform:uppercase;color:#75869a}
            .report-stat-value{font-size:27px;font-weight:750;color:#173b53;margin-top:9px;line-height:1.15;overflow-wrap:anywhere}
            .report-stat-note{font-size:12px;color:#8492a6;margin-top:7px;line-height:1.45}
            .report-chart-list{display:grid;gap:14px}
            .report-chart-row{display:grid;grid-template-columns:minmax(120px,190px) minmax(100px,1fr) 95px;gap:13px;align-items:center}
            .report-chart-label{font-size:13px;font-weight:600;color:#405568}
            .report-track{height:12px;background:#edf2f6;border-radius:20px;overflow:hidden}
            .report-fill{height:100%;border-radius:20px;background:#168b83;min-width:0}
            .report-fill.blue{background:#4386b8}.report-fill.amber{background:#d59a3c}.report-fill.red{background:#c66d76}.report-fill.slate{background:#8393a5}
            .report-chart-value{text-align:right;font-size:13px;font-weight:700;color:#34495e}
            .report-legend{font-size:12px;color:#718096;margin-top:12px;line-height:1.6}
            .report-two-col{display:grid;grid-template-columns:repeat(2,minmax(0,1fr));gap:18px}
            .report-table-wrap{overflow-x:auto}
            .report-table-wrap .data-table{min-width:760px}
            .report-empty{padding:24px;text-align:center;color:#8291a3;background:#f8fafc;border-radius:9px}
            .report-error{padding:13px 16px;margin:16px 0;background:#fff1f0;border:1px solid #ffd4d0;border-radius:8px;color:#9b302b}
            .report-chip{display:inline-block;padding:4px 8px;border-radius:20px;background:#eef3f7;color:#405568;font-size:11px;font-weight:700;margin:2px}
            .report-chip.urgent{background:#fde8e8;color:#a62e36}.report-chip.attention{background:#fff1d9;color:#8a5b12}.report-chip.routine{background:#e8f1f8;color:#315f80}
            .report-score{font-size:12px;color:#617489;margin-top:5px}
            .report-json{max-width:260px;max-height:180px;overflow:auto;white-space:pre-wrap;overflow-wrap:anywhere;font-size:11px;background:#f7f9fb;padding:8px;border-radius:6px}
            @media(max-width:800px){.report-two-col{grid-template-columns:1fr}.report-page .page-header{flex-direction:column}.report-chart-row{grid-template-columns:105px minmax(70px,1fr) 75px;gap:8px}}
            @media(max-width:520px){.report-panel{padding:14px}.report-filter .form-group{min-width:100%}.report-filter-actions{width:100%}.report-filter-actions .btn{flex:1;text-align:center}.report-stat-value{font-size:24px}}
        </style>
    </head>
    <body>
    <div class="app-layout">
        <?php include __DIR__ . '/../../includes/sidebar.php'; ?>
        <main class="main-content">
            <?php include __DIR__ . '/../../includes/topbar.php'; ?>
            <div class="page-content report-page">
                <div class="page-header">
                    <div>
                        <h1><?= reportEscape($title) ?></h1>
                        <p><?= reportEscape($subtitle) ?></p>
                    </div>
                    <a href="index.php" class="btn btn-secondary">Back to Reports</a>
                </div>
    <?php
}

function reportRenderFilter(string $action, string $search, string $from, string $to, string $placeholder, string $extraFields = ''): void
{
    ?>
    <section class="search-panel report-panel">
        <form method="GET" action="<?= reportEscape($action) ?>" class="report-filter">
            <div class="form-group">
                <label for="search">Search</label>
                <input type="text" id="search" name="search" value="<?= reportEscape($search) ?>" placeholder="<?= reportEscape($placeholder) ?>">
            </div>
            <?= $extraFields ?>
            <div class="form-group">
                <label for="date_from">Date From</label>
                <input type="date" id="date_from" name="date_from" value="<?= reportEscape($from) ?>">
            </div>
            <div class="form-group">
                <label for="date_to">Date To</label>
                <input type="date" id="date_to" name="date_to" value="<?= reportEscape($to) ?>">
            </div>
            <div class="report-filter-actions">
                <button type="submit" class="btn btn-primary">Apply Filter</button>
                <a href="<?= reportEscape($action) ?>" class="btn btn-secondary">Reset</a>
            </div>
        </form>
    </section>
    <?php
}

function reportStat(string $label, string $value, string $note = ''): void
{
    ?>
    <div class="report-stat">
        <div class="report-stat-label"><?= reportEscape($label) ?></div>
        <div class="report-stat-value"><?= reportEscape($value) ?></div>
        <?php if ($note !== ''): ?><div class="report-stat-note"><?= reportEscape($note) ?></div><?php endif; ?>
    </div>
    <?php
}

function reportBar(string $label, float $percent, string $value, string $color = ''): void
{
    $percent = max(0, min(100, $percent));
    ?>
    <div class="report-chart-row">
        <div class="report-chart-label"><?= reportEscape($label) ?></div>
        <div class="report-track"><div class="report-fill <?= reportEscape($color) ?>" style="width:<?= number_format($percent, 2, '.', '') ?>%"></div></div>
        <div class="report-chart-value"><?= reportEscape($value) ?></div>
    </div>
    <?php
}

function reportRenderEnd(): void
{
    ?>
            </div>
        </main>
    </div>
    </body>
    </html>
    <?php
}
?>

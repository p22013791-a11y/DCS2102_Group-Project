<?php
require_once __DIR__ . '/../../includes/auth.php';
requireRole('Senior Keeper');
require_once __DIR__ . '/report_helpers.php';

[$dateFrom, $dateTo, $error] = reportReadDateRange();
$search = trim((string)($_GET['search'] ?? ''));
$action = trim((string)($_GET['action'] ?? ''));
$allowedActions = ['CREATE', 'UPDATE', 'DELETE', 'ARCHIVE', 'SEND', 'FINALIZE', 'REOPEN', 'SUBMIT', 'REVIEW'];
if ($error === '' && $action !== '' && !in_array($action, $allowedActions, true)) {
    $error = 'Invalid action filter.';
}
$records = [];
$pageError = false;

if ($error === '') {
    try {
        $sql = "
            SELECT a.user_id, a.action, a.entity_type, a.entity_id,
                   a.old_values, a.new_values, a.description, a.created_at,
                   u.staff_code, u.full_name AS keeper_name
            FROM audit_logs a
            INNER JOIN users u ON u.user_id = a.user_id
            WHERE 1 = 1
        ";
        $params = [];
        if ($dateFrom !== '') {
            $sql .= " AND a.created_at >= ?";
            $params[] = $dateFrom . ' 00:00:00';
        }
        if ($dateTo !== '') {
            $sql .= " AND a.created_at <= ?";
            $params[] = $dateTo . ' 23:59:59';
        }
        if ($action !== '') {
            $sql .= " AND a.action = ?";
            $params[] = $action;
        }
        if ($search !== '') {
            $sql .= " AND (u.staff_code LIKE ? OR u.full_name LIKE ? OR a.entity_type LIKE ? OR CAST(a.entity_id AS CHAR) LIKE ? OR a.description LIKE ? OR a.old_values LIKE ? OR a.new_values LIKE ?)";
            $keyword = "%{$search}%";
            for ($i = 0; $i < 7; $i++) $params[] = $keyword;
        }
        $sql .= " ORDER BY a.created_at DESC";
        $stmt = $pdo->prepare($sql);
        $stmt->execute($params);
        $records = $stmt->fetchAll(PDO::FETCH_ASSOC);
    } catch (PDOException $e) {
        error_log('Audit log report error: ' . $e->getMessage());
        $pageError = true;
    }
}

$actionCounts = [];
$keepers = [];
$entities = [];
foreach ($records as $r) {
    $key = $r['action'] ?: 'OTHER';
    $actionCounts[$key] = ($actionCounts[$key] ?? 0) + 1;
    $keepers[(int)$r['user_id']] = true;
    $entities[$r['entity_type'] ?: 'Unspecified'] = true;
}
arsort($actionCounts);
$topAction = $actionCounts ? array_key_first($actionCounts) : '—';
$extra = '<div class="form-group"><label for="action">Action</label><select id="action" name="action"><option value="">All Actions</option>';
foreach ($allowedActions as $item) {
    $extra .= '<option value="' . reportEscape($item) . '"' . ($action === $item ? ' selected' : '') . '>' . reportEscape($item) . '</option>';
}
$extra .= '</select></div>';

reportRenderStart('Audit Logs Report', 'Track important keeper actions and changes made across PKMS.', ['Reports', 'Audit Logs Report']);
if ($error !== ''): ?><div class="report-error"><?= reportEscape($error) ?></div><?php endif; ?>
<?php reportRenderFilter('audit_logs.php', $search, $dateFrom, $dateTo, 'Keeper, module, record ID or description...', $extra); ?>
<?php if ($pageError): ?><div class="report-error">Unable to load audit logs. Please try again.</div><?php else: ?>
<section class="report-panel">
    <div class="report-panel-heading"><div><h2>System Activity Overview</h2><div class="report-muted">Audit events provide traceability for important changes and keeper actions.</div></div></div>
    <div class="report-stats">
        <?php reportStat('Total Activities', (string)count($records), 'Events recorded in the selected period'); ?>
        <?php reportStat('Keepers Involved', (string)count($keepers), 'Distinct accounts associated with these events'); ?>
        <?php reportStat('Modules / Entity Types', (string)count($entities), 'Different record categories affected'); ?>
        <?php reportStat('Most Frequent Action', (string)$topAction, $actionCounts ? (string)reset($actionCounts) . ' occurrence(s)' : 'No activity'); ?>
    </div>
</section>
<div class="report-two-col">
    <section class="report-panel">
        <div class="report-panel-heading"><h2>Activity by Action</h2></div>
        <?php if (!$actionCounts): ?><div class="report-empty">No audit activity found.</div><?php else: ?>
        <div class="report-chart-list">
            <?php $maxAction = max($actionCounts); foreach ($actionCounts as $label => $count): ?>
                <?php reportBar($label, ($count / $maxAction) * 100, (string)$count); ?>
            <?php endforeach; ?>
        </div><?php endif; ?>
    </section>
    <section class="report-panel">
        <div class="report-panel-heading"><h2>Modules Affected</h2></div>
        <?php if (!$entities): ?><div class="report-empty">No affected modules recorded.</div><?php else: ?>
        <div class="report-chart-list">
            <?php foreach (array_keys($entities) as $entity): ?>
                <?php $entityCount = 0; foreach ($records as $r) if (($r['entity_type'] ?: 'Unspecified') === $entity) $entityCount++; ?>
                <?php reportBar($entity, count($records) ? ($entityCount / count($records)) * 100 : 0, (string)$entityCount, 'blue'); ?>
            <?php endforeach; ?>
        </div><?php endif; ?>
    </section>
</div>
<section class="report-panel">
    <div class="report-panel-heading"><h2>Audit Activity Details</h2><span class="report-muted"><?= count($records) ?> event(s)</span></div>
    <?php if (!$records): ?><div class="report-empty">No audit records found for this period.</div><?php else: ?>
    <div class="report-table-wrap"><table class="data-table">
        <thead><tr><th>Date &amp; Time</th><th>Keeper</th><th>Action</th><th>Entity Type</th><th>Entity ID</th><th>Description</th><th>Previous Values</th><th>New Values</th></tr></thead>
        <tbody><?php foreach ($records as $r): ?><tr>
            <td><?= reportEscape($r['created_at']) ?></td>
            <td><?= reportEscape($r['staff_code'] . ' - ' . $r['keeper_name']) ?></td>
            <td><span class="report-chip"><?= reportEscape($r['action']) ?></span></td>
            <td><?= reportEscape($r['entity_type']) ?></td><td><?= reportEscape($r['entity_id']) ?></td>
            <td><?= reportEscape($r['description']) ?></td>
            <td><details><summary>View</summary><pre class="report-json"><?= reportEscape($r['old_values']) ?></pre></details></td>
            <td><details><summary>View</summary><pre class="report-json"><?= reportEscape($r['new_values']) ?></pre></details></td>
        </tr><?php endforeach; ?></tbody>
    </table></div><?php endif; ?>
</section>
<?php endif; ?>
<?php reportRenderEnd(); ?>

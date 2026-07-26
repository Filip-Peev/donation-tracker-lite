<?php
session_start();
$pageTitle = 'Admin Dashboard';
require_once __DIR__ . '/../includes/header.php';
require_admin();

$total_items = db()->query("SELECT COUNT(*) FROM items")->fetchColumn();
$total_locations = db()->query("SELECT COUNT(*) FROM locations")->fetchColumn();
$total_inspections = db()->query("SELECT COUNT(*) FROM inspections")->fetchColumn();

$overdue = db()->query("
    SELECT * FROM (
        SELECT i.*, l.name AS location_name,
            (SELECT ins.inspected_at FROM inspections ins WHERE ins.item_id = i.id ORDER BY ins.inspected_at DESC LIMIT 1) AS last_inspected
        FROM items i
        LEFT JOIN locations l ON i.location_id = l.id
        WHERE i.status IN ('donated', 'in_use')
    ) sub
    WHERE sub.last_inspected IS NULL OR sub.last_inspected < datetime('now', '-3 months')
    ORDER BY sub.last_inspected ASC
")->fetchAll();

$recent_inspections = db()->query("
    SELECT ins.*, i.name AS item_name, i.type AS item_type, ins.inspector_name
    FROM inspections ins
    JOIN items i ON ins.item_id = i.id
    ORDER BY ins.inspected_at DESC
    LIMIT 10
")->fetchAll();
?>

<div class="d-flex justify-content-between align-items-center mb-4">
    <h3><i class="bi bi-speedometer2"></i> Dashboard</h3>
</div>

<div class="row mb-4">
    <div class="col-md-3">
        <div class="card stat-card">
            <div class="stat-number"><?= $total_items ?></div>
            <div class="stat-label">Total Items</div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="card stat-card">
            <div class="stat-number"><?= $total_locations ?></div>
            <div class="stat-label">Locations</div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="card stat-card">
            <div class="stat-number"><?= $total_inspections ?></div>
            <div class="stat-label">Inspections Done</div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="card stat-card">
            <div class="stat-number text-danger"><?= count($overdue) ?></div>
            <div class="stat-label">Overdue Inspections</div>
        </div>
    </div>
</div>

<?php if (count($overdue) > 0): ?>
<div class="card mb-4 overdue">
    <div class="card-header bg-danger text-white">
        <i class="bi bi-exclamation-triangle"></i> Items Due for Inspection
    </div>
    <div class="card-body p-0">
        <table class="table table-hover mb-0">
            <thead>
                <tr>
                    <th>Item</th>
                    <th>Type</th>
                    <th>Location</th>
                    <th>Last Inspected</th>
                    <th></th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($overdue as $row): ?>
                <tr>
                    <td><strong><?= e($row['name']) ?></strong></td>
                    <td><span class="badge bg-secondary"><?= e(ucfirst($row['type'])) ?></span></td>
                    <td><?= e($row['location_name'] ?? 'Unassigned') ?></td>
                    <td class="text-danger">
                        <?= $row['last_inspected'] ? date('M j, Y', strtotime($row['last_inspected'])) : '<em>Never</em>' ?>
                    </td>
                    <td>
                        <a href="/donation-tracker-lite/admin/items.php?edit=<?= $row['id'] ?>" class="btn btn-sm btn-outline-primary">View</a>
                    </td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>
<?php endif; ?>

<div class="row">
    <div class="col-lg-8">
        <div class="card">
            <div class="card-header">
                <i class="bi bi-clock-history"></i> Recent Inspections
            </div>
            <div class="card-body p-0">
                <?php if (count($recent_inspections) > 0): ?>
                <table class="table table-hover mb-0">
                    <thead>
                        <tr>
                            <th>Item</th>
                            <th>Status</th>
                            <th>Inspector</th>
                            <th>Date</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($recent_inspections as $row): ?>
                        <tr>
                            <td>
                                <i class="bi bi-<?= $row['item_type'] === 'laptop' ? 'laptop' : ($row['item_type'] === 'router' ? 'wifi' : 'box') ?>"></i>
                                <?= e($row['item_name']) ?>
                            </td>
                            <td>
                                <?php
                                $badge = match($row['status']) {
                                    'working' => 'success',
                                    'damaged' => 'warning',
                                    'missing' => 'danger',
                                    'replaced' => 'info',
                                    default => 'secondary'
                                };
                                ?>
                                <span class="badge bg-<?= $badge ?> badge-status"><?= e(ucfirst($row['status'])) ?></span>
                            </td>
                            <td><?= e($row['inspector_name']) ?></td>
                            <td><?= date('M j, Y g:i A', strtotime($row['inspected_at'])) ?></td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
                <?php else: ?>
                <p class="text-muted p-3 mb-0">No inspections recorded yet.</p>
                <?php endif; ?>
            </div>
        </div>
    </div>
    <div class="col-lg-4">
        <div class="card">
            <div class="card-header">
                <i class="bi bi-lightning"></i> Quick Actions
            </div>
            <div class="card-body">
                <a href="/donation-tracker-lite/admin/items.php" class="btn btn-primary w-100 mb-2">
                    <i class="bi bi-plus-circle"></i> Add New Item
                </a>
                <a href="/donation-tracker-lite/admin/locations.php" class="btn btn-outline-primary w-100 mb-2">
                    <i class="bi bi-geo-alt"></i> Manage Locations
                </a>
                <a href="/donation-tracker-lite/admin/inspections.php" class="btn btn-outline-primary w-100 mb-2">
                    <i class="bi bi-clipboard-check"></i> View All Inspections
                </a>
                <a href="/donation-tracker-lite/admin/items.php?export=1" class="btn btn-outline-secondary w-100">
                    <i class="bi bi-download"></i> Export JSON
                </a>
            </div>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>

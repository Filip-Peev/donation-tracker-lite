<?php
session_start();
$pageTitle = 'Inspections';
require_once __DIR__ . '/../includes/header.php';
require_admin();

// Handle delete
if (isset($_GET['delete']) && verify_csrf()) {
    $stmt = db()->prepare("DELETE FROM inspections WHERE id = ?");
    $stmt->execute([$_GET['delete']]);
    flash('success', 'Inspection record deleted.');
    redirect('/donation-tracker-lite/admin/inspections.php');
}

$filter_item = $_GET['item'] ?? '';
$filter_status = $_GET['status'] ?? '';

$sql = "
    SELECT ins.*, i.name AS item_name, i.type AS item_type, ins.inspector_name
    FROM inspections ins
    JOIN items i ON ins.item_id = i.id
    WHERE 1=1
";
$params = [];

if ($filter_item) {
    $sql .= " AND ins.item_id = ?";
    $params[] = $filter_item;
}
if ($filter_status) {
    $sql .= " AND ins.status = ?";
    $params[] = $filter_status;
}

$sql .= " ORDER BY ins.inspected_at DESC";
$stmt = db()->prepare($sql);
$stmt->execute($params);
$inspections = $stmt->fetchAll();

$items = db()->query("SELECT id, name FROM items ORDER BY name")->fetchAll();
?>

<div class="d-flex justify-content-between align-items-center mb-4">
    <h3><i class="bi bi-clipboard-check"></i> Inspections</h3>
</div>

<div class="card mb-4">
    <div class="card-body">
        <form method="GET" class="row g-3 align-items-end">
            <div class="col-md-4">
                <label class="form-label">Filter by Item</label>
                <select name="item" class="form-select">
                    <option value="">All Items</option>
                    <?php foreach ($items as $item): ?>
                    <option value="<?= $item['id'] ?>" <?= $filter_item == $item['id'] ? 'selected' : '' ?>><?= e($item['name']) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="col-md-4">
                <label class="form-label">Filter by Status</label>
                <select name="status" class="form-select">
                    <option value="">All Statuses</option>
                    <?php foreach (['working','damaged','missing','replaced'] as $s): ?>
                    <option value="<?= $s ?>" <?= $filter_status === $s ? 'selected' : '' ?>><?= ucfirst($s) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="col-md-4">
                <button type="submit" class="btn btn-primary">Filter</button>
                <a href="/donation-tracker-lite/admin/inspections.php" class="btn btn-outline-secondary">Clear</a>
            </div>
        </form>
    </div>
</div>

<div class="card">
    <div class="card-body p-0">
        <?php if (count($inspections) > 0): ?>
        <table class="table table-hover table-sm mb-0">
            <thead>
                <tr>
                    <th>ID</th>
                    <th>Item</th>
                    <th>Status</th>
                    <th>Inspector</th>
                    <th>Date</th>
                    <th>Photo</th>
                    <th>Notes</th>
                    <th></th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($inspections as $row): ?>
                <tr>
                    <td><?= $row['id'] ?></td>
                    <td>
                        <i class="bi bi-<?= $row['item_type'] === 'laptop' ? 'laptop' : 'box' ?>"></i>
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
                        <span class="badge bg-<?= $badge ?>"><?= e(ucfirst($row['status'])) ?></span>
                    </td>
                    <td><?= e($row['inspector_name']) ?></td>
                    <td><?= date('M j, Y g:i A', strtotime($row['inspected_at'])) ?></td>
                    <td>
                        <?php if ($row['photo_path']): ?>
                        <a href="/donation-tracker-lite/uploads/<?= e($row['photo_path']) ?>" target="_blank" class="btn btn-sm btn-outline-secondary">
                            <i class="bi bi-image"></i>
                        </a>
                        <?php else: ?>
                        -
                        <?php endif; ?>
                    </td>
                    <td><small><?= e($row['notes'] ?? '') ?></small></td>
                    <td>
                        <a href="?delete=<?= $row['id'] ?>&csrf_token=<?= csrf_token() ?>"
                           class="btn btn-sm btn-outline-danger"
                           onclick="return confirm('Delete this record?')">
                            <i class="bi bi-trash"></i>
                        </a>
                    </td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
        <?php else: ?>
        <p class="text-muted p-3 mb-0">No inspections found.</p>
        <?php endif; ?>
    </div>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>

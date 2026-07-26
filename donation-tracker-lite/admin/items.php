<?php
session_start();
require_once __DIR__ . '/../config/database.php';
require_admin();

// Handle export before any HTML output
if (isset($_GET['export'])) {
    $rows = db()->query("
        SELECT i.*, l.name AS location_name, l.address AS location_address,
            (SELECT ins.status FROM inspections ins WHERE ins.item_id = i.id ORDER BY ins.inspected_at DESC LIMIT 1) AS last_inspection_status,
            (SELECT ins.inspected_at FROM inspections ins WHERE ins.item_id = i.id ORDER BY ins.inspected_at DESC LIMIT 1) AS last_inspected
        FROM items i
        LEFT JOIN locations l ON i.location_id = l.id ORDER BY i.donation_date DESC
    ")->fetchAll();
    header('Content-Type: application/json');
    header('Content-Disposition: attachment; filename=items_export_' . date('Y-m-d') . '.json');
    echo json_encode($rows, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
    exit;
}

$pageTitle = 'Manage Items';
require_once __DIR__ . '/../includes/header.php';

$locations = db()->query("SELECT id, name FROM locations ORDER BY name")->fetchAll();

// Handle delete
if (isset($_GET['delete']) && verify_csrf()) {
    $stmt = db()->prepare("DELETE FROM items WHERE id = ?");
    $stmt->execute([$_GET['delete']]);
    flash('success', 'Item deleted.');
    redirect('/donation-tracker-lite/admin/items.php');
}

// Handle form submit (add/edit)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && verify_csrf()) {
    $id = $_POST['id'] ?? '';
    $data = [
        'name' => trim($_POST['name']),
        'type' => $_POST['type'],
        'serial_number' => trim($_POST['serial_number']),
        'description' => trim($_POST['description']),
        'donor_name' => trim($_POST['donor_name']),
        'donor_email' => trim($_POST['donor_email']),
        'donation_date' => $_POST['donation_date'],
        'location_id' => $_POST['location_id'] ?: null,
        'status' => $_POST['status'],
    ];

    if ($id) {
        $stmt = db()->prepare("UPDATE items SET name=?, type=?, serial_number=?, description=?, donor_name=?, donor_email=?, donation_date=?, location_id=?, status=? WHERE id=?");
        $stmt->execute([...array_values($data), $id]);
        flash('success', 'Item updated.');
    } else {
        $data['qr_token'] = bin2hex(random_bytes(32));
        $cols = implode(', ', array_keys($data));
        $placeholders = implode(', ', array_fill(0, count($data), '?'));
        $stmt = db()->prepare("INSERT INTO items ($cols) VALUES ($placeholders)");
        $stmt->execute(array_values($data));
        flash('success', 'Item added.');
    }
    redirect('/donation-tracker-lite/admin/items.php');
}

// Fetch item for editing
$edit_item = null;
if (isset($_GET['edit'])) {
    $stmt = db()->prepare("SELECT * FROM items WHERE id = ?");
    $stmt->execute([$_GET['edit']]);
    $edit_item = $stmt->fetch();
}

$items = db()->query("
    SELECT i.*, l.name AS location_name FROM items i
    LEFT JOIN locations l ON i.location_id = l.id
    ORDER BY i.donation_date DESC
")->fetchAll();
?>

<div class="d-flex justify-content-between align-items-center mb-4">
    <h3><i class="bi bi-laptop"></i> Items</h3>
    <a href="/donation-tracker-lite/admin/items.php?export=1" class="btn btn-outline-secondary btn-sm">
        <i class="bi bi-download"></i> Export JSON
    </a>
</div>

<div class="row">
    <div class="col-lg-5">
        <div class="card mb-4">
            <div class="card-header"><?= $edit_item ? 'Edit Item' : 'Add New Item' ?></div>
            <div class="card-body">
                <form method="POST">
                    <input type="hidden" name="csrf_token" value="<?= csrf_token() ?>">
                    <?php if ($edit_item): ?>
                    <input type="hidden" name="id" value="<?= $edit_item['id'] ?>">
                    <?php endif; ?>

                    <div class="mb-3">
                        <label class="form-label">Name *</label>
                        <input type="text" name="name" class="form-control" required value="<?= e($edit_item['name'] ?? '') ?>">
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Type *</label>
                        <select name="type" class="form-select" required>
                            <?php foreach (['laptop','router','tablet','desktop','monitor','other'] as $t): ?>
                            <option value="<?= $t ?>" <?= ($edit_item['type'] ?? '') === $t ? 'selected' : '' ?>><?= ucfirst($t) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Serial Number</label>
                        <input type="text" name="serial_number" class="form-control" value="<?= e($edit_item['serial_number'] ?? '') ?>">
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Description</label>
                        <textarea name="description" class="form-control" rows="2"><?= e($edit_item['description'] ?? '') ?></textarea>
                    </div>
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Donor Name</label>
                            <input type="text" name="donor_name" class="form-control" value="<?= e($edit_item['donor_name'] ?? '') ?>">
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Donor Email</label>
                            <input type="email" name="donor_email" class="form-control" value="<?= e($edit_item['donor_email'] ?? '') ?>">
                        </div>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Donation Date *</label>
                        <input type="date" name="donation_date" class="form-control" required value="<?= e($edit_item['donation_date'] ?? date('Y-m-d')) ?>">
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Location</label>
                        <select name="location_id" class="form-select">
                            <option value="">-- None --</option>
                            <?php foreach ($locations as $loc): ?>
                            <option value="<?= $loc['id'] ?>" <?= ($edit_item['location_id'] ?? '') == $loc['id'] ? 'selected' : '' ?>><?= e($loc['name']) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Status *</label>
                        <select name="status" class="form-select" required>
                            <?php foreach (['donated','in_use','returned','retired','lost'] as $s): ?>
                            <option value="<?= $s ?>" <?= ($edit_item['status'] ?? 'donated') === $s ? 'selected' : '' ?>><?= ucfirst(str_replace('_', ' ', $s)) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <button type="submit" class="btn btn-primary w-100"><?= $edit_item ? 'Update Item' : 'Add Item' ?></button>
                    <?php if ($edit_item): ?>
                    <a href="/donation-tracker-lite/admin/items.php" class="btn btn-outline-secondary w-100 mt-2">Cancel</a>
                    <?php endif; ?>
                </form>
            </div>
        </div>
    </div>

    <div class="col-lg-7">
        <div class="card">
            <div class="card-header">All Items (<?= count($items) ?>)</div>
            <div class="card-body p-0">
                <table class="table table-hover table-sm mb-0">
                    <thead>
                        <tr>
                            <th>#</th>
                            <th>Name</th>
                            <th>Type</th>
                            <th>Donor</th>
                            <th>Location</th>
                            <th>Status</th>
                            <th>QR</th>
                            <th></th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($items as $item): ?>
                        <tr class="<?= ($item['status'] === 'lost') ? 'table-danger' : '' ?>">
                            <td><?= $item['id'] ?></td>
                            <td>
                                <a href="/donation-tracker-lite/admin/items.php?edit=<?= $item['id'] ?>"><?= e($item['name']) ?></a>
                            </td>
                            <td><small class="text-muted"><?= e(ucfirst($item['type'])) ?></small></td>
                            <td><?= e($item['donor_name']) ?></td>
                            <td><?= e($item['location_name'] ?? '-') ?></td>
                            <td>
                                <?php
                                $badge = match($item['status']) {
                                    'in_use' => 'success',
                                    'donated' => 'primary',
                                    'returned' => 'info',
                                    'retired' => 'secondary',
                                    'lost' => 'danger',
                                    default => 'secondary'
                                };
                                ?>
                                <span class="badge bg-<?= $badge ?> badge-status"><?= e(ucfirst(str_replace('_', ' ', $item['status']))) ?></span>
                            </td>
                            <td>
                                <a href="/donation-tracker-lite/api/qr.php?token=<?= e($item['qr_token']) ?>" target="_blank" class="btn btn-sm btn-outline-secondary" title="View QR Code">
                                    <i class="bi bi-qr-code"></i>
                                </a>
                            </td>
                            <td>
                                <a href="?delete=<?= $item['id'] ?>&csrf_token=<?= csrf_token() ?>"
                                   class="btn btn-sm btn-outline-danger"
                                   onclick="return confirm('Delete this item?')">
                                    <i class="bi bi-trash"></i>
                                </a>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>

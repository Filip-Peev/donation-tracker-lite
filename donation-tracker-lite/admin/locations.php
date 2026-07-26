<?php
session_start();
$pageTitle = 'Manage Locations';
require_once __DIR__ . '/../includes/header.php';
require_admin();

// Handle delete
if (isset($_GET['delete']) && verify_csrf()) {
    $stmt = db()->prepare("DELETE FROM locations WHERE id = ?");
    $stmt->execute([$_GET['delete']]);
    flash('success', 'Location deleted.');
    redirect('/donation-tracker-lite/admin/locations.php');
}

// Handle form submit
if ($_SERVER['REQUEST_METHOD'] === 'POST' && verify_csrf()) {
    $id = $_POST['id'] ?? '';
    $data = [
        'name' => trim($_POST['name']),
        'address' => trim($_POST['address']),
        'contact_name' => trim($_POST['contact_name']),
        'contact_email' => trim($_POST['contact_email']),
        'contact_phone' => trim($_POST['contact_phone']),
    ];

    if ($id) {
        $stmt = db()->prepare("UPDATE locations SET name=?, address=?, contact_name=?, contact_email=?, contact_phone=? WHERE id=?");
        $stmt->execute([...array_values($data), $id]);
        flash('success', 'Location updated.');
    } else {
        $cols = implode(', ', array_keys($data));
        $placeholders = implode(', ', array_fill(0, count($data), '?'));
        $stmt = db()->prepare("INSERT INTO locations ($cols) VALUES ($placeholders)");
        $stmt->execute(array_values($data));
        flash('success', 'Location added.');
    }
    redirect('/donation-tracker-lite/admin/locations.php');
}

// Fetch for editing
$edit_loc = null;
if (isset($_GET['edit'])) {
    $stmt = db()->prepare("SELECT * FROM locations WHERE id = ?");
    $stmt->execute([$_GET['edit']]);
    $edit_loc = $stmt->fetch();
}

$locations = db()->query("
    SELECT l.*, COUNT(i.id) AS item_count
    FROM locations l
    LEFT JOIN items i ON i.location_id = l.id
    GROUP BY l.id
    ORDER BY l.name
")->fetchAll();
?>

<div class="d-flex justify-content-between align-items-center mb-4">
    <h3><i class="bi bi-geo-alt"></i> Locations</h3>
</div>

<div class="row">
    <div class="col-lg-4">
        <div class="card mb-4">
            <div class="card-header"><?= $edit_loc ? 'Edit Location' : 'Add Location' ?></div>
            <div class="card-body">
                <form method="POST">
                    <input type="hidden" name="csrf_token" value="<?= csrf_token() ?>">
                    <?php if ($edit_loc): ?>
                    <input type="hidden" name="id" value="<?= $edit_loc['id'] ?>">
                    <?php endif; ?>

                    <div class="mb-3">
                        <label class="form-label">Name *</label>
                        <input type="text" name="name" class="form-control" required value="<?= e($edit_loc['name'] ?? '') ?>">
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Address</label>
                        <input type="text" name="address" class="form-control" value="<?= e($edit_loc['address'] ?? '') ?>">
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Contact Name</label>
                        <input type="text" name="contact_name" class="form-control" value="<?= e($edit_loc['contact_name'] ?? '') ?>">
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Contact Email</label>
                        <input type="email" name="contact_email" class="form-control" value="<?= e($edit_loc['contact_email'] ?? '') ?>">
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Contact Phone</label>
                        <input type="text" name="contact_phone" class="form-control" value="<?= e($edit_loc['contact_phone'] ?? '') ?>">
                    </div>

                    <button type="submit" class="btn btn-primary w-100"><?= $edit_loc ? 'Update' : 'Add Location' ?></button>
                    <?php if ($edit_loc): ?>
                    <a href="/donation-tracker-lite/admin/locations.php" class="btn btn-outline-secondary w-100 mt-2">Cancel</a>
                    <?php endif; ?>
                </form>
            </div>
        </div>
    </div>

    <div class="col-lg-8">
        <div class="card">
            <div class="card-header">All Locations (<?= count($locations) ?>)</div>
            <div class="card-body p-0">
                <table class="table table-hover mb-0">
                    <thead>
                        <tr>
                            <th>Name</th>
                            <th>Address</th>
                            <th>Contact</th>
                            <th>Items</th>
                            <th></th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($locations as $loc): ?>
                        <tr>
                            <td><strong><?= e($loc['name']) ?></strong></td>
                            <td><?= e($loc['address'] ?? '-') ?></td>
                            <td>
                                <?php if ($loc['contact_name']): ?>
                                <?= e($loc['contact_name']) ?><br>
                                <small class="text-muted"><?= e($loc['contact_email'] ?? '') ?></small>
                                <?php else: ?>
                                -
                                <?php endif; ?>
                            </td>
                            <td><span class="badge bg-primary"><?= $loc['item_count'] ?></span></td>
                            <td>
                                <a href="?edit=<?= $loc['id'] ?>" class="btn btn-sm btn-outline-primary"><i class="bi bi-pencil"></i></a>
                                <a href="?delete=<?= $loc['id'] ?>&csrf_token=<?= csrf_token() ?>"
                                   class="btn btn-sm btn-outline-danger"
                                   onclick="return confirm('Delete this location?')"><i class="bi bi-trash"></i></a>
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

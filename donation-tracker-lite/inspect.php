<?php
session_start();
require_once __DIR__ . '/config/database.php';

$token = $_GET['token'] ?? null;
if (!$token) {
    redirect('/donation-tracker-lite/');
}

$item = db()->prepare("
    SELECT i.*, l.name AS location_name, l.address AS location_address
    FROM items i
    LEFT JOIN locations l ON i.location_id = l.id
    WHERE i.qr_token = ?
");
$item->execute([$token]);
$item = $item->fetch();

if (!$item) {
    redirect('/donation-tracker-lite/');
}

$success = '';

if (isset($_GET['submitted'])) {
    $success = 'Inspection submitted. Thank you!';
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $inspector_name = trim($_POST['inspector_name'] ?? '');
    $status = $_POST['status'] ?? '';
    $notes = trim($_POST['notes'] ?? '');
    $photo_path = null;

    if (!$inspector_name || !$status) {
        $error = 'Please fill in your name and select an inspection status.';
    } else {
        if (isset($_FILES['photo']) && $_FILES['photo']['error'] === UPLOAD_ERR_OK) {
            $allowed = ['image/jpeg', 'image/png', 'image/webp'];
            if (in_array($_FILES['photo']['type'], $allowed)) {
                $ext = pathinfo($_FILES['photo']['name'], PATHINFO_EXTENSION);
                $filename = 'inspection_' . time() . '_' . bin2hex(random_bytes(8)) . '.' . $ext;
                move_uploaded_file($_FILES['photo']['tmp_name'], UPLOAD_DIR . $filename);
                $photo_path = $filename;
            }
        }

        $stmt = db()->prepare("INSERT INTO inspections (item_id, inspector_name, status, photo_path, notes) VALUES (?, ?, ?, ?, ?)");
        $stmt->execute([$item['id'], $inspector_name ?: null, $status, $photo_path, $notes]);

        $new_status = $item['status'];
        if ($status === 'missing') $new_status = 'lost';
        elseif ($status === 'replaced') $new_status = 'retired';
        elseif ($status === 'working' && $item['status'] === 'donated') $new_status = 'in_use';

        if ($new_status !== $item['status']) {
            db()->prepare("UPDATE items SET status = ? WHERE id = ?")->execute([$new_status, $item['id']]);
        }

        redirect('/donation-tracker-lite/inspect.php?token=' . urlencode($token) . '&submitted=1');
    }
}

$history = db()->prepare("
    SELECT * FROM inspections WHERE item_id = ? ORDER BY inspected_at DESC LIMIT 5
");
$history->execute([$item['id']]);
$history = $history->fetchAll();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Inspect: <?= e($item['name']) ?> - Donation Tracker</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css" rel="stylesheet">
    <style>
        body { background-color: #f5f7fa; }
        .card { border: none; box-shadow: 0 1px 3px rgba(0,0,0,0.08); }
    </style>
</head>
<body>
    <nav class="navbar navbar-dark bg-dark">
        <div class="container">
            <span class="navbar-brand"><i class="bi bi-box-seam"></i> Donation Tracker</span>
        </div>
    </nav>

    <div class="container py-5" style="max-width: 700px;">
        <h4 class="mb-2">
            <i class="bi bi-<?= $item['type'] === 'laptop' ? 'laptop' : ($item['type'] === 'router' ? 'wifi' : 'box') ?>"></i>
            <?= e($item['name']) ?>
        </h4>
        <p class="text-muted mb-4">
            <?= e(ucfirst($item['type'])) ?>
            <?php if ($item['location_name']): ?>
            | <i class="bi bi-geo-alt"></i> <?= e($item['location_name']) ?>
            <?php endif; ?>
            <?php if ($item['serial_number']): ?>
            | S/N: <?= e($item['serial_number']) ?>
            <?php endif; ?>
        </p>

        <?php if ($success): ?>
        <div class="alert alert-success"><i class="bi bi-check-circle"></i> <?= $success ?></div>
        <?php endif; ?>
        <?php if (!empty($error)): ?>
        <div class="alert alert-danger"><i class="bi bi-exclamation-circle"></i> <?= e($error) ?></div>
        <?php endif; ?>

        <div class="card mb-4">
            <div class="card-header"><i class="bi bi-clipboard-check"></i> Record Inspection</div>
            <div class="card-body">
                <form method="POST" enctype="multipart/form-data">
                    <div class="mb-3">
                        <label class="form-label">Your Name *</label>
                        <input type="text" name="inspector_name" class="form-control" placeholder="e.g. Maria" required value="<?= e($_POST['inspector_name'] ?? '') ?>">
                    </div>

                    <div class="mb-3">
                        <label class="form-label">Item Status *</label>
                        <div class="d-flex gap-3 flex-wrap">
                            <div class="form-check">
                                <input class="form-check-input" type="radio" name="status" value="working" id="s_working" required>
                                <label class="form-check-label" for="s_working"><span class="badge bg-success fs-6">Working</span></label>
                            </div>
                            <div class="form-check">
                                <input class="form-check-input" type="radio" name="status" value="damaged" id="s_damaged">
                                <label class="form-check-label" for="s_damaged"><span class="badge bg-warning text-dark fs-6">Damaged</span></label>
                            </div>
                            <div class="form-check">
                                <input class="form-check-input" type="radio" name="status" value="missing" id="s_missing">
                                <label class="form-check-label" for="s_missing"><span class="badge bg-danger fs-6">Missing</span></label>
                            </div>
                            <div class="form-check">
                                <input class="form-check-input" type="radio" name="status" value="replaced" id="s_replaced">
                                <label class="form-check-label" for="s_replaced"><span class="badge bg-info fs-6">Replaced</span></label>
                            </div>
                        </div>
                    </div>

                    <div class="mb-3">
                        <label class="form-label">Photo Evidence</label>
                        <input type="file" name="photo" class="form-control" accept="image/*" capture="environment">
                        <small class="text-muted">Take a photo with your phone camera</small>
                    </div>

                    <div class="mb-3">
                        <label class="form-label">Notes</label>
                        <textarea name="notes" class="form-control" rows="2" placeholder="Any observations..."></textarea>
                    </div>

                    <button type="submit" class="btn btn-primary btn-lg w-100">
                        <i class="bi bi-send"></i> Submit Inspection
                    </button>
                </form>
            </div>
        </div>

        <?php if (count($history) > 0): ?>
        <div class="card">
            <div class="card-header">Recent Inspections</div>
            <div class="card-body p-0">
                <div class="list-group list-group-flush">
                    <?php foreach ($history as $h): ?>
                    <div class="list-group-item">
                        <div class="d-flex justify-content-between align-items-center">
                            <div>
                                <?php
                                $badge = match($h['status']) {
                                    'working' => 'success',
                                    'damaged' => 'warning',
                                    'missing' => 'danger',
                                    'replaced' => 'info',
                                    default => 'secondary'
                                };
                                ?>
                                <span class="badge bg-<?= $badge ?>"><?= e(ucfirst($h['status'])) ?></span>
                                <?php if ($h['inspector_name']): ?>
                                <span class="ms-2 text-muted">by <?= e($h['inspector_name']) ?></span>
                                <?php endif; ?>
                            </div>
                            <small class="text-muted"><?= date('M j, Y g:i A', strtotime($h['inspected_at'])) ?></small>
                        </div>
                        <?php if ($h['photo_path']): ?>
                        <div class="mt-2">
                            <img src="/donation-tracker-lite/uploads/<?= e($h['photo_path']) ?>" class="img-thumbnail" style="max-height: 120px;">
                        </div>
                        <?php endif; ?>
                        <?php if ($h['notes']): ?>
                        <div><small class="text-muted"><?= e($h['notes']) ?></small></div>
                        <?php endif; ?>
                    </div>
                    <?php endforeach; ?>
                </div>
            </div>
        </div>
        <?php endif; ?>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>

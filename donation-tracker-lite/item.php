<?php
$pageTitle = 'Item Details';
require_once __DIR__ . '/config/database.php';

$token = $_GET['token'] ?? null;
if (!$token) {
    header('Location: /donation-tracker-lite/check.php');
    exit;
}

$stmt = db()->prepare("
    SELECT i.*, l.name AS location_name FROM items i
    LEFT JOIN locations l ON i.location_id = l.id
    WHERE i.qr_token = ?
");
$stmt->execute([$token]);
$item = $stmt->fetch();

if (!$item) {
    header('Location: /donation-tracker-lite/check.php');
    exit;
}

$history = db()->prepare("
    SELECT ins.*
    FROM inspections ins
    WHERE ins.item_id = ?
    ORDER BY ins.inspected_at DESC
");
$history->execute([$item['id']]);
$history = $history->fetchAll();

$status_color = match($item['status']) {
    'in_use' => 'success',
    'donated' => 'primary',
    'returned' => 'info',
    'retired' => 'secondary',
    'lost' => 'danger',
    default => 'secondary'
};
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= e($item['name']) ?> - Donation Tracker</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css" rel="stylesheet">
    <style>
        body { background-color: #f5f7fa; }
        .card { border: none; box-shadow: 0 1px 3px rgba(0,0,0,0.08); }
        .timeline { position: relative; padding-left: 30px; }
        .timeline::before { content: ''; position: absolute; left: 10px; top: 0; bottom: 0; width: 2px; background: #dee2e6; }
        .timeline-item { position: relative; margin-bottom: 1.5rem; }
        .timeline-item::before {
            content: '';
            position: absolute;
            left: -24px;
            top: 6px;
            width: 12px;
            height: 12px;
            border-radius: 50%;
            background: #0d6efd;
        }
        .timeline-item.danger::before { background: #dc3545; }
        .timeline-item.warning::before { background: #ffc107; }
        .timeline-item.success::before { background: #198754; }
        .timeline-item.info::before { background: #0dcaf0; }
    </style>
</head>
<body>
    <nav class="navbar navbar-dark bg-dark">
        <div class="container">
            <a class="navbar-brand" href="/donation-tracker-lite/">
                <i class="bi bi-box-seam"></i> Donation Tracker
            </a>
        </div>
    </nav>

    <div class="container py-5">
        <a href="/donation-tracker-lite/check.php" class="btn btn-outline-secondary mb-4">
            <i class="bi bi-arrow-left"></i> Back to Search
        </a>

        <div class="row">
            <div class="col-lg-5">
                <div class="card mb-4">
                    <div class="card-body">
                        <h4>
                            <i class="bi bi-<?= $item['type'] === 'laptop' ? 'laptop' : ($item['type'] === 'router' ? 'wifi' : 'box') ?>"></i>
                            <?= e($item['name']) ?>
                        </h4>
                        <span class="badge bg-<?= $status_color ?> mb-3"><?= e(ucfirst(str_replace('_', ' ', $item['status']))) ?></span>

                        <table class="table table-sm">
                            <tr><th>Type</th><td><?= e(ucfirst($item['type'])) ?></td></tr>
                            <tr><th>Serial Number</th><td><?= e($item['serial_number'] ?? 'N/A') ?></td></tr>
                            <tr><th>Description</th><td><?= e($item['description'] ?? '-') ?></td></tr>
                            <tr><th>Donated By</th><td><?= e($item['donor_name'] ?? 'Anonymous') ?></td></tr>
                            <tr><th>Donation Date</th><td><?= date('M j, Y', strtotime($item['donation_date'])) ?></td></tr>
                            <tr><th>Location</th><td><?= e($item['location_name'] ?? 'N/A') ?></td></tr>
                        </table>
                    </div>
                </div>

                <div class="card text-center">
                    <div class="card-body">
                        <h6>Scan QR to verify this item</h6>
                        <img src="/donation-tracker-lite/api/qr.php?token=<?= e($item['qr_token']) ?>&size=200" alt="QR Code" class="img-fluid" style="max-width: 200px;">
                    </div>
                </div>
            </div>

            <div class="col-lg-7">
                <div class="card">
                    <div class="card-header">
                        <i class="bi bi-clock-history"></i> Inspection History
                    </div>
                    <div class="card-body">
                        <?php if (count($history) > 0): ?>
                        <div class="timeline">
                            <?php foreach ($history as $h):
                                $color = match($h['status']) {
                                    'working' => 'success',
                                    'damaged' => 'warning',
                                    'missing' => 'danger',
                                    'replaced' => 'info',
                                    default => ''
                                };
                            ?>
                            <div class="timeline-item <?= $color ?>">
                                <div class="d-flex justify-content-between">
                                    <div>
                                        <strong><?= e(ucfirst($h['status'])) ?></strong>
                                        <span class="text-muted">by <?= e($h['inspector_name']) ?></span>
                                    </div>
                                    <small class="text-muted"><?= date('M j, Y g:i A', strtotime($h['inspected_at'])) ?></small>
                                </div>
                                <?php if ($h['photo_path']): ?>
                                <div class="mt-2">
                                    <a href="/donation-tracker-lite/uploads/<?= e($h['photo_path']) ?>" target="_blank">
                                        <img src="/donation-tracker-lite/uploads/<?= e($h['photo_path']) ?>" class="img-thumbnail" style="max-height: 120px;">
                                    </a>
                                </div>
                                <?php endif; ?>
                                <?php if ($h['notes']): ?>
                                <div><small class="text-muted"><?= e($h['notes']) ?></small></div>
                                <?php endif; ?>
                            </div>
                            <?php endforeach; ?>
                        </div>
                        <?php else: ?>
                        <p class="text-muted text-center py-3">No inspections recorded yet.</p>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>

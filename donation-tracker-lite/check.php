<?php
$pageTitle = 'Check Your Donated Items';
require_once __DIR__ . '/config/database.php';

$results = [];
$searched = false;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $searched = true;
    $email = trim($_POST['email'] ?? '');

    if ($email) {
        $stmt = db()->prepare("
            SELECT i.*,
                l.name AS location_name,
                (SELECT ins.status FROM inspections ins WHERE ins.item_id = i.id ORDER BY ins.inspected_at DESC LIMIT 1) AS last_status,
                (SELECT ins.inspected_at FROM inspections ins WHERE ins.item_id = i.id ORDER BY ins.inspected_at DESC LIMIT 1) AS last_inspected
            FROM items i
            LEFT JOIN locations l ON i.location_id = l.id
            WHERE i.donor_email = ?
            ORDER BY i.donation_date DESC
        ");
        $stmt->execute([$email]);
        $results = $stmt->fetchAll();
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Check Your Items - Donation Tracker</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css" rel="stylesheet">
    <style>
        body { background-color: #f5f7fa; }
        .hero { padding: 3rem 0; text-align: center; }
        .card { border: none; box-shadow: 0 1px 3px rgba(0,0,0,0.08); }
    </style>
</head>
<body>
    <nav class="navbar navbar-dark bg-dark">
        <div class="container">
            <a class="navbar-brand" href="/donation-tracker-lite/">
                <i class="bi bi-box-seam"></i> Donation Tracker
            </a>
            <a href="/donation-tracker-lite/login.php" class="btn btn-outline-light btn-sm">Staff Login</a>
        </div>
    </nav>

    <div class="container py-5">
        <div class="hero">
            <h1><i class="bi bi-search"></i> Check Your Donated Items</h1>
            <p class="text-muted">Enter the email you used when donating to see the status of your items.</p>
        </div>

        <div class="row justify-content-center">
            <div class="col-lg-6">
                <div class="card mb-4">
                    <div class="card-body">
                        <form method="POST">
                            <div class="input-group">
                                <input type="email" name="email" class="form-control form-control-lg" placeholder="Enter your email address" required value="<?= e($_POST['email'] ?? '') ?>">
                                <button type="submit" class="btn btn-primary">
                                    <i class="bi bi-search"></i> Search
                                </button>
                            </div>
                        </form>
                    </div>
                </div>

                <?php if ($searched): ?>
                <?php if (count($results) > 0): ?>
                <h5 class="mb-3">We found <?= count($results) ?> item(s) under this email:</h5>
                <?php foreach ($results as $item):
                    $status_color = match($item['last_status'] ?? $item['status']) {
                        'working' => 'success',
                        'damaged' => 'warning',
                        'missing' => 'danger',
                        'replaced' => 'info',
                        'in_use' => 'success',
                        'donated' => 'primary',
                        'returned' => 'info',
                        'retired' => 'secondary',
                        'lost' => 'danger',
                        default => 'secondary'
                    };
                ?>
                <div class="card mb-3">
                    <div class="card-body">
                        <div class="d-flex justify-content-between align-items-start">
                            <div>
                                <h5 class="card-title mb-1">
                                    <i class="bi bi-<?= $item['type'] === 'laptop' ? 'laptop' : ($item['type'] === 'router' ? 'wifi' : 'box') ?>"></i>
                                    <?= e($item['name']) ?>
                                </h5>
                                <small class="text-muted">
                                    <?= e(ucfirst($item['type'])) ?>
                                    <?php if ($item['serial_number']): ?>
                                    | S/N: <?= e($item['serial_number']) ?>
                                    <?php endif; ?>
                                </small>
                            </div>
                            <span class="badge bg-<?= $status_color ?>"><?= e(ucfirst(str_replace('_', ' ', $item['last_status'] ?? $item['status']))) ?></span>
                        </div>
                        <hr>
                        <div class="row">
                            <div class="col-6">
                                <small class="text-muted">Donated</small><br>
                                <strong><?= date('M j, Y', strtotime($item['donation_date'])) ?></strong>
                            </div>
                            <div class="col-6">
                                <small class="text-muted">Location</small><br>
                                <strong><?= e($item['location_name'] ?? 'N/A') ?></strong>
                            </div>
                            <div class="col-6 mt-2">
                                <small class="text-muted">Last Checked</small><br>
                                <strong><?= $item['last_inspected'] ? date('M j, Y', strtotime($item['last_inspected'])) : '<em>Not yet inspected</em>' ?></strong>
                            </div>
                            <div class="col-6 mt-2">
                                <small class="text-muted">Current Status</small><br>
                                <strong><?= e(ucfirst(str_replace('_', ' ', $item['status']))) ?></strong>
                            </div>
                        </div>
                        <div class="mt-3">
                            <a href="/donation-tracker-lite/item.php?token=<?= e($item['qr_token']) ?>" class="btn btn-sm btn-outline-primary">
                                <i class="bi bi-info-circle"></i> View Full History
                            </a>
                        </div>
                    </div>
                </div>
                <?php endforeach; ?>
                <?php else: ?>
                <div class="alert alert-warning">
                    <i class="bi bi-exclamation-circle"></i> No items found for this email. Please check your email address or contact the administrator.
                </div>
                <?php endif; ?>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>

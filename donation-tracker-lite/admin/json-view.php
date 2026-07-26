<?php
session_start();
require_once __DIR__ . '/../config/database.php';

$rows = db()->query("
    SELECT i.*, l.name AS location_name, l.address AS location_address,
        (SELECT ins.status FROM inspections ins WHERE ins.item_id = i.id ORDER BY ins.inspected_at DESC LIMIT 1) AS last_inspection_status,
        (SELECT ins.inspector_name FROM inspections ins WHERE ins.item_id = i.id ORDER BY ins.inspected_at DESC LIMIT 1) AS last_inspector,
        (SELECT ins.inspected_at FROM inspections ins WHERE ins.item_id = i.id ORDER BY ins.inspected_at DESC LIMIT 1) AS last_inspected
    FROM items i
    LEFT JOIN locations l ON i.location_id = l.id
    ORDER BY i.donation_date DESC
")->fetchAll();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Items JSON View</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css" rel="stylesheet">
    <style>
        pre.json { background: #1e1e2e; color: #cdd6f4; padding: 1.5rem; border-radius: 8px; overflow-x: auto; font-size: 0.85rem; max-height: 600px; }
        .json-key { color: #89b4fa; }
        .json-string { color: #a6e3a1; }
        .json-number { color: #fab387; }
        .json-null { color: #6c7086; }
    </style>
</head>
<body>
    <nav class="navbar navbar-dark bg-dark">
        <div class="container">
            <span class="navbar-brand"><i class="bi bi-box-seam"></i> Donation Tracker</span>
            <a href="/donation-tracker-lite/admin/dashboard.php" class="btn btn-outline-light btn-sm">Back to Dashboard</a>
        </div>
    </nav>

    <div class="container py-4">
        <div class="d-flex justify-content-between align-items-center mb-4">
            <h3><i class="bi bi-code-slash"></i> Items Data (JSON)</h3>
            <div>
                <a href="/donation-tracker-lite/admin/items.php?export=1" class="btn btn-sm btn-primary"><i class="bi bi-download"></i> Download JSON</a>
            </div>
        </div>

        <div class="card mb-4">
            <div class="card-header">Table View (<?= count($rows) ?> items)</div>
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-hover table-sm mb-0">
                        <thead>
                            <tr>
                                <th>ID</th>
                                <th>Name</th>
                                <th>Type</th>
                                <th>Serial</th>
                                <th>Donor</th>
                                <th>Email</th>
                                <th>Donated</th>
                                <th>Location</th>
                                <th>Status</th>
                                <th>Last Inspection</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($rows as $row): ?>
                            <tr>
                                <td><?= $row['id'] ?></td>
                                <td><?= e($row['name']) ?></td>
                                <td><span class="badge bg-secondary"><?= e(ucfirst($row['type'])) ?></span></td>
                                <td><small><?= e($row['serial_number'] ?? '-') ?></small></td>
                                <td><?= e($row['donor_name'] ?? '-') ?></td>
                                <td><small><?= e($row['donor_email'] ?? '-') ?></small></td>
                                <td><?= date('M j, Y', strtotime($row['donation_date'])) ?></td>
                                <td><?= e($row['location_name'] ?? '-') ?></td>
                                <td>
                                    <?php
                                    $badge = match($row['status']) {
                                        'in_use' => 'success',
                                        'donated' => 'primary',
                                        'returned' => 'info',
                                        'retired' => 'secondary',
                                        'lost' => 'danger',
                                        default => 'secondary'
                                    };
                                    ?>
                                    <span class="badge bg-<?= $badge ?>"><?= e(ucfirst(str_replace('_', ' ', $row['status']))) ?></span>
                                </td>
                                <td>
                                    <?php if ($row['last_inspected']): ?>
                                    <small>
                                        <?= e(ucfirst($row['last_inspection_status'])) ?>
                                        by <?= e($row['last_inspector'] ?? 'Unknown') ?>
                                        <br><span class="text-muted"><?= date('M j, Y', strtotime($row['last_inspected'])) ?></span>
                                    </small>
                                    <?php else: ?>
                                    <small class="text-muted"><em>Never</em></small>
                                    <?php endif; ?>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>

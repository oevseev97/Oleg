<?php
require_once __DIR__ . '/db.php';

$pdo = getDb();
$id = (int)($_GET['id'] ?? 0);

$stmt = $pdo->prepare("SELECT * FROM equipment WHERE id = :id");
$stmt->execute([':id' => $id]);
$equipment = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$equipment) {
    http_response_code(404);
    echo "Equipment not found.";
    exit;
}

$stmt = $pdo->prepare("
    SELECT * FROM service_records
    WHERE equipment_id = :equipment_id
    ORDER BY created_at DESC, id DESC
");
$stmt->execute([':equipment_id' => $id]);
$records = $stmt->fetchAll(PDO::FETCH_ASSOC);

$stepsByRecord = [];
$photosByRecord = [];

if ($records) {
    $recordIds = array_map(fn($r) => (int)$r['id'], $records);
    $in = implode(',', array_fill(0, count($recordIds), '?'));

    $stmt = $pdo->prepare("SELECT * FROM service_steps WHERE service_record_id IN ($in) ORDER BY id ASC");
    $stmt->execute($recordIds);
    foreach ($stmt->fetchAll(PDO::FETCH_ASSOC) as $step) {
        $stepsByRecord[$step['service_record_id']][] = $step;
    }

    $stmt = $pdo->prepare("SELECT * FROM photos WHERE service_record_id IN ($in) ORDER BY id ASC");
    $stmt->execute($recordIds);
    foreach ($stmt->fetchAll(PDO::FETCH_ASSOC) as $photo) {
        $photosByRecord[$photo['service_record_id']][] = $photo;
    }
}

$scheme = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http';
$host = $_SERVER['HTTP_HOST'] ?? 'localhost';
$qrUrl = $scheme . '://' . $host . '/qr.php?t=' . urlencode($equipment['qr_token']);
?>
<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <title>Equipment card</title>
    <link rel="stylesheet" href="style.css">
</head>
<body>
<div class="container">
    <h1>Equipment card</h1>

    <p>
        <a class="btn" href="equipment_list.php">Back to list</a>
        <a class="btn" href="service_add.php?equipment_id=<?= (int)$equipment['id'] ?>">Add service</a>
    </p>

    <div class="card">
        <h2><?= h($equipment['name']) ?></h2>
        <p><strong>ID:</strong> <?= (int)$equipment['id'] ?></p>
        <p><strong>Serial number:</strong> <?= h($equipment['serial_number']) ?></p>
        <p><strong>Location:</strong> <?= h($equipment['location']) ?></p>
        <p><strong>Description:</strong> <?= nl2br(h($equipment['description'])) ?></p>
        <p><strong>QR token:</strong> <?= h($equipment['qr_token']) ?></p>
        <p><strong>QR URL:</strong><br><?= h($qrUrl) ?></p>
    </div>

    <h2>Service history</h2>

    <?php if (!$records): ?>
        <div class="card">There are no service records yet.</div>
    <?php else: ?>
        <?php foreach ($records as $record): ?>
            <div class="card">
                <p><strong>Date:</strong> <?= h($record['created_at']) ?></p>
                <p><strong>Executor:</strong> <?= h($record['performed_by']) ?></p>
                <p><strong>Comment:</strong><br><?= nl2br(h($record['comment'])) ?></p>

                <h3>Stages</h3>
                <?php if (!empty($stepsByRecord[$record['id']])): ?>
                    <ul>
                        <?php foreach ($stepsByRecord[$record['id']] as $step): ?>
                            <li>
                                <strong><?= h($step['title']) ?></strong>
                                <?php if ($step['status']): ?>
                                    — <?= h($step['status']) ?>
                                <?php endif; ?>
                                <?php if ($step['note']): ?>
                                    <br><small><?= nl2br(h($step['note'])) ?></small>
                                <?php endif; ?>
                            </li>
                        <?php endforeach; ?>
                    </ul>
                <?php else: ?>
                    <p>There are no stages.</p>
                <?php endif; ?>

                <h3>Photos</h3>
                <?php if (!empty($photosByRecord[$record['id']])): ?>
                    <div class="photo-grid">
                        <?php foreach ($photosByRecord[$record['id']] as $photo): ?>
                            <div class="photo-card">
                                <img src="<?= h($photo['file_path']) ?>" alt="photo">
                                <div><?= h($photo['caption']) ?></div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php else: ?>
                    <p>There are no photographs.</p>
                <?php endif; ?>
            </div>
        <?php endforeach; ?>
    <?php endif; ?>
</div>
</body>
</html>
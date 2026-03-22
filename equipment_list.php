<?php
require_once __DIR__ . '/db.php';

$pdo = getDb();

$stmt = $pdo->query("SELECT * FROM equipment ORDER BY id DESC");
$items = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>
<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <title>Equipment list</title>
    <link rel="stylesheet" href="style.css">
</head>
<body>
<div class="container">
    <h1>Equipment list</h1>

    <p>
        <a class="btn" href="index.php">Home</a>
        <a class="btn" href="equipment_create.php">Add equipment</a>
    </p>

    <?php if (!$items): ?>
        <div class="card">Equipment has not been added yet.</div>
    <?php else: ?>
        <?php foreach ($items as $item): ?>
            <div class="card">
                <h2><?= h($item['name']) ?></h2>
                <p><strong>ID:</strong> <?= (int)$item['id'] ?></p>
                <p><strong>Serial number:</strong> <?= h($item['serial_number']) ?></p>
                <p><strong>location:</strong> <?= h($item['location']) ?></p>
                <p><strong>QR token:</strong> <?= h($item['qr_token']) ?></p>
                <p>
                    <a class="btn" href="equipment_view.php?id=<?= (int)$item['id'] ?>">Open card</a>
                    <a class="btn" href="qr.php?t=<?= urlencode($item['qr_token']) ?>">Open via QR link</a>
                </p>
            </div>
        <?php endforeach; ?>
    <?php endif; ?>
</div>
</body>
</html>
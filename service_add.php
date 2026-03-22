<?php
require_once __DIR__ . '/db.php';

$pdo = getDb();
$equipmentId = (int)($_GET['equipment_id'] ?? $_POST['equipment_id'] ?? 0);
$error = '';

$stmt = $pdo->prepare("SELECT * FROM equipment WHERE id = :id");
$stmt->execute([':id' => $equipmentId]);
$equipment = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$equipment) {
    http_response_code(404);
    echo "Equipment not found.";
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $performedBy = trim($_POST['performed_by'] ?? '');
    $comment = trim($_POST['comment'] ?? '');

    $steps = $_POST['steps'] ?? [];
    $statuses = $_POST['statuses'] ?? [];
    $notes = $_POST['notes'] ?? [];

    $pdo->beginTransaction();

    try {
        $stmt = $pdo->prepare("
            INSERT INTO service_records (equipment_id, performed_by, comment)
            VALUES (:equipment_id, :performed_by, :comment)
        ");
        $stmt->execute([
            ':equipment_id' => $equipmentId,
            ':performed_by' => $performedBy,
            ':comment' => $comment,
        ]);

        $recordId = (int)$pdo->lastInsertId();

        $stmtStep = $pdo->prepare("
            INSERT INTO service_steps (service_record_id, title, status, note)
            VALUES (:service_record_id, :title, :status, :note)
        ");

        for ($i = 0; $i < count($steps); $i++) {
            $title = trim($steps[$i] ?? '');
            $status = trim($statuses[$i] ?? '');
            $note = trim($notes[$i] ?? '');

            if ($title === '') {
                continue;
            }

            $stmtStep->execute([
                ':service_record_id' => $recordId,
                ':title' => $title,
                ':status' => $status,
                ':note' => $note,
            ]);
        }

        if (!empty($_FILES['photos']['name'][0])) {
            $uploadDir = __DIR__ . '/uploads/';
            if (!is_dir($uploadDir)) {
                mkdir($uploadDir, 0777, true);
            }

            $stmtPhoto = $pdo->prepare("
                INSERT INTO photos (service_record_id, file_path, caption)
                VALUES (:service_record_id, :file_path, :caption)
            ");

            foreach ($_FILES['photos']['tmp_name'] as $index => $tmpName) {
                if (!is_uploaded_file($tmpName)) {
                    continue;
                }

                $originalName = $_FILES['photos']['name'][$index] ?? 'photo.jpg';
                $ext = strtolower(pathinfo($originalName, PATHINFO_EXTENSION));
                if (!in_array($ext, ['jpg', 'jpeg', 'png', 'webp'], true)) {
                    continue;
                }

                $safeFileName = uniqid('photo_', true) . '.' . $ext;
                $targetPath = $uploadDir . $safeFileName;
                $publicPath = 'uploads/' . $safeFileName;

                if (move_uploaded_file($tmpName, $targetPath)) {
                    $stmtPhoto->execute([
                        ':service_record_id' => $recordId,
                        ':file_path' => $publicPath,
                        ':caption' => '',
                    ]);
                }
            }
        }

        $pdo->commit();
        redirect("equipment_view.php?id=$equipmentId");
    } catch (Throwable $e) {
        $pdo->rollBack();
        $error = 'Error saving:' . $e->getMessage();
    }
}
?>
<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <title>Add service</title>
    <link rel="stylesheet" href="style.css">
    <script>
        function addStepRow() {
            const container = document.getElementById('steps-container');
            const row = document.createElement('div');
            row.className = 'step-row';
            row.innerHTML = `
                <input type="text" name="steps[]" placeholder="Procedure">
                <input type="text" name="statuses[]" placeholder="Status (eg: completed)">
                <input type="text" name="notes[]" placeholder="---">
            `;
            container.appendChild(row);
        }
    </script>
</head>
<body>
<div class="container">
    <h1>Add service</h1>

    <p><a class="btn" href="equipment_view.php?id=<?= (int)$equipment['id'] ?>">Back</a></p>

    <div class="card">
        <strong>Equipment:</strong> <?= h($equipment['name']) ?>
    </div>

    <?php if ($error): ?>
        <div class="card error"><?= h($error) ?></div>
    <?php endif; ?>

    <form method="post" enctype="multipart/form-data" class="card">
        <input type="hidden" name="equipment_id" value="<?= (int)$equipment['id'] ?>">

        <label>Performed by</label>
        <input type="text" name="performed_by">

        <label>Comment</label>
        <textarea name="comment" rows="4"></textarea>

        <h3>Service stages</h3>
        <div id="steps-container">
            <div class="step-row">
                <input type="text" name="steps[]" placeholder="Service stages">
                <input type="text" name="statuses[]" placeholder="Status">
                <input type="text" name="notes[]" placeholder="Notes">
            </div>
        </div>

        <p><button class="btn" type="button" onclick="addStepRow()">Add another stage</button></p>

        <label>Photos</label>
        <input type="file" name="photos[]" multiple accept=".jpg,.jpeg,.png,.webp">

        <button class="btn" type="submit">Save service</button>
    </form>
</div>
</body>
</html>
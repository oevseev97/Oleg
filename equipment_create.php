<?php
require_once __DIR__ . '/db.php';

$pdo = getDb();
$error = '';

function generateQrToken(): string
{
    return bin2hex(random_bytes(8));
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = trim($_POST['name'] ?? '');
    $serialNumber = trim($_POST['serial_number'] ?? '');
    $location = trim($_POST['location'] ?? '');
    $description = trim($_POST['description'] ?? '');

    if ($name === '') {
        $error = 'Equipment name is required.';
    } else {
        $token = generateQrToken();

        $stmt = $pdo->prepare("
            INSERT INTO equipment (qr_token, name, serial_number, location, description)
            VALUES (:qr_token, :name, :serial_number, :location, :description)
        ");

        $stmt->execute([
            ':qr_token' => $token,
            ':name' => $name,
            ':serial_number' => $serialNumber,
            ':location' => $location,
            ':description' => $description,
        ]);

        $newId = (int)$pdo->lastInsertId();
        redirect("equipment_view.php?id=$newId");
    }
}
?>
<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <title>Add equipment</title>
    <link rel="stylesheet" href="style.css">
</head>
<body>
<div class="container">
    <h1>Add equipment</h1>

    <p><a class="btn" href="equipment_list.php">Back to list</a></p>

    <?php if ($error): ?>
        <div class="card error"><?= h($error) ?></div>
    <?php endif; ?>

    <form method="post" class="card">
        <label>Equipment name</label>
        <input type="text" name="name" required>

        <label>Serial number</label>
        <input type="text" name="serial_number">

        <label>Location</label>
        <input type="text" name="location">

        <label>Description</label>
        <textarea name="description" rows="4"></textarea>

        <button class="btn" type="submit">Create</button>
    </form>
</div>
</body>
</html>
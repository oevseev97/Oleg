<?php
require_once __DIR__ . '/db.php';

$pdo = getDb();
$token = trim($_GET['t'] ?? '');

if ($token === '') {
    http_response_code(400);
    echo "QR token is missing.";
    exit;
}

$stmt = $pdo->prepare("SELECT id FROM equipment WHERE qr_token = :token");
$stmt->execute([':token' => $token]);
$equipmentId = $stmt->fetchColumn();

if (!$equipmentId) {
    http_response_code(404);
    echo "Equipment not found by QR token.";
    exit;
}

redirect("equipment_view.php?id=" . (int)$equipmentId);
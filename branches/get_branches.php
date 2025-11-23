<?php
header("Content-Type: application/json");
require_once "../config.php";

// قراءة JSON
$data = json_decode(file_get_contents("php://input"), true);
$token = $data["token"] ?? "";

// التحقق من التوكن
if (empty($token)) {
    echo json_encode(["status" => false, "message" => "Token required"]);
    exit;
}

// التحقق من صحة التوكن
$stmt = $pdo->prepare("SELECT user_id FROM user_tokens WHERE token = ?");
$stmt->execute([$token]);
$tok = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$tok) {
    echo json_encode(["status" => false, "message" => "Invalid token"]);
    exit;
}

// جلب الفروع
$stmt = $pdo->prepare("SELECT id, name, city, created_at FROM branches ORDER BY id DESC");
$stmt->execute();
$rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

echo json_encode(["status" => true, "data" => $rows]);

<?php
header("Content-Type: application/json");
require_once __DIR__ . "/../config.php";

// قراءة الـ token
$data = json_decode(file_get_contents("php://input"), true);
$token = $data["token"] ?? "";

// التحقق من وجود التوكن
if (empty($token)) {
    echo json_encode(["status" => false, "message" => "Token required"]);
    exit;
}

// جلب user_id من جدول user_tokens
$stmt = $pdo->prepare("SELECT user_id FROM user_tokens WHERE token = ?");
$stmt->execute([$token]);
$tokenRow = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$tokenRow) {
    echo json_encode(["status" => false, "message" => "Invalid token"]);
    exit;
}

$userId = $tokenRow["user_id"];

// جلب المهام الخاصة بالمستخدم
$stmt = $pdo->prepare("
    SELECT 
        id, customer_id, task_type, status, notes, attachment, created_at, updated_at
    FROM tasks 
    WHERE created_by = ?
");
$stmt->execute([$userId]);
$tasks = $stmt->fetchAll(PDO::FETCH_ASSOC);

echo json_encode(["status" => true, "data" => $tasks]);
exit;

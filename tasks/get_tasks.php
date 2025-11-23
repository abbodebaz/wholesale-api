<?php
header("Content-Type: application/json");
require_once "../config.php";

// قراءة JSON
$input = json_decode(file_get_contents("php://input"), true);
$token = $input["token"] ?? "";

// تحقق من وجود التوكن
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

$user_id = $tokenRow["user_id"];

// جلب المهام
$stmt = $pdo->prepare("
    SELECT 
        id,
        created_by,
        customer_id,
        task_type,
        status,
        notes,
        attachment,
        created_at,
        updated_at
    FROM tasks
    WHERE created_by = ?
    ORDER BY created_at DESC
");

$stmt->execute([$user_id]);
$tasks = $stmt->fetchAll(PDO::FETCH_ASSOC);

echo json_encode([
    "status" => true,
    "data" => $tasks
]);

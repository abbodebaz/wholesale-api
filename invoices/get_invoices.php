<?php
header("Content-Type: application/json");
require_once "../config.php";

// قراءة بيانات الإدخال
$input = json_decode(file_get_contents("php://input"), true);
$token = $input["token"] ?? "";

// التحقق من وجود التوكن
if (empty($token)) {
    echo json_encode(["status" => false, "message" => "Token required"]);
    exit;
}

// التحقق من PDO
if (!isset($pdo)) {
    echo json_encode(["status" => false, "message" => "PDO NOT LOADED"]);
    exit;
}

// جلب user_id من user_tokens
$stmt = $pdo->prepare("SELECT user_id FROM user_tokens WHERE token = ?");
$stmt->execute([$token]);
$userRow = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$userRow) {
    echo json_encode(["status" => false, "message" => "Invalid token"]);
    exit;
}

$user_id = $userRow["user_id"];

// جلب الفواتير حسب assigned_to (وليس created_by)
$stmt = $pdo->prepare("
    SELECT *
    FROM invoices
    WHERE assigned_to = ?
    ORDER BY id DESC
");
$stmt->execute([$user_id]);
$invoices = $stmt->fetchAll(PDO::FETCH_ASSOC);

echo json_encode([
    "status" => true,
    "data" => $invoices
]);

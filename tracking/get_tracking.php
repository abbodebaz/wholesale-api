<?php
header("Content-Type: application/json");
require_once "../config.php";

// قراءة token من body
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

// جلب بيانات التتبع
$stmt = $pdo->prepare("
    SELECT id, field_user_id, status, lat, lng, timestamp 
    FROM tracking 
    WHERE field_user_id = ? 
    ORDER BY timestamp DESC
");
$stmt->execute([$user_id]);
$tracking = $stmt->fetchAll(PDO::FETCH_ASSOC);

// إرجاع البيانات
echo json_encode([
    "status" => true,
    "data" => $tracking
]);

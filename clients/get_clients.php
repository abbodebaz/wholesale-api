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

// التحقق من اتصال PDO
if (!isset($pdo)) {
    echo json_encode(["status" => false, "message" => "PDO NOT LOADED"]);
    exit;
}

// جلب المستخدم من user_tokens
$stmt = $pdo->prepare("SELECT user_id FROM user_tokens WHERE token = ?");
$stmt->execute([$token]);
$userRow = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$userRow) {
    echo json_encode(["status" => false, "message" => "Invalid token"]);
    exit;
}

$user_id = $userRow["user_id"];

// **جلب العملاء الذي أضافهم هذا المستخدم**
$stmt = $pdo->prepare("
    SELECT 
        id, name, phone, store_name, address, lat, lng, created_at
    FROM customers
    WHERE created_by = ?
    ORDER BY id DESC
");
$stmt->execute([$user_id]);
$clients = $stmt->fetchAll(PDO::FETCH_ASSOC);

// إخراج النتيجة
echo json_encode([
    "status" => true,
    "data" => $clients
]);

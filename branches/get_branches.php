<?php
header("Content-Type: application/json");
require_once "../config.php";

// قراءة token
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

// التحقق من أن التوكن يخص مستخدم
$stmt = $pdo->prepare("SELECT user_id FROM user_tokens WHERE token = ?");
$stmt->execute([$token]);
$userRow = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$userRow) {
    echo json_encode(["status" => false, "message" => "Invalid token"]);
    exit;
}

// جلب الفروع (الأعمدة الموجودة فعلياً)
$stmt = $pdo->prepare("SELECT id, name, city, sap_code FROM branches");
$stmt->execute();
$branches = $stmt->fetchAll(PDO::FETCH_ASSOC);

// إرجاع البيانات
echo json_encode([
    "status" => true,
    "data" => $branches
]);

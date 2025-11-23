<?php
header("Content-Type: application/json");
require_once "../config.php";

// قراءة بيانات الإدخال
$data = json_decode(file_get_contents("php://input"), true);
$token = $data["token"] ?? "";

// التحقق من وجود التوكن
if (empty($token)) {
    echo json_encode(["status" => false, "message" => "Token required"]);
    exit;
}

// التحقق من التوكن وجلب user_id
$stmt = $pdo->prepare("SELECT user_id FROM user_tokens WHERE token = ?");
$stmt->execute([$token]);
$user = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$user) {
    echo json_encode(["status" => false, "message" => "Invalid token"]);
    exit;
}

$created_by = $user["user_id"];

// قراءة بيانات العميل
$name = $data["name"] ?? "";
$phone = $data["phone"] ?? "";
$store_name = $data["store_name"] ?? "";
$address = $data["address"] ?? "";
$lat = $data["lat"] ?? null;
$lng = $data["lng"] ?? null;

// التحقق من الحقول المطلوبة
if (empty($name) || empty($phone) || empty($store_name)) {
    echo json_encode([
        "status" => false,
        "message" => "Missing required fields"
    ]);
    exit;
}

// إدخال العميل في قاعدة البيانات
$query = "INSERT INTO customers (name, phone, store_name, address, lat, lng, created_by, created_at)
          VALUES (?, ?, ?, ?, ?, ?, ?, NOW())";

$stmt = $pdo->prepare($query);

$success = $stmt->execute([
    $name, $phone, $store_name, $address, $lat, $lng, $created_by
]);

if ($success) {
    echo json_encode([
        "status" => true,
        "message" => "Client added successfully",
        "client_id" => $pdo->lastInsertId()
    ]);
} else {
    echo json_encode([
        "status" => false,
        "message" => "Failed to add client"
    ]);
}

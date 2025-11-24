<?php
header("Content-Type: application/json");
require_once "../config.php";

$token       = $_POST["token"] ?? "";
$customer_id = $_POST["customer_id"] ?? "";
$notes       = $_POST["notes"] ?? "";
$lat         = $_POST["lat"] ?? "";
$lng         = $_POST["lng"] ?? "";
$images_json = $_POST["images"] ?? "[]";

// تحقق من التوكين
$stmt = $pdo->prepare("SELECT user_id FROM user_tokens WHERE token = ?");
$stmt->execute([$token]);
$user = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$user) {
    echo json_encode(["status" => false, "message" => "Invalid token"]);
    exit;
}

$field_user_id = $user["user_id"];

// حفظ الزيارة
$stmt = $pdo->prepare("
    INSERT INTO visits (field_user_id, customer_id, notes, images, visited_at, lat, lng)
    VALUES (?, ?, ?, ?, NOW(), ?, ?)
");

$ok = $stmt->execute([
    $field_user_id,
    $customer_id,
    $notes,
    $images_json, // روابط Cloudinary
    $lat,
    $lng
]);

echo json_encode([
    "status" => $ok,
    "message" => $ok ? "Visit added successfully" : "Failed to add visit"
]);

<?php

header("Content-Type: application/json");
require_once "../config.php";

// قراءة JSON BODY
$input = json_decode(file_get_contents("php://input"), true);

$token       = $input["token"]       ?? "";
$customer_id = $input["customer_id"] ?? "";
$notes       = $input["notes"]       ?? "";
$images      = $input["images"]      ?? [];   // array of Base64 strings
$lat         = $input["lat"]         ?? "";
$lng         = $input["lng"]         ?? "";

// تحقق من التوكن
if (empty($token)) {
    echo json_encode(["status" => false, "message" => "Token required"]);
    exit;
}

$stmt = $pdo->prepare("SELECT user_id FROM user_tokens WHERE token = ?");
$stmt->execute([$token]);
$user = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$user) {
    echo json_encode(["status" => false, "message" => "Invalid token"]);
    exit;
}

$field_user_id = $user["user_id"];

// رفع الصور
$uploaded_files = [];

if (!empty($images) && is_array($images)) {

    $uploadPath = "../uploads/visits/";

    if (!is_dir($uploadPath)) {
        mkdir($uploadPath, 0777, true);
    }

    foreach ($images as $img64) {
        $imageName = uniqid("visit_") . ".jpg";
        $imagePath = $uploadPath . $imageName;

        file_put_contents($imagePath, base64_decode($img64));

        $uploaded_files[] = $imageName;
    }
}

$images_json = json_encode($uploaded_files);

// إدخال الزيارة
$stmt = $pdo->prepare("
    INSERT INTO visits (field_user_id, customer_id, notes, images, visited_at, lat, lng)
    VALUES (?, ?, ?, ?, NOW(), ?, ?)
");

$ok = $stmt->execute([$field_user_id, $customer_id, $notes, $images_json, $lat, $lng]);

echo json_encode([
    "status" => $ok,
    "message" => $ok ? "Visit added" : "Failed to add visit"
]);

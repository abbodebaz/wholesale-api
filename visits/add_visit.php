<?php

$allowedOrigins = [
    "https://ebaaptl.com",
    "https://www.ebaaptl.com"
];

if (isset($_SERVER['HTTP_ORIGIN']) && in_array($_SERVER['HTTP_ORIGIN'], $allowedOrigins)) {
    header("Access-Control-Allow-Origin: " . $_SERVER['HTTP_ORIGIN']);
}

header("Access-Control-Allow-Headers: Content-Type, Authorization");
header("Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS");

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit();
}

header("Content-Type: application/json");
require_once "../config.php";
require_once "../cloudinary_config.php"; // ملف إعدادات Cloudinary

use Cloudinary\Cloudinary;

// قراءة البيانات
$token       = $_POST["token"] ?? "";
$customer_id = $_POST["customer_id"] ?? "";
$notes       = $_POST["notes"] ?? "";
$lat         = $_POST["lat"] ?? "";
$lng         = $_POST["lng"] ?? "";

// تحقق من التوكن
$stmt = $pdo->prepare("SELECT user_id FROM user_tokens WHERE token = ?");
$stmt->execute([$token]);
$user = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$user) {
    echo json_encode(["status" => false, "message" => "Invalid token"]);
    exit;
}

$field_user_id = $user["user_id"];

// رفع الصور إلى Cloudinary
$imageUrls = [];

if (!empty($_FILES["images"]["name"][0])) {

    for ($i = 0; $i < count($_FILES["images"]["name"]); $i++) {

        $tmpName = $_FILES["images"]["tmp_name"][$i];

        $cloudinary = new Cloudinary([
            "cloud" => [
                "cloud_name" => "dzkcfjm8s",
                "api_key"    => "416946167141595",
                "api_secret" => "YOUR_SECRET_HERE"
            ]
        ]);

        $upload = $cloudinary->uploadApi()->upload($tmpName, [
            "folder" => "visits"
        ]);

        if (!empty($upload["secure_url"])) {
            $imageUrls[] = $upload["secure_url"];
        }
    }
}

$imagesJSON = json_encode($imageUrls);

// حفظ البيانات
$stmt = $pdo->prepare("
    INSERT INTO visits (field_user_id, customer_id, notes, images, visited_at, lat, lng)
    VALUES (?, ?, ?, ?, NOW(), ?, ?)
");

$ok = $stmt->execute([
    $field_user_id,
    $customer_id,
    $notes,
    $imagesJSON,
    $lat,
    $lng
]);

echo json_encode([
    "status"  => $ok,
    "message" => $ok ? "Visit added successfully" : "Failed to add visit",
    "images"  => $imageUrls
]);

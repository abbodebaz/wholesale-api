<?php

// =========================
//   CORS
// =========================
$allowedOrigins = [
    "https://ebaaptl.com",
    "https://www.ebaaptl.com"
];

if (isset($_SERVER['HTTP_ORIGIN']) && in_array($_SERVER['HTTP_ORIGIN'], $allowedOrigins)) {
    header("Access-Control-Allow-Origin: " . $_SERVER['HTTP_ORIGIN']);
}

header("Access-Control-Allow-Headers: Content-Type, Authorization");
header("Access-Control-Allow-Methods: GET, POST, OPTIONS");

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit();
}

header("Content-Type: application/json");
require_once "../config.php";


// =========================
//   إستقبال البيانات
// =========================
$token       = $_POST["token"] ?? "";
$customer_id = $_POST["customer_id"] ?? "";
$notes       = $_POST["notes"] ?? "";
$lat         = $_POST["lat"] ?? "";
$lng         = $_POST["lng"] ?? "";
$images_b64  = $_POST["images"] ?? "[]";    // هنا الصور Base64 جاهزة


// =========================
//   تحقق التوكن
// =========================
$stmt = $pdo->prepare("SELECT user_id FROM user_tokens WHERE token = ?");
$stmt->execute([$token]);
$user = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$user) {
    echo json_encode(["status" => false, "message" => "Invalid token"]);
    exit;
}

$field_user_id = $user["user_id"];


// =========================
//   حفظ الصور Base64 في السيرفر
// =========================
$imgArray = json_decode($images_b64, true);
$savedImages = [];

$uploadDir = "/home/u630342272/domains/ebaaptl.com/public_html/wholesale/uploads/visits/";

if (!is_dir($uploadDir)) {
    mkdir($uploadDir, 0777, true);
}

foreach ($imgArray as $img) {

    // decode Base64
    $part = explode(",", $img);
    $decoded = base64_decode(end($part));

    // اسم الصورة
    $fileName = "visit_" . uniqid() . ".jpg";
    $fullPath = $uploadDir . $fileName;

    // حفظ الصورة
    file_put_contents($fullPath, $decoded);

    $savedImages[] = $fileName;
}

$imagesJSON = json_encode($savedImages);


// =========================
//   إدخال الزيارة
// =========================
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
    "images"  => $savedImages
]);

<?php

// السماح للدومينات
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

// مسار رفع الصور الحقيقي
$uploadDir = "/home/u630342272/domains/ebaaptl.com/public_html/wholesale/uploads/visits/";

if (!is_dir($uploadDir)) {
    mkdir($uploadDir, 0777, true);
}

$imageNames = [];

if (isset($_FILES["images"])) {
    for ($i = 0; $i < count($_FILES["images"]["name"]); $i++) {

        $tmp  = $_FILES["images"]["tmp_name"][$i];
        $orig = $_FILES["images"]["name"][$i];

        if (!is_uploaded_file($tmp)) continue;

        $ext  = strtolower(pathinfo($orig, PATHINFO_EXTENSION));
        $name = "visit_" . uniqid() . "." . $ext;

        if (move_uploaded_file($tmp, $uploadDir . $name)) {
            $imageNames[] = $name;
        }
    }
}

$imagesJSON = json_encode($imageNames);

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
    "images"  => $imageNames,
    "message" => $ok ? "Visit added successfully" : "Failed to add visit"
]);

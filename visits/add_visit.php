<?php

// السماح للدومين الرئيسي فقط
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

// استقبال المدخلات
$token       = $_POST["token"]       ?? "";
$customer_id = $_POST["customer_id"] ?? "";
$notes       = $_POST["notes"]       ?? "";
$lat         = $_POST["lat"]         ?? "";
$lng         = $_POST["lng"]         ?? "";

// التحقق من المدخلات
if (empty($token) || empty($customer_id) || empty($lat) || empty($lng)) {
    echo json_encode(["status" => false, "message" => "Missing required fields"]);
    exit;
}

// التحقق من التوكن
$stmt = $pdo->prepare("SELECT user_id FROM user_tokens WHERE token = ?");
$stmt->execute([$token]);
$user = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$user) {
    echo json_encode(["status" => false, "message" => "Invalid token"]);
    exit;
}

$field_user_id = $user["user_id"];

// المسار الصحيح في هوستنجر
$uploadDir = "/home/u630342272/domains/ebaaptl.com/public_html/wholesale/uploads/visits/";
$uploadURL = "https://ebaaptl.com/wholesale/uploads/visits/";

// إنشاء مجلد الرفع إذا غير موجود
if (!is_dir($uploadDir)) {
    mkdir($uploadDir, 0777, true);
}

// معالجة الصور
$imageNames = [];

if (!empty($_FILES["images"]["name"][0])) {

    for ($i = 0; $i < count($_FILES["images"]["name"]); $i++) {

        $tmpName  = $_FILES["images"]["tmp_name"][$i];
        $origName = $_FILES["images"]["name"][$i];

        // استخراج نوع الامتداد
        $ext = pathinfo($origName, PATHINFO_EXTENSION);
        $fileName = "visit_" . uniqid() . "." . strtolower($ext);

        // المسار الكامل
        $fullPath = $uploadDir . $fileName;

        // الرفع
        if (move_uploaded_file($tmpName, $fullPath)) {
            $imageNames[] = $fileName;
        }
    }
}

// تحويل الصور إلى JSON
$imagesJSON = json_encode($imageNames);

// حفظ الزيارة في قاعدة البيانات
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
    "images"  => $imageNames
]);


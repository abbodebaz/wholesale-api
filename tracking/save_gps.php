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

// قراءة بيانات JSON
$input = json_decode(file_get_contents("php://input"), true);

$token = $input["token"] ?? "";
$lat   = $input["lat"]   ?? "";
$lng   = $input["lng"]   ?? "";

// تحقق من القيم المطلوبة
if (empty($token) || empty($lat) || empty($lng)) {
    echo json_encode([
        "status" => false,
        "message" => "Missing parameters"
    ]);
    exit;
}

// التحقق من التوكن
$stmt = $pdo->prepare("SELECT user_id FROM user_tokens WHERE token = ?");
$stmt->execute([$token]);
$user = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$user) {
    echo json_encode([
        "status" => false,
        "message" => "Invalid token"
    ]);
    exit;
}

$field_user_id = $user["user_id"];

// إدخال اللوكيشن في جدول tracking_logs
$stmt = $pdo->prepare("
    INSERT INTO tracking_logs (field_user_id, lat, lng, tracked_at)
    VALUES (?, ?, ?, NOW())
");

$ok = $stmt->execute([$field_user_id, $lat, $lng]);

// رد JSON
echo json_encode([
    "status" => $ok,
    "message" => $ok ? "GPS saved" : "Failed to save GPS"
]);
?>

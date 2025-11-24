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

$token = $_GET["token"] ?? "";
$date  = $_GET["date"] ?? date("Y-m-d");

if (empty($token)) {
    echo json_encode(["status" => false, "message" => "Token required"]);
    exit;
}

// تحقق من التوكن
$stmt = $pdo->prepare("SELECT user_id FROM user_tokens WHERE token = ?");
$stmt->execute([$token]);
$user = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$user) {
    echo json_encode(["status" => false, "message" => "Invalid token"]);
    exit;
}

$field_user_id = $user["user_id"];

// جلب خط السير حسب اليوم
$stmt = $pdo->prepare("
    SELECT lat, lng, tracked_at
    FROM tracking_logs
    WHERE field_user_id = ?
      AND DATE(tracked_at) = ?
    ORDER BY tracked_at ASC
");
$stmt->execute([$field_user_id, $date]);
$data = $stmt->fetchAll(PDO::FETCH_ASSOC);

echo json_encode([
    "status" => true,
    "data" => $data
]);

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

// ========== قراءة التوكن من كل مكان ==========
$input = json_decode(file_get_contents("php://input"), true);

// 1) JSON body
$token = $input["token"] ?? "";

// 2) GET parameter
if (empty($token) && isset($_GET['token'])) {
    $token = $_GET['token'];
}

// 3) Authorization header: Bearer TOKEN
if (empty($token) && isset($_SERVER['HTTP_AUTHORIZATION'])) {
    if (preg_match('/Bearer\s(\S+)/', $_SERVER['HTTP_AUTHORIZATION'], $matches)) {
        $token = $matches[1];
    }
}

// إذا مافي توكن
if (empty($token)) {
    echo json_encode(["status" => false, "message" => "Token required"]);
    exit;
}

// ========== تحقق من اتصال قاعدة البيانات ==========
if (!isset($pdo)) {
    echo json_encode(["status" => false, "message" => "PDO NOT LOADED"]);
    exit;
}

// ========== تحقق من التوكن ==========
$stmt = $pdo->prepare("SELECT user_id FROM user_tokens WHERE token = ?");
$stmt->execute([$token]);
$userRow = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$userRow) {
    echo json_encode(["status" => false, "message" => "Invalid token"]);
    exit;
}

$user_id = $userRow["user_id"];

// ========== جلب العملاء ==========
$stmt = $pdo->prepare("
    SELECT id, name, phone, store_name, address, lat, lng, created_at
    FROM customers
    WHERE created_by = ?
    ORDER BY id DESC
");
$stmt->execute([$user_id]);
$clients = $stmt->fetchAll(PDO::FETCH_ASSOC);

// ========== إخراج ==========
echo json_encode([
    "status" => true,
    "data" => $clients
]);

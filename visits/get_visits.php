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

$stmt = $pdo->prepare("
    SELECT v.*, c.name AS customer_name, c.phone AS customer_phone
    FROM visits v
    JOIN customers c ON c.id = v.customer_id
    WHERE v.field_user_id = ?
    ORDER BY v.id DESC
");

$stmt->execute([$field_user_id]);
$data = $stmt->fetchAll(PDO::FETCH_ASSOC);

echo json_encode(["status" => true, "data" => $data]);

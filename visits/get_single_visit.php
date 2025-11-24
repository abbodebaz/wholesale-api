<?php
header("Content-Type: application/json");
require_once "../config.php";

$token = $_GET["token"] ?? "";
$id    = $_GET["id"] ?? "";

if (empty($token) || empty($id)) {
    echo json_encode(["status" => false, "message" => "Missing parameters"]);
    exit;
}

$stmt = $pdo->prepare("SELECT user_id FROM user_tokens WHERE token = ?");
$stmt->execute([$token]);
$user = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$user) {
    echo json_encode(["status" => false, "message" => "Invalid token"]);
    exit;
}

$stmt = $pdo->prepare("
    SELECT v.*, c.name AS customer_name, c.phone AS customer_phone
    FROM visits v
    JOIN customers c ON c.id = v.customer_id
    WHERE v.id = ?
");
$stmt->execute([$id]);
$data = $stmt->fetch(PDO::FETCH_ASSOC);

echo json_encode(["status" => true, "data" => $data]);

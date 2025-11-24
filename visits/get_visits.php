<?php
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

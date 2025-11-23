<?php
header("Content-Type: application/json");
require_once "../config.php";

$data = json_decode(file_get_contents("php://input"), true);
$token = $data["token"] ?? "";

if (empty($token)) {
    echo json_encode(["status" => false, "message" => "Token required"]);
    exit;
}

$stmt = $pdo->prepare("SELECT user_id FROM user_tokens WHERE token = ?");
$stmt->execute([$token]);
$tok = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$tok) {
    echo json_encode(["status" => false, "message" => "Invalid token"]);
    exit;
}

$user_id = $tok["user_id"];

$stmt = $pdo->prepare("SELECT * FROM tracking WHERE user_id = ? ORDER BY id DESC");
$stmt->execute([$user_id]);

echo json_encode(["status" => true, "data" => $stmt->fetchAll(PDO::FETCH_ASSOC)]);

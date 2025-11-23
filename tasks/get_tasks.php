<?php
require_once __DIR__ . '/../config.php';
header('Content-Type: application/json');

// Read token
$input = json_decode(file_get_contents("php://input"), true);
$token = $input['token'] ?? null;

if (!$token) {
    echo json_encode(["status" => false, "message" => "Missing token"]);
    exit;
}

// Validate token
$stmt = $pdo->prepare("SELECT user_id FROM user_tokens WHERE token = ?");
$stmt->execute([$token]);
$tokenData = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$tokenData) {
    echo json_encode(["status" => false, "message" => "Invalid token"]);
    exit;
}

$user_id = $tokenData['user_id'];

// Fetch tasks created by this user
$stmt = $pdo->prepare("
    SELECT 
        t.id,
        t.customer_id,
        t.task_type,
        t.status,
        t.notes,
        t.attachment,
        t.created_at,
        t.updated_at,
        c.name AS customer_name,
        c.phone AS customer_phone
    FROM tasks t
    LEFT JOIN customers c ON c.id = t.customer_id
    WHERE t.created_by = ?
    ORDER BY t.id DESC
");

$stmt->execute([$user_id]);
$tasks = $stmt->fetchAll(PDO::FETCH_ASSOC);

echo json_encode([
    "status" => true,
    "data" => $tasks
]);

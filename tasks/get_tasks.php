<?php
require_once "../config.php";

header("Content-Type: application/json");

// Only POST
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(["status" => false, "message" => "Method not allowed"]);
    exit;
}

// Get raw input
$input = json_decode(file_get_contents("php://input"), true);
$token = $input['token'] ?? '';

if (!$token) {
    echo json_encode(["status" => false, "message" => "Token is required"]);
    exit;
}

// Validate token
try {
    $stmt = $pdo->prepare("SELECT user_id FROM user_tokens WHERE token = ?");
    $stmt->execute([$token]);
    $tokenData = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$tokenData) {
        echo json_encode(["status" => false, "message" => "Invalid token"]);
        exit;
    }

    $user_id = $tokenData['user_id'];

    // Get tasks created by this user
    $query = "
        SELECT 
            id,
            customer_id,
            task_type,
            status,
            notes,
            attachment,
            created_at,
            updated_at
        FROM tasks
        WHERE created_by = ?
        ORDER BY id DESC
    ";

    $stmt = $pdo->prepare($query);
    $stmt->execute([$user_id]);
    $tasks = $stmt->fetchAll(PDO::FETCH_ASSOC);

    echo json_encode([
        "status" => true,
        "data" => $tasks
    ]);

} catch (Exception $e) {
    echo json_encode([
        "status" => false,
        "message" => "Server error",
        "error" => $e->getMessage()
    ]);
}

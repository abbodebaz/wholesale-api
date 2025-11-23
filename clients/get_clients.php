<?php
header("Content-Type: application/json; charset=UTF-8");
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: POST");

$config = include __DIR__ . "/../config.php";

try {
    $dsn = "mysql:host={$config['host']};dbname={$config['db']};charset=utf8mb4";
    $pdo = new PDO($dsn, $config['user'], $config['pass'], [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION
    ]);
} catch (Exception $e) {
    echo json_encode([
        "status" => false,
        "message" => "DB Connection failed",
        "error" => $e->getMessage()
    ]);
    exit;
}

$data = json_decode(file_get_contents("php://input"), true);

$token = $data['token'] ?? "";

// التحقق من التوكن
$stmt = $pdo->prepare("SELECT user_id FROM user_tokens WHERE token = ?");
$stmt->execute([$token]);
$tokenData = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$tokenData) {
    echo json_encode([
        "status" => false,
        "message" => "Invalid token"
    ]);
    exit;
}

$user_id = $tokenData['user_id'];

// معلومات المستخدم
$stmt = $pdo->prepare("SELECT * FROM users WHERE id = ?");
$stmt->execute([$user_id]);
$user = $stmt->fetch(PDO::FETCH_ASSOC);

$role = $user['role_id'];
$branch_id = $user['branch_id'];
$team_id = $user['team_id'];

// جلب العملاء حسب نوع المستخدم
if ($role == 3) { 
    // مندوب ميداني يشوف عملاء فريقه
    $stmt = $pdo->prepare("
        SELECT c.* FROM customers c
        JOIN teams t ON t.id = ?
        WHERE c.created_by = t.field_user_id
    ");
    $stmt->execute([$team_id]);

} elseif ($role == 2) { 
    // موظف مكتبي يشوف عملاء الفرع
    $stmt = $pdo->prepare("
        SELECT c.* FROM customers c
        JOIN teams t ON c.created_by = t.field_user_id
        WHERE t.branch_id = ?
    ");
    $stmt->execute([$branch_id]);

} else {
    echo json_encode([
        "status" => false,
        "message" => "User role not allowed"
    ]);
    exit;
}

$clients = $stmt->fetchAll(PDO::FETCH_ASSOC);

echo json_encode([
    "status" => true,
    "data" => $clients
]);

<?php
header("Content-Type: application/json; charset=UTF-8");
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: POST");

$config = include __DIR__ . "/../config.php";

// اتصال بقاعدة البيانات
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

// قراءة البيانات
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

// جلب المهام الخاصة بالمستخدم
$stmt = $pdo->prepare("
    SELECT 
        t.id,
        t.task_type,
        t.notes,
        t.status,
        t.customer_id,
        t.created_at,
        c.name AS customer_name,
        c.phone AS customer_phone,
        c.store_name
    FROM tasks t
    LEFT JOIN customers c ON c.id = t.customer_id
    WHERE t.assigned_to = ?
    ORDER BY t.created_at DESC
");


$stmt->execute([$user_id]);

$tasks = $stmt->fetchAll(PDO::FETCH_ASSOC);

echo json_encode([
    "status" => true,
    "data" => $tasks
]);

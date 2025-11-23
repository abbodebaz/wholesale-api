<?php
header("Content-Type: application/json");
require_once "../config.php";   // أهم خطوة – الاتصال بقاعدة البيانات

// قراءة الـ token
$data = json_decode(file_get_contents("php://input"), true);
$token = $data["token"] ?? "";

// تأكيد وجود التوكن
if (empty($token)) {
    echo json_encode(["status" => false, "message" => "Token required"]);
    exit;
}

// التحقق من صلاحية المستخدم
$stmt = $pdo->prepare("SELECT user_id FROM user_tokens WHERE token = ?");
$stmt->execute([$token]);
$tokenRow = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$tokenRow) {
    echo json_encode(["status" => false, "message" => "Invalid token"]);
    exit;
}

$user_id = $tokenRow["user_id"];

// الآن نجيب المهام الخاصة بهذا المستخدم
$stmt = $pdo->prepare("
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
");
$stmt->execute([$user_id]);

$tasks = $stmt->fetchAll(PDO::FETCH_ASSOC);

echo json_encode([
    "status" => true,
    "data" => $tasks
]);
?>

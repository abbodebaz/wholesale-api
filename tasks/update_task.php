<?php
header("Content-Type: application/json");
require_once "../config.php";

// قراءة JSON
$data      = json_decode(file_get_contents("php://input"), true);
$token     = $data["token"] ?? "";
$task_id   = $data["task_id"] ?? "";
$status    = $data["status"] ?? null;
$notes     = $data["notes"] ?? null;
$attachment = $data["attachment"] ?? null;

// 1) التحقق من وجود التوكن
if (empty($token)) {
    echo json_encode(["status" => false, "message" => "Token required"]);
    exit;
}

// 2) التحقق من المستخدم بواسطة التوكن
$stmt = $pdo->prepare("SELECT user_id, role_id FROM user_tokens 
                       JOIN users ON users.id = user_tokens.user_id
                       WHERE token = ?");
$stmt->execute([$token]);
$user = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$user) {
    echo json_encode(["status" => false, "message" => "Invalid token"]);
    exit;
}

$user_id = $user["user_id"];
$role_id = $user["role_id"];

// 3) التأكد من وجود المهمة
$stmt = $pdo->prepare("SELECT * FROM tasks WHERE id = ?");
$stmt->execute([$task_id]);
$task = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$task) {
    echo json_encode(["status" => false, "message" => "Task not found"]);
    exit;
}

// 4) السماح بالتعديل حسب الصلاحية
if ($role_id == 3 && $task["created_by"] != $user_id) {
    echo json_encode(["status" => false, "message" => "You cannot update this task"]);
    exit;
}

// 5) بناء SQL ديناميكي حسب المدخلات
$fields = [];
$params = [];

if ($status !== null) {
    $fields[] = "status = ?";
    $params[] = $status;
}

if ($notes !== null) {
    $fields[] = "notes = ?";
    $params[] = $notes;
}

if ($attachment !== null) {
    $fields[] = "attachment = ?";
    $params[] = $attachment;
}

$fields[] = "updated_at = NOW()";

$params[] = $task_id;

// 6) تنفيذ التحديث
$sql = "UPDATE tasks SET " . implode(", ", $fields) . " WHERE id = ?";
$stmt = $pdo->prepare($sql);
$stmt->execute($params);

echo json_encode([
    "status" => true,
    "message" => "Task updated successfully"
]);

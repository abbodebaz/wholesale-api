<?php
header("Content-Type: application/json");
require_once "../config.php";

// قراءة بيانات الإدخال
$data = json_decode(file_get_contents("php://input"), true);

// قراءة التوكن
$token = $data["token"] ?? "";
if (empty($token)) {
    echo json_encode(["status" => false, "message" => "Token required"]);
    exit;
}

// التحقق من التوكن وجلب بيانات المستخدم
$stmt = $pdo->prepare("SELECT user_id FROM user_tokens WHERE token = ?");
$stmt->execute([$token]);
$user = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$user) {
    echo json_encode(["status" => false, "message" => "Invalid token"]);
    exit;
}

$created_by = $user["user_id"];

// قراءة بيانات المهمة
$customer_id = $data["customer_id"] ?? null;
$task_type = $data["task_type"] ?? "";
$status = $data["status"] ?? "open";
$notes = $data["notes"] ?? "";
$attachment = $data["attachment"] ?? null;

// التحقق من الحقول الأساسية
if (!$customer_id || empty($task_type)) {
    echo json_encode(["status" => false, "message" => "Missing required fields"]);
    exit;
}

// إضافة المهمة في قاعدة البيانات
$sql = "INSERT INTO tasks (created_by, customer_id, task_type, status, notes, attachment, created_at, updated_at)
        VALUES (?, ?, ?, ?, ?, ?, NOW(), NOW())";

$stmt = $pdo->prepare($sql);
$ok = $stmt->execute([$created_by, $customer_id, $task_type, $status, $notes, $attachment]);

if ($ok) {
    echo json_encode([
        "status" => true,
        "message" => "Task added successfully",
        "task_id" => $pdo->lastInsertId()
    ]);
} else {
    echo json_encode(["status" => false, "message" => "Failed to add task"]);
}

<?php

header("Access-Control-Allow-Origin: https://ebaaptl.com");
header("Content-Type: application/json");
require_once "../config.php"; // ملف الاتصال بالداتابيز

$token = $_GET["token"] ?? "";

// استخراج user_id من token
$stmt = $pdo->prepare("SELECT user_id FROM user_tokens WHERE token=? ORDER BY id DESC LIMIT 1");
$stmt->execute([$token]);
$user = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$user) {
    echo json_encode(["error" => "Invalid token"]);
    exit;
}

$user_id = $user["user_id"];

// =======================
// 1) زيارات اليوم
// =======================

$stmt = $pdo->prepare("
    SELECT COUNT(*) 
    FROM visits 
    WHERE field_user_id = ? 
    AND DATE(visited_at) = CURDATE()
");
$stmt->execute([$user_id]);
$today_visits = $stmt->fetchColumn();

// =======================
// 2) عدد العملاء
// =======================

$stmt = $pdo->prepare("
    SELECT COUNT(*) 
    FROM customers 
    WHERE created_by = ?
");
$stmt->execute([$user_id]);
$customers = $stmt->fetchColumn();

// =======================
// 3) مهام مفتوحة
// =======================

$stmt = $pdo->prepare("
    SELECT COUNT(*) 
    FROM tasks 
    WHERE created_by = ? 
    AND status != 'done'
");
$stmt->execute([$user_id]);
$open_tasks = $stmt->fetchColumn();

// =======================
// 4) آخر زيارة
// =======================

$stmt = $pdo->prepare("
    SELECT visited_at 
    FROM visits 
    WHERE field_user_id = ? 
    ORDER BY visited_at DESC 
    LIMIT 1
");
$stmt->execute([$user_id]);
$last_visit = $stmt->fetchColumn() ?: null;

// =======================
// 5) آخر 5 زيارات
// =======================

$stmt = $pdo->prepare("
    SELECT v.*, c.name AS customer_name 
    FROM visits v
    LEFT JOIN customers c ON v.customer_id = c.id
    WHERE v.field_user_id = ?
    ORDER BY v.visited_at DESC
    LIMIT 5
");
$stmt->execute([$user_id]);
$recent = $stmt->fetchAll(PDO::FETCH_ASSOC);

// =======================
// إرسال الرد
// =======================

echo json_encode([
    "today_visits"    => (int)$today_visits,
    "customers"       => (int)$customers,
    "open_tasks"      => (int)$open_tasks,
    "last_visit_time" => $last_visit,
    "recent_visits"   => $recent
]);

<?php

$allowedOrigins = [
    "https://ebaaptl.com",
    "https://www.ebaaptl.com"
];

if (isset($_SERVER['HTTP_ORIGIN']) && in_array($_SERVER['HTTP_ORIGIN'], $allowedOrigins)) {
    header("Access-Control-Allow-Origin: " . $_SERVER['HTTP_ORIGIN']);
}

header("Access-Control-Allow-Headers: Content-Type, Authorization");
header("Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS");

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit();
}


header("Content-Type: application/json");
require_once "../config.php";

// قراءة بيانات JSON القادمة من التطبيق
$input = json_decode(file_get_contents("php://input"), true);
$email = $input['email'] ?? '';
$password = $input['password'] ?? '';

if (empty($email) || empty($password)) {
    echo json_encode(["status" => "error", "message" => "Email and password are required"]);
    exit();
}


// التحقق من وجود اتصال PDO
if (!isset($pdo)) {
    echo json_encode(["status" => false, "message" => "PDO NOT LOADED"]);
    exit;
}

// البحث عن المستخدم
$stmt = $pdo->prepare("SELECT * FROM users WHERE phone = ?");
$stmt->execute([$phone]);
$user = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$user) {
    echo json_encode(["status" => false, "message" => "User not found"]);
    exit;
}

// التحقق من كلمة المرور
if (!password_verify($password, $user["password"])) {
    echo json_encode(["status" => false, "message" => "Incorrect password"]);
    exit;
}

// إنشاء توكن جديد
$token = bin2hex(random_bytes(20));

$stmt = $pdo->prepare("INSERT INTO user_tokens (user_id, token, created_at) VALUES (?, ?, NOW())");
$stmt->execute([$user["id"], $token]);

// إخراج البيانات
echo json_encode([
    "status" => true,
    "message" => "Login successful",
    "data" => [
        "id" => $user["id"],
        "name" => $user["name"],
        "phone" => $user["phone"],
        "role_id" => $user["role_id"],
        "branch_id" => $user["branch_id"],
        "team_id" => $user["team_id"],
        "token" => $token
    ]
]);

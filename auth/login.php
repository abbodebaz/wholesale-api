<?php
header("Content-Type: application/json; charset=UTF-8");
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: POST");

// استدعاء ملف الكونفيق
$config = include __DIR__ . "/../config.php";

// الاتصال بقاعدة البيانات باستخدام المتغيرات البيئية
$conn = new mysqli(
    $config['host'],
    $config['user'],
    $config['pass'],
    $config['db']
);

if ($conn->connect_error) {
    echo json_encode([
        "status" => false,
        "message" => "Database connection failed: " . $conn->connect_error
    ]);
    exit;
}

$data = json_decode(file_get_contents("php://input"), true);

$phone = $data['phone'] ?? "";
$password = $data['password'] ?? "";

// تحقق من القيم
if (empty($phone) || empty($password)) {
    echo json_encode([
        "status" => false,
        "message" => "phone and password are required"
    ]);
    exit;
}

$stmt = $conn->prepare("SELECT id, name, phone, password FROM agents WHERE phone = ?");
$stmt->bind_param("s", $phone);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows == 0) {
    echo json_encode([
        "status" => false,
        "message" => "Agent not found"
    ]);
    exit;
}

$user = $result->fetch_assoc();

// التأكد من كلمة المرور (لازم تكون password_hash)
if (!password_verify($password, $user['password'])) {
    echo json_encode([
        "status" => false,
        "message" => "Incorrect password"
    ]);
    exit;
}

$token = bin2hex(random_bytes(16));

// حفظ التوكن في جدول tokens
$conn->query("INSERT INTO agent_tokens (agent_id, token, created_at) VALUES ('{$user['id']}', '$token', NOW())");

echo json_encode([
    "status" => true,
    "message" => "Login successful",
    "data" => [
        "id" => $user['id'],
        "name" => $user['name'],
        "phone" => $user['phone'],
        "token" => $token
    ]
]);
?>

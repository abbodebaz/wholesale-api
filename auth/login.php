<?php
header("Content-Type: application/json; charset=UTF-8");
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: POST");

// استدعاء الكونفيق
$config = include __DIR__ . "/../config.php";

// الاتصال بقاعدة البيانات
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

// التحقق من المدخلات
if (empty($phone) || empty($password)) {
    echo json_encode([
        "status" => false,
        "message" => "phone and password are required"
    ]);
    exit;
}

// البحث في جدول users
$stmt = $conn->prepare("SELECT id, name, phone, password, role_id, branch_id, team_id FROM users WHERE phone = ?");
$stmt->bind_param("s", $phone);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows == 0) {
    echo json_encode([
        "status" => false,
        "message" => "User not found"
    ]);
    exit;
}

$user = $result->fetch_assoc();

// التحقق من صحة كلمة المرور
if (!password_verify($password, $user['password'])) {
    echo json_encode([
        "status" => false,
        "message" => "Incorrect password"
    ]);
    exit;
}

// إنشاء token
$token = bin2hex(random_bytes(16));

// إنشاء جدول tokens إذا ما كان موجود (مرة واحدة فقط)
$conn->query("
    CREATE TABLE IF NOT EXISTS user_tokens (
        id INT AUTO_INCREMENT PRIMARY KEY,
        user_id INT,
        token VARCHAR(255),
        created_at DATETIME,
        INDEX(user_id)
    )
");

// حفظ التوكن
$stmt2 = $conn->prepare("INSERT INTO user_tokens (user_id, token, created_at) VALUES (?, ?, NOW())");
$stmt2->bind_param("is", $user['id'], $token);
$stmt2->execute();

// الاستجابة
echo json_encode([
    "status" => true,
    "message" => "Login successful",
    "data" => [
        "id" => $user['id'],
        "name" => $user['name'],
        "phone" => $user['phone'],
        "role_id" => $user['role_id'],
        "branch_id" => $user['branch_id'],
        "team_id" => $user['team_id'],
        "token" => $token
    ]
]);
?>

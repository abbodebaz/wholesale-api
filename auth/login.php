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

$phone = $data['phone'] ?? "";
$password = $data['password'] ?? "";

if (empty($phone) || empty($password)) {
    echo json_encode([
        "status" => false,
        "message" => "phone and password are required"
    ]);
    exit;
}

$stmt = $pdo->prepare("SELECT * FROM users WHERE phone = ?");
$stmt->execute([$phone]);
$user = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$user) {
    echo json_encode([
        "status" => false,
        "message" => "User not found"
    ]);
    exit;
}

if (!password_verify($password, $user['password'])) {
    echo json_encode([
        "status" => false,
        "message" => "Incorrect password"
    ]);
    exit;
}

$token = bin2hex(random_bytes(16));

$pdo->exec("
CREATE TABLE IF NOT EXISTS user_tokens (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT,
    token VARCHAR(255),
    created_at DATETIME,
    INDEX(user_id)
)
");

$stmt2 = $pdo->prepare("INSERT INTO user_tokens (user_id, token, created_at) VALUES (?, ?, NOW())");
$stmt2->execute([$user['id'], $token]);

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

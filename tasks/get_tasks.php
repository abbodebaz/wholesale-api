<?php
header("Content-Type: application/json");

// ================================
//   🧪 فحص مسار config.php
// ================================
$paths = [
    __DIR__ . "/../config.php",
    __DIR__ . "/config.php",
    __DIR__ . "/../../config.php",
    __DIR__ . "/../..//config.php",
];

$loaded = false;

foreach ($paths as $p) {
    if (file_exists($p)) {
        require_once $p;
        $loaded = true;
        break;
    }
}

if (!$loaded) {
    echo json_encode([
        "status" => false,
        "message" => "Config file not found",
        "tried_paths" => $paths
    ]);
    exit;
}

// ================================
//   🧪 فحص PDO بعد تحميل الكونفيق
// ================================
if (!isset($pdo)) {
    echo json_encode([
        "status" => false,
        "message" => "PDO NOT LOADED – config.php did not create \$pdo"
    ]);
    exit;
}

// ================================
//   🧪 قراءة التوكن
// ================================
$data = json_decode(file_get_contents("php://input"), true);
$token = $data["token"] ?? "";

if (empty($token)) {
    echo json_encode(["status" => false, "message" => "Token required"]);
    exit;
}

// ================================
//   🧪 التحقق من التوكن
// ================================
$stmt = $pdo->prepare("SELECT user_id FROM user_tokens WHERE token = ?");
$stmt->execute([$token]);
$tokenRow = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$tokenRow) {
    echo json_encode(["status" => false, "message" => "Invalid token"]);
    exit;
}

$user_id = $tokenRow["user_id"];

// ================================
//   ✔️ جلب مهام هذا المستخدم
// ================================
$sql = "
    SELECT 
        id,
        created_by,
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
";

$stmt = $pdo->prepare($sql);
$stmt->execute([$user_id]);
$tasks = $stmt->fetchAll(PDO::FETCH_ASSOC);

// ================================
//   🚀 الإخراج النهائي
// ================================
echo json_encode([
    "status" => true,
    "data" => $tasks
]);


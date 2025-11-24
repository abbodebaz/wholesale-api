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

$input = json_decode(file_get_contents("php://input"), true);

$token       = $input["token"]       ?? "";
$customer_id = $input["customer_id"] ?? "";
$notes       = $input["notes"]       ?? "";
$images      = $input["images"]      ?? [];
$lat         = $input["lat"]         ?? "";
$lng         = $input["lng"]         ?? "";

// check
if (empty($token) || empty($customer_id) || empty($lat) || empty($lng)) {
    echo json_encode(["status" => false, "message" => "Missing required fields"]);
    exit;
}

// token check
$stmt = $pdo->prepare("SELECT user_id FROM user_tokens WHERE token = ?");
$stmt->execute([$token]);
$user = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$user) {
    echo json_encode(["status" => false, "message" => "Invalid token"]);
    exit;
}

$field_user_id = $user["user_id"];

// === FIXED: Correct Hostinger upload path ===
$uploadURL  = "https://ebaaptl.com/wholesale/uploads/visits/";
$uploadDIR  = "/home/u630342272/domains/ebaaptl.com/public_html/wholesale/uploads/visits/";

if (!is_dir($uploadDIR)) {
    mkdir($uploadDIR, 0777, true);
}

$imageNames = [];

if (!empty($images)) {
    foreach ($images as $imgBase64) {

        $data = explode(",", $imgBase64);
        $decoded = base64_decode(end($data));

        $fileName = uniqid("visit_") . ".jpg";
        $fullPath = $uploadDIR . $fileName;

        error_reporting(E_ALL);
        ini_set('display_errors', 1);

$write = file_put_contents($fullPath, $decoded);

if ($write === false) {
    echo json_encode([
        "status" => false,
        "message" => "Failed to write file",
        "path" => $fullPath,
        "dir_exists" => is_dir(dirname($fullPath)),
        "dir_writable" => is_writable(dirname($fullPath)),
        "file_writable" => is_writable($uploadDIR)
    ]);
    exit;
}

        $imageNames[] = $fileName;
    }
}

$imagesJSON = json_encode($imageNames);

// save visit
$stmt = $pdo->prepare("
    INSERT INTO visits (field_user_id, customer_id, notes, images, visited_at, lat, lng)
    VALUES (?, ?, ?, ?, NOW(), ?, ?)
");

$ok = $stmt->execute([
    $field_user_id,
    $customer_id,
    $notes,
    $imagesJSON,
    $lat,
    $lng
]);

echo json_encode([
    "status" => $ok,
    "message" => $ok ? "Visit added successfully" : "Failed to add visit"
]);

<?php
// marquee-api/login.php

header("Access-Control-Allow-Origin: *");
header("Content-Type: application/json; charset=UTF-8");
header("Access-Control-Allow-Methods: POST, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type, Access-Control-Allow-Headers, Authorization, X-Requested-With");

require_once 'config.php';

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit();
}

$data = json_decode(file_get_contents("php://input"));

if (!isset($data->name) || !isset($data->pin)) {
    http_response_code(400);
    echo json_encode(["message" => "Name and PIN are required."]);
    exit();
}

try {
    // 1. Find the user
    $stmt = $pdo->prepare("SELECT * FROM users WHERE name = :name LIMIT 1");
    $stmt->execute(['name' => $data->name]);
    $user = $stmt->fetch();

    // 2. Verify the PIN
    if ($user && password_verify($data->pin, $user['pin_hash'])) {
        // Success! Return the user info (BUT NOT THE HASH)
        echo json_encode([
            "success" => true,
            "user" => [
                "id" => $user['id'],
                "name" => $user['name'],
                "role" => $user['role']
            ]
        ]);
    } else {
        // Failed
        http_response_code(401);
        echo json_encode(["success" => false, "message" => "Invalid credentials."]);
    }

} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode(["error" => $e->getMessage()]);
}
?>
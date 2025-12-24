<?php
// marquee-api/save_comment.php
header("Access-Control-Allow-Origin: *");
header("Content-Type: application/json; charset=UTF-8");
header("Access-Control-Allow-Methods: POST, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type, Access-Control-Allow-Headers, Authorization, X-Requested-With");

require_once 'config.php';

// Handle Preflight
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit();
}

$data = json_decode(file_get_contents("php://input"));

if (!isset($data->date) || !isset($data->user_id) || !isset($data->message)) {
    http_response_code(400);
    echo json_encode(["message" => "Emma says: Missing data."]);
    exit();
}

try {
    $sql = "INSERT INTO comments (date, user_id, message) VALUES (:date, :uid, :msg)";
    $stmt = $pdo->prepare($sql);
    
    $stmt->execute([
        ':date' => $data->date,
        ':uid' => $data->user_id,
        ':msg' => $data->message
    ]);

    // Return the new ID so the frontend can update instantly
    echo json_encode(["success" => true, "id" => $pdo->lastInsertId()]);

} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode(["error" => $e->getMessage()]);
}
?>
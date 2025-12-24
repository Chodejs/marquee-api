<?php
// marquee-api/edit_comment.php
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

if (!isset($data->id) || !isset($data->user_id) || !isset($data->message)) {
    http_response_code(400);
    echo json_encode(["message" => "Missing data."]);
    exit();
}

try {
    // 1. Verify Ownership (Only the author can edit text)
    $checkStmt = $pdo->prepare("SELECT user_id FROM comments WHERE id = :id");
    $checkStmt->execute([':id' => $data->id]);
    $comment = $checkStmt->fetch();

    if (!$comment || $comment['user_id'] != $data->user_id) {
        http_response_code(403);
        echo json_encode(["message" => "Permission denied."]);
        exit();
    }

    // 2. Update
    $updateStmt = $pdo->prepare("UPDATE comments SET message = :msg WHERE id = :id");
    $updateStmt->execute([':msg' => $data->message, ':id' => $data->id]);

    echo json_encode(["success" => true]);

} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode(["error" => $e->getMessage()]);
}
?>
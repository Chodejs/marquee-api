<?php
// marquee-api/delete_comment.php
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

if (!isset($data->id) || !isset($data->user_id)) {
    http_response_code(400);
    echo json_encode(["message" => "Missing ID or User."]);
    exit();
}

try {
    // 1. Get the comment owner and the requestor's role
    $checkSql = "SELECT c.user_id as owner_id, u.role as requestor_role 
                 FROM comments c 
                 JOIN users u ON u.id = :uid 
                 WHERE c.id = :cid";
    
    $stmt = $pdo->prepare($checkSql);
    $stmt->execute([':uid' => $data->user_id, ':cid' => $data->id]);
    $info = $stmt->fetch();

    if (!$info) {
        http_response_code(404);
        echo json_encode(["message" => "Comment or User not found."]);
        exit();
    }

    // 2. Authorization: Allow if you are the Owner OR an Admin
    if ($info['owner_id'] == $data->user_id || $info['requestor_role'] === 'admin') {
        $delStmt = $pdo->prepare("DELETE FROM comments WHERE id = :id");
        $delStmt->execute([':id' => $data->id]);
        echo json_encode(["success" => true]);
    } else {
        http_response_code(403);
        echo json_encode(["message" => "You don't have permission to delete this."]);
    }

} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode(["error" => $e->getMessage()]);
}
?>
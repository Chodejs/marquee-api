<?php
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

// 1. Auth Check
requireAuth($data);

if (!isset($data->dates) || !is_array($data->dates)) {
    http_response_code(400);
    echo json_encode(["message" => "Emma says: No dates provided."]);
    exit();
}

try {
    // We use "WHERE IN" clause to delete multiple rows efficiently
    // Create placeholders like (?,?,?) based on count of dates
    $placeholders = str_repeat('?,', count($data->dates) - 1) . '?';
    
    // We accept the list of dates
    $sql = "DELETE FROM calendar_days WHERE date IN ($placeholders)";
    
    $stmt = $pdo->prepare($sql);
    $stmt->execute($data->dates);
    
    echo json_encode([
        "message" => "Deleted " . $stmt->rowCount() . " days.",
        "deleted_count" => $stmt->rowCount()
    ]);

} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode(["error" => $e->getMessage()]);
}
?>
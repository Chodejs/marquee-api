<?php
// marquee-api/delete_day.php
// ... Headers ...
header("Access-Control-Allow-Origin: *");
header("Content-Type: application/json; charset=UTF-8");
header("Access-Control-Allow-Methods: POST, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type, Access-Control-Allow-Headers, Authorization, X-Requested-With");

require_once 'config.php';

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') { http_response_code(200); exit(); }

$data = json_decode(file_get_contents("php://input"));

// 1. SECURITY CHECK
requireAuth($data);

if (!isset($data->date)) {
    http_response_code(400);
    echo json_encode(["message" => "Emma says: I need a date to delete."]);
    exit();
}

try {
    $stmt = $pdo->prepare("DELETE FROM calendar_days WHERE date = :date");
    $stmt->execute([':date' => $data->date]);
    echo json_encode(["message" => "Day cleared successfully."]);
} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode(["error" => $e->getMessage()]);
}
?>
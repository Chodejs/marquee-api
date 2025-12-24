<?php
// marquee-api/delete_day.php
header("Access-Control-Allow-Origin: *");
header("Content-Type: application/json; charset=UTF-8");
header("Access-Control-Allow-Methods: POST");
header("Access-Control-Allow-Headers: Content-Type, Access-Control-Allow-Headers, Authorization, X-Requested-With");

require_once 'config.php';

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit();
}

$data = json_decode(file_get_contents("php://input"));

if (!isset($data->date)) {
    http_response_code(400);
    echo json_encode(["message" => "Emma says: I need a date to delete."]);
    exit();
}

try {
    // We delete based on the unique date
    $stmt = $pdo->prepare("DELETE FROM calendar_days WHERE date = :date");
    $stmt->execute([':date' => $data->date]);

    echo json_encode(["message" => "Day cleared successfully."]);

} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode(["error" => $e->getMessage()]);
}
?>
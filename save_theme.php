<?php
// marquee-api/save_theme.php
header("Access-Control-Allow-Origin: *");
header("Content-Type: application/json; charset=UTF-8");
header("Access-Control-Allow-Methods: POST");
header("Access-Control-Allow-Headers: Content-Type, Access-Control-Allow-Headers, Authorization, X-Requested-With");

require_once 'config.php';

// Handle preflight
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit();
}

$data = json_decode(file_get_contents("php://input"));

if (!isset($data->month) || !isset($data->year) || !isset($data->title)) {
    http_response_code(400);
    echo json_encode(["message" => "Emma says: We need a month, year, and title."]);
    exit();
}

try {
    // Unique key is (month, year), so we can use ON DUPLICATE KEY UPDATE
    $sql = "INSERT INTO monthly_themes (month, year, title, description)
            VALUES (:month, :year, :title, :description)
            ON DUPLICATE KEY UPDATE
            title = VALUES(title),
            description = VALUES(description)";

    $stmt = $pdo->prepare($sql);
    
    $stmt->execute([
        ':month' => $data->month,
        ':year' => $data->year,
        ':title' => $data->title,
        ':description' => $data->description ?? ''
    ]);

    echo json_encode(["message" => "Theme saved successfully."]);

} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode(["error" => $e->getMessage()]);
}
?>
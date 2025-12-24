<?php
// marquee-api/save_theme.php
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

if (!isset($data->month) || !isset($data->year) || !isset($data->title)) {
    http_response_code(400);
    echo json_encode(["message" => "Emma says: Missing theme data."]);
    exit();
}

try {
    $sql = "INSERT INTO monthly_themes (month, year, title, description, theme_color)
            VALUES (:month, :year, :title, :description, :color)
            ON DUPLICATE KEY UPDATE
            title = VALUES(title),
            description = VALUES(description),
            theme_color = VALUES(theme_color)";

    $stmt = $pdo->prepare($sql);
    
    $stmt->execute([
        ':month' => $data->month,
        ':year' => $data->year,
        ':title' => $data->title,
        ':description' => $data->description ?? '',
        ':color' => $data->theme_color ?? '#38bdf8'
    ]);

    echo json_encode(["message" => "Theme saved successfully."]);

} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode(["error" => $e->getMessage()]);
}
?>
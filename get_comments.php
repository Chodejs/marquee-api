<?php
// marquee-api/get_comments.php
header("Access-Control-Allow-Origin: *");
header("Content-Type: application/json; charset=UTF-8");
header("Access-Control-Allow-Methods: GET");

require_once 'config.php';

if (!isset($_GET['date'])) {
    http_response_code(400);
    echo json_encode([]);
    exit();
}

try {
    // Join with users table to get the name of the author
    $sql = "SELECT c.id, c.message, c.created_at, u.name as author_name, u.id as author_id
            FROM comments c
            JOIN users u ON c.user_id = u.id
            WHERE c.date = :date
            ORDER BY c.created_at ASC"; // Oldest first (like a chat log)

    $stmt = $pdo->prepare($sql);
    $stmt->execute([':date' => $_GET['date']]);
    
    echo json_encode($stmt->fetchAll());

} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode(["error" => $e->getMessage()]);
}
?>
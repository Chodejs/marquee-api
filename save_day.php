<?php
// marquee-api/save_day.php

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

// Basic Validation
if (!isset($data->date) || !isset($data->movie_tmdb_id) || !isset($data->movie_title)) {
    http_response_code(400);
    echo json_encode(["message" => "Emma says: Missing required data."]);
    exit();
}

try {
    // --- VETO LOGIC START ---
    $vetoedByToSave = null;
    
    // Check if the user requested a VETO
    // We expect 'vetoed' to be true/false from frontend, and 'user_id' to be sent if logged in
    if (isset($data->vetoed) && $data->vetoed === true) {
        
        if (!isset($data->user_id)) {
            http_response_code(401);
            echo json_encode(["message" => "You must be logged in to veto."]);
            exit();
        }

        $userId = $data->user_id;

        // 1. Count existing vetoes for this user in this month
        // We look at the month/year of the TARGET date they are trying to save
        $countSql = "SELECT COUNT(*) as count FROM calendar_days 
                     WHERE MONTH(date) = MONTH(:date) 
                     AND YEAR(date) = YEAR(:date) 
                     AND vetoed_by = :uid
                     AND date != :currentDate"; // Don't count the current day if updating same day
        
        $countStmt = $pdo->prepare($countSql);
        $countStmt->execute([
            ':date' => $data->date, 
            ':uid' => $userId,
            ':currentDate' => $data->date
        ]);
        $row = $countStmt->fetch();
        $usedVetoes = (int)$row['count'];

        // 2. Enforce Limit
        if ($usedVetoes >= 3) {
            http_response_code(403); // Forbidden
            echo json_encode([
                "message" => "Veto limit reached! You have used {$usedVetoes} of 3 vetoes this month."
            ]);
            exit();
        }

        // If safe, set the ID to save
        $vetoedByToSave = $userId;
    }
    // --- VETO LOGIC END ---


    // SQL Query: Updated to use vetoed_by instead of vetoed
    $sql = "INSERT INTO calendar_days 
            (date, movie_tmdb_id, movie_title, movie_poster_url, movie_runtime, picked_by, dinner_title, dinner_show_title, vetoed_by)
            VALUES (:date, :tmdb_id, :title, :poster, :runtime, :picked_by, :dinner, :dinner_show, :vetoed_by)
            ON DUPLICATE KEY UPDATE
            movie_tmdb_id = VALUES(movie_tmdb_id),
            movie_title = VALUES(movie_title),
            movie_poster_url = VALUES(movie_poster_url),
            movie_runtime = VALUES(movie_runtime),
            picked_by = VALUES(picked_by),
            dinner_title = VALUES(dinner_title),
            dinner_show_title = VALUES(dinner_show_title),
            vetoed_by = VALUES(vetoed_by)";

    $stmt = $pdo->prepare($sql);
    
    $stmt->execute([
        ':date' => $data->date,
        ':tmdb_id' => $data->movie_tmdb_id,
        ':title' => $data->movie_title,
        ':poster' => $data->movie_poster_url ?? '',
        ':runtime' => $data->movie_runtime ?? 0,
        ':picked_by' => $data->picked_by ?? 'Family',
        ':dinner' => $data->dinner_title ?? null,
        ':dinner_show' => $data->dinner_show_title ?? null,
        ':vetoed_by' => $vetoedByToSave // Will be User ID or NULL
    ]);

    echo json_encode([
        "message" => "Day updated successfully.", 
        "id" => $pdo->lastInsertId(),
        "vetoes_used" => isset($usedVetoes) ? $usedVetoes + 1 : null
    ]);

} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode(["error" => $e->getMessage()]);
}
?>
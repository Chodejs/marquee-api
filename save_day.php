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

// 1. SECURITY CHECK
requireAuth($data);

// Basic Validation
if (!isset($data->date) || !isset($data->movie_tmdb_id) || !isset($data->movie_title)) {
    http_response_code(400);
    echo json_encode(["message" => "Emma says: Missing required data."]);
    exit();
}

try {
    // --- NEW: Determine Duration ---
    // If frontend sends duration_days, use it. Otherwise 1.
    // We cap it at 60 just to prevent infinite loops if something goes wrong.
    $duration = isset($data->duration_days) ? (int)$data->duration_days : 1;
    if ($duration < 1) $duration = 1;
    if ($duration > 60) $duration = 60;

    $baseDate = new DateTime($data->date);
    $savedCount = 0;

    // --- NEW: Loop through the duration ---
    for ($i = 0; $i < $duration; $i++) {
        
        // Calculate the date for this iteration
        // Clone the base date so we don't mutate the original on every loop
        $currentDateObj = clone $baseDate;
        $currentDateObj->modify("+$i day");
        $loopDateStr = $currentDateObj->format('Y-m-d');

        // --- VETO LOGIC (Inside loop now) ---
        $vetoedByToSave = null;
        if (isset($data->vetoed) && $data->vetoed === true) {
            $userId = $data->user_id; 

            // Check how many vetoes this user has used in the target month
            $countSql = "SELECT COUNT(*) as count FROM calendar_days 
                         WHERE MONTH(date) = MONTH(:date) 
                         AND YEAR(date) = YEAR(:date) 
                         AND vetoed_by = :uid
                         AND date != :currentDate";
            
            $countStmt = $pdo->prepare($countSql);
            $countStmt->execute([
                ':date' => $loopDateStr, 
                ':uid' => $userId,
                ':currentDate' => $loopDateStr
            ]);
            $row = $countStmt->fetch();
            $usedVetoes = (int)$row['count'];

            if ($usedVetoes >= 3) {
                // If they hit the limit mid-loop, we stop and warn them.
                // Previous days in the loop are already saved (which is fine).
                http_response_code(403); 
                echo json_encode([
                    "message" => "Veto limit reached on $loopDateStr! Saved $savedCount days."
                ]);
                exit();
            }
            $vetoedByToSave = $userId;
        }

        // --- SAVE LOGIC ---
        $sql = "INSERT INTO calendar_days 
                (date, movie_tmdb_id, movie_title, movie_poster_url, movie_runtime, picked_by, dinner_title, vetoed_by)
                VALUES (:date, :tmdb_id, :title, :poster, :runtime, :picked_by, :dinner, :vetoed_by)
                ON DUPLICATE KEY UPDATE
                movie_tmdb_id = VALUES(movie_tmdb_id),
                movie_title = VALUES(movie_title),
                movie_poster_url = VALUES(movie_poster_url),
                movie_runtime = VALUES(movie_runtime),
                picked_by = VALUES(picked_by),
                dinner_title = VALUES(dinner_title),
                vetoed_by = VALUES(vetoed_by)";

        $stmt = $pdo->prepare($sql);
        
        $stmt->execute([
            ':date' => $loopDateStr,
            ':tmdb_id' => $data->movie_tmdb_id,
            ':title' => $data->movie_title,
            ':poster' => $data->movie_poster_url ?? '',
            ':runtime' => $data->movie_runtime ?? 0,
            ':picked_by' => $data->picked_by ?? 'Family',
            // Only save dinner on the first day ($i === 0), otherwise NULL
            ':dinner' => ($i === 0) ? ($data->dinner_title ?? null) : null,
            ':vetoed_by' => $vetoedByToSave 
        ]);

        $savedCount++;
    }

    echo json_encode([
        "message" => "Success! Covered $savedCount days with this show.", 
        "id" => $pdo->lastInsertId(), // ID of the last one inserted
        "days_saved" => $savedCount
    ]);

} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode(["error" => $e->getMessage()]);
}
?>
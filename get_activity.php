<?php
// marquee-api/get_activity.php

header("Access-Control-Allow-Origin: *");
header("Content-Type: application/json; charset=UTF-8");
header("Access-Control-Allow-Methods: GET");

require_once 'config.php';

try {
    $activity = [];

    // 1. Fetch Recent Comments (Limit 10)
    $stmtComments = $pdo->query("
        SELECT 
            c.id, 
            c.message, 
            c.created_at, 
            u.name as user_name, 
            'comment' as type, 
            c.date as event_date 
        FROM comments c 
        JOIN users u ON c.user_id = u.id 
        ORDER BY c.created_at DESC 
        LIMIT 10
    ");
    $comments = $stmtComments->fetchAll();

    // 2. Fetch Recent Calendar Updates (Limit 10)
    // We get the movie title and who vetoed it (if anyone)
    $stmtUpdates = $pdo->query("
        SELECT 
            cd.id, 
            cd.movie_title, 
            cd.picked_by, 
            cd.vetoed_by, 
            cd.updated_at as created_at, 
            'update' as type, 
            cd.date as event_date, 
            u.name as vetoer_name
        FROM calendar_days cd
        LEFT JOIN users u ON cd.vetoed_by = u.id
        WHERE cd.updated_at IS NOT NULL
        ORDER BY cd.updated_at DESC 
        LIMIT 10
    ");
    $updates = $stmtUpdates->fetchAll();

    // 3. Merge and Sort
    $activity = array_merge($comments, $updates);

    // Sort by Date (Newest first)
    usort($activity, function($a, $b) {
        return strtotime($b['created_at']) - strtotime($a['created_at']);
    });

    // Return only the top 10 most recent events
    echo json_encode(array_slice($activity, 0, 10));

} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode(["error" => $e->getMessage()]);
}
?>
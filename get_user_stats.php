<?php
// marquee-api/get_user_stats.php

header("Access-Control-Allow-Origin: *");
header("Content-Type: application/json; charset=UTF-8");
header("Access-Control-Allow-Methods: GET");

require_once 'config.php';

// We need a user_id to look up stats
if (!isset($_GET['user_id'])) {
    http_response_code(400);
    echo json_encode(["message" => "User ID is required."]);
    exit();
}

$userId = (int)$_GET['user_id'];
// Optional: Allow fetching stats for a specific month (defaults to current)
$month = isset($_GET['month']) ? (int)$_GET['month'] : (int)date('n');
$year = isset($_GET['year']) ? (int)$_GET['year'] : (int)date('Y');

$response = [
    'user' => null,
    'veto_count' => 0,
    'veto_limit' => 3, // Hardcoded limit for now
    'recent_picks' => [],
    'vetoed_movies' => [] // The "Wall of Shame"
];

try {
    // 1. Get User Name (to match against the 'picked_by' string column)
    $userStmt = $pdo->prepare("SELECT id, name, role FROM users WHERE id = :id");
    $userStmt->execute(['id' => $userId]);
    $user = $userStmt->fetch();

    if (!$user) {
        http_response_code(404);
        echo json_encode(["message" => "User not found."]);
        exit();
    }
    $response['user'] = $user;

    // 2. Get Veto Count (Current Month)
    // Counts how many times this user ID appears in vetoed_by column for the target month
    $vetoCountSql = "SELECT COUNT(*) as count FROM calendar_days 
                     WHERE vetoed_by = :uid 
                     AND MONTH(date) = :m AND YEAR(date) = :y";
    $vetoStmt = $pdo->prepare($vetoCountSql);
    $vetoStmt->execute(['uid' => $userId, 'm' => $month, 'y' => $year]);
    $response['veto_count'] = (int)$vetoStmt->fetch()['count'];

    // 3. Get Recent Picks (Last 5)
    // Matches the string name in 'picked_by' column
    $picksSql = "SELECT id, date, movie_title, movie_poster_url, vetoed_by 
                 FROM calendar_days 
                 WHERE picked_by = :name 
                 AND (vetoed_by IS NULL OR vetoed_by = 0) -- Only show successful picks?
                 ORDER BY date DESC 
                 LIMIT 5";
    $picksStmt = $pdo->prepare($picksSql);
    $picksStmt->execute(['name' => $user['name']]);
    $response['recent_picks'] = $picksStmt->fetchAll();

    // 4. Get Veto History (Wall of Shame)
    // Movies this user personally vetoed
    $shameSql = "SELECT id, date, movie_title, movie_poster_url 
                 FROM calendar_days 
                 WHERE vetoed_by = :uid 
                 ORDER BY date DESC 
                 LIMIT 10";
    $shameStmt = $pdo->prepare($shameSql);
    $shameStmt->execute(['uid' => $userId]);
    $response['vetoed_movies'] = $shameStmt->fetchAll();

    echo json_encode($response);

} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode(["error" => $e->getMessage()]);
}
?>
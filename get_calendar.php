<?php
// marquee-api/get_calendar.php

header("Access-Control-Allow-Origin: *");
header("Content-Type: application/json; charset=UTF-8");
header("Access-Control-Allow-Methods: GET");

require_once 'config.php';

$month = isset($_GET['month']) ? (int)$_GET['month'] : (int)date('n');
$year = isset($_GET['year']) ? (int)$_GET['year'] : (int)date('Y');

$response = [
    'month' => $month,
    'year' => $year,
    'theme' => null,
    'days' => []
];

try {
    // 1. Fetch Theme
    $themeStmt = $pdo->prepare("SELECT * FROM monthly_themes WHERE month = :m AND year = :y LIMIT 1");
    $themeStmt->execute(['m' => $month, 'y' => $year]);
    $response['theme'] = $themeStmt->fetch();

    $daysSql = "SELECT 
                    cd.*, 
                    u.name as vetoed_by_name 
                FROM calendar_days cd
                LEFT JOIN users u ON cd.vetoed_by = u.id
                WHERE MONTH(cd.date) = :m AND YEAR(cd.date) = :y 
                ORDER BY cd.date ASC";
    
    $daysStmt = $pdo->prepare($daysSql);
    $daysStmt->execute(['m' => $month, 'y' => $year]);
    $response['days'] = $daysStmt->fetchAll();

    echo json_encode($response);

} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['error' => $e->getMessage()]);
}
?>
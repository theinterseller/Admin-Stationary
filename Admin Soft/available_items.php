<?php
header('Content-Type: application/json');
error_reporting(E_ALL);
ini_set('display_errors', 1);

// DB Config
$host = "localhost";
$db = "u221875567_Stationary";
$user = "u221875567_Admin_stat";
$pass = "Sandeep@8528";

try {
    $pdo = new PDO("mysql:host=$host;dbname=$db;charset=utf8mb4", $user, $pass);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    $sql = "
        SELECT item_name,
               SUM(CASE WHEN mode = 'issued' THEN quantity ELSE 0 END) AS total_issued,
               SUM(CASE WHEN mode = 'restocked' THEN quantity ELSE 0 END) AS total_restocked
        FROM stationery_log
        GROUP BY item_name
        HAVING (total_restocked - total_issued) >= 1
    ";

    $stmt = $pdo->query($sql);
    $items = [];

    while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
        $balance = $row['total_restocked'] - $row['total_issued'];
        $items[$row['item_name']] = $balance;
    }

    echo json_encode($items);
} catch (PDOException $e) {
    echo json_encode(["error" => "DB Error: " . $e->getMessage()]);
}

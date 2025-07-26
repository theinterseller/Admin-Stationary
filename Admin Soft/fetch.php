<?php
header('Content-Type: application/json');
error_reporting(E_ALL);
ini_set('display_errors', 1);

// DB connection config
$host = "localhost";
$db = "u221875567_Stationary";
$user = "u221875567_Admin_stat";
$pass = "Sandeep@8528";
$charset = "utf8mb4";

$dsn = "mysql:host=$host;dbname=$db;charset=$charset";
$options = [
  PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
  PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
  PDO::ATTR_EMULATE_PREPARES => false,
];

// Connect DB
try {
  $pdo = new PDO($dsn, $user, $pass, $options);
} catch (PDOException $e) {
  echo json_encode(["error" => "Database connection failed: " . $e->getMessage()]);
  exit;
}

// Parse JSON
$rawInput = file_get_contents("php://input");
$data = json_decode($rawInput, true);

// Validate input
if (!is_array($data)) {
  echo json_encode(["error" => "Invalid JSON input"]);
  exit;
}

$conditions = [];
$params = [];

// Filters
if (!empty($data['month'])) {
  $conditions[] = "DATE_FORMAT(timestamp, '%Y-%m') = CAST(? AS CHAR CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci)";
  $params[] = $data['month'];
}
if (!empty($data['name'])) {
  $conditions[] = "person COLLATE utf8mb4_general_ci LIKE ?";
  $params[] = "%" . $data['name'] . "%";
}
if (!empty($data['email'])) {
  $conditions[] = "email COLLATE utf8mb4_general_ci LIKE ?";
  $params[] = "%" . $data['email'] . "%";
}
if (!empty($data['vendor'])) {
  $conditions[] = "vendor COLLATE utf8mb4_general_ci LIKE ?";
  $params[] = "%" . $data['vendor'] . "%";
}
if (!empty($data['bill'])) {
  $conditions[] = "bill COLLATE utf8mb4_general_ci LIKE ?";
  $params[] = "%" . $data['bill'] . "%";
}
if (!empty($data['axiscode'])) {
  $conditions[] = "axis_code COLLATE utf8mb4_general_ci LIKE ?";
  $params[] = "%" . $data['axiscode'] . "%";
}

// Query
$sql = "SELECT * FROM stationery_log";
if (!empty($conditions)) {
  $sql .= " WHERE " . implode(" AND ", $conditions);
}
$sql .= " ORDER BY timestamp DESC";

// Execute
try {
  $stmt = $pdo->prepare($sql);
  $stmt->execute($params);
  $rows = $stmt->fetchAll();
} catch (Exception $e) {
  echo json_encode(["error" => "Query failed: " . $e->getMessage()]);
  exit;
}

// Output
$result = [];
foreach ($rows as $row) {
  $result[] = [
    'date' => $row['timestamp'],
    'entity' => $row['entity'],
    'mode' => $row['mode'],
    'person' => $row['person'],
    'vendor' => $row['vendor'],
    'bill' => $row['bill'],
    'axiscode' => $row['axis_code'] ?? '',  // optional
    'items' => [[
      'name' => $row['item_name'],
      'qty' => $row['quantity']
    ]]
  ];
}

echo json_encode($result);
?>

<?php
header('Content-Type: application/json');
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Step 1: Read JSON input
$input = json_decode(file_get_contents("php://input"), true);
$employee_id = $input['employee_id'] ?? '';
$dob = $input['dob'] ?? '';

if (empty($employee_id) || empty($dob)) {
    echo json_encode(["valid" => false, "error" => "Missing employee ID or DOB"]);
    exit;
}

// Step 2: Database connection
$host = "localhost";
$dbname = "u221875567_Stationary";
$username = "u221875567_Admin_stat";
$password = "Sandeep@8528";

$conn = new mysqli($host, $username, $password, $dbname);
if ($conn->connect_error) {
    echo json_encode(["valid" => false, "error" => "Database connection failed"]);
    exit;
}

// Step 3: Check against the correct table
$stmt = $conn->prepare("SELECT * FROM employee_master WHERE employee_id = ? AND dob = ?");
$stmt->bind_param("ss", $employee_id, $dob);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows > 0) {
    echo json_encode(["valid" => true]);
} else {
    echo json_encode(["valid" => false]);
}

$stmt->close();
$conn->close();
?>

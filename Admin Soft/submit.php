<?php
header('Content-Type: application/json');
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Decode input
$input = json_decode(file_get_contents("php://input"), true);

if (!$input) {
    http_response_code(400);
    echo json_encode(["error" => "Invalid JSON input"]);
    exit;
}

// Extract data
$entity          = $input["entity"] ?? "";
$mode            = $input["mode"] ?? "";
$person          = $input["person"] ?? "";
$vendor          = $input["vendor"] ?? "";
$bill            = $input["bill"] ?? "";
$axis_code       = $input["axis_code"] ?? "";
$employee_email  = $input["employee_email"] ?? "";
$manager_email   = $input["manager_email"] ?? "";
$items           = $input["items"] ?? [];

if (empty($person) || empty($items)) {
    http_response_code(400);
    echo json_encode(["error" => "Required fields are missing"]);
    exit;
}

// DB connection
$conn = new mysqli("localhost", "u221875567_Admin_stat", "Sandeep@8528", "u221875567_Stationary");
if ($conn->connect_error) {
    http_response_code(500);
    echo json_encode(["error" => "Database connection failed"]);
    exit;
}

// ✅ RESTOCK MODE: Direct insert
if ($mode === "restocked") {
    $validAxisCode = "Admin@123";

    if (empty($axis_code)) {
        http_response_code(400);
        echo json_encode(["error" => "Axis code is required for restocked items"]);
        exit;
    }
    if ($axis_code !== $validAxisCode) {
        http_response_code(403);
        echo json_encode(["error" => "Invalid Axis Code. Submission denied."]);
        exit;
    }

    foreach ($items as $item) {
        $stmt = $conn->prepare("INSERT INTO stationery_log (entity, mode, person, vendor, bill, axis_code, item_name, quantity) VALUES (?, ?, ?, ?, ?, ?, ?, ?)");
        $stmt->bind_param("sssssssi", $entity, $mode, $person, $vendor, $bill, $axis_code, $item['name'], $item['qty']);
        $stmt->execute();
        $stmt->close();
    }

    echo json_encode(["status" => "restock_logged"]);
    $conn->close();
    exit;
}

// ✅ ISSUE MODE: Save for approval
$items_json   = json_encode($items);
$submitted_at = date("Y-m-d H:i:s", time() + 19800); // IST

$stmt = $conn->prepare("INSERT INTO approval_requests (entity, mode, person, employee_email, manager_email, vendor, bill, axis_code, items_json, status, submitted_at) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, 'pending', ?)");
$stmt->bind_param("ssssssssss", $entity, $mode, $person, $employee_email, $manager_email, $vendor, $bill, $axis_code, $items_json, $submitted_at);

$stmt->execute();
$request_id = $stmt->insert_id;
$stmt->close();

// 🔄 Call external script to send approval email to manager
$payload = json_encode(["request_id" => $request_id]);

$ch = curl_init("https://adminstat.giveyourissuesofit.org.in/send_approval_email.php");
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_POSTFIELDS, $payload);
curl_setopt($ch, CURLOPT_HTTPHEADER, ["Content-Type: application/json"]);
$response = curl_exec($ch);
$curlError = curl_error($ch);
curl_close($ch);

// ✅ Return status
if ($curlError) {
    echo json_encode(["status" => "approval_saved", "email_error" => $curlError]);
} else {
    echo json_encode(["status" => "approval_saved", "email_response" => $response]);
}

$conn->close();
?>

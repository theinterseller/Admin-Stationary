<?php
require __DIR__ . '/phpmailer/PHPMailer.php';
require __DIR__ . '/phpmailer/SMTP.php';
require __DIR__ . '/phpmailer/Exception.php';

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

header('Content-Type: application/json');
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Step 1: Read JSON input
$input = json_decode(file_get_contents("php://input"), true);
$request_id = $input['request_id'] ?? '';

if (!$request_id) {
    http_response_code(400);
    echo json_encode(["error" => "Missing request_id"]);
    exit;
}

// Step 2: Fetch request details
$conn = new mysqli("localhost", "u221875567_Admin_stat", "Sandeep@8528", "u221875567_Stationary");
if ($conn->connect_error) {
    echo json_encode(["error" => "Database connection failed"]);
    exit;
}

$stmt = $conn->prepare("SELECT * FROM approval_requests WHERE request_id = ?");
$stmt->bind_param("i", $request_id);
$stmt->execute();
$result = $stmt->get_result();
if ($result->num_rows === 0) {
    echo json_encode(["error" => "Request not found"]);
    exit;
}
$data = $result->fetch_assoc();
$stmt->close();
$conn->close();

// Step 3: Extract fields
$manager_email  = $data['manager_email'];
$employee_email = $data['employee_email'];
$person         = $data['person'];
$entity         = $data['entity'];
$items          = json_decode($data['items_json'], true);
$approve_url    = "https://adminstat.giveyourissuesofit.org.in/approve.php?id=$request_id&action=approve";
$reject_url     = "https://adminstat.giveyourissuesofit.org.in/approve.php?id=$request_id&action=reject";

// Step 4: Build item list
$itemList = "";
foreach ($items as $item) {
    $itemList .= "- " . htmlspecialchars($item['name']) . " (Qty: " . intval($item['qty']) . ")<br>";
}

// ============================
// ✅ Send Email to Manager
// ============================
try {
    $mail = new PHPMailer(true);
    $mail->isSMTP();
    $mail->Host       = 'mail.prayatnaworld.org';
    $mail->SMTPAuth   = true;
    $mail->Username   = 'adminstationary@prayatnaworld.org';
    $mail->Password   = 'Admin@2025';
    $mail->SMTPSecure = 'ssl';
    $mail->Port       = 465;

    $mail->CharSet    = 'UTF-8';
    $mail->Encoding   = 'base64';

    $mail->setFrom('adminstationary@prayatnaworld.org', 'Stationery Approval System');
    $mail->addAddress($manager_email);

    $mail->isHTML(true);
    $mail->Subject = "Stationery Request Approval Needed – $person ($entity)";
    $mail->Body    = "
        Dear Manager,<br><br>
        You have a new stationery request from <strong>$person</strong> at <strong>$entity</strong>.<br><br>
        <b>Items Requested:</b><br>
        $itemList<br><br>
        Please review and take action:<br><br>
        <a href='$approve_url' style='background:green;color:white;padding:10px 15px;text-decoration:none;'>✅ Approve</a>
        &nbsp;&nbsp;
        <a href='$reject_url' style='background:red;color:white;padding:10px 15px;text-decoration:none;'>❌ Reject</a><br><br>
        Thank you,<br>
        Stationery Approval System
    ";
    $mail->AltBody = "New stationery request from $person at $entity. Approve: $approve_url or Reject: $reject_url";

    $mail->send();
} catch (Exception $e) {
    echo json_encode(["error" => "Manager Email Error: " . $mail->ErrorInfo]);
    exit;
}

// ============================
// ✅ Send Email to Employee
// ============================
try {
    $empMail = new PHPMailer(true);
    $empMail->isSMTP();
    $empMail->Host       = 'mail.prayatnaworld.org';
    $empMail->SMTPAuth   = true;
    $empMail->Username   = 'adminstationary@prayatnaworld.org';
    $empMail->Password   = 'Admin@2025';
    $empMail->SMTPSecure = 'ssl';
    $empMail->Port       = 465;

    $empMail->CharSet    = 'UTF-8';
    $empMail->Encoding   = 'base64';

    $empMail->setFrom('adminstationary@prayatnaworld.org', 'Stationery Approval System');
    $empMail->addAddress($employee_email);

    $empMail->isHTML(true);
    $empMail->Subject = "Your Stationery Request has been submitted";
    $empMail->Body    = "
        Dear $person,<br><br>
        Your stationery request has been successfully submitted and is currently pending approval by your manager.<br><br>
        <b>Items Requested:</b><br>
        $itemList<br><br>
        You will receive a confirmation email once your request is approved or rejected.<br><br>
        Thank you,<br>
        Stationery Approval System
    ";

    $empMail->AltBody = "Your stationery request has been submitted and is pending approval.";
    $empMail->send();
} catch (Exception $e) {
    echo json_encode(["error" => "Employee Email Error: " . $empMail->ErrorInfo]);
    exit;
}

// ✅ Done
echo json_encode(["status" => "Emails sent successfully", "request_id" => $request_id]);
?>

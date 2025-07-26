<?php
// approve.php

error_reporting(E_ALL);
ini_set('display_errors', 1);

// ✅ Load PHPMailer
require __DIR__ . '/phpmailer/PHPMailer.php';
require __DIR__ . '/phpmailer/SMTP.php';
require __DIR__ . '/phpmailer/Exception.php';

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

// DB credentials
$host     = "localhost";
$dbname   = "u221875567_Stationary";
$username = "u221875567_Admin_stat";
$password = "Sandeep@8528";

// Validate input
$request_id = $_GET['id'] ?? '';
$action     = $_GET['action'] ?? '';

if (!$request_id || !in_array($action, ['approve', 'reject'])) {
    die("<h3>❌ Invalid approval request.</h3>");
}

// DB connection
$conn = new mysqli($host, $username, $password, $dbname);
if ($conn->connect_error) {
    die("<h3>❌ DB connection failed: " . $conn->connect_error . "</h3>");
}

// Check if request exists and is still pending
$stmt = $conn->prepare("SELECT * FROM approval_requests WHERE request_id = ? AND status = 'pending'");
if (!$stmt) {
    die("<h3>❌ SQL prepare error: " . $conn->error . "</h3>");
}
$stmt->bind_param("s", $request_id);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows === 0) {
    die("<h3>⚠️ This approval link is invalid or already processed.</h3>");
}

$request         = $result->fetch_assoc();
$employee_email  = $request['employee_email'];
$person          = $request['person'];
$entity          = $request['entity'];
$items_json      = json_decode($request['items_json'], true);

// ✅ Determine status
$newStatus = $action === 'approve' ? 'approved' : 'rejected';

// ✅ If approved, insert into stationery_log
if ($newStatus === 'approved' && is_array($items_json)) {
    $insert_stmt = $conn->prepare("INSERT INTO stationery_log (item_name, quantity, mode, person, entity, vendor, bill, timestamp) VALUES (?, ?, 'issued', ?, ?, '', '', NOW())");

    foreach ($items_json as $item) {
        $item_name = $item['name'];
        $quantity  = intval($item['qty']);
        $insert_stmt->bind_param("siss", $item_name, $quantity, $person, $entity);
        $insert_stmt->execute();
    }

    $insert_stmt->close();
}

// ✅ Update request status
$update = $conn->prepare("UPDATE approval_requests SET status = ?, approved_at = NOW() WHERE request_id = ?");
$update->bind_param("ss", $newStatus, $request_id);
$update->execute();

// ✅ Display success
echo "<h2 style='color:green;'>✅ Request has been <strong>$newStatus</strong> successfully.</h2>";
echo "<p style='color:gray;'>⏰ Processed on: " . date("Y-m-d H:i:s") . "</p>";

// ✅ Email confirmation to employee (No approval link included)
if (!empty($employee_email)) {
    $itemList = "";
    foreach ($items_json as $item) {
        $itemList .= "- " . htmlspecialchars($item['name']) . " (Qty: " . intval($item['qty']) . ")<br>";
    }

    $subject = "Your Stationery Request has been $newStatus";
    $body = "
        Dear $person,<br><br>
        Your stationery request from <strong>$entity</strong> has been <strong style='color:green;'>$newStatus</strong>.<br><br>
        <b>Items Requested:</b><br>$itemList<br><br>
        Thank you,<br>
        Stationery Approval System
    ";

    $mail = new PHPMailer(true);
    try {
        $mail->isSMTP();
        $mail->Host       = 'mail.prayatnaworld.org';
        $mail->SMTPAuth   = true;
        $mail->Username   = 'adminstationary@prayatnaworld.org';
        $mail->Password   = 'Admin@2025';
        $mail->SMTPSecure = 'ssl';
        $mail->Port       = 465;

        $mail->setFrom('adminstationary@prayatnaworld.org', 'Stationery Approval System');
        $mail->addAddress($employee_email);
        $mail->isHTML(true);
        $mail->Subject = $subject;
        $mail->Body    = $body;

        $mail->send();
        echo "<p style='color:blue;'>📧 Confirmation email sent to employee.</p>";
    } catch (Exception $e) {
        echo "<p style='color:red;'>⚠️ Email error: " . $mail->ErrorInfo . "</p>";
    }
}

$conn->close();
?>

<?php
// update_approval_status.php

error_reporting(E_ALL);
ini_set('display_errors', 1);

// DB config
$host     = "localhost";
$dbname   = "u221875567_Stationary";
$username = "u221875567_Admin_stat";
$password = "Sandeep@8528";

// Admin Email
$admin_email = "adminstationary@prayatnaworld.org";

// Load PHPMailer
require __DIR__ . '/phpmailer/PHPMailer.php';
require __DIR__ . '/phpmailer/SMTP.php';
require __DIR__ . '/phpmailer/Exception.php';
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

// Connect to DB
$conn = new mysqli($host, $username, $password, $dbname);
if ($conn->connect_error) {
    http_response_code(500);
    die("❌ DB connection failed.");
}

// Get POST data
$request_id = isset($_POST['id']) ? intval($_POST['id']) : 0;
$action     = $_POST['action'] ?? '';

if ($request_id === 0 || !in_array($action, ['approve', 'reject'])) {
    http_response_code(400);
    die("❌ Invalid input.");
}

$new_status  = $action === 'approve' ? 'approved' : 'rejected';
$approved_at = date("Y-m-d H:i:s", time() + 19800); // UTC +5:30

// Update approval status
$stmt = $conn->prepare("UPDATE approval_requests SET status = ?, approved_at = ? WHERE request_id = ?");
$stmt->bind_param("ssi", $new_status, $approved_at, $request_id);

if (!$stmt->execute()) {
    http_response_code(500);
    echo "❌ Failed to update request.";
    $stmt->close();
    $conn->close();
    exit;
}

echo "✅ Request $new_status successfully.";
$stmt->close();

// Fetch request details
$query = $conn->prepare("SELECT person, entity, employee_email, items_json FROM approval_requests WHERE request_id = ?");
$query->bind_param("i", $request_id);
$query->execute();
$result = $query->get_result();

if ($result && $row = $result->fetch_assoc()) {
    $person         = $row['person'];
    $entity         = $row['entity'];
    $employee_email = $row['employee_email'];
    $items          = json_decode($row['items_json'], true);

    // Insert into log if approved
    if ($new_status === 'approved' && is_array($items)) {
        $insert_stmt = $conn->prepare("INSERT INTO stationery_log (item_name, quantity, mode, person, entity, vendor, bill, timestamp) VALUES (?, ?, 'issued', ?, ?, '', '', NOW())");

        foreach ($items as $item) {
            $item_name = $item['name'];
            $quantity  = intval($item['qty']);
            $insert_stmt->bind_param("siss", $item_name, $quantity, $person, $entity);
            $insert_stmt->execute();
        }

        $insert_stmt->close();
    }

    // Build item list for email
    $itemList = "";
    if (is_array($items)) {
        foreach ($items as $item) {
            $itemList .= "- " . htmlspecialchars($item['name']) . " (Qty: " . intval($item['qty']) . ")<br>";
        }
    }

    // Email to admin
    try {
        $mail = new PHPMailer(true);
        $mail->isSMTP();
        $mail->Host       = 'mail.prayatnaworld.org';
        $mail->SMTPAuth   = true;
        $mail->Username   = 'adminstationary@prayatnaworld.org';
        $mail->Password   = 'Admin@2025';
        $mail->SMTPSecure = 'ssl';
        $mail->Port       = 465;

        $mail->setFrom('adminstationary@prayatnaworld.org', 'Stationery Admin Panel');
        $mail->addAddress($admin_email);
        $mail->isHTML(true);
        $mail->Subject = "Request #$request_id has been $new_status by Admin";
        $mail->Body    = "
            <strong>Request ID:</strong> $request_id<br>
            <strong>Status:</strong> <span style='color:green;'>$new_status</span><br>
            <strong>Person:</strong> $person ($entity)<br>
            <strong>Employee Email:</strong> $employee_email<br><br>
            <strong>Items:</strong><br>$itemList<br>
            <br>📌 Action completed via <b>Admin Panel</b> at $approved_at.
        ";
        $mail->send();
    } catch (Exception $e) {
        error_log("Admin mail error: " . $mail->ErrorInfo);
    }

    // Email to employee — confirmation only, no links
    if (!empty($employee_email)) {
        try {
            $employeeMail = new PHPMailer(true);
            $employeeMail->isSMTP();
            $employeeMail->Host       = 'mail.prayatnaworld.org';
            $employeeMail->SMTPAuth   = true;
            $employeeMail->Username   = 'adminstationary@prayatnaworld.org';
            $employeeMail->Password   = 'Admin@2025';
            $employeeMail->SMTPSecure = 'ssl';
            $employeeMail->Port       = 465;

            $employeeMail->setFrom('adminstationary@prayatnaworld.org', 'Stationery Approval System');
            $employeeMail->addAddress($employee_email);
            $employeeMail->isHTML(true);
            $employeeMail->Subject = "Your Stationery Request has been $new_status";
            $employeeMail->Body    = "
                Dear $person,<br><br>
                Your stationery request from <strong>$entity</strong> has been <strong style='color:green;'>$new_status</strong>.<br><br>
                <b>Items Requested:</b><br>$itemList<br><br>
                Thank you,<br>
                Stationery Approval System
            ";
            $employeeMail->send();
        } catch (Exception $e) {
            error_log("Employee mail error: " . $employeeMail->ErrorInfo);
        }
    }
}

$query->close();
$conn->close();
?>

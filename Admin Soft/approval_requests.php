<?php
// approval_requests.php

$host = "localhost";
$dbname = "u221875567_Stationary";
$username = "u221875567_Admin_stat";
$password = "Sandeep@8528";

$conn = new mysqli($host, $username, $password, $dbname);
if ($conn->connect_error) {
    die("<h3>Database connection failed.</h3>");
}

$sql = "SELECT * FROM approval_requests ORDER BY request_id DESC";
$result = $conn->query($sql);
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <title>Approval Requests Panel</title>
  <style>
    body { font-family: Arial; padding: 20px; background: #f2f2f2; }
    table { width: 100%; border-collapse: collapse; background: #fff; margin-top: 20px; }
    th, td { padding: 12px; border: 1px solid #ccc; text-align: left; vertical-align: top; }
    th { background: #003366; color: white; }
    button { padding: 6px 12px; margin-right: 5px; cursor: pointer; }
    .approve { background-color: #4CAF50; color: white; }
    .reject { background-color: #f44336; color: white; }
    h2 { color: #003366; }
    ul { margin: 0; padding-left: 18px; }
    small { color: gray; }
  </style>
</head>
<body>
  <h2>📋 Stationery Approval Requests</h2>

  <table>
    <thead>
      <tr>
        <th>ID</th>
        <th>Entity</th>
        <th>Person</th>
        <th>Manager Email</th>
        <th>Employee Email</th>
        <th>Requested Items</th>
        <th>Submitted At</th>
        <th>Status</th>
        <th>Action</th>
      </tr>
    </thead>
    <tbody>
      <?php while($row = $result->fetch_assoc()): ?>
        <tr>
          <td><?= $row['request_id'] ?></td>
          <td><?= htmlspecialchars($row['entity']) ?></td>
          <td><?= htmlspecialchars($row['person']) ?></td>
          <td><?= htmlspecialchars($row['manager_email']) ?></td>
          <td><?= htmlspecialchars($row['employee_email']) ?></td>
          <td>
            <ul>
              <?php
              $items = json_decode($row['items_json'], true);
              if (is_array($items)) {
                foreach ($items as $item) {
                  echo "<li>" . htmlspecialchars($item['name']) . " (Qty: " . intval($item['qty']) . ")</li>";
                }
              } else {
                echo "<li><em>Invalid item data</em></li>";
              }
              ?>
            </ul>
          </td>
          <td>
            <?= date("d M Y, h:i A", strtotime($row['submitted_at'])) ?>
          </td>
          <td><strong><?= ucfirst($row['status']) ?></strong></td>
          <td>
            <?php if ($row['status'] === 'pending'): ?>
              <button class="approve" onclick="updateStatus(<?= $row['request_id'] ?>, 'approve')">✅ Approve</button>
              <button class="reject" onclick="updateStatus(<?= $row['request_id'] ?>, 'reject')">❌ Reject</button>
            <?php else: ?>
              -
            <?php endif; ?>
          </td>
        </tr>
      <?php endwhile; ?>
    </tbody>
  </table>

<script>
function updateStatus(id, action) {
  if (!confirm("Are you sure to " + action + " this request?")) return;

  fetch('update_approval_status.php', {
    method: 'POST',
    headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
    body: `id=${id}&action=${action}`
  })
  .then(res => res.text())
  .then(msg => {
    alert(msg);
    location.reload();
  });
}
</script>

</body>
</html>
<?php $conn->close(); ?>

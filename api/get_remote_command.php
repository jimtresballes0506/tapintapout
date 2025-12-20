<?php
require '../db_connect.php';

$door_id = intval($_GET['door_id'] ?? 0);

if (!$door_id) {
    echo json_encode(['command' => null]);
    exit;
}

// get latest pending command
$stmt = $conn->prepare("
  SELECT id, command
  FROM remote_commands
  WHERE door_id = ?
  AND status = 'pending'
  ORDER BY created_at ASC
  LIMIT 1
");
$stmt->bind_param("i", $door_id);
$stmt->execute();
$res = $stmt->get_result();

if ($row = $res->fetch_assoc()) {
    // mark as sent
    $upd = $conn->prepare("
      UPDATE remote_commands
      SET status = 'sent'
      WHERE id = ?
    ");
    $upd->bind_param("i", $row['id']);
    $upd->execute();

    echo json_encode(['command' => $row['command']]);
} else {
    echo json_encode(['command' => null]);
}

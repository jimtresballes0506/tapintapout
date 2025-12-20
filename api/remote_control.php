<?php
session_start();
require '../db_connect.php';

if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'admin') {
    http_response_code(403);
    echo json_encode(['error' => 'Unauthorized']);
    exit;
}

$door_id = intval($_POST['door_id'] ?? 0);
$action  = $_POST['action'] ?? '';

if (!$door_id || !in_array($action, ['LOCK','UNLOCK'])) {
    http_response_code(400);
    echo json_encode(['error' => 'Invalid parameters']);
    exit;
}

$stmt = $conn->prepare("
  INSERT INTO remote_commands (door_id, command)
  VALUES (?, ?)
");
$stmt->bind_param("is", $door_id, $action);
$stmt->execute();

echo json_encode(['status' => 'ok']);

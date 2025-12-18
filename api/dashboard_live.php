<?php
require __DIR__ . '/../db_connect.php';
header('Content-Type: application/json');

$rooms = $conn->query("
    SELECT 
        r.id,
        r.room_code,
        r.room_name,
        r.occupancy_status,
        al.timestamp AS last_update
    FROM rooms r
    LEFT JOIN access_logs al 
        ON al.id = (
            SELECT id 
            FROM access_logs 
            WHERE room_id = r.id
            ORDER BY timestamp DESC
            LIMIT 1
        )
")->fetch_all(MYSQLI_ASSOC);


$logs = $conn->query("
    SELECT 
        al.timestamp,
        al.event_type,
        al.access_result,
        al.access_reason,
        u.fullname,
        r.room_code,
        r.room_name
    FROM access_logs al
    LEFT JOIN users u ON al.user_id = u.id
    LEFT JOIN rooms r ON al.room_id = r.id
    ORDER BY al.timestamp DESC
    LIMIT 5
")->fetch_all(MYSQLI_ASSOC);


echo json_encode([
    'rooms' => $rooms,
    'logs'  => $logs,
    'time'  => date('H:i:s')
]);

<?php
require __DIR__ . '/../db_connect.php';
header('Content-Type: application/json');

// Get room status + last activity per room
$rooms = $conn->query("
    SELECT 
        r.id,
        r.room_code,
        r.room_name,
        r.occupancy_status,
        al.timestamp AS last_update,
        u.fullname AS last_user,
        al.event_type
    FROM rooms r
    LEFT JOIN access_logs al 
        ON al.id = (
            SELECT id 
            FROM access_logs 
            WHERE room_id = r.id
            ORDER BY timestamp DESC
            LIMIT 1
        )
    LEFT JOIN users u ON al.user_id = u.id
    ORDER BY r.room_name
")->fetch_all(MYSQLI_ASSOC);

echo json_encode([
    'rooms' => $rooms,
    'time'  => date('h:i:s A')
]);

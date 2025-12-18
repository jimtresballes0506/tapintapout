<?php
date_default_timezone_set('Asia/Manila');

header('Content-Type: application/json; charset=utf-8');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    header('Access-Control-Allow-Methods: GET,POST,OPTIONS');
    header('Access-Control-Allow-Headers: Content-Type');
    exit;
}
header('Access-Control-Allow-Origin: *');

require __DIR__ . '/../db_connect.php';

$rfid = trim($_REQUEST['rfid'] ?? '');
$door = $_REQUEST['door_id'] ?? $_REQUEST['room_id'] ?? null;
$event = $_REQUEST['event'] ?? ($_REQUEST['action'] ?? 'tap_in'); 

if ($rfid === '' || $door === null || !ctype_digit((string)$door)) {
    http_response_code(400);
    echo json_encode(['access' => 'denied', 'reason' => 'missing_parameters']);
    exit;
}
$room_id = (int)$door;
$event_type = ($event === 'tap_out') ? 'tap_out' : 'tap_in';


$rfid = strtoupper(preg_replace('/\s+/', '', $rfid));

$response = [
    'access' => 'denied',
    'reason' => 'unknown',
    'user_id' => null,
    'user_name' => null,
];


$stmt = $conn->prepare("
    SELECT t.id AS tag_id, t.rfid_uid, t.status AS tag_status, t.is_master,
       t.user_id, u.fullname, u.status AS user_status, u.role AS user_role
    FROM rfid_tags t
    LEFT JOIN users u ON t.user_id = u.id
    WHERE t.rfid_uid = ?
    LIMIT 1
");
$stmt->bind_param('s', $rfid);
$stmt->execute();
$res = $stmt->get_result();
$tag = ($res && $res->num_rows) ? $res->fetch_assoc() : null;
$stmt->close();

$user_id = null;
if (!$tag) {
    $response['reason'] = 'unknown_tag';
    $response['access'] = 'denied';
} else {
    $user_id = $tag['user_id'] ? (int)$tag['user_id'] : null;

    // Check tag status
    if ($tag['tag_status'] !== 'active') {
        $response['reason'] = 'tag_' . $tag['tag_status'];
        $response['access'] = 'denied';
    }
    // Check user status
    elseif ($user_id && $tag['user_status'] !== 'active') {
        $response['reason'] = 'user_inactive';
        $response['access'] = 'denied';
    } else {

        if ((int)$tag['is_master'] === 1) {

            $response['access'] = 'granted';
            $response['reason'] = 'admin_override';
            $response['user_id'] = $user_id;
            $response['user_name'] = $tag['fullname'] ?: 'Administrator';

        } else {

            $lastEvent = null;

            $last = $conn->prepare("
                SELECT event_type
                FROM access_logs
                WHERE user_id = ?
                AND room_id = ?
                ORDER BY timestamp DESC
                LIMIT 1
            ");
            $last->bind_param('ii', $user_id, $room_id);
            $last->execute();
            $resLast = $last->get_result();
            $last->close();

            if ($resLast && $resLast->num_rows === 1) {
                $lastEvent = $resLast->fetch_assoc()['event_type'];
            }

            if ($lastEvent === 'tap_in') {
                $event_type = 'tap_out';
            } else {
                $event_type = 'tap_in';
            }

            $dayMap = [
                'Monday'    => 'Mon',
                'Tuesday'   => 'Tue',
                'Wednesday' => 'Wed',
                'Thursday'  => 'Thu',
                'Friday'    => 'Fri',
                'Saturday'  => 'Sat',
                'Sunday'    => 'Sun',
            ];

            $currentDay  = $dayMap[date('l')];
            $currentTime = date('H:i:s');

            $sched = $conn->prepare("
                SELECT id
                FROM schedules
                WHERE room_id = ?
                AND faculty_id = ?
                AND day_of_week = ?
                AND ? BETWEEN start_time AND end_time
                LIMIT 1
            ");

            $sched->bind_param(
                'iiss',
                $room_id,
                $user_id,
                $currentDay,
                $currentTime
            );

            $sched->execute();
            $schedRes = $sched->get_result();
            $sched->close();

            if ($schedRes->num_rows === 0) {
                $response['access'] = 'denied';
                $response['reason'] = 'outside_schedule';
            } else {
                $response['access'] = 'granted';
                $response['reason'] = 'ok';
                $response['user_id'] = $user_id;
                $response['user_name'] = $tag['fullname'];
            }
        }
    }    
}

mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);

$user_id = ($user_id !== null) ? (int)$user_id : null;

$ins = $conn->prepare("
    INSERT INTO access_logs 
    (rfid_uid, user_id, room_id, event_type, access_result, access_reason, timestamp)
    VALUES (?, ?, ?, ?, ?, ?, NOW())
");

if (
    $response['access'] === 'granted'
    && $room_id
    && $tag
    && (int)$tag['is_master'] !== 1
) {

    if ($event_type === 'tap_in') {
        $stmtRoom = $conn->prepare("
            UPDATE rooms
            SET occupancy_status = 'occupied'
            WHERE id = ?
        ");
        $stmtRoom->bind_param("i", $room_id);
        $stmtRoom->execute();
        $stmtRoom->close();
    }

    if ($event_type === 'tap_out') {
        $stmtRoom = $conn->prepare("
            UPDATE rooms
            SET occupancy_status = 'available'
            WHERE id = ?
        ");
        $stmtRoom->bind_param("i", $room_id);
        $stmtRoom->execute();
        $stmtRoom->close();
    }
}


$ins->bind_param(
    'siisss',
    $rfid,
    $user_id,
    $room_id,
    $event_type,
    $response['access'],
    $response['reason']
);

$ins->execute();
$insert_id = $ins->insert_id;
$ins->close();


$response['log_id'] = $insert_id;
$response['event'] = $event_type;
echo json_encode($response);

<!-- to get the  student list -->

<?php
require_once '../admin/includes/config.php';
require_once '../admin/includes/functions.php';

$meetingId = $_GET['meeting_id'] ?? '';
$attendanceData = getAttendanceData();

header('Content-Type: application/json');

if (empty($meetingId) || !isset($attendanceData['attendees'][$meetingId])) {
    echo json_encode([]);
    exit;
}

$participants = [];
foreach ($attendanceData['attendees'][$meetingId] as $participant) {
    $participants[] = [
        'name' => $participant['name'],
        'email' => $participant['email'],
        'join_time' => date('d M Y h:i A', strtotime($participant['join_time'])),
        'leave_time' => $participant['leave_time'] === 'Still in meeting' ? 
                       'Still in meeting' : 
                       date('d M Y h:i A', strtotime($participant['leave_time'])),
        'duration' => $participant['duration'],
        'status' => $participant['status'] ?? 'completed'
    ];
}

echo json_encode($participants);
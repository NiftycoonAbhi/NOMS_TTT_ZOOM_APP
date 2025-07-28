<?php
require_once '../admin/includes/config.php';

// Set default timezone to IST
date_default_timezone_set('Asia/Kolkata');

// function to get the student list  from the meeting
function getMeetingParticipants($meetingId) {
    $token = getZoomAccessToken();
    if (!$token) {
        return ['error' => 'Could not obtain access token'];
    }

    // First try to get completed meeting data
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, "https://api.zoom.us/v2/report/meetings/{$meetingId}/participants");
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_HTTPHEADER, [
        'Authorization: Bearer ' . $token,
        'Content-Type: application/json'
    ]);

    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);

    // If completed meeting data is available
    if ($httpCode === 200) {
        $data = json_decode($response, true);
        $data['meeting_status'] = 'completed';
        return $data;
    }

    // If not found, try to get active meeting data
    return getActiveMeetingParticipants($meetingId);
}

// function to get the active student list from the running meeting that host by admin
function getActiveMeetingParticipants($meetingId) {
    $token = getZoomAccessToken();
    if (!$token) {
        return ['error' => 'Could not obtain access token'];
    }

    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, "https://api.zoom.us/v2/metrics/meetings/{$meetingId}/participants");
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_HTTPHEADER, [
        'Authorization: Bearer ' . $token,
        'Content-Type: application/json'
    ]);

    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);

    $data = json_decode($response, true);

    if ($httpCode !== 200) {
        return [
            'error' => $data['message'] ?? 'API request failed',
            'code' => $httpCode,
            'details' => $data['errors'] ?? []
        ];
    }

    // Format active meeting data to match completed meeting format
    $formattedData = [
        'meeting_status' => 'active',
        'participants' => []
    ];

    foreach ($data['participants'] as $participant) {
        $currentTime = new DateTime('now', new DateTimeZone('UTC'));
        $joinTime = new DateTime($participant['join_time'], new DateTimeZone('UTC'));
        
        $duration = $currentTime->getTimestamp() - $joinTime->getTimestamp();
        
        $formattedData['participants'][] = [
            'id' => $participant['id'] ?? uniqid(),
            'name' => $participant['name'] ?? '',
            'user_email' => $participant['email'] ?? '',
            'join_time' => $participant['join_time'],
            'leave_time' => '', // Will be empty for active participants
            'duration' => $duration
        ];
    }

    return $formattedData;
}


// to save the attendance data into the json 
function saveAttendanceData($meetingId, $participants, $meetingStatus = 'completed') {
    $data = json_decode(file_get_contents(ATTENDANCE_FILE), true);
    $timestamp = date('Y-m-d H:i:s');

    // Add meeting if not exists
    if (!isset($data['meetings'][$meetingId])) {
        $data['meetings'][$meetingId] = [
            'first_tracked' => $timestamp,
            'last_updated' => $timestamp,
            'status' => $meetingStatus
        ];
    } else {
        $data['meetings'][$meetingId]['last_updated'] = $timestamp;
        $data['meetings'][$meetingId]['status'] = $meetingStatus;
    }

    // Add participants
    foreach ($participants as $participant) {
        $userId = $participant['id'] ?? uniqid();
        
        // Convert UTC times to IST
        $joinTime = new DateTime($participant['join_time'], new DateTimeZone('UTC'));
        $joinTime->setTimezone(new DateTimeZone('Asia/Kolkata'));
        
        $leaveTime = isset($participant['leave_time']) && !empty($participant['leave_time']) ? 
            new DateTime($participant['leave_time'], new DateTimeZone('UTC')) : 
            null;
        
        if ($leaveTime) {
            $leaveTime->setTimezone(new DateTimeZone('Asia/Kolkata'));
        }
        
        // Convert duration from seconds to minutes
        $durationMinutes = round($participant['duration'] / 60, 1);

        $data['attendees'][$meetingId][$userId] = [
            'name' => $participant['name'],
            'email' => $participant['user_email'] ?? '',
            'join_time' => $joinTime->format('Y-m-d H:i:s'),
            'leave_time' => $leaveTime ? $leaveTime->format('Y-m-d H:i:s') : 'Still in meeting',
            'duration' => $durationMinutes,
            'last_updated' => $timestamp,
            'status' => empty($participant['leave_time']) ? 'active' : 'completed'
        ];
    }

    file_put_contents(ATTENDANCE_FILE, json_encode($data, JSON_PRETTY_PRINT));
    return true;
}

// function to get the atendance data to dispaly in the student dashboard
function getAttendanceData() {
    return json_decode(file_get_contents(ATTENDANCE_FILE), true);
}

// Function to get meeting participants for Excel export
function getMeetingParticipantsForExport($meetingId) {
    global $conn;
    
    // Query attendance data from database tables
    $query = "
        SELECT 
            mad.student_id,
            mad.join_time,
            mad.leave_time,
            mad.duration_minutes,
            mad.meeting_date,
            mah.start_time,
            mah.end_time,
            mah.topic,
            sd.student_name,
            sd.whatsapp as phone,
            z.course,
            z.batch,
            z.branch
        FROM meeting_att_details mad
        LEFT JOIN meeting_att_head mah ON mad.meeting_id = mah.meeting_id AND mad.meeting_date = mah.meeting_date
        LEFT JOIN student_details sd ON mad.student_id = sd.student_id
        LEFT JOIN zoom z ON mad.meeting_id = z.meeting_id AND mad.student_id = z.student_id
        WHERE mad.meeting_id = ?
        ORDER BY mad.student_id, mad.join_time
    ";
    
    $stmt = mysqli_prepare($conn, $query);
    if (!$stmt) {
        error_log("Prepare failed: " . mysqli_error($conn));
        return false;
    }
    
    mysqli_stmt_bind_param($stmt, "s", $meetingId);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);
    
    if (!$result || mysqli_num_rows($result) === 0) {
        return false;
    }
    
    $participants = [];
    $student_sessions = [];
    
    // Group sessions by student
    while ($row = mysqli_fetch_assoc($result)) {
        $student_id = $row['student_id'];
        if (!isset($student_sessions[$student_id])) {
            $student_sessions[$student_id] = [
                'student_info' => [
                    'id' => $row['student_id'],
                    'name' => $row['student_name'] ?: 'Unknown Student',
                    'phone' => $row['phone'] ?: 'N/A',
                    'course' => $row['course'] ?: 'N/A',
                    'batch' => $row['batch'] ?: 'N/A',
                    'branch' => $row['branch'] ?: 'N/A'
                ],
                'meeting_info' => [
                    'topic' => $row['topic'] ?: 'N/A',
                    'date' => $row['meeting_date'],
                    'start_time' => $row['start_time'],
                    'end_time' => $row['end_time']
                ],
                'sessions' => []
            ];
        }
        
        $student_sessions[$student_id]['sessions'][] = [
            'join_time' => $row['join_time'],
            'leave_time' => $row['leave_time'],
            'duration_minutes' => $row['duration_minutes']
        ];
    }
    
    // Calculate total attendance for each student
    foreach ($student_sessions as $student_id => $data) {
        $student_info = $data['student_info'];
        $meeting_info = $data['meeting_info'];
        $sessions = $data['sessions'];
        
        // Calculate total duration and session details
        $total_duration_minutes = 0;
        $session_details = [];
        $first_join = null;
        $last_leave = null;
        
        foreach ($sessions as $session) {
            $total_duration_minutes += $session['duration_minutes'];
            $session_details[] = [
                'join' => date('H:i:s', strtotime($session['join_time'])),
                'leave' => date('H:i:s', strtotime($session['leave_time'])),
                'duration' => $session['duration_minutes']
            ];
            
            if ($first_join === null || strtotime($session['join_time']) < strtotime($first_join)) {
                $first_join = $session['join_time'];
            }
            if ($last_leave === null || strtotime($session['leave_time']) > strtotime($last_leave)) {
                $last_leave = $session['leave_time'];
            }
        }
        
        // Calculate attendance percentage
        $meeting_duration_minutes = 0;
        if ($meeting_info['start_time'] && $meeting_info['end_time']) {
            $meeting_duration_minutes = (strtotime($meeting_info['end_time']) - strtotime($meeting_info['start_time'])) / 60;
        }
        
        $attendance_percentage = $meeting_duration_minutes > 0 ? 
            round(($total_duration_minutes / $meeting_duration_minutes) * 100, 1) : 0;
        
        // Format duration
        $hours = floor($total_duration_minutes / 60);
        $minutes = $total_duration_minutes % 60;
        $duration_formatted = ($hours > 0 ? $hours . 'h ' : '') . $minutes . 'm';
        
        $participants[] = [
            'student_id' => $student_info['id'],
            'name' => $student_info['name'],
            'phone' => $student_info['phone'],
            'course' => $student_info['course'],
            'batch' => $student_info['batch'],
            'branch' => $student_info['branch'],
            'join_time' => $first_join ? date('Y-m-d H:i:s', strtotime($first_join)) : 'N/A',
            'leave_time' => $last_leave ? date('Y-m-d H:i:s', strtotime($last_leave)) : 'N/A',
            'total_duration' => $duration_formatted,
            'duration_minutes' => $total_duration_minutes,
            'attendance_percentage' => $attendance_percentage,
            'session_count' => count($sessions),
            'session_details' => $session_details,
            'meeting_topic' => $meeting_info['topic'],
            'meeting_date' => $meeting_info['date'],
            'meeting_start' => $meeting_info['start_time'],
            'meeting_end' => $meeting_info['end_time'],
            'status' => $attendance_percentage > 50 ? 'Present' : 'Partial'
        ];
    }
    
    mysqli_stmt_close($stmt);
    return $participants;
}
<?php
require_once '../admin/includes/config.php';

// Set default timezone to IST
date_default_timezone_set('Asia/Kolkata');

function getZoomAccessToken() {
    $accountId = ZOOM_ACCOUNT_ID;
    $clientId = ZOOM_CLIENT_ID;
    $clientSecret = ZOOM_CLIENT_SECRET;

    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, "https://zoom.us/oauth/token?grant_type=account_credentials&account_id={$accountId}");
    curl_setopt($ch, CURLOPT_POST, 1);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_HTTPHEADER, [
        'Authorization: Basic ' . base64_encode("{$clientId}:{$clientSecret}"),
        'Content-Type: application/x-www-form-urlencoded'
    ]);

    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);

    if ($httpCode !== 200) {
        error_log("Zoom OAuth Error: HTTP $httpCode - $response");
        return null;
    }

    $data = json_decode($response, true);
    return $data['access_token'] ?? null;
}

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
            'name' => $participant['name'],
            'user_email' => $participant['email'] ?? '',
            'join_time' => $participant['join_time'],
            'leave_time' => '', // Will be empty for active participants
            'duration' => $duration
        ];
    }

    return $formattedData;
}

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

function getAttendanceData() {
    return json_decode(file_get_contents(ATTENDANCE_FILE), true);
}
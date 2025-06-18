<?php
require 'token.php';

function createMeeting($topic, $start, $duration=60) {
    $token = getZoomToken();
    $body = json_encode([
        'topic' => $topic,
        'type' => 2,
        'start_time' => $start,
        'duration' => $duration,
        'settings' => [
            'approval_type' => 0,
            'registration_type' => 1,
            'registrants_email_notification' => false,
            'registrants_confirmation_email' => false
        ]
    ]);
    $ch = curl_init('https://api.zoom.us/v2/users/me/meetings');
    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_HTTPHEADER => [
            "Authorization: Bearer $token",
            'Content-Type: application/json'
        ],
        CURLOPT_POST => true,
        CURLOPT_POSTFIELDS => $body
    ]);
    $res = json_decode(curl_exec($ch), true);
    curl_close($ch);
    return $res['id'];
}

function registerStudent($meetingId, $firstName, $lastName = '', $studentId = '') {
    $token = getZoomToken();
    
    // Generate a dummy email that Zoom will accept
    $domain = "gmail.com"; // Replace with your domain
    $email = $studentId ? "{$firstName}@{$domain}" : uniqid()."@{$domain}";

    $body = json_encode([
        "email" => $email, // Still required by Zoom API
        "first_name" => $firstName,
        "last_name" => $lastName,
        "custom_questions" => [
            [
                "title" => "Student ID",
                "value" => $studentId ?: 'N/A'
            ]
        ]
    ]);

    $ch = curl_init("https://api.zoom.us/v2/meetings/$meetingId/registrants");
    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_HTTPHEADER => [
            "Authorization: Bearer $token",
            'Content-Type: application/json'
        ],
        CURLOPT_POST => true,
        CURLOPT_POSTFIELDS => $body
    ]);

    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);

    if ($httpCode !== 201) {
        $res = json_decode($response, true);
        return isset($res['message']) ? $res['message'] : 'Registration failed. Please try again.';
    }

    $res = json_decode($response, true);
    return $res['join_url'];
}

function getMeetingJoinUrl($meetingId) {
    $token = getZoomToken();
    $ch = curl_init("https://api.zoom.us/v2/meetings/$meetingId");
    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_HTTPHEADER => [
            "Authorization: Bearer $token",
            'Content-Type: application/json'
        ]
    ]);
    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);

    if ($httpCode === 200) {
        $res = json_decode($response, true);
        return $res['join_url'] ?? null;
    }
    return null;
}

function getZoomAccessToken() {
    $account_id = '89NOV9jAT-SH7wJmjvsptg';
    $client_id = '4y5ckqpJQ1WvJAmk3x6PvQ';
    $client_secret = '8eH7szslJoGeBbyRULvEm6Bx7eE630jB';
    
    $url = 'https://zoom.us/oauth/token?grant_type=account_credentials&account_id='.$account_id;
    
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_HTTPHEADER, [
        'Authorization: Basic '.base64_encode($client_id.':'.$client_secret)
    ]);
    
    $response = curl_exec($ch);
    curl_close($ch);
    
    return json_decode($response, true);
}
?>
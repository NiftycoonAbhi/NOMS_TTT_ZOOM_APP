<?php
require_once __DIR__ . '/../../Home/token.php';

function createMeeting($topic, $start, $duration = 60)
{
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

// function to register student to the particular meeting
function registerStudent($meetingId, $firstName, $lastName = '', $studentId = '')
{
    $token = getZoomToken(); // Replace with your Zoom JWT or access token fetcher

    // Define your dummy email domain
    $domain = "gmail.com"; // You can use gmail.com or your own domain

    // Sanitize studentId to remove spaces and special characters for email
    if ($studentId) {
        // Replace spaces with underscores and remove other invalid characters
        // Safely sanitize and use Student ID as email
        $sanitizedId = preg_replace('/[^A-Za-z0-9_]/', '', str_replace(' ', '_', $studentId));
        $email = "{$sanitizedId}@gmail.com";

    } else {
        // Use a unique fallback if student ID is not provided
        $email = uniqid("user_") . "@{$domain}";
    }

    // Prepare the payload for the Zoom API
    $body = json_encode([
        "email" => $email,
        "first_name" => $firstName,
        "last_name" => $lastName,
        "custom_questions" => [
            [
                "title" => "Student ID",
                "value" => $studentId ?: 'N/A'
            ]
        ]
    ]);

    // Initialize cURL request
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

    // Execute and process the response
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

// function to generate the meeting url separately for the student
function getMeetingJoinUrl($meetingId)
{
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

function getZoomAccessToken()
{
    $account_id = '89NOV9jAT-SH7wJmjvsptg';
    $client_id = '4y5ckqpJQ1WvJAmk3x6PvQ';
    $client_secret = '8eH7szslJoGeBbyRULvEm6Bx7eE630jB';

    $url = 'https://zoom.us/oauth/token?grant_type=account_credentials&account_id=' . $account_id;

    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_HTTPHEADER, [
        'Authorization: Basic ' . base64_encode($client_id . ':' . $client_secret)
    ]);

    $response = curl_exec($ch);
    curl_close($ch);

    return json_decode($response, true);
}

function getZoomMeetingDetails($meetingId)
{
    $token = getZoomToken(); // Use your existing token fetch logic

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
        return json_decode($response, true);
    }

    return null;
}


?>


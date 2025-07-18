<?php
// Ensure error logging is enabled for development/debugging
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

// Set default timezone for all DateTime operations to prevent warnings
date_default_timezone_set('Asia/Kolkata');

// --- Configuration for Zoom API Credentials ---
// IMPORTANT: Replace with your actual Zoom App credentials.
// For security, ideally store these in environment variables or a secure config file outside web root.
// For this example, we'll define them here for clarity.
$zoom_account_id = '89NOV9jAT-SH7wJmjvsptg'; // Replace with your Zoom Account ID
$zoom_client_id = '4y5ckqpJQ1WvJAmk3x6PvQ';  // Replace with your Zoom Client ID
$zoom_client_secret = '8eH7szslJoGeBbyRULvEm6Bx7eE630jB'; // Replace with your Zoom Client Secret
// --- End Configuration ---

/**
 * Function to log errors for debugging within this file
 */
function log_zoom_api_error($message, $level = 'error', $log_file = null) {
    // Default log file for API errors if not specified
    if ($log_file === null) {
        $log_file = __DIR__ . '/../../logs/zoom_api_errors.log';
    }
    // Ensure the log directory exists
    if (!file_exists(dirname($log_file))) {
        mkdir(dirname($log_file), 0777, true);
    }
    error_log(date('[Y-m-d H:i:s]') . ' Zoom API ' . strtoupper($level) . ': ' . $message . PHP_EOL, 3, $log_file);
}

/**
 * Generate Access Token using OAuth (Server-to-Server)
 *
 * @return string|null The access token on success, or null on failure.
 */
function getZoomAccessToken() {
    global $zoom_account_id, $zoom_client_id, $zoom_client_secret;

    $account_id = defined('ZOOM_ACCOUNT_ID') ? ZOOM_ACCOUNT_ID : $zoom_account_id;
    $client_id = defined('ZOOM_CLIENT_ID') ? ZOOM_CLIENT_ID : $zoom_client_id;
    $client_secret = defined('ZOOM_CLIENT_SECRET') ? ZOOM_CLIENT_SECRET : $zoom_client_secret;

    $url = "https://zoom.us/oauth/token?grant_type=account_credentials&account_id=" . urlencode($account_id);

    $headers = [
        'Authorization: Basic ' . base64_encode("$client_id:$client_secret")
    ];

    $ch = curl_init($url);
    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_POST => true,
        CURLOPT_HTTPHEADER => $headers
    ]);

    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $curlError = curl_error($ch);
    curl_close($ch);

    $res = json_decode($response, true);

    if ($httpCode !== 200 || isset($res['error'])) {
        log_zoom_api_error("Failed to get Zoom Access Token. HTTP Code: {$httpCode}. cURL Error: {$curlError}. Response: " . print_r($res, true));
        return null;
    }
    return $res['access_token'] ?? null;
}

/**
 * Centralized function to make Zoom API calls with error handling and pagination.
 *
 * @param string $endpoint The API endpoint, e.g., "users/me/meetings".
 * @param string $accessToken The Zoom OAuth access token.
 * @param string $method The HTTP method (GET, POST, PUT, DELETE).
 * @param array $data The request body data.
 * @return array The decoded JSON response or an error array.
 */
function callZoomApi($endpoint, $accessToken, $method = 'GET', $data = []) {
    $url = "https://api.zoom.us/v2/" . $endpoint;

    $ch = curl_init($url);
    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_HTTPHEADER => [
            "Authorization: Bearer $accessToken",
            'Content-Type: application/json'
        ],
        CURLOPT_SSL_VERIFYPEER => true,
        CURLOPT_SSL_VERIFYHOST => 2,
    ]);

    if ($method === 'POST') {
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
    } elseif ($method === 'PUT') {
        curl_setopt($ch, CURLOPT_CUSTOMREQUEST, "PUT");
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
    } elseif ($method === 'DELETE') {
        curl_setopt($ch, CURLOPT_CUSTOMREQUEST, "DELETE");
    }

    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $curlError = curl_error($ch);
    curl_close($ch);

    $decodedResponse = json_decode($response, true);

    if ($curlError) {
        log_zoom_api_error("cURL Error for {$endpoint}: {$curlError}");
        return ['error' => $curlError, 'http_code' => $httpCode];
    }
    
    if (isset($decodedResponse['code']) && $decodedResponse['code'] != 200 && $decodedResponse['code'] != 201 && $decodedResponse['code'] != 204) {
        log_zoom_api_error("Zoom API Error (Code {$decodedResponse['code']}) for {$endpoint}: " . ($decodedResponse['message'] ?? 'Unknown API error'));
        return ['error' => $decodedResponse['message'] ?? 'Zoom API error', 'http_code' => $httpCode];
    }
    
    if (in_array($httpCode, [200, 201, 204])) {
        return $decodedResponse;
    } else {
        log_zoom_api_error("Zoom API HTTP Error for {$endpoint}: HTTP Code {$httpCode}. Response: " . ($response ?: 'No response body'));
        return ['error' => $decodedResponse['message'] ?? 'HTTP Error', 'http_code' => $httpCode];
    }
}

/**
 * Create Zoom Meeting
 *
 * @return string|null The new meeting's ID on success, or null on failure.
 */
function createMeeting($topic, $start, $duration = 60) {
    $token = getZoomAccessToken();
    if (!$token) return null;

    $data = [
        'topic' => $topic,
        'type' => 2, // Scheduled meeting
        'start_time' => $start, // UTC time
        'duration' => $duration,
        'settings' => [
            'approval_type' => 0, // Automatically approve
            'registration_type' => 1, // Require registration
            'registrants_email_notification' => false,
            'registrants_confirmation_email' => false
        ]
    ];

    $response = callZoomApi('users/me/meetings', $token, 'POST', $data);
    return $response['id'] ?? null;
}

/**
 * Register Student for a Zoom Meeting.
 *
 * @param string $meetingId
 * @param string $firstName
 * @param string $lastName
 * @param string $studentId
 * @return string The join URL on success, or an error message.
 */
function registerStudent($meetingId, $firstName, $lastName = '', $studentId = '') {
    $token = getZoomAccessToken();
    if (!$token) return 'Error: Access Token not available.';

    // Student ID to Email Transformation Logic
    $transformedId = str_replace('-', '.', $studentId);
    $transformedId = str_replace(' ', '-', $transformedId);
    $cleanedId = preg_replace('/[^a-zA-Z0-9.\-]/', '', $transformedId);
    $email = strtolower($cleanedId) . '@niftycoon.in';

    $data = [
        "email" => $email,
        "first_name" => $firstName,
        "last_name" => $lastName,
        "custom_questions" => [[
            "title" => "Student ID",
            "value" => $studentId ?: 'N/A'
        ]]
    ];

    $response = callZoomApi("meetings/{$meetingId}/registrants", $token, 'POST', $data);

    log_zoom_api_error("Zoom Registration API Call for Student ID: {$studentId}", 'debug', __DIR__ . '/../../logs/zoom_api_debug.log');
    log_zoom_api_error("Generated Email for Zoom: {$email}", 'debug', __DIR__ . '/../../logs/zoom_api_debug.log');
    log_zoom_api_error("Request Body: " . json_encode($data), 'debug', __DIR__ . '/../../logs/zoom_api_debug.log');
    log_zoom_api_error("API Response: " . print_r($response, true), 'debug', __DIR__ . '/../../logs/zoom_api_debug.log');

    if (!isset($response['error']) && ($response['http_code'] ?? 201) === 201) {
        return $response['join_url'] ?? 'Registration successful, but join_url not found in response.';
    } else {
        return $response['error'] ?? 'Registration failed.';
    }
}

/**
 * Remove a registrant from a Zoom Meeting.
 *
 * @param string $meetingId
 * @param string $studentId
 * @return bool True on success, false on failure.
 */
function removeZoomRegistrant($meetingId, $studentId) {
    $token = getZoomAccessToken();
    if (!$token) return false;

    // Re-generate the email using the same logic as registration to ensure match
    $transformedId = str_replace('-', '.', $studentId);
    $transformedId = str_replace(' ', '-', $transformedId);
    $cleanedId = preg_replace('/[^a-zA-Z0-9.\-]/', '', $transformedId);
    $email = strtolower($cleanedId) . '@niftycoon.in';

    $data = [
        "action" => "cancel",
        "registrants" => [["email" => $email]]
    ];

    $response = callZoomApi("meetings/{$meetingId}/registrants/status", $token, 'PUT', $data);

    if (!isset($response['error']) && in_array(($response['http_code'] ?? 204), [200, 204])) {
        return true;
    } else {
        log_zoom_api_error("Failed to remove registrant {$email} from meeting {$meetingId}: " . ($response['error'] ?? 'Unknown error'));
        return false;
    }
}

/**
 * Get general meeting details for a single meeting ID.
 *
 * @param string $meetingId
 * @return array The meeting details or an error array.
 */
function getZoomMeetingDetails($meetingId) {
    $token = getZoomAccessToken();
    if (!$token) return ['error' => 'Access Token not available.'];

    $response = callZoomApi("meetings/{$meetingId}", $token);
    return $response;
}

/**
 * List a user's Zoom meetings with pagination.
 *
 * @param string $access_token The Zoom OAuth access token.
 * @param string|null $meeting_type
 * @return array An array of meetings or an error array.
 */
function listZoomMeetings($access_token, $meeting_type = null) {
    $allMeetings = [];
    $nextPageToken = '';
    $endpointBase = 'users/me/meetings?page_size=300';

    if ($meeting_type) {
        $endpointBase .= '&type=' . urlencode($meeting_type);
    }

    do {
        $endpoint = $endpointBase;
        if ($nextPageToken) {
            $endpoint .= '&next_page_token=' . urlencode($nextPageToken);
        }

        $response = callZoomApi($endpoint, $access_token);

        if (isset($response['error'])) {
            log_zoom_api_error("Failed to list Zoom meetings (type: {$meeting_type}): " . $response['error']);
            return ['meetings' => [], 'error' => $response['error']];
        }

        $allMeetings = array_merge($allMeetings, $response['meetings'] ?? []);
        $nextPageToken = $response['next_page_token'] ?? '';

    } while ($nextPageToken);

    return ['meetings' => $allMeetings];
}

/**
 * Get meeting registrants for a specific meeting with pagination.
 *
 * @param string $meetingId
 * @param string $accessToken
 * @return array An array of registrants or an empty array on error.
 */
function getMeetingRegistrants($meetingId, $accessToken) {
    $allRegistrants = [];
    $nextPageToken = '';
    $endpointBase = "meetings/{$meetingId}/registrants?page_size=300";

    do {
        $endpoint = $endpointBase;
        if ($nextPageToken) {
            $endpoint .= '&next_page_token=' . urlencode($nextPageToken);
        }

        $response = callZoomApi($endpoint, $accessToken);

        if (isset($response['error'])) {
            if (($response['http_code'] ?? 0) == 404 || strpos(($response['error'] ?? ''), 'invalid meeting') !== false || strpos(($response['error'] ?? ''), 'Meeting does not exist') !== false) {
                 log_zoom_api_error("Meeting ID {$meetingId} not found or invalid for registrants API.", 'warning');
            } else {
                 log_zoom_api_error("Failed to list registrants for meeting ID " . $meetingId . ": " . $response['error']);
            }
            return [];
        }

        $allRegistrants = array_merge($allRegistrants, $response['registrants'] ?? []);
        $nextPageToken = $response['next_page_token'] ?? '';

    } while ($nextPageToken);

    return ['registrants' => $allRegistrants];
}

/**
 * Fetches participants for a given meeting ID from the Zoom API.
 * This function uses the `callZoomApi` helper for consistency and robustness.
 *
 * @param string $meeting_id The ID of the meeting to query.
 * @return array An array of meeting participants or an empty array on failure.
 */
function getMeetingParticipants($meeting_id) {
    $zoom_access_token = getZoomAccessToken();
    if (!$zoom_access_token) {
        log_zoom_api_error("Failed to get Zoom Access Token in getMeetingParticipants.");
        return [];
    }
    
    // Zoom API endpoint for past meeting participants.
    $endpoint = "past_meetings/{$meeting_id}/participants";

    $response = callZoomApi($endpoint, $zoom_access_token);
    
    if (isset($response['error'])) {
        log_zoom_api_error("Failed to fetch participants for meeting {$meeting_id}: " . $response['error']);
        return [];
    }

    // Note on Scalability: This endpoint has a limit of 300 participants per request.
    // For large meetings, you would need to implement pagination by checking for 'next_page_token'.
    // This basic version only retrieves the first page of participants.
    
    return $response['participants'] ?? [];
}


// for testing
// function getMeetingParticipants($meeting_id) {
//     // Test data for meeting ID 987654
//     if ($meeting_id === '987654') {
//         return [
//             [
//                 "user_email" => "TTT-10th-ICSE-24-25-102@niftycoon.in",
//                 "join_time" => "2025-07-14T09:01:00Z",
//                 "leave_time" => "2025-07-14T09:25:00Z"
//             ],
//               [
//                 "user_email" => "TTT-10th-ICSE-24-25-102@niftycoon.in",
//                 "join_time" => "2025-07-14T09:25:00Z",
//                 "leave_time" => "2025-07-14T09:55:00Z"
//             ],
//             [
//                 "user_email" => "TTT-10th-ICSE-24-25-103@niftycoon.in",
//                 "join_time" => "2025-07-14T09:05:00Z",
//                 "leave_time" => "2025-07-14T09:58:00Z"
//             ]
//         ];
//     }

//     // Default empty result
//     return [];
// }



/**
 * Get student's Zoom meetings from Zoom API for dashboard display.
 *
 * @param string $studentId The internal student ID.
 * @return array An array of meetings relevant to the student.
 */
function get_zoom_meetings($studentId) {
    // Student ID to Email Transformation Logic
    $transformedId = str_replace('-', '.', $studentId);
    $transformedId = str_replace(' ', '-', $transformedId);
    $cleanedId = preg_replace('/[^a-zA-Z0-9.\-]/', '', $transformedId);
    $student_email = strtolower($cleanedId) . '@niftycoon.in';

    $access_token = getZoomAccessToken();
    if (!$access_token) {
        log_zoom_api_error("Failed to get Zoom Access Token in get_zoom_meetings for student: " . $studentId);
        return [];
    }

    $all_zoom_meetings = [];
    $meeting_types_to_fetch = ['scheduled', 'upcoming', 'live', 'ended']; 

    foreach ($meeting_types_to_fetch as $type) {
        $response = listZoomMeetings($access_token, $type);
        $all_zoom_meetings = array_merge($all_zoom_meetings, $response['meetings'] ?? []);
    }

    $unique_meetings_for_student = [];
    $meetingDetailsCache = [];

    foreach ($all_zoom_meetings as $meeting) {
        $meeting_id = $meeting['id'];

        $registrantsData = getMeetingRegistrants($meeting_id, $access_token);
        $registrants = $registrantsData['registrants'] ?? [];

        foreach ($registrants as $reg) {
            $found_by_email = false;
            if (isset($reg['email'])) {
                 $found_by_email = (strtolower($reg['email']) === $student_email);
            }
            
            $found_by_custom_question = false;
            foreach ($reg['custom_questions'] ?? [] as $q) {
                if (isset($q['title']) && strtolower(trim($q['title'])) === 'student id' && strtolower(trim($q['value'])) === strtolower(trim($studentId))) {
                    $found_by_custom_question = true;
                    break;
                }
            }

            if (($found_by_email || $found_by_custom_question) && !isset($unique_meetings_for_student[$meeting_id])) {
                if (!isset($meetingDetailsCache[$meeting_id])) {
                    $details = getZoomMeetingDetails($meeting_id, $access_token);
                    if (isset($details['error'])) {
                        log_zoom_api_error("Failed to get meeting details for ID " . $meeting_id . ": " . $details['error'] . " (Skipping meeting)");
                        continue 2;
                    }
                    $meetingDetailsCache[$meeting_id] = $details;
                }
                $details = $meetingDetailsCache[$meeting_id];

                $start_time = '';
                if (isset($details['occurrences']) && !empty($details['occurrences'])) {
                    $current_time_utc = new DateTime('now', new DateTimeZone('UTC'));
                    $found_relevant_occurrence = false;
                    foreach ($details['occurrences'] as $occurrence) {
                        $occurrence_dt = new DateTime($occurrence['start_time'], new DateTimeZone('UTC'));
                        if ($occurrence_dt >= $current_time_utc || ($current_time_utc->getTimestamp() - $occurrence_dt->getTimestamp() < (24 * 3600))) {
                            $start_time = $occurrence['start_time'];
                            $found_relevant_occurrence = true;
                            break;
                        }
                    }
                    if (!$found_relevant_occurrence && !empty($details['occurrences'])) {
                        $start_time = $details['occurrences'][0]['start_time'] ?? '';
                    }
                }
                if (!$start_time && isset($details['start_time'])) {
                    $start_time = $details['start_time'];
                }
                
                $status = $details['status'] ?? ''; 
                
                $unique_meetings_for_student[$meeting_id] = [
                    'meeting_id' => $meeting_id,
                    'topic'      => $details['topic'] ?? ($meeting['topic'] ?? 'N/A'),
                    'zoom_link'  => $reg['join_url'],
                    'timestamp'  => $reg['create_time'] ?? '',
                    'status'     => $status,
                    'start_time' => $start_time
                ];
            }
        }
    }

    usort($unique_meetings_for_student, function($a, $b) {
        $timeA = new DateTime($a['start_time'] ?? '9999-01-01T00:00:00Z');
        $timeB = new DateTime($b['start_time'] ?? '9999-01-01T00:00:00Z');
        return $timeA <=> $timeB;
    });

    return array_values($unique_meetings_for_student);
}
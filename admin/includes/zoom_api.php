<?php
// Include application configuration for consistent error handling
require_once __DIR__ . '/../../config.php';

// Set default timezone for all DateTime operations to prevent warnings
if (isDebugMode()) {
    date_default_timezone_set('Asia/Kolkata');
}

// Include multi-account configuration
require_once __DIR__ . '/multi_account_config.php';

/**
 * TTT NOMS Zoom API Integration
 * 
 * RECURRING MEETING SUPPORT:
 * This API implementation fully supports both regular meetings (type 2) and recurring meetings (type 8).
 * 
 * Key Features:
 * - Automatic detection of meeting type (regular vs recurring)
 * - Occurrence-specific registration for recurring meetings
 * - Automatic occurrence selection (next future occurrence for registration, most recent ended for participants)
 * - Consistent behavior across all functions (register, remove, get registrants, get participants)
 * 
 * Functions with Recurring Meeting Support:
 * - registerStudent(): Automatically detects recurring meetings and registers for next occurrence
 * - removeZoomRegistrant(): Removes from the same occurrence used for registration
 * - getMeetingRegistrants(): Gets registrants for specific occurrence (auto-detects if not specified)
 * - getMeetingParticipants(): Gets participants from most recent ended occurrence (auto-detects if not specified)
 * - getZoomMeetingDetails(): Returns full meeting details including occurrences for recurring meetings
 * 
 * Zoom Meeting Types:
 * - Type 1: Instant Meeting
 * - Type 2: Scheduled Meeting (regular)
 * - Type 3: Recurring Meeting with no fixed time
 * - Type 8: Recurring Meeting with fixed time (main type we support)
 */

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
    // Get current zoom credentials from session
    $credentials = getZoomApiCredentials();
    
    if (!$credentials) {
        log_zoom_api_error("No Zoom account selected. Please select a Zoom account first.");
        return null;
    }
    
    $account_id = $credentials['account_id'];
    $client_id = $credentials['client_id'];
    $client_secret = $credentials['client_secret'];

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
 * Automatically splits full names into first and last names if needed.
 * If only firstName is provided and contains spaces, it will be split.
 * Ensures both first_name and last_name are provided (Zoom API requirement).
 *
 * @param string $meetingId The Zoom meeting ID
 * @param string $firstName Student's first name or full name (will be split if contains spaces)
 * @param string $lastName Student's last name (optional - will be auto-generated if empty)
 * @param string $studentId Student ID for identification and email generation
 * @return string The join URL on success, or an error message.
 */
function registerStudent($meetingId, $firstName, $lastName = '', $studentId = '') {
    $token = getZoomAccessToken();
    if (!$token) return 'Error: Access Token not available.';

    // First, check if this is a recurring meeting
    $meetingDetails = getZoomMeetingDetails($meetingId);
    log_zoom_api_error("Meeting details check - Meeting ID: {$meetingId}", 'debug', __DIR__ . '/../../logs/zoom_api_debug.log');
    log_zoom_api_error("Meeting details response: " . print_r($meetingDetails, true), 'debug', __DIR__ . '/../../logs/zoom_api_debug.log');
    
    if (isset($meetingDetails['error'])) {
        log_zoom_api_error("Failed to get meeting details: " . $meetingDetails['error'], 'error', __DIR__ . '/../../logs/zoom_api_debug.log');
        return "Meeting does not exist: {$meetingId}. " . $meetingDetails['error'];
    }

    // Check if it's a recurring meeting
    $registrationUrl = "meetings/{$meetingId}/registrants";
    if (isset($meetingDetails['type']) && $meetingDetails['type'] == 8) {
        log_zoom_api_error("Detected recurring meeting (type 8)", 'debug', __DIR__ . '/../../logs/zoom_api_debug.log');
        // This is a recurring meeting, we need to find the next occurrence
        if (isset($meetingDetails['occurrences']) && !empty($meetingDetails['occurrences'])) {
            log_zoom_api_error("Found " . count($meetingDetails['occurrences']) . " occurrences", 'debug', __DIR__ . '/../../logs/zoom_api_debug.log');
            $currentTime = new DateTime('now', new DateTimeZone('UTC'));
            $nextOccurrence = null;
            
            foreach ($meetingDetails['occurrences'] as $occurrence) {
                $occurrenceTime = new DateTime($occurrence['start_time'], new DateTimeZone('UTC'));
                if ($occurrenceTime >= $currentTime) {
                    $nextOccurrence = $occurrence;
                    break;
                }
            }
            
            // If no future occurrence found, use the first one
            if (!$nextOccurrence && !empty($meetingDetails['occurrences'])) {
                $nextOccurrence = $meetingDetails['occurrences'][0];
                log_zoom_api_error("No future occurrence found, using first occurrence", 'debug', __DIR__ . '/../../logs/zoom_api_debug.log');
            }
            
            if ($nextOccurrence && isset($nextOccurrence['occurrence_id'])) {
                $registrationUrl = "meetings/{$meetingId}/registrants?occurrence_id=" . $nextOccurrence['occurrence_id'];
                log_zoom_api_error("Registering for recurring meeting occurrence: " . $nextOccurrence['occurrence_id'], 'debug', __DIR__ . '/../../logs/zoom_api_debug.log');
            } else {
                log_zoom_api_error("No valid occurrence found for recurring meeting", 'error', __DIR__ . '/../../logs/zoom_api_debug.log');
            }
        } else {
            log_zoom_api_error("Recurring meeting but no occurrences found", 'error', __DIR__ . '/../../logs/zoom_api_debug.log');
        }
    } else {
        log_zoom_api_error("Regular meeting (type: " . ($meetingDetails['type'] ?? 'unknown') . ")", 'debug', __DIR__ . '/../../logs/zoom_api_debug.log');
    }

    // Use the full student ID combined with name for clear identification
    // This ensures students appear as "TTT-10th-ICSE-24-25-102 Rajesh Kumar" in Zoom
    if (!empty($studentId)) {
        // Extract actual name parts for proper display
        if (empty($lastName) && !empty($firstName)) {
            $nameParts = explode(' ', trim($firstName), 2);
            if (count($nameParts) >= 2) {
                $actualFirstName = $nameParts[0];
                $actualLastName = $nameParts[1];
            } else {
                $actualFirstName = $nameParts[0];
                $actualLastName = 'Student';
            }
        } else {
            $actualFirstName = $firstName ?: 'Student';
            $actualLastName = $lastName ?: 'User';
        }
        
        // Create display name with Student ID + Full Name for maximum clarity
        $displayFirstName = $studentId . ' ' . $actualFirstName;
        $displayLastName = $actualLastName;
    } else {
        // Fallback to regular name handling if no student ID provided
        if (empty($lastName) && !empty($firstName)) {
            $nameParts = explode(' ', trim($firstName), 2);
            if (count($nameParts) >= 2) {
                $displayFirstName = $nameParts[0];
                $displayLastName = $nameParts[1];
            } else {
                $displayFirstName = $nameParts[0];
                $displayLastName = 'Student';
            }
        } else {
            $displayFirstName = $firstName ?: 'Student';
            $displayLastName = $lastName ?: 'User';
        }
        $actualFirstName = $displayFirstName;
        $actualLastName = $displayLastName;
    }

    // Student ID to Email Transformation Logic
    $transformedId = str_replace('-', '.', $studentId);
    $transformedId = str_replace(' ', '-', $transformedId);
    $cleanedId = preg_replace('/[^a-zA-Z0-9.\-]/', '', $transformedId);
    $email = strtolower($cleanedId) . '@niftycoon.in';

    $data = [
        "email" => $email,
        "first_name" => $displayFirstName,
        "last_name" => $displayLastName,
        "custom_questions" => [[
            "title" => "Student ID",
            "value" => $studentId ?: 'N/A'
        ]]
    ];

    $response = callZoomApi($registrationUrl, $token, 'POST', $data);

    log_zoom_api_error("Zoom Registration API Call for Student ID: {$studentId}", 'debug', __DIR__ . '/../../logs/zoom_api_debug.log');
    log_zoom_api_error("Meeting Type: " . ($meetingDetails['type'] ?? 'unknown'), 'debug', __DIR__ . '/../../logs/zoom_api_debug.log');
    log_zoom_api_error("Registration URL: {$registrationUrl}", 'debug', __DIR__ . '/../../logs/zoom_api_debug.log');
    log_zoom_api_error("Generated Email for Zoom: {$email}", 'debug', __DIR__ . '/../../logs/zoom_api_debug.log');
    log_zoom_api_error("Display Name for Zoom: {$displayFirstName} {$displayLastName}", 'debug', __DIR__ . '/../../logs/zoom_api_debug.log');
    log_zoom_api_error("Request Body: " . json_encode($data), 'debug', __DIR__ . '/../../logs/zoom_api_debug.log');
    log_zoom_api_error("API Response: " . print_r($response, true), 'debug', __DIR__ . '/../../logs/zoom_api_debug.log');

    if (!isset($response['error']) && ($response['http_code'] ?? 201) === 201) {
        return $response['join_url'] ?? 'Registration successful, but join_url not found in response.';
    } else {
        $errorMsg = $response['error'] ?? 'Registration failed.';
        return "Meeting does not exist: {$meetingId}. {$errorMsg}";
    }
}

/**
 * Optimized bulk registration for multiple students
 * Caches meeting details to avoid repeated API calls
 * 
 * @param string $meetingId The Zoom meeting ID
 * @param array $students Array of student data [['student_id' => '', 'name' => ''], ...]
 * @return array Results array with success/error counts and details
 */
function registerStudentsBulk($meetingId, $students) {
    $results = [
        'success_count' => 0,
        'error_count' => 0,
        'errors' => [],
        'success_students' => [],
        'cached_meeting_details' => null,
        'registration_url' => null
    ];
    
    if (empty($students)) {
        $results['errors'][] = 'No students provided for registration';
        return $results;
    }
    
    $token = getZoomAccessToken();
    if (!$token) {
        $results['errors'][] = 'Error: Access Token not available';
        return $results;
    }

    // OPTIMIZATION 1: Cache meeting details - fetch only once for all students
    $meetingDetails = getZoomMeetingDetails($meetingId);
    if (isset($meetingDetails['error'])) {
        $results['errors'][] = "Meeting does not exist: {$meetingId}. " . $meetingDetails['error'];
        return $results;
    }
    
    $results['cached_meeting_details'] = $meetingDetails;
    
    // OPTIMIZATION 2: Pre-calculate registration URL once
    $registrationUrl = "meetings/{$meetingId}/registrants";
    if (isset($meetingDetails['type']) && $meetingDetails['type'] == 8) {
        // Handle recurring meeting - find next occurrence once
        if (isset($meetingDetails['occurrences']) && !empty($meetingDetails['occurrences'])) {
            $currentTime = new DateTime('now', new DateTimeZone('UTC'));
            $nextOccurrence = null;
            
            foreach ($meetingDetails['occurrences'] as $occurrence) {
                $occurrenceTime = new DateTime($occurrence['start_time'], new DateTimeZone('UTC'));
                if ($occurrenceTime >= $currentTime) {
                    $nextOccurrence = $occurrence;
                    break;
                }
            }
            
            if (!$nextOccurrence && !empty($meetingDetails['occurrences'])) {
                $nextOccurrence = $meetingDetails['occurrences'][0];
            }
            
            if ($nextOccurrence && isset($nextOccurrence['occurrence_id'])) {
                $registrationUrl .= "?occurrence_id=" . $nextOccurrence['occurrence_id'];
            }
        }
    }
    
    $results['registration_url'] = $registrationUrl;
    
    // OPTIMIZATION 3: Process students in batches (reduced logging)
    $batch_size = 10;
    $total_students = count($students);
    
    log_zoom_api_error("Starting bulk registration for {$total_students} students to meeting {$meetingId}", 'info', __DIR__ . '/../../logs/zoom_api_debug.log');
    log_zoom_api_error("Using registration URL: {$registrationUrl}", 'debug', __DIR__ . '/../../logs/zoom_api_debug.log');
    
    foreach (array_chunk($students, $batch_size) as $batch_index => $batch) {
        $batch_start = $batch_index * $batch_size + 1;
        $batch_end = min(($batch_index + 1) * $batch_size, $total_students);
        
        log_zoom_api_error("Processing batch {$batch_start}-{$batch_end} of {$total_students}", 'debug', __DIR__ . '/../../logs/zoom_api_debug.log');
        
        foreach ($batch as $student) {
            $studentId = $student['student_id'] ?? '';
            $firstName = $student['name'] ?? 'Student';
            
            // OPTIMIZATION 4: Simplified name processing
            $nameParts = explode(' ', trim($firstName), 2);
            $actualFirstName = $nameParts[0];
            $actualLastName = isset($nameParts[1]) ? $nameParts[1] : 'Student';
            
            // Create display name with Student ID
            $displayFirstName = $studentId . ' ' . $actualFirstName;
            $displayLastName = $actualLastName;
            
            // OPTIMIZATION 5: Simplified email generation
            $email = strtolower(str_replace(['-', ' '], ['.', '-'], preg_replace('/[^a-zA-Z0-9.\-\s]/', '', $studentId))) . '@niftycoon.in';
            
            $data = [
                "email" => $email,
                "first_name" => $displayFirstName,
                "last_name" => $displayLastName,
                "custom_questions" => [[
                    "title" => "Student ID",
                    "value" => $studentId
                ]]
            ];
            
            // OPTIMIZATION 6: Single API call per student (no redundant logging)
            $response = callZoomApi($registrationUrl, $token, 'POST', $data);
            
            if (!isset($response['error']) && ($response['http_code'] ?? 201) === 201) {
                $results['success_count']++;
                $results['success_students'][] = [
                    'student_id' => $studentId,
                    'join_url' => $response['join_url'] ?? '',
                    'email' => $email
                ];
            } else {
                $results['error_count']++;
                $errorMsg = $response['error'] ?? 'Registration failed';
                $results['errors'][] = "Student {$studentId}: {$errorMsg}";
            }
        }
        
        // OPTIMIZATION 7: Brief pause between batches to avoid rate limits
        if ($batch_index < count(array_chunk($students, $batch_size)) - 1) {
            usleep(100000); // 0.1 second pause
        }
    }
    
    log_zoom_api_error("Bulk registration completed: {$results['success_count']} success, {$results['error_count']} errors", 'info', __DIR__ . '/../../logs/zoom_api_debug.log');
    
    return $results;
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

    // Check if this is a recurring meeting
    $meetingDetails = getZoomMeetingDetails($meetingId);
    if (isset($meetingDetails['error'])) {
        log_zoom_api_error("Failed to get meeting details for removal: " . $meetingDetails['error']);
        return false;
    }

    // Check if it's a recurring meeting
    $cancellationUrl = "meetings/{$meetingId}/registrants/status";
    if (isset($meetingDetails['type']) && $meetingDetails['type'] == 8) {
        // This is a recurring meeting, we need to find the next occurrence
        if (isset($meetingDetails['occurrences']) && !empty($meetingDetails['occurrences'])) {
            $currentTime = new DateTime('now', new DateTimeZone('UTC'));
            $nextOccurrence = null;
            
            foreach ($meetingDetails['occurrences'] as $occurrence) {
                $occurrenceTime = new DateTime($occurrence['start_time'], new DateTimeZone('UTC'));
                if ($occurrenceTime >= $currentTime) {
                    $nextOccurrence = $occurrence;
                    break;
                }
            }
            
            // If no future occurrence found, use the first one
            if (!$nextOccurrence && !empty($meetingDetails['occurrences'])) {
                $nextOccurrence = $meetingDetails['occurrences'][0];
            }
            
            if ($nextOccurrence && isset($nextOccurrence['occurrence_id'])) {
                $cancellationUrl = "meetings/{$meetingId}/registrants/status?occurrence_id=" . $nextOccurrence['occurrence_id'];
            }
        }
    }

    // Re-generate the email using the same logic as registration to ensure match
    $transformedId = str_replace('-', '.', $studentId);
    $transformedId = str_replace(' ', '-', $transformedId);
    $cleanedId = preg_replace('/[^a-zA-Z0-9.\-]/', '', $transformedId);
    $email = strtolower($cleanedId) . '@niftycoon.in';

    $data = [
        "action" => "cancel",
        "registrants" => [["email" => $email]]
    ];

    $response = callZoomApi($cancellationUrl, $token, 'PUT', $data);

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
 * Supports both regular and recurring meetings.
 *
 * @param string $meetingId
 * @param string $accessToken
 * @param string|null $occurrenceId Optional occurrence ID for recurring meetings
 * @return array An array of registrants or an empty array on error.
 */
function getMeetingRegistrants($meetingId, $accessToken, $occurrenceId = null) {
    $allRegistrants = [];
    $nextPageToken = '';
    $endpointBase = "meetings/{$meetingId}/registrants?page_size=300";
    
    // Add occurrence ID for recurring meetings
    if ($occurrenceId) {
        $endpointBase .= "&occurrence_id=" . urlencode($occurrenceId);
        log_zoom_api_error("Getting registrants for recurring meeting occurrence: {$occurrenceId}", 'debug', __DIR__ . '/../../logs/zoom_api_debug.log');
    } else {
        // Check if this is a recurring meeting and automatically get next occurrence
        $meetingDetails = getZoomMeetingDetails($meetingId);
        if (isset($meetingDetails['type']) && $meetingDetails['type'] == 8) {
            // This is a recurring meeting, find the next occurrence
            if (isset($meetingDetails['occurrences']) && !empty($meetingDetails['occurrences'])) {
                $currentTime = new DateTime('now', new DateTimeZone('UTC'));
                $nextOccurrence = null;
                
                foreach ($meetingDetails['occurrences'] as $occurrence) {
                    $occurrenceTime = new DateTime($occurrence['start_time'], new DateTimeZone('UTC'));
                    if ($occurrenceTime >= $currentTime) {
                        $nextOccurrence = $occurrence;
                        break;
                    }
                }
                
                // If no future occurrence found, use the first one
                if (!$nextOccurrence && !empty($meetingDetails['occurrences'])) {
                    $nextOccurrence = $meetingDetails['occurrences'][0];
                }
                
                if ($nextOccurrence && isset($nextOccurrence['occurrence_id'])) {
                    $endpointBase .= "&occurrence_id=" . urlencode($nextOccurrence['occurrence_id']);
                    log_zoom_api_error("Auto-detected recurring meeting, using occurrence: " . $nextOccurrence['occurrence_id'], 'debug', __DIR__ . '/../../logs/zoom_api_debug.log');
                }
            }
        }
    }

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
 * Supports both regular and recurring meetings.
 *
 * @param string $meeting_id The ID of the meeting to query.
 * @param string|null $occurrence_id Optional occurrence ID for recurring meetings
 * @return array An array of meeting participants or an empty array on failure.
 */
function getMeetingParticipants($meeting_id, $occurrence_id = null) {
    $zoom_access_token = getZoomAccessToken();
    if (!$zoom_access_token) {
        log_zoom_api_error("Failed to get Zoom Access Token in getMeetingParticipants.");
        return [];
    }
    
    // Zoom API endpoint for past meeting participants.
    $endpoint = "past_meetings/{$meeting_id}/participants";
    
    // Add occurrence ID for recurring meetings
    if ($occurrence_id) {
        $endpoint .= "?occurrence_id=" . urlencode($occurrence_id);
        log_zoom_api_error("Getting participants for recurring meeting occurrence: {$occurrence_id}", 'debug', __DIR__ . '/../../logs/zoom_api_debug.log');
    } else {
        // Check if this is a recurring meeting and get the most recent occurrence
        $meetingDetails = getZoomMeetingDetails($meeting_id);
        if (isset($meetingDetails['type']) && $meetingDetails['type'] == 8) {
            // This is a recurring meeting, find the most recent occurrence that has ended
            if (isset($meetingDetails['occurrences']) && !empty($meetingDetails['occurrences'])) {
                $currentTime = new DateTime('now', new DateTimeZone('UTC'));
                $recentOccurrence = null;
                
                // Find the most recent occurrence that has already ended
                foreach (array_reverse($meetingDetails['occurrences']) as $occurrence) {
                    $occurrenceEndTime = new DateTime($occurrence['start_time'], new DateTimeZone('UTC'));
                    $occurrenceEndTime->add(new DateInterval('PT' . ($meetingDetails['duration'] ?? 60) . 'M'));
                    
                    if ($occurrenceEndTime <= $currentTime) {
                        $recentOccurrence = $occurrence;
                        break;
                    }
                }
                
                if ($recentOccurrence && isset($recentOccurrence['occurrence_id'])) {
                    $endpoint .= "?occurrence_id=" . urlencode($recentOccurrence['occurrence_id']);
                    log_zoom_api_error("Auto-detected recurring meeting, using most recent ended occurrence: " . $recentOccurrence['occurrence_id'], 'debug', __DIR__ . '/../../logs/zoom_api_debug.log');
                }
            }
        }
    }

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
                    $details = getZoomMeetingDetails($meeting_id);
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
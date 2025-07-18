<?php
// zoom-webhook.php
// ===================================================================
// COMPLETE FLOW:
// 1. Receive Zoom webhook for 'meeting.ended' event
// 2. Validate required meeting data (ID, start/end times)
// 3. Convert UTC times to IST timezone
// 4. Begin database transaction
// 5. Update meeting header record (insert or update)
// 6. Fetch participant details from Zoom API
// 7. Process each participant:
//    a. Extract student ID from Zoom email
//    b. Convert join/leave times to IST
//    c. Validate student exists in database
// 8. Store each attendance session as separate records
// 9. Commit transaction on success, rollback on failure
// 10. Log errors and return appropriate HTTP responses
// ===================================================================

// Enable error reporting for debugging
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

// Database connection
require_once __DIR__ . '/db/dbconn.php';

/**
 * Log error messages to a dedicated error log file
 * @param string $message Error message to log
 */
function log_webhook_error($message) {
    $log_file = __DIR__ . '/../logs/zoom_webhook_errors.log';
    if (!file_exists(dirname($log_file))) {
        mkdir(dirname($log_file), 0777, true);
    }
    error_log(date('[Y-m-d H:i:s]') . ' Webhook Error: ' . $message . PHP_EOL, 3, $log_file);
}

/**
 * Convert UTC datetime to IST timezone
 * @param string $utcTime UTC datetime string
 * @return string Converted datetime in IST format
 */
function convertToIST($utcTime) {
    try {
        $utcDateTime = new DateTime($utcTime, new DateTimeZone('UTC'));
        $utcDateTime->setTimezone(new DateTimeZone('Asia/Kolkata'));
        return $utcDateTime->format('Y-m-d H:i:s');
    } catch (Exception $e) {
        log_webhook_error("Time conversion failed: " . $e->getMessage());
        return null;
    }
}

// Verify request method is POST
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    log_webhook_error("Invalid request method: " . $_SERVER['REQUEST_METHOD']);
    http_response_code(405);
    echo "Invalid request method";
    exit;
}

// Get raw input from webhook
$data = json_decode(file_get_contents("php://input"), true);
if (json_last_error() !== JSON_ERROR_NONE) {
    log_webhook_error("Invalid JSON received: " . json_last_error_msg());
    http_response_code(400);
    echo "Invalid JSON data";
    exit;
}

// Log the incoming webhook data for debugging
$debug_log = __DIR__ . '/../logs/zoom_webhook_debug.log';
error_log(date('[Y-m-d H:i:s]') . ' Webhook received: ' . json_encode($data) . PHP_EOL, 3, $debug_log);

// Handle Zoom URL validation event
if (isset($data['event']) && $data['event'] === 'endpoint.url_validation') {
    $plainToken = $data['payload']['plainToken'] ?? '';
    $zoomVerificationToken = '7CRrtPjcSj-ZQgF0IMnlaA'; // Your Zoom secret token
    
    if (empty($plainToken)) {
        log_webhook_error("Missing plainToken in URL validation request");
        http_response_code(400);
        echo "Missing plainToken";
        exit;
    }

    $response = [
        'plainToken' => $plainToken,
        'encryptedToken' => hash_hmac('sha256', $plainToken, $zoomVerificationToken, false)
    ];

    header('Content-Type: application/json');
    echo json_encode($response);
    exit;
}

// Validate webhook event type and structure
if (isset($data['event']) && $data['event'] === 'meeting.ended' && isset($data['payload']['object'])) {
    $meeting = $data['payload']['object'];
    
    // Extract and validate essential meeting data
    $meeting_id = $meeting['id'] ?? null;
    $start_time = $meeting['start_time'] ?? null;
    $end_time = $meeting['end_time'] ?? null;
    $meeting_date = $start_time ? date('Y-m-d', strtotime($start_time)) : null;
    
    if (!$meeting_id || !$start_time || !$end_time) {
        log_webhook_error("Missing essential meeting data: " . json_encode($meeting));
        http_response_code(400);
        echo "Missing meeting data.";
        exit();
    }
    
    // Convert meeting times to IST timezone
    $start_time_ist = convertToIST($start_time);
    $end_time_ist = convertToIST($end_time);
    
    if (!$start_time_ist || !$end_time_ist) {
        log_webhook_error("Time conversion failed for meeting: {$meeting_id}");
        http_response_code(500);
        echo "Error processing meeting times.";
        exit();
    }
    
    // Start database transaction
    $conn->begin_transaction();
    try {
        // Update meeting header record (insert or update on duplicate)
        $stmt_head = $conn->prepare("
            INSERT INTO meeting_att_head (meeting_id, meeting_date, start_time, end_time, status)
            VALUES (?, ?, ?, ?, 'ended')
            ON DUPLICATE KEY UPDATE
                meeting_date = VALUES(meeting_date),
                start_time = VALUES(start_time),
                end_time = VALUES(end_time),
                status = 'ended'
        ");
        if (!$stmt_head) throw new Exception("Prepare head failed: " . $conn->error);
        $stmt_head->bind_param("ssss", $meeting_id, $meeting_date, $start_time_ist, $end_time_ist);
        $stmt_head->execute();
        if ($stmt_head->error) throw new Exception("Execute head failed: " . $stmt_head->error);
        
        // Load Zoom API functionality
        require_once __DIR__ . '/admin/includes/zoom_api.php';
        $participants = getMeetingParticipants($meeting_id);
        
        // Handle case where no participants were found
        if (empty($participants)) {
            log_webhook_error("No participants found for meeting: {$meeting_id}");
            $conn->commit();
            http_response_code(200);
            echo "Webhook processed. No participants.";
            exit();
        }
        
        // Prepare attendance details insert statement
        $stmt_details = $conn->prepare("
            INSERT INTO meeting_att_details 
                (meeting_id, student_id, meeting_date, start_time, join_time, leave_time)
            VALUES (?, ?, ?, ?, ?, ?)
        ");
        if (!$stmt_details) throw new Exception("Prepare detail failed: " . $conn->error);
        $stmt_details->bind_param("ssssss", $meeting_id, $internal_student_id, $meeting_date, $start_time_ist, $join_time_ist, $leave_time_ist);
        
        // Track attendance sessions per student
        $student_sessions = [];
        $processed_count = 0;
        
        // Process each participant
        foreach ($participants as $p) {
            $zoom_user_email = $p['user_email'] ?? null;
            $join_time = $p['join_time'] ?? null;
            $leave_time = $p['leave_time'] ?? null;
            $internal_student_id = null;
            
            // Process only niftycoon.in email addresses
            if ($zoom_user_email) {
                $domain = '@niftycoon.in';
                if (str_ends_with($zoom_user_email, $domain)) {
                    // Convert email to internal student ID format
                    $email_local_part = substr($zoom_user_email, 0, -strlen($domain));
                    $temp_id_step1 = str_replace('-', ' ', $email_local_part);
                    $temp_id_step2 = str_replace('.', '-', $temp_id_step1);
                    $internal_student_id = strtoupper($temp_id_step2);
                    log_webhook_error("DEBUG: Candidate student_id from email '{$zoom_user_email}' → {$internal_student_id}");
                } else {
                    log_webhook_error("Skipping non-niftycoon email: {$zoom_user_email}");
                    continue;
                }
            } else {
                log_webhook_error("Participant email missing.");
                continue;
            }
            
            // Validate student exists in database
            $check_student_stmt = $conn->prepare("SELECT student_id FROM student_details WHERE student_id = ? LIMIT 1");
            if (!$check_student_stmt) {
                log_webhook_error("Prepare check failed: " . $conn->error);
                continue;
            }
            $check_student_stmt->bind_param("s", $internal_student_id);
            $check_student_stmt->execute();
            $result = $check_student_stmt->get_result();
            if ($result->num_rows === 0) {
                log_webhook_error("Student ID '{$internal_student_id}' not found in DB.");
                continue;
            }
            
            // Convert participant times to IST
            $join_time_ist = $join_time ? convertToIST($join_time) : null;
            $leave_time_ist = $leave_time ? convertToIST($leave_time) : null;
            
            if (!$join_time_ist) {
                log_webhook_error("Invalid join time for student {$internal_student_id}");
                continue;
            }
            
            // Store each join/leave session as a separate record
            if ($internal_student_id && $join_time_ist) {
                if (!isset($student_sessions[$internal_student_id])) {
                    $student_sessions[$internal_student_id] = [];
                }
                
                $student_sessions[$internal_student_id][] = [
                    'join_time' => $join_time_ist,
                    'leave_time' => $leave_time_ist
                ];
            }
        }
        
        // Insert all attendance records
        foreach ($student_sessions as $student_id => $sessions) {
            foreach ($sessions as $session) {
                $join_time_ist = $session['join_time'];
                $leave_time_ist = $session['leave_time'];
                
                $stmt_details->bind_param("ssssss", $meeting_id, $student_id, $meeting_date, $start_time_ist, $join_time_ist, $leave_time_ist);
                $stmt_details->execute();
                
                if ($stmt_details->error) {
                    log_webhook_error("Insert failed for {$student_id}: " . $stmt_details->error);
                } else {
                    $processed_count++;
                    log_webhook_error("Insert success for {$student_id} session");
                }
            }
        }
        
        // Commit transaction on success
        $conn->commit();
        http_response_code(200);
        echo "Webhook processed successfully. {$processed_count} attendance records created.";
        
    } catch (Exception $e) {
        // Rollback transaction on failure
        $conn->rollback();
        log_webhook_error("Transaction failed: " . $e->getMessage());
        http_response_code(500);
        echo "Error processing webhook: " . $e->getMessage();
    }
} else {
    // Handle invalid webhook events
    $event = $data['event'] ?? 'none';
    log_webhook_error("Invalid/missing event in webhook. Event received: {$event}");
    http_response_code(400);
    echo "Invalid event. Expected 'meeting.ended' but received '{$event}'";
}
?>
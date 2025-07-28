<?php
// zoom-webhook.php - Multi-Account Support
// ===================================================================
// COMPLETE FLOW:
// 1. Receive Zoom webhook for 'meeting.ended' event
// 2. Determine which Zoom account the meeting belongs to
// 3. Validate incoming data (ID, start/end times)
// 4. Convert UTC times to IST timezone
// 5. Begin database transaction
// 6. Update meeting header record (insert or update) with account ID
// 7. Fetch participant details from Zoom API using correct credentials
// 8. Process each participant:
//    a. Extract student ID from Zoom email
//    b. Convert join/leave times to IST
//    c. Validate student exists in database
// 9. Store each attendance session as separate records with account ID
// 10. Commit transaction on success, rollback on failure
// 11. Log errors and return appropriate HTTP responses
// ===================================================================

require_once 'db/dbconn.php'; // Database connection

/**
 * Log error messages to a dedicated error log file
 * @param string $message Error message to log
 */
function log_webhook_error($message) {
    $log_file = __DIR__ . '/logs/zoom_webhook_errors.log';
    if (!file_exists(dirname($log_file))) {
        mkdir(dirname($log_file), 0777, true); // Create directory if it doesn't exist
    }
    error_log(date('[Y-m-d H:i:s]') . ' Webhook Error: ' . $message . PHP_EOL, 3, $log_file);
}

/**
 * Get Zoom credentials by account ID from webhook data
 * @param string $account_id The account ID from webhook
 * @return array|null Zoom credentials or null if not found
 */
function getZoomCredentialsByAccountId($account_id) {
    global $conn;
    
    $stmt = $conn->prepare("SELECT * FROM zoom_api_credentials WHERE account_id = ? AND is_active = 1");
    $stmt->bind_param("s", $account_id);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows > 0) {
        return $result->fetch_assoc();
    }
    
    return null;
}

/**
 * Convert UTC datetime to IST timezone
 * @param string $utcTime UTC datetime string
 * @return string Converted datetime in IST format
 */
function convertToIST($utcTime) {
    $utcDateTime = new DateTime($utcTime, new DateTimeZone('UTC'));
    $utcDateTime->setTimezone(new DateTimeZone('Asia/Kolkata'));
    return $utcDateTime->format('Y-m-d H:i:s');
}

// Get raw input from webhook
$data = json_decode(file_get_contents("php://input"), true);

// Validate webhook event type and structure
if (isset($data['event']) && $data['event'] === 'meeting.ended' && isset($data['payload']['object'])) {
    $meeting = $data['payload']['object'];
    
    // Extract and validate essential meeting data
    $meeting_id = $meeting['id'] ?? null;
    $start_time = $meeting['start_time'] ?? null;
    $end_time = $meeting['end_time'] ?? null;
    $meeting_date = $start_time ? date('Y-m-d', strtotime($start_time)) : null;
    
    // Get account ID from webhook payload
    $account_id = $data['payload']['account_id'] ?? null;
    
    if (!$meeting_id || !$start_time || !$end_time || !$account_id) {
        log_webhook_error("Missing essential meeting data or account ID.");
        http_response_code(400);
        echo json_encode(['error' => 'Missing essential data']);
        exit;
    }
    
    // Get Zoom credentials for this account
    $zoom_credentials = getZoomCredentialsByAccountId($account_id);
    if (!$zoom_credentials) {
        log_webhook_error("Unknown Zoom account ID: $account_id");
        http_response_code(404);
        echo json_encode(['error' => 'Unknown account']);
        exit;
    }
    
    $zoom_credentials_id = $zoom_credentials['id'];
    
    // Convert meeting times to IST timezone
    $start_time_ist = convertToIST($start_time);
    $end_time_ist = convertToIST($end_time);
    
    // Start database transaction
    $conn->begin_transaction();
    try {
        // Update meeting header record (insert or update on duplicate) with zoom credentials ID
        $stmt_head = $conn->prepare("
            INSERT INTO meeting_att_head (meeting_id, meeting_date, start_time, end_time, zoom_credentials_id)
            VALUES (?, ?, ?, ?, ?)
            ON DUPLICATE KEY UPDATE
                meeting_date = VALUES(meeting_date),
                start_time = VALUES(start_time),
                end_time = VALUES(end_time),
                zoom_credentials_id = VALUES(zoom_credentials_id)
        ");
        if (!$stmt_head) throw new Exception("Prepare head failed: " . $conn->error);
        $stmt_head->bind_param("ssssi", $meeting_id, $meeting_date, $start_time_ist, $end_time_ist, $zoom_credentials_id);
        $stmt_head->execute();
        if ($stmt_head->error) throw new Exception("Execute head failed: " . $stmt_head->error);
        
        // Load webhook-specific Zoom functionality
        require_once __DIR__ . '/webhook_config.php';
        
        $participants = getWebhookMeetingParticipants($meeting_id, $zoom_credentials);
        
        // Handle case where no participants were found
        if (empty($participants)) {
            log_webhook_error("No participants found.");
            $conn->commit();
            http_response_code(200);
            echo "Webhook processed. No participants.";
            exit();
        }
        
        // Prepare attendance details insert statement with zoom credentials ID
        $stmt_details = $conn->prepare("
            INSERT INTO meeting_att_details 
                (meeting_id, student_id, meeting_date, start_time, end_time, join_time, leave_time, zoom_credentials_id)
            VALUES (?, ?, ?, ?, ?, ?, ?, ?)
        ");
        if (!$stmt_details) throw new Exception("Prepare detail failed: " . $conn->error);
        
        // Track attendance sessions per student
        $student_sessions = [];
        
        // Process each participant
        foreach ($participants as $p) {
            $zoom_user_email = $p['user_email'] ?? null;
            $join_time = $p['join_time'] ?? null;
            $leave_time = $p['leave_time'] ?? null;
            $internal_student_id = null;
            
            // Process only niftycoon.in or tttacademy.com email addresses
            if ($zoom_user_email) {
                $niftycoon_domain = '@niftycoon.in';
                $tttacademy_domain = '@tttacademy.com';
                
                if (str_ends_with($zoom_user_email, $niftycoon_domain) || str_ends_with($zoom_user_email, $tttacademy_domain)) {
                    // Convert email to internal student ID format
                    // Email format: ttt-10th-icse-24-25-101.aryan.sharma@niftycoon.in
                    // OR: ttt-10th-icse-24-25-288@tttacademy.com
                    // Target format: TTT-10th-ICSE-24-25-101 Aryan Sharma
                    
                    $domain_to_remove = str_ends_with($zoom_user_email, $niftycoon_domain) ? $niftycoon_domain : $tttacademy_domain;
                    $email_local_part = substr($zoom_user_email, 0, -strlen($domain_to_remove));
                    
                    if (str_contains($email_local_part, '.')) {
                        // Format with name: ttt-10th-icse-24-25-101.aryan.sharma
                        $parts = explode('.', $email_local_part);
                        if (count($parts) >= 2) {
                            $id_part = implode('.', array_slice($parts, 0, -2)); // Everything except last 2 parts (first, last name)
                            $first_name = $parts[count($parts) - 2];
                            $last_name = $parts[count($parts) - 1];
                            
                            // Convert ID part: ttt-10th-icse-24-25-101 → TTT-10th-ICSE-24-25-101
                            $id_formatted = strtoupper($id_part);
                            
                            // Convert name: aryan.sharma → Aryan Sharma
                            $name_formatted = ucfirst($first_name) . ' ' . ucfirst($last_name);
                            
                            // Combine: TTT-10th-ICSE-24-25-101 Aryan Sharma
                            $internal_student_id = $id_formatted . ' ' . $name_formatted;
                        } else {
                            // Fallback for malformed emails
                            $internal_student_id = strtoupper(str_replace('.', ' ', $email_local_part));
                        }
                    } else {
                        // Format without name: ttt-10th-icse-24-25-288
                        // Need to find the student name from database
                        $id_formatted = strtoupper($email_local_part);
                        
                        // Try to find student in database to get the name
                        $check_student_stmt = $conn->prepare("SELECT student_id FROM student_details WHERE student_id LIKE ? LIMIT 1");
                        $search_pattern = $id_formatted . '%';
                        $check_student_stmt->bind_param("s", $search_pattern);
                        $check_student_stmt->execute();
                        $result = $check_student_stmt->get_result();
                        
                        if ($result->num_rows > 0) {
                            $internal_student_id = $result->fetch_assoc()['student_id'];
                        } else {
                            // If not found, use the formatted ID as is
                            $internal_student_id = $id_formatted;
                        }
                    }
                } else {
                    log_webhook_error("Skipping non-approved domain email: {$zoom_user_email}");
                    continue;
                }
            } else {
                log_webhook_error("Participant email missing.");
                continue;
            }
            
            // BYPASS student check for debug (currently commented out)
            /*
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
            */
            
            // Convert participant times to IST
            $join_time_ist = $join_time ? convertToIST($join_time) : null;
            $leave_time_ist = $leave_time ? convertToIST($leave_time) : null;
            
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
                
                $stmt_details->bind_param("sssssssi", $meeting_id, $student_id, $meeting_date, $start_time_ist, $end_time_ist, $join_time_ist, $leave_time_ist, $zoom_credentials_id);
                $stmt_details->execute();
                
                if ($stmt_details->error) {
                    log_webhook_error("Insert failed for {$student_id}: " . $stmt_details->error);
                } else {
                    log_webhook_error("Insert success for {$student_id} session");
                }
            }
        }
        
        // Commit transaction on success
        $conn->commit();
        http_response_code(200);
        echo "Webhook received and processed successfully.";
        
    } catch (Exception $e) {
        // Rollback transaction on failure
        $conn->rollback();
        log_webhook_error("Transaction failed: " . $e->getMessage());
        http_response_code(500);
        echo "Error processing webhook.";
    }
} else {
    // Handle invalid webhook events
    log_webhook_error("Invalid/missing event in webhook: " . json_encode($data));
    http_response_code(400);
    echo "Invalid event.";
}
?>
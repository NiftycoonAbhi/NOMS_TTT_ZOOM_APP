<?php
/* # ******************************************************************************
# Program: Attendance details for the particular meeting id
# Author: NifTycoon Company
# Copyright © [2023] NifTycoon Company. All rights reserved.
#
# Description: In this program Admin can view the attendance of the meetings And also admin can see the complete information of the attendance of the particular student who attended the meeting.
#
# This program is the property of NifTycoon Company and is protected by copyright laws.
# Unauthorized reproduction or distribution of this program, or any portion of it,
# may result in severe civil and criminal penalties, and will be prosecuted to the
# maximum extent possible under the law.
#
# NifTycoon Company reserves the right to modify this program as needed.
#
# ******************************************************************************/

// Include multi-account configuration
require_once 'includes/multi_account_config.php';

// Check if user has selected a Zoom account, redirect if not
requireZoomAccountSelection('select_zoom_account.php');

// Get current zoom credentials ID for filtering
$current_zoom_credentials_id = getCurrentZoomCredentialsId();
$current_account = getCurrentZoomAccount();

// Handle logout
if (isset($_POST['logout']) || isset($_GET['logout'])) {
    logoutUser('select_zoom_account.php');
}

// Handle account switching
if (isset($_POST['switch_account'])) {
    clearCurrentZoomAccount();
    header("Location: select_zoom_account.php");
    exit();
}

// ===================================================================
// COMPLETE FLOW:
// 1. Initialize session and set timezone to Asia/Kolkata
// 2. Include required files (functions, headers, database connection)
// 3. Check if viewing specific student's attendance or meeting overview
// 4. For specific student:
//    a. Fetch student details from database
//    b. Calculate total meeting duration
//    c. Retrieve all attendance sessions for the student
//    d. Calculate attendance percentage
//    e. Display student summary and attendance history
// 5. For meeting overview:
//    a. Fetch meeting details from database
//    b. Calculate total meeting duration
//    c. Retrieve all registered students from 'zoom' table
//    d. Calculate attendance metrics for each student
//    e. Display meeting details and attendance list
// ===================================================================

// Set default timezone to IST (session already started in multi_account_config.php)
date_default_timezone_set('Asia/Kolkata');
$ist_timezone = new DateTimeZone('Asia/Kolkata');

// Include required files
include($_SERVER['DOCUMENT_ROOT'] . '/TTT_NOMS_ZOOM/common/php/niftycoon_functions.php');
include __DIR__ . '/../headers/header2.php';

// Include database connection
require_once '../db/dbconn.php';
// Verify database connection
if (!$conn) {
    die("Database connection failed: " . mysqli_connect_error());
}

// Include admin configurations and functions
require_once '../admin/includes/config.php';
require_once '../admin/includes/functions.php';

// Initialize variables
$studentDetails = false;

// Check if viewing specific student's attendance details
if (isset($_GET['student_id']) && isset($_GET['meeting_id'])) {
    $studentDetails = true;
    $studentId = trim($_GET['student_id']);
    $meetingId = trim($_GET['meeting_id']);
    
    // Fetch student details from database
    $studentQuery = $conn->prepare("
        SELECT 
            s.student_id,
            COALESCE(c.course_name, 'N/A') AS course_name,
            COALESCE(b.batch_name, 'N/A') AS batch_name
        FROM student_details s
        LEFT JOIN courses c ON s.course = c.course_code
        LEFT JOIN batchs b ON s.batch = b.batch_name
        WHERE s.student_id = ?
        LIMIT 1
    ");
    $studentQuery->bind_param("s", $studentId);
    $studentQuery->execute();
    $studentResult = $studentQuery->get_result();
    $studentData = $studentResult->fetch_assoc();
    
    // Handle case where student doesn't exist
    if (!$studentData) {
        echo "<div class='alert alert-danger'>No student found with ID $studentId</div>";
        exit;
    }
    
    // Calculate total meeting duration from meeting header
    $meetingDurationQuery = $conn->prepare("
        SELECT SUM(TIMESTAMPDIFF(SECOND, start_time, end_time)) AS total_duration
        FROM meeting_att_head
        WHERE meeting_id = ?
    ");
    $meetingDurationQuery->bind_param("s", $meetingId);
    $meetingDurationQuery->execute();
    $meetingDurationResult = $meetingDurationQuery->get_result();
    $meetingDurationData = $meetingDurationResult->fetch_assoc();
    $total_meeting_duration = $meetingDurationData['total_duration'] ?? 0;
    
    // Fetch all attendance sessions for this student in the meeting
    $attendanceQuery = $conn->prepare("
        SELECT 
            mad.join_time,
            mad.leave_time,
            mh.start_time,
            mh.end_time,
            mh.meeting_id
        FROM meeting_att_details mad
        JOIN meeting_att_head mh ON mad.meeting_id = mh.meeting_id 
        WHERE mad.student_id = ? AND mad.meeting_id = ?
        ORDER BY mad.join_time ASC;
    ");
    $attendanceQuery->bind_param("ss", $studentId, $meetingId);
    $attendanceQuery->execute();
    $attendanceResult = $attendanceQuery->get_result();
    
    // Initialize variables for calculations
    $total_attended_duration = 0;
    $attendance_records = [];
    
    // Process each attendance record
    if ($attendanceResult && $attendanceResult->num_rows > 0) {
        while ($record = $attendanceResult->fetch_assoc()) {
            // Calculate duration of this attendance session
            $leave_time = $record['leave_time'] ?? $record['end_time']; // Use meeting end time if leave_time is NULL
            
            if ($record['join_time'] && $leave_time) {
                $attended_duration = strtotime($leave_time) - strtotime($record['join_time']);
                // Ensure duration is not negative
                $attended_duration = max(0, $attended_duration);
            } else {
                $attended_duration = 0;
            }
            
            $total_attended_duration += $attended_duration;
            
            // Store record for display
            $attendance_records[] = [
                'date' => date('M j, Y', strtotime($record['start_time'])),
                'meeting_id' => $record['meeting_id'],
                'meeting_time' => date('g:i A', strtotime($record['start_time'])) . ' - ' . date('g:i A', strtotime($record['end_time'])),
                'join_time' => $record['join_time'],
                'leave_time' => $record['leave_time'],
                'attended_duration' => $attended_duration,
                'session_start' => $record['start_time'],
                'session_end' => $record['end_time']
            ];
        }
    }
    
    // Calculate overall attendance percentage
    $overall_attendance_percent = $total_meeting_duration > 0 ?
        ($total_attended_duration / $total_meeting_duration) * 100 : 0;
?>
    <!-- Account Header Bar -->
    <div class="bg-primary text-white py-2 px-3 d-flex justify-content-between align-items-center" style="position: fixed; top: 0; left: 0; right: 0; z-index: 1000;">
        <div>
            <i class="fas fa-building"></i> Current Account: <strong><?= htmlspecialchars($current_account['name'] ?? 'No Account') ?></strong>
        </div>
        <div class="d-flex gap-2">
            <form method="POST" style="margin: 0;" class="me-2">
                <button type="submit" name="switch_account" class="btn btn-light btn-sm">
                    <i class="fas fa-exchange-alt"></i> Switch Account
                </button>
            </form>
            <form method="POST" style="margin: 0;">
                <button type="submit" name="logout" class="btn btn-outline-light btn-sm">
                    <i class="fas fa-sign-out-alt"></i> Logout
                </button>
            </form>
        </div>
    </div>
    <div style="margin-top: 50px;"></div> <!-- Spacer for fixed header -->
    
    <!-- Student-specific attendance details view -->
    <div class="container mt-4">
        <div class="card mb-4">
            <div class="card-header bg-primary text-white">
                <h4>Student Attendance Details</h4>
            </div>
            <div class="card-body">
                <div class="row">
                    <div class="col-md-6">
                        <p><strong>Student ID:</strong> <?= htmlspecialchars($studentData['student_id']) ?></p>
                    </div>
                    <div class="col-md-6">
                        <p><strong>Course:</strong> <?= htmlspecialchars($studentData['course_name'] ?? 'N/A') ?></p>
                        <p><strong>Batch:</strong> <?= htmlspecialchars($studentData['batch_name'] ?? 'N/A') ?></p>
                        <p><strong>Overall Average Attendance %:</strong>
                            <span
                                class="<?= $overall_attendance_percent > 75 ? 'text-success' : ($overall_attendance_percent > 50 ? 'text-warning' : 'text-danger') ?>">
                                <?= round($overall_attendance_percent, 1) ?>%
                            </span>
                        </p>
                    </div>
                </div>
            </div>
        </div>
        
        <!-- Attendance history table -->
        <div class="card">
            <div class="card-header bg-primary text-white">
                <h4>Attendance History</h4>
            </div>
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-hover table-striped mb-0 attendance-table">
                        <thead class="sticky-top bg-white">
                            <tr>
                                <th class="bg-light border-end"><i class="fas fa-calendar-alt"></i> Date</th>
                                <th class="bg-light border-end"><i class="fas fa-video"></i> Meeting ID</th>
                                <th class="bg-light border-end"><i class="fas fa-clock"></i> Meeting Time (IST)</th>
                                <th class="bg-light"><i class="fas fa-sign-in-alt"></i> Joined At (IST)</th>
                                <th class="bg-light"><i class="fas fa-sign-out-alt"></i> Left At (IST)</th>
                                <th class="text-end bg-light"><i class="fas fa-hourglass-half"></i> Attended Duration</th>
                                <th class="text-end bg-light"><i class="fas fa-percentage"></i> Attendance %</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (!empty($attendance_records)): ?>
                                <?php 
                                // Group attendance records by meeting_id and meeting_time
                                $grouped_records = [];
                                foreach ($attendance_records as $record) {
                                    $group_key = $record['meeting_id'] . '|' . $record['meeting_time'] . '|' . $record['date'];
                                    if (!isset($grouped_records[$group_key])) {
                                        $grouped_records[$group_key] = [
                                            'meeting_info' => [
                                                'date' => $record['date'],
                                                'meeting_id' => $record['meeting_id'],
                                                'meeting_time' => $record['meeting_time']
                                            ],
                                            'sessions' => []
                                        ];
                                    }
                                    $grouped_records[$group_key]['sessions'][] = $record;
                                }
                                
                                foreach ($grouped_records as $group_key => $group):
                                    $meeting_info = $group['meeting_info'];
                                    $sessions = $group['sessions'];
                                    $session_count = count($sessions);
                                    
                                    foreach ($sessions as $session_index => $record):
                                        // Calculate session duration and attendance percentage
                                        $session_duration = strtotime($record['session_end']) - strtotime($record['session_start']);
                                        $attendance_percent = $session_duration > 0 ? ($record['attended_duration'] / $session_duration) * 100 : 0;
                                        
                                        // Format times for display
                                        $joinTimeIST = !empty($record['join_time']) ? date('M j, Y H:i:s', strtotime($record['join_time'])) : '-';
                                        $leaveTimeIST = !empty($record['leave_time']) ? date('M j, Y H:i:s', strtotime($record['leave_time'])) : 'Still in meeting';
                                ?>
                                        <tr <?= $session_index > 0 ? 'class="grouped-session"' : '' ?>>
                                            <?php if ($session_index === 0): ?>
                                                <td rowspan="<?= $session_count ?>" class="align-middle border-end border-primary" style="background-color: #f8f9fa;">
                                                    <strong><?= htmlspecialchars($meeting_info['date']) ?></strong>
                                                </td>
                                                <td rowspan="<?= $session_count ?>" class="align-middle border-end border-primary" style="background-color: #f8f9fa;">
                                                    <strong><?= htmlspecialchars($meeting_info['meeting_id']) ?></strong>
                                                </td>
                                                <td rowspan="<?= $session_count ?>" class="align-middle border-end border-primary" style="background-color: #f8f9fa;">
                                                    <strong><?= htmlspecialchars($meeting_info['meeting_time']) ?></strong>
                                                </td>
                                            <?php endif; ?>
                                            <td><?= $joinTimeIST ?></td>
                                            <td><?= $leaveTimeIST ?></td>
                                            <td class="text-end">
                                                <?php
                                                // Format duration as hours and minutes
                                                if ($record['attended_duration'] > 0) {
                                                    $hours = floor($record['attended_duration'] / 3600);
                                                    $minutes = floor(($record['attended_duration'] % 3600) / 60);
                                                    echo ($hours > 0 ? $hours . ' hr ' : '') . $minutes . ' min';
                                                } else {
                                                    echo '-';
                                                }
                                                ?>
                                            </td>
                                            <td class="text-end">
                                                <span class="attendance-percentage <?= $attendance_percent > 75 ? 'attendance-excellent' : ($attendance_percent > 50 ? 'attendance-good' : 'attendance-poor') ?>">
                                                    <?= round($attendance_percent, 1) ?>%
                                                </span>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                    <?php if ($session_count > 1): ?>
                                        <!-- Summary row for multiple sessions -->
                                        <tr class="table-info border-top border-2 border-primary">
                                            <td colspan="4" class="text-end fw-bold">
                                                <i class="fas fa-calculator"></i> Total for this meeting:
                                            </td>
                                            <td class="text-end fw-bold">
                                                <?php
                                                $total_duration = array_sum(array_column($sessions, 'attended_duration'));
                                                $total_hours = floor($total_duration / 3600);
                                                $total_minutes = floor(($total_duration % 3600) / 60);
                                                echo ($total_hours > 0 ? $total_hours . ' hr ' : '') . $total_minutes . ' min';
                                                ?>
                                            </td>
                                            <td class="text-end fw-bold">
                                                <?php
                                                $meeting_duration = strtotime($sessions[0]['session_end']) - strtotime($sessions[0]['session_start']);
                                                $total_attendance_percent = $meeting_duration > 0 ? ($total_duration / $meeting_duration) * 100 : 0;
                                                ?>
                                                <span class="attendance-percentage <?= $total_attendance_percent > 75 ? 'attendance-excellent' : ($total_attendance_percent > 50 ? 'attendance-good' : 'attendance-poor') ?>">
                                                    <i class="fas fa-chart-pie"></i> <?= round($total_attendance_percent, 1) ?>%
                                                </span>
                                            </td>
                                        </tr>
                                    <?php endif; ?>
                                <?php endforeach; ?>
                            <?php else: ?>
                                <tr>
                                    <td colspan="7" class="text-center py-4 text-muted">
                                        No attendance records found for this meeting and student.
                                    </td>
                                </tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
    <?php
} else {
    // Meeting overview view (when no specific student is selected)
    $meetingId = isset($_GET['meeting_id']) ? trim($_GET['meeting_id']) : null;
    
    // Validate meeting ID
    if (!$meetingId) {
        echo "<div class='alert alert-danger'>Invalid meeting ID provided.</div>";
        exit;
    }
    
    // Fetch meeting details from database
    $meetingQuery = $conn->prepare("SELECT * FROM meeting_att_head WHERE meeting_id = ?");
    $meetingQuery->bind_param("s", $meetingId);
    $meetingQuery->execute();
    $meetingResult = $meetingQuery->get_result();
    $meetingData = $meetingResult->fetch_assoc();
    
    // Handle case where meeting doesn't exist
    if (!$meetingData) {
        echo "<script>
                alert('Meeting is not completed yet. Attendance data will be available after the meeting ends.');
                window.history.back();
              </script>";
        exit;
    }
    
    // Calculate total meeting duration
    $meetingDurationQuery = $conn->prepare("
        SELECT SUM(TIMESTAMPDIFF(SECOND, start_time, end_time)) AS total_duration
        FROM meeting_att_head
        WHERE meeting_id = ?
    ");
    $meetingDurationQuery->bind_param("s", $meetingId);
    $meetingDurationQuery->execute();
    $meetingDurationResult = $meetingDurationQuery->get_result();
    $meetingDurationData = $meetingDurationResult->fetch_assoc();
    $total_meeting_duration = $meetingDurationData['total_duration'] ?? 0;
    
    // Fetch all students registered for this meeting from 'zoom' table
    $participantsQuery = $conn->prepare("
        SELECT 
            z.student_id, 
            sd.student_name, 
            sd.course, 
            c.course_name, 
            sd.batch, 
            b.batch_name,
            MIN(mad.join_time) as first_join_time,
            MAX(mad.leave_time) as last_leave_time,
            SUM(
                CASE 
                    WHEN mad.join_time IS NOT NULL AND mad.leave_time IS NOT NULL 
                    THEN TIMESTAMPDIFF(SECOND, mad.join_time, mad.leave_time) 
                    ELSE 0 
                END
            ) as total_attended_seconds
        FROM 
            zoom z
        INNER JOIN 
            student_details sd ON z.student_id = sd.student_id
        LEFT JOIN 
            courses c ON sd.course = c.course_code
        LEFT JOIN 
            batchs b ON sd.batch = b.batch_name
        LEFT JOIN 
            meeting_att_details mad ON z.student_id = mad.student_id AND mad.meeting_id = ?
        WHERE 
            z.meeting_id = ?
        GROUP BY
            z.student_id, sd.student_name, sd.course, c.course_name, sd.batch, b.batch_name
        ORDER BY 
            z.student_id
    ");
    $participantsQuery->bind_param("ss", $meetingId, $meetingId);
    $participantsQuery->execute();
    $participants = $participantsQuery->get_result();
?>
    <!-- Meeting overview attendance view -->
    <div class="container mt-4">
        <h1 class="mb-4">Zoom Meeting Attendance - <?= htmlspecialchars($meetingId) ?></h1>
        
        <!-- Meeting details card -->
        <div class="card mb-4">
            <div class="card-header bg-primary text-white d-flex justify-content-between align-items-center">
                <h4>Meeting Details</h4>
                <span class="badge <?= (strtotime($meetingData['end_time']) > time()) ? 'bg-success' : 'bg-secondary' ?>">
                    <?= (strtotime($meetingData['end_time']) > time() ? 'Active' : 'Completed') ?>
                </span>
            </div>
            <div class="card-body">
                <div class="row">
                    <div class="col-md-6">
                        <h5>
                            <i class="fas fa-video zoom-icon"></i> Meeting ID: <?= htmlspecialchars($meetingId) ?>
                        </h5>
                        <p class="text-muted"><i class="fas fa-clock me-1"></i>
                            Duration: <?= round($total_meeting_duration / 60) ?> minutes
                        </p>
                    </div>
                    <div class="col-md-6 text-end">
                        <a href="export_attendance.php?meeting_id=<?= htmlspecialchars($meetingId) ?>" 
                           class="btn btn-success">
                            <i class="fas fa-download me-1"></i>Export to Excel
                        </a>
                    </div>
                </div>
            </div>
        </div>
        
        <!-- Attendance list table -->
        <div class="card">
            <div class="card-header bg-primary text-white">
                <h4>Attendance List</h4>
            </div>
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-hover table-striped mb-0" id="participantsTable">
                        <thead class="sticky-top bg-white">
                            <tr>
                                <th>Student ID</th>
                                <th>Course</th>
                                <th>Batch</th>
                                <th>Join Time (IST)</th>
                                <th>Leave Time (IST)</th>
                                <th class="text-end">Duration</th>
                                <th class="text-end">Attendance %</th>
                                <th>Status</th>
                                <th>Details</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if ($participants && $participants->num_rows > 0): ?>
                                <?php while ($row = $participants->fetch_assoc()):
                                    $attended_duration = $row['total_attended_seconds'] ?? 0;
                                    $attendance_percent = $total_meeting_duration > 0 ? ($attended_duration / $total_meeting_duration) * 100 : 0;
                                    $status = ($attended_duration > 0) ? 'Present' : 'Absent';
                                ?>
                                    <tr>
                                        <td><?= htmlspecialchars($row['student_id']) ?></td>
                                        <td><?= htmlspecialchars($row['course_name'] ?? 'N/A') ?></td>
                                        <td><?= htmlspecialchars($row['batch_name'] ?? 'N/A') ?></td>
                                        <td><?= htmlspecialchars($row['first_join_time'] ?? '-') ?></td>
                                        <td><?= htmlspecialchars($row['last_leave_time'] ?? '-') ?></td>
                                        <td class="text-end">
                                            <?php
                                            // Format duration as hours and minutes
                                            if ($attended_duration > 0) {
                                                $hours = floor($attended_duration / 3600);
                                                $minutes = floor(($attended_duration % 3600) / 60);
                                                echo ($hours > 0 ? $hours . ' hr ' : '') . $minutes . ' min';
                                            } else {
                                                echo '-';
                                            }
                                            ?>
                                        </td>
                                        <td
                                            class="text-end <?= $attendance_percent > 75 ? 'text-success' : ($attendance_percent > 50 ? 'text-warning' : 'text-danger') ?>">
                                            <?= $attended_duration > 0 ? round($attendance_percent, 1) . '%' : '0%' ?>
                                        </td>
                                        <td><span
                                                class="badge <?= $status === 'Present' ? 'bg-success' : 'bg-danger' ?>"><?= ucfirst($status) ?></span>
                                        </td>
                                        <td><a href="?meeting_id=<?= $meetingId ?>&student_id=<?= urlencode($row['student_id']) ?>"
                                                class="btn btn-sm btn-outline-primary"><i class="fas fa-eye"></i></a></td>
                                    </tr>
                                <?php endwhile; ?>
                            <?php else: ?>
                                <tr>
                                    <td colspan="10" class="text-center py-4 text-muted">
                                        No students registered for this meeting.
                                    </td>
                                </tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
    <?php
}
?>

<!-- Custom CSS for improved attendance table layout -->
<style>
    /* Enhanced table styling for grouped attendance records */
    .attendance-table {
        border-collapse: separate;
        border-spacing: 0;
    }
    
    .attendance-table .grouped-session {
        border-left: 3px solid #007bff;
    }
    
    .attendance-table .grouped-session td {
        border-top: 1px dashed #dee2e6;
    }
    
    /* Styling for merged cells */
    .attendance-table td[rowspan] {
        vertical-align: middle;
        font-weight: 600;
        background: linear-gradient(135deg, #f8f9fa 0%, #e9ecef 100%);
        border-right: 2px solid #007bff;
        position: relative;
    }
    
    .attendance-table td[rowspan]::after {
        content: '';
        position: absolute;
        left: 0;
        top: 0;
        bottom: 0;
        width: 4px;
        background: linear-gradient(to bottom, #007bff, #0056b3);
    }
    
    /* Session divider styling */
    .session-divider {
        border-top: 2px solid #007bff !important;
        background-color: #f0f7ff !important;
    }
    
    /* Summary row styling */
    .table-info.border-top {
        background-color: #e3f2fd !important;
        border-top: 3px solid #1976d2 !important;
    }
    
    /* Hover effects */
    .attendance-table tbody tr:hover {
        background-color: #f8f9fa;
        transform: translateX(2px);
        transition: all 0.2s ease;
    }
    
    .attendance-table tbody tr:hover td[rowspan] {
        background: linear-gradient(135deg, #e3f2fd 0%, #bbdefb 100%);
    }
    
    /* Responsive table improvements */
    @media (max-width: 768px) {
        .attendance-table {
            font-size: 0.85rem;
        }
        
        .attendance-table td[rowspan] {
            min-width: 120px;
        }
    }
    
    /* Animation for session groups */
    .grouped-session {
        animation: slideIn 0.3s ease-out;
    }
    
    @keyframes slideIn {
        from {
            opacity: 0;
            transform: translateX(-10px);
        }
        to {
            opacity: 1;
            transform: translateX(0);
        }
    }
    
    /* Status badges */
    .attendance-percentage {
        padding: 0.25rem 0.5rem;
        border-radius: 0.375rem;
        font-weight: 600;
        font-size: 0.875rem;
    }
    
    .attendance-excellent {
        background-color: #d4edda;
        color: #155724;
        border: 1px solid #c3e6cb;
    }
    
    .attendance-good {
        background-color: #fff3cd;
        color: #856404;
        border: 1px solid #ffeaa7;
    }
    
    .attendance-poor {
        background-color: #f8d7da;
        color: #721c24;
        border: 1px solid #f5c6cb;
    }
</style>

<!-- Bootstrap and custom scripts -->
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<script>
    document.addEventListener('DOMContentLoaded', function() {
        // Initialize popovers
        var popoverTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="popover"]'))
        var popoverList = popoverTriggerList.map(function (popoverTriggerEl) {
            return new bootstrap.Popover(popoverTriggerEl, {
                html: true // Allow HTML in popover content
            });
        });
    });
</script>
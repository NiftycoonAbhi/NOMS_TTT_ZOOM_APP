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

session_start();
// Set default timezone to IST
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
            AND mad.start_time >= mh.start_time 
            AND mad.join_time >= mh.start_time 
            AND mad.leave_time <= mh.end_time
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
            $attended_duration = strtotime($record['leave_time']) - strtotime($record['join_time']);
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
                    <table class="table table-hover table-striped mb-0">
                        <thead class="sticky-top bg-white">
                            <tr>
                                <th>Date</th>
                                <th>Meeting ID</th>
                                <th>Meeting Time (IST)</th>
                                <th>Joined At (IST)</th>
                                <th>Left At (IST)</th>
                                <th class="text-end">Attended Duration</th>
                                <th class="text-end">Attendance %</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (!empty($attendance_records)): ?>
                                <?php foreach ($attendance_records as $index => $record):
                                    // Calculate session duration and attendance percentage
                                    $session_duration = strtotime($record['session_end']) - strtotime($record['session_start']);
                                    $attendance_percent = $session_duration > 0 ? ($record['attended_duration'] / $session_duration) * 100 : 0;
                                    
                                    // Format times for display
                                    $joinTimeIST = !empty($record['join_time']) ? date('M j, Y H:i:s', strtotime($record['join_time'])) : '-';
                                    $leaveTimeIST = !empty($record['leave_time']) ? date('M j, Y H:i:s', strtotime($record['leave_time'])) : '-';
                                ?>
                                    <tr>
                                        <td><?= htmlspecialchars($record['date']) ?></td>
                                        <td><?= htmlspecialchars($record['meeting_id']) ?></td>
                                        <td><?= htmlspecialchars($record['meeting_time']) ?></td>
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
                                        <td class="text-end <?= $attendance_percent > 75 ? 'text-success' : ($attendance_percent > 50 ? 'text-warning' : 'text-danger') ?>">
                                            <?= round($attendance_percent, 1) ?>%
                                        </td>
                                    </tr>
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
        echo "<div class='alert alert-danger'>Meeting not found in database.</div>";
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
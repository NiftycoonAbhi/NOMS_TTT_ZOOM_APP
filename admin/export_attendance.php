<?php
require_once __DIR__ . '/includes/multi_account_config.php';
require_once __DIR__ . '/includes/config.php';
require_once __DIR__ . '/includes/functions.php';
require_once __DIR__ . '/../db/dbconn.php'; // Add database connection

// Check if user has selected a Zoom account
requireZoomAccountSelection('select_zoom_account.php');

// Handle logout and account switching
if (isset($_POST['logout'])) {
    logoutUser();
}

if (isset($_POST['switch_account'])) {
    header('Location: select_zoom_account.php');
    exit();
}

// Get current account info for display
$current_account = getCurrentZoomAccount();

// Check admin session


if (isset($_GET['meeting_id'])) {
    $meetingId = $_GET['meeting_id'];
    
    // Validate meeting ID
    if (!preg_match('/^\d+$/', $meetingId)) {
        die("Invalid meeting ID format");
    }

    $data = getMeetingParticipantsForExport($meetingId);

    if ($data === false) {
        die("No attendance data found for meeting ID: $meetingId");
    }

    // Get meeting info from first participant
    $meeting_info = !empty($data) ? $data[0] : null;

    // Set headers for Excel download
    header('Content-Type: application/vnd.ms-excel');
    header('Content-Disposition: attachment; filename="zoom_attendance_' . $meetingId . '_' . date('Y-m-d') . '.xls"');
    
    // Start Excel content
    echo "<table border='1'>";
    echo "<tr><th colspan='12' style='font-size:16px; background-color:#1f4e79; color:white;'>TTT Zoom Meeting Attendance Report</th></tr>";
    echo "<tr><th colspan='12' style='background-color:#d9e2f3;'>Meeting ID: " . htmlspecialchars($meetingId) . "</th></tr>";
    if ($meeting_info) {
        echo "<tr><th colspan='12' style='background-color:#d9e2f3;'>Topic: " . htmlspecialchars($meeting_info['meeting_topic']) . "</th></tr>";
        echo "<tr><th colspan='12' style='background-color:#d9e2f3;'>Date: " . htmlspecialchars($meeting_info['meeting_date']) . "</th></tr>";
        echo "<tr><th colspan='12' style='background-color:#d9e2f3;'>Time: " . 
             date('g:i A', strtotime($meeting_info['meeting_start'])) . " - " . 
             date('g:i A', strtotime($meeting_info['meeting_end'])) . "</th></tr>";
    }
    echo "<tr><th colspan='12' style='background-color:#d9e2f3;'>Exported on: " . date('Y-m-d H:i:s') . " (IST)</th></tr>";
    echo "<tr><th colspan='12'></th></tr>"; // Empty row for spacing
    
    // Column headers
    echo "<tr style='background-color:#4472c4; color:white; font-weight:bold;'>";
    echo "<th>No.</th>";
    echo "<th>Student ID</th>";
    echo "<th>Student Name</th>";
    echo "<th>Course</th>";
    echo "<th>Batch</th>";
    echo "<th>Branch</th>";
    echo "<th>Phone</th>";
    echo "<th>First Join Time</th>";
    echo "<th>Last Leave Time</th>";
    echo "<th>Total Duration</th>";
    echo "<th>Sessions</th>";
    echo "<th>Attendance %</th>";
    echo "<th>Status</th>";
    echo "</tr>";
    
    // Data rows
    $counter = 1;
    $totalDurationMinutes = 0;
    $totalStudents = count($data);
    
    foreach ($data as $participant) {
        // Determine row color based on attendance percentage
        $bg_color = '';
        if ($participant['attendance_percentage'] >= 75) {
            $bg_color = 'background-color:#d4edda;'; // Green for excellent attendance
        } elseif ($participant['attendance_percentage'] >= 50) {
            $bg_color = 'background-color:#fff3cd;'; // Yellow for good attendance
        } else {
            $bg_color = 'background-color:#f8d7da;'; // Red for poor attendance
        }
        
        echo "<tr style='$bg_color'>";
        echo "<td>" . $counter++ . "</td>";
        echo "<td>" . htmlspecialchars($participant['student_id']) . "</td>";
        echo "<td>" . htmlspecialchars($participant['name']) . "</td>";
        echo "<td>" . htmlspecialchars($participant['course']) . "</td>";
        echo "<td>" . htmlspecialchars($participant['batch']) . "</td>";
        echo "<td>" . htmlspecialchars($participant['branch']) . "</td>";
        echo "<td>" . htmlspecialchars($participant['phone']) . "</td>";
        echo "<td>" . htmlspecialchars($participant['join_time']) . "</td>";
        echo "<td>" . htmlspecialchars($participant['leave_time']) . "</td>";
        echo "<td>" . htmlspecialchars($participant['total_duration']) . "</td>";
        echo "<td>" . $participant['session_count'] . "</td>";
        echo "<td>" . $participant['attendance_percentage'] . "%</td>";
        echo "<td>" . htmlspecialchars($participant['status']) . "</td>";
        echo "</tr>";
        
        // Add session details if multiple sessions
        if ($participant['session_count'] > 1) {
            echo "<tr style='background-color:#f8f9fa;'>";
            echo "<td colspan='7' style='font-size:10px; padding-left:20px;'>Session Details:</td>";
            echo "<td colspan='5' style='font-size:10px;'>";
            foreach ($participant['session_details'] as $i => $session) {
                echo "Session " . ($i + 1) . ": " . $session['join'] . " - " . $session['leave'] . " ({$session['duration']}m)";
                if ($i < count($participant['session_details']) - 1) echo " | ";
            }
            echo "</td>";
            echo "</tr>";
        }
        
        $totalDurationMinutes += $participant['duration_minutes'];
    }
    
    // Summary statistics
    echo "<tr><td colspan='13'></td></tr>";
    echo "<tr style='font-weight:bold; background-color:#e2efda;'>";
    echo "<td colspan='9'>SUMMARY STATISTICS</td>";
    echo "<td colspan='4'></td>";
    echo "</tr>";
    
    $avgDuration = $totalStudents > 0 ? round($totalDurationMinutes / $totalStudents, 1) : 0;
    $avgAttendance = $totalStudents > 0 ? round(array_sum(array_column($data, 'attendance_percentage')) / $totalStudents, 1) : 0;
    
    echo "<tr style='background-color:#f2f2f2;'>";
    echo "<td colspan='7'>Total Students:</td>";
    echo "<td><strong>$totalStudents</strong></td>";
    echo "<td colspan='5'></td>";
    echo "</tr>";
    
    echo "<tr style='background-color:#f2f2f2;'>";
    echo "<td colspan='7'>Average Duration per Student:</td>";
    echo "<td><strong>" . floor($avgDuration / 60) . "h " . ($avgDuration % 60) . "m</strong></td>";
    echo "<td colspan='5'></td>";
    echo "</tr>";
    
    echo "<tr style='background-color:#f2f2f2;'>";
    echo "<td colspan='7'>Average Attendance Percentage:</td>";
    echo "<td><strong>$avgAttendance%</strong></td>";
    echo "<td colspan='5'></td>";
    echo "</tr>";
    
    // Attendance distribution
    $excellent = count(array_filter($data, function($p) { return $p['attendance_percentage'] >= 75; }));
    $good = count(array_filter($data, function($p) { return $p['attendance_percentage'] >= 50 && $p['attendance_percentage'] < 75; }));
    $poor = count(array_filter($data, function($p) { return $p['attendance_percentage'] < 50; }));
    
    echo "<tr style='background-color:#d4edda;'>";
    echo "<td colspan='7'>Excellent Attendance (75%+):</td>";
    echo "<td><strong>$excellent students</strong></td>";
    echo "<td colspan='5'></td>";
    echo "</tr>";
    
    echo "<tr style='background-color:#fff3cd;'>";
    echo "<td colspan='7'>Good Attendance (50-74%):</td>";
    echo "<td><strong>$good students</strong></td>";
    echo "<td colspan='5'></td>";
    echo "</tr>";
    
    echo "<tr style='background-color:#f8d7da;'>";
    echo "<td colspan='7'>Poor Attendance (<50%):</td>";
    echo "<td><strong>$poor students</strong></td>";
    echo "<td colspan='5'></td>";
    echo "</tr>";

    echo "</table>";
    exit;
} else {
    die("Meeting ID not specified");
}
?>
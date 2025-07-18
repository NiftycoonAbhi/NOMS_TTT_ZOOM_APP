<?php
require_once __DIR__ . '/../admin/includes/config.php';
require_once __DIR__ . '/../admin/includes/functions.php';

session_start();

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

    // Set headers for Excel download
    header('Content-Type: application/vnd.ms-excel');
    header('Content-Disposition: attachment; filename="zoom_attendance_' . $meetingId . '_' . date('Y-m-d') . '.xls"');
    
    // Start Excel content
    echo "<table border='1'>";
    echo "<tr><th colspan='6' style='font-size:16px;'>Zoom Meeting Attendance Report</th></tr>";
    echo "<tr><th colspan='6'>Meeting ID: " . htmlspecialchars($meetingId) . "</th></tr>";
    echo "<tr><th colspan='6'>Exported on: " . date('Y-m-d H:i:s') . "</th></tr>";
    echo "<tr><th colspan='6'></th></tr>"; // Empty row for spacing
    
    // Column headers
    echo "<tr style='background-color:#f2f2f2;'>";
    echo "<th style='font-weight:bold;'>No.</th>";
    echo "<th style='font-weight:bold;'>Participant Name</th>";
    echo "<th style='font-weight:bold;'>Email</th>";
    echo "<th style='font-weight:bold;'>Join Time</th>";
    echo "<th style='font-weight:bold;'>Leave Time</th>";
    echo "<th style='font-weight:bold;'>Duration (minutes)</th>";
    echo "<th style='font-weight:bold;'>Status</th>";
    echo "</tr>";
    
    // Data rows
    $counter = 1;
    $totalDuration = 0;
    foreach ($data as $participant) {
        echo "<tr>";
        echo "<td>" . $counter++ . "</td>";
        echo "<td>" . htmlspecialchars($participant['name']) . "</td>";
        echo "<td>" . htmlspecialchars($participant['email']) . "</td>";
        echo "<td>" . htmlspecialchars($participant['join_time']) . "</td>";
        echo "<td>" . htmlspecialchars($participant['leave_time']) . "</td>";
        echo "<td>" . htmlspecialchars($participant['duration']) . "</td>";
        echo "<td>" . htmlspecialchars(ucfirst($participant['status'])) . "</td>";
        echo "</tr>";
        
        $totalDuration += (float)$participant['duration'];
    }
    
    // Footer with totals
    echo "<tr><td colspan='7'></td></tr>";
    echo "<tr style='font-weight:bold;background-color:#f2f2f2;'>";
    echo "<td colspan='4'>Total Participants: " . ($counter - 1) . "</td>";
    echo "<td>Total Duration: " . round($totalDuration, 2) . " min</td>";
    echo "<td>Avg: " . ($counter > 1 ? round($totalDuration / ($counter - 1), 2) : 0) . " min</td>";
    echo "</tr>";
    
    echo "</table>";
    exit;
} else {
    die("Meeting ID not specified");
}
?>
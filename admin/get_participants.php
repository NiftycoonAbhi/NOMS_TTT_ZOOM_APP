<?php
// Include multi-account configuration
require_once 'includes/multi_account_config.php';

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

include('../common/php/niftycoon_functions.php');
// $admin_access = login_permission('12221');
// if($admin_access == 0){
//     no_alert_header("../../../admin/login");
// }
// if($admin_access != 0){
require_once __DIR__ . '/includes/config.php';
require_once __DIR__ . '/includes/functions.php';

header('Content-Type: application/json');
//   getting the list of the students present in the meeting
try {
    $meetingId = $_GET['meeting_id'] ?? '';
    if (empty($meetingId) || !is_numeric($meetingId)) {
        throw new Exception('Valid meeting ID is required');
    }

    $attendanceData = getAttendanceData();
    
    if (!isset($attendanceData['meetings'][$meetingId])) {
        throw new Exception("Meeting not found: $meetingId");
    }

    $meetingStatus = $attendanceData['meetings'][$meetingId]['status'] ?? 'completed';
    $isActive = ($meetingStatus === 'active');
    
    // Enhanced participant tracking with real-time updates
    $participants = [];
    $studentStats = [];
    $currentParticipants = [];
    
    if (isset($attendanceData['attendees'][$meetingId])) {
        // First pass to organize all sessions by student
        foreach ($attendanceData['attendees'][$meetingId] as $participant) {
            $email = $participant['email'] ?? 'N/A';
            
            if (!isset($studentStats[$email])) {
                $studentStats[$email] = [
                    'name' => $participant['name'] ?? 'Unknown',
                    'email' => $email,
                    'sessions' => [],
                    'total_duration' => 0,
                    'session_count' => 0,
                    'current_session' => null
                ];
            }
            
            $duration = round($participant['duration'] ?? 0, 1);
            $session = [
                'join_time' => $participant['join_time'] ?? 'N/A',
                'leave_time' => $participant['leave_time'] ?? 'N/A',
                'duration' => $duration,
                'status' => empty($participant['leave_time']) || $participant['leave_time'] === 'Still in meeting' 
                           ? 'active' : 'completed'
            ];
            
            $studentStats[$email]['sessions'][] = $session;
            $studentStats[$email]['total_duration'] += $duration;
            $studentStats[$email]['session_count']++;
            
            // Track current active sessions
            if ($session['status'] === 'active') {
                $studentStats[$email]['current_session'] = $session;
                $currentParticipants[$email] = true;
            }
        }
        
        // Second pass to format participants data
        foreach ($studentStats as $email => $stats) {
            $lastSession = end($stats['sessions']);
            $isCurrentlyActive = isset($currentParticipants[$email]);
            
            $participants[] = [
                'name' => $stats['name'],
                'email' => $email,
                'join_time' => isset($lastSession['join_time']) ? 
                              date('M j, Y g:i A', strtotime($lastSession['join_time'])) : 'N/A',
                'leave_time' => $isCurrentlyActive ? 'Still in meeting' : 
                               (isset($lastSession['leave_time']) ? 
                               date('M j, Y g:i A', strtotime($lastSession['leave_time'])) : 'N/A'),
                'duration' => $isCurrentlyActive ? 
                             calculateCurrentDuration($lastSession['join_time']) : 
                             $lastSession['duration'] ?? 0,
                'status' => $isCurrentlyActive ? 'active' : 'left',
                'session_count' => $stats['session_count'],
                'total_duration' => $stats['total_duration'],
                'all_sessions' => $stats['sessions'],
                'is_active' => $isCurrentlyActive,
                'current_duration' => $isCurrentlyActive ? 
                                    calculateCurrentDuration($lastSession['join_time']) : 0
            ];
        }
        
        // Sort participants: active first, then by join time
        usort($participants, function($a, $b) {
            if ($a['is_active'] !== $b['is_active']) {
                return $a['is_active'] ? -1 : 1;
            }
            return strtotime($a['join_time']) <=> strtotime($b['join_time']);
        });
    }

    $response = [
        'success' => true,
        'meeting_id' => $meetingId,
        'meeting_status' => $meetingStatus,
        'is_active' => $isActive,
        'participants' => $participants,
        'active_count' => count($currentParticipants),
        'total_count' => count($participants),
        'timestamp' => date('Y-m-d H:i:s'),
        'last_updated' => $attendanceData['meetings'][$meetingId]['last_updated'] ?? null,
        'current_time' => date('Y-m-d H:i:s')
    ];

    echo json_encode($response);
    exit;

} catch (Exception $e) {
    http_response_code(400);
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage(),
        'meeting_id' => $meetingId ?? 'not provided'
    ]);
    exit;
}

function calculateCurrentDuration($joinTime) {
    if (empty($joinTime)) return 0;
    $joinTimestamp = strtotime($joinTime);
    $currentTimestamp = time();
    return round(($currentTimestamp - $joinTimestamp) / 60, 1); // Convert to minutes
}
// }
?>
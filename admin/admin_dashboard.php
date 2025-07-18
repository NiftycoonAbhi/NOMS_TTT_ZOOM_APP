<?php
/* # ******************************************************************************
# Program: Admin dashboard
# Author: NifTycoon Company
# Copyright © [2023] NifTycoon Company. All rights reserved.
#
# Description: In this program Admin can see the detailed information of the meeting.
#               admin can see the upcoming,completed,live meeting list
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
// 1. Start session and include required functions
// 2. Define Zoom API credentials (account ID, client ID, client secret)
// 3. Get meeting ID from URL parameter, validate it exists
// 4. Get Zoom access token using OAuth credentials
// 5. Fetch meeting details from Zoom API using the access token
// 6. If meeting is recurring (type 8):
//    a. If API returns occurrences, use them directly
//    b. Otherwise, generate occurrences based on recurrence pattern
// 7. If meeting is not recurring, create single occurrence
// 8. Display meeting details and all occurrences in HTML format
// 9. Handle errors at each step with appropriate user feedback
// ===================================================================
session_start();

date_default_timezone_set('Asia/Kolkata');
// $admin_access = login_permission('12221');
// if($admin_access == 0){
//     no_alert_header("../../../admin/login");
// }
// if($admin_access != 0){
// Zoom API credentials
define('ZOOM_ACCOUNT_ID', '89NOV9jAT-SH7wJmjvsptg');
define('ZOOM_CLIENT_ID', '4y5ckqpJQ1WvJAmk3x6PvQ');
define('ZOOM_CLIENT_SECRET', '8eH7szslJoGeBbyRULvEm6Bx7eE630jB');
// Function to get Zoom access token
function getZoomAccessToken()
{
    $url = 'https://zoom.us/oauth/token';
    $headers = [
        'Authorization: Basic ' . base64_encode(ZOOM_CLIENT_ID . ':' . ZOOM_CLIENT_SECRET),
        'Content-Type: application/x-www-form-urlencoded'
    ];
    $data = [
        'grant_type' => 'account_credentials',
        'account_id' => ZOOM_ACCOUNT_ID
    ];
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_POST, 1);
    curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($data));
    curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    $response = curl_exec($ch);
    curl_close($ch);
    return json_decode($response, true);
}
// Function to list Zoom meetings
function listZoomMeetings($access_token, $type = 'upcoming')
{
    $url = 'https://api.zoom.us/v2/users/me/meetings?type=' . $type . '&page_size=30';
    $headers = [
        'Authorization: Bearer ' . $access_token,
        'Content-Type: application/json'
    ];
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    $response = curl_exec($ch);
    curl_close($ch);
    return json_decode($response, true);
}
// Function to delete a Zoom meeting
function deleteZoomMeeting($access_token, $meeting_id)
{
    $url = 'https://api.zoom.us/v2/meetings/' . $meeting_id;
    $headers = [
        'Authorization: Bearer ' . $access_token,
        'Content-Type: application/json'
    ];
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_CUSTOMREQUEST, 'DELETE');
    curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    $response = curl_exec($ch);
    $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);
    return $http_code === 204;
}
// Function to fetch a Zoom meeting by ID
function fetchZoomMeeting($meetingId, $access_token)
{
    $url = "https://api.zoom.us/v2/meetings/$meetingId";
    $headers = [
        "Authorization: Bearer $access_token",
        "Content-Type: application/json"
    ];
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    $result = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);
    if ($httpCode != 200) {
        return null;
    }
    return json_decode($result, true);
}
// Function to generate all occurrences for a recurring meeting
function generateAllOccurrences($meeting)
{
    $occurrences = [];
    // Validate required fields
    if (
        !isset($meeting['type']) || $meeting['type'] != 8 ||
        !isset($meeting['recurrence']) ||
        !isset($meeting['start_time']) ||
        !isset($meeting['duration'])
    ) {
        return $occurrences;
    }
    try {
        $start_time = new DateTime($meeting['start_time'], new DateTimeZone("UTC"));
        $start_time->setTimezone(new DateTimeZone("Asia/Kolkata"));
        $recurrence = $meeting['recurrence'];
        $type = $recurrence['type'];
        $repeat_interval = $recurrence['repeat_interval'] ?? 1;
        $end_times = $recurrence['end_times'] ?? null;
        $end_date_time = $recurrence['end_date_time'] ?? null;
        $end_date = null;
        if ($end_date_time) {
            $end_date = new DateTime($end_date_time, new DateTimeZone("UTC"));
            $end_date->setTimezone(new DateTimeZone("Asia/Kolkata"));
        }
        $count = 0;
        $current = clone $start_time;
        // Convert duration from hours/minutes to total minutes
        $duration_minutes = (int) $meeting['duration'];
        while (true) {
            if ($end_times && $count >= $end_times)
                break;
            if ($end_date && $current > $end_date)
                break;
            $end = clone $current;
            $end->add(new DateInterval('PT' . $duration_minutes . 'M'));
            $occurrences[] = [
                'start_time' => $current->format('Y-m-d\TH:i:s'),
                'end_time' => $end->format('Y-m-d\TH:i:s'),
                'date' => $current->format('D, d M Y'),
                'start' => $current->format('h:i A'),
                'end' => $end->format('h:i A'),
                'duration' => $duration_minutes
            ];
            $count++;
            if ($type == 1) { // Daily
                $current->modify("+$repeat_interval day");
            } elseif ($type == 2) { // Weekly
                $weekly_days = isset($recurrence['weekly_days']) ? explode(",", $recurrence['weekly_days']) : [];
                sort($weekly_days);
                $current_wd = $current->format('N');
                $next_day = null;
                foreach ($weekly_days as $wd) {
                    if ($wd > $current_wd) {
                        $next_day = $wd;
                        break;
                    }
                }
                if ($next_day) {
                    $days_diff = $next_day - $current_wd;
                    $current->modify("+$days_diff days");
                } elseif (!empty($weekly_days)) {
                    $first_day = $weekly_days[0];
                    $days_diff = (7 - $current_wd) + $first_day + (7 * ($repeat_interval - 1));
                    $current->modify("+$days_diff days");
                } else {
                    break;
                }
            } elseif ($type == 3) { // Monthly
                $current->modify("+$repeat_interval month");
            } else {
                break;
            }
        }
    } catch (Exception $e) {
        error_log("Error generating occurrences: " . $e->getMessage());
        return [];
    }
    return $occurrences;
}
// Handle meeting deletion
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['delete_meeting'])) {
    $token_data = getZoomAccessToken();
    if (isset($token_data['access_token'])) {
        $meeting_id = $_POST['meeting_id'];
        $deleted = deleteZoomMeeting($token_data['access_token'], $meeting_id);
        if ($deleted) {
            $success = "Meeting deleted successfully!";
        } else {
            $error = "Failed to delete meeting";
        }
    }
}
// Handle meeting details view
$meetingDetails = null;
$occurrences = [];
$detailsError = '';
if ($_SERVER['REQUEST_METHOD'] === 'GET' && isset($_GET['id'])) {
    $meetingId = trim($_GET['id']);
    if (!empty($meetingId)) {
        $token_data = getZoomAccessToken();
        if (isset($token_data['access_token'])) {
            $meetingDetails = fetchZoomMeeting($meetingId, $token_data['access_token']);
            if ($meetingDetails) {
                // For recurring meetings
                if (($meetingDetails['type'] ?? 0) == 8) {
                    if (isset($meetingDetails['occurrences'])) {
                        // Use Zoom's provided occurrences if available
                        foreach ($meetingDetails['occurrences'] as $occurrence) {
                            if ($occurrence['status'] == 'available') {
                                try {
                                    $start = new DateTime($occurrence['start_time'], new DateTimeZone("UTC"));
                                    $start->setTimezone(new DateTimeZone("Asia/Kolkata"));
                                    $end = clone $start;
                                    $end->add(new DateInterval('PT' . $occurrence['duration'] . 'M'));
                                    $occurrences[] = [
                                        'start_time' => $occurrence['start_time'],
                                        'end_time' => $end->format('Y-m-d\TH:i:s'),
                                        'date' => $start->format('D, d M Y'),
                                        'start' => $start->format('h:i A'),
                                        'end' => $end->format('h:i A'),
                                        'duration' => $occurrence['duration']
                                    ];
                                } catch (Exception $e) {
                                    continue;
                                }
                            }
                        }
                    } else {
                        // Fallback to generating occurrences if not provided by API
                        $occurrences = generateAllOccurrences($meetingDetails);
                    }
                } else {
                    // For non-recurring meetings
                    if (isset($meetingDetails['start_time']) && isset($meetingDetails['duration'])) {
                        try {
                            $start = new DateTime($meetingDetails['start_time'], new DateTimeZone("UTC"));
                            $start->setTimezone(new DateTimeZone("Asia/Kolkata"));
                            $end = clone $start;
                            $end->add(new DateInterval('PT' . (int) $meetingDetails['duration'] . 'M'));
                            $occurrences[] = [
                                'start_time' => $meetingDetails['start_time'],
                                'end_time' => $end->format('Y-m-d\TH:i:s'),
                                'date' => $start->format('D, d M Y'),
                                'start' => $start->format('h:i A'),
                                'end' => $end->format('h:i A'),
                                'duration' => (int) $meetingDetails['duration']
                            ];
                        } catch (Exception $e) {
                            $detailsError = "Invalid meeting time or duration format";
                        }
                    }
                }
                if (empty($occurrences)) {
                    $detailsError = "No valid occurrences found for this meeting";
                }
            } else {
                $detailsError = "Meeting not found or access denied";
            }
        } else {
            $detailsError = "Failed to get Zoom access token";
        }
    } else {
        $detailsError = "Please enter a meeting ID";
    }
}
// Get meetings from Zoom
$meetings = [];
$past_meetings = [];
$live_meetings = [];
$error = '';
$success = '';
$active_tab = isset($_GET['tab']) ? $_GET['tab'] : 'live'; // Default to live meetings
if (!isset($_GET['id'])) {
    try {
        $token_data = getZoomAccessToken();
        if (isset($token_data['access_token'])) {
            // Get upcoming meetings
            $upcoming_data = listZoomMeetings($token_data['access_token'], 'upcoming');
            if (isset($upcoming_data['meetings'])) {
                // Group recurring meetings by their series
                $groupedMeetings = [];
                foreach ($upcoming_data['meetings'] as $meeting) {
                    // For recurring meetings, use the series ID if available
                    $key = ($meeting['type'] == 8 && isset($meeting['occurrences'][0]['series_id']))
                        ? $meeting['occurrences'][0]['series_id']
                        : $meeting['id'];
                    if (!isset($groupedMeetings[$key])) {
                        $groupedMeetings[$key] = $meeting;
                    }
                }
                // Convert back to array and sort by start time (most recent first)
                $meetings = array_values($groupedMeetings);
                usort($meetings, function ($a, $b) {
                    return strtotime($a['start_time']) - strtotime($b['start_time']);
                });
            }

            // Get past meetings
            $past_data = listZoomMeetings($token_data['access_token'], 'past');
            if (isset($past_data['meetings'])) {
                // Group recurring meetings by their series
                $groupedPastMeetings = [];
                foreach ($past_data['meetings'] as $meeting) {
                    // For recurring meetings, use the series ID if available
                    $key = ($meeting['type'] == 8 && isset($meeting['occurrences'][0]['series_id']))
                        ? $meeting['occurrences'][0]['series_id']
                        : $meeting['id'];
                    if (!isset($groupedPastMeetings[$key])) {
                        $groupedPastMeetings[$key] = $meeting;
                    }
                }
                // Convert back to array and sort by start time (most recent first)
                $past_meetings = array_values($groupedPastMeetings);
                usort($past_meetings, function ($a, $b) {
                    return strtotime($b['start_time']) - strtotime($a['start_time']);
                });
            }

            // Get live meetings (meetings that have started but not ended)
            $current_time = time();
            foreach ($meetings as $meeting) {
                $start_time = strtotime($meeting['start_time']);
                $duration_minutes = (int) $meeting['duration'];
                $end_time = $start_time + ($duration_minutes * 60);

                if ($current_time >= $start_time && $current_time <= $end_time) {
                    $live_meetings[] = $meeting;
                }
            }
        } else {
            $error = "Failed to get Zoom access token";
        }
    } catch (Exception $e) {
        $error = "Zoom API error: " . $e->getMessage();
    }
}
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo isset($_GET['id']) ? 'Meeting Details' : 'Zoom Meetings'; ?> | TTT Academy</title>
    <?php if (!isset($_GET['id']))
        include __DIR__ . '/../headers/header2.php'; ?>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="https://cdn.datatables.net/1.11.5/css/dataTables.bootstrap5.min.css">
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="https://cdn.datatables.net/1.11.5/js/jquery.dataTables.min.js"></script>
    <script src="https://cdn.datatables.net/1.11.5/js/dataTables.bootstrap5.min.js"></script>
    <style>
        body {
            padding-top: 70px;
            background-color: #f8f9fa;
        }

        .navbar {
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.1);
        }

        .meeting-card {
            transition: all 0.3s;
            border-left: 4px solid #2d8cff;
            margin-bottom: 20px;
        }

        .meeting-card:hover {
            transform: translateY(-3px);
            box-shadow: 0 5px 15px rgba(45, 140, 255, 0.1);
        }

        .zoom-icon {
            color: #2d8cff;
            font-size: 1.2rem;
            margin-right: 8px;
        }

        .action-btn {
            width: 32px;
            height: 32px;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            margin-right: 5px;
        }

        .stat-card {
            border-left: 4px solid #4e73df;
        }

        .recent-meeting {
            background-color: #fff8e1;
        }

        .upcoming-meeting {
            background-color: #e8f5e9;
        }

        .live-meeting {
            background-color: #ffebee;
        }

        .occurrence-item {
            padding: 10px;
            margin-bottom: 10px;
            border: 1px solid #dee2e6;
            border-radius: 4px;
            background-color: #f8f9fa;
        }

        .time-range {
            font-weight: bold;
        }

        .recurrence-details {
            background-color: #f8f9fa;
            padding: 10px;
            border-radius: 4px;
            margin-top: 10px;
        }

        .back-btn {
            margin-bottom: 20px;
        }

        .recurring-badge {
            font-size: 0.75rem;
        }

        .schedule-info {
            font-size: 0.9rem;
        }

        .nav-tabs .nav-link {
            background-color: #495057;
            color: #495057;
            font-weight: 500;
        }

        .nav-tabs .nav-link.active {
            font-weight: 600;
            border-bottom: 3px solid #2d8cff;
        }

        .tab-content {
            padding: 20px 0;
        }

        .stats-row {
            margin-bottom: 20px;
        }

        .live-badge {
            background-color: #f44336 !important;
            color: white !important;
        }
    </style>
</head>

<body>
    <?php if (isset($_GET['id'])): ?>
        <!-- Meeting Details Page -->
        <div class="container py-4">
            <?php if ($detailsError): ?>
                <div class="alert alert-danger"><?= $detailsError ?></div>
            <?php endif; ?>
            <?php if ($meetingDetails): ?>
                <div class="meeting-card card shadow mb-4">
                    <div class="card-body">
                        <h4><?= htmlspecialchars($meetingDetails['topic'] ?? 'No Topic') ?></h4>
                        <p class="text-muted">Meeting ID: <?= $meetingDetails['id'] ?? 'N/A' ?></p>
                        <p>Total Occurrences: <?= count($occurrences) ?></p>
                        <?php if (($meetingDetails['type'] ?? 0) == 8): ?>
                            <div class="recurrence-details">
                                <h5>Recurrence Pattern:</h5>
                                <?php if (isset($meetingDetails['recurrence'])): ?>
                                    <?php $r = $meetingDetails['recurrence']; ?>
                                    <p>
                                        <?php
                                        if ($r['type'] == 1)
                                            echo "Daily";
                                        elseif ($r['type'] == 2)
                                            echo "Weekly";
                                        elseif ($r['type'] == 3)
                                            echo "Monthly";
                                        else
                                            echo "Custom";
                                        ?>
                                        <?php if (isset($r['repeat_interval']))
                                            echo "(every {$r['repeat_interval']} "; ?>
                                        <?php if ($r['type'] == 2 && isset($r['weekly_days'])): ?>
                                            on <?= str_replace(',', ', ', $r['weekly_days']) ?>
                                        <?php endif; ?>
                                    </p>
                                    <?php if (isset($r['end_date_time'])): ?>
                                        <?php
                                        $end_dt = new DateTime($r['end_date_time'], new DateTimeZone("UTC"));
                                        $end_dt->setTimezone(new DateTimeZone("Asia/Kolkata"));
                                        ?>
                                        <p>Ends on: <?= $end_dt->format('D, d M Y') ?></p>
                                    <?php elseif (isset($r['end_times'])): ?>
                                        <p>Ends after: <?= $r['end_times'] ?> occurrences</p>
                                    <?php endif; ?>
                                <?php endif; ?>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
                <?php if (!empty($occurrences)): ?>
                    <div class="card shadow">
                        <div class="card-header">
                            <h5 class="m-0 font-weight-bold text-primary">All Occurrences</h5>
                        </div>
                        <div class="card-body">
                            <?php foreach ($occurrences as $occurrence): ?>
                                <div class="occurrence-item">
                                    <div class="row">
                                        <div class="col-md-3">
                                            <strong><?= $occurrence['date'] ?? 'N/A' ?></strong>
                                        </div>
                                        <div class="col-md-4">
                                            <span class="time-range">
                                                <?= $occurrence['start'] ?? 'N/A' ?> - <?= $occurrence['end'] ?? 'N/A' ?> IST
                                            </span>
                                        </div>
                                        <div class="col-md-3">
                                            Duration: <?= $occurrence['duration'] ?? 'N/A' ?> minutes
                                        </div>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    </div>
                <?php else: ?>
                    <div class="alert alert-warning">No valid occurrences found for this meeting</div>
                <?php endif; ?>
            <?php endif; ?>
        </div>
    <?php else: ?>
        <!-- Main Meetings List Page -->
        <div class="container-fluid py-4">
            <?php if ($error): ?>
                <div class="alert alert-danger"><?php echo $error; ?></div>
            <?php endif; ?>
            <?php if ($success): ?>
                <div class="alert alert-success"><?php echo $success; ?></div>
            <?php endif; ?>
            <!-- Stats Row -->
            <div class="row stats-row mb-4 justify-content-center">
                <div class="col-xl-3 col-md-6 mb-4">
                    <div class="card stat-card shadow h-100 py-2">
                        <div class="card-body">
                            <div class="row no-gutters align-items-center">
                                <div class="col mr-2">
                                    <div class="text-xs font-weight-bold text-primary text-uppercase mb-1">
                                        Upcoming Meetings</div>
                                    <div class="h5 mb-0 font-weight-bold text-gray-800">
                                        <?php echo count($meetings); ?>
                                    </div>
                                </div>
                                <div class="col-auto">
                                    <i class="fas fa-calendar fa-2x text-gray-300"></i>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="col-xl-3 col-md-6 mb-4">
                    <div class="card stat-card shadow h-100 py-2">
                        <div class="card-body">
                            <div class="row no-gutters align-items-center">
                                <div class="col mr-2">
                                    <div class="text-xs font-weight-bold text-success text-uppercase mb-1">
                                        Completed Meetings</div>
                                    <div class="h5 mb-0 font-weight-bold text-gray-800">
                                        <?php echo count($past_meetings); ?>
                                    </div>
                                </div>
                                <div class="col-auto">
                                    <i class="fas fa-check-circle fa-2x text-gray-300"></i>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="col-xl-3 col-md-6 mb-4">
                    <div class="card stat-card shadow h-100 py-2">
                        <div class="card-body">
                            <div class="row no-gutters align-items-center">
                                <div class="col mr-2">
                                    <div class="text-xs font-weight-bold text-danger text-uppercase mb-1">
                                        Live Meetings</div>
                                    <div class="h5 mb-0 font-weight-bold text-gray-800">
                                        <?php echo count($live_meetings); ?>
                                    </div>
                                </div>
                                <div class="col-auto">
                                    <i class="fas fa-video fa-2x text-gray-300"></i>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <!-- Tabs Navigation -->

            <ul class="nav nav-tabs justify-content-center" id="meetingsTab" role="tablist">
                <li class="nav-item" role="presentation">
                    <button class="nav-link <?= $active_tab === 'upcoming' ? 'active' : '' ?>" id="upcoming-tab"
                        data-bs-toggle="tab" data-bs-target="#upcoming" type="button" role="tab" aria-controls="upcoming"
                        aria-selected="<?= $active_tab === 'upcoming' ? 'true' : 'false' ?>">
                        <i class="fas fa-calendar-alt me-2"></i>Upcoming Meetings
                    </button>
                </li>
                <li class="nav-item" role="presentation">
                    <button class="nav-link <?= $active_tab === 'completed' ? 'active' : '' ?>" id="completed-tab"
                        data-bs-toggle="tab" data-bs-target="#completed" type="button" role="tab" aria-controls="completed"
                        aria-selected="<?= $active_tab === 'completed' ? 'true' : 'false' ?>">
                        <i class="fas fa-check-circle me-2"></i>Completed Meetings
                    </button>
                </li>
                <li class="nav-item" role="presentation">
                    <button class="nav-link <?= $active_tab === 'live' ? 'active' : '' ?>" id="live-tab"
                        data-bs-toggle="tab" data-bs-target="#live" type="button" role="tab" aria-controls="live"
                        aria-selected="<?= $active_tab === 'live' ? 'true' : 'false' ?>">
                        <i class="fas fa-video me-2"></i>Live Meetings
                    </button>
                </li>
            </ul>
            <!-- Tabs Content -->
            <div class="tab-content" id="meetingsTabContent">
                <!-- Upcoming Meetings Tab -->
                <div class="tab-pane fade <?= $active_tab === 'upcoming' ? 'show active' : '' ?>" id="upcoming"
                    role="tabpanel" aria-labelledby="upcoming-tab">
                    <div class="card shadow mb-4">
                        <div class="card-header py-3">
                            <h5 class="m-0 font-weight-bold text-primary"><i class="fas fa-list me-2"></i>Upcoming Zoom
                                Meetings</h5>
                        </div>
                        <div class="card-body">
                            <div class="table-responsive">
                                <table class="table table-striped" id="upcomingMeetingsTable" width="100%" cellspacing="0">
                                    <thead>
                                        <tr>
                                            <th>Meeting</th>
                                            <th>Schedule</th>
                                            <th>Duration</th>
                                            <th>Status</th>
                                            <th>Attendance</th>
                                            <th>Actions</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php foreach ($meetings as $meeting):
                                            $isRecurring = ($meeting['type'] ?? 0) == 8;
                                            $isRecent = false;
                                            $isLive = false;

                                            // Calculate meeting times
                                            $start_time = strtotime($meeting['start_time']);
                                            $duration_minutes = (int) $meeting['duration'];
                                            $end_time = $start_time + ($duration_minutes * 60);
                                            $current_time = time();

                                            // Check if meeting is live
                                            if ($current_time >= $start_time && $current_time <= $end_time) {
                                                $isLive = true;
                                            }

                                            // Check if meeting is recent (started within last hour but not live)
                                            if (!$isLive && $current_time >= $start_time && $current_time <= ($start_time + 3600)) {
                                                $isRecent = true;
                                            }

                                            $isPast = $current_time > $end_time;
                                            $rowClass = $isLive ? 'live-meeting' : ($isRecent ? 'recent-meeting' : ($isPast ? 'table-secondary' : 'upcoming-meeting'));

                                            // Calculate recurrence info
                                            $recurrenceInfo = '';
                                            if ($isRecurring && isset($meeting['recurrence'])) {
                                                $r = $meeting['recurrence'];

                                                // Get pattern type
                                                $pattern = '';
                                                if ($r['type'] == 1)
                                                    $pattern = "Daily";
                                                elseif ($r['type'] == 2)
                                                    $pattern = "Weekly";
                                                elseif ($r['type'] == 3)
                                                    $pattern = "Monthly";

                                                // Add interval if set
                                                if (isset($r['repeat_interval'])) {
                                                    $pattern .= " (every {$r['repeat_interval']} ";
                                                }

                                                // Add days if weekly
                                                if ($r['type'] == 2 && isset($r['weekly_days'])) {
                                                    $pattern .= "on " . str_replace(',', ', ', $r['weekly_days']);
                                                }

                                                // Add end condition
                                                $endCondition = '';
                                                if (isset($r['end_date_time'])) {
                                                    $endDate = new DateTime($r['end_date_time']);
                                                    $endCondition = "Ends on " . $endDate->format('M j, Y');
                                                } elseif (isset($r['end_times'])) {
                                                    $endCondition = "Ends after {$r['end_times']} occurrences";
                                                }

                                                $recurrenceInfo = "<div class='schedule-info'><strong>Pattern:</strong> $pattern<br><strong>$endCondition</strong></div>";
                                            }
                                            ?>
                                            <tr class="<?php echo $rowClass; ?>"
                                                onclick="window.location='recurring_details?id=<?php echo $meeting['id']; ?>';">
                                                <td style="cursor:pointer;">
                                                    <div style="text-decoration: none; color: inherit;">
                                                        <i class="fas fa-video zoom-icon"></i>
                                                        <strong><?php echo htmlspecialchars($meeting['topic']); ?></strong>
                                                        <div class="text-muted small mt-1">
                                                            <i class="fas fa-id-badge me-1"></i>
                                                            ID: <?php echo $meeting['id']; ?>
                                                            <?php if ($isRecurring): ?>
                                                                <span
                                                                    class="badge bg-info text-dark recurring-badge ms-2">Recurring</span>
                                                            <?php endif; ?>
                                                        </div>
                                                    </div>
                                                </td>
                                                <td style="cursor:pointer;">
                                                    <div>
                                                        <strong>Start:</strong> <?php echo date('M j, Y g:i A', $start_time); ?>
                                                    </div>
                                                    <?php if ($isRecurring): ?>
                                                        <?php echo $recurrenceInfo; ?>
                                                    <?php endif; ?>

                                                    <?php if ($isLive): ?>
                                                        <span class="badge bg-danger live-badge">LIVE NOW</span>
                                                    <?php elseif ($isRecent): ?>
                                                        <span class="badge bg-warning text-dark">Happening Now</span>
                                                    <?php elseif ($isPast): ?>
                                                        <span class="badge bg-secondary">Completed</span>
                                                    <?php else: ?>
                                                        <span class="badge bg-success">Upcoming</span>
                                                    <?php endif; ?>
                                                </td>
                                                <td style="cursor:pointer;">
                                                    <?php echo $meeting['duration']; ?> minutes
                                                </td>
                                                <td style="cursor:pointer;">
                                                    <?php
                                                    if ($isLive)
                                                        echo '<span class="badge bg-danger live-badge">Live</span>';
                                                    elseif ($isPast)
                                                        echo '<span class="badge bg-secondary">Past</span>';
                                                    else
                                                        echo '<span class="badge bg-success">Upcoming</span>';
                                                    ?>
                                                </td>
                                                <td>
                                                    <!-- Attendance View Button -->
                                                    <a href="meeting_details?meeting_id=<?php echo $meeting['id']; ?>"
                                                        class="btn " title="View Attendance">
                                                        <i class="fas fa-users"></i> View
                                                    </a>
                                                    <!-- Optional: Display attendance count if available -->
                                                    <?php if (isset($meeting['attendance_count'])): ?>
                                                        <span class="badge bg-primary ms-1">
                                                            <?php echo $meeting['attendance_count']; ?>
                                                        </span>
                                                    <?php endif; ?>
                                                </td>
                                                <td>
                                                    <!-- to edit the details of the meeting -->
                                                    <a href="https://zoom.us/meeting/<?php echo $meeting['id']; ?>/edit"
                                                        target="_blank" class="btn btn-sm btn-warning action-btn" title="Edit">
                                                        <i class="fas fa-edit"></i>
                                                    </a>
                                                    <!-- to delete the meeting directly from the zoom meetings -->
                                                    <form method="POST" style="display:inline;"
                                                        onsubmit="return confirm('Are you sure you want to delete this meeting?')">
                                                        <input type="hidden" name="meeting_id"
                                                            value="<?php echo $meeting['id']; ?>">
                                                        <button type="submit" name="delete_meeting"
                                                            class="btn btn-sm btn-danger action-btn" title="Delete">
                                                            <i class="fas fa-trash-alt"></i>
                                                        </button>
                                                    </form>
                                                </td>
                                            </tr>
                                        <?php endforeach; ?>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                </div>
                <!-- Completed Meetings Tab -->
                <div class="tab-pane fade <?= $active_tab === 'completed' ? 'show active' : '' ?>" id="completed"
                    role="tabpanel" aria-labelledby="completed-tab">
                    <div class="card shadow mb-4">
                        <div class="card-header py-3">
                            <h5 class="m-0 font-weight-bold text-primary"><i class="fas fa-check-circle me-2"></i>Completed
                                Zoom Meetings</h5>
                        </div>
                        <div class="card-body">
                            <div class="table-responsive">
                                <table class="table table-striped" id="completedMeetingsTable" width="100%" cellspacing="0">
                                    <thead>
                                        <tr>
                                            <th>Meeting</th>
                                            <th>Last Schedule Time</th>
                                            <th>Duration</th>
                                            <th>Status</th>
                                            <th>Attendance</th>
                                            <th>Actions</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php foreach ($past_meetings as $meeting):
                                            $isRecurring = ($meeting['type'] ?? 0) == 8;

                                            // Calculate recurrence info
                                            $recurrenceInfo = '';
                                            if ($isRecurring && isset($meeting['recurrence'])) {
                                                $r = $meeting['recurrence'];

                                                // Get pattern type
                                                $pattern = '';
                                                if ($r['type'] == 1)
                                                    $pattern = "Daily";
                                                elseif ($r['type'] == 2)
                                                    $pattern = "Weekly";
                                                elseif ($r['type'] == 3)
                                                    $pattern = "Monthly";

                                                // Add interval if set
                                                if (isset($r['repeat_interval'])) {
                                                    $pattern .= " (every {$r['repeat_interval']} ";
                                                }

                                                // Add days if weekly
                                                if ($r['type'] == 2 && isset($r['weekly_days'])) {
                                                    $pattern .= "on " . str_replace(',', ', ', $r['weekly_days']);
                                                }

                                                // Add end condition
                                                $endCondition = '';
                                                if (isset($r['end_date_time'])) {
                                                    $endDate = new DateTime($r['end_date_time']);
                                                    $endCondition = "Ends on " . $endDate->format('M j, Y');
                                                } elseif (isset($r['end_times'])) {
                                                    $endCondition = "Ends after {$r['end_times']} occurrences";
                                                }

                                                $recurrenceInfo = "<div class='schedule-info'><strong>Pattern:</strong> $pattern<br><strong>$endCondition</strong></div>";
                                            }
                                            ?>
                                            <tr class="table-secondary"
                                                onclick="window.location='recurring_details?id=<?php echo $meeting['id']; ?>';">
                                                <td style="cursor:pointer;">
                                                    <div style="text-decoration: none; color: inherit;">
                                                        <i class="fas fa-video zoom-icon"></i>
                                                        <strong><?php echo htmlspecialchars($meeting['topic']); ?></strong>
                                                        <div class="text-muted small mt-1">
                                                            <i class="fas fa-id-badge me-1"></i>
                                                            ID: <?php echo $meeting['id']; ?>
                                                            <?php if ($isRecurring): ?>
                                                                <span
                                                                    class="badge bg-info text-dark recurring-badge ms-2">Recurring</span>
                                                            <?php endif; ?>
                                                        </div>
                                                    </div>
                                                </td>
                                                <td style="cursor:pointer;">
                                                    <div>
                                                        <?php
                                                        $utc_time = new DateTime($meeting['start_time'], new DateTimeZone('UTC'));
                                                        $utc_time->setTimezone(new DateTimeZone('Asia/Kolkata'));
                                                        echo '' . $utc_time->format('M j, Y g:i A');
                                                        ?>
                                                    </div>
                                                    <?php if ($isRecurring): ?>
                                                        <?php echo $recurrenceInfo; ?>
                                                    <?php endif; ?>
                                                    <span class="badge bg-secondary">Completed</span>
                                                </td>
                                                <td style="cursor:pointer;">
                                                    <?php echo $meeting['duration']; ?> minutes
                                                </td>
                                                <td style="cursor:pointer;">
                                                    <span class="badge bg-secondary">Completed</span>
                                                </td>
                                                <td>
                                                    <!-- Attendance View Button -->
                                                    <a href="meeting_details?meeting_id=<?php echo $meeting['id']; ?>"
                                                        class="btn " title="View Attendance">
                                                        <i class="fas fa-users"></i> View
                                                    </a>
                                                    <!-- Optional: Display attendance count if available -->
                                                    <?php if (isset($meeting['attendance_count'])): ?>
                                                        <span class="badge bg-primary ms-1">
                                                            <?php echo $meeting['attendance_count']; ?>
                                                        </span>
                                                    <?php endif; ?>
                                                </td>
                                                <td>
                                                    <!-- Delete button for completed meetings -->
                                                    <form method="POST" style="display:inline;"
                                                        onsubmit="return confirm('Are you sure you want to delete this meeting?')">
                                                        <input type="hidden" name="meeting_id"
                                                            value="<?php echo $meeting['id']; ?>">
                                                        <button type="submit" name="delete_meeting"
                                                            class="btn btn-sm btn-danger action-btn" title="Delete">
                                                            <i class="fas fa-trash-alt"></i>
                                                        </button>
                                                    </form>
                                                </td>
                                            </tr>
                                        <?php endforeach; ?>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                </div>
                <!-- Live Meetings Tab -->
                <div class="tab-pane fade <?= $active_tab === 'live' ? 'show active' : '' ?>" id="live" role="tabpanel"
                    aria-labelledby="live-tab">
                    <div class="card shadow mb-4">
                        <div class="card-header py-3">
                            <h5 class="m-0 font-weight-bold text-primary"><i class="fas fa-video me-2"></i>Live Zoom
                                Meetings</h5>
                        </div>
                        <div class="card-body">
                            <div class="table-responsive">
                                <table class="table table-striped" id="liveMeetingsTable" width="100%" cellspacing="0">
                                    <thead>
                                        <tr>
                                            <th>Meeting</th>
                                            <th>Schedule</th>
                                            <th>Duration</th>
                                            <th>Status</th>
                                            <th>Attendance</th>
                                            <th>Actions</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php foreach ($live_meetings as $meeting):
                                            $isRecurring = ($meeting['type'] ?? 0) == 8;

                                            // Calculate recurrence info
                                            $recurrenceInfo = '';
                                            if ($isRecurring && isset($meeting['recurrence'])) {
                                                $r = $meeting['recurrence'];

                                                // Get pattern type
                                                $pattern = '';
                                                if ($r['type'] == 1)
                                                    $pattern = "Daily";
                                                elseif ($r['type'] == 2)
                                                    $pattern = "Weekly";
                                                elseif ($r['type'] == 3)
                                                    $pattern = "Monthly";

                                                // Add interval if set
                                                if (isset($r['repeat_interval'])) {
                                                    $pattern .= " (every {$r['repeat_interval']} ";
                                                }

                                                // Add days if weekly
                                                if ($r['type'] == 2 && isset($r['weekly_days'])) {
                                                    $pattern .= "on " . str_replace(',', ', ', $r['weekly_days']);
                                                }

                                                // Add end condition
                                                $endCondition = '';
                                                if (isset($r['end_date_time'])) {
                                                    $endDate = new DateTime($r['end_date_time']);
                                                    $endCondition = "Ends on " . $endDate->format('M j, Y');
                                                } elseif (isset($r['end_times'])) {
                                                    $endCondition = "Ends after {$r['end_times']} occurrences";
                                                }

                                                $recurrenceInfo = "<div class='schedule-info'><strong>Pattern:</strong> $pattern<br><strong>$endCondition</strong></div>";
                                            }
                                            ?>
                                            <tr class="live-meeting"
                                                onclick="window.location='recurring_details?id=<?php echo $meeting['id']; ?>';">
                                                <td style="cursor:pointer;">
                                                    <div style="text-decoration: none; color: inherit;">
                                                        <i class="fas fa-video zoom-icon"></i>
                                                        <strong><?php echo htmlspecialchars($meeting['topic']); ?></strong>
                                                        <div class="text-muted small mt-1">
                                                            <i class="fas fa-id-badge me-1"></i>
                                                            ID: <?php echo $meeting['id']; ?>
                                                            <?php if ($isRecurring): ?>
                                                                <span
                                                                    class="badge bg-info text-dark recurring-badge ms-2">Recurring</span>
                                                            <?php endif; ?>
                                                        </div>
                                                    </div>
                                                </td>
                                                <td style="cursor:pointer;">
                                                    <div>
                                                        <strong>Start:</strong>
                                                        <?php echo date('M j, Y g:i A', strtotime($meeting['start_time'])); ?>
                                                    </div>
                                                    <?php if ($isRecurring): ?>
                                                        <?php echo $recurrenceInfo; ?>
                                                    <?php endif; ?>
                                                    <span class="badge bg-danger live-badge">LIVE NOW</span>
                                                </td>
                                                <td style="cursor:pointer;">
                                                    <?php echo $meeting['duration']; ?> minutes
                                                </td>
                                                <td style="cursor:pointer;">
                                                    <span class="badge bg-danger live-badge">Live</span>
                                                </td>
                                                <td>
                                                    <!-- Attendance View Button -->
                                                    <a href="meeting_details?meeting_id=<?php echo $meeting['id']; ?>"
                                                        class="btn " title="View Attendance">
                                                        <i class="fas fa-users"></i> View
                                                    </a>
                                                    <!-- Optional: Display attendance count if available -->
                                                    <?php if (isset($meeting['attendance_count'])): ?>
                                                        <span class="badge bg-primary ms-1">
                                                            <?php echo $meeting['attendance_count']; ?>
                                                        </span>
                                                    <?php endif; ?>
                                                </td>
                                                <td>
                                                    <!-- Edit button for live meetings -->
                                                    <a href="https://zoom.us/meeting/<?php echo $meeting['id']; ?>/edit"
                                                        target="_blank" class="btn btn-sm btn-warning action-btn" title="Edit">
                                                        <i class="fas fa-edit"></i>
                                                    </a>
                                                    <!-- Delete button for live meetings -->
                                                    <form method="POST" style="display:inline;"
                                                        onsubmit="return confirm('Are you sure you want to delete this meeting?')">
                                                        <input type="hidden" name="meeting_id"
                                                            value="<?php echo $meeting['id']; ?>">
                                                        <button type="submit" name="delete_meeting"
                                                            class="btn btn-sm btn-danger action-btn" title="Delete">
                                                            <i class="fas fa-trash-alt"></i>
                                                        </button>
                                                    </form>
                                                </td>
                                            </tr>
                                        <?php endforeach; ?>
                                        <?php if (empty($live_meetings)): ?>
                                            <tr>
                                                <td colspan="6" class="text-center py-4">
                                                    <i class="fas fa-video-slash text-muted me-2"></i>
                                                    No live meetings currently
                                                </td>
                                            </tr>
                                        <?php endif; ?>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    <?php endif; ?>
    <!-- Bootstrap JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/js/all.min.js"></script>
    <?php if (!isset($_GET['id'])): ?>
        <!-- DataTables JS for main meetings page -->
        <script>
            $(document).ready(function () {
                // Initialize DataTables (your existing code)

                // Tab handling
                const urlParams = new URLSearchParams(window.location.search);
                const tabParam = urlParams.get('tab');

                // If no tab specified, default to 'live'
                const defaultTab = 'live';
                const activeTab = tabParam || defaultTab;

                // Activate the tab
                const tabElement = document.getElementById(activeTab + '-tab');
                if (tabElement) {
                    const tab = new bootstrap.Tab(tabElement);
                    tab.show();
                }

                // Update URL when tab changes
                $('button[data-bs-toggle="tab"]').on('shown.bs.tab', function (e) {
                    const tabId = $(e.target).attr('id').replace('-tab', '');
                    const url = new URL(window.location);
                    url.searchParams.set('tab', tabId);
                    window.history.pushState({}, '', url);
                });
            });
        </script>
    <?php endif; ?>
</body>

</html>
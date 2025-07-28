<?php
/* # ******************************************************************************
# Program: Detailed information of the recurring meeting details
# Author: NifTycoon Company
# Copyright © [2023] NifTycoon Company. All rights reserved.
#
# Description: In this program Admin can view the complete details if the meeting is Recurring complete information will  be stored in this.
#
# This program is the property of NifTycoon Company and is protected by copyright laws.
# Unauthorized reproduction or distribution of this program, or any portion of it,
# may result in severe civil and criminal penalties, and will be prosecuted to the
# maximum extent possible under the law.
#
# NifTycoon Company reserves the right to modify this program as needed.
#
# ****************************************************************************** */

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

// Get current account info for display
$current_account = getCurrentZoomAccount();

// Include common functions
include('../common/php/niftycoon_functions.php');

// Get meeting ID from URL and validate
$meetingId = isset($_GET['id']) ? trim($_GET['id']) : null;
if (!$meetingId) {
    die("<div class='alert alert-danger'>Meeting ID is required</div>");
}

// Include Zoom API functions
require_once 'includes/zoom_api.php';

/**
 * Generate all occurrences for a recurring meeting based on recurrence pattern
 * @param array $meeting Meeting data from Zoom API
 * @return array List of all occurrences with start/end times
 */
function generateAllOccurrences($meeting) {
    $occurrences = [];
    
    // Validate meeting data for recurrence generation
    if (!isset($meeting['type']) || $meeting['type'] != 8 || 
        !isset($meeting['recurrence']) || 
        !isset($meeting['start_time']) || 
        !isset($meeting['duration'])) {
        return $occurrences;
    }
    
    try {
        // Create DateTime object from meeting start time (UTC)
        $start_time = new DateTime($meeting['start_time'], new DateTimeZone("UTC"));
        $start_time->setTimezone(new DateTimeZone("Asia/Kolkata"));
        
        $recurrence = $meeting['recurrence'];
        $type = $recurrence['type'];
        $repeat_interval = $recurrence['repeat_interval'] ?? 1;
        $end_times = $recurrence['end_times'] ?? null;
        $end_date_time = $recurrence['end_date_time'] ?? null;
        
        // Set end date if provided
        $end_date = null;
        if ($end_date_time) {
            $end_date = new DateTime($end_date_time, new DateTimeZone("UTC"));
            $end_date->setTimezone(new DateTimeZone("Asia/Kolkata"));
        }
        
        $count = 0;
        $current = clone $start_time;
        $duration_minutes = (int)$meeting['duration'];
        
        // Generate occurrences until end condition is met
        while (true) {
            // Break if end times limit reached
            if ($end_times && $count >= $end_times) break;
            // Break if end date reached
            if ($end_date && $current > $end_date) break;
            
            // Calculate end time for current occurrence
            $end = clone $current;
            $end->add(new DateInterval('PT' . $duration_minutes . 'M'));
            
            // Add occurrence to list
            $occurrences[] = [
                'start_time' => $current->format('Y-m-d\TH:i:s'),
                'end_time' => $end->format('Y-m-d\TH:i:s'),
                'date' => $current->format('D, d M Y'),
                'start' => $current->format('h:i A'),
                'end' => $end->format('h:i A'),
                'duration' => $duration_minutes
            ];
            
            $count++;
            
            // Calculate next occurrence date based on recurrence type
            if ($type == 1) { // Daily
                $current->modify("+$repeat_interval day");
            } elseif ($type == 2) { // Weekly
                $weekly_days = isset($recurrence['weekly_days']) ? explode(",", $recurrence['weekly_days']) : [];
                sort($weekly_days);
                $current_wd = $current->format('N');
                
                $next_day = null;
                // Find next weekday in the recurrence pattern
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
                    // If no next day found, wrap around to first day
                    $first_day = $weekly_days[0];
                    $days_diff = (7 - $current_wd) + $first_day + (7 * ($repeat_interval - 1));
                    $current->modify("+$days_diff days");
                }
            } elseif ($type == 3) { // Monthly
                $current->modify("+$repeat_interval month");
            }
        }
    } catch (Exception $e) {
        // Log error and return empty array
        error_log("Error generating occurrences: " . $e->getMessage());
    }
    
    return $occurrences;
}

// Initialize variables
$meetingData = null;
$occurrences = [];
$error = '';

// Get Zoom access token
$access_token = getZoomAccessToken();
if ($access_token) {
    // Fetch meeting details from Zoom API
    $meetingData = getZoomMeetingDetails($meetingId);
    
    if ($meetingData) {
        // Handle recurring meetings (type 8)
        if (($meetingData['type'] ?? 0) == 8) {
            // Try to get occurrences from API response
            if (isset($meetingData['occurrences'])) {
                foreach ($meetingData['occurrences'] as $occurrence) {
                    // Only include available occurrences
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
                            // Skip invalid occurrences
                            continue;
                        }
                    }
                }
            } else {
                // Generate occurrences if not provided by API
                $occurrences = generateAllOccurrences($meetingData);
            }
        } else {
            // Handle non-recurring meetings
            if (isset($meetingData['start_time']) && isset($meetingData['duration'])) {
                try {
                    $start = new DateTime($meetingData['start_time'], new DateTimeZone("UTC"));
                    $start->setTimezone(new DateTimeZone("Asia/Kolkata"));
                    $end = clone $start;
                    $end->add(new DateInterval('PT' . (int)$meetingData['duration'] . 'M'));
                    
                    $occurrences[] = [
                        'start_time' => $meetingData['start_time'],
                        'end_time' => $end->format('Y-m-d\TH:i:s'),
                        'date' => $start->format('D, d M Y'),
                        'start' => $start->format('h:i A'),
                        'end' => $end->format('h:i A'),
                        'duration' => (int)$meetingData['duration']
                    ];
                } catch (Exception $e) {
                    $error = "Invalid meeting time or duration format";
                }
            }
        }
        
        // Check if we have any valid occurrences
        if (empty($occurrences)) {
            $error = "No valid occurrences found for this meeting";
        }
    } else {
        $error = "Meeting not found or access denied";
    }
} else {
    $error = "Failed to get Zoom access token";
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Recurring Meeting Details - ID: <?= htmlspecialchars($meetingId) ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
    <style>
        body {
            padding: 20px;
            background-color: #f8f9fa;
        }
        .meeting-card {
            margin-bottom: 20px;
            border-left: 4px solid #2d8cff;
            padding: 15px;
            background-color: white;
            border-radius: 5px;
            box-shadow: 0 2px 5px rgba(0,0,0,0.1);
        }
        .occurrence-item {
            padding: 10px;
            margin-bottom: 10px;
            border: 1px solid #dee2e6;
            border-radius: 4px;
            background-color: #f8f9fa;
        }
        .zoom-icon {
            color: #2d8cff;
            margin-right: 8px;
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
        .action-btn {
            margin-right: 5px;
        }
    </style>
</head>
<body>
    <?php 
        // Include header
        include __DIR__ . '/../headers/header2.php';
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
    
    <div class="container"> 
        <h2 class="mb-4"><i class="fas fa-video zoom-icon"></i>Recurring Meeting Details</h2>
        
        <!-- Display error messages if any -->
        <?php if ($error): ?>
            <div class="alert alert-danger"><?= $error ?></div>
        <?php endif; ?>
        
        <!-- Display meeting details if available -->
        <?php if ($meetingData): ?>
            <div class="meeting-card">
                <h4><?= htmlspecialchars($meetingData['topic'] ?? 'No Topic') ?></h4>
                <p class="text-muted">Meeting ID: <?= $meetingData['id'] ?? 'N/A' ?></p>
                <p>Total Occurrences: <?= count($occurrences) ?></p>
                
                <!-- Display recurrence pattern for recurring meetings -->
                <?php if (($meetingData['type'] ?? 0) == 8): ?>
                    <div class="recurrence-details">
                        <h5>Recurrence Pattern:</h5>
                        <?php if (isset($meetingData['recurrence'])): ?>
                            <?php $r = $meetingData['recurrence']; ?>
                            <p>
                                <?php 
                                // Determine recurrence type
                                if ($r['type'] == 1) echo "Daily";
                                elseif ($r['type'] == 2) echo "Weekly";
                                elseif ($r['type'] == 3) echo "Monthly";
                                else echo "Custom";
                                ?>
                                
                                <?php if ($r['type'] == 2 && isset($r['weekly_days'])): ?>
                                    on <?= str_replace(',', ', ', $r['weekly_days']) ?>
                                <?php endif; ?>
                            </p>
                            
                            <!-- Display end condition -->
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
            
            <!-- Display all occurrences -->
            <?php if (!empty($occurrences)): ?>
                <h4 class="mt-4">All Occurrences:</h4>
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
            <?php else: ?>
                <div class="alert alert-warning">No valid occurrences found for this meeting</div>
            <?php endif; ?>
        <?php endif; ?>
    </div>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
<?php
?>
<?php
// Direct test of meeting details API
require_once 'admin/includes/multi_account_config.php';
require_once 'admin/includes/zoom_api.php';

// Start session and select account
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

// Auto-select first available account for testing
$all_accounts = getAllZoomCredentials();
if (!empty($all_accounts)) {
    setCurrentZoomAccount($all_accounts[0]['id']);
}

$meetingId = "86803353253";

echo "<h1>Direct Meeting Details Test</h1>";
echo "<p>Testing meeting ID: <strong>{$meetingId}</strong></p>";

echo "<h2>1. Access Token Test:</h2>";
$token = getZoomAccessToken();
if ($token) {
    echo "<div style='color: green'>✓ Access token obtained successfully</div>";
    echo "<div>Token preview: " . substr($token, 0, 20) . "...</div>";
} else {
    echo "<div style='color: red'>✗ Failed to get access token</div>";
    exit;
}

echo "<h2>2. Meeting Details API Call:</h2>";
$meetingDetails = getZoomMeetingDetails($meetingId);

echo "<h3>Raw API Response:</h3>";
echo "<pre>" . print_r($meetingDetails, true) . "</pre>";

if (isset($meetingDetails['error'])) {
    echo "<div style='color: red'>✗ API Error: " . htmlspecialchars($meetingDetails['error']) . "</div>";
    echo "<div>HTTP Code: " . ($meetingDetails['http_code'] ?? 'unknown') . "</div>";
} else {
    echo "<div style='color: green'>✓ Meeting details retrieved successfully</div>";
    echo "<div><strong>Meeting Type:</strong> " . ($meetingDetails['type'] ?? 'unknown') . "</div>";
    echo "<div><strong>Topic:</strong> " . htmlspecialchars($meetingDetails['topic'] ?? 'N/A') . "</div>";
    echo "<div><strong>Start Time:</strong> " . htmlspecialchars($meetingDetails['start_time'] ?? 'N/A') . "</div>";
    
    if (isset($meetingDetails['type']) && $meetingDetails['type'] == 8) {
        echo "<div style='color: blue'>📅 This is a recurring meeting</div>";
        if (isset($meetingDetails['occurrences'])) {
            echo "<div><strong>Occurrences:</strong> " . count($meetingDetails['occurrences']) . " found</div>";
            
            // Show occurrence details
            echo "<h3>Occurrence Details:</h3>";
            foreach ($meetingDetails['occurrences'] as $i => $occurrence) {
                $occurrenceTime = new DateTime($occurrence['start_time'], new DateTimeZone('UTC'));
                $currentTime = new DateTime('now', new DateTimeZone('UTC'));
                $isFuture = $occurrenceTime >= $currentTime;
                
                echo "<div style='border: 1px solid #ccc; margin: 5px; padding: 10px;'>";
                echo "<strong>Occurrence " . ($i + 1) . ":</strong><br>";
                echo "ID: " . htmlspecialchars($occurrence['occurrence_id']) . "<br>";
                echo "Start: " . htmlspecialchars($occurrence['start_time']) . "<br>";
                echo "Status: " . ($isFuture ? "<span style='color: green'>Future</span>" : "<span style='color: gray'>Past</span>") . "<br>";
                echo "</div>";
            }
        } else {
            echo "<div style='color: orange'>⚠ No occurrences found in meeting details</div>";
        }
    } else {
        echo "<div style='color: blue'>📅 This is a regular meeting</div>";
    }
}

echo "<h2>3. Registration URL Test:</h2>";
if (!isset($meetingDetails['error'])) {
    $registrationUrl = "meetings/{$meetingId}/registrants";
    
    if (isset($meetingDetails['type']) && $meetingDetails['type'] == 8) {
        // Find next occurrence
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
                echo "<div style='color: green'>✓ Next occurrence found: " . htmlspecialchars($nextOccurrence['occurrence_id']) . "</div>";
            } else {
                echo "<div style='color: red'>✗ No valid occurrence found</div>";
            }
        }
    }
    
    echo "<div><strong>Registration URL:</strong> " . htmlspecialchars($registrationUrl) . "</div>";
}

echo "<h2>4. Current Time Check:</h2>";
$currentUTC = new DateTime('now', new DateTimeZone('UTC'));
echo "<div><strong>Current UTC Time:</strong> " . $currentUTC->format('Y-m-d H:i:s T') . "</div>";
?>

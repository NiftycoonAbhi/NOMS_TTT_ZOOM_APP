<?php
// Quick fix script for finding the correct meeting account
require_once 'admin/includes/multi_account_config.php';
require_once 'admin/includes/zoom_api.php';

if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

$target_meeting_id = "86803353253";

echo "<h1>Finding Meeting 86803353253 Across All Accounts</h1>";

$all_accounts = getAllZoomCredentials();
$meeting_found = false;
$correct_account = null;

foreach ($all_accounts as $i => $account) {
    echo "<h2>Testing Account " . ($i + 1) . ": " . htmlspecialchars($account['name']) . "</h2>";
    
    // Select this account
    setCurrentZoomAccount($account['id']);
    
    // Test access token
    $token = getZoomAccessToken();
    if (!$token) {
        echo "<div style='color: red'>✗ Cannot get access token for this account</div>";
        continue;
    }
    
    echo "<div style='color: green'>✓ Access token obtained</div>";
    
    // Test the specific meeting
    echo "<div>Testing meeting ID: {$target_meeting_id}</div>";
    $meeting_details = getZoomMeetingDetails($target_meeting_id);
    
    if (isset($meeting_details['error'])) {
        echo "<div style='color: red'>✗ " . htmlspecialchars($meeting_details['error']) . "</div>";
    } else {
        echo "<div style='color: green; font-weight: bold; border: 2px solid green; padding: 10px;'>";
        echo "🎯 MEETING FOUND IN THIS ACCOUNT!<br>";
        echo "Topic: " . htmlspecialchars($meeting_details['topic']) . "<br>";
        echo "Type: " . ($meeting_details['type'] ?? 'unknown') . " " . (($meeting_details['type'] ?? 0) == 8 ? "(Recurring)" : "(Regular)") . "<br>";
        echo "Start: " . htmlspecialchars($meeting_details['start_time'] ?? 'N/A') . "<br>";
        
        if (isset($meeting_details['type']) && $meeting_details['type'] == 8) {
            echo "Occurrences: " . (isset($meeting_details['occurrences']) ? count($meeting_details['occurrences']) : 0) . "<br>";
        }
        echo "</div>";
        
        $meeting_found = true;
        $correct_account = $account;
        
        // Test registration to make sure it works
        echo "<h3>Testing Registration on This Account:</h3>";
        $test_result = registerStudent($target_meeting_id, "Test Student", "User", "TTT-TEST-" . date('His'));
        
        if (strpos($test_result, 'https://') === 0) {
            echo "<div style='color: green'>✅ REGISTRATION TEST SUCCESSFUL!</div>";
            echo "<div>Join URL received: " . substr($test_result, 0, 50) . "...</div>";
        } else {
            echo "<div style='color: red'>❌ Registration test failed: " . htmlspecialchars($test_result) . "</div>";
        }
        
        break; // Meeting found, stop searching
    }
    
    echo "<hr>";
}

if ($meeting_found && $correct_account) {
    echo "<div style='background: #d4edda; border: 2px solid #28a745; padding: 20px; margin: 20px 0;'>";
    echo "<h2>✅ SOLUTION FOUND!</h2>";
    echo "<p><strong>Meeting 86803353253 is in account: " . htmlspecialchars($correct_account['name']) . "</strong></p>";
    echo "<p>The system is now configured to use this account. Your registration should work now.</p>";
    echo "<p><a href='Home/index.php?meeting_id=86803353253' style='background: #28a745; color: white; padding: 10px; text-decoration: none; border-radius: 5px;'>🔗 Try Registration Now</a></p>";
    echo "</div>";
} else {
    echo "<div style='background: #f8d7da; border: 2px solid #dc3545; padding: 20px; margin: 20px 0;'>";
    echo "<h2>❌ MEETING NOT FOUND</h2>";
    echo "<p>Meeting ID 86803353253 was not found in any of the configured Zoom accounts.</p>";
    echo "<p><strong>Possible reasons:</strong></p>";
    echo "<ul>";
    echo "<li>The meeting was deleted</li>";
    echo "<li>The meeting ID is incorrect</li>";
    echo "<li>The meeting is in a different Zoom account not configured in this system</li>";
    echo "<li>Access permissions have changed</li>";
    echo "</ul>";
    echo "<p><strong>Next steps:</strong></p>";
    echo "<ol>";
    echo "<li>Verify the meeting ID in your Zoom web portal</li>";
    echo "<li>Check if the meeting is in a different Zoom account</li>";
    echo "<li>Create a new recurring meeting if needed</li>";
    echo "</ol>";
    echo "</div>";
}

// Show current account status
echo "<h2>Current System Status:</h2>";
if (hasSelectedZoomAccount()) {
    $current = getCurrentZoomAccount();
    echo "<div>Currently selected account: <strong>" . htmlspecialchars($current['name']) . "</strong></div>";
} else {
    echo "<div style='color: red'>No account currently selected</div>";
}
?>

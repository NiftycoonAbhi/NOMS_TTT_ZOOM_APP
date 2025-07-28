<?php
// Debug current Zoom account and list meetings
require_once 'admin/includes/multi_account_config.php';
require_once 'admin/includes/zoom_api.php';

// Start session
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

echo "<h1>Zoom Account & Meeting Debug</h1>";

echo "<h2>1. Available Zoom Accounts:</h2>";
$all_accounts = getAllZoomCredentials();
if (!empty($all_accounts)) {
    foreach ($all_accounts as $i => $account) {
        echo "<div style='border: 1px solid #ccc; margin: 5px; padding: 10px;'>";
        echo "<strong>Account " . ($i + 1) . ":</strong><br>";
        echo "ID: " . htmlspecialchars($account['id']) . "<br>";
        echo "Name: " . htmlspecialchars($account['name']) . "<br>";
        echo "Account ID: " . htmlspecialchars($account['account_id']) . "<br>";
        echo "<a href='?select_account=" . $i . "'>Select This Account</a>";
        echo "</div>";
    }
} else {
    echo "<div style='color: red'>No Zoom accounts configured</div>";
}

// Handle account selection
if (isset($_GET['select_account'])) {
    $account_index = intval($_GET['select_account']);
    if (isset($all_accounts[$account_index])) {
        setCurrentZoomAccount($all_accounts[$account_index]['id']);
        echo "<div style='color: green'>✓ Selected account: " . htmlspecialchars($all_accounts[$account_index]['name']) . "</div>";
        echo "<meta http-equiv='refresh' content='2'>";
    }
}

echo "<h2>2. Current Selected Account:</h2>";
if (hasSelectedZoomAccount()) {
    $current_account = getCurrentZoomAccount();
    echo "<div style='color: green'>✓ Account Selected</div>";
    echo "<div><strong>Name:</strong> " . htmlspecialchars($current_account['name']) . "</div>";
    echo "<div><strong>Account ID:</strong> " . htmlspecialchars($current_account['account_id']) . "</div>";
    
    echo "<h2>3. Access Token Test:</h2>";
    $token = getZoomAccessToken();
    if ($token) {
        echo "<div style='color: green'>✓ Access token obtained</div>";
        
        echo "<h2>4. List Recent Meetings:</h2>";
        $meetings_response = listZoomMeetings($token);
        
        if (isset($meetings_response['error'])) {
            echo "<div style='color: red'>✗ Error listing meetings: " . htmlspecialchars($meetings_response['error']) . "</div>";
        } else {
            $meetings = $meetings_response['meetings'] ?? [];
            echo "<div><strong>Found " . count($meetings) . " meetings</strong></div>";
            
            if (!empty($meetings)) {
                // Look for our target meeting
                $target_found = false;
                echo "<h3>Recent Meetings:</h3>";
                
                foreach (array_slice($meetings, 0, 20) as $meeting) { // Show first 20
                    $is_target = ($meeting['id'] == 86803353253);
                    if ($is_target) $target_found = true;
                    
                    echo "<div style='border: 1px solid " . ($is_target ? 'red' : '#ccc') . "; margin: 5px; padding: 10px; background: " . ($is_target ? '#ffe6e6' : 'white') . ";'>";
                    echo "<strong>Meeting ID:</strong> " . htmlspecialchars($meeting['id']) . ($is_target ? " <span style='color: red'>← TARGET MEETING!</span>" : "") . "<br>";
                    echo "<strong>Topic:</strong> " . htmlspecialchars($meeting['topic']) . "<br>";
                    echo "<strong>Type:</strong> " . ($meeting['type'] ?? 'unknown') . " " . (($meeting['type'] ?? 0) == 8 ? "(Recurring)" : "(Regular)") . "<br>";
                    echo "<strong>Start:</strong> " . htmlspecialchars($meeting['start_time'] ?? 'N/A') . "<br>";
                    echo "<strong>Status:</strong> " . htmlspecialchars($meeting['status'] ?? 'unknown') . "<br>";
                    echo "</div>";
                }
                
                if (!$target_found) {
                    echo "<div style='color: red; font-weight: bold; border: 2px solid red; padding: 10px; margin: 10px 0;'>";
                    echo "⚠ TARGET MEETING 86803353253 NOT FOUND in this account!<br>";
                    echo "This meeting may be in a different Zoom account or may have been deleted.";
                    echo "</div>";
                } else {
                    echo "<div style='color: green; font-weight: bold; border: 2px solid green; padding: 10px; margin: 10px 0;'>";
                    echo "✓ TARGET MEETING 86803353253 FOUND in this account!";
                    echo "</div>";
                }
                
                // Show recurring meetings specifically
                echo "<h3>Recurring Meetings Only:</h3>";
                $recurring_meetings = array_filter($meetings, function($m) { return ($m['type'] ?? 0) == 8; });
                if (empty($recurring_meetings)) {
                    echo "<div>No recurring meetings found</div>";
                } else {
                    foreach ($recurring_meetings as $meeting) {
                        echo "<div style='border: 1px solid blue; margin: 5px; padding: 10px; background: #e6f3ff;'>";
                        echo "<strong>ID:</strong> " . htmlspecialchars($meeting['id']) . "<br>";
                        echo "<strong>Topic:</strong> " . htmlspecialchars($meeting['topic']) . "<br>";
                        echo "<strong>Start:</strong> " . htmlspecialchars($meeting['start_time'] ?? 'N/A') . "<br>";
                        echo "</div>";
                    }
                }
            } else {
                echo "<div>No meetings found in this account</div>";
            }
        }
        
    } else {
        echo "<div style='color: red'>✗ Failed to get access token</div>";
    }
    
} else {
    echo "<div style='color: red'>✗ No account selected</div>";
    // Auto-select first account
    if (!empty($all_accounts)) {
        setCurrentZoomAccount($all_accounts[0]['id']);
        echo "<div>Auto-selecting first account...</div>";
        echo "<meta http-equiv='refresh' content='2'>";
    }
}

echo "<h2>5. Manual Meeting Test:</h2>";
echo "<form method='GET'>";
echo "<label>Test Meeting ID: <input type='text' name='test_meeting' value='" . ($_GET['test_meeting'] ?? '86803353253') . "'></label>";
echo "<button type='submit'>Test This Meeting</button>";
echo "</form>";

if (isset($_GET['test_meeting']) && hasSelectedZoomAccount()) {
    $test_id = $_GET['test_meeting'];
    echo "<h3>Testing Meeting ID: " . htmlspecialchars($test_id) . "</h3>";
    
    $test_details = getZoomMeetingDetails($test_id);
    if (isset($test_details['error'])) {
        echo "<div style='color: red'>✗ " . htmlspecialchars($test_details['error']) . "</div>";
    } else {
        echo "<div style='color: green'>✓ Meeting found!</div>";
        echo "<div><strong>Topic:</strong> " . htmlspecialchars($test_details['topic']) . "</div>";
        echo "<div><strong>Type:</strong> " . ($test_details['type'] ?? 'unknown') . "</div>";
    }
}
?>

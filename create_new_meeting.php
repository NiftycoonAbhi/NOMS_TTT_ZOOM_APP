<?php
// Quick script to create a new recurring meeting
require_once 'admin/includes/multi_account_config.php';
require_once 'admin/includes/zoom_api.php';

if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

// Auto-select first account
$all_accounts = getAllZoomCredentials();
if (!empty($all_accounts)) {
    setCurrentZoomAccount($all_accounts[0]['id']);
}

if (isset($_POST['create_meeting'])) {
    echo "<h1>Creating New Recurring Meeting</h1>";
    
    $token = getZoomAccessToken();
    if (!$token) {
        echo "<div style='color: red'>Failed to get access token</div>";
        exit;
    }
    
    $topic = $_POST['topic'] ?: 'TTT Daily Class';
    $start_date = $_POST['start_date'] ?: date('Y-m-d');
    $start_time = $_POST['start_time'] ?: '06:00';
    $duration = intval($_POST['duration']) ?: 60;
    $occurrences = intval($_POST['occurrences']) ?: 30;
    
    // Create recurring meeting data
    $meeting_data = [
        'topic' => $topic,
        'type' => 8, // Recurring meeting with fixed time
        'start_time' => $start_date . 'T' . $start_time . ':00Z',
        'duration' => $duration,
        'recurrence' => [
            'type' => 1, // Daily
            'repeat_interval' => 1,
            'end_times' => $occurrences
        ],
        'settings' => [
            'approval_type' => 0, // Automatically approve
            'registration_type' => 1, // Require registration
            'registrants_email_notification' => false,
            'registrants_confirmation_email' => false,
            'auto_recording' => 'none'
        ]
    ];
    
    $response = callZoomApi('users/me/meetings', $token, 'POST', $meeting_data);
    
    if (isset($response['error'])) {
        echo "<div style='color: red'>Error creating meeting: " . htmlspecialchars($response['error']) . "</div>";
    } else {
        $new_meeting_id = $response['id'];
        echo "<div style='background: #d4edda; border: 2px solid #28a745; padding: 20px;'>";
        echo "<h2>✅ NEW RECURRING MEETING CREATED!</h2>";
        echo "<p><strong>Meeting ID:</strong> <span style='font-size: 24px; color: #007bff;'>{$new_meeting_id}</span></p>";
        echo "<p><strong>Topic:</strong> " . htmlspecialchars($topic) . "</p>";
        echo "<p><strong>Type:</strong> Daily Recurring Meeting</p>";
        echo "<p><strong>Duration:</strong> {$duration} minutes</p>";
        echo "<p><strong>Occurrences:</strong> {$occurrences}</p>";
        echo "<p><a href='Home/index.php?meeting_id={$new_meeting_id}' style='background: #28a745; color: white; padding: 10px; text-decoration: none; border-radius: 5px;'>🔗 Use This Meeting for Registration</a></p>";
        echo "</div>";
        
        echo "<h3>Meeting Details:</h3>";
        echo "<pre>" . print_r($response, true) . "</pre>";
    }
    
} else {
?>
<!DOCTYPE html>
<html>
<head>
    <title>Create New Recurring Meeting</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body>
    <div class="container py-4">
        <h1>Create New Recurring Meeting</h1>
        <p>If your original meeting was deleted, you can create a new one here.</p>
        
        <form method="POST" class="row g-3">
            <div class="col-md-6">
                <label class="form-label">Meeting Topic</label>
                <input type="text" name="topic" class="form-control" value="TTT Daily Class" required>
            </div>
            
            <div class="col-md-3">
                <label class="form-label">Start Date</label>
                <input type="date" name="start_date" class="form-control" value="<?= date('Y-m-d') ?>" required>
            </div>
            
            <div class="col-md-3">
                <label class="form-label">Start Time (UTC)</label>
                <input type="time" name="start_time" class="form-control" value="06:00" required>
            </div>
            
            <div class="col-md-6">
                <label class="form-label">Duration (minutes)</label>
                <input type="number" name="duration" class="form-control" value="60" min="1" max="480" required>
            </div>
            
            <div class="col-md-6">
                <label class="form-label">Number of Occurrences</label>
                <input type="number" name="occurrences" class="form-control" value="30" min="1" max="50" required>
            </div>
            
            <div class="col-12">
                <button type="submit" name="create_meeting" class="btn btn-primary btn-lg">Create Recurring Meeting</button>
                <a href="find_meeting_account.php" class="btn btn-secondary">← Back to Account Search</a>
            </div>
        </form>
    </div>
</body>
</html>
<?php
}
?>

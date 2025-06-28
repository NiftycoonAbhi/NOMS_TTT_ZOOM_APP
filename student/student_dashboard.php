<?php
require '../admin/includes/zoom_api.php';

$dataFile = '../data/registrations.json';
$removedFile = '../data/removed_students.json';
$searchTerm = isset($_GET['student_id']) ? trim($_GET['student_id']) : '';
$studentMeetings = [];
$removedStudents = [];

// Load registration data
$registrations = file_exists($dataFile) ? json_decode(file_get_contents($dataFile), true) ?: [] : [];
$removedStudents = file_exists($removedFile) ? json_decode(file_get_contents($removedFile), true) ?: [] : [];

// Search for meetings
if (!empty($searchTerm)) {
    foreach ($registrations as $meetingId => $participants) {
        foreach ($participants as $id => $details) {
            $searchNormalized = strtolower(trim($searchTerm));
            $idNormalized = strtolower(trim($id));
            $studentIdNormalized = isset($details['student_id']) ? strtolower(trim($details['student_id'])) : '';

            if ($searchNormalized === $idNormalized || $searchNormalized === $studentIdNormalized) {
                $isRemoved = isset($removedStudents[$meetingId][$id]);
                if (!$isRemoved) {
                    // Fetch Zoom Meeting Time
                    $zoomDetails = getZoomMeetingDetails($meetingId);

                    // Extract correct start time (supporting recurring meetings)
                    $meetingTimeUTC = null;
                    if (!empty($zoomDetails['occurrences'][0]['start_time'])) {
                        $meetingTimeUTC = $zoomDetails['occurrences'][0]['start_time'];
                    } elseif (!empty($zoomDetails['start_time'])) {
                        $meetingTimeUTC = $zoomDetails['start_time'];
                    }

                    // Convert to IST
                    $meetingTimeIST = $meetingTimeUTC 
                        ? (new DateTime($meetingTimeUTC, new DateTimeZone('UTC')))
                            ->setTimezone(new DateTimeZone('Asia/Kolkata'))
                            ->format('Y-m-d h:i A')
                        : 'Not available';

                    // Override if not set
                    $details['meeting_time'] = $meetingTimeIST;

                    $studentMeetings[] = [
                        'meeting_id' => $meetingId,
                        'details' => $details
                    ];
                }
                break;
            }
        }
    }
}
?>
<!doctype html>
<html lang="en">
<head>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.0.0/dist/css/bootstrap.min.css" rel="stylesheet">
  <title>Student Dashboard</title>
  <style>
    .dashboard-container {
      max-width: 800px;
      margin: 50px auto;
      padding: 20px;
      border-radius: 10px;
      box-shadow: 0 0 15px rgba(0,0,0,0.1);
      background-color: #fff;
    }
    .meeting-card {
      margin-bottom: 20px;
      border-left: 4px solid #0d6efd;
    }
  </style>
</head>
<body>
  <div class="dashboard-container">
    <h1 class="text-center mb-4">Student Meeting Dashboard</h1>

    <form method="get" class="mb-5">
      <div class="input-group">
        <input type="text" name="student_id" class="form-control form-control-lg" 
               placeholder="Enter your Student ID or Email" value="<?= htmlspecialchars($searchTerm) ?>" required>
        <button class="btn btn-primary btn-lg" type="submit">Find My Meetings</button>
      </div>
    </form>

    <?php if (!empty($searchTerm)): ?>
      <?php if (!empty($studentMeetings)): ?>
        <h3 class="mb-3">Your Scheduled Meetings</h3>

        <?php foreach ($studentMeetings as $meeting): ?>
          <div class="card meeting-card">
            <div class="card-body">
              <div class="d-flex justify-content-between align-items-center">
                <div>
                  <h5>Meeting ID: <?= htmlspecialchars($meeting['meeting_id']) ?></h5>
                  <p class="mb-1">
                    <strong>Meeting Time:</strong> 
                    <?= htmlspecialchars($meeting['details']['meeting_time']) ?>
                  </p>
                </div>
                <div>
                  <a href="<?= htmlspecialchars($meeting['details']['zoom_link']) ?>" 
                     target="_blank" 
                     class="btn btn-success btn-lg">
                    Join Meeting
                  </a>
                </div>
              </div>
            </div>
          </div>
        <?php endforeach; ?>

      <?php else: ?>
        <div class="alert alert-warning">
          <h4>No active meetings found for: <?= htmlspecialchars($searchTerm) ?></h4>
          <p>You may have been removed from these meetings or entered an incorrect ID.</p>
        </div>
      <?php endif; ?>
    <?php endif; ?>
  </div>
</body>
</html>

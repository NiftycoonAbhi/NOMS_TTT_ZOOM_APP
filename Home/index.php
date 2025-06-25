<?php include '../headers/header.php'; ?>
<?php
require '../admin/includes/config.php';
require '../admin/includes/zoom_api.php';

$link = "";
$fullName = "";
$meetingId = isset($_POST['meeting_id']) ? trim($_POST['meeting_id']) : '';
$dataFile = __DIR__ . '/../data/registrations.json';
$removedFile = __DIR__ . '/../data/removed_students.json';
$registrations = [];
$currentMeetingRegistrations = [];
$removedStudents = [];

// Load existing data
if (file_exists($dataFile)) {
  $registrations = json_decode(file_get_contents($dataFile), true) ?: [];
}

if (file_exists($removedFile)) {
  $removedStudents = json_decode(file_get_contents($removedFile), true) ?: [];
}

// Handle student removal
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['remove_student'])) {
  $studentKey = $_POST['student_key'];
  $meetingIdToRemove = $_POST['meeting_id'];

  if (isset($registrations[$meetingIdToRemove][$studentKey])) {
    // Add to removed students list
    $removedStudents[$meetingIdToRemove][$studentKey] = $registrations[$meetingIdToRemove][$studentKey];
    file_put_contents($removedFile, json_encode($removedStudents, JSON_PRETTY_PRINT));

    // Remove from active registrations
    unset($registrations[$meetingIdToRemove][$studentKey]);
    file_put_contents($dataFile, json_encode($registrations, JSON_PRETTY_PRINT));

    // Refresh current view
    $currentMeetingRegistrations = $registrations[$meetingIdToRemove] ?? [];
  }
}

// Get registrations for current meeting ID
if ($meetingId) {
  $currentMeetingRegistrations = $registrations[$meetingId] ?? [];
}

// Handle new registration
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['fname'])) {
  $fname = $_POST['fname'];
  $lname = $_POST['lname'];
  $studentId = isset($_POST['student_id']) ? trim($_POST['student_id']) : '';

  $fullName = trim("$fname $lname");
  $link = registerStudent($meetingId, $fname, $lname, $studentId);

  if (str_starts_with($link, 'http')) {
    // Set the timezone to IST
    date_default_timezone_set('Asia/Kolkata');

    // Create timestamp with IST timezone
    $timestamp = date('Y-m-d H:i:s');

    $registrationData = [
      'timestamp' => $timestamp, // This will now be in IST
      'full_name' => $fullName,
      'student_id' => $studentId,
      'zoom_link' => $link,
      'timezone' => 'IST' // Optional: store the timezone for reference
    ];

    if (!isset($registrations[$meetingId])) {
      $registrations[$meetingId] = [];
    }

    $key = empty($studentId) ? uniqid('student_', true) : $studentId;
    $registrations[$meetingId][$key] = $registrationData;
    file_put_contents($dataFile, json_encode($registrations, JSON_PRETTY_PRINT));

    // Remove from removed list if re-registering
    if (isset($removedStudents[$meetingId][$key])) {
      unset($removedStudents[$meetingId][$key]);
      file_put_contents($removedFile, json_encode($removedStudents, JSON_PRETTY_PRINT));
    }

    $currentMeetingRegistrations = $registrations[$meetingId];
  }
}
?>
<!doctype html>
<html lang="en">

<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>Zoom Student Registration</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.0/font/bootstrap-icons.css">
  <style>
    :root {
      --primary-color: #2d8cff;
      --secondary-color: #6c757d;
      --success-color: #28a745;
      --danger-color: #dc3545;
      --light-bg: #f8f9fa;
      --card-shadow: 0 0.5rem 1rem rgba(0, 0, 0, 0.05);
    }
    
    .meeting-id-container {
      background-color: var(--light-bg);
      border-radius: 0.5rem;
      padding: 1rem;
      margin-bottom: 1.5rem;
      box-shadow: var(--card-shadow);
    }
    
    .form-container {
      display: flex;
      flex-wrap: wrap;
      gap: 1.5rem;
      margin-bottom: 1.5rem;
    }
    
    .form-column {
      flex: 1;
      min-width: 300px;
    }
    
    .registration-card, .selection-card {
      height: 100%;
      box-shadow: var(--card-shadow);
      border: none;
      border-radius: 0.5rem;
    }
    
    .card-header {
      background-color: var(--light-bg);
      border-bottom: 1px solid rgba(0, 0, 0, 0.05);
    }
    
    .badge-count {
      font-size: 0.8rem;
      vertical-align: middle;
    }
    
    .zoom-link {
      color: var(--primary-color);
      text-decoration: none;
    }
    
    .action-buttons .btn {
      margin-right: 0.3rem;
    }
    
    @media (max-width: 768px) {
      .form-column {
        flex: 100%;
      }
    }
  </style>
</head>

<body>
  <div class="container py-4">
    <!-- Meeting ID Input -->
     <!-- section for Entering the meeting id that is created in the zoom app -->
    <div class="meeting-id-container">
      <form method="post" class="row g-3 align-items-center">
        <div class="col-md-12">
          <label for="meeting_id" class="form-label fw-bold">Meeting ID</label>
          <div class="input-group">
            <span class="input-group-text"><i class="bi bi-hash"></i></span>
            <input type="text" name="meeting_id" id="meeting_id" class="form-control"
              placeholder="Enter Meeting ID (e.g. 87185382135)" value="<?= htmlspecialchars($meetingId) ?>" required>
            <button type="submit" class="btn btn-primary">Load</button>
          </div>
        </div>
      </form>
    </div>

    <!-- Forms Container -->
     <!-- Student loading from the database -->
    <div class="form-container">
      <!-- Student Selection Form (Left Column) -->
      <div class="form-column">
        <div class="selection-card card">
          <div class="card-header">
            <h5 class="mb-0"><i class="bi bi-people-fill"></i> Import Students</h5>
          </div>
          <div class="card-body">
            <form action="<?php echo htmlspecialchars($_SERVER['PHP_SELF']); ?>" method="POST">
              <!-- Branch Dropdown -->
              <div class="mb-3">
                <label for="branch" class="form-label">Select Branch</label>
                <select name="branch" id="branch" class="form-select" required>
                  <option value="" disabled selected>-- Select Branch --</option>
                  <?php for ($i = 1; $i <= 5; $i++): ?>
                    <option value="B<?php echo $i; ?>">B<?php echo $i; ?></option>
                  <?php endfor; ?>
                </select>
              </div>

              <!-- Course Dropdown -->
              <div class="mb-3">
                <label for="course" class="form-label">Select Course</label>
                <select name="course" id="course" class="form-select" required>
                  <option value="" disabled selected>-- Select Course --</option>
                  <?php for ($i = 1; $i <= 5; $i++): ?>
                    <option value="C<?php echo $i; ?>">C<?php echo $i; ?></option>
                  <?php endfor; ?>
                </select>
              </div>

              <!-- Batch Dropdown -->
              <div class="mb-3">
                <label for="batch" class="form-label">Select Batch</label>
                <select name="batch" id="batch" class="form-select" required>
                  <option value="" disabled selected>-- Select Batch --</option>
                  <?php for ($i = 1; $i <= 5; $i++): ?>
                    <option value="Batch <?php echo $i; ?>">Batch <?php echo $i; ?></option>
                  <?php endfor; ?>
                </select>
              </div>

              <!-- Submit Button -->
              <div class="d-grid">
                <button type="submit" class="btn btn-primary">
                  <i class="bi bi-upload me-2"></i> Import Students
                </button>
              </div>
            </form>

            <?php if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['branch'])): ?>
              <div class="alert alert-success mt-4">
                <strong>Selection Summary:</strong><br>
                <span class="fw-bold">Branch:</span> <?= htmlspecialchars($_POST['branch']) ?><br>
                <span class="fw-bold">Course:</span> <?= htmlspecialchars($_POST['course']) ?><br>
                <span class="fw-bold">Batch:</span> <?= htmlspecialchars($_POST['batch']) ?>
              </div>
            <?php endif; ?>
          </div>
        </div>
      </div>
 <!-- Student loading from the database ends here -->
      <!-- Registration Form (Right Column) -->
       <!-- Manually adding the student with there student id,student  first name and last name start here -->
      <div class="form-column">
        <div class="registration-card card">
          <div class="card-header">
            <h5 class="mb-0"><i class="bi bi-person-plus-fill"></i> Register New Student</h5>
          </div>
          <div class="card-body">
            <form method="post" class="row g-3">
              <!-- Student ID field -->
              <div class="col-md-6">
                <label for="student_id" class="form-label">Student ID</label>
                <div class="input-group">
                  <span class="input-group-text"><i class="bi bi-person-badge"></i></span>
                  <input type="text" name="student_id" id="student_id" class="form-control" placeholder="Student ID">
                </div>
              </div>

              <!-- First name field -->
              <div class="col-md-6">
                <label for="fname" class="form-label">First name</label>
                <div class="input-group">
                  <span class="input-group-text"><i class="bi bi-person"></i></span>
                  <input name="fname" id="fname" class="form-control" placeholder="First name" required>
                </div>
              </div>

              <!-- Last name field -->
              <div class="col-md-6">
                <label for="lname" class="form-label">Last name</label>
                <div class="input-group">
                  <span class="input-group-text"><i class="bi bi-person"></i></span>
                  <input name="lname" id="lname" class="form-control" placeholder="Last name">
                </div>
              </div>

              <input type="hidden" name="meeting_id" value="<?= htmlspecialchars($meetingId) ?>">

              <div class="col-12">
                <button class="btn btn-primary w-100">
                  <i class="bi bi-save"></i> Register Student
                </button>
              </div>
            </form>

            <?php if (!empty($link)): ?>
              <?php if (str_starts_with($link, 'http')): ?>
                <div class="alert alert-success d-flex align-items-center mt-4">
                  <i class="bi bi-check-circle-fill me-2 fs-4"></i>
                  <div class="w-100">
                    <strong><?= htmlspecialchars($fullName) ?></strong> registered successfully!<br>
                    <div class="d-flex align-items-center mt-2">
                      <span class="me-2">Join Link:</span>
                      <a href="<?= htmlspecialchars($link) ?>" target="_blank" class="zoom-link text-truncate d-inline-block"
                        style="max-width: 70%;">
                        <?= htmlspecialchars($link) ?>
                      </a>
                      <button class="btn btn-sm btn-outline-secondary ms-2 py-0"
                        onclick="copyToClipboard('<?= htmlspecialchars($link) ?>')" title="Copy link">
                        <i class="bi bi-clipboard"></i>
                      </button>
                    </div>
                  </div>
                </div>
              <?php else: ?>
                <div class="alert alert-danger d-flex align-items-center mt-4">
                  <i class="bi bi-exclamation-triangle-fill me-2 fs-4"></i>
                  <div><?= htmlspecialchars($link) ?></div>
                </div>
              <?php endif; ?>
            <?php endif; ?>
          </div>
        </div>
      </div>
    </div>
     <!-- Manually adding the student with there student id,student  first name and last name  ends here-->

    <!-- Registrations List -->
     <!-- Dispaly the registered student list from meeting id starts here -->
    <?php if ($meetingId): ?>
      <div class="registration-card card mt-4">
        <div class="card-header d-flex justify-content-between align-items-center">
          <h5 class="mb-0">
            <i class="bi bi-list-check"></i> Registered Students
            <span class="badge bg-primary rounded-pill badge-count ms-2"><?= count($currentMeetingRegistrations) ?></span>
          </h5>
          <small class="text-muted">Meeting ID: <?= htmlspecialchars($meetingId) ?></small>
        </div>
        <div class="card-body p-0">
          <?php if (empty($currentMeetingRegistrations)): ?>
            <div class="text-center py-4">
              <i class="bi bi-people fs-1 text-muted"></i>
              <p class="text-muted mt-2">No registrations yet for this meeting.</p>
            </div>
          <?php else: ?>
            <div class="table-responsive registration-list">
              <table class="table table-hover mb-0">
                <thead>
                  <tr>
                    <th>ID</th>
                    <th>Name</th>
                    <th>Registered At</th>
                    <th>Actions</th>
                  </tr>
                </thead>
                <tbody>
                  <?php foreach ($currentMeetingRegistrations as $id => $registration): ?>
                    <tr>
                      <td><code><?= htmlspecialchars($id) ?></code></td>
                      <td><?= htmlspecialchars($registration['full_name']) ?></td>
                      <td>
                        <?php
                        $date = new DateTime($registration['timestamp'], new DateTimeZone('Asia/Kolkata'));
                        echo $date->format('d M Y h:i A');
                        ?>
                      </td>
                      <td class="action-buttons">
                        <form method="post" style="display: inline;">
                          <input type="hidden" name="meeting_id" value="<?= htmlspecialchars($meetingId) ?>">
                          <input type="hidden" name="student_key" value="<?= htmlspecialchars($id) ?>">
                          <button type="submit" name="remove_student" class="btn btn-sm btn-danger"
                            onclick="return confirm('Are you sure you want to remove this student?')" title="Remove">
                            <i class="bi bi-trash"></i>
                          </button>
                        </form>
                      </td>
                    </tr>
                  <?php endforeach; ?>
                </tbody>
              </table>
            </div>
          <?php endif; ?>
        </div>
      </div>
    <?php endif; ?>
  </div>
 <!-- Dispaly the registered student list from meeting id ends here -->
  <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
  <script>
    // Auto-submit form when meeting ID changes
    document.getElementById('meeting_id').addEventListener('change', function() {
      this.closest('form').submit();
    });

    // Focus on first name field after page loads
    document.addEventListener('DOMContentLoaded', function() {
      document.getElementById('fname').focus();
    });

    // Copy to clipboard function
    function copyToClipboard(text) {
      navigator.clipboard.writeText(text).then(function() {
        const btn = event.target.closest('button');
        const originalTitle = btn.title;
        btn.innerHTML = '<i class="bi bi-check"></i>';
        btn.title = 'Copied!';
        setTimeout(() => {
          btn.innerHTML = '<i class="bi bi-clipboard"></i>';
          btn.title = originalTitle;
        }, 2000);
      }).catch(function(err) {
        console.error('Could not copy text: ', err);
      });
    }
  </script>
</body>
</html>
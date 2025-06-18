<?php include __DIR__ . '../../../headers/header.php'; ?>
<?php
require_once 'config.php';
require_once 'functions.php';

$message = '';
$attendanceData = getAttendanceData();
$specificMeetingId = isset($_GET['id']) ? trim($_GET['id']) : null;

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['meeting_id'])) {
    $meetingId = trim($_POST['meeting_id']);

    if (!empty($meetingId)) {
        $result = getMeetingParticipants($meetingId);

        if (isset($result['participants'])) {
            $meetingStatus = $result['meeting_status'] ?? 'completed';
            saveAttendanceData($meetingId, $result['participants'], $meetingStatus);

            $statusNote = $meetingStatus === 'active' ?
                " (Meeting is still ongoing - data will update automatically)" :
                " (Completed meeting)";

            $message = "Attendance data for meeting {$meetingId} has been saved successfully!{$statusNote}";
            $attendanceData = getAttendanceData();
            $specificMeetingId = $meetingId; // Set the specific meeting ID to show
        } else {
            $errorMsg = $result['error'] ?? 'Unknown error occurred';
            $message = "Error: Could not fetch participants. $errorMsg";

            if (strpos($errorMsg, 'Meeting does not exist') !== false) {
                $message .= "<br><strong>Note:</strong> This meeting might not exist or you may not have permission to access it.";
            }

            if (isset($result['details'])) {
                $message .= "<br>Details: " . json_encode($result['details']);
            }
        }
    } else {
        $message = "Please enter a valid meeting ID";
    }
}
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Zoom Attendance Tracker</title>

    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <style>
        .duration-cell {
            text-align: right;
            padding-right: 2rem !important;
        }

        .zoom-icon {
            color: #2D8CFF;
            margin-right: 8px;
        }

        .meeting-card {
            border-left: 4px solid #2D8CFF;
            margin-bottom: 15px;
            padding: 15px;
            background-color: #f8f9fa;
            border-radius: 4px;
        }
    </style>
</head>

<body>
    <div class="container mt-4">
        <h1 class="mb-4">Zoom Meeting Attendance Tracker</h1>

        <?php if ($message): ?>
            <div class="alert alert-<?= strpos($message, 'Error') !== false ? 'danger' : 'info' ?>">
                <?= htmlspecialchars($message) ?>
                <?php if (strpos($message, '<br>') !== false): ?>
                    <?= substr($message, strpos($message, '<br>') + 4) ?>
                <?php endif; ?>
            </div>
        <?php endif; ?>

        <div class="card mb-4">
            <div class="card-header bg-primary text-white">
                <h4>Track Meeting</h4>
            </div>
            <div class="card-body">
                <form method="POST" id="trackForm">
                    <div class="mb-3">
                        <label for="meeting_id" class="form-label">Zoom Meeting ID</label>
                        <input type="text" class="form-control" id="meeting_id" name="meeting_id" required pattern="\d+"
                            title="Please enter only numbers" value="<?= htmlspecialchars($specificMeetingId ?? '') ?>">
                        <div class="form-text">Enter the numeric meeting ID from Zoom</div>
                    </div>
                    <button type="submit" class="btn btn-primary" id="trackButton">
                        <span id="buttonText">Track Attendance</span>
                        <span id="spinner" class="spinner-border spinner-border-sm d-none" role="status"></span>
                    </button>
                </form>
            </div>
        </div>

        <?php if ($specificMeetingId && isset($attendanceData['meetings'][$specificMeetingId])): ?>
            <div class="card">
                <div class="card-header bg-primary text-white">
                    <h4>Meeting Details</h4>
                </div>
                <div class="card-body">


                    <div class="table-responsive mt-4">
                        <table class="table table-striped">
                            <thead>
                                <tr>
                                    <th>Participants</th>
                                    <th>Status</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <tr>
                                    <td>
                                        <div class="meeting-card">
                                            <a href="index.php?id=<?= $specificMeetingId ?>"
                                                style="text-decoration: none; color: inherit;">
                                                <i class="fas fa-video zoom-icon"></i>
                                                <strong>Meeting ID: <?= htmlspecialchars($specificMeetingId) ?></strong>
                                                <div class="text-muted small mt-1">
                                                    <i class="fas fa-id-badge me-1"></i>
                                                    ID: <?= htmlspecialchars($specificMeetingId) ?>
                                                </div>
                                            </a>
                                        </div>
                                    </td>
                                    <td>
                                        <?= isset($attendanceData['attendees'][$specificMeetingId]) ?
                                            count($attendanceData['attendees'][$specificMeetingId]) : 0 ?>
                                    </td>
                                    <td>
                                        <?= $attendanceData['meetings'][$specificMeetingId]['status'] === 'active' ?
                                            '<span class="badge bg-success">Active</span>' :
                                            '<span class="badge bg-secondary">Completed</span>' ?>
                                    </td>
                                    <td>
                                        <a href="javascript:void(0)"
                                            onclick="showParticipants('<?= htmlspecialchars($specificMeetingId) ?>')"
                                            class="btn btn-sm btn-info">
                                            View Participants
                                        </a>
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        <?php elseif (empty($attendanceData['meetings'])): ?>
            <div class="alert alert-info">
                No meetings tracked yet. Enter a meeting ID above to get started.
            </div>
        <?php endif; ?>
    </div>

    <!-- Participant Modal -->
    <div class="modal fade" id="participantsModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Participants for Meeting: <span id="modalMeetingId"></span></h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <div class="table-responsive">
                        <table class="table" id="participantsTable">
                            <thead>
                                <tr>
                                    <th>Name</th>
                                    <th>Email</th>
                                    <th>Join Time (IST)</th>
                                    <th>Leave Time (IST)</th>
                                    <th class="duration-cell">Duration (min)</th>
                                    <th>Status</th>
                                </tr>
                            </thead>
                            <tbody></tbody>
                        </table>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        document.getElementById('trackForm').addEventListener('submit', function () {
            const button = document.getElementById('trackButton');
            const spinner = document.getElementById('spinner');
            const buttonText = document.getElementById('buttonText');

            button.disabled = true;
            buttonText.textContent = 'Processing...';
            spinner.classList.remove('d-none');
        });

        function showParticipants(meetingId) {
            const modal = new bootstrap.Modal(document.getElementById('participantsModal'));
            const tableBody = document.querySelector('#participantsTable tbody');

            // Show loading state
            tableBody.innerHTML = '<tr><td colspan="6" class="text-center">Loading...</td></tr>';
            document.getElementById('modalMeetingId').textContent = meetingId;
            modal.show();

            fetch('get_participants.php?meeting_id=' + encodeURIComponent(meetingId))
                .then(response => {
                    if (!response.ok) throw new Error('Network response was not ok');
                    return response.json();
                })
                .then(data => {
                    tableBody.innerHTML = '';

                    if (data && data.length > 0) {
                        data.forEach(participant => {
                            const row = document.createElement('tr');
                            row.innerHTML = `
                                <td>${participant.name || 'N/A'}</td>
                                <td>${participant.email || 'N/A'}</td>
                                <td>${participant.join_time || 'N/A'}</td>
                                <td>${participant.leave_time || 'N/A'}</td>
                                <td class="duration-cell">${participant.duration || '0'}</td>
                                <td>${participant.status === 'active' ?
                                    '<span class="badge bg-success">Active</span>' :
                                    '<span class="badge bg-secondary">Left</span>'}</td>
                            `;
                            tableBody.appendChild(row);
                        });
                    } else {
                        const row = document.createElement('tr');
                        row.innerHTML = '<td colspan="6" class="text-center">No participants found</td>';
                        tableBody.appendChild(row);
                    }
                })
                .catch(error => {
                    console.error('Error:', error);
                    tableBody.innerHTML = '<tr><td colspan="6" class="text-center text-danger">Error loading participants</td></tr>';
                });
        }
    </script>
</body>

</html>
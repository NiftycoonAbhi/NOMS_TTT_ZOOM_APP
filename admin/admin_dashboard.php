<?php
// Zoom API credentials
define('ZOOM_ACCOUNT_ID', '89NOV9jAT-SH7wJmjvsptg');
define('ZOOM_CLIENT_ID', '4y5ckqpJQ1WvJAmk3x6PvQ');
define('ZOOM_CLIENT_SECRET', '8eH7szslJoGeBbyRULvEm6Bx7eE630jB');

// Function to get Zoom access token
function getZoomAccessToken() {
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
function listZoomMeetings($access_token) {
    $url = 'https://api.zoom.us/v2/users/me/meetings?type=upcoming&page_size=30';
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
function deleteZoomMeeting($access_token, $meeting_id) {
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

// Get meetings from Zoom
$meetings = [];
$error = '';
$success = '';

try {
    $token_data = getZoomAccessToken();
    if (isset($token_data['access_token'])) {
        $zoom_data = listZoomMeetings($token_data['access_token']);
        if (isset($zoom_data['meetings'])) {
            // Sort meetings by start time (most recent first)
            usort($zoom_data['meetings'], function($a, $b) {
                return strtotime($b['start_time']) - strtotime($a['start_time']);
            });
            $meetings = $zoom_data['meetings'];
        }
    } else {
        $error = "Failed to get Zoom access token";
    }
} catch (Exception $e) {
    $error = "Zoom API error: " . $e->getMessage();
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Zoom Meetings | TTT Academy</title>
    <?php include __DIR__ . '/../headers/header2.php'; ?>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="https://cdn.datatables.net/1.11.5/css/dataTables.bootstrap5.min.css">
    <style>
        body {
            padding-top: 70px;
            background-color: #f8f9fa;
        }
        .navbar {
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
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
    </style>
</head>
<body>
   
    <div class="container-fluid py-4">
        <?php if ($error): ?>
            <div class="alert alert-danger"><?php echo $error; ?></div>
        <?php endif; ?>
        
        <?php if ($success): ?>
            <div class="alert alert-success"><?php echo $success; ?></div>
        <?php endif; ?>

        <!-- display the total number meetings present in the zoom account -->
        <div class="row mb-4">
            <!-- Stats Card -->
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
        </div>

        <!-- display the list of the upcoming meetings -->
        <div class="card shadow mb-4">
            <div class="card-header py-3">
                <h5 class="m-0 font-weight-bold text-primary"><i class="fas fa-list me-2"></i>Zoom Meetings</h5>
            </div>
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table table-striped" id="meetingsTable" width="100%" cellspacing="0">
                        <thead>
                            <tr>
                                <th>Meeting</th>
                                <th>Date & Time</th>
                                <th>Duration</th>
                                <th>Status</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($meetings as $meeting): 
                                $isRecent = strtotime($meeting['start_time']) > strtotime('-1 hour') && 
                                            strtotime($meeting['start_time']) < time();
                                $isPast = strtotime($meeting['start_time']) < time();
                                $rowClass = $isRecent ? 'recent-meeting' : ($isPast ? 'table-secondary' : 'upcoming-meeting');
                            ?>
                                <tr class="<?php echo $rowClass; ?>">
                                    <td>
                                       <a href="../admin/meeting_details.php?id=<?php echo $meeting['id']; ?>" style="text-decoration: none; color: inherit;">
                                        <i class="fas fa-video zoom-icon"></i>
                                        <strong><?php echo htmlspecialchars($meeting['topic']); ?></strong>
                                        <div class="text-muted small mt-1">
                                             <i class="fas fa-id-badge me-1"></i>
                                             ID: <?php echo $meeting['id']; ?>
                                        </div>
                                        </a>

                                   </td>
                                    <td>
                                        <?php echo date('M j, Y g:i A', strtotime($meeting['start_time'])); ?>
                                        <?php if ($isRecent): ?>
                                            <span class="badge bg-warning text-dark">Happening Now</span>
                                        <?php elseif ($isPast): ?>
                                            <span class="badge bg-secondary">Completed</span>
                                        <?php endif; ?>
                                    </td>
                                    <td><?php echo $meeting['duration']; ?> minutes</td>
                                    <td>
                                        <?php 
                                            if ($isRecent) echo '<span class="badge bg-warning text-dark">Live</span>';
                                            elseif ($isPast) echo '<span class="badge bg-secondary">Past</span>';
                                            else echo '<span class="badge bg-success">Upcoming</span>';
                                        ?>
                                    </td>
                                    <td>
                                        <?php if (!$isPast): ?>
                                        <?php endif; ?>
                                        <!-- to edit the details of the meeting -->
                                        <a href="https://zoom.us/meeting/<?php echo $meeting['id']; ?>/edit" target="_blank"
                                           class="btn btn-sm btn-warning action-btn" title="Edit">
                                            <i class="fas fa-edit"></i>
                                        </a>
                                        <!-- to delete the meeting directly from the zoom meetings -->
                                        <form method="POST" style="display:inline;" onsubmit="return confirm('Are you sure you want to delete this meeting?')">
                                            <input type="hidden" name="meeting_id" value="<?php echo $meeting['id']; ?>">
                                            <button type="submit" name="delete_meeting" class="btn btn-sm btn-danger action-btn" title="Delete">
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

    <!-- Bootstrap JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <!-- jQuery and DataTables -->
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="https://cdn.datatables.net/1.11.5/js/jquery.dataTables.min.js"></script>
    <script src="https://cdn.datatables.net/1.11.5/js/dataTables.bootstrap5.min.js"></script>
    <script>
        $(document).ready(function() {
            $('#meetingsTable').DataTable({
                "order": [[1, "desc"]], // Sort by date descending (most recent first)
                "pageLength": 10,
                "columnDefs": [
                    { "orderable": false, "targets": [4] } // Disable sorting on actions column
                ]
            });
        });
    </script>
</body>
</html>
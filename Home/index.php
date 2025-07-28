<!doctype html>
<html lang="en">

<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>Zoom Student Registration</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.0/font/bootstrap-icons.css">
  <!-- jQuery for AJAX functionality -->
  <script src="https://cdn.jsdelivr.net/npm/jquery@3.6.0/dist/jquery.min.js"></script>
  <script src="../common/js/ajax.js"></script>
  <script src="../common/js/common.js"></script>
</head>

<body>
  <?php
  // Include multi-account configuration (this will start session)
  require_once '../admin/includes/multi_account_config.php';
  
  // Handle quick account selection for testing
  if (isset($_GET['auto_select'])) {
      $all_accounts = getAllZoomCredentials();
      $account_index = intval($_GET['auto_select']) - 1;
      if (!empty($all_accounts) && isset($all_accounts[$account_index])) {
          setCurrentZoomAccount($all_accounts[$account_index]['id']);
          header("Location: index.php" . (isset($_GET['meeting_id']) ? "?meeting_id=" . $_GET['meeting_id'] : ""));
          exit();
      }
  }
  
    // Ensure user has selected a Zoom account
  if (!hasSelectedZoomAccount()) {
      // For testing purposes, provide quick account selection links
      ?>
      <div class="container py-4">
          <div class="row justify-content-center">
              <div class="col-md-8">
                  <div class="card shadow">
                      <div class="card-header bg-warning text-dark">
                          <h4 class="mb-0"><i class="fas fa-exclamation-triangle"></i> Institution Selection Required</h4>
                      </div>
                      <div class="card-body text-center">
                          <h5>Welcome to TTT NOMS Zoom Management System</h5>
                          <p class="mb-4">You need to select your institution before accessing the main dashboard.</p>
                          <div class="mb-4">
                              <i class="fas fa-graduation-cap fa-4x text-primary"></i>
                          </div>
                          <a href="select_institution.php" class="btn btn-primary btn-lg">
                              <i class="fas fa-arrow-right me-2"></i>Select Your Institution
                          </a>
                          
                          <!-- Quick access for testing -->
                          <div class="mt-3">
                              <small class="text-muted d-block mb-2">Quick Access (Testing):</small>
                              <div class="btn-group" role="group">
                                  <a href="?auto_select=1" class="btn btn-outline-secondary btn-sm">TTT Main Account</a>
                                  <a href="?auto_select=2" class="btn btn-outline-secondary btn-sm">Laggere TTT Branch</a>
                              </div>
                          </div>
                          <hr class="my-4">
                          <small class="text-muted">
                              After selecting your institution, you'll be able to:
                              <ul class="list-unstyled mt-2">
                                  <li><i class="fas fa-check text-success me-2"></i>Enter meeting IDs</li>
                                  <li><i class="fas fa-check text-success me-2"></i>Import students to meetings</li>
                                  <li><i class="fas fa-check text-success me-2"></i>Register individual students</li>
                                  <li><i class="fas fa-check text-success me-2"></i>View registered participants</li>
                              </ul>
                          </small>
                      </div>
                  </div>
              </div>
          </div>
      </div>
      </body>
      </html>
      <?php
      exit();
  }
  
  // Handle logout and account switching
  if (isset($_POST['logout'])) {
      logoutUser('../admin/select_zoom_account.php');
  }
  
  if (isset($_POST['switch_account'])) {
      clearCurrentZoomAccount();
      header('Location: select_institution.php');
      exit();
  }
  
  // At this point, user has selected an account and we can continue
  
  // Get current institution details
  $current_account = getCurrentZoomAccount();
  $zoom_credentials_id = getCurrentZoomCredentialsId();
  
  include '../headers/header.php';
  include_once('../common/php/niftycoon_functions.php');
  include_once('../db/dbconn.php');
  require_once '../admin/includes/config.php';
  require_once '../admin/includes/zoom_api.php';

  // $admin_access = login_permission('12221');
  // if($admin_access == 0){
  //     no_alert_header("../../../admin/login");
  // }

  if (isset($_GET['meeting_id'])) {
    $meeting_id = $_GET['meeting_id'];
    $zoomDetails = getZoomMeetingDetails($meeting_id);
    if (empty($zoomDetails)) {
      alert_header("!! Sorry We Are Not Able To Find Meeting. Please Enter Valid Zoom Meeting ID !!", 'index');
    }

    // Display zoom meeting in IFRAME
  } else {
    $meeting_id = '';
  }

  if (isset($_GET['branch_filter'])) {
    $branch_filter = $_GET['branch_filter'];
  } else {
    $branch_filter = '';
  }

  if (isset($_GET['course_filter'])) {
    $course_filter = $_GET['course_filter'];
  } else {
    $course_filter = '';
  }

  if (isset($_GET['batch_filter'])) {
    $batch_filter = $_GET['batch_filter'];
  } else {
    $batch_filter = '';
  }

  $link = "";

  // Handle student removal
  if (isset($_POST['remove_student'])) {
    if (prevent_double_submit(1)) {
      $student_id_remove = $_POST['student_id_remove'];
      $delete_status = 1;

      foreach ($student_id_remove as $key => $value) {
        $is_exist_meet = NifTycoon_Get_Count('zoom', "meeting_id = '$meeting_id' and student_id = '$value'", $conn);
        if ($is_exist_meet != 0) {
          // Add Zoom remove API here
          $delete_status = NifTycoon_Delete_Data("zoom", "meeting_id = '$meeting_id' and student_id = '$value'", $conn);
        }
      }
      if ($delete_status) {
        alert_header("Students Removed Successfully", "index?meeting_id=$meeting_id");
      } else {
        alert_header("!! Something Went Wrong !!", "index?meeting_id=$meeting_id");
      }
    }
  }

  // Handle new registration (optimized individual registration)
  if (isset($_POST['register'])) {
    if (prevent_double_submit(1)) {
      $studentId = isset($_POST['student_id']) ? trim($_POST['student_id']) : '';
      
      // Quick validation
      if (empty($studentId)) {
        alert_header("Student ID is required", "index?meeting_id=$meeting_id");
      }
      
      $is_exist = NifTycoon_Select_Data("student_details", "student_id = '$studentId' and status = 1", "", "id", "desc", 1, $conn)[0];

      if ($is_exist != null) {
        $course = $is_exist['course'];
        $branch = $is_exist['branch'];
        $batch = $is_exist['batch'];
        $name = $is_exist['student_name'];
        
        // Check if already registered (quick check)
        $is_exist_meet = NifTycoon_Get_Count('zoom', "meeting_id = '$meeting_id' and student_id = '$studentId'", $conn);
        if ($is_exist_meet == 0) {
          // Use optimized registration for individual student
          $single_student = array(array(
            'student_id' => $studentId,
            'name' => $name
          ));
          
          $bulk_results = registerStudentsBulk($meeting_id, $single_student);
          
          if ($bulk_results['success_count'] > 0) {
            $success = $bulk_results['success_students'][0];
            $insert_array = array(
              'student_id' => $studentId,
              'meeting_id' => $meeting_id,
              'branch' => $branch,
              'course' => $course,
              'batch' => $batch,
              'link' => $success['join_url'],
              'updated_by' => 'System Auto Registration',
            );

            $insert_status = NifTycoon_Insert_Data("zoom", $insert_array, $conn);

            if ($insert_status) {
              alert_header("Student Added Successfully", "index?meeting_id=$meeting_id");
            } else {
              alert_header("!! Please Contact Developer !!", "developer?error=ZoomApiSuccessButFailedToAddDb");
            }
          } else {
            $error_msg = !empty($bulk_results['errors']) ? $bulk_results['errors'][0] : 'Registration failed';
            alert_header("$error_msg - !! Error Student Not Added !! May be Wrong Meeting ID or due to Repeated Try...! Please Try after 12 hr", "index?meeting_id=$meeting_id");
          }
        } else {
          alert_header("Student is already registered for this meeting", "index?meeting_id=$meeting_id");
        }
      } else {
        alert_header("!!! Student Not Found !!!", "index?meeting_id=$meeting_id");
      }
    }
  }

  if (isset($_POST['register_bulk'])) {
    if (prevent_double_submit(1)) {
      $batch = $_POST['batch'];
      $course = isset($_POST['course']) ? $_POST['course'] : '';
      $branch = isset($_POST['branch']) ? $_POST['branch'] : '';
      
      if ($meeting_id != '') {
        if ($batch != '') {
          $where_cond = "batch = '$batch' and status = 1 and student_id != ''";
        } else {
          alert_header("No Batch Selected", "index?meeting_id=$meeting_id");
        }
        if ($course != '') {
          $where_cond = $where_cond . " and course = '$course'";
        }
        if ($branch != '') {
          $where_cond = $where_cond . " and branch = '$branch'";
        }

        // OPTIMIZED BULK REGISTRATION PROCESS
        echo "<script>showLoader('Preparing student list for bulk registration...');</script>";
        
        // 1. Get all students at once
        $get_students = NifTycoon_Select_Data("student_details", $where_cond, "", "id", "desc", "", $conn);
        $total_found = count($get_students);
        
        if ($total_found == 0) {
          alert_header("No students found matching the criteria", "index?meeting_id=$meeting_id");
        }
        
        echo "<script>updateLoaderMessage('Found {$total_found} students. Checking existing registrations...');</script>";
        
        // 2. Bulk check existing registrations to avoid duplicates
        $student_ids = array_column($get_students, 'student_id');
        $student_ids_str = "'" . implode("','", $student_ids) . "'";
        $existing_registrations = NifTycoon_Select_Data("zoom", "meeting_id = '$meeting_id' AND student_id IN ($student_ids_str)", "", "student_id", "asc", "", $conn);
        $existing_student_ids = array_column($existing_registrations, 'student_id');
        
        // 3. Filter out already registered students
        $students_to_register = array();
        foreach ($get_students as $student) {
          if (!in_array($student['student_id'], $existing_student_ids)) {
            $students_to_register[] = array(
              'student_id' => $student['student_id'],
              'name' => $student['student_name'],
              'course' => $student['course'],
              'batch' => $student['batch'],
              'branch' => $student['branch']
            );
          }
        }
        
        $students_to_register_count = count($students_to_register);
        $already_registered_count = $total_found - $students_to_register_count;
        
        if ($students_to_register_count == 0) {
          alert_header("All {$total_found} students are already registered for this meeting", "index?meeting_id=$meeting_id");
        }
        
        echo "<script>updateLoaderMessage('Registering {$students_to_register_count} new students with Zoom API...');</script>";
        
        // 4. Use optimized bulk registration
        $bulk_results = registerStudentsBulk($meeting_id, $students_to_register);
        
        echo "<script>updateLoaderMessage('Processing database updates...');</script>";
        
        // 5. Batch database inserts for successful registrations
        if ($bulk_results['success_count'] > 0) {
          $insert_data = array();
          foreach ($bulk_results['success_students'] as $success) {
            // Find the student details
            $student_info = null;
            foreach ($students_to_register as $student) {
              if ($student['student_id'] === $success['student_id']) {
                $student_info = $student;
                break;
              }
            }
            
            if ($student_info) {
              $insert_data[] = array(
                'student_id' => $success['student_id'],
                'meeting_id' => $meeting_id,
                'branch' => $student_info['branch'],
                'course' => $student_info['course'],
                'batch' => $student_info['batch'],
                'link' => $success['join_url'],
                'updated_by' => 'System Optimized Bulk Registration',
              );
            }
          }
          
          // Batch insert for better performance
          if (!empty($insert_data)) {
            foreach ($insert_data as $insert_array) {
              NifTycoon_Insert_Data("zoom", $insert_array, $conn);
            }
          }
        }
        
        // 6. Generate comprehensive success/error message
        $message_parts = array();
        
        if ($bulk_results['success_count'] > 0) {
          $message_parts[] = "✅ {$bulk_results['success_count']} students registered successfully";
        }
        
        if ($already_registered_count > 0) {
          $message_parts[] = "ℹ {$already_registered_count} students were already registered";
        }
        
        if ($bulk_results['error_count'] > 0) {
          $error_summary = array_slice($bulk_results['errors'], 0, 5); // Show first 5 errors
          $message_parts[] = "❌ {$bulk_results['error_count']} students failed to register";
          if (count($bulk_results['errors']) > 5) {
            $message_parts[] = "First 5 errors: " . implode("; ", $error_summary) . "...";
          } else {
            $message_parts[] = "Errors: " . implode("; ", $error_summary);
          }
        }
        
        $final_message = implode(" | ", $message_parts);
        
        if ($bulk_results['error_count'] == 0) {
          alert_header($final_message, "index?meeting_id=$meeting_id");
        } else {
          alert_header($final_message, "index?meeting_id=$meeting_id");
        }
      } else {
        alert_header("!! Meeting Id Not Found !!", "index?meeting_id=$meeting_id");
      }
    }
  }

  // Prevent multiple subbmission
  $form_token = prevent_double_submit(0);

  // Show main registration interface (removed admin access check for now)
  $show_interface = true;
  if($show_interface) {
  ?>
  <!-- LOADER START -->
  <div class="loader text-center bg-white fixed-top" id="show_msg_div">
    <div class="row">
      <div class="spinner-border text-center mt-5"></div>
      <h2 id="msg"> Please wait, we are updating your request. &nbsp</h1>
    </div>
  </div>
  <!-- LOADER END -->

  <div class="container py-4">
    <!-- Meeting ID Input -->
    <!-- section for Entering the meeting id that is created in the zoom app -->
    <div class="meeting-id-container">
      <div class="col-md-12">
        <label for="meeting_id" class="form-label fw-bold">Meeting ID</label>
        <div class="input-group">
          <span class="input-group-text"><i class="bi bi-hash"></i></span>
          <input type="text" name="meeting_id" id="meeting_id" class="form-control"
            placeholder="Enter Meeting ID (e.g. 87185382135)" value="<?= htmlspecialchars($meeting_id) ?>" required>
          <button type="button" class="btn btn-primary" onclick="update_link_meeting_id()">Load</button>
        </div>
        <?php if ($meeting_id == ''): ?>
        <div class="alert alert-info mt-3">
          <h6><i class="bi bi-info-circle"></i> Getting Started</h6>
          <p class="mb-2">To register students for a meeting, please:</p>
          <ol class="mb-2">
            <li><strong>Enter a Meeting ID</strong> in the field above</li>
            <li><strong>Click "Load"</strong> to access registration options</li>
            <li><strong>Choose your method:</strong> Bulk import or individual registration</li>
          </ol>
          <small class="text-muted">
            <strong>Need a Meeting ID?</strong> 
            <a href="https://zoom.us/meeting/schedule" target="_blank" class="link-primary">Create a new Zoom meeting</a> 
            or get the ID from an existing meeting.
          </small>
        </div>
        
        <!-- Preview of available features -->
        <div class="row mt-4">
          <div class="col-md-6">
            <div class="card border-primary">
              <div class="card-body text-center">
                <i class="bi bi-people-fill text-primary" style="font-size: 2rem;"></i>
                <h6 class="mt-2">Bulk Import Students</h6>
                <p class="text-muted small">Import multiple students by selecting batch, course, and branch</p>
              </div>
            </div>
          </div>
          <div class="col-md-6">
            <div class="card border-success">
              <div class="card-body text-center">
                <i class="bi bi-person-plus-fill text-success" style="font-size: 2rem;"></i>
                <h6 class="mt-2">Individual Registration</h6>
                <p class="text-muted small">Register single students using their Student ID</p>
              </div>
            </div>
          </div>
        </div>
        <?php endif; ?>
      </div>
    </div>
    <?php
    // Show forms only if meeting_id is provided
    if ($meeting_id != '') {
    ?>
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
              <form method="POST" onsubmit="show_msg('Processing your request')">
                <input type="hidden" name="form_token" value="<?= $form_token ?>">
                <input type="hidden" name="meeting_id" value="<?= $meeting_id ?>">
                
                <!-- Branch Dropdown (First) -->
                <div class="mb-3">
                  <label for="branch" class="form-label">Select Branch</label>
                  <select name="branch" id="branch" class="form-select" required onchange="get_courses_by_branch(this.value)">
                    <option value="">-- Select Branch --</option>
                    <?php
                    $get_branches = NifTycoon_Select_Data("branch_details", "", "", "id", "asc", "", $conn);
                    foreach ($get_branches as $fetch_branch) {
                      $branch_code = $fetch_branch['branch_code'];
                      $branch_name = $fetch_branch['branch_name'];
                      echo "<option value='$branch_code'>$branch_code - $branch_name</option>";
                    }
                    ?>
                  </select>
                </div>

                <!-- Course Dropdown (Second) -->
                <div class="mb-3" id="select_courses"></div>

                <!-- Batch Dropdown (Third) -->
                <div class="mb-3" id="select_batches"></div>

                <!-- Submit Button -->
                <div class="d-grid">
                  <button type="submit" class="btn btn-primary" name="register_bulk" onclick="return confirmation('Are you sure..! Filled data is correct..?')">
                    <i class="bi bi-upload me-2"></i> Import Students
                  </button>
                </div>
              </form>
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
              <form method="post" class="row g-3" onsubmit="show_msg('Processing your request')">
                <input type="hidden" name="form_token" value="<?= $form_token ?>">
                <!-- Student ID field -->
                <div class="col-md-12 col-12 col-lg-12 col-sm-12">
                  <label for="student_id" class="form-label">Student ID</label>
                  <div class="input-group">
                    <span class="input-group-text"><i class="bi bi-person-badge"></i></span>
                    <input type="text" name="student_id" id="student_id" class="form-control" placeholder="Student ID" required>
                  </div>
                </div>
                <input type="hidden" name="meeting_id" value="<?= htmlspecialchars($meeting_id) ?>">
                <div class="col-12">
                  <button type="submit" class="btn btn-primary w-100" name="register" onclick="console.log('Registration button clicked'); return confirmation('Are you sure..! Filled data is correct..?')">
                    <i class="bi bi-save"></i> Register Student
                  </button>
                  <small class="text-muted mt-2 d-block">💡 Tip: Check browser console (F12) for any error messages</small>
                </div>
              </form>
            </div>
          </div>
        </div>
      </div>
      <!-- Manually adding the student with there student id,student  first name and last name  ends here-->

      <!-- Registrations List -->
      <!-- Dispaly the registered student list from meeting id starts here -->
      <div class="registration-card card mt-4">
        <h5 class="mb-0">
          <?php
          $where_remove = "meeting_id = '$meeting_id'";
          if ($branch_filter != '') {
            $where_remove = $where_remove . " and branch = '$branch_filter'";
          }
          if ($course_filter != '') {
            $where_remove = $where_remove . " and course = '$course_filter'";
          }
          if ($batch_filter != '') {
            $where_remove = $where_remove . " and course = '$course_filter' and batch = '$batch_filter'";
          }
          $count = NifTycoon_Get_Count('zoom', $where_remove, $conn);
          ?>
          <i class="bi bi-list-check"></i> Registered Students
          <span class="badge bg-primary rounded-pill badge-count ms-2"><?= $count ?></span>
        </h5>
        <div class="card-header d-flex justify-content-end align-items-center">
          <small class="text-muted" style="margin-right: 20px;">
            <select class="form-select" onchange="update_branch_link(this.value)">
              <?php
              if ($branch_filter != '') {
                echo "<option value='$branch_filter'>$branch_filter</option>";
                echo "<option value=''>All Branchs</option>";
              } else {
                echo "<option value=''>Branch Filter</option>";
              }
              $get_branch_f = NifTycoon_Select_Data("zoom", "meeting_id = '$meeting_id'", "branch", "id", "desc", "", $conn);
              foreach ($get_branch_f as $fetch_branch_f) {
                $branch_f = $fetch_branch_f['branch'];
                echo "<option value='$branch_f'>$branch_f</option>";
              }
              ?>
            </select>
          </small>
          <?php if ($branch_filter != '') { ?>
            <small class="text-muted" style="margin-right: 20px;">
              <select class="form-select" onchange="update_course_link(this.value)">
                <?php
                if ($course_filter != '') {
                  echo "<option value='$course_filter'>$course_filter</option>";
                  echo "<option value=''>All Course</option>";
                } else {
                  echo "<option value=''>Course Filter</option>";
                }
                $get_course_f = NifTycoon_Select_Data("zoom", "meeting_id = '$meeting_id' and branch = '$branch_filter'", "course", "id", "desc", "", $conn);
                foreach ($get_course_f as $fetch_course_f) {
                  $course_f = $fetch_course_f['course'];
                  echo "<option value='$course_f'>$course_f</option>";
                }
                ?>
              </select>
            </small>
          <?php }
          if ($course_filter != '') { ?>
            <small class="text-muted">
              <select class="form-select" onchange="update_batch_link(this.value)">
                <?php
                if ($batch_filter != '') {
                  echo "<option value='$batch_filter'>$batch_filter</option>";
                  echo "<option value=''>All Batches</option>";
                } else {
                  echo "<option value=''>Batch Filter</option>";
                }
                $get_batch_f = NifTycoon_Select_Data("zoom", "meeting_id = '$meeting_id' and branch = '$branch_filter' and course = '$course_filter'", "course", "id", "desc", "", $conn);
                foreach ($get_batch_f as $fetch_batch_f) {
                  $batch_f = $fetch_batch_f['batch'];
                  echo "<option value='$batch_f'>$batch_f</option>";
                }
                ?>
              </select>
            </small>
          <?php } ?>
        </div>
        <div class="card-body p-0">
          <?php if ($count == 0) { ?>
            <div class="text-center py-4">
              <i class="bi bi-people fs-1 text-muted"></i>
              <p class="text-muted mt-2">No registrations yet for this meeting.</p>
            </div>
          <?php } else {
          ?>
            <form method="post" style="display: inline;" onsubmit="show_msg('Processing your request')">
              <input type="hidden" name="form_token" value="<?= $form_token ?>">
              <div class="table-responsive registration-list">
                <table class="table table-hover mb-0">
                  <thead>
                    <tr>
                      <th width="40px"><input type="checkbox" id="selectAll" style="border: solid 2px red;" class="form-check-input"></th>
                      <th>Sl No.</th>
                      <th>Student Id</th>
                      <th>Registered On</th>
                      <th>Registered By</th>
                      <th>Link</th>
                    </tr>
                  </thead>
                  <tbody>
                    <?php
                    $si = 0;
                    $get_students_zoom = NifTycoon_Select_Data("zoom", $where_remove, "", "id", "desc", $count, $conn);
                    foreach ($get_students_zoom as $fetch_students_zoom) {
                      $si++;
                    ?>
                      <tr>
                        <td><input type="checkbox" style="border: solid 2px black;" name="student_id_remove[]" value="<?= $fetch_students_zoom['student_id']; ?>" class="form-check-input student-checkbox"></td>
                        <td><code><?= $si ?></code></td>
                        <td><?= $fetch_students_zoom['student_id']; ?></td>
                        <td><?= $fetch_students_zoom['updated_on']; ?></td>
                        <td><?= $fetch_students_zoom['updated_by']; ?></td>
                        <td><button class="btn btn-sm btn-outline-secondary ms-2 py-0"
                            onclick="copyToClipboard('<?= $fetch_students_zoom['link']; ?>')" title="Copy link">
                            <i class="bi bi-clipboard"></i>
                          </button></td>
                      </tr>
                    <?php } ?>
                  </tbody>
                </table>
              </div>
            <?php
          } ?>
            <div class="p-3 border-top">
              <button type="submit" name="remove_student" class="btn btn-sm btn-danger" onclick="return confirmation('Are you sure..! Filled data is correct..?')" title="Remove" id="deleteSelectedBtn" disabled>
                <i class="bi bi-trash"> Delete Selected</i>
              </button>
            </form>
        </div>
      </div>
  </div>

  </div>
<?php
    }
?>
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

  .registration-card,
  .selection-card {
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

  /* LOADER STYLE START */
  .loader {
    height: 100%;
    top: 0;
    position: fixed;
    z-index: 1080;
    width: 100%;
    display: none;
  }

  .loader .spinner-border {
    color: red;
    font-size: 50px;
    height: 50px;
    width: 50px;
    align-items: center;
    margin-left: auto;
    margin-right: auto;
  }

  .loader h2 {
    color: darkblue;
    align-items: center;
    margin-left: auto;
    margin-right: auto;
    font-style: italic;
  }

  /* LOADER STYLE END */
</style>
<!-- Dispaly the registered student list from meeting id ends here -->
<script>
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

  function update_link_meeting_id() {
    var meeting_id = document.getElementById('meeting_id').value;
    window.location = "index?meeting_id=" + meeting_id;
  }

  function show_msg(message) {
    document.getElementById('msg').innerText = message;
    document.getElementById('show_msg_div').style.display = 'block';
    return true; // Allow form submission to continue
  }

  function hide_msg() {
    document.getElementById('show_msg_div').style.display = 'none';
  }

  // Enhanced loader functions for bulk registration
  function showLoader(message) {
    document.getElementById('msg').innerText = message || 'Processing...';
    document.getElementById('show_msg_div').style.display = 'block';
    
    // Add progress animation
    const spinner = document.querySelector('.spinner-border');
    if (spinner) {
      spinner.style.animation = 'spin 1s linear infinite';
    }
  }

  function updateLoaderMessage(message) {
    const msgElement = document.getElementById('msg');
    if (msgElement) {
      msgElement.innerText = message;
      
      // Add a subtle fade effect
      msgElement.style.opacity = '0.7';
      setTimeout(() => {
        msgElement.style.opacity = '1';
      }, 200);
    }
  }

  function hideLoader() {
    document.getElementById('show_msg_div').style.display = 'none';
  }

  function confirmation(message) {
    return confirm(message);
  }

  function get_batches(course) {
    $.ajax({
      type: 'POST',
      url: 'fetch_batch.php',
      data: {
        text: course
      },
      success: function(data) {

        $('#select_batchs').html(data);
      }
    })
  }

  function get_courses_by_branch(branch) {
    // Clear dependent dropdowns first
    $('#select_courses').html('');
    $('#select_batches').html('');
    
    if (branch === '') {
      return;
    }
    
    $.ajax({
      type: 'POST',
      url: 'fetch_course_by_branch.php',
      data: {
        branch: branch
      },
      success: function(data) {
        $('#select_courses').html(data);
      },
      error: function() {
        $('#select_courses').html('<div class="alert alert-danger">Error loading courses</div>');
      }
    })
  }

  function get_batches_by_course(course, branch) {
    // Clear batches dropdown first
    $('#select_batches').html('');
    
    if (course === '' || branch === '') {
      return;
    }
    
    $.ajax({
      type: 'POST',
      url: 'fetch_batch_by_course.php',
      data: {
        course: course,
        branch: branch
      },
      success: function(data) {
        $('#select_batches').html(data);
      },
      error: function() {
        $('#select_batches').html('<div class="alert alert-danger">Error loading batches</div>');
      }
    })
  }

  // Select all checkbox functionality
  document.getElementById('selectAll').addEventListener('change', function() {
    const checkboxes = document.querySelectorAll('.student-checkbox');
    checkboxes.forEach(checkbox => {
      checkbox.checked = this.checked;
    });
    updateDeleteButtonState();
  });

  // Update delete button state when individual checkboxes change
  document.querySelectorAll('.student-checkbox').forEach(checkbox => {
    checkbox.addEventListener('change', updateDeleteButtonState);
  });

  function updateDeleteButtonState() {
    const checkedCount = document.querySelectorAll('.student-checkbox:checked').length;
    const deleteBtn = document.getElementById('deleteSelectedBtn');
    deleteBtn.disabled = checkedCount === 0;
    if (checkedCount > 0) {
      deleteBtn.innerHTML = `<i class="bi bi-trash"></i> Delete Selected (${checkedCount})`;
    } else {
      deleteBtn.innerHTML = `<i class="bi bi-trash"></i> Delete Selected`;
    }
  }

  function update_batch_link(batch) {
    window.location = "index?meeting_id=<?= $meeting_id ?>&batch_filter=" + batch;
  }

  function update_course_link(course) {
    window.location = "index?meeting_id=<?= $meeting_id ?>&batch_filter=<?= $batch_filter ?>&course_filter=" + course;
  }

  function update_branch_link(branch) {
    window.location = "index?meeting_id=<?= $meeting_id ?>&batch_filter=<?= $batch_filter ?>&course_filter=<?= $course_filter ?>&branch_filter=" + branch;
  }

  // Enhanced bulk registration form handler
  document.addEventListener('DOMContentLoaded', function() {
    const bulkForm = document.querySelector('form[action*="register_bulk"]');
    if (bulkForm) {
      bulkForm.addEventListener('submit', function(e) {
        const branch = document.querySelector('select[name="branch"]')?.value || '';
        const course = document.querySelector('select[name="course"]')?.value || '';
        const batch = document.querySelector('select[name="batch"]')?.value || '';
        
        if (!batch) {
          alert('Please select a batch before proceeding with bulk registration.');
          e.preventDefault();
          return false;
        }
        
        const confirmMessage = `Are you sure you want to register all students from:\n` +
                             `Branch: ${branch || 'All'}\n` +
                             `Course: ${course || 'All'}\n` +
                             `Batch: ${batch}\n\n` +
                             `This may take a few moments for large batches.`;
        
        if (!confirm(confirmMessage)) {
          e.preventDefault();
          return false;
        }
        
        // Show enhanced loader with progress message
        showLoader('Initializing bulk registration process...');
        
        // Allow form to submit
        return true;
      });
    }
    
    // Enhanced individual registration form handler
    const singleForm = document.querySelector('form[action*="register"]');
    if (singleForm && !singleForm.querySelector('input[name="register_bulk"]')) {
      singleForm.addEventListener('submit', function(e) {
        const studentId = document.querySelector('input[name="student_id"]')?.value.trim();
        
        if (!studentId) {
          alert('Please enter a Student ID.');
          e.preventDefault();
          return false;
        }
        
        showLoader('Registering student ' + studentId + '...');
        return true;
      });
    }
  });
</script>
<?php
  } // End of show_interface check
?>
</body>

</html>
<!-- # ******************************************************************************
# Program: Registering the student to meeting or importing the students to the particular meeting id
# Author: NifTycoon Company
# Copyright © [2023] NifTycoon Company. All rights reserved.
#
# Description: In this program admin can see the import the students for the particular meeting.
#
# This program is the property of NifTycoon Company and is protected by copyright laws.
# Unauthorized reproduction or distribution of this program, or any portion of it,
# may result in severe civil and criminal penalties, and will be prosecuted to the
# maximum extent possible under the law.
#
# NifTycoon Company reserves the right to modify this program as needed.
#
# ****************************************************************************** -->
<!doctype html>
<html lang="en">

<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>Zoom Student Registration</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.0/font/bootstrap-icons.css">
  <script src="../../../common/js/ajax.js"></script>
  <script src="../../../common/js/common.js"></script>
</head>

<body>
  <?php
  session_start();
  include '../headers/header.php';
  include('../common/php/niftycoon_functions.php');
  include('../db/dbconn.php');
  require '../admin/includes/config.php';
  require '../admin/includes/zoom_api.php';

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
    // echo '<pre>' . htmlspecialchars(json_encode($zoomDetails, JSON_PRETTY_PRINT)) . '</pre>';

    // This is used to dispaly zoom in IFRAME below link is dummy use the proper link
    // echo embedZoomMeeting('https://us06web.zoom.us/w/81041662397?tk=5XiA6bYGuYWi0QUaWgdxPJmc_SQl_dSemMavPLcmm0I.DQgAAAAS3nWhvRY3UTVPZHhUaFJmeUlGaTdCbTBHRnBBAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAA&pwd=Jawo8AkOw5d6cpUYXi6XBpZ0Gaaabu.1');
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

  // Handle new registration
  if (isset($_POST['register'])) {
    if (prevent_double_submit(1)) {
      $studentId = isset($_POST['student_id']) ? trim($_POST['student_id']) : '';
      $is_exist = NifTycoon_Select_Data("student_details", "student_id = '$studentId' and status = 1", "", "id", "desc", 1, $conn)[0];

      if ($is_exist != null) {
        $course = $is_exist['course'];
        $branch = $is_exist['branch'];
        $course = $is_exist['course'];
        $batch = $is_exist['batch'];
        $name = $is_exist['student_name'];
      } else {
        alert_header("!!! Student Not Found !!!", "index?meeting_id=$meeting_id");
      }
      $is_exist_meet = NifTycoon_Get_Count('zoom', "meeting_id = '$meeting_id' and student_id = '$studentId'", $conn);
      if ($is_exist_meet == 0) {
        $link = registerStudent($meeting_id, $name, 'TTT', $studentId);
        if (str_starts_with($link, 'http')) {
          $insert_array = array(
            'student_id' => $studentId,
            'meeting_id' => $meeting_id,
            'branch' => $branch,
            'course' => $course,
            'batch' => $batch,
            'link' => $link,
            'updated_on' => get_date_time(),
            'updated_by' => 'NeedToUpdate',
          );

          $insert_status = NifTycoon_Insert_Data("zoom", $insert_array, $conn);

          if ($insert_status) {
            alert_header("Students Added Sucessfully", "index?meeting_id=$meeting_id");
          } else {
            alert_header("!! Please Contact Developer !!", "developer?error=ZoomApiSuccessButFailedToAddDb");
          }
        } else {
          alert_header("$link - !! Error Student Not Added !! May be Wrong Meeting ID or due to Repeated Try...! Please Try after 12 hr", "index?meeting_id=$meeting_id");
        }
      }
    }
  }

  if (isset($_POST['register_bulk'])) {
    if (prevent_double_submit(1)) {
      $branch = $_POST['branch'];
      $course = isset($_POST['course']) ? $_POST['course'] : '';
      $batch = isset($_POST['batch']) ? $_POST['batch'] : '';
      $insert_status = 1;
      if ($meeting_id != '') {
        if ($branch != '') {
          $where_cond = "branch = '$branch' and status = 1 and student_id != ''";
        } else {
          alert_header("No Branch Selected", "index?meeting_id=$meeting_id");
        }
        if ($course != '') {
          $where_cond = $where_cond . " and course = '$course'";
        }
        if ($batch != '') {
          $where_cond = $where_cond . " and batch = '$batch'";
        }

        $failed_links = '';
        $i = 0;

        $get_students = NifTycoon_Select_Data("student_details", $where_cond, "", "id", "desc", "", $conn);
        foreach ($get_students as $fetch_students) {
          $name = $fetch_students['student_name'];
          $studentId = $fetch_students['student_id'];
          $is_exist_meet = NifTycoon_Get_Count('zoom', "meeting_id = '$meeting_id' and student_id = '$studentId'", $conn);
          if ($is_exist_meet == 0) {
            $course = $fetch_students['course'];
            $batch = $fetch_students['batch'];
            $link = registerStudent($meeting_id, $name, 'TTT', $studentId);
            if (!str_starts_with($link, 'http')) {
              $i++;
              $failed_links = $failed_links . ' ' . $i . ') ' . $studentId;
            } else {
              $insert_array = array(
                'student_id' => $studentId,
                'meeting_id' => $meeting_id,
                'branch' => $branch,
                'course' => $course,
                'batch' => $batch,
                'link' => $link,
                'updated_on' => get_date_time(),
                'updated_by' => 'NeedToUpdate',
              );

              $insert_status = NifTycoon_Insert_Data("zoom", $insert_array, $conn);
            }
          }
        }

        if ($failed_links == '') {
          if ($insert_status) {
            alert_header("Students Added Sucessfully", "index?meeting_id=$meeting_id");
          } else {
            alert_header("!! Please Contact Developer !!", "developer?error=ZoomApiSuccessButFailedToAddDb");
          }
        } else {
          alert_header("$link - !! Warning !! Some Students Not Added - $failed_links", "index?meeting_id=$meeting_id");
        }
      } else {
        alert_header("!! Meeting Id Not Found !!", "index?meeting_id=$meeting_id");
      }
    }
  }

  // Prevent multiple subbmission
  $form_token = prevent_double_submit(0);

  // if($admin_access != 0){
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
      </div>
    </div>
    <?php
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
                <!-- Branch Dropdown -->
                <div class="mb-3">
                  <label for="branch" class="form-label">Select Branch</label>
                  <select name="branch" id="branch" class="form-select" required onchange="get_courses(this.value)">
                    <option value="">-- Select Branch --</option>
                    <?php
                    $get_branch = NifTycoon_Select_Data("branch_details", "", "", "id", "asc", "", $conn);
                    foreach ($get_branch as $fetch_branch) {
                      $branch = $fetch_branch['branch_code'];
                      echo "<option value='$branch'>$branch</option>";
                    }
                    ?>
                  </select>
                </div>

                <!-- Course Dropdown -->
                <div class="mb-3" id="select_courses"></div>

                <!-- Batch Dropdown -->
                <div class="mb-3" id="select_batchs"></div>

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
                  <button type="submit" class="btn btn-primary w-100" name="register" onclick="return confirmation('Are you sure..! Filled data is correct..?')">
                    <i class="bi bi-save"></i> Register Student
                  </button>
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

  function get_courses(course) {
    $.ajax({
      type: 'POST',
      url: 'fetch_course.php',
      data: {
        text: course
      },
      success: function(data) {
        $('#select_courses').html(data);
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

  function update_branch_link(branch) {
    window.location = "index?meeting_id=<?= $meeting_id ?>&branch_filter=" + branch;
  }

  function update_course_link(course) {
    window.location = "index?meeting_id=<?= $meeting_id ?>&branch_filter=<?= $branch_filter ?>&course_filter=" + course;
  }

  function update_batch_link(batch) {
    window.location = "index?meeting_id=<?= $meeting_id ?>&branch_filter=<?= $branch_filter ?>&course_filter=<?= $course_filter ?>&batch_filter=" + batch;
  }
</script>
<?php
  // }
?>
</body>

</html>
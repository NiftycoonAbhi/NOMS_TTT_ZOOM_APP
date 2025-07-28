<?php
include_once('../db/dbconn.php');
include_once('../common/php/niftycoon_functions.php');

if(isset($_POST['branch'])) {
    $branch = mysqli_real_escape_string($conn, $_POST['branch']);
    
    if ($branch != "") {
        // Get courses that have students in the selected branch (using branch code)
        $query = "SELECT DISTINCT course FROM student_details WHERE branch = '$branch' AND status = 1 ORDER BY course";
        $result = mysqli_query($conn, $query);
        
        if ($result && $result->num_rows > 0) {
        ?>
        <label for="course" class="form-label">Select Course</label>
        <select id="course" onchange="get_batches_by_course(this.value, '<?= htmlspecialchars($branch) ?>')"
          class="form-select" name="course" required>
          <option value="">-- Select Course --</option>
          <?php
          while ($row = mysqli_fetch_assoc($result)) {
              $course_code = $row['course'];
              ?>
              <option value="<?= htmlspecialchars($course_code) ?>"><?= htmlspecialchars($course_code) ?></option>
              <?php
          }
          ?>
        </select>
        <?php
        } else {
            echo '<div class="alert alert-warning">No courses found for the selected branch.</div>';
        }
    } else {
        echo '<div class="alert alert-info">Please select a branch first.</div>';
    }
} else {
    echo '<div class="alert alert-danger">Invalid request.</div>';
}
?>

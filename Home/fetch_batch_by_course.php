<?php
include_once('../db/dbconn.php');
include_once('../common/php/niftycoon_functions.php');

if(isset($_POST['course']) && isset($_POST['branch'])) {
    $course = mysqli_real_escape_string($conn, $_POST['course']);
    $branch = mysqli_real_escape_string($conn, $_POST['branch']);
    
    if ($course != "" && $branch != "") {
        // Get batches that have students in the selected course and branch
        $query = "SELECT DISTINCT batch FROM student_details WHERE course = '$course' AND branch = '$branch' AND status = 1 ORDER BY batch";
        $result = mysqli_query($conn, $query);
        
        if ($result && $result->num_rows > 0) {
        ?>
        <label for="batch" class="form-label">Select Batch</label>
        <select id="batch" class="form-select" name="batch" required>
          <option value="">-- Select Batch --</option>
          <?php
          while ($row = mysqli_fetch_assoc($result)) {
              $batch_code = $row['batch'];
              ?>
              <option value="<?= htmlspecialchars($batch_code) ?>"><?= htmlspecialchars($batch_code) ?></option>
              <?php
          }
          ?>
        </select>
        <?php
        } else {
            echo '<div class="alert alert-warning">No batches found for the selected course and branch.</div>';
        }
    } else {
        echo '<div class="alert alert-info">Please select both course and branch.</div>';
    }
} else {
    echo '<div class="alert alert-danger">Invalid request - missing course or branch.</div>';
}
?>

<?php
include_once('../db/dbconn.php');
include_once('../common/php/niftycoon_functions.php');
if(isset($_POST['text']))
{
$text=mysqli_real_escape_string($conn,$_POST['text']);
if ($text!="")
{
  $where_course = "branch = '$text' and course_status = 1";
  $get_courses = NifTycoon_Select_Data('courses',$where_course,'', 'id', 'desc', '', $conn)
  ?>
      <label for="course" class="form-label">Select Course</label>
      <select id="course" onchange="get_batches(this.value)"
        class="form-select"
        name="course">
        <option value="">All Courses</option>
        <?php
        foreach ($get_courses as $fetch_courses)
        {
         ?>
          <option value="<?php echo $fetch_courses['course_code'] ?>"><?php echo $fetch_courses['course_code'] ?></option>
      <?php
        }
        ?>
      </select>
  <?php
}
}

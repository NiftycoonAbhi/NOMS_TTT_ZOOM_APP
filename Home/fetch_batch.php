<?php
include('../db/dbconn.php');
include('../../../common/php/niftycoon_functions.php');
if(isset($_POST['text']))
{
$text=mysqli_real_escape_string($conn,$_POST['text']);
if ($text!="")
{
  $where_course = "course_code = '$text'";
  $get_batches = NifTycoon_Select_Data('batchs',$where_course,'', 'id', 'desc', '', $conn)
  ?>
      <label for="batch" class="form-label">Select Batch</label>
      <select
        class="form-select" id="batch"
        name="batch">
        <option value="">All Batches</option>
        <?php
        foreach ($get_batches as $fetch_batches)
        {
         ?>
          <option value="<?php echo $fetch_batches['batch_name'] ?>"><?php echo $fetch_batches['batch_name'] ?></option>
      <?php
        }
        ?>
      </select>
  <?php
}
}

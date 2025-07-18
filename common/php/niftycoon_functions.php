<?php
/* NifTycoon standard functions
* Copyright © [2023] NifTycoon Company. All rights reserved.
* Author: Sandeep 
* Last update on: 27/04/2025
*/


/* Standard function supports */
// 1. NifTycoon_Insert_Data
// 2. NifTycoon_Insert_Array_Data
// 3. NifTycoon_Update_Data
// 4. NifTycoon_Delete_Data
// 5. NifTycoon_Select_Data
// 6. NifTycoon_Select_Data_offset
// 7. NifTycoon_Select_Data_Join
// 8. NifTycoon_Select_Data_Join_offset
// 9. NifTycoon_Get_Count
// 10. NifTycoon_Get_Max_Num
// 11. Niftycoon_total_column
// 12. Niftycoon_total_column_join
// 13. Niftycoon_total_column_offset
// 14. NifTycoon_Select_OtherCondition
// 15. encryptData
// 16. decryptData
// 17. get_date_time
// 18. get_date
// 19. get_date_us
// 20. get_time
// 21. convert_date_indian_standard
// 22. alert_header
// 23. no_alert_header
// 24. alert_no_header
// 25. alert_reload
// 26. reload
// 27. formatAsRupees
// 28. NifTycoon_convert_amount_in_words_ind
// 29. unique_code
// 30. is_exist
// 31. test
// 32. prevent_double_submits
// 33. send_message_fast2sms
// 34. NifTycoon_copy_table_to_table

// Need to add
// 1. Niftycoon_total_column_join_offset 



// 1. Function to insert data (Insert Data in DB specfic table) use secured Bind method
// parameters ::

// $tableName : Name of the table

// $columnsData : Array of column name and data
// Ex:    $columnsData = array
// (
// 'col-1' => $col-1-data,
// 'col-2' => $col-2-data,
// Add more columns and data as needed
// );

// $mysqli : db connection

// return : $NT_IN_QUERY (status)

function NifTycoon_Insert_Data($tableName, $columnsData, $mysqli)
{

  // Construct placeholders for values
  $placeholders = rtrim(str_repeat('?, ', count($columnsData)), ', ');

  // Construct SQL statement
  $NT_IN = "INSERT INTO `$tableName` (`" . implode('`, `', array_keys($columnsData)) . "`) VALUES ($placeholders)";

  // Prepare the SQL statement
  $NT_IN_QUERY = $mysqli->prepare($NT_IN);

  // Check if the statement was prepared successfully
  if (!$NT_IN_QUERY) {
    die("Failed to prepare statement: " . $mysqli->error);
  }

  // Bind parameters dynamically
  $types = '';
  $bindParams = array();

  foreach ($columnsData as $key => $value) {
     // $types .= 's'; // Assuming all values are strings
    if (is_int($value)) {
    $types   .= 'i';              // integer
    }
    elseif (is_float($value)) {
    $types   .= 'd';              // double (float)
    }
    elseif (is_null($value)) {
    $types   .= 's';              // no direct NULL-type, send as string and let MySQL convert
    }
    else {
    $types   .= 's';              // default to string
    }
    $bindParams[] = &$columnsData[$key]; // Get reference of variable
  }

  array_unshift($bindParams, $types); // Add types as first element
  call_user_func_array(array($NT_IN_QUERY, 'bind_param'), $bindParams);

  // Execute the statement
  $NT_IN_QUERY->execute();

  // Check for errors
  if ($NT_IN_QUERY->errno) {
    echo "Error: " . $NT_IN_QUERY->error;
    // Handle error as needed
  }

  // Close the statement
  $NT_IN_QUERY->close();

  return $NT_IN_QUERY;
}


// 2. Function to insert data within Array (Same as above) only used when data is in array
// parameters ::

// $tableName : Name of the table

// $columnsData : Array of column name and data
// Ex:        The function should called number of times the data repeats ie count($main_var)
// $col-1-data_arraydata = count($col-1-data); here we assume $col-1-data as main variable which has max count of array
// We have to reassign data within for loop (before : $example_var_arraydata After : $example_var)
// for ($i = 0; $i < $$col-1-data_arraydata; $i++) {
// $col-1-data = $col-1-data_arraydata;
// $col-2-data = $col-1-data_arraydata[$i];

// $columnsData = array
// (
// 'col-1' => $col-1-data,
// 'col-2' => $col-2-data,
// Add more columns and data as needed
// );

// $mysqli : db connection

// return : $NT_IN_QUERY (status)

function NifTycoon_Insert_Array_Data($tableName, $columnsData, $mysqli)
{
  // Extract keys and values from the data array
  $columns = array_keys($columnsData);
  $placeholders = rtrim(str_repeat('?, ', count($columnsData)), ', ');

  // Construct SQL statement
  $NT_INARRAY = "INSERT INTO `$tableName` (`" . implode('`, `', $columns) . "`) VALUES ($placeholders)";

  // Prepare SQL statement
  $NT_INARRAY_QUERY = $mysqli->prepare($NT_INARRAY);

  // Check if the statement was prepared successfully
  if (!$NT_INARRAY_QUERY) {
    die("Failed to prepare statement: " . $mysqli->error);
  }

  // Bind parameters dynamically
  // $types = str_repeat('s', count($columnsData)); // Assuming all values are strings
  // $bindParams = array($types);

  $types = '';
  foreach ($columnsData as $value) {
    if (is_int($value)) {
        $types .= 'i'; // integer
    } elseif (is_float($value)) {
        $types .= 'd'; // double (float)
    } elseif (is_null($value)) {
        $types .= 's'; // send as string, MySQL will convert NULL if needed
    } else {
        $types .= 's'; // default to string
    }
  }

// Now prepare bind parameters array
$bindParams = array($types);

  foreach ($columnsData as &$value) { // Pass each parameter as reference
    $bindParams[] = &$value;
  }
  call_user_func_array(array($NT_INARRAY_QUERY, 'bind_param'), $bindParams);

  // Execute the statement
  $NT_INARRAY_QUERY->execute();

  // Check for errors
  if ($NT_INARRAY_QUERY->errno) {
    die("Error executing statement: " . $stmt->error);
  }

  // Close the statement
  $NT_INARRAY_QUERY->close();

  return $NT_INARRAY_QUERY;
}


// 3. Function to update data
// parameters ::

// $tableName : Name of the table

// $setColumnsData : Array of column name and data
// Ex:    $columnsData = array
// (
// 'col-1' => $col-1-data,
// 'col-2' => $col-2-data,
// Add more columns and data as needed
// );

// $whereColumnsData : where condition for update
// Ex :     $whereColumnsData = "col_name = 'data' AND col_name = 'data' OR ETC..";

// $mysqli : db connection
//
// return : $NT_UP_QUERY (status)

function NifTycoon_Update_Data($tableName, $setColumnsData, $whereColumnsData, $mysqli)
{
  // Construct SET clause
  $setClause = implode('=?, ', array_keys($setColumnsData)) . '=?';

  // Prepare SQL statement
  $NT_UP = "UPDATE `$tableName` SET $setClause WHERE $whereColumnsData";
  $NT_UP_QUERY = $mysqli->prepare($NT_UP);

  // Check if the statement was prepared successfully
  if (!$NT_UP_QUERY) {
    die("Failed to prepare statement: " . $mysqli->error);
  }

  // Bind parameters dynamically
  $types = '';
  $bindParams = array();

  foreach ($setColumnsData as $key => $value) {
     // $types .= 's'; // Assuming all values are strings
    if (is_int($value)) {
        $types   .= 'i';              // integer
    }
    elseif (is_float($value)) {
        $types   .= 'd';              // double (float)
    }
    elseif (is_null($value)) {
        $types   .= 's';              // no direct NULL-type, send as string and let MySQL convert
    }
    else {
        $types   .= 's';              // default to string
    }
    $bindParams[] = &$setColumnsData[$key]; // Get reference of variable
  }

  array_unshift($bindParams, $types); // Add types as first element
  call_user_func_array(array($NT_UP_QUERY, 'bind_param'), $bindParams);

  // Execute the statement
  $NT_UP_QUERY->execute();

  // Check for errors
  if ($NT_UP_QUERY->errno) {
    throw new Exception("Error executing statement: " . $NT_UP_QUERY->error);
  }

  return $NT_UP_QUERY;
}


// 4. Function to Delete data
// parameters ::

// $tableName : Name of the table

// $whereColumnsData : where condition for delete
// Ex :     $whereColumnsData = "col_name = 'data' AND col_name = 'data' OR ETC..";

// $mysqli : db connection
//
// Return : $NT_DL_QUERY (status)

function NifTycoon_Delete_Data($tableName, $whereColumnsData, $mysqli)
{

  // Prepare SQL statement
  $NT_DL = "DELETE FROM `$tableName` WHERE $whereColumnsData";
  $NT_DL_QUERY = $mysqli->prepare($NT_DL);

  // Check if the statement was prepared successfully
  if (!$NT_DL_QUERY) {
    die("Failed to prepare statement: " . $mysqli->error);
  }

  // Execute the statement
  $NT_DL_QUERY->execute();

  // Check for errors
  if ($NT_DL_QUERY->errno) {
    die("Error executing statement: " . $NT_DL_QUERY->error);
  }

  return $NT_DL_QUERY;
}


// 5. Function to select data ( * offset is not supported )
// parameters ::

// $tableName : Name of the table

// $whereColumnsData : where condition for delete
// Ex :     $whereColumnsData = "col_name = 'data' AND col_name = 'data' OR ETC..";

// $group_by : group by data (if nothing pass '')

// $orderby : column name to order (used with $orderByColumn)

// $orderByColumn : desc or asc (by default asc will be applied)

// $limit : limit of data to be fetched (pass 0 if no limit to apply)

// $mysqli : db connection
//
// Return:  $Result_Select : fetched data array
// Ex:      $resultSet = NifTycoon_Select_Data('test_details',$columnsData,$conn);
// if multiple rows fetch:
// foreach ($resultSet as $result)
//  {
//      echo $result['col_name'];
//  }

// if only one row fetch:
// elseif(is_array($result)){
// echo $result['col_name'];
// }

function NifTycoon_Select_Data($tableName, $whereColumnsData, $group_by, $orderby = null, $orderByColumn = null, $limit = null, $mysqli)
{

  // Construct SQL statement
  if ($whereColumnsData != '') {
    $NT_SEL = "SELECT * FROM `$tableName` WHERE $whereColumnsData ";
  } else {
    $NT_SEL = "SELECT * FROM `$tableName` ";
  }

  // Add GROUP BY clause if specified
  if ($group_by != '') {
    $NT_SEL .= " GROUP BY $group_by";
  }

  // Add ORDER BY clause if specified
  if ($orderByColumn && $orderby) {
    $NT_SEL .= " ORDER BY $orderby $orderByColumn";
  }

  // Add LIMIT clause if specified
  if ($limit >= 1) {
    $NT_SEL .= " LIMIT $limit";
  }

  $NT_SEL_QUERY = $mysqli->prepare($NT_SEL);

  // Check if the statement was prepared successfully
  if (!$NT_SEL_QUERY) {
    die("Failed to prepare statement: " . $mysqli->error);
  }

  // Bind result variables
  $result = array();
  $meta = $NT_SEL_QUERY->result_metadata();
  while ($field = $meta->fetch_field()) {
    $result[$field->name] = null;
    $bindParams[] = &$result[$field->name];
  }
  call_user_func_array(array($NT_SEL_QUERY, 'bind_result'), $bindParams);

  // Execute the statement
  $NT_SEL_QUERY->execute();

  // Fetch the results


  if ($NT_SEL_QUERY->num_rows == 1) {
    $Result_Select =  $NT_SEL_QUERY->fetch();
  } else {
    $Result_Select = array();
    // Fetch all rows if there are multiple rows
    while ($NT_SEL_QUERY->fetch()) {

      $row = array();
      foreach ($result as $key => $value) {
        $row[$key] = $value;
      }
      $Result_Select[] = $row;
    }
  }

  // Close the statement
  $NT_SEL_QUERY->close();

  return $Result_Select;
}


// 6. Function to join tables and get data from table where one column will be common ( offset is not supported)

// parameters ::

// $fetch_table : Name of the table from whih data to be fetched

// $join_table : Refrence table

// $common_column_fetch_table : Common column between both tables in fetch table

// $common_column_join_table : Common column between both tables in join table

// $where_cond : where condition for delete
// Ex :     $whereColumnsData = "ft.col_name = 'data' AND jt.col_name = 'data' OR ETC..";

// $group_by : group by data (if nothing pass '')

// $orderby : column name to order (used with $orderByColumn)

// $orderByColumn : desc or asc (by default asc will be applied)

// $limit : limit of data to be fetched (pass 0 if no limit to apply)

// $mysqli : db connection
//
// Return:  $Result_Select : fetched data array
// Ex:      $resultSet = NifTycoon_Select_Data('test_details',$columnsData,$conn);
// if multiple rows fetch:
// foreach ($resultSet as $result)
//  {
//      echo $result['col_name'];
//  }

// if only one row fetch:
// elseif(is_array($result)){
// echo $result['col_name'];
// }


function NifTycoon_Select_Data_Join($fetch_table, $join_table, $common_column_fetch_table, $common_column_join_table, $where_cond, $group_by, $orderby = null, $orderByColumn = null, $limit = null, $mysqli)
{

  // Construct SQL statement
  if ($where_cond != '') {
    $NT_SEL = "SELECT ft.* FROM `$fetch_table` ft INNER JOIN `$join_table` jt on ft.$common_column_fetch_table = jt.$common_column_join_table
  WHERE $where_cond";
  } else {
    $NT_SEL = "SELECT ft.* FROM `$fetch_table` ft INNER JOIN `$join_table` jt on ft.$common_column_fetch_table = jt.$common_column_join_table ";
  }

  // Add GROUP BY clause if specified
  if ($group_by != '') {
    $NT_SEL .= " GROUP BY $group_by";
  }

  // Add ORDER BY clause if specified
  if ($orderByColumn && $orderby) {
    $NT_SEL .= " ORDER BY $orderby $orderByColumn";
  }

  // Add LIMIT clause if specified
  if ($limit >= 1) {
    $NT_SEL .= " LIMIT $limit";
  }

  $NT_SEL_QUERY = $mysqli->prepare($NT_SEL);

  // Check if the statement was prepared successfully
  if (!$NT_SEL_QUERY) {
    die("Failed to prepare statement: " . $mysqli->error);
  }

  // Bind result variables
  $result = array();
  $meta = $NT_SEL_QUERY->result_metadata();
  while ($field = $meta->fetch_field()) {
    $result[$field->name] = null;
    $bindParams[] = &$result[$field->name];
  }
  call_user_func_array(array($NT_SEL_QUERY, 'bind_result'), $bindParams);

  // Execute the statement
  $NT_SEL_QUERY->execute();

  // Fetch the results


  if ($NT_SEL_QUERY->num_rows == 1) {
    $Result_Select =  $NT_SEL_QUERY->fetch();
  } else {
    $Result_Select = array();
    // Fetch all rows if there are multiple rows
    while ($NT_SEL_QUERY->fetch()) {

      $row = array();
      foreach ($result as $key => $value) {
        $row[$key] = $value;
      }
      $Result_Select[] = $row;
    }
  }

  // Close the statement
  $NT_SEL_QUERY->close();

  return $Result_Select;
}


// 7. Function to select data same as NifTycoon_Select_Data() with added offset as parameter

function NifTycoon_Select_Data_offset($tableName, $whereColumnsData, $group_by, $orderby = null, $orderByColumn = null, $limit = null, $offset = 0, $mysqli)
{

  // Construct SQL statement
  if ($whereColumnsData != '') {
    $NT_SEL = "SELECT * FROM `$tableName` WHERE $whereColumnsData ";
  } else {
    $NT_SEL = "SELECT * FROM `$tableName` ";
  }

  // Add GROUP BY clause if specified
  if ($group_by != '') {
    $NT_SEL .= " GROUP BY $group_by";
  }

  // Add ORDER BY clause if specified
  if ($orderByColumn && $orderby) {
    $NT_SEL .= " ORDER BY $orderby $orderByColumn";
  }

  // Add LIMIT clause if specified
  if ($limit >= 1) {
    $NT_SEL .= " LIMIT $limit";
  }

  if ($offset != '') {
    $NT_SEL .= " OFFSET $offset";
  }


  $NT_SEL_QUERY = $mysqli->prepare($NT_SEL);

  // Check if the statement was prepared successfully
  if (!$NT_SEL_QUERY) {
    die("Failed to prepare statement: " . $mysqli->error);
  }

  // Bind result variables
  $result = array();
  $meta = $NT_SEL_QUERY->result_metadata();
  while ($field = $meta->fetch_field()) {
    $result[$field->name] = null;
    $bindParams[] = &$result[$field->name];
  }
  call_user_func_array(array($NT_SEL_QUERY, 'bind_result'), $bindParams);

  // Execute the statement
  $NT_SEL_QUERY->execute();

  // Fetch the results


  if ($NT_SEL_QUERY->num_rows == 1) {
    $Result_Select =  $NT_SEL_QUERY->fetch();
  } else {
    $Result_Select = array();
    // Fetch all rows if there are multiple rows
    while ($NT_SEL_QUERY->fetch()) {

      $row = array();
      foreach ($result as $key => $value) {
        $row[$key] = $value;
      }
      $Result_Select[] = $row;
    }
  }

  // Close the statement
  $NT_SEL_QUERY->close();

  return $Result_Select;
}


// 8. Function to join tables with OFFSET and get data from table with where one column will be common
// Same as NifTycoon_Select_Data_Join() with added offset as parameter

function NifTycoon_Select_Data_Join_offset($fetch_table, $join_table, $common_column_fetch_table, $common_column_join_table, $where_cond, $group_by, $orderby = null, $orderByColumn = null, $limit = null, $offset = 0,  $mysqli)
{

  // Construct SQL statement
  if ($where_cond != '') {
    $NT_SEL = "SELECT ft.* FROM `$fetch_table` ft INNER JOIN `$join_table` jt on ft.$common_column_fetch_table = jt.$common_column_join_table
   WHERE $where_cond";
  } else {
    $NT_SEL = "SELECT ft.* FROM `$fetch_table` ft INNER JOIN `$join_table` jt on ft.$common_column_fetch_table = jt.$common_column_join_table ";
  }

  // Add GROUP BY clause if specified
  if ($group_by != '') {
    $NT_SEL .= " GROUP BY $group_by";
  }

  // Add ORDER BY clause if specified
  if ($orderByColumn && $orderby) {
    $NT_SEL .= " ORDER BY $orderby $orderByColumn";
  }

  // Add LIMIT clause if specified
  if ($limit >= 1) {
    $NT_SEL .= " LIMIT $limit";
  }

  if ($offset != '') {
    $NT_SEL .= " OFFSET $offset";
  }

  $NT_SEL_QUERY = $mysqli->prepare($NT_SEL);

  // Check if the statement was prepared successfully
  if (!$NT_SEL_QUERY) {
    die("Failed to prepare statement: " . $mysqli->error);
  }

  // Bind result variables
  $result = array();
  $meta = $NT_SEL_QUERY->result_metadata();
  while ($field = $meta->fetch_field()) {
    $result[$field->name] = null;
    $bindParams[] = &$result[$field->name];
  }
  call_user_func_array(array($NT_SEL_QUERY, 'bind_result'), $bindParams);

  // Execute the statement
  $NT_SEL_QUERY->execute();

  // Fetch the results


  if ($NT_SEL_QUERY->num_rows == 1) {
    $Result_Select =  $NT_SEL_QUERY->fetch();
  } else {
    $Result_Select = array();
    // Fetch all rows if there are multiple rows
    while ($NT_SEL_QUERY->fetch()) {

      $row = array();
      foreach ($result as $key => $value) {
        $row[$key] = $value;
      }
      $Result_Select[] = $row;
    }
  }

  // Close the statement
  $NT_SEL_QUERY->close();

  return $Result_Select;
}


// 9. Function to get count in table by where condition
// parameters ::

// $tableName : Name of the table

// $whereColumnsData : where condition for get max number
// Ex :     $whereColumnsData = "col_name = 'data' AND col_name = 'data' OR ETC..";

// $mysqli : db connection

// return : $count (num rows)

function NifTycoon_Get_Count($tableName, $whereColumnsData, $mysqli)
{

  // Construct SQL statement
  $NT_COUNT = "SELECT COUNT(*) AS row_count FROM `$tableName` WHERE $whereColumnsData";

  $NT_COUNT_QUERY = $mysqli->prepare($NT_COUNT);

  // Check if the statement was prepared successfully
  if (!$NT_COUNT_QUERY) {
    die("Failed to prepare statement: " . $mysqli->error);
  }

  // Execute the statement
  $NT_COUNT_QUERY->execute();

  $NT_COUNT_QUERY->bind_result($count);

  // Fetch the result
  $NT_COUNT_QUERY->fetch();

  // Close the statement
  $NT_COUNT_QUERY->close();

  // Return the maximum count of id
  return $count;
}


// 10. Function to get max number in column where condition
// parameters ::

// $tableName : Name of the table

// $col_name : Name of column

// $whereColumnsData : where condition for get max number
// Ex :     $whereColumnsData = "col_name = 'data' AND col_name = 'data' OR ETC..";

// $mysqli : db connection

// return : $maxNum (max num)

function NifTycoon_Get_Max_Num($tableName, $col_name, $whereColumnsData, $mysqli)
{

  // Construct SQL statement
  $NT_MAX_NUM = "SELECT MAX($col_name) AS max_value FROM `$tableName` WHERE $whereColumnsData";

  $NT_MAX_NUM_QUERY = $mysqli->prepare($NT_MAX_NUM);

  // Check if the statement was prepared successfully
  if (!$NT_MAX_NUM_QUERY) {
    die("Failed to prepare statement: " . $mysqli->error);
  }

  // Execute the statement
  $NT_MAX_NUM_QUERY->execute();

  $NT_MAX_NUM_QUERY->bind_result($maxNum);

  // Fetch the result
  $NT_MAX_NUM_QUERY->fetch();

  // Close the statement
  $NT_MAX_NUM_QUERY->close();

  // Return the maximum count of id
  return $maxNum;
}


// 11. Function to get Total of the column with no offset

// parameters ::

// $tableName : Name of the table

// $col_name : Name of column

// $whereColumnsData : where condition for get max number
// Ex :     $whereColumnsData = "col_name = 'data' AND col_name = 'data' OR ETC..";

// $mysqli : db connection

// return : $total_column (Total sum of column)

function Niftycoon_total_column($tableName, $column_name, $whereColumnsData, $mysqli, $offset = 0)
{
  $get_total_column = "SELECT SUM($column_name) AS total_column FROM $tableName WHERE $whereColumnsData ORDER BY id LIMIT 10000 OFFSET $offset";

  $get_total_column_QUERY = $mysqli->prepare($get_total_column);

  // Check if the statement was prepared successfully
  if (!$get_total_column_QUERY) {
    die("Failed to prepare statement: " . $mysqli->error);
  }

  // Execute the statement
  $get_total_column_QUERY->execute();

  $get_total_column_QUERY->bind_result($total_column);

  // Fetch the result
  $get_total_column_QUERY->fetch();

  // Close the statement
  $get_total_column_QUERY->close();

  // Return the maximum count of id
  return $total_column;
}


// 12. Function to get total of column with join

// parameters ::

// $fetch_table : Name of the table from which total of colum need to fetch

// $join_table : Refrence table

// $total_column_name : Column name to get total sum

// $common_column_fetch_table : Common column between both tables in fetch table

// $common_column_join_table : Common column between both tables in join table 

// $whereColumnsData : where condition for get max number
// Ex :     $whereColumnsData = "jt.col_name = 'data' AND ft.col_name = 'data' OR ETC..";

// $mysqli : db connection

// return : $total_column (Total sum of column)

function Niftycoon_total_column_join($fetch_table, $join_table, $total_column_name, $common_column_fetch_table, $common_column_join_table, $where_cond, $mysqli)
{
  $get_total_column = "SELECT SUM(ft.$total_column_name) AS total_column FROM `$fetch_table` ft INNER JOIN `$join_table` jt on ft.$common_column_fetch_table = jt.$common_column_join_table WHERE $where_cond";

  $get_total_column_QUERY = $mysqli->prepare($get_total_column);

  // Check if the statement was prepared successfully
  if (!$get_total_column_QUERY) {
    die("Failed to prepare statement: " . $mysqli->error);
  }

  // Execute the statement
  if (!$get_total_column_QUERY->execute()) {
    die("Execution failed: " . $get_total_column_QUERY->error);
  }

  // Bind the result
  $get_total_column_QUERY->bind_result($total_column);

  // Fetch the result
  $get_total_column_QUERY->fetch();

  // Close the statement
  $get_total_column_QUERY->close();

  // Return the total amount
  return $total_column ?? 0; // Default to 0 if NULL

}


// 13. Function to get Total of the column with offset
// Same as Niftycoon_total_column with added offset as parmeter

function Niftycoon_total_column_offset($tableName, $column_name, $whereColumnsData, $mysqli, $offset)
{
  $get_total_column = "
SELECT COALESCE(SUM($column_name), 0) AS total_amount
FROM (
    SELECT 
        @row_num := @row_num + 1 AS row_num, 
        $column_name
    FROM $tableName, (SELECT @row_num := 0) r
    WHERE $whereColumnsData
    ORDER BY id
) ranked_data
WHERE row_num > $offset";

  $get_total_column_QUERY = $mysqli->prepare($get_total_column);

  // Check if the statement was prepared successfully
  if (!$get_total_column_QUERY) {
    die("Failed to prepare statement: " . $mysqli->error);
  }

  // Execute the statement
  if (!$get_total_column_QUERY->execute()) {
    die("Execution failed: " . $get_total_column_QUERY->error);
  }

  // Bind the result
  $get_total_column_QUERY->bind_result($total_column);

  // Fetch the result
  $get_total_column_QUERY->fetch();

  // Close the statement
  $get_total_column_QUERY->close();

  // Return the total amount
  return $total_column ?? 0; // Default to 0 if NULL

}


// 14. Function to select data for Other conditions
// parameters ::

// $fullQuery : Query need to be execute

// $mysqli : db connection
//
// Return:  $Result_Select : fetched data array
// Ex:      $resultSet = NifTycoon_Select_OtherCondition($Query,$conn);
// if multiple rows fetch:
// foreach ($resultSet as $result)
//  {
//      echo $result['col_name'];
//  }

// if only one row fetch:
// elseif(is_array($result)){
// echo $result['col_name'][0];
// }

function NifTycoon_Select_OtherCondition($fullQuery, $mysqli)
{
  $NT_SEL_QUERY_OTH = $mysqli->prepare($fullQuery);

  // Check if the statement was prepared successfully
  if (!$NT_SEL_QUERY_OTH) {
    die("Failed to prepare statement: " . $mysqli->error);
  }

  // Bind result variables
  $result = array();
  $meta = $NT_SEL_QUERY_OTH->result_metadata();
  while ($field = $meta->fetch_field()) {
    $result[$field->name] = null;
    $bindParams[] = &$result[$field->name];
  }
  call_user_func_array(array($NT_SEL_QUERY_OTH, 'bind_result'), $bindParams);

  // Execute the statement
  $NT_SEL_QUERY_OTH->execute();

  // Fetch the results


  if ($NT_SEL_QUERY_OTH->num_rows == 1) {
    $Result_Select =  $NT_SEL_QUERY_OTH->fetch();
  } else {
    $Result_Select = array();
    // Fetch all rows if there are multiple rows
    while ($NT_SEL_QUERY_OTH->fetch()) {

      $row = array();
      foreach ($result as $key => $value) {
        $row[$key] = $value;
      }
      $Result_Select[] = $row;
    }
  }

  // Close the statement
  $NT_SEL_QUERY_OTH->close();

  return $Result_Select;
}


// 15. Function to enctype data
// uses openssl_encrypt and base64_encode with 2 keys
// params ::
// $data : data which need to be encrypted
// $key : Primary key for encryption

// return : encrypted data

function encryptData($data, $key)
{
  $iv = "1002301331032001";
  $encryptedData = openssl_encrypt($data, 'aes-256-cbc', $key, 0, $iv);
  return base64_encode($encryptedData);
}


// 16. Function to decrypt data
// uses base64_decode and openssl_decrypt with 2 keys
// params ::
// $data : data which need to be decrypted
// $key : Primary key for decryption

// return : decrypted data

function decryptData($encryptedData, $key)
{
  $iv = "1002301331032001";
  $decodedData = base64_decode($encryptedData);
  return openssl_decrypt($decodedData, 'aes-256-cbc', $key, 0, $iv);
}


// 17. To get current time and date
// return : DD/MM/YY HH:II:SS

function get_date_time()
{
  date_default_timezone_set('Asia/Kolkata'); // Set the timezone to IST
  return date('d/m/Y H:i:s'); // Format the date and time
}


// 18. To get current date (Indian Standard)
// return : DD/MM/YY

function get_date()
{
  date_default_timezone_set('Asia/Kolkata'); // Set the timezone to IST
  return date('d/m/Y'); // Format the date and time
}


// 19. To get Time
// return : HH:II:SS

function get_time()
{
  date_default_timezone_set('Asia/Kolkata'); // Set the timezone to IST
  return date('H:i:s'); // Format the date and time
}


// 20. To get current date (US standard easy to handle in db)
// return : YY-MM-DD

function get_date_us()
{
  date_default_timezone_set('Asia/Kolkata'); // Set the timezone to IST
  return date('Y-m-d'); // Format the date and time
}


// 21. To get current time and date
// Some time dates are taken input from user (input type = "data") need to convert in indian standard

// Params : $us_date

//return : DD/MM/YY

function convert_date_indian_standard($us_date)
{
  // Create a DateTime object from the input date
  if ($us_date != '') {
    $in_date = DateTime::createFromFormat('Y-m-d', $us_date);
    return $in_date->format('d/m/y');
  } else {
    return '';
  }
}


// 22. Redirect to page with specific alert

function alert_header($alert_msg, $location)
{
  echo "<script>alert('$alert_msg');window.location='$location';</script>";
  exit();
}


// 23. Redirect to page with no alert

function no_alert_header($location)
{
  echo "<script>window.location='$location';</script>";
  exit();
}


//24. Alert msg with no Redirect

function alert_no_header($alert_msg)
{
  echo "<script>alert('$alert_msg');</script>";
}


// 25. Reload page with alert message

function alert_reload($alert_msg)
{
  echo "<script>alert('$alert_msg'); window.location.reload();</script>";
}


// 26. Reload page with no alert msg

function reload()
{
  echo "<script> window.location.reload();</script>";
}


// 27. This function return in format as amount for ex: 2000 is returned as 2000.00/-
// Mainly used for display purpose like master pages and print pages
// Params : $number

//return : Standard amount formet withh added paisa and /- at end

function formatAsRupees($number)
{
  $suffix = "/-";
  $decimals = 2;
  $number = floatval($number);
  $number = round($number, $decimals);
  $integerPart = floor($number);
  $fractionalPart = $number - $integerPart;
  $integerPartStr = strval($integerPart);
  $length = strlen($integerPartStr);
  if ($length > 3) {
    $lastThree = substr($integerPartStr, -3);
    $restUnits = substr($integerPartStr, 0, -3);
    $restUnits = preg_replace('/\B(?=(\d{2})+(?!\d))/', ',', $restUnits);
    $formattedIntegerPart = $restUnits . ',' . $lastThree;
  } else {
    $formattedIntegerPart = $integerPartStr;
  }
  $formattedFractionalPart = number_format($fractionalPart, $decimals, '.', '');
  $formattedFractionalPart = substr($formattedFractionalPart, 1); // Remove "0."
  $formattedNumber = $formattedIntegerPart . $formattedFractionalPart;
  return $formattedNumber . $suffix;
}


// 28. This function convert the amount in number to amount in words including paisa
// Range is from 0.00 to 99 corers

// Params :: $number // number need to convert in word

// return : Converted amount string in word

function NifTycoon_convert_amount_in_words_ind($number)
{
  $words = array(
    0 => 'Zero',
    1 => 'One',
    2 => 'Two',
    3 => 'Three',
    4 => 'Four',
    5 => 'Five',
    6 => 'Six',
    7 => 'Seven',
    8 => 'Eight',
    9 => 'Nine',
    10 => 'Ten',
    11 => 'Eleven',
    12 => 'Twelve',
    13 => 'Thirteen',
    14 => 'Fourteen',
    15 => 'Fifteen',
    16 => 'Sixteen',
    17 => 'Seventeen',
    18 => 'Eighteen',
    19 => 'Nineteen'
  );

  $tens = array(
    2 => 'Twenty',
    3 => 'Thirty',
    4 => 'Forty',
    5 => 'Fifty',
    6 => 'Sixty',
    7 => 'Seventy',
    8 => 'Eighty',
    9 => 'Ninety'
  );
  $paisa = round(fmod($number, 1) * 100);
  $output = '';
  $crores = floor($number / 10000000);
  $number = $number % 10000000;

  $lakhs = floor($number / 100000);
  $number = $number % 100000;

  $thousands = floor($number / 1000);
  $number = $number % 1000;

  $hundreds = floor($number / 100);
  $number = $number % 100;

  $tensPart = floor($number / 10);
  $onesPart = $number % 10;

  if ($crores > 0) {
    $output .= processNumber($crores, $words, $tens) . ' Crore ';
  }

  if ($lakhs > 0) {
    $output .= processNumber($lakhs, $words, $tens) . ' Lakh ';
  }

  if ($thousands > 0) {
    $output .= processNumber($thousands, $words, $tens) . ' Thousand ';
  }

  if ($hundreds > 0) {
    $output .= $words[$hundreds] . ' Hundred ';
  }

  if ($tensPart > 1) {
    $output .= $tens[$tensPart] . ' ';
    if ($onesPart > 0) {
      $output .= $words[$onesPart];
    }
  } elseif ($tensPart == 1) {
    $output .= $words[$tensPart * 10 + $onesPart];
  } elseif ($onesPart > 0) {
    $output .= $words[$onesPart];
  }

  $output .= ' Rupees';

  if ($paisa > 0) {
    $output .= ' and ' . processNumber($paisa, $words, $tens) . ' Paise';
  }

  return ucfirst($output);
}

// This function will called by NifTycoon_convert_amount_in_words_ind()
function processNumber($num, $words, $tens)
{
  if ($num < 20) {
    return $words[$num];
  } else {
    return $tens[floor($num / 10)] . ($num % 10 > 0 ? ' ' . $words[$num % 10] : '');
  }
}


// 29. function returns unique code
// This function used to get a unique code which add 6 digit random number in front of ID 
// of given tables id.

// Params :: $tableName // table name for which code need to genarate
// $mysqli :  DB conn

// return : Unique code 

function unique_code($tableName, $mysqli)
{
  $NT_MAX_id_NUM = "SELECT MAX(id) AS max_id_value FROM `$tableName`";

  $NT_MAX_Id_QUERY = $mysqli->prepare($NT_MAX_id_NUM);

  // Check if the statement was prepared successfully
  if (!$NT_MAX_Id_QUERY) {
    die("Failed to prepare statement: " . $mysqli->error);
  }

  // Execute the statement
  $NT_MAX_Id_QUERY->execute();

  $NT_MAX_Id_QUERY->bind_result($maxID);

  // Fetch the result
  $NT_MAX_Id_QUERY->fetch();

  // Close the statement
  $NT_MAX_Id_QUERY->close();

  // Return the maximum count of id
  $maxID = $maxID + 1;
  return $maxID . rand(0000, 9999);
}


// 30. Function checks if data is exist in given table

// Params : $table_name // Table name for which data need to be searched
//          $where_cond // Where condition 
//          $conn // DB connection

// return : TRUE if exist else FALSE

function is_exist($table_name, $where_cond, $conn)
{
  $is_exist = NifTycoon_Get_Count($table_name, $where_cond, $conn);
  if ($is_exist > 0) {
    return TRUE;
  } else {
    return FALSE;
  }
}


// 31. Debug function works as a break point in code
function test()
{
  $alert_msg = "Yes code is here";
  echo "<script>alert('$alert_msg');</script>";
}


// 32. Function to prevent multiple submissions of form 
// IMP: This Function is mandatory in user side form submissions

// Params : $place // There are two places
// place1 Top where we set session
// place2 Within POST or GET where we verify session token

// return : TRUE if valid else FALSE

// Usage documentation part need to create

function prevent_double_submit($place)
{
  //place = 0 (after function)  1 (function);
  $token_name = 'form_token';
  if ($place == 0) {
    return $_SESSION[$token_name] = bin2hex(random_bytes(16));
  } else {
    if (
      isset($_POST[$token_name]) &&
      $_POST[$token_name] === $_SESSION[$token_name]
    ) {
      // Valid submission — regenerate token to prevent resubmission
      $_SESSION[$token_name] = bin2hex(random_bytes(16));
      return TRUE; // allow processing
    } else {
      return FALSE; // duplicate or invalid submission
    }
  }
}


// 33. Function to send sms using FAST2SMS with verified DLT templete

// Params: $template_id // verified templete ID from FAST2SMS
// $phone_numbers // one or array of phone numbers
// $val_array  // array of values in templete
// $apiKey // FAST2SMS API Key
// $sender_id // FAST2SMS sender ID

// return : TRUE if message sent else FALSE

function send_message_fast2sms($template_id, $phone_numbers, $val_array, $apiKey, $sender_id)
{

  // Fast2SMS configurations setup
  // $template_id must match your DLT-approved template.
  // variables_values must match the number of variables your DLT template expects.

  $route = "dlt"; // Since it's DLT route
  $language = "english"; // Assuming English

  // Implode variables_values for API
  $variables_values = implode('|', $val_array);

  // API Endpoint
  $url = "https://www.fast2sms.com/dev/bulkV2";

  // Handle single or multiple phone numbers
  if (is_array($phone_numbers)) {
    $numbers = implode(',', $phone_numbers); // convert array to comma-separated string
  } else {
    $numbers = $phone_numbers; // already a single number
  }

  // Prepare Data
  $data = [
    "sender_id" => $sender_id,
    "message" => $template_id, // Note: Fast2SMS expects DLT Template ID or registered text
    "variables_values" => $variables_values,
    "route" => $route,
    "numbers" => $numbers
  ];

  // Initialize cURL
  $curl = curl_init();
  curl_setopt_array($curl, [
    CURLOPT_URL => $url,
    CURLOPT_RETURNTRANSFER => true,
    CURLOPT_CUSTOMREQUEST => "POST",
    CURLOPT_POSTFIELDS => json_encode($data),
    CURLOPT_HTTPHEADER => [
      "authorization: $apiKey",
      "accept: */*",
      "cache-control: no-cache",
      "content-type: application/json"
    ],
  ]);

  $response = curl_exec($curl);
  $err = curl_error($curl);
  curl_close($curl);

  // Debug Log (optional: store to database using $conn if needed)

  if ($err) {
    // If cURL error occurs
    return false;
  } else {
    // Check Fast2SMS Response
    $responseData = json_decode($response, true);
    if (isset($responseData['return']) && $responseData['return'] == true) {
      return true;
    } else {
      return false;
    }
  }
}



// 34. Function ro copy from one table to other, also data can be inserrted 

// parameters ::

// $from_table : Name of the table from which data need to copied

// $to_table : Name of the table to which data need to pasted

// $column : Array of column name or data of source and destination table (destination table should only have correct column name)
// Ex:    $column = array
// (
// 'from_table-col-1' => 'to_table-col-1'',  // from column to destination column
// '"col-2"' => 'to_table-col-2' // data string to destination cloumn
//  $value => 'to_table-col-3'  // Variable to destination cloumn ($value should be done var_dump($value); befor passing in array, this converts in array formet which the function knows its a variable)
//  Add more columns and data as needed
// );

// where_cond : Where condition to fetch from source table

// $orderby : column name to order (used with $orderByColumn)

// $orderByColumn : desc or asc (by default asc will be applied)

// $limit : limit of data to be fetched (pass 0 if no limit to apply)

// $mysqli : db connection

// return : $stmt (status)

// IMPORTANT : use of var_dump() befor passing: $value should be done var_dump($value); befor passing in array, this converts in array formet which the function knows its a variable

function NifTycoon_copy_table_to_table($from_table, $to_table, $columns, $where_cond, $orderby = null, $orderByColumn = null, $limit = null, $mysqli)
{

  $targetColumns = [];
  $sourceValues  = [];

  foreach ($columns as $source => $target) {
    $targetColumns[] = $target;

    if ($source === null || $source === '') {
      $sourceValues[] = "''"; // empty string
    } elseif (preg_match('/^["\'].*["\']$/', $source)) {
      $sourceValues[] = $source; // already quoted
    } elseif (!preg_match('/^[a-zA-Z0-9_]+$/', $source)) {
      // Assume it's a fixed value; quote it safely
      $sourceValues[] = "'" . $mysqli->real_escape_string($source) . "'";
    } else {
      $sourceValues[] = $source; // assume it's a column
    }
  }

  $targetColumnStr = implode(", ", $targetColumns);
  $sourceValueStr  = implode(", ", $sourceValues);

  // Base SQL
  $query = "INSERT INTO `$to_table` ($targetColumnStr) SELECT $sourceValueStr FROM `$from_table`";

  // Optional clauses
  if (!empty($where_cond)) {
    $query .= " WHERE $where_cond";
  }

  if ($orderByColumn && $orderby) {
    $query .= " ORDER BY $orderby $orderByColumn";
  }

  if (is_numeric($limit) && $limit > 0) {
    $query .= " LIMIT " . intval($limit);
  }

  // Prepare and execute
  $stmt = $mysqli->prepare($query);
  if (!$stmt) {
    throw new Exception("Failed to prepare statement: " . $mysqli->error);
  }

  if (!$stmt->execute()) {
    throw new Exception("Error executing statement: " . $stmt->error);
  }

  return $stmt;
}
?>
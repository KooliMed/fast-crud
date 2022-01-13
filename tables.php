<?php

session_start();
if (isset($_POST['load']) && $_POST['load'] == 'load') {
  session_destroy();
  session_start();
}
if (isset($_POST['find']) && !empty($_POST['find']) && (empty($_POST['regex']) || empty($_POST['regexselect']))) {
  session_destroy();
  session_start();
}


/* require "db.php";
/* db setting; these are the only vars to change */
$db_host = "localhost";
$db_username = "root";
$db_password = "";
$db_name = "animaux2";
$Pagination = 3;

$Connection = mysqli_connect($db_host, $db_username, $db_password, $db_name);
// Check connection
if (mysqli_connect_errno()) {
  echo "Failed to connect to MySQL: " . mysqli_connect_error();
}
mysqli_set_charset($Connection, "utf8");
/* eof db conig */

$BaseUrl = (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] == 'on' ? 'https' : 'http') . '://' .  $_SERVER['HTTP_HOST'];
$_SESSION['updateurl'] = $UpdateUrl = $BaseUrl . $_SERVER["REQUEST_URI"];



//get any record field as input select option
function InputSelect($Connection, $TableSelect, $TableColumn)
{

  if (empty($TableColumn)) {
    $TableColumn = 'id';
  };

  $Result = mysqli_query($Connection, "SELECT * FROM " . $TableSelect . " ORDER BY " . $TableColumn . " ASC  LIMIT 100 ");
  if (!$Result) {
    printf("Error: %s\n", mysqli_error($Connection));
  }
  $List = '';
  while ($Fetch = mysqli_fetch_array($Result)) {
    $List .= "<option value='" . $Fetch[$TableColumn] . "'>" . $Fetch[$TableColumn] . "</option>";
  }
  mysqli_free_result($Result);
  return $List;
}


//get the table structure and fields names
function GetColumnNames($Connection, $Table)
{
  $SqlRequest = 'DESCRIBE ' . $Table;
  $Result = mysqli_query($Connection, $SqlRequest);

  $ColumnsNamesList = array();
  while ($Records = mysqli_fetch_assoc($Result)) {
    $ColumnsNamesList[] = $Records['Field'];
  }
  mysqli_free_result($Result);
  return $ColumnsNamesList;
}

//get the table structure and fields names
function get_column_types($Connection, $Table)
{
  $SqlRequest = 'DESCRIBE ' . $Table;
  $Result = mysqli_query($Connection, $SqlRequest);

  $ColumnNameType = array();
  while ($Records = mysqli_fetch_assoc($Result)) {
    $ColumnNameType[] = $Records['Type'];
  }
  mysqli_free_result($Result);
  return $ColumnNameType;
}

//get any field name (not records) as input select option
function InputSelectFields($ColumnsNamesList, $ColumnsNamesNumber, $RegexSelectValue)
{
  $i = 0;
  $n = $ColumnsNamesNumber;
  $List = '';
  while ($n > 0) {
    if ($ColumnsNamesList[$i] == $RegexSelectValue) {
      $SelectedStatus = ' selected ';
    } else {
      $SelectedStatus = '';
    }
    $List .= "<option" . $SelectedStatus . " value='" . $ColumnsNamesList[$i] . "'>" . $ColumnsNamesList[$i] . "</option>";
    $i++;
    $n--;
  }
  return $List;
}

function SqlUpdate($Connection, $Table, $ColumnsNamesList, $ColumnsNamesNumber)
{
  $UpReq = "UPDATE " . $Table . " SET";

  $n = $ColumnsNamesNumber - 1; //because we will add manually the id field -1
  $j = 1;
  while ($n > 0) {
    $UpReq .= " " . $ColumnsNamesList[$j] . "='" . $_POST["$ColumnsNamesList[$j]"] . "'";
    $j++;
    $n--;
    if ($n > 0) {
      $UpReq .= ",";
    }
  }
  $UpReq .= "  where " . $Table . "." . $ColumnsNamesList[0] . "='" . $_POST["$ColumnsNamesList[0]"] . "' ";

  if ($_POST["update"] == 'update') {

    if (mysqli_query($Connection, $UpReq)) {

      $_SESSION['ResultMessage'] = '<div class="alert alert-success" role="alert">Record updated successfully' . '</div>';
      $_SESSION['KeepMessage'] = 1;
      echo '<meta http-equiv="refresh" content="0;url=' . $_SESSION['updateurl'] . '" />';
    } else {
      $_SESSION['ResultMessage'] = '<div class="alert alert-danger" role="alert">Error updating record: ' . mysqli_error($Connection) . '</div>';
      $_SESSION['KeepMessage'] = 1;
      echo '<meta http-equiv="refresh" content="0;url=' . $_SESSION['updateurl'] . '" />';
    }
  }
}



// IMPORTANT must replace any ' or " by \' or \"
// replace this with mysql_real_escape_string() 
foreach ($_POST as $param_name => $param_val) {
  $param_val = mysqli_real_escape_string($Connection, $param_val);
  $_POST[$param_name] = $param_val;
}

?>
<!doctype html>
<html lang="en">

<head>
  <!-- Required meta tags -->
  <meta http-equiv="Content-Type" content="text/html; charset=UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">
  <title>Table!</title>
  <link rel="stylesheet" href="https://stackpath.bootstrapcdn.com/bootstrap/4.2.1/css/bootstrap.min.css" integrity="sha384-GJzZqFGwb1QTTN6wy59ffF1BuGJpLSa9DkKMp0DgiMDm4iYMj70gZWKYbI706tWS" crossorigin="anonymous">
  <link rel="stylesheet" href="https://use.fontawesome.com/releases/v5.6.3/css/all.css" integrity="sha384-UHRtZLI+pbxtHCWp1t77Bi1L4ZtiqrqD80Kn4Z8NTSRyMA2Fd33n5dQ8lWUE00s/" crossorigin="anonymous">
</head>

<body>
  <div id="alldiv" style="display:none">
    <script src="js/jquery-3.3.1.min.js"></script>
   
    <script src="js/bootstrap.min.js"></script>
    &nbsp;<br />
    <?php


    echo $_SESSION['ResultMessage'];

    if ($_SESSION['KeepMessage'] != 1) {
      $_SESSION['ResultMessage'] = '';
    }
    $_SESSION['KeepMessage'] = 2;
    if (empty($_POST["update"]) && empty($_POST["delete"]) && empty($_POST["create"])) {
      echo '<script>$("#alldiv").css("display", "block");</script>';
    }
    ?>
    <div class="container-fluid">
      <?php

      if (isset($_GET['pg']) && !isset($_POST['find']) && !isset($_POST['load']) && $_GET['pg'] > 0) {
        $PagesNbr = $_GET['pg'];
      } else {
        $PagesNbr = 1;
      }
      $TablesList = GetTable($Connection, $db_name);
      $firstKey = array_key_first($TablesList);

      $defaultTable = $TablesList[$firstKey]; // get first db table by default

      $offset = ($PagesNbr - 1) * $Pagination;
      if (isset($_POST['load']) && $_POST['load'] == 'load' && empty($_POST['tableselect'])) {
        $_SESSION['table'] = $defaultTable;
      }
      if (isset($_POST['load']) && $_POST['load'] == 'load' && !empty($_POST['tableselect'])) {
        $_SESSION['table'] = $_POST['tableselect'];
      }
      if (!isset($_POST['load']) && !isset($_POST['tableselect']) && empty($_SESSION['table'])) {
        $_SESSION['table'] = $defaultTable;
      }
      $Table = $_SESSION['table'];
      $TableSelect = $defaultTable;
      $TableColumn = ""; // check this field is endeed empty if we do not have any special option
      /*
      Set Vars for each html input tag (must add some more..once we fix any issue with these)
      Need to create functions to spot/guess what should be the field of the record of each table dynamically
      */
      $InputText = 'type="text"  ';
      $InputTextId = '';
      $InputTextHidden = 'type="hidden" ';
      $InputTextReadOnly = 'type="text"  readonly ';
      $InputCheckbox = 'type="checkbox"'; //must have value 1/0 on/off On/Off ON/OFF
      $InputSelect = 'select';
      $InputSubmit = 'type="submit"';
      $InputTypeList = array($InputTextReadOnly, $InputText, $InputText, $InputText, $InputText, $InputText, $InputText, $InputText, $InputText);
      $InputTypeCreate = array($InputText, $InputText, $InputText, $InputText, $InputText, $InputText, $InputText, $InputText);

      /*
        Need to create a way to add as many elements of the two arrays $InputTypeList & $InputTypeCreate as the number of fields in a table
        have no time, hope some one will do it.
      */

      function GetTable($Connection, $db_name)
      {
        $TableList = array();
        $SqlRequest = 'SELECT * FROM INFORMATION_SCHEMA.TABLES WHERE TABLE_SCHEMA LIKE "' . $db_name . '"  ORDER BY `TABLES`.`TABLE_NAME` ASC';
        $Result = mysqli_query($Connection, $SqlRequest);
        while ($Records = mysqli_fetch_assoc($Result)) {
          $TableList[] = $Records['TABLE_NAME'];
        }
        mysqli_free_result($Result);
        return $TableList;
      }


      $k = count($TablesList); // count the array records number. It begins with 1, not 0
      $i = 0;
      while ($k > 0) {
        if ($TablesList[$i] == $_SESSION['table']) {
          $SelectedStatus = ' selected ';
        } else {
          $SelectedStatus = '';
        }
        $TablesListOptions .= "<option" . $SelectedStatus . " value='" . $TablesList[$i] . "'>" . $TablesList[$i] . "</option>";
        $k--;
        $i++;
      }
      echo '<form action="" method="post" id="dbtable">
<div class="row">
<div class="col">
</div>
<div class="col"><div class="input-group mb-3">
  <div class="input-group-prepend">
    <label class="input-group-text" for="tableselect">Table</label>
  </div>
  <select class="custom-select" id="tableselect" name="tableselect">
  <option></option>
' . $TablesListOptions . '
  </select> &nbsp;&nbsp;
<button type="submit" id="load" name="load" value="load" class="btn btn-secondary">load</button>
</div>
</div>
</div></form>';

      $ColumnsNamesList = GetColumnNames($Connection, $Table);
      $ColumnsNamesNumber = $TableColumnsNumberStored = count($ColumnsNamesList); //count the array records number. It does begins with 1, not 0


      if (!empty($_POST['regex']) && !empty($_POST['regexselect'])) {
        $_SESSION['SqlRegex'] = ' where ' . $_POST['regexselect'] . ' LIKE"%' . $_POST['regex'] . '%" ';
        $_SESSION['RegexValue'] = $_POST['regex'];
        $_SESSION['RegexSelectValue'] = $_POST['regexselect'];
      }
      if (!empty($_POST['find']) && (empty($_POST['regex']) || empty($_POST['regexselect']))) {
        $_SESSION['SqlRegex'] = '';
        $_SESSION['RegexValue'] = '';
        $_SESSION['RegexSelectValue'] = '';
      }
      $SqlRegex = $_SESSION['SqlRegex'];
      $RegexSelectValue = $_SESSION['RegexSelectValue'];

      //get the table records now
      $Records = mysqli_query($Connection, "SELECT * FROM $Table $SqlRegex ORDER BY $Table.$ColumnsNamesList[0] DESC  LIMIT  $Pagination OFFSET $offset ") or die(mysqli_error($Connection));
      /* Détermine le nombre de lignes du jeu de résultats */
      $NbrRecords = $NbrRecordsStored = mysqli_num_rows($Records);
      // list table
      $RecordsCount = mysqli_query($Connection, "SELECT COUNT(*) FROM $Table $SqlRegex ORDER BY $Table.$ColumnsNamesList[0] DESC ") or die(mysqli_error($Connection));
      $TotalRecords = mysqli_fetch_array($RecordsCount);
      $TotalPages = ceil($TotalRecords[0] / $Pagination);

      $RecordsAll = array();
      $i = 0;
      while ($RecordsList = mysqli_fetch_array($Records)) {
        $y = $TableColumnsNumberStored;
        $Ycounter = 0;
        $RecordsRows = array();
        while ($y > 0) {
          $TemporaryArray = array($ColumnsNamesList[$Ycounter] => $RecordsList[$Ycounter]);
          $RecordsRows = array_merge($RecordsRows, $TemporaryArray);
          $Ycounter++;
          $y--;
        }

        array_push($RecordsAll, $RecordsRows);
        $i++;
      }

      $i = 0;
      $InputListGuessed = array();
      $InputCreateListGuessed = array();
      $n = $ColumnsNamesNumber;
      while ($n > 0) {
        $InputTypeList1 = array($ColumnsNamesList[$i] => $InputTypeList[$i]);
        $InputTypeCreate1 = array($ColumnsNamesList[$i] => $InputTypeCreate[$i]);
        array_push($InputListGuessed, $InputTypeList1);
        array_push($InputCreateListGuessed, $InputTypeCreate1);

        $n--;
        $i++;
      }
      unset($InputCreateListGuessed[0]); //remove the id as it should be created automatically


      if ($_POST["delete"] == 'delete') {
        if (mysqli_query($Connection, "DELETE FROM " . $Table . " WHERE id = " . $_POST['id'] . " ;")) {
          $_SESSION['ResultMessage'] = '<div class="alert alert-success" role="alert">Record deleted successfully' . '</div>';
          $_SESSION['KeepMessage'] = 1;
          echo '<meta http-equiv="refresh" content="0;url=' . $_SESSION['updateurl'] . '" />';
        } else {
          $_SESSION['ResultMessage'] = '<div class="alert alert-danger" role="alert">Error deleting record: ' . mysqli_error($Connection) . '</div>';
          $_SESSION['KeepMessage'] = 1;
          echo '<meta http-equiv="refresh" content="0;url=' . $_SESSION['updateurl'] . '" />';
        }
      }
      $ColumnNameType = get_column_types($Connection, $Table);
      /* create table */
      echo '<div class="table-responsive"><form action="" method="post" id="formUsers"><table class="table table-bordered table-sm"><tr>';
      $i = 1;
      $n = $ColumnsNamesNumber - 1;
      while ($n > 0) {
        echo '<th>' . $ColumnsNamesList[$i] . ' <br/><sup>(' . $ColumnNameType[$i] . ')</sup></th>';
        $i++;
        $n--;
      }
      echo '<th></th></tr><tbody>';

      echo '<tr>';

      /* because we will add manually the id field -1 */
      $n = $ColumnsNamesNumber - 1; // the create incremantal should be set to 1, as we already unset the id field in the list of table fields
      $j = 1;

      while ($n > 0) {
        if ($InputCreateListGuessed[$j][$ColumnsNamesList[$j]] == 'select') {
          $SelectField = InputSelect($Connection, $TableSelect, $TableColumn);
          echo '<th><select class="custom-select" name="' . $ColumnsNamesList[$j] . '" id="' . $ColumnsNamesList[$j] . '">' . $SelectField . '</select></th>';
        } else {
          echo '<th><input class="form-control" placeholder="' . $ColumnsNamesList[$j] . '" name="' . $ColumnsNamesList[$j] . '" id="' . $ColumnsNamesList[$j] . '" ' . $InputCreateListGuessed[$j][$ColumnsNamesList[$j]] . '></th>';
        }

        $n--;
        $SqlCreateFields .= ' ' . $ColumnsNamesList[$j];
        $SqlCreatePosts .= ' "' . $_POST[$ColumnsNamesList[$j]] . '"';
        if ($n > 0) {
          $SqlCreateFields .= ',';
          $SqlCreatePosts .= ',';
        }
        $j++;
      }
      echo '<th><div class="container"><input name="create" id="create" type="submit" value="create"  size="5" class="btn btn-success"></div></th>
</tr></tbody>
</table></form></div><hr>';
      //find form
      echo '<form action="" method="post" id="formRegex">
<div class="row">
<div class="col"><div class="input-group mb-3">
<input type="text" id="regex" name="regex" value="' . $_SESSION['RegexValue'] . '" class="form-control" placeholder="Regex or Word Like" aria-label="Regex or Word Like" aria-describedby="basic-addon2">
  <div class="input-group-append">
    <span class="input-group-text" id="basic-addon2">Search</span>
  </div>
</div>
</div>
<div class="col"><div class="input-group mb-3">
  <div class="input-group-prepend">
    <label class="input-group-text" for="regexselect">Field</label>
  </div>
  <select class="custom-select" id="regexselect" name="regexselect">
  <option></option>
' . InputSelectFields($ColumnsNamesList, $ColumnsNamesNumber, $RegexSelectValue) . '
  </select> &nbsp;&nbsp;
<button type="submit" id="find" name="find" value="find" class="btn btn-secondary">find</button>
</div>
</div>
</div>
</form>';
      SqlUpdate($Connection, $Table, $ColumnsNamesList, $ColumnsNamesNumber);
      echo '
<div class="container-fluid"><table class="table table-striped table-bordered table-hover table-sm"><thead><tr>';
      $i = 0;
      $n = $ColumnsNamesNumber;
      while ($n > 0) {
        echo '<th>' . $ColumnsNamesList[$i] . '</th>';
        $i++;
        $n--;
      }
      echo '<th></th></tr></thead><tbody>';
      $i = 0;
      $z = $NbrRecordsStored;
      while ($z > 0) {
        echo '
	<form action="' . $UpdateUrl . '" method="post" id="formUsers' . $RecordsAll[$i][$ColumnsNamesList[0]] . '"><tr>';
        $n = $ColumnsNamesNumber;
        $j = 0;
        while ($n > 0) {
          $extra = '';
          if ($InputListGuessed[$j][$ColumnsNamesList[$j]] == 'type="checkbox"') {
            $extra = ' checked ';
            if ($RecordsAll[$i][$ColumnsNamesList[$j]] == 0 || $RecordsAll[$i][$ColumnsNamesList[$j]] == 'Off' || $RecordsAll[$i][$ColumnsNamesList[$j]] == 'OFF' || $RecordsAll[$i][$ColumnsNamesList[$j]] == 'off') {
              $extra = '';
            }
          }
          echo '<th><input class="form-control" name="' . $ColumnsNamesList[$j] . '" id="' . $ColumnsNamesList[$j] . '" ' . $InputListGuessed[$j][$ColumnsNamesList[$j]] . ' value="' . $RecordsAll[$i][$ColumnsNamesList[$j]] . '" ' . $extra . '></th>';
          $j++;
          $n--;
        }
        $i++;
        $z--;
        echo '<th><div class="container"><div class="row"><div class="col-6"><button name="update" id="update" type="submit" value="update" class="btn btn-primary btn-sm" size="5"><i class="fas fa-edit"></i></button></div><div class="col-6"><button name="delete" id="delete" type="submit" value="delete" class="btn btn-danger  btn-sm" size="5"><i class="fas fa-trash-alt"></i></button></div></div></div></th>
</tr></form>';
      }
      echo '</tbody></table>

<nav aria-label="...">
    <ul class="pagination pagination-sm">';
      if ($PagesNbr == 0) {
        $PagesNbr = 1;
      }
      if ($TotalPages == 0) {
        $TotalPages = 1;
      }
      if ($PagesNbr == 1) {
        echo '<li class="page-item disabled"><a class="page-link" href="#" aria-disabled="true">First</a>';
      } else {
        echo '<li class="page-item"><a class="page-link" href="tables.php?pg=1">First</a></li>';
      }
      if ($PagesNbr <= 1) {
        echo '<li class="page-item  disabled"><a class="page-link" href="#"  aria-disabled="true">Prev</a>';
      } else {
        echo '<li class="page-item"><a class="page-link" href="tables.php?pg=' . ($PagesNbr - 1) . '">Prev</a></li>';
      }
      if ($PagesNbr >= $TotalPages) {
        echo '<li class="page-item  disabled"><a class="page-link" href="#"  aria-disabled="true">Next</a></li>';
      } else {
        echo '<li class="page-item"><a class="page-link" href="tables.php?pg=' . ($PagesNbr + 1) . '">Next</a></li>';
      }
      if ($PagesNbr == $TotalPages) {
        echo '<li class="page-item disabled"><a class="page-link" href="#" aria-disabled="true">Last</a></li>';
      } else {
        echo '<li class="page-item"><a class="page-link" href="tables.php?pg=' . $TotalPages . '">Last</a></li>';
      }
      echo '</ul>
</nav></div>';

      if ($_POST["create"] == 'create') {

        $SqlCreate = "INSERT INTO " . $Table . "(" . $SqlCreateFields . " ) VALUES (" . $SqlCreatePosts . ");";
        if (mysqli_query($Connection, $SqlCreate)) {
          $_SESSION['ResultMessage'] = '<div class="alert alert-success" role="alert">Record created successfully' . '</div>';
          $_SESSION['KeepMessage'] = 1;
          echo '<meta http-equiv="refresh" content="0;url=tables.php" />';
        } else {
          $_SESSION['ResultMessage'] = '<div class="alert alert-danger" role="alert">Error creating record: ' . mysqli_error($Connection) . '</div>';
        }
      }

      mysqli_close($Connection);
      ?>




    </div>
  </div>
</body>

</html>
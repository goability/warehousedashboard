'<?php

/*
bulk import of user records
*/
const DATABASE_TYPE_MYSQL_NAME          = "warehouse"; // used as prefix for queries
const DATABASE_TYPE_MYSQL_USER          = "warehouse_admin";
const DATABASE_TYPE_MYSQL_PASSWORD      = "password1";
const DATABASE_TYPE_MYSQL_HOST          = "127.0.0.1";
const DATABASE_TYPE_MYSQL_PORT          = "3308";

$dbConnection = new mysqli( DATABASE_TYPE_MYSQL_HOST,
                            DATABASE_TYPE_MYSQL_USER,
                            DATABASE_TYPE_MYSQL_PASSWORD,
                            DATABASE_TYPE_MYSQL_NAME,
                            DATABASE_TYPE_MYSQL_PORT);
if (mysqli_connect_error()) {
  die('Connect Error (' . mysqli_connect_errno() . ') '
          . mysqli_connect_error());
        }

$row = 1;
if (($handle = fopen("testdata.csv", "r")) !== FALSE) {
    while (($data = fgetcsv($handle, 1000, "|")) !== FALSE) {
        if ($row>1){
          echo "<br>-----NOT A HEADER ROW, insert this data...";
          $firstname = $data[0];
          $lastname = $data[1];
          $middlename = $data[2];
          $companyname    = $data[3];
          $emailaddress   = $data[4];
          $profilename    = strtolower($data[0]) . "1";
          $pw             = password_hash($lastname, PASSWORD_DEFAULT);

          $sql = "INSERT INTO user (`firstname`, `lastname`, `middlename`, `companyname`, `emailaddress`, `profilename`, `upasswd`)
            VALUES ('$firstname', '$lastname', '$middlename', '$companyname', '$emailaddress', '$profilename', '$pw')";

          $dbConnection->query($sql);

          echo "<Br> INSERTED --- $lastname, $firstname $middlename";
          echo "<br> $sql <br>";
        }

        $num = count($data);
        echo "<p> $num fields in line $row: <br /></p>\n";
        $row++;
        for ($c=0; $c < $num; $c++) {

            echo $data[$c] . "<br />\n";
        }
    }
    fclose($handle);
}

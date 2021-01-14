<!DOCTYPE html>
<html lang="en" dir="ltr">
  <head>
    <meta charset="utf-8">
    <title></title>
  </head>
  <body>


<?php
namespace Ability\Warehouse;
set_time_limit(0);
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


// CREATE STORAGE REQUESTS

 /*
 Look at import-palletcontents
 for each palletID, add ONE storage item for the product
 If the palletID already has been seen, do not create another one

 */


$sql = "SELECT * FROM `import-palletcontents` WHERE `import-palletcontents`.`ownerid`=62";
$result = $dbConnection->query($sql);
  $palletIDsStored = array();
    $i = 0;
while ($row = $result->fetch(\PDO::FETCH_ASSOC)){

  $itemid = $row['storageitemid'];
  $storagerequestname = "request $itemid";
  $ownerid  = $row['ownerid'];
  $lotnumber = $row['lotnumber'];
  $palletid = $row['palletID'];
  //echo "pallet [$palletid]";
  if (!in_array($palletid,$palletIDsStored)){
    $palletIDsStored[]=$palletid;

    $sqlInsert = "INSERT INTO `storage` (`itemid`, `name`, `qty`, `userid_requestor`, `userid_approver`, `userid_stocker`, `lotnumber`, `tag`, `notes`, `date_created`, `date_approved`, `date_stored`, `date_needed`)";
    $sqlInsert .= " VALUES($itemid, '$storagerequestname', 0, $ownerid, 1001, 1003, '$lotnumber', NULL, 'newsys import', CURRENT_TIMESTAMP, CURRENT_TIMESTAMP, CURRENT_TIMESTAMP, CURRENT_TIMESTAMP)";

    //$result = $dbConnection->query($sqlInsert);
    //break;

    echo "<br>$sqlInsert";

    if (!$result){
      die('error on import');
    }
    $i++;
  }

}
echo "count inserted $i";


//CREATE PALLET INVENTORY
/*
$sqlBase = "SELECT palletid, qtyin, qtyout FROM `import-transactions`";


        $lastID = 0;
        $totalCount = 0;
        $thisCount = 0;

        $workingRecords = array();

          for ($i=0; $i < 4 ; $i++) {

            $workingRecords = array();
            $sql = $sqlBase . " WHERE palletid > $lastID ORDER BY palletid LIMIT 100000";
                    echo "<Br>$sql<br>";

            $result = $dbConnection->query($sql);
            while ($row = $result->fetch_assoc()){
                $workingRecords[] = $row;
            }

            $lastID = $workingRecords[count($workingRecords)-1]['palletid'];

            echo "<br>last ID for round $i is $lastID";
            $totalCount += count($workingRecords);

            echo "<Br> LOADED $totalCount Transactions so far";


            echo "<br>Now update the pallet inventory qty";

            $importedRecordCount = 0;
            foreach ($workingRecords as $row) {
              $importedRecordCount++;
              $palletid = $row['palletid'];
              $item_qty = $row['qtyin'] - $row['qtyout'];
              $sql = "UPDATE storagepalletinventory SET item_qty=$item_qty WHERE palletid=$palletid";
              //echo "<br>$sql<Br>";
              $result = $dbConnection->query($sql);

              if (!$result){
                die('error on import');
              }
            }
            echo " ----> DONE - UPDATED $importedRecordCount records<br>";

          }

          echo "<hr>Now update the storageitem on the pallet";
*/
//UPDATE PALLET INVENTORY WITH CORRECT STORAGE ITEM ID
/*
          $sql = "SELECT palletid, storageitem.id AS storageitemid, `import-palletcontents`.`uom` FROM `import-palletcontents` INNER JOIN `storageitem` ON `storageitem`.`id`=`import-palletcontents`.`storageitemid` WHERE 1";
          echo "<Br>$sql<br>";

          $result = $dbConnection->query($sql);
          while ($row = $result->fetch_assoc()){
              $palletcontents[] = $row;
              $c++;
          }

          foreach ($palletcontents as $row) {
            $storageitemID = $row['storageitemid'];
            $palletid = $row['palletid'];

            $sqlUpdate = "UPDATE storagepalletinventory SET itemid=$storageitemID WHERE palletid=$palletid";
            $result = $dbConnection->query($sqlUpdate);

            if (!$result){
              die('error on import');
            }

            $d++;

          }

          echo "<br>found $c records updated  $d with correct storageitem IDS";

*/
/*
echo "<hr>Now update the storage request ID on the pallet";

//UPDATE PALLET INVENTORY WITH CORRECT STORAGE REQUEST ID
//base query // TODO: needs work, returns way too many
$sqlBase = "SELECT palletid, storage.id AS storageid, `import-palletcontents`.`uom` FROM `storage` INNER JOIN `import-palletcontents` ON `storage`.`itemid`=`import-palletcontents`.`storageitemid` WHERE 1";
$sqlCount = "SELECT COUNT(palletid) numRecs FROM `storage` INNER JOIN `import-palletcontents` ON `storage`.`itemid`=`import-palletcontents`.`storageitemid` WHERE 1";


$c=$d=0;
$result = $dbConnection->query($sqlCount);

$row = $result->fetch_assoc();
$result = null;
$numRecs = $row['numRecs'];
echo "<Br>Found [$numRecs] records total ";

$startRecordIndex = $totalRecordsUpdated = 0;
$limit = $numRecs>5000 ? 5000 : $numRecs;


$palletIDsStored = array();
echo "<br>Updating pallets: ";
for ($i=0; $i < $numRecs; $i+=$limit) {
  $sql = $sqlBase . " LIMIT $startRecordIndex, $limit";

  //echo $sql;
  $result2 = $dbConnection->query($sql);
  while ($row = $result2->fetch_assoc()){

      $storageID  = $row['storageid'];
      $palletid   = $row['palletid'];

      if (!in_array($palletid,$palletIDsStored)){
        $palletIDsStored[]=$palletid;
        $sqlUpdate = "UPDATE storagepalletinventory SET storageid=$storageID WHERE palletid=$palletid";
        $result = $dbConnection->query($sqlUpdate);

        if (!$result){
          die('error on import');
        }
        else{
          echo "[$palletid]";
        }
        $d++;
        $c++;

        $totalRecordsUpdated += 1;
      }


    }
    $startRecordIndex += $limit;

    //echo "<br>found $c records updated  $d with correct storage request IDS";
  }
  echo "Done [$totalRecordsUpdated] "
*/

//UPDATE PALLET INVENTORY WITH CORRECT STORAGE ITEM ID
/*
          $sql = "SELECT `storageid`, `item_qty` FROM `storagepalletinventory`";

          echo "<Br>$sql<br>";

          $result = $dbConnection->query($sql);
          while ($row = $result->fetch_assoc()){
              $palletcontents[] = $row;
              $c++;
          }

          foreach ($palletcontents as $row) {
            $storageid = $row['storageid'];
            $qty      = $row['item_qty'];

            $sqlUpdate = "UPDATE storage SET qty=$qty WHERE id=$storageid";
            //echo $sqlUpdate;
            $result = $dbConnection->query($sqlUpdate);

            if (!$result){
              die('error on import');
            }

            $d++;

          }

          echo "<br>found $c storage requests updated  $d with correct quantities";
*/
?>
</body>
</html>

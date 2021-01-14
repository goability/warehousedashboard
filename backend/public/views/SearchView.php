<?php
namespace Ability\Warehouse;

// Search View, show results of a search
$searchString = !empty($_POST['searchString']) ? $_POST['searchString'] : null;
$currentUserID = SessionManager::GetCurrentUserID();

if (!is_null($searchString)){
  $searchResults = DataProvider::SearchForItem($searchString, $currentUserID);
}

?>
<?php if (empty($searchResults)){
  echo "No results found for [$searchString]";
}
else{
?>
<div class="content-container">
<?php
foreach ($searchResults as $storageItem) {
  ?>
  <ul class="list-group">
    <li class="list-group-item"><?php echo $storageItem["name"]; ?></li>
  </ul>
  <?php
}
?>

</div>
<?php }?>

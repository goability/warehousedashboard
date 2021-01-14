<?php

namespace Ability\Warehouse;

// User View
// Show the Storage Item Form
//  A View should know what mode it is in and show the form

if (null==$currentRecord)
  echo "No record.  Logic Error";

$currentRecord->ShowForm();

?>

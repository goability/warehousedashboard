<?php
namespace Ability\Warehouse;

// A default view for any object
//  Assumptions:  $currentRecord is loaded and implements ShowForm()
//
// Show the Storage Item Form
//  A View should know what mode it is in and show the form

if (null==$currentRecord)
{
  echo "No record. ";
}
else
{
  $currentRecord->ShowForm();
}

<?php

namespace Ability\Warehouse;

//DESIGN:
//  This form maps to DB fields and can be in CRUD modes.  Delete/Update/Save/Cancel
//  a current $this object of type $ResourceBaseType is required
// NOTE that this form is included from within a particular Object class, so this is User, StorageItem, ...


if (null==$this)
  echo "No record ";
?>
<ul class="nav nav-tabs">
  <li class="nav-item">
    <a class="nav-link" data-toggle="tab" href="#manage"><?php echo static::$FormTitle; ?></a>
  </li>
</ul>

<div class="tab-content">
  <div class="tab-pane container-content active" id="manage" style="margin:0px;">
        <?php require("formItemRecordGeneric.php");?>
  </div>
</div>

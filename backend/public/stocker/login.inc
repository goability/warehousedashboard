<?php
namespace Ability\Warehouse;
?>
<form action="login" method="post">
  <table>
    <tr>
      <td>user<input type="text" name="username"></td>
    </tr>
    <tr>
      <td>pass<input type="password" name="password"></td>
    </tr>
    <tr>
      <td><input type="submit" value="login"><?php echo $statusMessage; ?></td>
    </tr>
  </table>
</form>

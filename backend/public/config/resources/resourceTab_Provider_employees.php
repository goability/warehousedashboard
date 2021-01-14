<?php
namespace Ability\Warehouse;

$employees = SessionManager::GetEmployees();

if (empty($employees)){
  echo "<div class='h3' id='pending-storage-title'>No active employees</div>";
} else {
      foreach ($employees as $type=>$employees) {
        echo "<div class='h3' id='employee-title'>$type Employees</div>";
        ?>

      <table class="table" id="employee-table">
        <thead>
          <tr>
            <th>Name</th>
            <th>Email</th>
            <th>Location</th>
            <th>Recent actions</th>
          </tr>
        </thead>
        <tbody id='pending-storage-body'>
            <?php
            foreach ($employees as $employee) {

              $recent_actions = "none";
              ?>
              <tr class='table-dark text-dark'>
                <td><?php echo $employee["name"]; ?></td>
                <td><?php echo $employee["emailaddress"]; ?></td>
                <td><?php echo $employee["location"]; ?></td>
                <td><?php echo $recent_actions; ?></td>
              </tr>

              <?php
            }
      }

  ?>
  </tbody>
</table>
<?php } //end of else there is item history

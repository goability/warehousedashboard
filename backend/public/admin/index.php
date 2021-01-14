<?php

require_once "../../src/dbconfig/db_config.php";
require_once "../../src/classes/ResourceBaseType.php";
require_once "./menuAdminView.php";
require_once "./menuAdminRouter.php";

echo get_menu();

handle_route();

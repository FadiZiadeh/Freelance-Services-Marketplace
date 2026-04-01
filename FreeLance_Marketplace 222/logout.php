<?php
declare(strict_types=1);
require_once "includes/auth.php.inc";
require_once "includes/flash.php.inc";

session_unset();
session_destroy();

session_start();
set_flash("success", "You have been logged out.");
header("Location: index.php");
exit;

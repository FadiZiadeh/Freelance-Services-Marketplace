<?php
declare(strict_types=1);

require_once "includes/auth.php.inc";
require_once "includes/flash.php.inc";

require_role('Client');

$_SESSION['cart'] = [];

set_flash("success", "Cart cleared successfully.");
header("Location: cart.php");
exit;
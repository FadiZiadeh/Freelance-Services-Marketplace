<?php
declare(strict_types=1);

require_once "includes/auth.php.inc";
require_once "includes/flash.php.inc";

require_role('Client');

$serviceId = trim((string)($_POST['id'] ?? ''));

if ($serviceId === '') {
  set_flash("error", "Missing service id.");
  header("Location: cart.php");
  exit;
}

if (!isset($_SESSION['cart']) || !is_array($_SESSION['cart'])) {
  $_SESSION['cart'] = [];
}

$before = count($_SESSION['cart']);


$_SESSION['cart'] = array_values(array_filter($_SESSION['cart'], function ($it) use ($serviceId) {
  if (!is_array($it)) return true; 
  return (string)($it['service_id'] ?? '') !== $serviceId;
}));

$after = count($_SESSION['cart']);

if ($after < $before) {
  set_flash("success", "Service removed from cart.");
} else {
  set_flash("error", "Service not found in cart.");
}

header("Location: cart.php");
exit;

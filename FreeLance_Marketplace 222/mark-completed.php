<?php
declare(strict_types=1);

require_once "includes/auth.php.inc";
require_once "includes/db.php.inc";
require_once "includes/flash.php.inc";

require_role('Client');

$clientId = (string)($_SESSION['user_id'] ?? '');
$orderId  = trim((string)($_POST['order_id'] ?? ''));

if ($orderId === '') {
  set_flash("error", "Missing order id.");
  header("Location: my-orders.php");
  exit;
}


$stmt = $pdo->prepare("
  SELECT order_id, status
  FROM orders
  WHERE order_id = :oid AND client_id = :cid
  LIMIT 1
");
$stmt->execute([':oid' => $orderId, ':cid' => $clientId]);
$row = $stmt->fetch();

if (!$row) {
  set_flash("error", "Order not found.");
  header("Location: my-orders.php");
  exit;
}

$status = (string)$row['status'];


if ($status !== 'Delivered') {
  set_flash("error", "You can mark completed only when the order is Delivered.");
  header("Location: order-details.php?id=" . urlencode($orderId));
  exit;
}


$upd = $pdo->prepare("
  UPDATE orders
  SET status = 'Completed'
  WHERE order_id = :oid AND client_id = :cid
");
$upd->execute([':oid' => $orderId, ':cid' => $clientId]);

set_flash("success", "Order marked as Completed. Thank you!");
header("Location: order-details.php?id=" . urlencode($orderId));
exit;

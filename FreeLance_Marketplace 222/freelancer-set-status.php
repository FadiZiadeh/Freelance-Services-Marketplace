<?php
declare(strict_types=1);

require_once "includes/auth.php.inc";
require_once "includes/db.php.inc";
require_once "includes/flash.php.inc";

require_role('Freelancer');

$fid = (string)($_SESSION['user_id'] ?? '');
$orderId = trim((string)($_POST['order_id'] ?? ''));
$newStatus = trim((string)($_POST['status'] ?? ''));

$allowed = ['In Progress']; 

if ($orderId === '' || !in_array($newStatus, $allowed, true)) {
  set_flash("error", "Invalid request.");
  header("Location: freelancer-orders.php");
  exit;
}


$stmt = $pdo->prepare("SELECT status FROM orders WHERE order_id=:oid AND freelancer_id=:fid LIMIT 1");
$stmt->execute([':oid' => $orderId, ':fid' => $fid]);
$row = $stmt->fetch();

if (!$row) {
  set_flash("error", "Order not found.");
  header("Location: freelancer-orders.php");
  exit;
}

$current = (string)$row['status'];
if ($current !== 'Pending') {
  set_flash("error", "You can only mark Pending orders as In Progress.");
  header("Location: freelancer-order-details.php?id=" . urlencode($orderId));
  exit;
}

$upd = $pdo->prepare("UPDATE orders SET status='In Progress' WHERE order_id=:oid AND freelancer_id=:fid");
$upd->execute([':oid' => $orderId, ':fid' => $fid]);

set_flash("success", "Order marked as In Progress.");
header("Location: freelancer-order-details.php?id=" . urlencode($orderId));
exit;

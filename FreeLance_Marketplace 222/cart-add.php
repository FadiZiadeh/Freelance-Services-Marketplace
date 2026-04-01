<?php
declare(strict_types=1);

require_once "includes/auth.php.inc";
require_once "includes/db.php.inc";
require_once "includes/flash.php.inc";

require_role('Client');

$serviceId = trim((string)($_GET['id'] ?? ''));
if ($serviceId === '') {
  header("Location: browse-services.php");
  exit;
}

$stmt = $pdo->prepare("
  SELECT service_id, service_title, price, delivery_time, revisions_included, image_1
  FROM services
  WHERE service_id = :sid AND status = 'Active'
  LIMIT 1
");
$stmt->execute([':sid' => $serviceId]);
$row = $stmt->fetch();

if (!$row) {
  set_flash("error", "Service not found or inactive.");
  header("Location: browse-services.php");
  exit;
}

if (!isset($_SESSION['cart']) || !is_array($_SESSION['cart'])) {
  $_SESSION['cart'] = [];
}

$item = [
  'service_id' => (string)$row['service_id'],
  'service_title' => (string)$row['service_title'],
  'price' => (float)$row['price'],
  'delivery_time' => (int)$row['delivery_time'],
  'revisions_included' => (int)$row['revisions_included'],
  'image_1' => (string)$row['image_1'],
];

foreach ($_SESSION['cart'] as $it) {
  if (is_array($it) && ($it['service_id'] ?? '') === $item['service_id']) {
    set_flash("error", "This service is already in your cart.");
    header("Location: cart.php");
    exit;
  }
}

$_SESSION['cart'][] = $item;

set_flash("success", "Service added to cart.");
header("Location: cart.php");
exit;

<?php
declare(strict_types=1);

require_once "includes/auth.php.inc";
require_once "includes/db.php.inc";
require_once "includes/flash.php.inc";

require_role('Client');

function gen_order_id(PDO $pdo): string {
  for ($i = 0; $i < 20; $i++) {
    $id = str_pad((string)random_int(0, 9999999999), 10, '0', STR_PAD_LEFT);
    $st = $pdo->prepare("SELECT 1 FROM orders WHERE order_id = :id LIMIT 1");
    $st->execute([':id' => $id]);
    if (!$st->fetch()) return $id;
  }
  
  return (string)time();
}

$cart = $_SESSION['cart'] ?? [];
if (!is_array($cart) || !$cart) {
  set_flash("error", "Your cart is empty.");
  header("Location: cart.php");
  exit;
}

$clientId = (string)($_SESSION['user_id'] ?? '');
$payment = trim((string)($_POST['payment_method'] ?? ''));

$req = $_POST['req'] ?? [];
if (!is_array($req)) $req = [];

$errors = [];

if ($payment === '') {
  $errors[] = "Payment method is required.";
}


foreach ($cart as $it) {
  if (!is_array($it)) continue;
  $sid = (string)($it['service_id'] ?? '');
  $txt = trim((string)($req[$sid] ?? ''));
  if ($sid === '' || $txt === '') {
    $errors[] = "Requirements are required for all services.";
    break;
  }
}

if ($errors) {
  set_flash("error", implode(" ", $errors));
  header("Location: checkout.php");
  exit;
}

try {
  $pdo->beginTransaction();

  $insert = $pdo->prepare("
  INSERT INTO orders
    (order_id, client_id, freelancer_id, service_id, service_title, price,
     delivery_time, revisions_included, requirements, instructions,
     deliverable_notes, status, payment_method, expected_delivery)
  VALUES
    (:order_id, :client_id, :freelancer_id, :service_id, :service_title, :price,
     :delivery_time, :revisions_included, :requirements, :instructions,
     NULL, 'Pending', :payment_method, :expected_delivery)
");

  foreach ($cart as $it) {
    if (!is_array($it)) continue;

    $serviceId = (string)($it['service_id'] ?? '');
    $title = (string)($it['service_title'] ?? '');
    $price = (float)($it['price'] ?? 0);
    $delivery = (int)($it['delivery_time'] ?? 0);
    $revs = (int)($it['revisions_included'] ?? 0);

    
    $st = $pdo->prepare("SELECT freelancer_id FROM services WHERE service_id = :sid LIMIT 1");
    $st->execute([':sid' => $serviceId]);
    $row = $st->fetch();
    if (!$row) {
      throw new RuntimeException("Service not found during checkout.");
    }
    $freelancerId = (string)$row['freelancer_id'];

    $requirements = trim((string)($req[$serviceId] ?? ''));

   
    $expected = (new DateTime('now'))->modify('+' . max(0, $delivery) . ' day')->format('Y-m-d');

    $insert->execute([
  ':order_id' => gen_order_id($pdo),
  ':client_id' => $clientId,
  ':freelancer_id' => $freelancerId,
  ':service_id' => $serviceId,
  ':service_title' => $title,
  ':price' => $price,
  ':delivery_time' => $delivery,
  ':revisions_included' => $revs,
  ':requirements' => $requirements,
  ':instructions' => null,
  ':payment_method' => $payment,
  ':expected_delivery' => $expected,
]);
  }

  $pdo->commit();

  
  $_SESSION['cart'] = [];

  set_flash("success", "Order(s) placed successfully!");
  header("Location: my-orders.php");
  exit;

} catch (Throwable $e) {
  if ($pdo->inTransaction()) $pdo->rollBack();
  set_flash("error", "Checkout failed: " . $e->getMessage());
  header("Location: checkout.php");
  exit;
}

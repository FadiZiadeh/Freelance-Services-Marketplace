<?php
declare(strict_types=1);

require_once "includes/auth.php.inc";
require_once "includes/flash.php.inc";

require_role('Client');

function h(string $s): string { return htmlspecialchars($s, ENT_QUOTES, 'UTF-8'); }

function normalize_cart_item($it): ?array {
  if (is_array($it)) {
    return [
      'service_id' => (string)($it['service_id'] ?? ''),
      'service_title' => (string)($it['service_title'] ?? ''),
      'price' => (float)($it['price'] ?? 0),
      'delivery_time' => (int)($it['delivery_time'] ?? 0),
      'revisions_included' => (int)($it['revisions_included'] ?? 0),
      'image_1' => (string)($it['image_1'] ?? ''),
    ];
  }

  if (is_object($it)) {
    $a = (array)$it;

    $get = function(string $needle) use ($a) {
      foreach ($a as $k => $v) {
        if (strpos((string)$k, $needle) !== false) return $v;
      }
      return null;
    };

    $sid = $get('service_id');
    if ($sid === null) return null;

    return [
      'service_id' => (string)$sid,
      'service_title' => (string)($get('service_title') ?? ''),
      'price' => (float)($get('price') ?? 0),
      'delivery_time' => (int)($get('delivery_time') ?? 0),
      'revisions_included' => (int)($get('revisions_included') ?? 0),
      'image_1' => (string)($get('image_1') ?? ''),
    ];
  }

  return null;
}


$rawCart = $_SESSION['cart'] ?? [];
if (!is_array($rawCart)) $rawCart = [];

$cart = [];
foreach ($rawCart as $it) {
  $norm = normalize_cart_item($it);
  if ($norm && $norm['service_id'] !== '') $cart[] = $norm;
}


$_SESSION['cart'] = $cart;


$subtotal = 0.0;
foreach ($cart as $it) $subtotal += (float)($it['price'] ?? 0);

$fee = $subtotal * 0.05;
$total = $subtotal + $fee;
?>
<!doctype html>
<html>
<head>
  <meta charset="utf-8">
  <title>Cart</title>
  <link rel="stylesheet" href="css/base.css">
  <style>
    .thumb {
      width:80px; height:55px;
      object-fit:cover;
      border-radius:10px;
      border:1px solid #ddd;
      display:block;
    }
    .actions-row{ display:flex; gap:10px; flex-wrap:wrap; margin-top:12px; }
    .total-line{ margin-top:6px; }
  </style>
</head>
<body>
<div class="page">
  <?php include "includes/header.php.inc"; ?>
  <?php include "includes/nav.php.inc"; ?>

  <main class="main">
    <h1>My Cart</h1>

    <?php if ($f = get_flash()): ?>
      <div class="flash <?php echo h($f['type']); ?>"><?php echo h($f['message']); ?></div>
    <?php endif; ?>

    <?php if (!$cart): ?>
      <p>Your cart is empty.</p>
      <a class="btn btn-primary" href="browse-services.php">Browse Services</a>
    <?php else: ?>

      <div class="table-wrap">
        <table class="tbl" style="width:100%; border-collapse:collapse;">
          <thead>
            <tr>
              <th>Image</th>
              <th>Service</th>
              <th>Price</th>
              <th>Delivery</th>
              <th>Revisions</th>
              <th>Action</th>
            </tr>
          </thead>
          <tbody>
          <?php foreach ($cart as $it): ?>
            <tr>
              <td style="width:90px;">
                <img class="thumb" src="<?php echo h((string)$it['image_1']); ?>" alt="img">
              </td>
              <td><?php echo h((string)$it['service_title']); ?></td>
              <td><?php echo number_format((float)$it['price'], 2); ?> USD</td>
              <td><?php echo (int)$it['delivery_time']; ?> days</td>
              <td><?php echo (int)$it['revisions_included']; ?></td>
              <td>
                <form method="post" action="cart-remove.php" style="margin:0;">
                  <input type="hidden" name="id" value="<?php echo h((string)$it['service_id']); ?>">
                  <button class="btn btn-secondary" type="submit">Remove</button>
                </form>
              </td>
            </tr>
          <?php endforeach; ?>
          </tbody>
        </table>
      </div>

      <div class="card" style="margin-top:14px; max-width:420px;">
        <div>Subtotal: <strong><?php echo number_format($subtotal, 2); ?> USD</strong></div>
        <div>Platform fee (5%): <strong><?php echo number_format($fee, 2); ?> USD</strong></div>
        <div class="total-line">Total: <strong><?php echo number_format($total, 2); ?> USD</strong></div>

        <div class="actions-row">
          <a class="btn btn-secondary" href="cart-clear.php">Clear Cart</a>
          <a class="btn btn-primary" href="checkout.php">Proceed to Checkout</a>
        </div>
      </div>

    <?php endif; ?>
  </main>

  <?php include "includes/footer.php.inc"; ?>
</div>
</body>
</html>

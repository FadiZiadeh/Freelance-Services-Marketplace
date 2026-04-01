<?php
declare(strict_types=1);

require_once "includes/auth.php.inc";
require_once "includes/flash.php.inc";

require_role('Client');

function h(string $s): string { return htmlspecialchars($s, ENT_QUOTES, 'UTF-8'); }

$cart = $_SESSION['cart'] ?? [];
if (!is_array($cart)) $cart = [];

if (!$cart) {
  set_flash("error", "Your cart is empty.");
  header("Location: cart.php");
  exit;
}

// totals
$subtotal = 0.0;
foreach ($cart as $it) {
  if (is_array($it)) $subtotal += (float)($it['price'] ?? 0);
}
$fee = $subtotal * 0.05;
$total = $subtotal + $fee;
?>
<!doctype html>
<html>
<head>
  <meta charset="utf-8">
  <title>Checkout</title>
  <link rel="stylesheet" href="css/base.css">
</head>
<body>
<div class="page">
  <?php include "includes/header.php.inc"; ?>
  <?php include "includes/nav.php.inc"; ?>

  <main class="main">
    <h1>Checkout</h1>

    <?php if ($f = get_flash()): ?>
      <div class="flash <?php echo h($f['type']); ?>"><?php echo h($f['message']); ?></div>
    <?php endif; ?>

    <form method="post" action="checkout-submit.php" novalidate>
      <div style="overflow:auto; border:1px solid #ddd; border-radius:12px;">
        <table class="tbl" style="width:100%; border-collapse:collapse;">
          <thead>
            <tr>
              <th>Image</th>
              <th>Service</th>
              <th>Price</th>
              <th>Delivery</th>
              <th>Revisions</th>
              <th style="min-width:260px;">Requirements (required)</th>
            </tr>
          </thead>
          <tbody>
          <?php foreach ($cart as $it): if (!is_array($it)) continue; 
            $sid = (string)($it['service_id'] ?? '');
          ?>
            <tr>
              <td style="width:90px;">
                <img src="<?php echo h((string)($it['image_1'] ?? '')); ?>" alt="img"
                     style="width:80px; height:55px; object-fit:cover; border-radius:10px; border:1px solid #ddd;">
              </td>
              <td><?php echo h((string)($it['service_title'] ?? '')); ?></td>
              <td><?php echo number_format((float)($it['price'] ?? 0), 2); ?> USD</td>
              <td><?php echo (int)($it['delivery_time'] ?? 0); ?> days</td>
              <td><?php echo (int)($it['revisions_included'] ?? 0); ?></td>
              <td>
                <textarea name="req[<?php echo h($sid); ?>]" rows="3" style="width:100%;"
                  placeholder="Describe what you need for this service..."></textarea>
              </td>
            </tr>
          <?php endforeach; ?>
          </tbody>
        </table>
      </div>

      <div style="margin-top:14px; border:1px solid #ddd; border-radius:12px; padding:12px; max-width:520px;">
        <div>Subtotal: <strong><?php echo number_format($subtotal, 2); ?> USD</strong></div>
        <div>Platform fee (5%): <strong><?php echo number_format($fee, 2); ?> USD</strong></div>
        <div style="margin-top:6px;">Total: <strong><?php echo number_format($total, 2); ?> USD</strong></div>

        <div style="margin-top:12px;">
          <label><strong>Payment Method *</strong></label><br>
          <select name="payment_method" required style="margin-top:6px; width:100%; max-width:280px;">
            <option value="">-- Select --</option>
            <option value="Credit Card">Credit Card</option>
            <option value="PayPal">PayPal</option>
            <option value="Bank Transfer">Bank Transfer</option>
          </select>
        </div>

        <div style="margin-top:14px; display:flex; gap:10px; flex-wrap:wrap;">
          <a class="btn btn-secondary" href="cart.php">Back to Cart</a>
          <button class="btn btn-primary" type="submit">Confirm & Place Order</button>
        </div>
      </div>
    </form>
  </main>

  <?php include "includes/footer.php.inc"; ?>
</div>
</body>
</html>

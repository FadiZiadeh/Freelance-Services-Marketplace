<?php
declare(strict_types=1);

require_once "includes/auth.php.inc";
require_once "includes/db.php.inc";
require_once "includes/flash.php.inc";

require_role('Client');

function h(string $s): string { return htmlspecialchars($s, ENT_QUOTES, 'UTF-8'); }

$clientId = (string)($_SESSION['user_id'] ?? '');

$stmt = $pdo->prepare("
  SELECT
    o.order_id, o.service_id, o.service_title, o.price,
    o.status, o.payment_method, o.created_date, o.expected_delivery,
    u.first_name AS freelancer_first, u.last_name AS freelancer_last
  FROM orders o
  JOIN users u ON u.user_id = o.freelancer_id
  WHERE o.client_id = :cid
  ORDER BY o.created_date DESC
");
$stmt->execute([':cid' => $clientId]);
$orders = $stmt->fetchAll();
?>
<!doctype html>
<html>
<head>
  <meta charset="utf-8">
  <title>My Orders</title>
  <link rel="stylesheet" href="css/base.css">
</head>
<body>
<div class="page">
  <?php include "includes/header.php.inc"; ?>
  <?php include "includes/nav.php.inc"; ?>

  <main class="main">
    <h1>My Orders</h1>

    <?php if ($f = get_flash()): ?>
      <div class="flash <?php echo h($f['type']); ?>"><?php echo h($f['message']); ?></div>
    <?php endif; ?>

    <?php if (!$orders): ?>
      <p>You have no orders yet.</p>
      <a class="btn btn-primary" href="browse-services.php">Browse Services</a>
    <?php else: ?>
      <div class="table-wrap">
        <table class="tbl" style="width:100%; border-collapse:collapse;">
          <thead>
            <tr>
              <th>Order ID</th>
              <th>Service</th>
              <th>Freelancer</th>
              <th>Price</th>
              <th>Status</th>
              <th>Created</th>
              <th>Expected Delivery</th>
              <th>Payment</th>
            </tr>
          </thead>
          <tbody>
          <?php foreach ($orders as $o): ?>
            <tr>
              <td>
                <a href="order-details.php?id=<?php echo h((string)$o['order_id']); ?>">
                  <?php echo h((string)$o['order_id']); ?>
                </a>
              </td>
              <td>
                <?php echo h((string)$o['service_title']); ?><br>
                <span class="muted">Service ID: <?php echo h((string)$o['service_id']); ?></span>
              </td>
              <td><?php echo h((string)$o['freelancer_first'] . ' ' . (string)$o['freelancer_last']); ?></td>
              <td><?php echo number_format((float)$o['price'], 2); ?> USD</td>
              <td><span class="badge"><?php echo h((string)$o['status']); ?></span></td>
              <td><?php echo h((string)$o['created_date']); ?></td>
              <td><?php echo h((string)($o['expected_delivery'] ?? '')); ?></td>
              <td><?php echo h((string)$o['payment_method']); ?></td>
            </tr>
          <?php endforeach; ?>
          </tbody>
        </table>
      </div>
    <?php endif; ?>

    <div class="actions-row">
      <a class="btn btn-secondary" href="browse-services.php">Continue Browsing</a>
      <a class="btn btn-secondary" href="cart.php">Back to Cart</a>
    </div>
  </main>

  <?php include "includes/footer.php.inc"; ?>
</div>
</body>
</html>

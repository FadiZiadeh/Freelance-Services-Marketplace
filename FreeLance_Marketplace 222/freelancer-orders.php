<?php
declare(strict_types=1);

require_once "includes/auth.php.inc";
require_once "includes/db.php.inc";
require_once "includes/flash.php.inc";

require_role('Freelancer');

function h(string $s): string { return htmlspecialchars($s, ENT_QUOTES, 'UTF-8'); }

$fid = (string)($_SESSION['user_id'] ?? '');
if ($fid === '') {
  header("Location: login.php");
  exit;
}

$stmt = $pdo->prepare("
  SELECT
    order_id,
    service_id,
    service_title,
    price,
    status,
    created_date,
    expected_delivery
  FROM orders
  WHERE freelancer_id = :fid
  ORDER BY created_date DESC
");
$stmt->execute([':fid' => $fid]);
$orders = $stmt->fetchAll();
?>
<!doctype html>
<html>
<head>
  <meta charset="utf-8">
  <title>My Orders (Freelancer)</title>
  <link rel="stylesheet" href="css/base.css">
</head>
<body>
<div class="page">
  <?php include "includes/header.php.inc"; ?>
  <?php include "includes/nav.php.inc"; ?>

  <main class="main">
    <h1>My Orders</h1>

    <?php if ($f = get_flash()): ?>
      <div class="flash <?php echo h($f['type']); ?>">
        <?php echo h($f['message']); ?>
      </div>
    <?php endif; ?>

    <?php if (!$orders): ?>
      <p class="muted">No orders yet.</p>
    <?php else: ?>
      <div class="table-wrap">
        <table class="tbl" style="width:100%; border-collapse:collapse;">
          <thead>
            <tr>
              <th>Order ID</th>
              <th>Service</th>
              <th>Price</th>
              <th>Status</th>
              <th>Created</th>
              <th>Expected Delivery</th>
              <th>Action</th>
            </tr>
          </thead>
          <tbody>
          <?php foreach ($orders as $o): ?>
            <tr>
              <td>
                <strong><?php echo h((string)$o['order_id']); ?></strong>
              </td>

              <td>
                <?php echo h((string)$o['service_title']); ?><br>
                <span class="muted">Service ID: <?php echo h((string)$o['service_id']); ?></span>
              </td>

              <td><?php echo number_format((float)$o['price'], 2); ?> USD</td>

              <td>
                <span class="badge"><?php echo h((string)$o['status']); ?></span>
              </td>

              <td><?php echo h((string)$o['created_date']); ?></td>

              <td><?php echo h((string)($o['expected_delivery'] ?? '')); ?></td>

              <td>
                <a class="btn btn-secondary"
                   href="freelancer-order-details.php?id=<?php echo h((string)$o['order_id']); ?>">
                  View
                </a>
              </td>
            </tr>
          <?php endforeach; ?>
          </tbody>
        </table>
      </div>
    <?php endif; ?>
  </main>

  <?php include "includes/footer.php.inc"; ?>
</div>
</body>
</html>

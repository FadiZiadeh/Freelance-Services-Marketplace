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
    service_id,
    service_title,
    category,
    subcategory,
    price,
    delivery_time,
    revisions_included,
    featured_status,
    status,
    created_date,
    image_1
  FROM services
  WHERE freelancer_id = :fid
  ORDER BY created_date DESC
");
$stmt->execute([':fid' => $fid]);
$services = $stmt->fetchAll();
?>
<!doctype html>
<html>
<head>
  <meta charset="utf-8">
  <title>My Services</title>
  <link rel="stylesheet" href="css/base.css">
</head>
<body>
<div class="page">
  <?php include "includes/header.php.inc"; ?>
  <?php include "includes/nav.php.inc"; ?>

  <main class="main">
    <h1>My Services</h1>

    <?php if ($f = get_flash()): ?>
      <div class="flash <?php echo h($f['type']); ?>"><?php echo h($f['message']); ?></div>
    <?php endif; ?>

    <?php if (!$services): ?>
      <p class="muted">You have no services yet.</p>
    <?php else: ?>
      <div style="overflow:auto; border:1px solid #ddd; border-radius:12px;">
        <table class="tbl" style="width:100%; border-collapse:collapse;">
          <thead>
            <tr>
              <th>Image</th>
              <th>Service</th>
              <th>Category</th>
              <th>Price</th>
              <th>Delivery</th>
              <th>Revisions</th>
              <th>Status</th>
              <th>Featured</th>
              <th>Created</th>
              <th>Action</th>
            </tr>
          </thead>
          <tbody>
          <?php foreach ($services as $s): ?>
            <tr>
              <td style="width:90px;">
                <img src="<?php echo h((string)$s['image_1']); ?>" alt="img"
                     style="width:80px; height:55px; object-fit:cover; border-radius:10px; border:1px solid #ddd;">
              </td>

              <td>
                <strong><?php echo h((string)$s['service_title']); ?></strong><br>
                <span class="muted"><?php echo h((string)$s['service_id']); ?></span>
              </td>

              <td><?php echo h((string)$s['category']); ?> · <?php echo h((string)$s['subcategory']); ?></td>
              <td><?php echo number_format((float)$s['price'], 2); ?> USD</td>
              <td><?php echo (int)$s['delivery_time']; ?> days</td>
              <td><?php echo (int)$s['revisions_included']; ?></td>
              <td><?php echo h((string)$s['status']); ?></td>
              <td><?php echo h((string)$s['featured_status']); ?></td>
              <td><?php echo h((string)$s['created_date']); ?></td>

              <td>
                <a class="btn btn-secondary"
                   href="service-edit.php?id=<?php echo h((string)$s['service_id']); ?>">
                  Edit
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

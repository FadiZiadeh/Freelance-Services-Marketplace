<?php
declare(strict_types=1);

require_once "includes/auth.php.inc";
require_once "includes/db.php.inc";
require_once "includes/flash.php.inc";

require_role('Client');

function h(string $s): string { return htmlspecialchars($s, ENT_QUOTES, 'UTF-8'); }

$clientId = (string)($_SESSION['user_id'] ?? '');
$orderId  = trim((string)($_GET['id'] ?? ''));

if ($orderId === '') {
  set_flash("error", "Missing order id.");
  header("Location: my-orders.php");
  exit;
}

$stmt = $pdo->prepare("
  SELECT
    o.order_id, o.service_id, o.service_title, o.price,
    o.delivery_time, o.revisions_included,
    o.requirements, o.instructions, o.deliverable_notes,
    o.status, o.payment_method,
    o.created_date, o.expected_delivery, o.completion_date,
    o.freelancer_id,
    u.first_name AS freelancer_first, u.last_name AS freelancer_last, u.email AS freelancer_email
  FROM orders o
  JOIN users u ON u.user_id = o.freelancer_id
  WHERE o.order_id = :oid AND o.client_id = :cid
  LIMIT 1
");
$stmt->execute([':oid' => $orderId, ':cid' => $clientId]);
$order = $stmt->fetch();

if (!$order) {
  set_flash("error", "Order not found.");
  header("Location: my-orders.php");
  exit;
}

$reqStmt = $pdo->prepare("
  SELECT file_id, file_path, original_filename, file_size, upload_date
  FROM file_attachments
  WHERE order_id = :oid AND file_type = 'requirement'
  ORDER BY upload_date DESC
");
$reqStmt->execute([':oid' => $orderId]);
$reqFiles = $reqStmt->fetchAll();

$delStmt = $pdo->prepare("
  SELECT file_id, file_path, original_filename, file_size, upload_date
  FROM file_attachments
  WHERE order_id = :oid AND file_type = 'deliverable'
  ORDER BY upload_date DESC
");
$delStmt->execute([':oid' => $orderId]);
$delFiles = $delStmt->fetchAll();
?>
<!doctype html>
<html>
<head>
  <meta charset="utf-8">
  <title>Order Details</title>
  <link rel="stylesheet" href="css/base.css">
</head>
<body>
<div class="page">
  <?php include "includes/header.php.inc"; ?>
  <?php include "includes/nav.php.inc"; ?>

  <main class="main">
    <h1>Order Details</h1>

    <?php if ($f = get_flash()): ?>
      <div class="flash <?php echo h($f['type']); ?>"><?php echo h($f['message']); ?></div>
    <?php endif; ?>

    <div class="split">

      
      <section class="card">
        <h2 style="margin-top:0;"><?php echo h((string)$order['service_title']); ?></h2>

        <div class="muted">
          Order ID: <strong><?php echo h((string)$order['order_id']); ?></strong>
          · Service ID: <?php echo h((string)$order['service_id']); ?>
        </div>

        <hr class="rule">

        <h3>Requirements</h3>
        <div class="pre"><?php echo h((string)$order['requirements']); ?></div>

        <?php if (!empty($order['instructions'])): ?>
          <h3 style="margin-top:14px;">Instructions</h3>
          <div class="pre"><?php echo h((string)$order['instructions']); ?></div>
        <?php endif; ?>

        <?php if (!empty($order['deliverable_notes'])): ?>
          <h3 style="margin-top:14px;">Deliverable Notes</h3>
          <div class="pre"><?php echo h((string)$order['deliverable_notes']); ?></div>
        <?php endif; ?>

        <h3 style="margin-top:18px;">Upload Requirement Files</h3>

        <form action="upload-requirement.php" method="post" enctype="multipart/form-data" style="margin:10px 0;">
          <input type="hidden" name="order_id" value="<?php echo h((string)$order['order_id']); ?>">
          <input type="file" name="req_files[]" multiple required>
          <button class="btn btn-primary" type="submit">Upload</button>
        </form>

        <div class="muted note">
          Allowed: pdf, doc, docx, png, jpg, jpeg, zip (max 5MB each)
        </div>

        <h3 style="margin-top:16px;">Requirement Files</h3>

        <?php if (!$reqFiles): ?>
          <div class="muted">No requirement files uploaded yet.</div>
        <?php else: ?>
          <ul class="list">
            <?php foreach ($reqFiles as $f): ?>
              <li>
                <a href="<?php echo h((string)$f['file_path']); ?>" target="_blank">
                  <?php echo h((string)$f['original_filename']); ?>
                </a>
                <span class="muted">
                  (<?php echo number_format(((int)$f['file_size'])/1024, 0); ?> KB,
                  <?php echo h((string)$f['upload_date']); ?>)
                </span>
              </li>
            <?php endforeach; ?>
          </ul>
        <?php endif; ?>

        <h3 style="margin-top:18px;">Deliverables</h3>

        <?php if (!$delFiles): ?>
          <div class="muted">No deliverables uploaded yet.</div>
        <?php else: ?>
          <ul class="list">
            <?php foreach ($delFiles as $f): ?>
              <li>
                <a href="<?php echo h((string)$f['file_path']); ?>" target="_blank">
                  <?php echo h((string)$f['original_filename']); ?>
                </a>
                <span class="muted">
                  (<?php echo number_format(((int)$f['file_size'])/1024, 0); ?> KB,
                  <?php echo h((string)$f['upload_date']); ?>)
                </span>
              </li>
            <?php endforeach; ?>
          </ul>
        <?php endif; ?>

        <?php if ((string)$order['status'] === 'Delivered'): ?>
          <hr class="rule">

          <h3>Request Revision</h3>
          <form action="revision-request.php" method="post" enctype="multipart/form-data" style="margin-top:10px;">
            <input type="hidden" name="order_id" value="<?php echo h((string)$order['order_id']); ?>">

            <label class="muted">Revision message</label><br>
            <textarea name="revision_message" rows="4" style="width:100%; max-width:700px;"
                      placeholder="Describe what needs to be changed..." required></textarea>

            <div style="margin-top:10px;">
              <label class="muted">Upload revision files (optional)</label><br>
              <input type="file" name="rev_files[]" multiple>
            </div>

            <div style="margin-top:12px;">
              <button class="btn btn-primary" type="submit">Submit Revision Request</button>
            </div>

            <div class="muted note" style="margin-top:8px;">
              Allowed: pdf, doc, docx, png, jpg, jpeg, zip (max 5MB each)
            </div>
          </form>
        <?php endif; ?>

        <div class="actions-row">
          <a class="btn btn-secondary" href="my-orders.php">Back to My Orders</a>
          <a class="btn btn-secondary" href="browse-services.php">Browse Services</a>
        </div>
      </section>

      
      <aside class="card">
        <div style="font-size:1.3rem; font-weight:700;">
          <?php echo number_format((float)$order['price'], 2); ?> USD
        </div>

        <div class="muted" style="margin-top:4px;">
          <?php echo (int)$order['delivery_time']; ?> days delivery ·
          <?php echo (int)$order['revisions_included']; ?> revisions
        </div>

        <hr class="rule">

        <div><strong>Status:</strong> <span class="badge"><?php echo h((string)$order['status']); ?></span></div>
        <div><strong>Payment:</strong> <?php echo h((string)$order['payment_method']); ?></div>
        <div><strong>Created:</strong> <?php echo h((string)$order['created_date']); ?></div>
        <div><strong>Expected delivery:</strong> <?php echo h((string)($order['expected_delivery'] ?? '')); ?></div>

        <?php if (!empty($order['completion_date'])): ?>
          <div><strong>Completed:</strong> <?php echo h((string)$order['completion_date']); ?></div>
        <?php endif; ?>

        <hr class="rule">

        <div><strong>Freelancer:</strong> <?php echo h((string)$order['freelancer_first'].' '.(string)$order['freelancer_last']); ?></div>
        <div class="muted note"><?php echo h((string)$order['freelancer_email']); ?></div>

        <?php if ((string)$order['status'] === 'Pending'): ?>
          <form action="cancel-order.php" method="post" style="margin-top:14px;"
                onsubmit="return confirm('Cancel this order? This cannot be undone.');">
            <input type="hidden" name="order_id" value="<?php echo h((string)$order['order_id']); ?>">
            <button class="btn btn-secondary" type="submit">Cancel Order</button>
          </form>
        <?php endif; ?>

        <?php if ((string)$order['status'] === 'Delivered'): ?>
          <form action="mark-completed.php" method="post" style="margin-top:14px;">
            <input type="hidden" name="order_id" value="<?php echo h((string)$order['order_id']); ?>">
            <button class="btn btn-primary" type="submit">Mark Completed</button>
          </form>
        <?php endif; ?>
      </aside>

    </div>
  </main>

  <?php include "includes/footer.php.inc"; ?>
</div>
</body>
</html>

<?php
declare(strict_types=1);

require_once "includes/auth.php.inc";
require_once "includes/db.php.inc";
require_once "includes/flash.php.inc";

require_role('Freelancer');

function h(string $s): string { return htmlspecialchars($s, ENT_QUOTES, 'UTF-8'); }

$freelancerId = (string)($_SESSION['user_id'] ?? '');
$orderId = trim((string)($_GET['id'] ?? ''));

if ($orderId === '') {
  set_flash("error", "Missing order id.");
  header("Location: freelancer-orders.php");
  exit;
}


$stmt = $pdo->prepare("
  SELECT
    o.order_id, o.service_id, o.service_title, o.price,
    o.delivery_time, o.revisions_included,
    o.requirements, o.instructions, o.deliverable_notes,
    o.status, o.payment_method,
    o.created_date, o.expected_delivery, o.completion_date,
    o.client_id,
    u.first_name AS client_first, u.last_name AS client_last, u.email AS client_email
  FROM orders o
  JOIN users u ON u.user_id = o.client_id
  WHERE o.order_id = :oid AND o.freelancer_id = :fid
  LIMIT 1
");
$stmt->execute([':oid' => $orderId, ':fid' => $freelancerId]);
$order = $stmt->fetch();

if (!$order) {
  set_flash("error", "Order not found.");
  header("Location: index.php");
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

$revStmt = $pdo->prepare("
  SELECT file_id, file_path, original_filename, file_size, upload_date
  FROM file_attachments
  WHERE order_id = :oid AND file_type = 'revision'
  ORDER BY upload_date DESC
");
$revStmt->execute([':oid' => $orderId]);
$revFiles = $revStmt->fetchAll();

?>
<!doctype html>
<html>
<head>
  <meta charset="utf-8">
  <title>Freelancer Order Details</title>
  <link rel="stylesheet" href="css/base.css">
</head>
<body>
<div class="page">
  <?php include "includes/header.php.inc"; ?>
  <?php include "includes/nav.php.inc"; ?>

  <main class="main">
    <h1>Order Details (Freelancer)</h1>

    <?php if ($f = get_flash()): ?>
      <div class="flash <?php echo h($f['type']); ?>"><?php echo h($f['message']); ?></div>
    <?php endif; ?>

    <div style="display:grid; grid-template-columns: 1fr 340px; gap:16px; align-items:start;">

      
      <section style="border:1px solid #ddd; border-radius:12px; padding:14px;">
        <h2 style="margin-top:0;"><?php echo h((string)$order['service_title']); ?></h2>

        <div class="muted" style="margin-top:-6px;">
          Order ID: <strong><?php echo h((string)$order['order_id']); ?></strong>
          · Service ID: <?php echo h((string)$order['service_id']); ?>
        </div>

        <hr style="border:none; border-top:1px solid #eee; margin:12px 0;">

        <h3>Client Requirements</h3>
        <div style="white-space:pre-wrap;"><?php echo h((string)$order['requirements']); ?></div>

        <?php if (!empty($order['instructions'])): ?>
          <h3 style="margin-top:14px;">Instructions</h3>
          <div style="white-space:pre-wrap;"><?php echo h((string)$order['instructions']); ?></div>
        <?php endif; ?>

       
        <h3 style="margin-top:16px;">Requirement Files (Client)</h3>

        <?php if (!$reqFiles): ?>
          <div class="muted">No requirement files uploaded yet.</div>
        <?php else: ?>
          <ul style="margin:8px 0; padding-left:18px;">
            <?php foreach ($reqFiles as $f): ?>
              <li style="margin:6px 0;">
                <a href="<?php echo h((string)$f['file_path']); ?>" target="_blank">
                  <?php echo h((string)$f['original_filename']); ?>
                </a>
                <span class="muted" style="font-size:0.9rem;">
                  (<?php echo number_format(((int)$f['file_size'])/1024, 0); ?> KB,
                  <?php echo h((string)$f['upload_date']); ?>)
                </span>
              </li>
            <?php endforeach; ?>
          </ul>
        <?php endif; ?>

        
        <h3 style="margin-top:16px;">Revision Requests (Client)</h3>

<?php if (!$revFiles): ?>
  <div class="muted">No revision requests yet.</div>
<?php else: ?>
  <ul style="margin:8px 0; padding-left:18px;">
    <?php foreach ($revFiles as $f): ?>
      <li style="margin:6px 0;">
        <a href="<?php echo h((string)$f['file_path']); ?>" target="_blank">
          <?php echo h((string)$f['original_filename']); ?>
        </a>
        <span class="muted" style="font-size:0.9rem;">
          (<?php echo number_format(((int)$f['file_size'])/1024, 0); ?> KB,
          <?php echo h((string)$f['upload_date']); ?>)
        </span>
      </li>
    <?php endforeach; ?>
  </ul>
<?php endif; ?>


        
        <h3 style="margin-top:18px;">Upload Deliverables</h3>

        <form action="upload-deliverable.php" method="post" enctype="multipart/form-data" style="margin:10px 0;">
          <input type="hidden" name="order_id" value="<?php echo h((string)$order['order_id']); ?>">
          <input type="file" name="del_files[]" multiple required>
          <button class="btn btn-primary" type="submit">Upload</button>
        </form>

        <div class="muted" style="font-size:0.95rem;">
          Allowed: pdf, doc, docx, png, jpg, jpeg, zip (max 10MB each)
        </div>

        
        <h3 style="margin-top:16px;">Deliverables (You)</h3>

        <?php if (!$delFiles): ?>
          <div class="muted">No deliverables uploaded yet.</div>
        <?php else: ?>
          <ul style="margin:8px 0; padding-left:18px;">
            <?php foreach ($delFiles as $f): ?>
              <li style="margin:6px 0;">
                <a href="<?php echo h((string)$f['file_path']); ?>" target="_blank">
                  <?php echo h((string)$f['original_filename']); ?>
                </a>
                <span class="muted" style="font-size:0.9rem;">
                  (<?php echo number_format(((int)$f['file_size'])/1024, 0); ?> KB,
                  <?php echo h((string)$f['upload_date']); ?>)
                </span>
              </li>
            <?php endforeach; ?>
          </ul>
        <?php endif; ?>

        <div style="margin-top:16px; display:flex; gap:10px; flex-wrap:wrap;">
          <a class="btn btn-secondary" href="index.php">Back</a>
        </div>
      </section>

      <!-- RIGHT -->
      <aside style="border:1px solid #ddd; border-radius:12px; padding:14px;">
        <div style="font-size:1.3rem; font-weight:700;">
          <?php echo number_format((float)$order['price'], 2); ?> USD
        </div>

        <div class="muted" style="margin-top:4px;">
          <?php echo (int)$order['delivery_time']; ?> days delivery ·
          <?php echo (int)$order['revisions_included']; ?> revisions
        </div>

        <hr style="border:none; border-top:1px solid #eee; margin:12px 0;">

        <div><strong>Status:</strong> <?php echo h((string)$order['status']); ?></div>
        <div><strong>Created:</strong> <?php echo h((string)$order['created_date']); ?></div>
        <div><strong>Expected delivery:</strong> <?php echo h((string)($order['expected_delivery'] ?? '')); ?></div>

        <?php if (!empty($order['completion_date'])): ?>
          <div><strong>Completed:</strong> <?php echo h((string)$order['completion_date']); ?></div>
        <?php endif; ?>

        <hr style="border:none; border-top:1px solid #eee; margin:12px 0;">

        <div><strong>Client:</strong> <?php echo h((string)$order['client_first'].' '.(string)$order['client_last']); ?></div>
        <div class="muted" style="font-size:0.95rem;"><?php echo h((string)$order['client_email']); ?></div>

        <?php if ((string)$order['status'] === 'Pending'): ?>
  <form action="freelancer-set-status.php" method="post" style="margin-top:14px;">
    <input type="hidden" name="order_id" value="<?php echo h((string)$order['order_id']); ?>">
    <input type="hidden" name="status" value="In Progress">
    <button class="btn btn-primary" type="submit">Mark In Progress</button>
  </form>
<?php endif; ?>

      </aside>

    </div>
  </main>

  <?php include "includes/footer.php.inc"; ?>
</div>
</body>
</html>

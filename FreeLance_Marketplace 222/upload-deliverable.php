<?php
declare(strict_types=1);

require_once "includes/auth.php.inc";
require_once "includes/db.php.inc";
require_once "includes/flash.php.inc";

require_role('Freelancer');

$freelancerId = (string)($_SESSION['user_id'] ?? '');
$orderId = trim((string)($_POST['order_id'] ?? ''));

if ($orderId === '') {
  set_flash("error", "Missing order id.");
  header("Location: freelancer-orders.php");
  exit;
}

/*
  1) Security + status check:
     - must belong to THIS freelancer
     - must NOT be Cancelled or Completed
     - (optional) allow only certain statuses
*/
$chk = $pdo->prepare("
  SELECT order_id, status
  FROM orders
  WHERE order_id = :oid AND freelancer_id = :fid
  LIMIT 1
");
$chk->execute([':oid' => $orderId, ':fid' => $freelancerId]);
$orderRow = $chk->fetch();

if (!$orderRow) {
  set_flash("error", "You are not allowed to upload deliverables for this order.");
  header("Location: freelancer-orders.php");
  exit;
}

$currentStatus = (string)$orderRow['status'];

// Block uploads for these statuses
if (in_array($currentStatus, ['Cancelled', 'Completed'], true)) {
  set_flash("error", "Upload disabled: order is {$currentStatus}.");
  header("Location: freelancer-order-details.php?id=" . urlencode($orderId));
  exit;
}

/*
  Optional strict rule:
  only allow upload when order is In Progress or Revision Requested
  (If you want to also allow from Pending, add 'Pending' here)
*/
$allowedStatuses = ['Pending','In Progress', 'Revision Requested', 'Delivered']; // you can remove 'Delivered' if you want stricter
if (!in_array($currentStatus, $allowedStatuses, true)) {
  set_flash("error", "You can upload deliverables only when status is: In Progress / Revision Requested.");
  header("Location: freelancer-order-details.php?id=" . urlencode($orderId));
  exit;
}

/*
  2) Validate files input
*/
if (!isset($_FILES['del_files']) || !is_array($_FILES['del_files']['name'])) {
  set_flash("error", "No files received.");
  header("Location: freelancer-order-details.php?id=" . urlencode($orderId));
  exit;
}

$allowedExt = ['pdf','doc','docx','png','jpg','jpeg','zip'];
$maxBytes = 10 * 1024 * 1024; // 10 MB

$baseDir = __DIR__ . "/uploads/orders/" . $orderId . "/deliverables/";
$publicDir = "uploads/orders/" . $orderId . "/deliverables/";

if (!is_dir($baseDir)) {
  mkdir($baseDir, 0777, true);
}

$names  = $_FILES['del_files']['name'];
$tmp    = $_FILES['del_files']['tmp_name'];
$errors = $_FILES['del_files']['error'];
$sizes  = $_FILES['del_files']['size'];

$insert = $pdo->prepare("
  INSERT INTO file_attachments (order_id, file_path, original_filename, file_size, file_type)
  VALUES (:oid, :path, :orig, :size, 'deliverable')
");

$uploadedCount = 0;

for ($i = 0; $i < count($names); $i++) {
  $origName = (string)$names[$i];
  $tmpPath  = (string)$tmp[$i];
  $err      = (int)$errors[$i];
  $size     = (int)$sizes[$i];

  if ($err === UPLOAD_ERR_NO_FILE) continue;
  if ($err !== UPLOAD_ERR_OK) continue;

  if ($size <= 0 || $size > $maxBytes) continue;

  $ext = strtolower(pathinfo($origName, PATHINFO_EXTENSION));
  if (!in_array($ext, $allowedExt, true)) continue;

  // safe unique filename
  $safeBase = preg_replace('/[^a-zA-Z0-9_\-]/', '_', pathinfo($origName, PATHINFO_FILENAME));
  if ($safeBase === '') $safeBase = 'file';

  $newName = $safeBase . "_" . date("Ymd_His") . "_" . mt_rand(1000, 9999) . "." . $ext;

  $destAbs = $baseDir . $newName;
  $destRel = $publicDir . $newName;

  if (move_uploaded_file($tmpPath, $destAbs)) {
    $insert->execute([
      ':oid'  => $orderId,
      ':path' => $destRel,
      ':orig' => $origName,
      ':size' => $size
    ]);
    $uploadedCount++;
  }
}

if ($uploadedCount <= 0) {
  set_flash("error", "No valid files uploaded. (Check file type/size)");
  header("Location: freelancer-order-details.php?id=" . urlencode($orderId));
  exit;
}

/*
  3) Update order status -> Delivered
  completion_date is DATE => CURDATE()
*/
$upd = $pdo->prepare("
  UPDATE orders
  SET status = 'Delivered', completion_date = CURDATE()
  WHERE order_id = :oid AND freelancer_id = :fid
");
$upd->execute([':oid' => $orderId, ':fid' => $freelancerId]);

set_flash("success", "Deliverables uploaded. Order marked as Delivered.");
header("Location: freelancer-order-details.php?id=" . urlencode($orderId));
exit;

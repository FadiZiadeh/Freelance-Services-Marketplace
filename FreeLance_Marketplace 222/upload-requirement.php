<?php
declare(strict_types=1);

require_once "includes/auth.php.inc";
require_once "includes/db.php.inc";
require_once "includes/flash.php.inc";

require_role('Client');

$clientId = (string)($_SESSION['user_id'] ?? '');
$orderId  = trim((string)($_POST['order_id'] ?? ''));

if ($orderId === '') {
  set_flash("error", "Missing order id.");
  header("Location: my-orders.php");
  exit;
}

// Security: ensure order belongs to this client
$chk = $pdo->prepare("SELECT order_id FROM orders WHERE order_id = :oid AND client_id = :cid LIMIT 1");
$chk->execute([':oid' => $orderId, ':cid' => $clientId]);
if (!$chk->fetch()) {
  set_flash("error", "You are not allowed to upload files for this order.");
  header("Location: my-orders.php");
  exit;
}

if (!isset($_FILES['req_files'])) {
  set_flash("error", "No files selected.");
  header("Location: order-details.php?id=" . urlencode($orderId));
  exit;
}

$allowed = ['pdf','doc','docx','png','jpg','jpeg','zip'];
$maxBytes = 5 * 1024 * 1024; // 5MB each

// folder: uploads/requirements/<order_id>/
$baseDir = __DIR__ . "/uploads/requirements/" . $orderId;
if (!is_dir($baseDir)) {
  if (!mkdir($baseDir, 0755, true)) {
    set_flash("error", "Server error: cannot create upload folder.");
    header("Location: order-details.php?id=" . urlencode($orderId));
    exit;
  }
}

$names = $_FILES['req_files']['name'] ?? [];
$tmp   = $_FILES['req_files']['tmp_name'] ?? [];
$err   = $_FILES['req_files']['error'] ?? [];
$sizes = $_FILES['req_files']['size'] ?? [];

$uploaded = 0;
$failed = 0;

$count = is_array($names) ? count($names) : 0;

for ($i = 0; $i < $count; $i++) {
  $originalName = trim((string)$names[$i]);
  if ($originalName === '') continue;

  $e = (int)($err[$i] ?? UPLOAD_ERR_NO_FILE);
  if ($e !== UPLOAD_ERR_OK) { $failed++; continue; }

  $size = (int)($sizes[$i] ?? 0);
  if ($size <= 0 || $size > $maxBytes) { $failed++; continue; }

  $ext = strtolower(pathinfo($originalName, PATHINFO_EXTENSION));
  if (!in_array($ext, $allowed, true)) { $failed++; continue; }

  $rand = bin2hex(random_bytes(6));
  $filename = "req_" . date("Ymd_His") . "_" . $rand . "." . $ext;

  $destAbs = $baseDir . "/" . $filename;
  $destRel = "uploads/requirements/" . $orderId . "/" . $filename;

  if (!move_uploaded_file((string)$tmp[$i], $destAbs)) { $failed++; continue; }

  // Insert into DB
  $ins = $pdo->prepare("
    INSERT INTO file_attachments (order_id, file_path, original_filename, file_size, file_type)
    VALUES (:oid, :path, :oname, :size, 'requirement')
  ");
  $ins->execute([
    ':oid'   => $orderId,
    ':path'  => $destRel,
    ':oname' => $originalName,
    ':size'  => $size,
  ]);

  $uploaded++;
}

if ($uploaded > 0 && $failed === 0) {
  set_flash("success", "Uploaded {$uploaded} file(s).");
} elseif ($uploaded > 0 && $failed > 0) {
  set_flash("success", "Uploaded {$uploaded} file(s). Skipped {$failed} file(s) (invalid type/size/error).");
} else {
  set_flash("error", "No files were uploaded (invalid type/size/error).");
}

header("Location: order-details.php?id=" . urlencode($orderId));
exit;

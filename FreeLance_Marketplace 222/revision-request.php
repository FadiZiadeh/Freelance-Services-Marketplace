<?php
declare(strict_types=1);

require_once "includes/auth.php.inc";
require_once "includes/db.php.inc";
require_once "includes/flash.php.inc";

require_role('Client');

$clientId = (string)($_SESSION['user_id'] ?? '');
$orderId  = trim((string)($_POST['order_id'] ?? ''));
$msg      = trim((string)($_POST['revision_message'] ?? ''));

if ($orderId === '') {
  set_flash("error", "Missing order id.");
  header("Location: my-orders.php");
  exit;
}


$stmt = $pdo->prepare("SELECT order_id, status FROM orders WHERE order_id=:oid AND client_id=:cid LIMIT 1");
$stmt->execute([':oid' => $orderId, ':cid' => $clientId]);
$order = $stmt->fetch();

if (!$order) {
  set_flash("error", "Order not found.");
  header("Location: my-orders.php");
  exit;
}


if ((string)$order['status'] !== 'Delivered') {
  set_flash("error", "You can request revision only after the order is Delivered.");
  header("Location: order-details.php?id=" . urlencode($orderId));
  exit;
}


$upd = $pdo->prepare("UPDATE orders SET status='Revision Requested' WHERE order_id=:oid AND client_id=:cid");
$upd->execute([':oid' => $orderId, ':cid' => $clientId]);


$baseDir = __DIR__ . "/uploads/revisions";
if (!is_dir($baseDir)) {
  @mkdir($baseDir, 0775, true);
}


$ins = $pdo->prepare("
  INSERT INTO file_attachments (order_id, file_path, original_filename, file_size, file_type)
  VALUES (:oid, :path, :name, :size, 'revision')
");


if ($msg !== '') {
  $safeName = "revision_message_" . date("Ymd_His") . ".txt";
  $relPath  = "uploads/revisions/" . $orderId . "_" . $safeName;
  $absPath  = __DIR__ . "/" . $relPath;

  file_put_contents($absPath, $msg);
  $size = (int)filesize($absPath);

  $ins->execute([
    ':oid'  => $orderId,
    ':path' => $relPath,
    ':name' => $safeName,
    ':size' => $size
  ]);
}

if (!empty($_FILES['rev_files']) && is_array($_FILES['rev_files']['name'])) {
  $allowedExt = ['pdf','doc','docx','png','jpg','jpeg','zip'];
  $maxBytes   = 5 * 1024 * 1024; 

  $count = count($_FILES['rev_files']['name']);
  for ($i = 0; $i < $count; $i++) {
    $err = (int)($_FILES['rev_files']['error'][$i] ?? UPLOAD_ERR_NO_FILE);
    if ($err === UPLOAD_ERR_NO_FILE) continue;
    if ($err !== UPLOAD_ERR_OK) continue;

    $tmp  = (string)$_FILES['rev_files']['tmp_name'][$i];
    $orig = (string)$_FILES['rev_files']['name'][$i];
    $size = (int)($_FILES['rev_files']['size'][$i] ?? 0);

    if ($size <= 0 || $size > $maxBytes) continue;

    $ext = strtolower(pathinfo($orig, PATHINFO_EXTENSION));
    if (!in_array($ext, $allowedExt, true)) continue;

    $safeOrig = preg_replace('/[^a-zA-Z0-9._-]/', '_', $orig);
    $newName  = $orderId . "_rev_" . date("Ymd_His") . "_" . $i . "_" . $safeOrig;

    $relPath = "uploads/revisions/" . $newName;
    $absPath = __DIR__ . "/" . $relPath;

    if (move_uploaded_file($tmp, $absPath)) {
      $ins->execute([
        ':oid'  => $orderId,
        ':path' => $relPath,
        ':name' => $safeOrig,
        ':size' => $size
      ]);
    }
  }
}

set_flash("success", "Revision requested successfully.");
header("Location: order-details.php?id=" . urlencode($orderId));
exit;

<?php
declare(strict_types=1);

require_once "includes/auth.php.inc";
require_once "includes/db.php.inc";
require_once "includes/flash.php.inc";

require_role('Freelancer');

$freelancerId = (string)$_SESSION['user_id'];
$serviceId = trim((string)($_POST['service_id'] ?? ''));

if ($serviceId === '') {
  set_flash("error", "Invalid request.");
  header("Location: my-services.php");
  exit;
}

$data = [
  'service_title' => trim($_POST['service_title']),
  'category' => trim($_POST['category']),
  'subcategory' => trim($_POST['subcategory']),
  'description' => trim($_POST['description']),
  'price' => (float)$_POST['price'],
  'delivery_time' => (int)$_POST['delivery_time'],
  'revisions_included' => (int)$_POST['revisions_included']
];

$imageSql = '';
$params = $data + [
  'sid' => $serviceId,
  'fid' => $freelancerId
];


if (!empty($_FILES['image_1']['name'])) {
  $ext = strtolower(pathinfo($_FILES['image_1']['name'], PATHINFO_EXTENSION));
  $newName = "uploads/services/" . uniqid('svc_') . "." . $ext;
  move_uploaded_file($_FILES['image_1']['tmp_name'], $newName);

  $imageSql = ", image_1 = :image_1";
  $params['image_1'] = $newName;
}

$sql = "
  UPDATE services
  SET
    service_title = :service_title,
    category = :category,
    subcategory = :subcategory,
    description = :description,
    price = :price,
    delivery_time = :delivery_time,
    revisions_included = :revisions_included
    $imageSql
  WHERE service_id = :sid AND freelancer_id = :fid
";

$stmt = $pdo->prepare($sql);
$stmt->execute($params);

set_flash("success", "Service updated successfully.");
header("Location: my-services.php");
exit;

<?php
declare(strict_types=1);

require_once "includes/auth.php.inc";
require_once "includes/db.php.inc";
require_once "includes/flash.php.inc";

require_role('Freelancer');

$fid = (string)($_SESSION['user_id'] ?? '');

function post(string $k): string { return trim((string)($_POST[$k] ?? '')); }

$serviceTitle = post('service_title');
$category     = post('category');
$subcategory  = post('subcategory');
$description  = post('description');

$price = (float)($_POST['price'] ?? 0);
$delivery = (int)($_POST['delivery_time'] ?? 0);
$revisions = (int)($_POST['revisions_included'] ?? -1);

$errors = [];

if ($serviceTitle === '') $errors[] = "Service title is required.";
if ($category === '') $errors[] = "Category is required.";
if ($subcategory === '') $errors[] = "Subcategory is required.";
if ($description === '') $errors[] = "Description is required.";
if ($price <= 0) $errors[] = "Price must be > 0.";
if ($delivery <= 0) $errors[] = "Delivery time must be > 0.";
if ($revisions < 0) $errors[] = "Revisions must be >= 0.";

$allowedCats = ['Programming','Design','Writing','Marketing'];
if ($category !== '' && !in_array($category, $allowedCats, true)) {
  $errors[] = "Invalid category.";
}


$allowedExt = ['jpg','jpeg','png'];
$maxBytes = 3 * 1024 * 1024;

function fileProvided(string $key): bool {
  return isset($_FILES[$key]) && (int)($_FILES[$key]['error'] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_NO_FILE;
}

function validateImage(string $key, array &$errors, array $allowedExt, int $maxBytes, bool $required): void {
  if (!isset($_FILES[$key])) {
    if ($required) $errors[] = "Missing {$key}.";
    return;
  }
  $err = (int)($_FILES[$key]['error'] ?? UPLOAD_ERR_NO_FILE);
  if ($err === UPLOAD_ERR_NO_FILE) {
    if ($required) $errors[] = "Image 1 is required.";
    return;
  }
  if ($err !== UPLOAD_ERR_OK) { $errors[] = "Upload failed for {$key}."; return; }

  $size = (int)($_FILES[$key]['size'] ?? 0);
  if ($size <= 0 || $size > $maxBytes) { $errors[] = "{$key} must be <= 3MB."; return; }

  $name = (string)($_FILES[$key]['name'] ?? '');
  $ext = strtolower(pathinfo($name, PATHINFO_EXTENSION));
  if (!in_array($ext, $allowedExt, true)) { $errors[] = "{$key} must be jpg/jpeg/png."; return; }
}

validateImage('image_1', $errors, $allowedExt, $maxBytes, true);
if (fileProvided('image_2')) validateImage('image_2', $errors, $allowedExt, $maxBytes, false);
if (fileProvided('image_3')) validateImage('image_3', $errors, $allowedExt, $maxBytes, false);


$_SESSION['old_service_form'] = [
  'service_title' => $serviceTitle,
  'category' => $category,
  'subcategory' => $subcategory,
  'description' => $description,
  'price' => (string)$price,
  'delivery_time' => (string)$delivery,
  'revisions_included' => (string)$revisions
];

if ($errors) {
  set_flash("error", implode(" ", $errors));
  header("Location: service-create.php");
  exit;
}


function generateServiceId(PDO $pdo): string {
  for ($i=0; $i<50; $i++) {
    $id = str_pad((string)random_int(1, 9999999999), 10, '0', STR_PAD_LEFT);
    $chk = $pdo->prepare("SELECT service_id FROM services WHERE service_id = :sid LIMIT 1");
    $chk->execute([':sid' => $id]);
    if (!$chk->fetch()) return $id;
  }
 
  return (string)time();
}

$serviceId = generateServiceId($pdo);


$dirAbs = __DIR__ . "/uploads/services/" . $serviceId . "/";
$dirRel = "uploads/services/" . $serviceId . "/";
if (!is_dir($dirAbs)) {
  mkdir($dirAbs, 0777, true);
}

function moveImage(string $key, string $dirAbs, string $dirRel): ?string {
  if (!isset($_FILES[$key])) return null;
  $err = (int)($_FILES[$key]['error'] ?? UPLOAD_ERR_NO_FILE);
  if ($err === UPLOAD_ERR_NO_FILE) return null;
  if ($err !== UPLOAD_ERR_OK) return null;

  $orig = (string)$_FILES[$key]['name'];
  $ext = strtolower(pathinfo($orig, PATHINFO_EXTENSION));
  $newName = $key . "_" . date("Ymd_His") . "_" . mt_rand(1000,9999) . "." . $ext;

  $abs = $dirAbs . $newName;
  $rel = $dirRel . $newName;

  return move_uploaded_file((string)$_FILES[$key]['tmp_name'], $abs) ? $rel : null;
}

$img1 = moveImage('image_1', $dirAbs, $dirRel);
$img2 = moveImage('image_2', $dirAbs, $dirRel);
$img3 = moveImage('image_3', $dirAbs, $dirRel);

if (!$img1) {
  set_flash("error", "Could not save Image 1.");
  header("Location: service-create.php");
  exit;
}


$ins = $pdo->prepare("
  INSERT INTO services
    (service_id, freelancer_id, service_title, category, subcategory, description,
     price, delivery_time, revisions_included, image_1, image_2, image_3, status, featured_status)
  VALUES
    (:sid, :fid, :title, :cat, :sub, :descr,
     :price, :del, :rev, :img1, :img2, :img3, 'Active', 'No')
");
$ins->execute([
  ':sid' => $serviceId,
  ':fid' => $fid,
  ':title' => $serviceTitle,
  ':cat' => $category,
  ':sub' => $subcategory,
  ':descr' => $description,
  ':price' => $price,
  ':del' => $delivery,
  ':rev' => $revisions,
  ':img1' => $img1,
  ':img2' => $img2,
  ':img3' => $img3
]);

unset($_SESSION['old_service_form']);
set_flash("success", "Service created successfully.");
header("Location: my-services.php");
exit;

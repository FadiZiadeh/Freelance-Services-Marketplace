<?php
declare(strict_types=1);

require_once "includes/auth.php.inc";
require_once "includes/db.php.inc";
require_once "includes/flash.php.inc";

require_role('Freelancer');

function h(string $s): string {
  return htmlspecialchars($s, ENT_QUOTES, 'UTF-8');
}

$freelancerId = (string)$_SESSION['user_id'];
$serviceId = trim((string)($_GET['id'] ?? ''));

if ($serviceId === '') {
  set_flash("error", "Missing service ID.");
  header("Location: my-services.php");
  exit;
}

$stmt = $pdo->prepare("
  SELECT *
  FROM services
  WHERE service_id = :sid AND freelancer_id = :fid
  LIMIT 1
");
$stmt->execute([
  ':sid' => $serviceId,
  ':fid' => $freelancerId
]);
$service = $stmt->fetch();

if (!$service) {
  set_flash("error", "Service not found.");
  header("Location: my-services.php");
  exit;
}
?>
<!doctype html>
<html>
<head>
  <meta charset="utf-8">
  <title>Edit Service</title>
  <link rel="stylesheet" href="css/base.css">
</head>
<body>
<div class="page">
<?php include "includes/header.php.inc"; ?>
<?php include "includes/nav.php.inc"; ?>

<main class="main">
<h1>Edit Service</h1>

<?php if ($f = get_flash()): ?>
  <div class="flash <?php echo h($f['type']); ?>">
    <?php echo h($f['message']); ?>
  </div>
<?php endif; ?>

<form action="service-edit-handler.php" method="post" enctype="multipart/form-data">

<input type="hidden" name="service_id" value="<?php echo h($service['service_id']); ?>">

<label>Service Title *</label>
<input type="text" name="service_title" required
       value="<?php echo h($service['service_title']); ?>">

<label>Category *</label>
<input type="text" name="category" required
       value="<?php echo h($service['category']); ?>">

<label>Subcategory *</label>
<input type="text" name="subcategory" required
       value="<?php echo h($service['subcategory']); ?>">

<label>Description *</label>
<textarea name="description" required><?php
  echo h($service['description']);
?></textarea>

<label>Price (USD) *</label>
<input type="number" step="0.01" name="price" required
       value="<?php echo h((string)$service['price']); ?>">

<label>Delivery Time (days) *</label>
<input type="number" name="delivery_time" required
       value="<?php echo h((string)$service['delivery_time']); ?>">

<label>Revisions Included *</label>
<input type="number" name="revisions_included" required
       value="<?php echo h((string)$service['revisions_included']); ?>">

<label>Replace Image (optional)</label>
<input type="file" name="image_1">

<button class="btn btn-primary">Update Service</button>
<a class="btn btn-secondary" href="my-services.php">Cancel</a>

</form>
</main>

<?php include "includes/footer.php.inc"; ?>
</div>
</body>
</html>

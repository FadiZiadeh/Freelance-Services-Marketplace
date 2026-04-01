<?php
declare(strict_types=1);

require_once "includes/auth.php.inc";
require_once "includes/flash.php.inc";

require_role('Freelancer');

function h(string $s): string { return htmlspecialchars($s, ENT_QUOTES, 'UTF-8'); }

$old = $_SESSION['old_service_form'] ?? [];
unset($_SESSION['old_service_form']);
?>
<!doctype html>
<html>
<head>
  <meta charset="utf-8">
  <title>Create Service</title>
  <link rel="stylesheet" href="css/base.css">
</head>
<body>
<div class="page">
  <?php include "includes/header.php.inc"; ?>
  <?php include "includes/nav.php.inc"; ?>

  <main class="main">
    <h1>Create Service</h1>

    <?php if ($f = get_flash()): ?>
      <div class="flash <?php echo h($f['type']); ?>"><?php echo h($f['message']); ?></div>
    <?php endif; ?>

    <section style="border:1px solid #ddd; border-radius:12px; padding:14px; max-width:850px;">
      <form action="service-create-handler.php" method="post" enctype="multipart/form-data" novalidate>

        <label>Service Title *</label>
        <input type="text" name="service_title" required
               value="<?php echo h((string)($old['service_title'] ?? '')); ?>"
               placeholder="e.g., I will design a modern logo for your brand">

        <div style="display:grid; grid-template-columns: 1fr 1fr; gap:12px; margin-top:10px;">
          <div>
            <label>Category *</label>
            <select name="category" required>
              <?php
              $cat = (string)($old['category'] ?? '');
              $cats = ['Programming','Design','Writing','Marketing'];
              ?>
              <option value="">Choose...</option>
              <?php foreach ($cats as $c): ?>
                <option value="<?php echo h($c); ?>" <?php echo ($cat===$c?'selected':''); ?>>
                  <?php echo h($c); ?>
                </option>
              <?php endforeach; ?>
            </select>
          </div>

          <div>
            <label>Subcategory *</label>
            <input type="text" name="subcategory" required
                   value="<?php echo h((string)($old['subcategory'] ?? '')); ?>"
                   placeholder="e.g., Web Development / Logo Design / SEO">
          </div>
        </div>

        <label style="margin-top:10px;">Description *</label>
        <textarea name="description" rows="6" required
          placeholder="Describe what you will deliver..."><?php echo h((string)($old['description'] ?? '')); ?></textarea>

        <div style="display:grid; grid-template-columns: 1fr 1fr 1fr; gap:12px; margin-top:10px;">
          <div>
            <label>Price (USD) *</label>
            <input type="number" step="0.01" min="1" name="price" required
                   value="<?php echo h((string)($old['price'] ?? '')); ?>">
          </div>

          <div>
            <label>Delivery Time (days) *</label>
            <input type="number" min="1" name="delivery_time" required
                   value="<?php echo h((string)($old['delivery_time'] ?? '')); ?>">
          </div>

          <div>
            <label>Revisions Included *</label>
            <input type="number" min="0" name="revisions_included" required
                   value="<?php echo h((string)($old['revisions_included'] ?? '')); ?>">
          </div>
        </div>

        <hr style="border:none; border-top:1px solid #eee; margin:16px 0;">

        <h3 style="margin:0 0 10px;">Images</h3>
        <div class="muted" style="margin-bottom:10px;">Image 1 is required. Allowed: jpg, jpeg, png (max 3MB each).</div>

        <div style="display:grid; grid-template-columns: 1fr 1fr 1fr; gap:12px;">
          <div>
            <label>Image 1 *</label>
            <input type="file" name="image_1" accept=".jpg,.jpeg,.png" required>
          </div>
          <div>
            <label>Image 2 (optional)</label>
            <input type="file" name="image_2" accept=".jpg,.jpeg,.png">
          </div>
          <div>
            <label>Image 3 (optional)</label>
            <input type="file" name="image_3" accept=".jpg,.jpeg,.png">
          </div>
        </div>

        <div style="margin-top:16px; display:flex; gap:10px; flex-wrap:wrap;">
          <button class="btn btn-primary" type="submit">Create Service</button>
          <a class="btn btn-secondary" href="my-services.php">Back to My Services</a>
        </div>

      </form>
    </section>
  </main>

  <?php include "includes/footer.php.inc"; ?>
</div>
</body>
</html>

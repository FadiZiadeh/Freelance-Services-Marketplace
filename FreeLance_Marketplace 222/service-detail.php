<?php
declare(strict_types=1);

require_once "includes/auth.php.inc";
require_once "includes/db.php.inc";
require_once "includes/flash.php.inc";

function h(string $s): string { return htmlspecialchars($s, ENT_QUOTES, 'UTF-8'); }

$serviceId = trim((string)($_GET['id'] ?? ''));
if ($serviceId === '') {
  header("Location: browse-services.php");
  exit;
}

$stmt = $pdo->prepare("
  SELECT
    s.service_id, s.freelancer_id, s.service_title, s.category, s.subcategory, s.description,
    s.price, s.delivery_time, s.revisions_included,
    s.image_1, s.image_2, s.image_3,
    s.featured_status, s.status, s.created_date,
    u.first_name, u.last_name
  FROM services s
  JOIN users u ON u.user_id = s.freelancer_id
  WHERE s.service_id = :sid
  LIMIT 1
");
$stmt->execute([':sid' => $serviceId]);
$sv = $stmt->fetch();

if (!$sv) {
  set_flash("error", "Service not found.");
  header("Location: browse-services.php");
  exit;
}




$isOwner = is_logged_in() && (current_role() === 'Freelancer') && (current_user_id() === $sv['freelancer_id']);
if ($sv['status'] !== 'Active' && !$isOwner) {
  set_flash("error", "This service is not available.");
  header("Location: browse-services.php");
  exit;
}


$cookieName = "recent_services";
$maxItems = 4;

$recent = [];
if (isset($_COOKIE[$cookieName])) {
  $parts = array_filter(array_map('trim', explode(',', (string)$_COOKIE[$cookieName])));
 
  foreach ($parts as $p) {
    if ($p !== '' && !in_array($p, $recent, true)) $recent[] = $p;
  }
}

$recent = array_values(array_filter($recent, fn($x) => $x !== $serviceId));
array_unshift($recent, $serviceId);
$recent = array_slice($recent, 0, $maxItems);


setcookie($cookieName, implode(',', $recent), [
  'expires' => time() + 60*60*24*30,
  'path' => '/',
  'samesite' => 'Lax'
]);


$images = [];
if (!empty($sv['image_1'])) $images[] = $sv['image_1'];
if (!empty($sv['image_2'])) $images[] = $sv['image_2'];
if (!empty($sv['image_3'])) $images[] = $sv['image_3'];
$mainImg = $images[0] ?? 'uploads/services/test.jpg';
?>
<!doctype html>
<html>
<head>
  <meta charset="utf-8">
  <title><?php echo h((string)$sv['service_title']); ?></title>
  <link rel="stylesheet" href="css/base.css">
  <style>
    .wrap { display:grid; grid-template-columns: 60% 40%; gap: 16px; }
    .panel { border:1px solid #ddd; border-radius: 14px; padding: 14px; background: #fff; }
    .gallery { display:grid; grid-template-columns: 1fr; gap: 10px; }
    .mainimg { height: 320px; border:1px solid #eee; border-radius: 14px; overflow:hidden; }
    .mainimg img { width:100%; height:100%; object-fit:cover; display:block; }
    .thumbs { display:flex; gap: 10px; flex-wrap:wrap; }
    .thumbs a { display:block; width:90px; height:64px; border:1px solid #ddd; border-radius: 12px; overflow:hidden; }
    .thumbs img { width:100%; height:100%; object-fit:cover; display:block; }

    .title { font-size: 1.6rem; margin: 0 0 8px; }
    .muted { color:#666; }
    .badges { display:flex; gap:8px; flex-wrap:wrap; }
    .badge { display:inline-block; padding:2px 10px; border:1px solid #999; border-radius:999px; font-size: 0.85rem; }
    .price { font-size: 1.4rem; font-weight: 800; }
    .cta { display:flex; gap:10px; flex-wrap:wrap; margin-top: 12px; }
  </style>
</head>
<body>
<div class="page">
  <?php include "includes/header.php.inc"; ?>
  <?php include "includes/nav.php.inc"; ?>

  <main class="main">
    <?php if ($f = get_flash()): ?>
      <div class="flash <?php echo h($f['type']); ?>"><?php echo h($f['message']); ?></div>
    <?php endif; ?>

    <?php if ($sv['status'] !== 'Active' && $isOwner): ?>
      <div class="flash error">This service is currently Inactive (only you can see it).</div>
    <?php endif; ?>

    <div class="wrap">
      
      <section class="panel">
        <h1 class="title"><?php echo h((string)$sv['service_title']); ?></h1>
        <div class="muted">
          <?php echo h((string)$sv['category']); ?> • <?php echo h((string)$sv['subcategory']); ?>
          • by <?php echo h((string)$sv['first_name'] . ' ' . (string)$sv['last_name']); ?>
        </div>

        <div class="gallery" style="margin-top:12px;">
          <div class="gallery">

  <?php foreach ($images as $i => $img): ?>
    <div class="mainimg target-img" id="img<?php echo $i; ?>">
      <img src="<?php echo h($img); ?>" alt="Service image">
    </div>
  <?php endforeach; ?>

  <div class="thumbs">
    <?php foreach ($images as $i => $img): ?>
      <a href="#img<?php echo $i; ?>">
        <img src="<?php echo h($img); ?>" alt="thumb">
      </a>
    <?php endforeach; ?>
  </div>

</div>


          <?php if (count($images) > 1): ?>
            <div class="thumbs">
              <?php foreach ($images as $idx => $img): ?>
                  <img src="<?php echo h($img); ?>" alt="thumb">
                </a>
              <?php endforeach; ?>
            </div>
          <?php endif; ?>
        </div>

        <h3 style="margin-top:14px;">Description</h3>
        <p><?php echo nl2br(h((string)$sv['description'])); ?></p>
      </section>

      
      <aside class="panel">
        <div class="price"><?php echo h((string)$sv['price']); ?> USD</div>
        <div class="muted"><?php echo h((string)$sv['delivery_time']); ?> days delivery • <?php echo h((string)$sv['revisions_included']); ?> revisions</div>

        <div class="badges" style="margin-top:10px;">
          <?php if ($sv['featured_status'] === 'Yes'): ?><span class="badge">Featured</span><?php endif; ?>
          <span class="badge"><?php echo h((string)$sv['status']); ?></span>
        </div>

        <div class="cta">
          <?php if (!is_logged_in()): ?>
            <a class="btn btn-primary" href="login.php">Login to Order</a>

          <?php elseif (current_role() === 'Client'): ?>
            
            <a class="btn btn-secondary" href="cart-add.php?id=<?php echo h($sv['service_id']); ?>">Add to Cart</a>
            <a class="btn btn-primary" href="order-now.php?id=<?php echo h($sv['service_id']); ?>">Order Now</a>

          <?php elseif ($isOwner): ?>
            <a class="btn btn-primary" href="edit-service.php?id=<?php echo h($sv['service_id']); ?>">Edit Service</a>

          <?php else: ?>
            <div class="muted">You are logged in as a Freelancer.</div>
          <?php endif; ?>
        </div>

        <div style="margin-top:14px;">
          <a class="btn btn-secondary" href="browse-services.php">Back to Browse</a>
        </div>
      </aside>
    </div>
  </main>

  <?php include "includes/footer.php.inc"; ?>
</div>
</body>
</html>

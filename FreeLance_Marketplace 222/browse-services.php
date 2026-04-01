<?php
declare(strict_types=1);

require_once "includes/auth.php.inc";
require_once "includes/db.php.inc";
require_once "includes/flash.php.inc";


function get(string $k): string {
  return trim((string)($_GET[$k] ?? ''));
}
function h(string $s): string {
  return htmlspecialchars($s, ENT_QUOTES, 'UTF-8');
}


$q           = get('q');           
$category    = get('category');    
$subcategory = get('subcategory'); 
$sort        = get('sort');        
$page        = (int)($_GET['page'] ?? 1);
if ($page < 1) $page = 1;

$perPage = 12; 
$offset  = ($page - 1) * $perPage;


$where = ["s.status = 'Active'"];
$params = [];

if ($q !== '') {
  $where[] = "(s.service_title LIKE :q OR s.description LIKE :q)";
  $params[':q'] = "%{$q}%";
}
if ($category !== '') {
  $where[] = "s.category = :category";
  $params[':category'] = $category;
}
if ($subcategory !== '') {
  $where[] = "s.subcategory = :subcategory";
  $params[':subcategory'] = $subcategory;
}

$whereSql = "WHERE " . implode(" AND ", $where);


$orderBy = "ORDER BY s.created_date DESC";
switch ($sort) {
  case 'price_asc':      $orderBy = "ORDER BY s.price ASC"; break;
  case 'price_desc':     $orderBy = "ORDER BY s.price DESC"; break;
  case 'delivery_asc':   $orderBy = "ORDER BY s.delivery_time ASC"; break;
  case 'delivery_desc':  $orderBy = "ORDER BY s.delivery_time DESC"; break;
  case 'newest':
  default:               $orderBy = "ORDER BY s.created_date DESC"; break;
}


$catRows = $pdo->query("SELECT DISTINCT category FROM services ORDER BY category")->fetchAll();
$subRows = $pdo->query("SELECT DISTINCT subcategory FROM services ORDER BY subcategory")->fetchAll();


$countSql = "SELECT COUNT(*) FROM services s {$whereSql}";
$stmtCount = $pdo->prepare($countSql);
$stmtCount->execute($params);
$total = (int)$stmtCount->fetchColumn();
$totalPages = (int)ceil($total / $perPage);
if ($totalPages < 1) $totalPages = 1;
if ($page > $totalPages) $page = $totalPages;


$sql = "
  SELECT
    s.service_id, s.service_title, s.category, s.subcategory, s.description,
    s.price, s.delivery_time, s.revisions_included, s.image_1, s.featured_status,
    s.created_date,
    u.first_name, u.last_name
  FROM services s
  JOIN users u ON u.user_id = s.freelancer_id
  {$whereSql}
  {$orderBy}
  LIMIT :limit OFFSET :offset
";
$stmt = $pdo->prepare($sql);


foreach ($params as $k => $v) {
  $stmt->bindValue($k, $v, PDO::PARAM_STR);
}

$stmt->bindValue(':limit', $perPage, PDO::PARAM_INT);
$stmt->bindValue(':offset', $offset, PDO::PARAM_INT);

$stmt->execute();
$services = $stmt->fetchAll();


function build_query(array $override = []): string {
  $base = $_GET;
  foreach ($override as $k => $v) $base[$k] = $v;
  return http_build_query($base);
}
?>
<!doctype html>
<html>
<head>
  <meta charset="utf-8">
  <title>Browse Services</title>
  <link rel="stylesheet" href="css/base.css">
  <style>
    
    .filters { display:flex; gap:12px; flex-wrap:wrap; align-items:end; padding:12px; border:1px solid #ddd; border-radius:12px; margin-bottom:16px; }
    .filters .grp { display:flex; flex-direction:column; gap:6px; min-width: 180px; }
    .filters input, .filters select { padding:10px; border:1px solid #ccc; border-radius:10px; width:100%; }

    .grid { display:grid; grid-template-columns: repeat(4, minmax(0, 1fr)); gap: 14px; }
    @media (max-width: 1200px) { .grid { grid-template-columns: repeat(3, 1fr); } }
    @media (max-width: 900px)  { .grid { grid-template-columns: repeat(2, 1fr); } }
    @media (max-width: 600px)  { .grid { grid-template-columns: 1fr; } }

    .card { border:1px solid #ddd; border-radius:14px; overflow:hidden; display:flex; flex-direction:column; background:#fff; }
    .card .img { height:140px; overflow:hidden; border-bottom:1px solid #eee; display:flex; align-items:center; justify-content:center; }
    .card .img img { width:100%; height:100%; object-fit:cover; display:block; }
    .card .body { padding:12px; display:flex; flex-direction:column; gap:8px; }
    .muted { color:#666; font-size:0.9rem; }
    .price { font-weight:700; font-size:1.05rem; }
    .badge { display:inline-block; padding:2px 8px; border:1px solid #999; border-radius:999px; font-size:0.85rem; }
    .pager { margin-top:16px; display:flex; gap:8px; align-items:center; flex-wrap:wrap; }
    .pager a { padding:8px 10px; border:1px solid #ccc; border-radius:10px; text-decoration:none; }
  </style>
</head>
<body>
<div class="page">
  <?php include "includes/header.php.inc"; ?>
  <?php include "includes/nav.php.inc"; ?>

  <main class="main">
    <h1>Browse Services</h1>

    <?php if ($f = get_flash()): ?>
      <div class="flash <?php echo h($f['type']); ?>"><?php echo h($f['message']); ?></div>
    <?php endif; ?>

    
    <form class="filters" method="get" action="browse-services.php">
      <div class="grp">
        <label>Search</label>
        <input type="text" name="q" value="<?php echo h($q); ?>" placeholder="Title or description">
      </div>

      <div class="grp">
        <label>Category</label>
        <select name="category">
          <option value="">All</option>
          <?php foreach ($catRows as $r): $c = (string)$r['category']; ?>
            <option value="<?php echo h($c); ?>" <?php echo ($c === $category) ? 'selected' : ''; ?>>
              <?php echo h($c); ?>
            </option>
          <?php endforeach; ?>
        </select>
      </div>

      <div class="grp">
        <label>Subcategory</label>
        <select name="subcategory">
          <option value="">All</option>
          <?php foreach ($subRows as $r): $s = (string)$r['subcategory']; ?>
            <option value="<?php echo h($s); ?>" <?php echo ($s === $subcategory) ? 'selected' : ''; ?>>
              <?php echo h($s); ?>
            </option>
          <?php endforeach; ?>
        </select>
      </div>

      <div class="grp">
        <label>Sort</label>
        <select name="sort">
          <option value="newest" <?php echo ($sort==='newest' || $sort==='')?'selected':''; ?>>Newest</option>
          <option value="price_asc" <?php echo ($sort==='price_asc')?'selected':''; ?>>Price: Low → High</option>
          <option value="price_desc" <?php echo ($sort==='price_desc')?'selected':''; ?>>Price: High → Low</option>
          <option value="delivery_asc" <?php echo ($sort==='delivery_asc')?'selected':''; ?>>Delivery: Fastest</option>
          <option value="delivery_desc" <?php echo ($sort==='delivery_desc')?'selected':''; ?>>Delivery: Slowest</option>
        </select>
      </div>

      <div class="grp">
        <button class="btn btn-primary" type="submit">Apply</button>
        <a class="btn btn-secondary" href="browse-services.php">Reset</a>
      </div>
    </form>

    <p class="muted"><?php echo $total; ?> result(s)</p>

    <?php if (!$services): ?>
      <p>No services found.</p>
    <?php else: ?>
      <section class="grid">
        <?php foreach ($services as $sv): ?>
          <article class="card">
            <div class="img">
              <img src="<?php echo h($sv['image_1']); ?>" alt="Service image">
            </div>
            <div class="body">
              <div>
                <strong><?php echo h($sv['service_title']); ?></strong><br>
                <span class="muted"><?php echo h($sv['category']); ?> • <?php echo h($sv['subcategory']); ?></span>
              </div>

              <div class="muted">
                by <?php echo h($sv['first_name'].' '.$sv['last_name']); ?>
              </div>

              <div>
                <span class="price"><?php echo h((string)$sv['price']); ?> USD</span>
                <span class="muted"> • <?php echo h((string)$sv['delivery_time']); ?> days</span>
              </div>

              <div>
                <?php if ($sv['featured_status'] === 'Yes'): ?>
                  <span class="badge">Featured</span>
                <?php endif; ?>
                <span class="badge"><?php echo h((string)$sv['revisions_included']); ?> revisions</span>
              </div>

              <div>
                <a class="btn btn-secondary" href="service-detail.php?id=<?php echo h($sv['service_id']); ?>">View</a>
              </div>
            </div>
          </article>
        <?php endforeach; ?>
      </section>

      
      <div class="pager">
        <?php if ($page > 1): ?>
          <a href="browse-services.php?<?php echo h(build_query(['page' => $page - 1])); ?>">← Prev</a>
        <?php endif; ?>

        <span class="muted">Page <?php echo $page; ?> of <?php echo $totalPages; ?></span>

        <?php if ($page < $totalPages): ?>
          <a href="browse-services.php?<?php echo h(build_query(['page' => $page + 1])); ?>">Next →</a>
        <?php endif; ?>
      </div>
    <?php endif; ?>

  </main>

  <?php include "includes/footer.php.inc"; ?>
</div>
</body>
</html>

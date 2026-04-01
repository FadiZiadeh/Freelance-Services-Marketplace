<?php
require_once "includes/auth.php.inc";
require_once "includes/flash.php.inc";
?>
<!doctype html>
<html>
<head>
  <meta charset="utf-8">
  <title>Home</title>
  <link rel="stylesheet" href="css/base.css">
</head>
<body>
<div class="page">
  <?php include "includes/header.php.inc"; ?>
  <?php include "includes/nav.php.inc"; ?>

  <main class="main">
    <?php if ($f = get_flash()): ?>
      <div class="flash <?php echo htmlspecialchars($f['type']); ?>">
        <?php echo htmlspecialchars($f['message']); ?>
      </div>
    <?php endif; ?>

    <h1>Welcome</h1>
    <p>Use the search bar to find services.</p>
  </main>

  <?php include "includes/footer.php.inc"; ?>
</div>
</body>
</html>

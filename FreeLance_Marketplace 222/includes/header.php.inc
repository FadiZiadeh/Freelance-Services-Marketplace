<?php
// includes/header.php.inc
require_once __DIR__ . "/auth.php.inc";
?>
<header class="site-header">
  <div class="header-left">
    <a class="logo" href="index.php">Freelance Marketplace</a>
  </div>

  <div class="header-center">
    <form class="search-form" method="get" action="browse-services.php">
      <input type="text" name="q" placeholder="Search services..." minlength="1">
      <button type="submit" class="btn btn-primary">Search</button>
    </form>
  </div>

  <div class="header-right">
    <?php if (!is_logged_in()): ?>
      <a class="btn btn-secondary" href="login.php">Login</a>
      <a class="btn btn-primary" href="register.php">Sign Up</a>
    <?php else: ?>
      <div class="profile-mini <?php echo (current_role()==='Client') ? 'client' : 'freelancer'; ?>">
        <span class="mini-name"><?php echo htmlspecialchars($_SESSION['first_name'] ?? 'User'); ?></span>
        <span class="mini-role"><?php echo htmlspecialchars($_SESSION['role']); ?></span>
      </div>
      <?php if (current_role() === 'Client'): ?>
        <a class="cart-link" href="cart.php">
          🛒 <?php
$cartCount = 0;
if (isset($_SESSION['cart']) && is_array($_SESSION['cart'])) {
  $cartCount = count($_SESSION['cart']);
}
?>
<span class="badge"><?php echo $cartCount; ?></span>
        </a>
      <?php endif; ?>
      <a class="btn btn-secondary" href="logout.php">Logout</a>
    <?php endif; ?>
  </div>
</header>

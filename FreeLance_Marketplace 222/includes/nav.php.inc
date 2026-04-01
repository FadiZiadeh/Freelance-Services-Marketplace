<?php

require_once __DIR__ . "/auth.php.inc";
$current = basename($_SERVER['PHP_SELF']);
function active(string $file, string $current): string {
    return ($file === $current) ? 'active' : '';
}
?>
<nav class="site-nav">
  <a class="<?php echo active('index.php',$current); ?>" href="index.php">Home</a>

  <?php if (!is_logged_in()): ?>
    <a class="<?php echo active('browse-services.php',$current); ?>" href="browse-services.php">Browse Services</a>
  <?php elseif (current_role() === 'Client'): ?>
    <a class="<?php echo active('browse-services.php',$current); ?>" href="browse-services.php">Browse Services</a>
    <a class="<?php echo active('my-orders.php',$current); ?>" href="my-orders.php">My Orders</a>
    <a class="<?php echo active('profile.php',$current); ?>" href="profile.php">Profile</a>
  <?php else: ?>
    <a class="<?php echo active('my-services.php',$current); ?>" href="my-services.php">My Services</a>
	<a href="service-create.php">Create Service</a>
    <a class="<?php echo active('my-orders.php',$current); ?>" href="freelancer-orders.php">Orders</a>
    <a class="<?php echo active('profile.php',$current); ?>" href="profile.php">Profile</a>
  <?php endif; ?>
</nav>

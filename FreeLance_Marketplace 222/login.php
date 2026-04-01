<?php
declare(strict_types=1);

require_once "includes/auth.php.inc";
require_once "includes/db.php.inc";
require_once "includes/flash.php.inc";

$errors = [];
$email = '';

function post(string $k): string {
  return trim((string)($_POST[$k] ?? ''));
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

  if (login_is_locked()) {
    $secs = login_lock_remaining_seconds();
    $mins = (int)ceil($secs / 60);
    $errors['general'] = "Too many failed attempts. Try again in {$mins} minute(s).";
  } else {

    $email = post('email');
    $password = (string)($_POST['password'] ?? '');

    if ($email === '') $errors['email'] = "Email is required.";
    if ($password === '') $errors['password'] = "Password is required.";
    if ($email !== '' && !filter_var($email, FILTER_VALIDATE_EMAIL)) $errors['email'] = "Invalid email.";

    if (!$errors) {
      $stmt = $pdo->prepare("SELECT * FROM users WHERE email = :email LIMIT 1");
      $stmt->execute([':email' => $email]);
      $u = $stmt->fetch();

      if (!$u || !password_verify($password, $u['password_hash'])) {
        login_register_failure();
        $errors['general'] = "Invalid email or password.";
      } elseif ($u['status'] !== 'Active') {
        $errors['general'] = "Your account is inactive.";
      } else {
        login_clear_failures(); 

        $_SESSION['user_id'] = $u['user_id'];
        $_SESSION['role'] = $u['role'];
        $_SESSION['first_name'] = $u['first_name'];
        $_SESSION['last_name'] = $u['last_name'];
        $_SESSION['email'] = $u['email'];
        $_SESSION['profile_photo'] = $u['profile_photo'] ?? '';

        
        $_SESSION['cart'] = $_SESSION['cart'] ?? [];
        $_SESSION['cart_count'] = is_array($_SESSION['cart']) ? count($_SESSION['cart']) : 0;

        set_flash("success", "Welcome back, ".$u['first_name']."!");
        header("Location: index.php");
        exit;
      }
    }
  }
}
?>
<!doctype html>
<html>
<head>
  <meta charset="utf-8">
  <title>Login</title>
  <link rel="stylesheet" href="css/base.css">
</head>
<body>
<div class="page">
  <?php include "includes/header.php.inc"; ?>
  <?php include "includes/nav.php.inc"; ?>

  <main class="main">
    <h1>Login</h1>

    <?php if (isset($errors['general'])): ?>
      <div class="flash error"><?php echo htmlspecialchars($errors['general']); ?></div>
    <?php endif; ?>

    <form method="post" novalidate>
      <label>Email *</label>
      <input type="email" name="email" value="<?php echo htmlspecialchars($email); ?>">
      <?php if (isset($errors['email'])): ?><div class="err"><?php echo htmlspecialchars($errors['email']); ?></div><?php endif; ?>

      <label>Password *</label>
      <input type="password" name="password" value="">
      <?php if (isset($errors['password'])): ?><div class="err"><?php echo htmlspecialchars($errors['password']); ?></div><?php endif; ?>

      <div style="margin-top:12px;">
        <button class="btn btn-primary" type="submit">Login</button>
        <a class="btn btn-secondary" href="register.php">Sign Up</a>
      </div>
    </form>
  </main>

  <?php include "includes/footer.php.inc"; ?>
</div>
</body>
</html>

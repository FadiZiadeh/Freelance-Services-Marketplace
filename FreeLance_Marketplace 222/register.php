<?php
declare(strict_types=1);

ini_set('display_errors', '1');
ini_set('display_startup_errors', '1');
error_reporting(E_ALL);

require_once "includes/auth.php.inc";
require_once "includes/db.php.inc";
require_once "includes/flash.php.inc";
require_once "includes/id_gen.php.inc";

$errors = [];
$values = [
  'first_name' => '',
  'last_name'  => '',
  'email'      => '',
  'phone'      => '',
  'country'    => '',
  'city'       => '',
  'role'       => 'Client'
];

function post(string $k): string {
  return trim((string)($_POST[$k] ?? ''));
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  $values['first_name'] = post('first_name');
  $values['last_name']  = post('last_name');
  $values['email']      = post('email');
  $values['phone']      = post('phone');
  $values['country']    = post('country');
  $values['city']       = post('city');
  $values['role']       = post('role');

  $password = (string)($_POST['password'] ?? '');
  $confirm  = (string)($_POST['confirm_password'] ?? '');

 
  foreach (['first_name','last_name','email','phone','country','city','role'] as $f) {
    if ($values[$f] === '') $errors[$f] = "This field is required.";
  }

  if ($values['email'] !== '' && !filter_var($values['email'], FILTER_VALIDATE_EMAIL)) {
    $errors['email'] = "Invalid email format.";
  }

  
  if ($values['phone'] !== '' && !preg_match('/^\d{10}$/', $values['phone'])) {
    $errors['phone'] = "Phone must be exactly 10 digits.";
  }

  if (!in_array($values['role'], ['Client','Freelancer'], true)) {
    $errors['role'] = "Invalid role.";
  }

  if ($password === '') $errors['password'] = "Password is required.";
  if ($confirm === '') $errors['confirm_password'] = "Confirm password is required.";
  if ($password !== '' && $confirm !== '' && $password !== $confirm) {
    $errors['confirm_password'] = "Passwords do not match.";
  }

  if (!$errors) {
    
    $stmt = $pdo->prepare("SELECT 1 FROM users WHERE email = :email LIMIT 1");
    $stmt->execute([':email' => $values['email']]);
    if ($stmt->fetch()) {
      $errors['email'] = "Email is already registered.";
    } else {
      
      $userId = null;
      for ($i = 0; $i < 10; $i++) {
        $candidate = gen_id10();
        $chk = $pdo->prepare("SELECT 1 FROM users WHERE user_id = :id LIMIT 1");
        $chk->execute([':id' => $candidate]);
        if (!$chk->fetch()) { $userId = $candidate; break; }
      }
      if ($userId === null) {
        $errors['general'] = "Could not generate a user id. Try again.";
      } else {
        $hash = password_hash($password, PASSWORD_DEFAULT);

        $ins = $pdo->prepare("
         INSERT INTO users (
  user_id, first_name, last_name, email, password_hash,
  phone, country, city, role
)
VALUES (
  :user_id, :first_name, :last_name, :email, :password_hash,
  :phone, :country, :city, :role
)

        ");

        $ins->execute([
  ':user_id'        => $userId,
  ':first_name'     => $values['first_name'],
  ':last_name'      => $values['last_name'],
  ':email'          => $values['email'],
  ':password_hash'  => $hash,
  ':phone'          => $values['phone'],
  ':country'        => $values['country'],
  ':city'           => $values['city'],
  ':role'           => $values['role'],
]);

        set_flash("success", "Account created successfully. Please login.");
        header("Location: login.php");
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
  <title>Register</title>
  <link rel="stylesheet" href="css/base.css">
</head>
<body>
<div class="page">
  <?php include "includes/header.php.inc"; ?>
  <?php include "includes/nav.php.inc"; ?>

  <main class="main">
    <h1>Create Account</h1>

    <?php if (isset($errors['general'])): ?>
      <div class="flash error"><?php echo htmlspecialchars($errors['general']); ?></div>
    <?php endif; ?>

    <form method="post" novalidate>
      <label>First Name *</label>
      <input type="text" name="first_name" value="<?php echo htmlspecialchars($values['first_name']); ?>">
      <?php if (isset($errors['first_name'])): ?><div class="err"><?php echo htmlspecialchars($errors['first_name']); ?></div><?php endif; ?>

      <label>Last Name *</label>
      <input type="text" name="last_name" value="<?php echo htmlspecialchars($values['last_name']); ?>">
      <?php if (isset($errors['last_name'])): ?><div class="err"><?php echo htmlspecialchars($errors['last_name']); ?></div><?php endif; ?>

      <label>Email *</label>
      <input type="email" name="email" value="<?php echo htmlspecialchars($values['email']); ?>">
      <?php if (isset($errors['email'])): ?><div class="err"><?php echo htmlspecialchars($errors['email']); ?></div><?php endif; ?>

      <label>Password *</label>
      <input type="password" name="password" value="">
      <?php if (isset($errors['password'])): ?><div class="err"><?php echo htmlspecialchars($errors['password']); ?></div><?php endif; ?>

      <label>Confirm Password *</label>
      <input type="password" name="confirm_password" value="">
      <?php if (isset($errors['confirm_password'])): ?><div class="err"><?php echo htmlspecialchars($errors['confirm_password']); ?></div><?php endif; ?>

      <label>Phone (10 digits) *</label>
      <input type="text" name="phone" value="<?php echo htmlspecialchars($values['phone']); ?>">
      <?php if (isset($errors['phone'])): ?><div class="err"><?php echo htmlspecialchars($errors['phone']); ?></div><?php endif; ?>

      <label>Country *</label>
      <input type="text" name="country" value="<?php echo htmlspecialchars($values['country']); ?>">
      <?php if (isset($errors['country'])): ?><div class="err"><?php echo htmlspecialchars($errors['country']); ?></div><?php endif; ?>

      <label>City *</label>
      <input type="text" name="city" value="<?php echo htmlspecialchars($values['city']); ?>">
      <?php if (isset($errors['city'])): ?><div class="err"><?php echo htmlspecialchars($errors['city']); ?></div><?php endif; ?>

      <label>Role *</label>
      <select name="role">
        <option value="Client" <?php echo ($values['role']==='Client')?'selected':''; ?>>Client</option>
        <option value="Freelancer" <?php echo ($values['role']==='Freelancer')?'selected':''; ?>>Freelancer</option>
      </select>
      <?php if (isset($errors['role'])): ?><div class="err"><?php echo htmlspecialchars($errors['role']); ?></div><?php endif; ?>

      <div style="margin-top:12px;">
        <button class="btn btn-primary" type="submit">Create Account</button>
        <a class="btn btn-secondary" href="login.php">Login</a>
      </div>
    </form>
  </main>

  <?php include "includes/footer.php.inc"; ?>
</div>
</body>
</html>

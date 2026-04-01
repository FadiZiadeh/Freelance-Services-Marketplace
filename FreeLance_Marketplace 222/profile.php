<?php
declare(strict_types=1);

require_once "includes/auth.php.inc";
require_once "includes/db.php.inc";
require_once "includes/flash.php.inc";

require_login(); 

function h(string $s): string { return htmlspecialchars($s, ENT_QUOTES, 'UTF-8'); }

$userId = (string)($_SESSION['user_id'] ?? '');

$stmt = $pdo->prepare("
  SELECT user_id, first_name, last_name, email, phone, country, city, role, status, profile_photo, registration_date
  FROM users
  WHERE user_id = :uid
  LIMIT 1
");
$stmt->execute([':uid' => $userId]);
$u = $stmt->fetch();

if (!$u) {
  set_flash("error", "User not found.");
  header("Location: index.php");
  exit;
}
?>
<!doctype html>
<html>
<head>
  <meta charset="utf-8">
  <title>Profile</title>
  <link rel="stylesheet" href="css/base.css">
</head>
<body>
<div class="page">
  <?php include "includes/header.php.inc"; ?>
  <?php include "includes/nav.php.inc"; ?>

  <main class="main">
    <h1>My Profile</h1>

    <?php if ($f = get_flash()): ?>
      <div class="flash <?php echo h($f['type']); ?>"><?php echo h($f['message']); ?></div>
    <?php endif; ?>

    <div style="display:grid; grid-template-columns: 340px 1fr; gap:16px; align-items:start;">

      
      <aside style="border:1px solid #ddd; border-radius:12px; padding:14px;">
        <div style="display:flex; align-items:center; gap:12px;">
          <div style="width:72px; height:72px; border-radius:14px; overflow:hidden; border:1px solid #ddd; background:#f7f7f7;">
            <img
              src="<?php echo h((string)($u['profile_photo'] ?: 'uploads/default-profile.png')); ?>"
              alt="Profile"
              style="width:100%; height:100%; object-fit:cover;"
            >
          </div>
          <div>
            <div style="font-weight:700; font-size:1.1rem;">
              <?php echo h((string)$u['first_name'] . ' ' . (string)$u['last_name']); ?>
            </div>
            <div class="muted"><?php echo h((string)$u['email']); ?></div>
            <div class="muted">Role: <?php echo h((string)$u['role']); ?></div>
          </div>
        </div>

        <hr style="border:none; border-top:1px solid #eee; margin:12px 0;">

        <div><strong>User ID:</strong> <?php echo h((string)$u['user_id']); ?></div>
        <div><strong>Status:</strong> <?php echo h((string)$u['status']); ?></div>
        <div><strong>Registered:</strong> <?php echo h((string)$u['registration_date']); ?></div>
      </aside>

      
      <section style="border:1px solid #ddd; border-radius:12px; padding:14px;">
        <h2 style="margin-top:0;">Edit Profile</h2>

        <form action="profile-update.php" method="post" enctype="multipart/form-data" novalidate>
          <div style="display:grid; grid-template-columns: 1fr 1fr; gap:12px;">
            <div>
              <label>First Name *</label>
              <input type="text" name="first_name" value="<?php echo h((string)$u['first_name']); ?>" required>
            </div>

            <div>
              <label>Last Name *</label>
              <input type="text" name="last_name" value="<?php echo h((string)$u['last_name']); ?>" required>
            </div>

            <div style="grid-column:1 / -1;">
              <label>Email *</label>
              <input type="email" name="email" value="<?php echo h((string)$u['email']); ?>" required>
            </div>

            <div>
              <label>Phone *</label>
              <input type="text" name="phone" value="<?php echo h((string)$u['phone']); ?>" required>
            </div>

            <div>
              <label>Country *</label>
              <input type="text" name="country" value="<?php echo h((string)$u['country']); ?>" required>
            </div>

            <div>
              <label>City *</label>
              <input type="text" name="city" value="<?php echo h((string)$u['city']); ?>" required>
            </div>

            <div style="grid-column:1 / -1;">
              <label>Profile Photo (optional)</label>
              <input type="file" name="profile_photo" accept=".png,.jpg,.jpeg">
              <div class="muted" style="margin-top:6px;">Allowed: png, jpg, jpeg (max 2MB)</div>
            </div>
          </div>

          <hr style="border:none; border-top:1px solid #eee; margin:16px 0;">

          <h3 style="margin:0 0 8px;">Change Password (optional)</h3>
          <div class="muted" style="margin-bottom:10px;">Leave empty if you don’t want to change it.</div>

          <div style="display:grid; grid-template-columns: 1fr 1fr; gap:12px;">
            <div>
              <label>New Password</label>
              <input type="password" name="new_password" value="">
            </div>
            <div>
              <label>Confirm New Password</label>
              <input type="password" name="confirm_password" value="">
            </div>
          </div>

          <div style="margin-top:14px;">
            <button class="btn btn-primary" type="submit">Save Changes</button>
          </div>
        </form>
      </section>

    </div>
  </main>

  <?php include "includes/footer.php.inc"; ?>
</div>
</body>
</html>

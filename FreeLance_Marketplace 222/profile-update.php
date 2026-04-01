<?php
declare(strict_types=1);

require_once "includes/auth.php.inc";
require_once "includes/db.php.inc";
require_once "includes/flash.php.inc";

require_login();

$userId = (string)($_SESSION['user_id'] ?? '');

function back(): void {
  header("Location: profile.php");
  exit;
}

function clean(string $k): string {
  return trim((string)($_POST[$k] ?? ''));
}

$first = clean('first_name');
$last  = clean('last_name');
$email = clean('email');
$phone = clean('phone');
$country = clean('country');
$city = clean('city');

$newPass = (string)($_POST['new_password'] ?? '');
$confPass = (string)($_POST['confirm_password'] ?? '');

$errors = [];

if ($first === '') $errors[] = "First name is required.";
if ($last === '')  $errors[] = "Last name is required.";
if ($email === '' || !filter_var($email, FILTER_VALIDATE_EMAIL)) $errors[] = "Valid email is required.";
if ($phone === '') $errors[] = "Phone is required.";
if ($country === '') $errors[] = "Country is required.";
if ($city === '') $errors[] = "City is required.";


$changePassword = ($newPass !== '' || $confPass !== '');
if ($changePassword) {
  if ($newPass === '' || $confPass === '') $errors[] = "Fill both password fields.";
  if ($newPass !== $confPass) $errors[] = "Passwords do not match.";
  if (strlen($newPass) < 6) $errors[] = "Password must be at least 6 characters.";
}


$chk = $pdo->prepare("SELECT user_id FROM users WHERE email = :e AND user_id <> :uid LIMIT 1");
$chk->execute([':e' => $email, ':uid' => $userId]);
if ($chk->fetch()) $errors[] = "This email is already used by another account.";


$newPhotoPath = null;

if (!empty($_FILES['profile_photo']) && (int)($_FILES['profile_photo']['error'] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_NO_FILE) {
  $err = (int)$_FILES['profile_photo']['error'];
  $tmp = (string)$_FILES['profile_photo']['tmp_name'];
  $name = (string)$_FILES['profile_photo']['name'];
  $size = (int)($_FILES['profile_photo']['size'] ?? 0);

  if ($err !== UPLOAD_ERR_OK) $errors[] = "Profile photo upload failed.";
  if ($size <= 0 || $size > 2 * 1024 * 1024) $errors[] = "Profile photo must be <= 2MB.";

  $ext = strtolower(pathinfo($name, PATHINFO_EXTENSION));
  if (!in_array($ext, ['png','jpg','jpeg'], true)) $errors[] = "Profile photo must be png/jpg/jpeg.";

  if (!$errors) {
    $dirAbs = __DIR__ . "/uploads/profiles/" . $userId . "/";
    $dirRel = "uploads/profiles/" . $userId . "/";

    if (!is_dir($dirAbs)) {
      @mkdir($dirAbs, 0777, true);
    }

    $fileName = "profile_" . date("Ymd_His") . "." . $ext;
    $destAbs = $dirAbs . $fileName;
    $destRel = $dirRel . $fileName;

    if (move_uploaded_file($tmp, $destAbs)) {
      $newPhotoPath = $destRel;
    } else {
      $errors[] = "Could not save uploaded profile photo.";
    }
  }
}

if ($errors) {
  set_flash("error", implode(" ", $errors));
  back();
}


if ($changePassword) {
  $hash = password_hash($newPass, PASSWORD_DEFAULT);
  $sql = "
    UPDATE users
    SET first_name=:fn, last_name=:ln, email=:em, phone=:ph, country=:co, city=:ci, password_hash=:pw
    WHERE user_id=:uid
  ";
  $params = [
    ':fn'=>$first, ':ln'=>$last, ':em'=>$email, ':ph'=>$phone,
    ':co'=>$country, ':ci'=>$city, ':pw'=>$hash, ':uid'=>$userId
  ];
} else {
  $sql = "
    UPDATE users
    SET first_name=:fn, last_name=:ln, email=:em, phone=:ph, country=:co, city=:ci
    WHERE user_id=:uid
  ";
  $params = [
    ':fn'=>$first, ':ln'=>$last, ':em'=>$email, ':ph'=>$phone,
    ':co'=>$country, ':ci'=>$city, ':uid'=>$userId
  ];
}


if ($newPhotoPath !== null) {
  $sql = str_replace(" WHERE user_id=:uid", ", profile_photo=:pp WHERE user_id=:uid", $sql);
  $params[':pp'] = $newPhotoPath;
}

$upd = $pdo->prepare($sql);
$upd->execute($params);


$_SESSION['first_name'] = $first;
$_SESSION['last_name']  = $last;
$_SESSION['email']      = $email;
if ($newPhotoPath !== null) $_SESSION['profile_photo'] = $newPhotoPath;

set_flash("success", "Profile updated successfully.");
back();

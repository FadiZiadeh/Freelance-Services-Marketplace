<?php
// includes/auth.php.inc
declare(strict_types=1);

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// 24h inactivity timeout
$INACTIVITY_LIMIT = 60 * 60 * 24;
$now = time();
if (isset($_SESSION['last_activity']) && ($now - (int)$_SESSION['last_activity']) > $INACTIVITY_LIMIT) {
    session_unset();
    session_destroy();
    session_start();
}
$_SESSION['last_activity'] = $now;

function is_logged_in(): bool {
    return isset($_SESSION['user_id'], $_SESSION['role']);
}

function require_login(): void {
    if (!is_logged_in()) {
        header("Location: login.php");
        exit;
    }
}

function require_role(string $role): void {
    require_login();
    if (($_SESSION['role'] ?? '') !== $role) {
        header("Location: index.php");
        exit;
    }
}

function current_user_id(): ?string {
    return $_SESSION['user_id'] ?? null;
}

function current_role(): ?string {
    return $_SESSION['role'] ?? null;
}

function login_is_locked(): bool {
    if (!isset($_SESSION['login_lock_until'])) return false;
    return time() < (int)$_SESSION['login_lock_until'];
}

function login_lock_remaining_seconds(): int {
    if (!isset($_SESSION['login_lock_until'])) return 0;
    $rem = (int)$_SESSION['login_lock_until'] - time();
    return max(0, $rem);
}

function login_register_failure(): void {
    $now = time();

    
    if (!isset($_SESSION['login_failures'])) $_SESSION['login_failures'] = [];
    $_SESSION['login_failures'][] = $now;

    
    $_SESSION['login_failures'] = array_values(array_filter(
        $_SESSION['login_failures'],
        fn($t) => ($now - (int)$t) <= 900
    ));

  
    if (count($_SESSION['login_failures']) >= 5) {
        $_SESSION['login_lock_until'] = $now + 1800; 
    }
}// this function tracks the login if the user tried 5 times wrong in 15 minutes it will lock for 30 

function login_clear_failures(): void {
    unset($_SESSION['login_failures'], $_SESSION['login_lock_until']);
}


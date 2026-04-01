<?php
require_once "includes/auth.php.inc"; // starts session
header("Content-Type: text/plain; charset=utf-8");

echo "SESSION ID: " . session_id() . "\n\n";
echo "CART TYPE: " . gettype($_SESSION['cart'] ?? null) . "\n\n";

echo "----- FULL SESSION -----\n";
print_r($_SESSION);

echo "\n\n----- CART ONLY -----\n";
print_r($_SESSION['cart'] ?? null);

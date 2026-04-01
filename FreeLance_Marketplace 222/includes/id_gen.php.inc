<?php
// includes/id_gen.php.inc
declare(strict_types=1);

function gen_id10(): string {
    // 10-digit numeric string
    $s = "";
    for ($i = 0; $i < 10; $i++) {
        $s .= (string) random_int(0, 9);
    }
    return $s;
}

<?php
// RedeemALL database settings.
// Put this file at /var/www/config.php (OUTSIDE /var/www/html) so nobody can open it
// in a browser, and never upload it to GitHub.
return [
    'db_host' => '10.0.2.4',               // private IP of the DB VM
    'db_name' => 'REDEEMALL',
    'db_user' => 'redeemuser',
    'db_pass' => 'ChooseAStrongPassword!', // the password you used in CREATE USER
];

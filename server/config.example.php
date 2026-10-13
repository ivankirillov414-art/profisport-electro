<?php
// Keep this file outside the public web directory. Copy to config.php.
return [
 'data_dir'=>'/absolute/private/path/electro-data',
 'admin_login'=>'admin',
 // Generate with PHP password_hash; do not use a shared or default password.
 'admin_password_hash'=>'',
];

<?php
// api/generar_hash.php
$password = 'password';
$hash = password_hash($password, PASSWORD_DEFAULT);
echo $hash;
?>
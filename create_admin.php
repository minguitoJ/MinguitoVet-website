<?php
require_once 'config/database.php';

$username = 'admin@minguito.com';
$password = 'admin123';

$stmt = $pdo->prepare("INSERT INTO admins (username, password) VALUES (?, ?)");
$stmt->execute([
    $username,
    password_hash($password, PASSWORD_DEFAULT)
]);

echo "Admin created!<br>";
echo "Username: admin<br>";
echo "Password: admin123";
?>
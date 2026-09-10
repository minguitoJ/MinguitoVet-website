<?php
require_once 'config/database.php';

$username = 'admin@minguito.com';
$password = 'admin123';

$stmt = $pdo->prepare("INSERT INTO users (username, password, role) VALUES (?, ?, 'admin')");
$stmt->execute([
    $username,
    password_hash($password, PASSWORD_DEFAULT)
]);

echo "Admin created!<br>";
echo "Username: " . htmlspecialchars($username) . "<br>";
echo "Password: admin123";
?>
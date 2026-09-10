<?php

function clean($data)
{
    return htmlspecialchars(
        trim($data),
        ENT_QUOTES,
        'UTF-8'
    );
}

function redirect($page)
{
    header("Location: $page");
    exit;
}

function isLoggedIn()
{
    return isset($_SESSION['user_id'])
        && ($_SESSION['user_role'] ?? '') === 'admin';
}

function requireLogin()
{
    if (!isLoggedIn()) {

        header("Location: ../login.php?type=admin");

        exit;
    }
}

?>
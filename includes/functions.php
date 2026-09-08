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
    return isset($_SESSION['admin_id']);
}


function requireLogin()
{
    if (!isLoggedIn()) {

        header("Location: index.php");

        exit;
    }
}

?>
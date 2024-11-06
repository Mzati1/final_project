<?php
// basically this is checking if the admin is a admin and if not redirect as seen below

//admin check
if (!isset($_SESSION['is_logged_in']) || $_SESSION['is_logged_in'] !== true) {
    header('Location: ../login.php');
    exit;
}

//now check if admin is an admin then chuck him to admin
if (isset($_SESSION['is_user']) && $_SESSION['is_user'] === true) {
    header("Location: ../client/dashboard.php");
}

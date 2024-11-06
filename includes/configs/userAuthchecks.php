<?php
// basically this is checking if the user is a user and if not redirect as seen below

//user check
if (!isset($_SESSION['is_logged_in']) || $_SESSION['is_logged_in'] !== true) {
    header('Location: ../login.php');
    exit;
}

//now check if user is an admin then chuck him to admin
if (isset($_SESSION['is_admin']) && $_SESSION['is_admin'] === true) {
    header("Location: ../admin/dashboard.php");
}

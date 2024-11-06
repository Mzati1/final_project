<?php
/* i think this page can act like a router or something also displaying a welcome page */

// Start session
session_start();

// route depending on role, admins get chucked to dashboard yawo and 
// users to panel yawo, simple

$baseUrl = "pages/";

if (isset($_SESSION['is_logged_in'])) {

    // if logged in it'll run these checks and redirect to pages
    if (isset($_SESSION['is_student']) && $_SESSION['is_student'] === false && $_SESSION['is_logged_in'] === true) {

        //redirect to admin dashboard
        header("Location: {$baseUrl}client/dashboard.php");
    } else if (isset($_SESSION['is_student']) && $_SESSION['is_student'] === true && $_SESSION['is_logged_in'] === true) {

        //redirect to admin dashboard
        header("Location: {$baseUrl}admin/dashboard.php");
    }
} else {
    header("Location: {$baseUrl}login.php");
}

exit;

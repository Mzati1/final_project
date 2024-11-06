<?php

//start session
session_start();

//incllude the admin auth checker
require __DIR__ . "/../../includes/configs/adminAuthChecks.php";

require __DIR__ . "/../../includes/configs/database.php";
require __DIR__ . "/../../includes/functions/queryExecutor.php";

function getUniversalData()
{
    //i want this function to get all the queries and reponses for all the data that all the types
    // of admin will have access to 
};

//this will conditionally get data depending on the admin type and add ontop of the general
// data and ma metric that we are getting in which they all have in common

if (isset($_SESSION['role']) && $_SESSION['role'] === "MEC") {
    // get data from database for mec admins ontop of general data
} else if (isset($_SESSION['role']) && $_SESSION['role'] === "ADMIN") {
    // get data from database for mec admins ontop of general data
    //which is literaly everything btw
} elseif (isset($_SESSION['role']) && $_SESSION['role'] === "DoSA") {
    //stuff for the dosa 
}



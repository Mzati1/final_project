<?php

//includes
require __DIR__ . "/../../includes/configs/database.php";
require __DIR__ . "/../../includes/functions/queryExecutor.php";

/* NOTE */

//this is the main area for selecting elections and displaying relevant data
// it will also have a section that shows completed ( or done ) elections, and their results
// i think for a completed election we can put how many votes and some stats
//is possible someone find a way to make graphs without external library, will pass data in json ( response ) to ease

// i was thinking we pass the election id in the url when redirecting on a click
//it'll go to electionVote.php where we will dynamically show the data and implement logic (CRUD)
//for voting for a particular election only

//also for the sake of scalability the internet says to store the images in this same folder
//and avoid direct uploads to database, that'll be better for ma HD images.

//start session
session_start();

//this checks and make sure the user is authenticated (i was reusing it alot on the client pages so i just make it a require)
require __DIR__ . "/../../includes/configs/userAuthchecks.php";

//initialise variables
$error;

//making queries and storing data for the dashboard:

/* QUERIES */
$studentDetailsQuery = "
    SELECT student_id, first_name, last_name, reg_number, email, is_registered, created_at
    FROM students
    WHERE email = :email
";

$electionDetailsQuery = "
    SELECT election_id, election_name, start_date, end_date, election_status
    FROM elections
";

$activeElectionDetailsQuery = "
    SELECT election_id, election_name, start_date, end_date, election_status
    FROM elections
";

$pastElectionQuery = "
    SELECT election_id, election_name, start_date, end_date, election_status
    FROM elections
    WHERE election_status = 'closed'
";

// Get student data
$getStudentDataStatement = executeQuery($pdo, $studentDetailsQuery, [':email' => $_SESSION['email']]);
if ($getStudentDataStatement) {
    $studentDataArray = $getStudentDataStatement->fetch(PDO::FETCH_ASSOC);
}

// Get election details
$getElectionDataStatement = executeQuery($pdo, $electionDetailsQuery);
if ($getElectionDataStatement) {
    $electionDataArray = $getElectionDataStatement->fetchAll(PDO::FETCH_ASSOC);
}

// Get election details
$getPastElectionDataStatement = executeQuery($pdo, $pastElectionQuery);
if ($getPastElectionDataStatement) {
    $pastElectionDataArray = $getPastElectionDataStatement->fetchAll(PDO::FETCH_ASSOC);
}

//get active elections details
$getActiveElectionDataStatement = executeQuery($pdo, $activeElectionDetailsQuery);
if ($getActiveElectionDataStatement) {
    $ActiveElectionDataArray = $getActiveElectionDataStatement->fetchAll(PDO::FETCH_ASSOC);
}

//reponses are in json format to make them easier to iterate
$userData = json_encode($studentDataArray);
$electionData = json_encode($electionDataArray);
$pastElectionData = json_encode($pastElectionDataArray);
$activeElectionData = json_encode($ActiveElectionDataArray);

echo $activeElectionData;

/*
when an election is pressed make sure to redirect to desired dynamic page 
using the elections id in the url eti heres a resource on how to do that
[https://stackoverflow.com/questions/33795123/php-header-redirect-with-parameter]
*/
<?php

//includes
require __DIR__ . "/../../includes/configs/database.php";
require __DIR__ . "/../../includes/functions/queryExecutor.php";

//variables
$error = null;

//checks if form has been submitted

if ($_SERVER['REQUEST_METHOD'] === "POST") {

    //clean inputs 
    if (isset($_POST['election_name'], $_POST['start_date'], $_POST['end_date'])) {
        $election_name = trim($_POST['election_name']);
        $start_date = trim($_POST['start_date']);
        $end_date = trim($_POST['end_date']);
    } else {
        $error = "Please Enter all inputs";
    }

    // check election_status specifically
    if (isset($_POST['election_status'])) {
        $status = trim($_POST['election_status']);
        if ($status === 'open' || $status === 'closed') {
            $election_status = $status;
        } else {
            $error = "invalid input for election status";
        }
    }

    //check for no error first
    if ($error === null) {

        $insertElectionStatement = "
            INSERT INTO elections (election_name, start_date, end_date, election_status)
            VALUES (:election_name, :start_date, :end_date, :election_status)
        ";

        $parameters = [
            ':election_name' => $election_name,
            ':start_date' => $start_date,
            ':end_date' => $end_date,
            ':election_status' => $election_status
        ];

        //run query
        if (executeQuery($pdo, $query, $parameters)) {
            // Redirect with  success message ( maybe )
            header("Location: dashboard.php?success=election_created");
            exit;
        } else {
            $error = "Failed to create the election. Please try again.";
        }
    }
}

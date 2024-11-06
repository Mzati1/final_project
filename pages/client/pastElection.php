<?php

//includes
require __DIR__ . "/../../includes/configs/database.php";
require __DIR__ . "/../../includes/functions/queryExecutor.php";

// Fire up the session
session_start();

// Check if user is authenticated
require __DIR__ . "/../../includes/configs/userAuthChecks.php";

// Initialize variables
$error = '';
$electionId = 0;

// Check if the id parameter is in the URL
if (isset($_GET['id'])) {
    $electionId = intval($_GET['id']);
}

// Query to fetch all details for a specific election, including candidates and their votes
$electionDetailsQuery = "
    SELECT 
        elections.election_id, 
        elections.election_name, 
        elections.start_date, 
        elections.end_date, 
        elections.election_status,
        positions.position_id,
        positions.position_name,
        candidates.first_name AS candidate_first_name,
        candidates.last_name AS candidate_last_name,
        candidates.manifesto,
        candidates.image_url,
        COALESCE(votes_count.votes_received, 0) AS votes_received
    FROM 
        elections
    LEFT JOIN 
        positions ON elections.election_id = positions.election_id
    LEFT JOIN 
        candidates ON positions.position_id = candidates.position_id 
                AND candidates.election_id = elections.election_id
    LEFT JOIN (
        SELECT 
            vote.candidate_id, 
            vote.election_id, 
            COUNT(vote.vote_id) AS votes_received
        FROM 
            votes AS vote
        GROUP BY 
            vote.candidate_id, 
            vote.election_id
    ) AS votes_count ON candidates.candidate_id = votes_count.candidate_id 
                    AND votes_count.election_id = elections.election_id
    WHERE 
        elections.election_id = :election_id
    GROUP BY 
        elections.election_id, 
        positions.position_id, 
        candidates.candidate_id
    ORDER BY 
        positions.position_id, 
        votes_received DESC; 
";

// Execute the query to get election details
$getElectionDetailsStatement = executeQuery($pdo, $electionDetailsQuery, [':election_id' => $electionId]);

// Initialize an array to store election details
$electionData = [
    'election_id' => null,
    'election_name' => null,
    'start_date' => null,
    'end_date' => null,
    'election_status' => null,
    'positions' => []
];

// Fetch the election details only if the query was successful
if ($getElectionDetailsStatement) {
    while ($row = $getElectionDetailsStatement->fetch(PDO::FETCH_ASSOC)) {
        // Populate the election data
        if ($electionData['election_id'] === null) {
            $electionData['election_id'] = $row['election_id'];
            $electionData['election_name'] = $row['election_name'];
            $electionData['start_date'] = $row['start_date'];
            $electionData['end_date'] = $row['end_date'];
            $electionData['election_status'] = $row['election_status'];
        }

        // Check if the position already exists in the positions array
        $positionIndex = array_search($row['position_id'], array_column($electionData['positions'], 'position_id'));

        if ($positionIndex === false) {
            // If position doesn't exist make a new one
            $electionData['positions'][] = [
                'position_id' => $row['position_id'],
                'position_name' => $row['position_name'],
                'candidates' => []
            ];
            $positionIndex = count($electionData['positions']) - 1; // Get the index of the new position
        }

        // Add candidate information to the candidates array for the corresponding position
        $electionData['positions'][$positionIndex]['candidates'][] = [
            'first_name' => $row['candidate_first_name'],
            'last_name' => $row['candidate_last_name'],
            'manifesto' => $row['manifesto'],
            'image_url' => $row['image_url'],
            'votes_received' => (int) $row['votes_received']  // Ensure it's an integer
        ];
    }
} else {
    $error = "Failed to get data!";
    exit;
}

// Output the results as JSON
echo json_encode($electionData);

<?php
// Start the session
session_start();

// Includes
require __DIR__ . "/../../includes/configs/database.php";
require __DIR__ . "/../../includes/functions/queryExecutor.php";


// Check if user is authenticated
require __DIR__ . "/../../includes/configs/userAuthChecks.php";

// Initialize variables
$electionId = 0;
$votes = [];

// Check if the 'id' parameter is present in the URL
if (isset($_GET['id'])) {
    $electionId = intval($_GET['id']); // Convert to integer
}

// SQL to get the election details
$electionDetailsQuery = "
    SELECT 
        elections.election_id AS election_id,
        elections.election_name AS election_name,
        elections.start_date AS start_date,
        elections.end_date AS end_date,
        elections.election_status AS election_status,
        positions.position_id AS position_id,
        positions.position_name AS position_name,
        candidates.candidate_id AS candidate_id,
        candidates.first_name AS candidate_first_name,
        candidates.last_name AS candidate_last_name,
        candidates.image_url As candidate_image_url,
        candidates.manifesto As candidate_manifesto
    FROM elections
    LEFT JOIN positions ON elections.election_id = positions.election_id
    LEFT JOIN candidates ON positions.position_id = candidates.position_id
    WHERE elections.election_id = :election_id
    ORDER BY positions.position_id, candidates.candidate_id
";

// Execute the query to get election details
$getElectionDetailsStatement = executeQuery($pdo, $electionDetailsQuery, [':election_id' => $electionId]);

// Initialize array for the JSON response
$electionData = [
    'election_id' => null,
    'election_name' => null,
    'start_date' => null,
    'end_date' => null,
    'election_status' => null,
    'positions' => []
];

// Fetch the election details if the query was successful
if ($getElectionDetailsStatement) {
    while ($row = $getElectionDetailsStatement->fetch(PDO::FETCH_ASSOC)) {
        //get the data
        if ($electionData['election_id'] === null) {
            $electionData['election_id'] = $row['election_id'];
            $electionData['election_name'] = $row['election_name'];
            $electionData['start_date'] = $row['start_date'];
            $electionData['end_date'] = $row['end_date'];
            $electionData['election_status'] = $row['election_status'];
        }

        // Check if the position already exists in the positions array to avoid duplicates ( i got an error first time so this solved )
        $positionIndex = array_search($row['position_id'], array_column($electionData['positions'], 'position_id'));

        if ($positionIndex === false) {
            // If position doesn't exist make a new one
            $electionData['positions'][] = [
                'position_id' => $row['position_id'],
                'position_name' => $row['position_name'],
                'candidates' => []
            ];
            $positionIndex = count($electionData['positions']) - 1; // Get the index of the new position by subtracting total - 1
        }

        // Add the candidate to the corresponding positions
        $electionData['positions'][$positionIndex]['candidates'][] = [
            'candidate_id' => $row['candidate_id'],
            'first_name' => $row['candidate_first_name'],
            'last_name' => $row['candidate_last_name'],
            'manifesto' => $row['candidate_manifesto'],
            'image_url' => $row['candidate_image_url']
        ];
    }
}

//to json
$electionData = json_encode($electionData);

// Output the results as JSON
echo $electionData;

/* 

as for the voting process we can use radio buttons and store the values in the $votes array then for loop the insertion of the,
votes into the database for the user, we already have much of the data we need anyways from ma responses and sessions ndapangawo 

*/

// SOMETHING LIKE THIS, will add this when you build the page

 /* 

foreach ($votes as $position => $candidateId) {
    if (!empty($candidateId)) {
        $stmt = $pdo->prepare("
                INSERT INTO votes (user_id, candidate_id, position_name)
                VALUES (:user_id, :candidate_id, :position_name)
            ");
        $stmt->execute([
            ':user_id' => $_SESSION['user_id'],
            ':candidate_id' => $candidateId,
            ':position_name' => $position
        ]);
    }
}

  */
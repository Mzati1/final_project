<?php
// Include your database connection
require __DIR__ . "/../../configs/database.php";

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['election_id']) && is_numeric($_POST['election_id'])) { //make sure its a number, no time for errors 
        $electionId = $_POST['election_id'];

        // Prepare the delete statement
        $query = "DELETE FROM elections WHERE election_id = :election_id";
        $stmt = $pdo->prepare($query);

        // Bind parameters and execute
        $stmt->bindParam(':election_id', $electionId, PDO::PARAM_INT);

        if ($stmt->execute()) {
            echo json_encode(['success' => true, 'message' => 'Election deleted successfully.']);
        } else {
            echo json_encode(['success' => false, 'message' => 'Failed to delete election.']);
        }
    } else {
        echo json_encode(['success' => false, 'message' => 'Invalid election ID.']);
    }
} else {
    echo json_encode(['success' => false, 'message' => 'Invalid request method.']);
}

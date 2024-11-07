<?php
// Include your database connection
require __DIR__ . "/../../configs/database.php";

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Check if required parameters are set and are not empty
    if (
        isset($_POST['election_id'], $_POST['name'], $_POST['start_date'], $_POST['end_date'], $_POST['status'])
        && !empty($_POST['election_id'])
        && !empty($_POST['name'])
        && !empty($_POST['start_date'])
        && !empty($_POST['end_date'])
        && !empty($_POST['status'])
    ) {
        // Sanitize the input values
        $electionId = $_POST['election_id'];
        $name = $_POST['name'];
        $startDate = $_POST['start_date'];
        $endDate = $_POST['end_date'];
        $status = $_POST['status'];

        // Prepare the update statement
        $query = "UPDATE elections 
                  SET election_name = :name, start_date = :start_date, end_date = :end_date, election_status = :status, 
                      modified_at = CURRENT_TIMESTAMP
                  WHERE election_id = :election_id";
        $stmt = $pdo->prepare($query);

        // Bind parameters and execute
        $stmt->bindParam(':election_id', $electionId, PDO::PARAM_INT);
        $stmt->bindParam(':name', $name, PDO::PARAM_STR);
        $stmt->bindParam(':start_date', $startDate, PDO::PARAM_STR);
        $stmt->bindParam(':end_date', $endDate, PDO::PARAM_STR);
        $stmt->bindParam(':status', $status, PDO::PARAM_STR);

        // Execute the statement and return a response
        if ($stmt->execute()) {
            echo json_encode(['success' => true, 'message' => 'Election updated successfully.']);
        } else {
            echo json_encode(['success' => false, 'message' => 'Failed to update election.']);
        }
    } else {
        // If any of the parameters are missing or empty
        echo json_encode(['success' => false, 'message' => 'Missing required parameters or invalid data.']);
    }
} else {
    echo json_encode(['success' => false, 'message' => 'Invalid request method.']);
}

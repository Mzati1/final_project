<?php
// Include your database connection
require __DIR__ . "/../../configs/database.php";

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Retrieve the JSON data from the POST request
    $inputData = json_decode(file_get_contents('php://input'), true);

    // Check if the data is valid and contains the required fields
    if (
        isset($inputData['election_id'], $inputData['election_name'], $inputData['start_date'], $inputData['end_date'], $inputData['status'])
        && !empty($inputData['election_id'])
        && !empty($inputData['election_name'])
        && !empty($inputData['start_date'])
        && !empty($inputData['end_date'])
        && !empty($inputData['status'])
    ) {
        // Sanitize and assign values from the input
        $electionId = $inputData['election_id'];
        $electionName = $inputData['election_name'];
        $startDate = $inputData['start_date'];
        $endDate = $inputData['end_date'];
        $status = $inputData['status'];

        // Prepare the update statement
        $query = "UPDATE elections 
                  SET election_name = :election_name, start_date = :start_date, end_date = :end_date, election_status = :status, 
                      modified_at = CURRENT_TIMESTAMP
                  WHERE election_id = :election_id";
        $stmt = $pdo->prepare($query);

        // Bind parameters and execute
        $stmt->bindParam(':election_id', $electionId, PDO::PARAM_INT);
        $stmt->bindParam(':election_name', $electionName, PDO::PARAM_STR);
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

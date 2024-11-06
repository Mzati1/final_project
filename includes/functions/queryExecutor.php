<?php
//so this will help in making db queries simpler, as it 
//will make it centralised and easy to change and use its also
//Maintaining our goal of making this project component based and reuseable
//i got the idea from [https://stackoverflow.com/questions/32417550/how-to-execute-sql-query-inside-a-php-function]

// Function to execute a query and handle errors
function executeQuery($pdo, $query, $params = [])
{
    try {
        $stmt = $pdo->prepare($query);
        $stmt->execute($params);
        return $stmt;
    } catch (PDOException $e) {
        // For now, we'll just echo the message for debugging
        echo 'Database error: ' . htmlspecialchars($e->getMessage());
        return null;
    }
}

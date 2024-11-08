<?php

require __DIR__ . "/../../configs/database.php";

try {
    // Fetch the latest login audit logs
    $loginSql = "SELECT 
                    la.audit_id, 
                    la.attempted_account, 
                    la.account_type, 
                    la.login_time, 
                    la.ip_address, 
                    la.client, 
                    la.login_status,
                    s.first_name AS student_first_name, 
                    s.last_name AS student_last_name,
                    a.first_name AS admin_first_name, 
                    a.last_name AS admin_last_name
                FROM login_audit la
                LEFT JOIN students s ON la.student_id = s.student_id
                LEFT JOIN admins a ON la.admin_id = a.admin_id
                ORDER BY la.login_time DESC
                LIMIT 50"; // Adjust limit as needed

    // Fetch the latest vote audit logs
    $voteSql = "SELECT 
                va.audit_id, 
                va.vote_time, 
                va.vote_id, 
                s.first_name AS student_first_name, 
                s.last_name AS student_last_name,
                c.first_name AS candidate_first_name, 
                c.last_name AS candidate_last_name, 
                e.election_name, 
                p.position_name
            FROM vote_audit va
            LEFT JOIN users u ON va.user_id = u.user_id
            LEFT JOIN students s ON u.student_id = s.student_id
            LEFT JOIN candidates c ON va.candidate_id = c.candidate_id
            LEFT JOIN elections e ON va.election_id = e.election_id
            LEFT JOIN positions p ON va.position_id = p.position_id
            ORDER BY va.vote_time DESC
            LIMIT 50"; // Adjust limit as needed

    // Execute the login audit query
    $loginStmt = $pdo->query($loginSql);
    $loginLogs = $loginStmt->fetchAll(PDO::FETCH_ASSOC);

    // Execute the vote audit query
    $voteStmt = $pdo->query($voteSql);
    $voteLogs = $voteStmt->fetchAll(PDO::FETCH_ASSOC);

    // Return the results as JSON
    echo json_encode([
        'loginLogs' => $loginLogs,
        'voteLogs' => $voteLogs
    ]);
} catch (PDOException $e) {
    // Handle error if the connection fails or query has issues
    echo json_encode(['error' => 'Database connection failed: ' . $e->getMessage()]);
}

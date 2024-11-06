<?php

//connection settings
$hostname = "localhost";
$dbname = "voting_system_database";
$username = "admin";
$password = "1111";

$dsnNoDb = "mysql:host=$hostname;charset=utf8mb4";

//databae schema & population paths
$databaseSchemaFile = __DIR__ . "/../../assets/database/database.sql";
$databasePopulationFile = __DIR__ . "/../../assets/database/populationData.sql";

//attempt to connect using try catch ( logs errors easier )
try {

    $pdo = new PDO($dsnNoDb, $username, $password);

    //Checking if the database exists
    $dbExistsQuery = "SELECT COUNT(*) FROM INFORMATION_SCHEMA.SCHEMATA WHERE SCHEMA_NAME = :dbname";
    $stmt = $pdo->prepare($dbExistsQuery);
    $stmt->bindParam(':dbname', $dbname);
    $stmt->execute();

    // Creating it if does not exist

    $isNewDatabase = false; // flag to track if the database is new or not

    if ($stmt->fetchColumn() == 0) {
        $pdo->exec("CREATE DATABASE `$dbname` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci");
        $isNewDatabase = true;
    }

    //connect to the made database with proper dsn setting now
    $dsnWithDb = "mysql:host=$hostname;dbname=$dbname;charset=utf8mb4";
    $pdo = new PDO($dsnWithDb, $username, $password);

    //trying to automatically make the tables when a new database is made
    if ($isNewDatabase) {
        if (file_exists($databaseSchemaFile)) {
            $schemaSql = file_get_contents($databaseSchemaFile);

            //run the query
            $pdo->exec($schemaSql);
        }

        //populating the database with data if available
        if (file_exists($databasePopulationFile)) {
            $populationSql = file_get_contents($databasePopulationFile);

            //run the query
            $pdo->exec($populationSql);
        }
    }
} catch (PDOException $e) {

    // Catch and display error if any
    echo "Connection failed: " . $e->getMessage();
}

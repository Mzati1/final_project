<?php

//includes 
require __DIR__ . "/../../includes/configs/database.php";
require __DIR__ . "/../../includes/functions/queryExecutor.php";


//variables being used ( change to meet ma requirements anu )
$error = null;
$image_url = '';
$manifesto_length = 30;
$supported_image_types = ['image/jpeg', 'image/png', 'image/jpg'];
$upload_dir = __DIR__ . "/../../assets/images/candidates/";

//checks if the election id is present in the url ( i.e ?election_id = 2 )
if (isset($_GET['election_id'])) { // this or whatever your passing kaya it'll just be id, you'll see and change
    $election_id = (int)$_GET['election_id'];
} else {

    //redirect to wherever you need
    header("Location: dashboard.php?error='mbolatu'");
    exit;
}

//check if the form has been submitted
if ($_SERVER['REQUEST_METHOD'] === "POST") {

    //cleans the variables, trim if present and leave empty if not
    $first_name = trim(isset($_POST['first_name']) ? $_POST['first_name'] : '');
    $last_name = trim(isset($_POST['last_name']) ? $_POST['last_name'] : '');
    $position_id = (int)(isset($_POST['position_id']) ? $_POST['position_id'] : null);
    $manifesto = trim(isset($_POST['manifesto']) ? $_POST['manifesto'] : '');


    //checks if empty
    if (isset($_FILES['image']) && $_FILES['image']['error'] === UPLOAD_ERR_OK) {

        /*uhh this checks if the directory exists for the uploads and if it doest
        it just makes one to avoid a chi error, check the permissions though im hoping theyre alright
        775 makes the owner of file R, W & E. its from last sem O.S
        */

        if (!is_dir($upload_dir)) {
            mkdir($upload_dir, 0755, true);
        }

        $image_type = mime_content_type($_FILES['image']['tmp_name']);


        //checks if the selected image is allowed ( variable is at the top )
        if (in_array($image_type, $supported_image_types)) {

            //makes the image, like this :  jacqline_zimba.png giving it a name
            $image_name = strtolower($first_name . '_' . $last_name . '.' . pathinfo($_FILES['image']['name'], PATHINFO_EXTENSION));

            //path
            $image_path = $upload_dir . $image_name;

            //moves the image with the new name and stuff to the directory, and checks for errors
            if (move_uploaded_file($_FILES['image']['tmp_name'], $image_path)) {
                $image_url = '/assets/images/candidates/' . $image_name;
            } else {
                $error = "Failed to upload image.";
            }
        } else {
            $error = "Unsupported image type.";
        }
    } else {
        $error = "Image upload error.";
    }

    //if inputs are empty
    if (!$election_id || !$first_name || !$last_name || !$position_id || !$image_url || !$manifesto) {
        $error = "Please input all fields.";


        //checks length of manifestso
    } elseif (str_word_count($manifesto) > $manifesto_length) {
        $error = "Manifesto must be 30 words or less.";
    }

    //if no error statement is executed
    if ($error === null) {

        //sql
        $createCandidateSql = "
            INSERT INTO candidates (election_id, position_id, first_name, last_name, image_url, manifesto, created_at)
            VALUES (:election_id, :position_id, :first_name, :last_name, :image_url, :manifesto, NOW())
        ";

        //params
        $parameters = [
            ':election_id' => $election_id,
            ':position_id' => $position_id,
            ':first_name' => $first_name,
            ':last_name' => $last_name,
            ':image_url' => $image_url,
            ':manifesto' => $manifesto,
        ];

        //on sucess goes back to dashboard and on fail error basi
        if (executeQuery($pdo, $createCandidateSql, $parameters)) {
            header("Location: dashboard.php?success=candidate_created");
            exit;
        } else {
            $error = "Cannot add candidate; something went wrong.";
        }
    }
}

//get all the available positions
$getPositionsSql = "
SELECT 
    positions.position_id,
    positions.position_name,
    positions.position_description,
    positions.created_at,
    elections.election_name,
    elections.start_date,
    elections.end_date,
    elections.election_status
FROM 
    positions
JOIN 
    elections ON positions.election_id = :election_id
";

//get and turn into json
$getPositionsStatement = executeQuery($pdo, $studentDetailsQuery, [':election_id' => $election_id]);
if ($getPositionsStatement) {
    $positionsArray = $getPositionsStatement->fetch(PDO::FETCH_ASSOC);
}

$electionsPositions = json_encode($positionsArray);

//testing ( just make sure you actually hvae positions available, iterate them on the select box for the position )
echo $electionsPositions;

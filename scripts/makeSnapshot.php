<?php
// needed for prepared DB statements
include_once('db/DBHandler.php');

// checks for input fields
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    header("Content-Type: application/json; charset=UTF-8");
    $obj = json_decode($_POST["tankstellen"], false);

    if (empty($obj)) {
        $err = "Keine Tankstellen übermittelt...";
    }
}

// check all fields
if (empty($obj) || !empty($err) || $_SERVER['REQUEST_METHOD'] == 'GET') {
    if ($_SERVER['REQUEST_METHOD'] == 'GET') {
        if (!empty($err)) {
            header('Status: 400'); // Status bad request
        echo $err;
            exit;
        }
    } else {
        header('Status: 400'); // Status bad request
    echo "GET Method used...";
        exit;
    }
} else {
    // Create connection
  $db = new DbHandler();

    header("Content-Type: application/json; charset=UTF-8");
    $obj = json_decode($_POST["tankstellen"], true);

    for ($i=0; $i<count($obj); $i++) {
        // insert each gas station into DB
    $db->insertGasStation($obj[$i]['idTankerkoenig'], $obj[$i]['diesel'], $obj[$i]['e5'], $obj[$i]['e10']);
    }
}

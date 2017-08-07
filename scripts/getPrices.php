<?php
// needed for prepared DB statements
include_once('db/DBHandler.php');

// checks for input fields
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    header("Content-Type: application/json; charset=UTF-8");
    $obj = json_decode($_POST["idTankerkoenig"], false);

    if (empty($obj)) {
        $err = "Keine Tankstelle übermittelt...";
    }
}

// check all fields
if (empty($obj) || !empty($err) || $_SERVER['REQUEST_METHOD'] == 'GET') {
    if ($_SERVER['REQUEST_METHOD'] == 'POST') {
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
    $obj = json_decode($_POST["idTankerkoenig"], true);

  // get prices out of DB
  $resultArr = $db->getPricesForStation($obj);

    if (count($resultArr) > 0) {
        header('Content-type: application/json');
        echo json_encode($resultArr);
    } else {
        header('Content-type: application/json');
        echo json_encode(null);
    }
}

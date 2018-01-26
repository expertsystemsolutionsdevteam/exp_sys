<?php

function returnSuccess($payload) {
    $payloadObj = new stdClass();
    $payloadObj->success = true;
    $payloadObj->payload = $payload;
    $successObj = json_encode($payloadObj);
    return $successObj;
}

function returnError($errorMsg) {
    $errorObj = new stdClass();
    $errorObj->success = false;
    $errorObj->error = $errorMsg;
    $errorObj->payload = [];
    $returnObj = json_encode($errorObj);
    return $returnObj;	
}

function getDBConnection() {

	$connStr = "host=192.168.1.3 port=5432 dbname=master_mind user=postgres password=window";
	$conn = pg_connect($connStr) or die(returnError("Error getting DB connection!"));
	if (!$conn) {
 		return -1;
	}
	return $conn;
}

?>
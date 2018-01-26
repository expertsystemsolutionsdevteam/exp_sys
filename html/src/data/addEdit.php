<?php
include '../../API/common_functions/coreUtils.php';

//simple check
$name = $_REQUEST['name'];
$desc = $_REQUEST['description'];
$json_field = $_REQUEST['json_field'];

if (strlen($name) > 0 && strlen($desc) > 0 && strlen($json_field)) {
	$param_array=array($name,$desc,$json_field);
} else {
	die(returnError("Error,  You must fill in all required fields."));
}

$conn = getDBConnection();

// Prepare a query for execution
$result = pg_prepare($conn, "my_query", 'INSERT INTO core.decision_tree (name,description,json_field) VALUES ($1, $2, $3)');
$result = pg_execute($conn, "my_query", $param_array);

$errorStr = pg_get_result($conn);
if (strlen($errorStr) > 0) {
	die(returnError("Error inserting into DB: $errorStr"));
} else {
	$payload = [];
	die(returnSuccess($payload));
}

?>
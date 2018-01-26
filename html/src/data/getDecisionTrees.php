<?php
include '../../API/common_functions/coreUtils.php';

$conn = getDBConnection();

$result = pg_query($conn, "SELECT name, description FROM core.decision_tree;");
$payload = pg_fetch_all($result);

die(returnSuccess($payload));
?>
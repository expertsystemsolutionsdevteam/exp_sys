<?php
include '../../API/common_functions/coreUtils.php';

//simple check
$xaction = $_REQUEST['xaction'];


switch ($xaction) {
    case 'get-master-combo':
    	$payload = array();
    	$payload[0] = "By Author";
    	$payload[1] = "Category";
    	$payload[2] = "Tags";

    	die(returnSuccess($payload));
        
        break;

    case 'get-Category':
    	$payload = array();
    	$payload[0] = "Classification";
    	$payload[1] = "Diagnosis";
    	$payload[2] = "Advise";
    	$payload[3] = "Planning";

    	die(returnSuccess($payload));
        break;

    case 'get-Tags':
    	$payload = array();
    	$payload[0] = "Business";
    	$payload[1] = "Manufacturing";
    	$payload[2] = "Ranching/Farming";
    	$payload[3] = "Other";

    	die(returnSuccess($payload));
        break;

    case 'get-By Author':
    	$payload = array();
    	$payload[0] = "martin.israelsen@gmail.com";
    	$payload[1] = "jack.spratt@gmail.com";
    	$payload[2] = "another.author@comcast.net";

    	die(returnSuccess($payload));
        break;

    default:
      
}

?>

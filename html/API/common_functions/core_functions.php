<?php
/**
 * @author: Marty Israelsen (martin.israelsen@gmail.com)
 * @date:   2017-09-27
 *
 * This file contains core API functions.  These functions can be used by
 *  all other api files.
 */

session_start();    // Start session so $_SESSION will be persistent between calls.

/**
  * Validates an API key.
  *
  * @param string $key  - The apikey to validate.
  *
  * @return 1 for success or 0 for failure
  */
function validKey( $key ) {
    if (!getApiKeyInfo($key)) {
        return 0;
    }
    return 1;
}




/**
  * Checks for DOS (Denial of Service) Attacks.
  *
  *   Uses $_SESSION variable to quickly check how many times we have been
  *     hit within a certain time period.  If we are hit more than X amount of
  *     times withing a 1 second time period we block the IP for X amount of
  *     time.  We also send an email to our team alerting us of the issue.
  *
  * @param string $ip - IP to check
  *
  * @return 1 for success or 0 for failure
  */
function isDosAttack( $ip ) {

    // Initialize count for this IP if it has not been done yet.
    if (!isset($_SESSION[$ip.'_count'])) {
        $_SESSION[$ip.'_count'] = 0;
        $_SESSION[$ip.'_start_time'] = time();
    }

    if ($_SESSION[$ip.'_count'] >= 0) {
        $_SESSION[$ip.'_count']++;
        if ($_SESSION[$ip.'_count'] >= 500) {
            unset($_SESSION[$ip.'_count']);

            $diff = (time() - $_SESSION[$ip.'_start_time']);

            if ($diff < 1) { // 500 hits in 1 second is an attack on our API
                $_SESSION[$ip.'_blocked'] = time() + 9;

                // Email us..
                $to = ", Marty Israelsen <martin_israelsen@comcast.net>";
                $from = "expert_systems@gmail.com";
                $subject = "DOS Attack?? from " . $ip;

                $message = "Potential DOS Attack from: " .$ip
                    . "\nOver 500 hits in 1 second!!: "
                    . "\nBlocking their IP for 15 minutes!!: "
		    . "\nSESSION: ".print_r($_SESSION, 1)
		    . "\nSERVER: ".print_r($_SERVER, 1);

                mailIt($to, $subject, $message, $from,'no-reply@'.$_SERVER['SERVER_NAME']);

                return 1;
            }
        }
    }
    return 0;
}

/**
  * Quickly checks to see if this ip has been blocked.
  *
  * @param string $ip  - The ip to check.
  *
  * @return 1 if blocked or 0 if not blocked
  */
function isBlockedIp( $ip ) {
    $diff = time() - $_SESSION[$ip.'_blocked'];
    if ($diff < 0) {
        return 1;  // We are blocking them until timeout has been reached.
    }
    return 0;
}

/**
  * Verifies that apiKey is found in the database.
  *
  *  If apiKey is found we populate the global variable GLOBAL_APIKEY_INFO with
  *   info about the user.
  *
  * @param string $apiKey  - The apikey to validate and get info about.
  *
  * @return 1 for success or 0 for failure
  */
function getApiKeyInfo( $apiKey, &$error ) {
    global $GLOBAL_APIKEY_INFO;

    $query = "SELECT k.api_key_id, u.user_id, u.user_name, u.first_name, u.last_name, u.email
                FROM exp_sys.api_key k
                JOIN exp_sys.user u ON (u.user_id = k.user_id)
                WHERE k.api_key = $1
                AND date_range && TSRANGE(now()::DATE,now()::DATE, '[]');";
    $conn = getDBConnection();
    if(!runBindQuery($conn, $query, [$apiKey], $result, $error)) {
        return 0;
    }   

    if (!$result) {
        return 0;
    }

    $rows = pg_num_rows($result);
    if ( $rows <= 0) {
        return 0;
    }
    $GLOBAL_APIKEY_INFO = pg_fetch_assoc($result);
    $GLOBAL_APIKEY_INFO['apikey'] = $apiKey;
    return 1;
}

function getCurrentApiKeyInfo( $userId ) {
    global $GLOBAL_APIKEY_INFO;

    $query = "SELECT user_api_key_id, uak.api_key, u.userid, u.username, u.name, u.email, u.phone
                FROM user_api_key uak
                JOIN users u ON (u.userid = uak.userid)
                WHERE
                active_date_range && DATERANGE(now()::DATE,now()::DATE, '[]')
                AND u.userid = $1
                LIMIT 1;";
    if(!runBindQuery($query, [$userId], $result, $error)) {
      die($query);
        return 0;
    }
    // $result = pg_query($query);
    if (!$result) {
        return 0;
    }
    $rows = pg_num_rows($result);
    if ( $rows <= 0) {
        return 0;
    }
    $GLOBAL_APIKEY_INFO = pg_fetch_assoc($result);
    return 1;
}

/**
  * Inserts a record into api stats table representing the hit against our api.
  *
  * @param string $success      - "true" or "false" - represents if the api call was
  *        successful or not.
  * @param string $error_str    - Error string to log if success is "false"
  *
  * @return 1 for success or 0 for failure
  */
function insertIntoApiStats( $success, $error_str ) {
    global $GLOBAL_APIKEY_INFO;

    // Create json groups of the url
    $urlArray = explode("/",$_SERVER['REQUEST_URI']);
    for ($i = 0, $j = 0; $i < count($urlArray); $i++) {
        if ($i == 4) { continue; } // Skip the api key
        if ($i > 2) {               // Skip the first three fields.
            $j++;
            $group = "group".$j;
            $urlHash[$group] = $urlArray[$i];
        }
    }
    $urlJson = json_encode($urlHash);

    $user_api_key_id = $GLOBAL_APIKEY_INFO['user_api_key_id'];
    $remote_addr = getIP();//$_SERVER['REMOTE_ADDR'];
    $request_uri = $_SERVER['REQUEST_URI'];

    if ($success == 'false' && $user_api_key_id == "") {
        $user_api_key_id = 1;   // Put failed api key requests under our CLIMATE key_id for now..
    }

    $insert = "INSERT INTO user_api_stat (
            user_api_key_id,
            ip_address,
            date_time,
            url,
            json_groups,
            success,
            error_str
        ) VALUES (
            $1,
            $2,
            now(),
            $3,
            $4,
            $success,
            $5
        );";

    $param_array = array($user_api_key_id, $remote_addr, $request_uri, $urlJson, $error_str);
    if(!execOnBothServers($insert,$param_array,$error)){
        return 0;
    }

    return 1;
}


/**
  * Function to pull in a file via url.
  *
  * @param string $url - The full url to pull the file from.
  * @param string $content - returns the contents of the file
  *
  * @return 1 for success or 0 for failure
  */
function getUrlFile($url, &$content) {
    try {
        $content = file_get_contents($url);

        if ($content === false) {
            return false;
        }
    } catch (Exception $e) {
        return false;
    }

    return true;
}


/**
  * Marty Israelsen
  * 2017-09-27
  *
  * @param IN:  Integer - Database Connection
  * @param IN:  String  - $prepare_stmt - Query string template
  * @param IN:  Array   - $param_array - Variables to put into the query template
  * @param OUT: Integer - $result - Database result pointer
  * @param OUT: String  - $error_msg Error - message returned on failure
  *
  * @return 1 if query was successful
  * @return 0 if errors occur
  */
function runBindQuery( $conn, $prepare_stmt, $param_array, &$result, &$error_msg ) {
    $error_msg = "";

    try {
        $result = pg_query_params($conn, $prepare_stmt,$param_array);
        if ( $result ) {
            return 1;
        }
        else{
            // Got an error!
            $error_msg = "ERROR:  Query failed on Database!";
            return 0;
        }
    } catch ( Exception $e ) {
        $error_msg = $e->getMessage();
        return 0;
    }
    return 0;
}


?>

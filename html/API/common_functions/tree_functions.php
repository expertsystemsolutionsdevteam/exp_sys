<?php
/**
 * @author: Marty Israelsen (martin.israelsen@gmail.com)
 * @date:   2017-09-27
 *
 * This file contains core API tree functions.  These functions can be used by
 *  all other api files.
 */

function getTreeAtNode($tree, $node, $level, &$payload, &$error_msg) {

	$conn = getDBConnection();

	// TODO:  Add recursive CTE to pull out the tree a certain number of levels deep.
	$query = "WITH RECURSIVE node_cte AS (
        SELECT xp1.exp_sys_id, nd1.level, nd1.name AS text, nd1.node_id, lnk1.to_node_id
              FROM exp_sys.exp_sys xp1
              JOIN exp_sys.node nd1 ON (nd1.exp_sys_id = xp1.exp_sys_id)
              JOIN exp_sys.node_links lnk1 ON (nd1.node_id = lnk1.node_id)
              WHERE nd1.node_id = $1

          UNION

        SELECT xp2.exp_sys_id, nd2.level, nd_2.name AS text, nd_2.node_id, lnk_2.to_node_id
            FROM exp_sys.exp_sys xp2
            JOIN exp_sys.node nd2 ON (nd2.exp_sys_id = xp2.exp_sys_id)
            JOIN exp_sys.node_links lnk2 ON (nd2.node_id = lnk2.node_id)
            LEFT JOIN exp_sys.node nd_2 ON (nd_2.node_id = lnk2.to_node_id)
            LEFT JOIN exp_sys.node_links lnk_2 ON (nd_2.node_id = lnk_2.node_id)
            INNER JOIN node_cte cte1 ON cte1.node_id = nd2.node_id
      )
      SELECT *, node_id AS key FROM node_cte;";

  $param_array = array($node);
  if (!runBindQuery($conn, $query, $param_array, $result, $error_msg)) {
  	return -1;
  } 

  $rows = pg_num_rows($result);
  if ( $rows <= 0) {
      $error_msg = "No Results found for tree/node combination! tree=$tree node=$node";
      return 0;
  }
  $returnArray = pg_fetch_all($result);


  // Marshall this data into GOJS format.
  $linkDataArray = array();
  $nodeDataArray = array();
  $checkDup = array('__a__'=>1);
  $prev_node_id = -1;
  $i = 0;
  $j = 0;
  foreach ($returnArray as $rec) {
    $nodeObj = [];
    $nodeObj['from'] = $rec['node_id'];
    if ($rec['to_node_id'] == null) {
      $rec['to_node_id'] = "null";
    }
    $nodeObj['to'] = $rec['to_node_id'];
    $linkDataArray[$i] = $nodeObj;
    if ($rec['node_id'] != $prev_node_id) {
      // Check for duplcate here.  If there is one dont allow it.
      if (!isset($checkDup[$rec['node_id']])) {
        $nodeDataArray[$j] = $rec;
        $checkDup[$rec['node_id']] = 1;
        unset($rec['to_node_id']);
        $j++;
      }
    }
    $i++;
    $prev_node_id = $rec['node_id'];
  }
  $payload = new stdClass();
  $payload->class = "go.GraphLinksModel";
  $payload->nodeDataArray = $nodeDataArray;
  $payload->linkDataArray = $linkDataArray;

  return 1;
}

/**
  * @author: Marty Israelsen (martin.israelsen@gmail.com)
  * @date:   2017-10-20
  *
  * Adds a new node to an existing node in the current tree.
  *
  * @param string $tree    - The tree to add to.
  * @param integer $node   - The node we are linked from
  * @param string $error   - If error occur error is returned
  *
  * @return 1 for success or 0 for failure
  */
function deleteNode($tree, $node, &$payload, &$error_msg) {

  $conn = getDBConnection();

  $query = "DELETE FROM exp_sys.node_links WHERE node_id = $1";
  if(!runBindQuery($conn, $query, [$node], $result, $error)) {
    $error_msg = "$error";
    return 0;
  } 

  $query = "DELETE FROM exp_sys.node_links WHERE to_node_id = $1";
  if(!runBindQuery($conn, $query, [$node], $result, $error)) {
    $error_msg = "$error";
    return 0;
  } 

  $query = "DELETE FROM exp_sys.node WHERE exp_sys_id = $1 AND node_id = $2";
  $conn = getDBConnection();

  if(!runBindQuery($conn, $query, [$tree, $node], $result, $error)) {
    $error_msg = "$error";
    return 0;
  }   
  return 1;
}

/**
  * @author: Marty Israelsen (martin.israelsen@gmail.com)
  * @date:   2017-10-20
  *
  * edit a node
  *
  * @param string $tree    - The tree to add to.
  * @param integer $node   - The node we want to edit
  * @param string $error   - If error occur error is returned
  *
  * @return 1 for success or 0 for failure
  */
function editNodeName($tree, $node, $name, &$payload, &$error_msg) {

  $conn = getDBConnection();

  $query = "UPDATE exp_sys.node SET name = $1 WHERE node_id = $2";
  if(!runBindQuery($conn, $query, [$name, $node], $result, $error)) {
    $error_msg = "$error";
    return 0;
  } 
 
  return 1;
}


/**
  * @author: Marty Israelsen (martin.israelsen@gmail.com)
  * @date:   2017-10-20
  *
  * Adds a new node to an existing node in the current tree.
  *
  * @param string $tree_id    - The tree to add to.
  * @param integer $fromnode  - The node we are linked from
  * @param string $error      - If error occur error is returned
  *
  * @return 1 for success or 0 for failure
  */
function addNodeToNode($tree_id, $fromnode, &$payload, &$error_msg) {

  $conn = getDBConnection();

  $query = "INSERT INTO exp_sys.node (node_id, exp_sys_id, name, description, level, dclass_type_id) 
            VALUES (DEFAULT, $1, $2, $3, $4, $5)
            RETURNING node_id;";
  $conn = getDBConnection();

  if(!runBindQuery($conn, $query, [$tree_id, "new", "", "0", null], $result, $error)) {
    $error_msg = "$error";
    return 0;
  }   
  $new_node = pg_fetch_all($result);

  $new_node_id = $new_node[0]['node_id'];

  $query = "INSERT INTO exp_sys.node_links (node_links_id, node_id, to_node_id) 
            VALUES (DEFAULT, $1, $2);";
  if(!runBindQuery($conn, $query, [$fromnode, $new_node_id], $result, $error)) {
    $error_msg = "$error";
    return 0;
  }  

  $payload = new stdClass();
  $payload->returnInfo = $new_node[0];
  return 1;
}


/**
  * @author: Marty Israelsen (martin.israelsen@gmail.com)
  * @date:   2017-09-27
  *
  * Verifies that there is a tree out that that really exists
  *
  * @param string $tree_id  - The tree to validate
  * @param string $error    - If error occur error is returned
  *
  * @return 1 for success or 0 for failure
  */
function validTree( $tree_id, &$error ) {

    $query = "SELECT xp.exp_sys_id
                FROM exp_sys.exp_sys xp
                WHERE xp.exp_sys_id = $1;";
    $conn = getDBConnection();
    if(!runBindQuery($conn, $query, [$tree_id], $result, $error)) {
        return 0;
    }   

    $rows = pg_num_rows($result);
    if ( $rows <= 0) {
    	$error = "No expert System by that number exists! tree_id = $tree_id";
      return 0;
    }
    return 1;
}

/**
  * @author: Marty Israelsen (martin.israelsen@gmail.com)
  * @date:   2017-10-20
  *
  * Verifies that there is a tree/node that exists.
  *
  * @param string $tree  - The tree to validate
  * @param stting $tonode  - The node to validate
  * @param string $error    - If error occur error is returned
  *
  * @return 1 for success or 0 for failure
  */
function validNode( $tree, $tonode, &$error ) {

    $query = "SELECT xp.exp_sys_id
                FROM exp_sys.exp_sys xp
                JOIN exp_sys.node nd ON (xp.exp_sys_id = nd.exp_sys_id)
                WHERE xp.exp_sys_id = $1 AND nd.node_id = $2;";
    $conn = getDBConnection();
    if(!runBindQuery($conn, $query, [$tree, $tonode], $result, $error)) {
        return 0;
    }   

    $rows = pg_num_rows($result);
    if ( $rows <= 0) {
      $error = "No Tree/Node combination exists! tree = $tree, node = $tonode";
      return 0;
    }
    return 1;
}

?>
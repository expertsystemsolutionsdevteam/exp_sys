<?php
require 'Slim/Slim.php';
\Slim\Slim::registerAutoloader();
$app = new \Slim\Slim();

//$GLOBAL_APIKEY_INFO = array();

include_once('common_functions/coreUtils.php');
include_once('common_functions/core_functions.php');
include_once('common_functions/tree_functions.php');
//$db = connect2DB();

 $app->hook('slim.before.dispatch', function() use ($app){
       $key = $app->router()->getCurrentRoute()->getParam('key');
//     $ip = getIP(); //$_SERVER['REMOTE_ADDR'];

//     if (isDosAttack($ip)) {
//         die(errorReturn("Blocking IP"));
//     }
//     if (isBlockedIp($ip)) {
//         die(":-(");
//     }
     if (!getApiKeyInfo($key, $error)) {
         die(returnError("Invalid key. $key"));
     }
 });

// /key/{key}/v1/get/tree/{tree#}/node/{node#}/levels/{#}
$app->group('/v1', function () use ($app) {
    $app->group('/key=:key', function () use ($app) {
        $app->group('/get',function () use ($app) {
            $app->group('/tree=:tree', function() use ($app) {
                $app->get('/node=:node', function ($key, $tree, $node) {
                    if (!validTree($tree, $error)) {
                        die(returnError("Sorry, not a valid tree!  $error"));
                    } else {
                        if (!getTreeAtNode($tree, $node, 2, $payload, $error_msg)) {
                            die(returnError("ERROR: $error_msg"));
                        } else {
                            die(returnSuccess($payload));
                        }                       
                    }
                });

                $app->get('/node=:node/levels=:levels', function ($key, $tree, $node, $levels) {
                    if (validDataSource($source)) {
                        $r = getTreeAtNode($tree, $node, $levels);
                        die(returnSuccess($r));
                    } else {
                        die(returnError("Sorry, not a valid state.   data state=$state"));
                    }
                });
            });


        });
        $app->group('/addnode',function () use ($app) {
            $app->group('/tree=:tree', function() use ($app) {
                $app->get('/tonode=:tonode', function($key, $tree, $tonode) {
                    if (!validNode($tree, $tonode, $error)) {
                       die(returnError("Sorry, not a valid tree/node!  $error")); 
                    } else {
                        if (!addNodeToNode($tree, $tonode, $payload, $error)) {
                            die(returnError("ERROR: $error"));
                        } else {
                            die(returnSuccess($payload));
                        }                       
                    }                            
                });
            });
        });
        $app->group('/delnode',function () use ($app) {
            $app->group('/tree=:tree', function() use ($app) {
                $app->get('/node=:node', function($key, $tree, $node) {
                    if (!validNode($tree, $node, $error)) {
                       die(returnError("Sorry, not a valid tree/node!  $error")); 
                    } else {
                        if (!deleteNode($tree, $node, $payload, $error)) {
                            die(returnError("ERROR: $error"));
                        } else {
                            die(returnSuccess($payload));
                        }                       
                    }                            
                });
            });
        });
        $app->group('/editnode',function () use ($app) {
            $app->group('/tree=:tree', function() use ($app) {
                $app->get('/node=:node/name=:name', function($key, $tree, $node, $name) {
                    if (!validNode($tree, $node, $error)) {
                       die(returnError("Sorry, not a valid tree/node!  $error")); 
                    } else {
                        if (!editNodeName($tree, $node, $name, $payload, $error)) {
                            die(returnError("ERROR: $error"));
                        } else {
                            die(returnSuccess($payload));
                        }                       
                    }                            
                });
            });
        });
    });
});

// POST route
$app->post(
    '/post',
    function () {
        echo 'This is a POST route';
    }
);

// PUT route
$app->put(
    '/put',
    function () {
        echo 'This is a PUT route';
    }
);

// PATCH route
$app->patch('/patch', function () {
    echo 'This is a PATCH route';
});

// DELETE route
$app->delete ('/delete',
    function () {
        echo 'This is a DELETE route';
    }
);

$app->get('', function () {

    echo "Hello world!!";

});

/**
 * Step 4: Run the Slim application
 *
 * This method should be called last. This executes the Slim application
 * and returns the HTTP response to the HTTP client.
 */
$app->run();

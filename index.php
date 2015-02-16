<?php

$action = 'default';
if( isset( $_GET['action'] ) && $_GET['action'] != '' ) {
    $action = $_GET['action'];
}

if( isset( $argv[1] ) && $argv[1] != '' ) {
    $action = $argv[1];
}

switch( $action ) {
    case 'update':
        require_once( "../../common.php" );
        require_once( __DIR__ . "/classes/Fleedicon.php" );
        Fleedicon::setAllFavicons();
        break;
    case 'default':
        echo 'Nothing to do';
        break;
}

?>

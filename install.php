<?php

require_once('classes/Fleedicon.php');
$plugins_path = Plugin::path();

touch($plugins_path.'check');

$feed = new Feed();
$conditions = 'SELECT id, website FROM `' . MYSQL_PREFIX .  'feed` ;';
$query = $feed->customQuery($conditions);

while( $feed = mysql_fetch_assoc($query) ) {
    $fleeicon = new Fleedicon($feed['id'], $plugins_path);
    $fleeicon->setFavicon($feed['website']);
}

?>

<?php

require_once('classes/Fleedicon.php');
$plugin_path = Plugin::path();

Fleedicon::createCheckDateFile($plugin_path);

$feed = new Feed();
$conditions = 'SELECT id, website FROM `' . MYSQL_PREFIX .  'feed` ;';
$query = $feed->customQuery($conditions);

while( $feed = mysql_fetch_assoc($query) ) {
    $fleeicon = new Fleedicon($feed['id'], $plugin_path);
    $fleeicon->setFavicon(true, $feed['website']);
}

?>

<?php

/*
  @name Fleedicon_content
  @author Simounet <contact@simounet.net>
  @initby gavrochelegnou <gavrochelegnou@trashmail.net>
  @licence CC by nc sa http://creativecommons.org/licenses/by-nc-sa/2.0/fr/
  @version 2.0.0
  @description Add a the site's favicon on the left of each feed (setup can take a while if you have many feeds)
 */


require( 'classes/Fleedicon.php' );

function fleedicon_content_plugin_addFavicon(&$event) {
    $path = Plugin::path();
    $fleedicon = new Fleedicon($event->getFeed(), $path);
    $event->favicon = $fleedicon->action();
}

function fleedicon_aside_plugin_addFavicon(&$feed) {
    $path = Plugin::path();
    $fleedicon = new Fleedicon($feed['id'], $path);
    $feed['favicon'] = $fleedicon->action();
}


Plugin::addHook("event_pre_title", "fleedicon_content_plugin_addFavicon");

Plugin::addHook("menu_pre_feed_link", "fleedicon_aside_plugin_addFavicon");


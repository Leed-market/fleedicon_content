<?php

/*
  @name Fleedicon_content
  @author gavrochelegnou <gavrochelegnou@trashmail.net>
  @author Simounet <contact@simounet.net>
  @licence CC by nc sa http://creativecommons.org/licenses/by-nc-sa/2.0/fr/
  @version 1.5.0
  @description Le plugin Fleedicon_content ajoute un favicon à gauche de chaque item lors de la lecture
 */


require( 'classes/Fleedicon.php' );

/*
 * Affiche un favicon en fonction d'un objet "event"
 * @param event $event
 */
function fleedicon_content_plugin_addFavicon(&$event) {
    $path = Plugin::path();
    $fleedicon = new Fleedicon($event->getFeed(), $path);
    $event->favicon = $fleedicon->action();
}

/**
 * Affiche un favicon en fonction d'un tableau "feed"
 * @param type $feed
 */
function fleedicon_aside_plugin_addFavicon(&$feed) {
    $path = Plugin::path();
    $fleedicon = new Fleedicon($feed['id'], $path);
    $feed['favicon'] = $fleedicon->action();
}


/**
 * Ajout de l'icone à coté de chaque item
 */
Plugin::addHook("event_pre_title", "fleedicon_content_plugin_addFavicon");

/**
 * Ajout de l'icone à coté du flux
 */
Plugin::addHook("menu_pre_feed_link", "fleedicon_aside_plugin_addFavicon");


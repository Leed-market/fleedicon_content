<?php

/*
  @name Fleedicon_content
  @author gavrochelegnou <gavrochelegnou@trashmail.net>
  @author Simounet <contact@simounet.net>
  @licence CC by nc sa http://creativecommons.org/licenses/by-nc-sa/2.0/fr/
  @version 1.5.0
  @description Le plugin Fleedicon_content ajoute un favicon à gauche de chaque item lors de la lecture
 */

/**
 * Télécharge le Favicon en fonction de l'ID d'un flux
 * @param int $feed_id
 */
function fleedicon_content_plugin_getFavicon($feed_id) {

    $basePath = Plugin::path() . 'favicons/';
    $icon_path = $basePath . $feed_id . '.png';
    $iconExists = file_exists( $icon_path );

    $defaultIconPath = $basePath . 'default.png';

    $newCheck = new DateTime( getCheckDate() );
    $newCheck->add( new DateInterval('P1M') );

    $today = new DateTime( date('Y-m-d') );

    if( $iconExists === false && $today > $newCheck ) {
    //if( 1 ) {

        setFavicon( $feed_id );

    }

    if( $iconExists === false ) {

        $icon_path = $defaultIconPath;

    }

    /**
     * Besoin de ça pour renseigner correctement le ALT
     */
    global $allFeeds;

    /**
     * Et l'image brute, sans CSS
     */
    return '<img src="' . $icon_path . '" width="16" height="16" alt="' . htmlentities($allFeeds['idMap'][$feed_id]['name'], ENT_QUOTES) . '" />';
}

function setDefaultFavicon($path) {
    copy(Plugin::path() . 'default.png', $path);
}

function setCheckDate($date) {
    file_put_contents( __DIR__ . '/check', $date );
}

function getCheckDate() {
    $content = file_get_contents( __DIR__ . '/check' );
    return $content;
}

function getFaviconFromUrl($url) {
    //echo 'in: ' . $url . '<br />';
    $h = @fopen($url, 'r');
    if ($h) {
        $context = stream_context_create(
                array (
                    'http' => array (
                        'follow_location' => false // don't follow redirects
                    )
                )
            );
        $html = file_get_contents($url, false, $context);
////echo '<pre>' . print_r( $html, true ) . '</pre>'; exit;
        if (preg_match('/<([^>]*)link([^>]*)rel\=("|\')?(icon|shortcut icon)("|\')?([^>]*)>/iU', $html, $out)) {
    //echo 'match out: <pre>' . print_r( $out, true ) . '</pre><br />';
            if (preg_match('/href([s]*)=([s]*)"([^"]*)"/iU', $out[0], $out)) {
    //echo 'match out2: <pre>' . print_r( $out, true ) . '</pre><br />';
                $ico_href = trim($out[3]);
    //echo 'ico href: <pre>' . print_r( $out, true ) . '</pre><br />';
                if (preg_match('/(http)(s)?(:\/\/)/', $ico_href, $matches, PREG_OFFSET_CAPTURE)) {
                    $ico_url = $ico_href;
    //echo 'ico url: ' . $ico_url . '<br />';
                } elseif (preg_match('/(\/\/)/', $ico_href, $matches, PREG_OFFSET_CAPTURE)) {
                    $ico_url = 'http:' . $ico_href;
    //echo 'ico url2: ' . $ico_url . '<br />';
                } else {
                    $ico_url = $url . '/' . ltrim($ico_href, '/');
    //echo 'else: ' . $ico_url . '<br />';
                }
            }
        }
    }
    $ico_url = isset( $ico_url ) ? $ico_url : $url . 'favicon.ico';
//echo 'before headers: ' . $ico_url . '<br />';
    
    $headers = @get_headers($ico_url, 1);
//echo 'headers: <pre>' . print_r( $headers, true ) . '</pre><br />';
    if ($headers && isset( $headers['Location'] ) ) {
        $headers['Location'] = is_array($headers['Location']) ? end($headers['Location']) : $headers['Location'];
        $ico_url = $headers['Location'];
        $headers = @get_headers($ico_url, 1);
//echo 'headers img: <pre>' . print_r( $headers, true ) . '</pre><br />';
    }

    if (preg_match('/(200 OK)|(302 Found)/', $headers[0], $matches, PREG_OFFSET_CAPTURE)) {
//echo 'match!<br /><br />';
        if ( getimagesize( $ico_url ) ) {
            //echo 'oui';
            return $ico_url;
        } else {
            //echo 'not';
        }
    }
    
    return false;
}

function setFavicon( $feed_id ) {

    /**
     * On récupère les infos du flux
     */
    $f = new Feed();
    $f = $f->getById($feed_id);

    /**
     * Et notamment le site web
     * Plus pertinent que l'URL du flux à cause notamment de feedburner
     */
    $url = $f->getWebsite();

    /**
     * Si l'URL est inexistante ou malformée on essaie 
     * quand même avec l'URL du flux
     */
    if (!$url) {
        $url = $f->getUrl();
    }

    if ($url) {
        if( $favicon_url = getFaviconFromUrl($url) ) {
            file_put_contents( $iconPath, file_get_contents($favicon_url) );
        } else {
            $url = parse_url($url);
            /**
             * Si l'une des deux marche on essai d'appeler le service g.etfv.co
             */
            $favicon = file_get_contents('http://g.etfv.co/' . $url['scheme'] . '://' . $url['host']);
            if( getimagesize($favicon) ) {
                file_put_contents($iconPath, file_get_contents('http://g.etfv.co/' . $url['scheme'] . '://' . $url['host']));
            } else {
                setDefaultFavicon($defaultIconPath);
            }
        }
    } else {
        /**
         * Sinon on utilise l'icône par défaut
         */
        setDefaultFavicon($defaultIconPath);
    }

    setCheckDate($today->format('Y-m-d'));

}

/**
 * Affiche un favicon en fonction d'un objet "event"
 * @param event $event
 */
function fleedicon_content_plugin_addFavicon(&$event) {
    $event->favicon = fleedicon_content_plugin_getFavicon($event->getFeed());
}

/**
 * Affiche un favicon en fonction d'un tableau "feed"
 * @param type $feed
 */
function fleedicon_aside_plugin_addFavicon(&$feed) {
    $feed['favicon'] = fleedicon_content_plugin_getFavicon($feed['id']);
}


/**
 * Ajout de l'icone à coté de chaque item
 */
Plugin::addHook("event_pre_title", "fleedicon_content_plugin_addFavicon");

/**
 * Ajout de l'icone à coté du flux
 */
Plugin::addHook("menu_pre_feed_link", "fleedicon_aside_plugin_addFavicon");


<?php

/**
 * Création du dossier de favicons
 */

$pluginsPath = Plugin::path() . 'favicons/';
if (file_exists($pluginsPath)) {
    $favicons = glob($pluginsPath.'*');
    foreach ($favicons as $favicon) {
        unlink($favicon);        
    }
    unlink($pluginsPath);
}
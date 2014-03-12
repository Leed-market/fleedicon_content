<?php

require_once( 'classes/Fleedicon.php' );

$plugin_path = Plugin::path();
Fleedicon::removeAllFavicons($plugin_path);
Fleedicon::removeCheckDateFile($plugin_path);

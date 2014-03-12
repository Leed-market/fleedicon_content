<?php

$plugins_path = Plugin::path();
$favicons_path = $plugins_path.'favicons/';
if (file_exists($favicons_path)) {
    $favicons = preg_grep('/default\.png$/', glob($favicons_path.'*'), PREG_GREP_INVERT);
    foreach ($favicons as $favicon) {
        unlink($favicon);        
    }
    unlink($plugins_path.'check');
}

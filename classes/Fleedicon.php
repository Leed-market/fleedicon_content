<?php

class Fleedicon {

    protected $feed_id;

    protected $plugin_path;
    protected static $icons_folder = 'favicons/';
    protected static $check_date_file = 'check';
    protected $base_path;
    protected $icon_path;
    protected $icon_exists;
    protected $default_icon_path;

    protected $today;

    protected $debug;

    public function __construct($feed_id, $path, $debug=false) {

        $this->feed_id = $feed_id;
        $this->plugin_path = $path;

        $this->base_path = $this->plugin_path . self::$icons_folder;
        $this->icon_path = $this->base_path . $this->feed_id . '.png';
        $this->icon_exists = file_exists( $this->icon_path );
        $this->default_icon_path = $this->base_path . 'default.png';

        $this->today = new DateTime( date('Y-m-d') );

        $this->debug = $debug;
    }

    public function action() {
        $new_check = $this->isNewCheck();

        if( (   $this->icon_exists === false 
             && $this->today > $new_check 
            )
            || $this->debug ) {
            $this->setFavicon();
        }

        if( $this->icon_exists === false ) {
            $this->icon_path = $this->default_icon_path;
        }

        /**
         * Besoin de ça pour renseigner correctement le ALT
         */
        global $allFeeds;

        return '<img src="' . $this->icon_path . '" width="16" height="16" alt="' . htmlentities($allFeeds['idMap'][$this->feed_id]['name'], ENT_QUOTES) . '" />';
    }

    public function setFavicon($set_check_date=true, $url=false) {

        if(!$url) {
            $f = new Feed();
            $f = $f->getById($this->feed_id);

            $url = $f->getWebsite();

            if (!$url) {
                $url = $f->getUrl();
            }
        }

        if($url) {
            $favicon = $this->getFaviconFromUrl($url);

            if($favicon !== false) {
                file_put_contents($this->icon_path, $favicon);
            } else {
                $etfv_url = 'http://g.etfv.co/' . $url . '?defaulticon=none';
                $favicon = $this->image($etfv_url);

                if($favicon !== false) {
                    file_put_contents($this->icon_path, $favicon);
                }
            }
        }

        if($set_check_date===true) {
            $this->setCheckDate($this->today->format('Y-m-d'));
        }

    }

    public function removeFavicon() {
        unlink($this->base_path.$this->feed_id.'.png');
    }

    protected function setCheckDate($date) {
        file_put_contents( $this->plugin_path . self::$check_date_file, $date );
    }

    protected function getCheckDate() {
        $content = file_get_contents( $this->plugin_path . self::$check_date_file );
        return $content;
    }

    protected function getFaviconFromUrl($url) {
        // Helped by: https://github.com/gokercebeci/geticon/blob/master/class.geticon.php
        $ico_checked = false;

        $logs[] = 'in: ' . $url;

         if($h = @fopen($url, 'r')) {
            $context = stream_context_create(
                    array (
                        'http' => array (
                            'follow_location' => false // don't follow redirects
                        )
                    )
                );
            $html = file_get_contents($url, false, $context);
            $logs[] = '<pre>' . print_r( $html, true ) . '</pre>';
            if (preg_match('/<([^>]*)link([^>]*)rel\=("|\')?(icon|shortcut icon)("|\')?([^>]*)>/iU', $html, $out)) {
                $logs[] = 'match out: <pre>' . print_r( $out, true ) . '</pre>';
                if (preg_match('/href([s]*)=([s]*)"([^"]*)"/iU', $out[0], $out)) {
                    $logs[] = 'match out2: <pre>' . print_r( $out, true ) . '</pre>';
                    $ico_href = trim($out[3]);
                    $logs[] = 'ico href: <pre>' . print_r( $out, true ) . '</pre>';
                    if (preg_match('/(http)(s)?(:\/\/)/', $ico_href, $matches, PREG_OFFSET_CAPTURE)) {
                        $ico_url = $ico_href;
                        $logs[] = 'ico url: ' . $ico_url;
                    } elseif (preg_match('/(\/\/)/', $ico_href, $matches, PREG_OFFSET_CAPTURE)) {
                        $ico_url = 'http:' . $ico_href;
                        $logs[] = 'ico url2: ' . $ico_url;
                    } else {
                        $ico_url = $url . ltrim($ico_href, '/');
                        $logs[] = 'else: ' . $ico_url;
                    }
                }
            }
        }

        if(!isset($ico_url)) {
            $parsed_url = parse_url($url);
            $base_url = $parsed_url['scheme'].'://'.$parsed_url['host'];
            $ico_url = $base_url . '/favicon.ico';
            if($this->fileExists($ico_url)) {
                $ico_checked = true;
            }
        }

        if($this->debug) {
            foreach($logs as $log) {
                echo $log;
            }
        }

        return $this->image($ico_url, $ico_checked);
    }

    protected function image($url, $checked=false) {
        if(!$this->fileExists($url)) {
            return false;
        }

        $finfo = new finfo(FILEINFO_MIME_TYPE);
        $file = file_get_contents($url);
        $mime_type = $finfo->buffer($file);

        $pos = strpos($mime_type, 'image');

        return $pos !== false ? $file : false;
    }

    protected function fileExists($url) {
        return (@fopen($url,"r")==true);
    }

    protected function isNewCheck() {
        $new_check = new DateTime( $this->getCheckDate() );
        $new_check->add( new DateInterval('P1M') );

        return $new_check;

    }
}

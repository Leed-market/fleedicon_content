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
        if( (   $this->icon_exists === false 
             && $this->today > $this->getNewCheckDate()
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
                $service_url = 'http://grabicon.com/icon?domain=' . $url;
                $favicon = $this->getImage($service_url);

                if($favicon !== false) {
                    file_put_contents($this->icon_path, $favicon);
                }
            }
        }

        if($set_check_date===true) {
            $this->setCheckDate($this->today->format('Y-m-d'));
        }

    }

    public static function setAllFavicons($plugin_path = './') {
        $feed = new Feed();
        $conditions = 'SELECT id, website FROM `' . MYSQL_PREFIX .  'feed` ;';
        $query = $feed->customQuery($conditions);

        while( $feed = $query->fetch_assoc() ) {
            $fleedicon = new Fleedicon($feed['id'], $plugin_path);
            if(!$fleedicon->icon_exists) {
                $fleedicon->setFavicon(true, $feed['website']);
            }
        }
    }

    public function removeFavicon( $path = false ) {
        if( ! $path ) {
            $path = $this->base_path . $this->feed_id . '.png';
        }

        self::deleteFavicon( $path );
    }

    public static function removeAllFavicons($plugin_path) {
        $favicons_path = $plugin_path . self::$icons_folder;
        if (file_exists($favicons_path)) {
            $favicons = preg_grep('/default\.png$/', glob($favicons_path.'*'), PREG_GREP_INVERT);
            foreach ($favicons as $favicon) {
                self::deleteFavicon( $favicon );
            }
        }
   }

    protected static function deleteFavicon( $path ) {
        if( ! file_exists( $path ) ) {
            return false;
        }
        unlink( $path );
    }

    protected function setCheckDate($date) {
        $filepath = $this->plugin_path . self::$check_date_file;
        self::createIfNotExists($filepath);
        file_put_contents( $filepath, $date );
    }

    protected function getCheckDate() {
        $content = file_get_contents( $this->plugin_path . self::$check_date_file );
        return $content;
    }

    public static function createIfNotExists($filepath) {
        if(!file_exists($filepath)) {
            touch($filepath);
        }
    }

    public static function removeCheckDateFile($plugin_path) {
        $check_date_file = $plugin_path . self::$check_date_file;
        if(file_exists($check_date_file)) {
            unlink($check_date_file);
        }
    }

    protected function getFaviconFromUrl($url) {
        // Helped by: https://github.com/gokercebeci/geticon/blob/master/class.geticon.php
        $logs[] = 'in: ' . $url;

        $user_agent = $this->getUserAgent();

        $context = stream_context_create(
                array (
                    'http' => array (
                        'follow_location' => true, // don't follow redirects
                        'user_agent' => $user_agent
                    )
                )
            );
        $html = @file_get_contents($url, false, $context);
        //$html = @stream_get_contents(fopen($url, "rb"));
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
                    if( strpos( $ico_href, '/' ) === 0 ) {
                        // If the icon url starts with /
                        // We use the host to form the absolute URL
                        $parsed_url = parse_url( $url );
                        $ico_url = $parsed_url['scheme'] . '://' . $parsed_url['host']  . $ico_href;
                    } else {
                        // If not, we are using the relative path
                        $ico_url = $url . ltrim($ico_href, '/');
                    }
                    $logs[] = 'else: ' . $ico_url;
                }
            }
        }

        if(!isset($ico_url)) {
            $parsed_url = parse_url($url);
            $base_url = $parsed_url['scheme'].'://'.$parsed_url['host'];
            $ico_url = $base_url . '/favicon.ico';
        }

        if($this->debug) {
            foreach($logs as $log) {
                echo $log;
            }
        }

        return $this->getImage($ico_url);
    }

    protected function getImage($url) {
        $finfo = new finfo(FILEINFO_MIME_TYPE);
        // Used for Grabicon.com
        $user_agent = $this->getUserAgent();
        $header_options = array(
          'http' => // The wrapper to be used
            array(
            'method'  => 'GET', // Request Method
            'header' => "Referer: " . $_SERVER["HTTP_HOST"] . $_SERVER['REQUEST_URI'] . "\r\n",
            'user_agent' => $user_agent
          )
        );
        $context = stream_context_create( $header_options );

        $file = @file_get_contents($url, false, $context);
        if($file === FALSE) {
            return false;
        }
        $mime_type = $finfo->buffer($file);

        $pos = strpos($mime_type, 'image');

        return $pos !== false ? $file : false;
    }

    protected function getNewCheckDate() {
        $new_check = new DateTime( $this->getCheckDate() );
        $new_check->add( new DateInterval('P1M') );

        return $new_check;
    }

    protected function getUserAgent() {
        $user_agent = 'Leed (X11; Linux i686; rv:37.0) Gecko/20100101 Firefox/37.0';
        if( isset($_SERVER['HTTP_USER_AGENT']) ) {
            $user_agent = $_SERVER['HTTP_USER_AGENT'];
        }
        return $user_agent;
    }
}

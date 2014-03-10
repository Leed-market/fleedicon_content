<?php

class Fleedicon {

    protected $feed_id;

    protected $plugin_path;
    protected $base_path;
    protected $icon_path;
    protected $icon_exists;
    protected $default_icon_path;

    protected $new_check;
    protected $today;

    public function __construct($feed_id, $path) {

        $this->feed_id = $feed_id;
        $this->plugin_path = $path;

        $this->base_path = $this->plugin_path . 'favicons/';
        $this->icon_path = $this->base_path . $this->feed_id . '.png';
        $this->icon_exists = file_exists( $this->icon_path );
        $this->default_icon_path = $this->base_path . 'default.png';

        $new_check = new DateTime( $this->getCheckDate() );
        $new_check->add( new DateInterval('P1M') );

        $this->new_check = $new_check;

        $this->today = new DateTime( date('Y-m-d') );

    }

    public function action() {

        if( $this->icon_exists === false && $this->today > $this->new_check ) {
        //if( 1 ) {

            setFavicon( $this->feed_id );

        }

        if( $this->icon_exists === false ) {

            $this->icon_path = $this->default_icon_path;

        }

        /**
         * Besoin de ça pour renseigner correctement le ALT
         */
        global $allFeeds;

        /**
         * Et l'image brute, sans CSS
         */
        return '<img src="' . $this->icon_path . '" width="16" height="16" alt="' . htmlentities($allFeeds['idMap'][$this->feed_id]['name'], ENT_QUOTES) . '" />';
    }

    protected function setDefaultFavicon($path) {
        copy(Plugin::path() . 'default.png', $path);
    }

    protected function setCheckDate($date) {
        file_put_contents( $this->plugin_path . 'check', $date );
    }

    protected function getCheckDate() {
        $content = file_get_contents( $this->plugin_path . 'check' );
        return $content;
    }

    protected function getFaviconFromUrl($url) {
        // [todo] - Use debug var instead of these comments
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

    protected function setFavicon( $feed_id ) {

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
                    setDefaultFavicon($this->default_icon_path);
                }
            }
        } else {
            /**
             * Sinon on utilise l'icône par défaut
             */
            setDefaultFavicon($this->default_icon_path);
        }

        setCheckDate($this->today->format('Y-m-d'));

    }
}

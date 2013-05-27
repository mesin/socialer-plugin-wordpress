<?php

/**
 * @author: Dmitriy Meshin <0x7ffec at gmail.com>
 * @date: 22 May 2013
 * @time: 12:54 PM
 */

session_start();

require_once ( 'Crypt.php' );

class Socialer {

    const JS_VERSION = '1.1';
    const SIGNATURE_LIFETIME = 3600;

    /**
     * @var array
     */
    protected static $options = array();

    public function init() {
        // showing notices if tweet error occurred
        //add_action('edit_form_after_title', array($this, 'showMessage'));

        // publishing tweet
        add_action('publish_post', array('Socialer', 'push_tweet'));

        // showing textarea or register button
        add_action('edit_form_after_editor', array('Socialer', 'show_tweet_box_or_register_button'));

        // ajax calls
        add_action('socialer_ajax_is_user_registered', array( $this, 'ajax_is_user_registered' ) );
        add_action('socialer_ajax_get_register_button', array( $this, 'ajax_get_socialer_register_button' ) );
        add_action('socialer_ajax_get_tweet_box', array( $this, 'ajax_get_tweet_box' ) );
        add_action('socialer_ajax_push_tweet', array( $this, 'ajax_push_tweet' ) );
    }

    public function ajax_push_tweet() {
        // check if user registered
        $response = wp_remote_retrieve_body(
            wp_remote_request(
                self::get_socialer_user_registered_callback_url(),
                array(
                    'method' => 'GET',
                    'sslverify' => true,
                )
            )
        );

        $response = json_decode($response);

        if ( $response && isset($response->success) && $response->success ) {

            $permalink = get_permalink($_GET['post']);
            $_POST['text'] = trim($_POST['text'], ',undefined');

            $response = wp_remote_retrieve_body(
                wp_remote_request(
                    self::get_socialer_push_tweet_callback_url(),
                    array(
                        'method' => 'POST',
                        'body' => array('text' => $_POST['text'] . ' ' . $permalink),
                        'sslverify' => true,
                    )
                )
            );

            $response = json_decode($response);
            $errors_messages = '';
            $errors_messages_presents = false;

            if ( isset($response->result) && isset($response->result->errors) ) {
                if ( is_array($response->result->errors) ) {
                    foreach ($response->result->errors as $error) {
                        if ( isset($error->message) ) {
                            $errors_messages_presents = true;
                            $errors_messages .= '<br>- '.$error->message;
                        }
                    }
                }
            }

            if ( $errors_messages_presents ) {
                $errors_messages = '<strong>Error occurred during tweet posting!</strong>' . $errors_messages;
                $_SESSION['soc_notices'] = $errors_messages;
                $_SESSION['soc_notices_is_error'] = true;
                $_SESSION['soc_last_tweet_status'] = false;
                $_SESSION['soc_last_tweet_text'] = $_POST['text'];
                die(json_encode(array(
                    'message'   => self::showMessage(),
                    'error'     => true
                )));
            }

            // no errors - show information that all OK
            $screen_name = '';
            $id_str = '';

            if ( $response && isset($response->result) ) {
                if ( isset($response->result->user) && isset($response->result->user->screen_name) ) {
                    $screen_name = $response->result->user->screen_name;
                }

                if ( isset($response->result->id_str) ) {
                    $id_str = $response->result->id_str;
                }
            }

            if ( $screen_name && $id_str ) {
                $_SESSION['soc_notices'] = "Tweet Posting was successful. <a href='http://twitter.com/"
                    . $screen_name . "/status/"
                    . $id_str . "' target='_blank'>View Tweet</a>";

                $_SESSION['soc_notices_is_error'] = false;
                $_SESSION['soc_last_tweet_status'] = true;
                unset($_SESSION['soc_last_tweet_text']);

                die(json_encode(array(
                    'message'   => self::showMessage(),
                    'success'   => true
                )));
            }

            $_SESSION['soc_notices_is_error'] = true;
            $_SESSION['soc_last_tweet_status'] = false;
            $_SESSION['soc_notices'] = 'Something is wrong. Please try again later';

            die(json_encode(array(
                'message'   => self::showMessage(),
                'error'     => true
            )));
        }
    }

    public function push_tweet() {

        if (
            0
            // TODO: or not checked send checkbox!
        ) {
            return;
        }

        // check if user registered
        $response = wp_remote_retrieve_body(
            wp_remote_request(
                self::get_socialer_user_registered_callback_url(),
                array(
                    'method' => 'GET',
                    'sslverify' => true,
                )
            )
        );

        $response = json_decode($response);

        if ( $response && isset($response->success) && $response->success ) {

            $permalink = get_permalink(get_the_ID());
            $_POST['socialer_tweet_body'] = trim($_POST['socialer_tweet_body'], ',undefined');

            $response = wp_remote_retrieve_body(
                wp_remote_request(
                    self::get_socialer_push_tweet_callback_url(),
                    array(
                        'method' => 'POST',
                        'body' => array('text' => $_POST['socialer_tweet_body'] . ' ' . $permalink),
                        'sslverify' => true,
                    )
                )
            );

            $response = json_decode($response);
            $errors_messages = '';
            $errors_messages_presents = false;

            if ( isset($response->result) && isset($response->result->errors) ) {
                if ( is_array($response->result->errors) ) {
                    foreach ($response->result->errors as $error) {
                        if ( isset($error->message) ) {
                            $errors_messages_presents = true;
                            $errors_messages .= '<br>- '.$error->message;
                        }
                    }
                }
            }

            if ( $errors_messages_presents ) {
                $errors_messages = '<strong>Error occurred during tweet posting!</strong>' . $errors_messages;
                $_SESSION['soc_notices'] = $errors_messages;
                $_SESSION['soc_notices_is_error'] = true;
                $_SESSION['soc_last_tweet_status'] = false;
                $_SESSION['soc_last_tweet_text'] = $_POST['socialer_tweet_body'];
                return;
            }

            // no errors - show information that all OK
            $screen_name = '';
            $id_str = '';

            if ( $response && isset($response->result) ) {
                if ( isset($response->result->user) && isset($response->result->user->screen_name) ) {
                    $screen_name = $response->result->user->screen_name;
                }

                if ( isset($response->result->id_str) ) {
                    $id_str = $response->result->id_str;
                }
            }

            if ( $screen_name && $id_str ) {
                $_SESSION['soc_notices'] = "Tweet Posting was successful. <a href='http://twitter.com/"
                                                . $screen_name . "/status/"
                                                . $id_str . "' target='_blank'>View Tweet</a>";
            }

            $_SESSION['soc_notices_is_error'] = false;
            $_SESSION['soc_last_tweet_status'] = true;
            unset($_SESSION['soc_last_tweet_text']);
        }
    }

    function showMessage()
    {
        $messages = '';
        if (
            isset($_SESSION['soc_notices'])
            && trim($_SESSION['soc_notices'])
        ) {
            $errormsg = !(isset($_SESSION['soc_last_tweet_status']) && $_SESSION['soc_last_tweet_status']);

            if ($errormsg) {
                $messages = '<div class="error" style="padding: 8px">';
            } else {
                $messages = '<div class="updated" style="padding: 8px">';
            }

            $messages .= $_SESSION['soc_notices'] . "</div>";
        }

        unset($_SESSION['soc_notices']);
        unset($_SESSION['soc_notices_is_error']);

        return $messages;
    }

    public function ajax_is_user_registered() {
        $response = wp_remote_retrieve_body(
            wp_remote_request(
                self::get_socialer_user_registered_callback_url(),
                array(
                    'method' => 'GET',
                    'sslverify' => true,
                )
            )
        );

        die($response);
    }

    /**
     * @return bool
     */
    public function is_debug_mode() {
        return true;
    }

    /**
     * @return string
     */
    protected function get_user_wp_uid() {
        return  'wp-'.get_current_blog_id().
                '-'.get_current_user_id().
                '-'.sha1(wp_get_current_user()->get('user_registered'));
    }

    /**
     * @return string
     */
    protected function get_site_id() {
        return get_current_blog_id();
    }

    /**
     *  Finding out if user is registered and showing tweet box or register button
     */
    public function show_tweet_box_or_register_button() {
        ?>
        <script type="text/javascript" src="<?php echo site_url( '/wp-includes/js/jquery/jquery.js' ) ?>"></script>
        <script type="text/javascript" src="<?php echo plugins_url( 'js/socialer.js', __FILE__ ) .'?v='.self::JS_VERSION ?>"></script>
        <div id="socialer-container" style="display: none"></div>
        <div id="socialer-container-wait" style="display: none">
            <br>
            <img src="<?php echo plugins_url( 'js/img/ajax-loader.gif', __FILE__ ) ?>" />
        </div>
        <div style="display: none"
             class="alljs-dispatcher"
             id="alljs-dispatcher-socialer"
             data-function="alljs.socialer.get_correct_box"
             data-base-url="<?php echo plugins_url( 'socialer_ajax.php?action=', __FILE__ ) ?>"
             <?php if (@$_GET['post']): ?>
             data-post-id="<?php echo @$_GET['post'] ?>"
             <?php endif ?>
        ></div>
        <?php
    }

    public function ajax_get_tweet_box() {
        $permalink = '';
        $tweet_maxlen = 100;
        $post_title = '';
        if (@$_GET['post']) {
            $permalink = get_permalink($_GET['post']);
            $tweet_maxlen = 140 - mb_strlen($permalink) - 1;
            $post_title = get_the_title($_GET['post']);
        }
        ?>
        <br>
        <div id="socialer-tweet-box" class="postbox ">
            <div class="handlediv" title="Click to toggle"><br></div><h3 class="hndle">
                <span>
                    <img style="width: 16px; height: 16px;" src="<?php echo plugins_url( 'js/img/twitter-bird-light-bgs.png', __FILE__ ) ?>" />
                    Socialer Tweet Box
                </span>
            </h3>
            <div class="inside">
                <div class="tagsdiv" id="post_tweet_box">
                    <div class="jaxtag">
                        <div id="socialer-message"><?php echo self::showMessage() ?></div>
                        <p>Enter tweet text without URL:</p>
                        <textarea
                            name="socialer_tweet_body"
                            rows="3"
                            style="width: 100%"
                            class="the-tags"
                            id="socialer-tweet-body"
                            maxlength="<?php echo $tweet_maxlen ?>"
><?php
    if (isset($_SESSION['soc_last_tweet_status']) && $_SESSION['soc_last_tweet_status'] == false ) {
        if (isset($_SESSION['soc_last_tweet_text'])){
            echo $_SESSION['soc_last_tweet_text'];
        } else {
            echo $post_title;
        }
    }
?></textarea>
                        <p class="howto">Maximum <?php echo $tweet_maxlen ?> characters. Available: <span id="socialer-tweet-chars-left"></span>.
                            Permalink will be added to message automatically
                            <?php if (@$_GET['post']): ?>
                            ( <?php echo $permalink ?> )
                            <?php else: ?>
                                when post will be published.
                            <?php endif ?>
                        </p>
                        <p>
                            <a class="button button-primary" href="<?php echo self::get_socialer_register_url(false) ?>" target="_blank">
                                Go to Dashboard
                            </a>
                            <?php if (@$_GET['post']): ?>
                            <a class="button button-primary" id="socialer-ajax-push-tweet">
                                Send Tweet
                            </a>
                                <img style="display: none" id="socialer-ajax-push-tweet-wait" src="<?php echo plugins_url( 'js/img/ajax-loader.gif', __FILE__ ) ?>" />
                            <?php endif ?>
                        </p>
                    </div>
                </div>
            </div>
        </div>
        <?php
    }

    public function ajax_get_socialer_register_button() {
        ?>
        <br>
        <div id="socialer-register-box" class="postbox " style="width: 100%">
            <div class="handlediv" title="Click to toggle"><br></div><h3 class="hndle"><span>Socialer Tweet Box</span></h3>
            <div class="inside">
                <div class="tagsdiv" id="post_tweet_box">
                    <div class="jaxtag">
                        <a
                            id="socialer-register-button"
                            class="button button-primary"
                            data-url="<?php echo self::get_socialer_register_url() ?>"
                        >
                            Register in Socialer
                        </a>
                    </div>
                </div>
            </div>
            <div class="alljs-dispatcher" data-function="alljs.socialer.bind_register_modal"></div>
        </div>
        <?php
    }

    /**
     * @param array $data
     * @return string
     */
    public function generate_socialer_signature( Array $data ) {
        $data['site_id']        = self::get_site_id();
        $data['user_wp_uid']    = self::get_user_wp_uid();
        $data['exp_t']          = (time() + self::SIGNATURE_LIFETIME);

        //var_dump($data);
        //var_dump(wp_get_current_user());
        $data = serialize($data);
        $result = Contextly_Crypt::getInstance()->encrypt($data, self::get_encryption_key());
        $result = base64_encode($result);
        $result = urlencode($result);

        return $result;
    }

    /**
     * TODO: build utms method
     * @param bool $add_utm
     * @return string
     */
    public function get_socialer_register_url( $add_utm = true ) {
        $url =  self::get_option('SOCIALER_URL')
                . '?sig='
                . self::generate_socialer_signature(array());

        if ( $add_utm ) {
            $url .= '&utm_source=wp_soc_plugin&utm_term=register';
        }

        return $url;
    }

    /**
     * @return string
     */
    protected function get_socialer_user_registered_callback_url() {
        return self::get_option('SOCIALER_URL')
               . self::get_option('SOCIALER_USER_REGISTERED_CALLBACK')
               . '?sig=' . self::generate_socialer_signature(array());
    }

    /**
     * @return string
     */
    protected function get_socialer_push_tweet_callback_url() {
        return self::get_option('SOCIALER_URL')
               . self::get_option('SOCIALER_PUSH_TWEET_CALLBACK')
               . '?sig=' . self::generate_socialer_signature(array());
    }

    /**
     * @return string
     */
    protected function get_encryption_key() {
        return self::get_option('ENCRYPTION_KEY');
    }

    /**
     * Loading options
     */
    public function __construct() {
        if ( empty(self::$options) ) {
            self::$options = require ( 'socialer_options.php' );
        }
    }

    /**
     * @param $key
     * @return null
     */
    public static function get_option( $key ) {
        $key = (String)$key;
        if ( !$key || !isset(self::$options[$key]) ) {
            return null;
        }

        return self::$options[$key];
    }

}
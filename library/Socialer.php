<?php

/**
 * @author: Dmitriy Meshin <0x7ffec at gmail.com>
 * @date: 22 May 2013
 * @time: 12:54 PM
 */

session_start();

require_once('Crypt.php');
require_once('View.php');

class Socialer {

    const JS_VERSION = '1.1';
    const SIGNATURE_LIFETIME = 3600;

    /**
     * https://dev.twitter.com/docs/api/1.1/get/help/configuration
     */
    const CHARACTERS_RESERVED_PER_MEDIA = 23;

    const POST_STATUS_FUTURE = 'future';
    const POST_STATUS_PUBLISHED = 'publish';
    const TWEET_TYPE_IMMEDIATELY = '';
    const TWEET_TYPE_SCHEDULE = 'on';

    /**
     * @var array
     */
    protected static $options = array();

    /**
     * @var Socialer_View
     */
    protected static $view = null;

    public function init() {
        // publishing tweet
        add_action('save_post', array($this, 'push_tweet'));
        //add_action('publish_post', array($this, 'push_tweet'));

        // showing textarea or register button
        add_action('edit_form_after_editor', array($this, 'show_tweet_box_or_register_button'));

        // ajax calls
        add_action('socialer_ajax_is_user_registered', array( $this, 'ajax_is_user_registered' ) );
        add_action('socialer_ajax_get_register_button', array( $this, 'ajax_get_socialer_register_button' ) );
        add_action('socialer_ajax_get_tweet_box', array( $this, 'ajax_get_tweet_box' ) );
        add_action('socialer_ajax_push_tweet', array( $this, 'ajax_push_tweet' ) );
        add_action('socialer_ajax_get_scheduled_tweet', array( $this, 'ajax_get_scheduled_tweet' ) );
        add_action('socialer_ajax_schedule_tweet', array( $this, 'ajax_schedule_tweet' ) );
    }

    public function ajax_get_scheduled_tweet() {
        self::$view->clearVars();

        if ( !isset($_POST['post_id']) || !$_POST['post_id'] ) {
            self::$view->assign('error', true);
            self::$view->assign('message', 'Bad post_id');
            die(self::$view->getJSON());
        }

        // check if user registered
        $response = wp_remote_retrieve_body(
            wp_remote_request(
                self::get_socialer_scheduled_tweet_url(),
                array(
                    'method' => 'POST',
                    'sslverify' => true,
                    'body' => array('post_id' => $_POST['post_id']),
                )
            )
        );

        die($response);
    }

    /**
     *  This method is used both for adding new schedule and updating existing
     *  // TODO: what if link does not exists yet?
     */
    public function ajax_schedule_tweet() {
        self::$view->clearVars();

        if ( !isset($_POST['post_id']) || !$_POST['post_id'] ) {
            self::$view->assign('error', true);
            self::$view->assign('message', 'Bad post_id');
            die(self::$view->getJSON());
        }

        if ( !isset($_POST['text']) || !$_POST['text'] ) {
            self::$view->assign('error', true);
            self::$view->assign('message', 'Empty tweet');
            die(self::$view->getJSON());
        }

        if ( !isset($_POST['permalink']) || !$_POST['permalink'] ) {
            self::$view->assign('error', true);
            self::$view->assign('message', 'Permalink does not exists!');
            die(self::$view->getJSON());
        }

        // check if user registered
        $response = wp_remote_retrieve_body(
            wp_remote_request(
                self::get_socialer_scheduled_tweet_url(),
                array(
                    'method' => 'POST',
                    'sslverify' => true,
                    'body' => array(
                        'post_id'       => $_POST['post_id'],
                        'text'          => $_POST['text'],
                        'permalink'     => $_POST['permalink'],
                        't_offset'      => $_POST['t_offset'],
                    ),
                )
            )
        );

        die($response);
    }

    /**
     *  This method is used both for adding new schedule and updating existing
     */
    public function schedule_tweet() {

        if ( !isset($_POST['post_date']) ) {
            $_POST['post_date'] = $_POST['aa'] . '-' . $_POST['mm'] . '-' . $_POST['jj']
                . ' ' . $_POST['hh'] . ':' . $_POST['mn'] . ':' . $_POST['ss'];
        }

        $current_timestamp = time();
        // throw how many seconds post will be published
        $post_time = strtotime($_POST['post_date']) - $current_timestamp;
        // adding tweet delay
        $delay = $post_time + $_POST['socialer-tweet-delay'] * 60 * 60;

        /*var_dump($_POST);
        print('Hours: ' . $_POST['socialer-tweet-delay'].'<br>');
        print('Post Time + Seconds: ' . $delay.'<br>');
        print('Post Time: ' . $post_time.'<br>');
        print('Current_timestamp: ' . $current_timestamp.'<br>');
        print('strtotime(post_date): ' . strtotime($_POST['post_date']).'<br>');
        print('post_date: ' . $_POST['post_date'].'<br>');*/

        $response = wp_remote_retrieve_body(
            wp_remote_request(
                self::get_socialer_schedule_tweet_url(),
                array(
                    'method' => 'POST',
                    'sslverify' => true,
                    'body' => array(
                        'post_id'       => get_the_ID(),
                        'text'          => $_POST['socialer_tweet_body'],
                        'permalink'     => get_permalink(get_the_ID()),
                        't_offset'      => $delay,
                        'hours'         => $_POST['socialer-tweet-delay']
                    ),
                )
            )
        );
        //$_SESSION['soc_notices'] .= serialize($response);

        //print_r($response); die();

        return $response;
    }

    /**
     *  This method is used in all cases when post did not scheduled
     */
    public function unschedule_tweet() {
        $response = wp_remote_retrieve_body(
            wp_remote_request(
                self::get_socialer_unschedule_tweet_url(),
                array(
                    'method' => 'POST',
                    'sslverify' => true,
                    'body' => array(
                        'post_id'       => get_the_ID()
                    ),
                )
            )
        );
        //$_SESSION['soc_notices'] .= serialize($response);
    }

    /**
     * @return string
     */
    public function get_socialer_scheduled_tweet_url() {
        return self::get_option('SOCIALER_URL')
                . self::get_option('SOCIALER_SCHEDULED_TWEET_CALLBACK')
                . '?sig=' . self::generate_socialer_signature(array());
    }

    /**
     * @return string
     */
    public function get_socialer_schedule_tweet_url() {
        return self::get_option('SOCIALER_URL')
                . self::get_option('SOCIALER_SCHEDULE_TWEET_CALLBACK')
                . '?sig=' . self::generate_socialer_signature(array());
    }

    /**
     * @return string
     */
    public function get_socialer_unschedule_tweet_url() {
        return self::get_option('SOCIALER_URL')
                . self::get_option('SOCIALER_UNSCHEDULE_TWEET_CALLBACK')
                . '?sig=' . self::generate_socialer_signature(array());
    }

    public function ajax_push_tweet() {

        self::$view->clearVars();

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
            //$errors_messages .= $_POST['text'] . ' ' . $permalink;

            if ( $errors_messages_presents ) {
                $errors_messages = '<strong>Error occurred during tweet posting!</strong>' . $errors_messages;
                $_SESSION['soc_notices'] = $errors_messages;
                $_SESSION['soc_notices_is_error'] = true;
                $_SESSION['soc_last_tweet_status'] = false;
                $_SESSION['soc_last_tweet_text'] = $_POST['text'];

                self::$view->assign('message',  self::showMessage());
                self::$view->assign('error',    true);
                die(self::$view->getJSON());
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

                self::$view->assign('message',  self::showMessage());
                self::$view->assign('success',  true);
                die(self::$view->getJSON());
            }

            $_SESSION['soc_notices_is_error'] = true;
            $_SESSION['soc_last_tweet_status'] = false;
            $_SESSION['soc_notices'] = 'Something is wrong. Please try again later';

            self::$view->assign('message',  self::showMessage());
            self::$view->assign('error',    true);
            die(self::$view->getJSON());
        }
    }

    public function push_tweet() {

        $postObj = get_post(get_the_ID());

        if (
            $postObj->post_status == self::POST_STATUS_FUTURE
            || strtotime($_POST['post_date']) > time()
        ) {
            if ( $_POST['socialer-tweet-type'] == self::TWEET_TYPE_SCHEDULE ) {
                $this->schedule_tweet();
            } else {
                // if we checked of schedule checkbox - then tweet have to be removed
                $this->unschedule_tweet();
            }
            // ANYWAY if post is scheduled - we do not need to send tweet
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
        echo self::$view->render('tweet_box_or_register_button.php');
    }

    public function ajax_get_tweet_box() {
        $permalink = '';
        $tweet_maxlen = 140 - self::CHARACTERS_RESERVED_PER_MEDIA;
        $post_title = '';
        if (@$_GET['post']) {
            $permalink = get_permalink($_GET['post']);
            $post_title = get_the_title($_GET['post']);
        }

        self::$view->assign('permalink', $permalink);
        self::$view->assign('tweet_maxlen', $tweet_maxlen);
        self::$view->assign('post_title', $post_title);
        echo self::$view->render('tweet_box.php');
    }

    public function ajax_get_socialer_register_button() {
        self::$view->assign('socialer_register_url', self::get_socialer_register_url());
        echo self::$view->render('socialer_register_button.php');
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
    public function get_socialer_user_registered_callback_url() {
        return self::get_option('SOCIALER_URL')
               . self::get_option('SOCIALER_USER_REGISTERED_CALLBACK')
               . '?sig=' . self::generate_socialer_signature(array());
    }

    /**
     * @return string
     */
    public function get_socialer_push_tweet_callback_url() {
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
            self::$options = require(APPLICATION_PATH . '/configs/socialer_options.php');
        }

        self::$view = new Socialer_View();
        self::$view->setViewsDirectory(APPLICATION_PATH . '/views/');
        self::$view->assign('js_base_url',              site_url( '/wp-includes/js/', SOCIALER_PLUGIN_BASE_FILE));
        self::$view->assign('js_plugin_base_url',       plugins_url( 'js/', SOCIALER_PLUGIN_BASE_FILE));
        self::$view->assign('img_plugin_base_url',      plugins_url( 'img/', SOCIALER_PLUGIN_BASE_FILE));
        self::$view->assign('plugin_base_url',          plugins_url( '/', SOCIALER_PLUGIN_BASE_FILE));
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
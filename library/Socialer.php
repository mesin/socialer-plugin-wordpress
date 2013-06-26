<?php

/**
 * @author: Dmitriy Meshin <0x7ffec at gmail.com>
 * @date: 22 May 2013
 * @time: 12:54 PM
 */

session_start();

require_once('Crypt.php');
require_once('View.php');
require_once('Settings.php');

class Socialer {

    const JS_VERSION                            = '1.2';
    const SIGNATURE_LIFETIME                    = 3600;
    const SOCIALER_AUTH_HEADER                  = 'socialer-fast-call';

    /**
     * https://dev.twitter.com/docs/api/1.1/get/help/configuration
     */
    const CHARACTERS_RESERVED_PER_MEDIA         = 23;
    const POST_STATUS_FUTURE                    = 'future';
    const POST_STATUS_PUBLISHED                 = 'publish';
    const POST_STATUS_DRAFT                     = 'draft';
    const TWEET_TYPE_IMMEDIATELY                = '';
    const TWEET_TYPE_SCHEDULE                   = 'on';
    const TWEETING_ON                           = 'on';

    /**
     * @var array
     */
    protected static $options                   = array();

    /**
     * @var Socialer_View
     */
    protected static $view                      = null;

    public function init() {
        if ( !Socialer_Settings::isSocialerActive() ) {
            return;
        }

        // showing textarea or register button OR message to save API key
        add_action('edit_form_after_editor', array($this, 'show_tweet_box_or_register_button'));

        if ( !Socialer_Settings::getApiKey() ) {
            return;
        }

        // publishing tweet
        add_action('save_post', array($this, 'Dispatch_Tweet'));

        // send tweet if it was draft and now is published
        add_action( 'draft_to_publish', array($this, 'send_tweet_from_draft_to_publish'));

        // save tweet body associated with post for later sending after becoming published
        add_action( 'auto-draft_to_draft', array($this, 'save_tweet_from_new_to_draft'));

        // ajax calls
        add_action('socialer_ajax_is_user_registered', array( $this, 'ajax_is_user_registered' ) );
        add_action('socialer_ajax_get_register_button', array( $this, 'ajax_get_socialer_register_button' ) );
        add_action('socialer_ajax_get_tweet_box', array( $this, 'ajax_get_tweet_box' ) );
        add_action('socialer_ajax_push_tweet', array( $this, 'ajax_push_tweet' ) );
        add_action('socialer_ajax_get_scheduled_tweet', array( $this, 'ajax_get_scheduled_tweet' ) );
        add_action('socialer_ajax_get_draft_tweet', array( $this, 'ajax_get_draft_tweet' ) );
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
                    'body' => array(
                        'post_id' => $_POST['post_id'],
                    ),
                    'headers'   => array(
                        self::SOCIALER_AUTH_HEADER => Socialer_Settings::getApiKey()
                    )
                )
            )
        );

        die($response);
    }

    /**
     *  This method is used both for adding new schedule and updating existing
     */
    public function ajax_schedule_tweet() {
        $current_timestamp = current_time( 'timestamp' );
        // throw how many seconds post will be published
        $post_time = strtotime($_POST['schedule_date']) - $current_timestamp;
        // adding tweet delay
        $delay = $post_time + $_POST['schedule_delay'] * 60 * 60;

        /*var_dump($_POST);
        print('Hours: ' . $_POST['socialer-tweet-delay'].'<br>');
        print('Post Time Offset + Seconds: ' . $delay.'<br>');
        print('Post Time Offset: ' . $post_time.'<br>');
        print('Current_timestamp: ' . $current_timestamp.' (' . date('Y-m-d h:i:s', $current_timestamp) . ')'.'<br>');
        print('strtotime(post_date): ' . strtotime($_POST['post_date']).'<br>');
        print('post_date: ' . $_POST['post_date'].'<br>');*/

        /**
         * Post structure:
         *
            text: jQuery('#socialer-tweet-body').val(),
            url: url,
            title: title,
            post_id: post_id,
            schedule_date: schedule_date,
            schedule_delay: schedule_delay
         */

        $response = wp_remote_retrieve_body(
            wp_remote_request(
                self::get_socialer_schedule_tweet_url(),
                array(
                    'method' => 'POST',
                    'sslverify' => true,
                    'body' => array(
                        'post_id'       => $_POST['post_id'],
                        'text'          => $_POST['text'],
                        'permalink'     => $_POST['url'],
                        't_offset'      => $delay,
                        'hours'         => $_POST['schedule_delay'],
                        'title'         => $_POST['title'],
                    ),
                    'headers'   => array(
                        self::SOCIALER_AUTH_HEADER => Socialer_Settings::getApiKey()
                    )
                )
            )
        );
        //$_SESSION['soc_notices'] .= serialize($response);

        //print_r($response); die();

        die($response);
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
                    'headers'   => array(
                        self::SOCIALER_AUTH_HEADER => Socialer_Settings::getApiKey()
                    )
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
                . self::get_option('SOCIALER_SCHEDULED_TWEET_API')
                . 'user_wp_uid/' . self::get_user_wp_uid();
    }

    /**
     * @return string
     */
    public function get_socialer_schedule_tweet_url() {
        return self::get_option('SOCIALER_URL')
                . self::get_option('SOCIALER_SCHEDULE_TWEET_API')
                . 'user_wp_uid/' . self::get_user_wp_uid();
    }

    /**
     * @return string
     */
    public function get_socialer_unschedule_tweet_url() {
        return self::get_option('SOCIALER_URL')
                . self::get_option('SOCIALER_UNSCHEDULE_TWEET_API')
                . 'user_wp_uid/' . self::get_user_wp_uid();
    }

    public function ajax_push_tweet() {

        self::$view->clearVars();

        // check if user registered
        $response = wp_remote_retrieve_body(
            wp_remote_request(
                self::get_socialer_user_registered_API_url(),
                array(
                    'method' => 'GET',
                    'sslverify' => true,
                    'headers'   => array(
                        self::SOCIALER_AUTH_HEADER => Socialer_Settings::getApiKey()
                    )
                )
            )
        );

        $response = json_decode($response);

        if ( $response && isset($response->success) && $response->success ) {

            $permalink = get_permalink($_GET['post']);
            $_POST['text'] = str_replace(',undefined', '', $_POST['text']);

            $response = wp_remote_retrieve_body(
                wp_remote_request(
                    self::get_socialer_push_tweet_API_url(),
                    array(
                        'method' => 'POST',
                        'body' => array(
                            'text'  => $_POST['text'] . ' ' . $permalink,
                            'url'   => $permalink,
                            'title' => $_POST['title']
                        ),
                        'sslverify' => true,
                        'headers'   => array(
                            self::SOCIALER_AUTH_HEADER => Socialer_Settings::getApiKey()
                        )
                    )
                )
            );

            //die($response);

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
            die($response);

            self::$view->assign('message',  self::showMessage());
            self::$view->assign('error',    true);
            die(self::$view->getJSON());
        }
    }

    /**
     * @param $post_id
     * @return string
     */
    protected function _get_tweet_for_draft($post_id) {
        $response = wp_remote_retrieve_body(
            wp_remote_request(
                self::get_socialer_get_tweet_draft_API_url(),
                array(
                    'method' => 'POST',
                    'body' => array(
                        'post_id'   => $post_id,
                    ),
                    'sslverify' => true,
                    'headers'   => array(
                        self::SOCIALER_AUTH_HEADER => Socialer_Settings::getApiKey()
                    )
                )
            )
        );

        return @json_decode($response);
    }

    /**
     * Moving all drafts to schedules to be sent NOW
     * @param $post_id
     * @return string
     */
    protected function move_tweet_drafts_to_schedules($post_id) {
        $response = wp_remote_retrieve_body(
            wp_remote_request(
                self::get_socialer_remove_tweet_draft_API_url(),
                array(
                    'method' => 'POST',
                    'body' => array(
                        'post_id'       => $post_id,
                        'permalink'     => get_permalink($post_id)
                    ),
                    'sslverify' => true,
                    'headers'   => array(
                        self::SOCIALER_AUTH_HEADER => Socialer_Settings::getApiKey()
                    )
                )
            )
        );

        return $response;
    }

    /**
     * Getting draft to display it in tweet textarea
     */
    public function ajax_get_draft_tweet() {
        $response = $this->_get_tweet_for_draft($_REQUEST['post']);
        die(json_encode($response));
    }

    /**
     * @return string
     */
    public function send_tweet_from_draft_to_publish() {
        $result = $this->move_tweet_drafts_to_schedules($_POST['post_ID']);
        $_SESSION['soc_notices'] = "In about one minute we'll send your Tweet";
        $_SESSION['soc_notices_is_error'] = false;
        $_SESSION['soc_last_tweet_status'] = true;
        //var_dump($result); die();
        return $result;
    }

    public static function isNewPost() {
        return !get_post(@$_REQUEST['post']);
    }

    /**
     * Saving tweet on draft saving
     * @return string
     */
    public function save_tweet_from_draft() {
        $_POST['socialer_tweet_body'] = str_replace(',undefined', '', @$_POST['socialer_tweet_body']);

        $response = wp_remote_retrieve_body(
            wp_remote_request(
                self::get_socialer_save_tweet_draft_API_url(),
                array(
                    'method' => 'POST',
                    'body' => array(
                        'text'      => $_POST['socialer_tweet_body'],
                        'post_id'   => $_POST['post_ID'],
                        'title'     => $_POST['post_title'],
                    ),
                    'sslverify' => true,
                    'headers'   => array(
                        self::SOCIALER_AUTH_HEADER => Socialer_Settings::getApiKey()
                    )
                )
            )
        );

        $_SESSION['soc_last_tweet_text'] = $_POST['socialer_tweet_body'];

        //var_dump($response); die();
        return $response;
    }

    /**
     *  Pushing tweet, or scheduling it ir unscheduling it
     */
    public function Dispatch_Tweet() {
        /**
         *  Protection because for some reason in some WP blogs hook works twice!
         *  And we have Twitter error: Status is a duplicate
         */
        static $tweet_pushed = false;
        if ( true === $tweet_pushed ) {
            return;
        }
        $tweet_pushed = true;

        $_POST['socialer_tweet_body'] = str_replace(',undefined', '', $_POST['socialer_tweet_body']);

        // If it is DRAFT - then we can only save drafts
        if ( self::isDraft() || $_POST['post_status'] == 'draft' ) {
            $response = $this->_get_tweet_for_draft($_REQUEST['post']);

            if (
                isset($response->success)
                && $response->success == true
                && isset($response->entry->tweet)
            ) {
                $_SESSION['send_tweet_on_update'] = true;
                $_SESSION['soc_last_tweet_text'] = $response->entry->tweet;
            } else {
                $this->save_tweet_from_draft();
            }

            return;
        }

        // otherwise do another staff
        if ( $_POST['socialer-tweet-schedule'] == self::TWEET_TYPE_SCHEDULE ) {
            // or mark for sending right now with ajax!
            $this->_prepare_tweet_scheduling();
        } else {
            // if we checked of schedule checkbox - then tweet have to be removed
            $this->unschedule_tweet();
        }

        // or mark for sending right now with ajax!
        if ( $_POST['socialer-tweeting-enabled'] == self::TWEETING_ON ) {
            $this->_prepare_tweet_sending();
        }
    }

    /**
     * @return bool
     */
    public static function isDraft() {
        return (@get_post($_REQUEST['post'])->post_status == self::POST_STATUS_DRAFT);
    }

    /**
     * @return bool
     */
    public static function isFuture() {
        return (@get_post($_REQUEST['post'])->post_status == Socialer::POST_STATUS_FUTURE);
    }

    protected function _prepare_tweet_sending() {
        $_SESSION['send_tweet_on_update'] = true;
        $_SESSION['soc_last_tweet_text'] = $_POST['socialer_tweet_body'];
    }

    protected function _prepare_tweet_scheduling() {
        $_SESSION['schedule_tweet_on_update'] = true;
        $_SESSION['soc_last_tweet_text'] = $_POST['socialer_tweet_body'];

        //echo $_SESSION['soc_last_tweet_text'];
        //die($_POST['socialer_tweet_body']);

        // WARNING: post date have to be taken from here because on new post it does not presents!
        if ( !isset($_POST['post_date']) ) {
            $_POST['post_date'] = $_POST['aa'] . '-' . $_POST['mm'] . '-' . $_POST['jj']
                . ' ' . $_POST['hh'] . ':' . $_POST['mn'] . ':' . $_POST['ss'];
        }
        $_SESSION['schedule_post_date'] = $_POST['post_date'];
        $_SESSION['schedule_delay'] = $_POST['socialer-tweet-delay'];

        //var_dump($_SESSION); die();
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
                self::get_socialer_user_registered_API_url(),
                array(
                    'method' => 'GET',
                    'sslverify' => true,
                    'headers'   => array(
                        self::SOCIALER_AUTH_HEADER => Socialer_Settings::getApiKey()
                    )
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
        if ( Socialer_Settings::getApiKey() ) {
            echo self::$view->render('tweet_box_or_register_button.php');
        } else {
            echo self::$view->render('get_api_key.php');
        }
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
        $data['api_key']        = Socialer_Settings::getApiKey();
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
    public function get_socialer_user_registered_API_url() {
        return self::get_option('SOCIALER_URL')
               . self::get_option('SOCIALER_USER_REGISTERED_API')
               . 'user_wp_uid/' . self::get_user_wp_uid();
    }

    /**
     * @return string
     */
    public function get_socialer_save_tweet_draft_API_url() {
        return self::get_option('SOCIALER_URL')
               . self::get_option('SOCIALER_SAVE_TWEET_DRAFT_API')
               . 'user_wp_uid/' . self::get_user_wp_uid();
    }

    /**
     * @return string
     */
    public function get_socialer_get_tweet_draft_API_url() {
        return self::get_option('SOCIALER_URL')
               . self::get_option('SOCIALER_GET_TWEET_DRAFT_API')
               . 'user_wp_uid/' . self::get_user_wp_uid();
    }

    /**
     * @return string
     */
    public function get_socialer_remove_tweet_draft_API_url() {
        return self::get_option('SOCIALER_URL')
               . self::get_option('SOCIALER_REMOVE_TWEET_DRAFT_API')
               . 'user_wp_uid/' . self::get_user_wp_uid();
    }

    /**
     * @return string
     */
    public function get_socialer_push_tweet_API_url() {
        return self::get_option('SOCIALER_URL')
               . self::get_option('SOCIALER_PUSH_TWEET_API')
               . 'user_wp_uid/' . self::get_user_wp_uid();
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
<?php
/**
 *  @author Meshin Dmitry <0x7ffec at gmail.com>
 */

session_start();

define('DOING_AJAX', true);

//Typical headers
header('Content-Type: text/json');

require_once('../../../wp-load.php');
send_nosniff_header();

//Disable caching
header('Cache-Control: no-cache');
header('Pragma: no-cache');

if ( !isset( $_REQUEST[ 'action' ] ) ) {
    die(json_encode(array(
        'error' => 'No action',
        'error_code' => -1
    )));
}

$action = esc_attr( $_REQUEST['action'] );

//A bit of security
$allowed_actions = array(
    'is_user_registered',
    'get_register_button',
    'get_tweet_box',
    'push_tweet',
    'get_scheduled_tweet',
    'schedule_tweet',
    'get_draft_tweet',
);

if( in_array($action, $allowed_actions) ) {
    if ( is_user_logged_in() ) {
        do_action( 'socialer_ajax_' . $action );
        return;
    }
}

die(json_encode(array(
    'error' => 'Action is not allowed or user did not logged in',
    'error_code' => -1
)));
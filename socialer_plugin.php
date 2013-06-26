<?php
/*
Plugin Name: Socialer
Plugin URI: http://contextly.com
Description: Adds the Socialer tool to your blog.
Author: Contextly
Version: 1.0
*/

define ( "SOCIALER_PLUGIN_VERSION", '1.0' );
define ( "SOCIALER_HTTPS", isset( $_SERVER['HTTPS'] ) && $_SERVER['HTTPS'] == 'on' );
define ( "SOCIALER_PLUGIN_BASE_FILE", __FILE__ );
define ( "APPLICATION_PATH", realpath(dirname(__FILE__)) );

require_once("library/Socialer.php");
require_once("library/Settings.php");

if ( is_admin() ) {
    $settings = new Socialer_Settings();
    $settings->init();
}

//var_dump($_POST); die();

// init Socialer
$socialer = new Socialer();
$socialer->init();

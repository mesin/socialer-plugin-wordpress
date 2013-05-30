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

require_once("library/Socialer.php");

if ( is_admin() ) {
    // TODO: Init Socialer WP settings
}

// init Socialer
$socialer = new Socialer();
$socialer->init();
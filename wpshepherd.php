<?php

/*
Plugin Name: Shepherd
Plugin URI: http://www.wpshepherd.com
Description: The Shepherd you always wanted.
Version: 1.0.0
Author: 8th Avenue
Author URI: http://www.wpshepherd.com
License: -
*/

require_once( $_SERVER['DOCUMENT_ROOT'] . '/wp-admin/includes/plugin.php' );

if ( file_exists(__DIR__ . '/api.key') ) {
	$options = get_option('shepherd_option_name');
	if(!$options['api_key']) {
		$url = str_replace(array('http://', 'www.', '/'), '', get_bloginfo('url'));;
		$api_key_contents = stripslashes_deep(file_get_contents(__DIR__ . '/api.key'));
		$save_arr = array('api_key' => $api_key_contents);
		update_option( 'shepherd_option_name', $save_arr );
		$data_arr = array('name' => get_bloginfo('name'));
		$response = wp_remote_post('https://www.wpshepherd.com/server/wpsave/api_key/' . $api_key_contents . '/url/' . $url . '/type/auto', array('body' => $data_arr, 'user-agent' => 'WordPress/WPShep/0.4'));	
	}
}

require_once( __DIR__ . '/request.php' );

if ( is_admin() )
	require_once dirname( __FILE__ ) . '/admin.php';

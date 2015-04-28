<?php

/*
Plugin Name: WP Shepherd
Plugin URI: http://www.wpshepherd.com
Description: The Shepherd you always wanted.
Version: 1.0.5
Author: 8th Avenue
Author URI: http://www.wpshepherd.com
License: -
*/
define('SHEP_VERSION', '1.0.5');
define('SHEP_ABSPATH', dirname(__FILE__) . '/../../../');
//define('SHEP_PATH', plugin_dir_path( __FILE__ ));
define('SHEP_PATH', dirname(__FILE__) .'/');
$wp_args = array('user-agent' => 'WordPress/WPShep/' . SHEP_VERSION, 'sslverify' => false);
require_once( SHEP_ABSPATH . '/wp-admin/includes/plugin.php' );

function wpshep_activate() {
	if ( file_exists(SHEP_PATH . '/api.key') ) {
		$options = get_option('shepherd_option_name');
		$url = str_replace(array('http://', 'www.', '/'), '', get_bloginfo('url'));;
		$api_key_contents = stripslashes_deep(file_get_contents(SHEP_PATH . '/api.key'));
		$save_arr = array('api_key' => $api_key_contents);
		update_option( 'shepherd_option_name', $save_arr );
		$data_arr = array('name' => get_bloginfo('name'));
		$response = wp_remote_post('https://www.wpshepherd.com/server/wpsave/api_key/' . $api_key_contents . '/url/' . $url . '/version/' . SHEP_VERSION . '/type/auto', array('body' => $data_arr, 'user-agent' => 'WordPress/WPShep/' . SHEP_VERSION, 'sslverify' => false));
		if(is_wp_error($hand)) {
			wp_remote_get('https://www.wpshepherd.com/server/wpsave/api_key/' . $api_key_contents . '/url/' . $url . '/version/' . SHEP_VERSION . '/type/failed', $wp_args);
		}	
	}
}
register_activation_hook( __FILE__, 'wpshep_activate' );

if ( function_exists('register_uninstall_hook') )
    register_uninstall_hook(__FILE__, 'wpshep_deinstall');

function wpshep_deinstall() {
    delete_option('shepherd_option_name');
}

require_once( SHEP_PATH . '/request.php' );

if ( is_admin() )
	require_once dirname( __FILE__ ) . '/admin.php';

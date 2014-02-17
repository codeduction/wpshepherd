<?php

/*
 * Codefuel Sheperd
 * Version 0.2 Reponse via json
 */
 
define('SHEP_PATH', dirname(__FILE__) .'/');
define('SHEP_REQUEST', __FILE__);

class Wp_Shep {
	protected $sVersion = null;
	protected $wp_args = array();
	protected $url;
	
	/*
	 * Get the correct version of the core
	 */
	function correct_version() {
		global $wp_version;
		$this->sVersion = $wp_version;
	}
	
	/*
	 * Add actions/filters ext
	 * return null
	 */
	function __construct() {
		$this->wp_args = array(
			'user-agent' => 'WordPress/WPShep/' . SHEP_VERSION,
		);		
		
		add_action( 'plugins_loaded', array( $this, 'correct_version' ), 5);
		add_action( 'init', array($this, 'request'), 500);
		$this->url = str_replace(array('http://', 'www.', '/'), '', get_bloginfo('url'));
	}
	
	/*
	 * Public request
	 * @usage check to see if the server is requesting wpshep/request
	 * @return $this->response();
	 */
	public function request() {
		if(isset($_GET['wpshep'])) {
			$options = get_option('shepherd_option_name');
			if(!isset($options['api_key']) || (isset($options['api_key']) && !$options['api_key'])) {
				echo json_encode(array('fault' => '504'));
				exit;
			} elseif(sha1(trim($options['api_key'])) != trim($_GET['wpshep'])) {
				echo json_encode(array('fault' => '506'));
				exit;
			} else {
				$this->reponse();
			}
		}
	}
	
	public function reponse() {
		try {
			include(__DIR__ . '/phpseclib/Crypt/RSA.php');
			$public_key = get_transient('wpshep_public');
			$md5_check = get_transient('wpshep_md5');
			$options = get_option('shepherd_option_name');
			if(!isset($options['api_key']) || (isset($options['api_key']) && !$options['api_key'])) {
				throw new Exception(504);
			}
			
			if(strlen($public_key)==0) {
				// General Response
				$response = wp_remote_get('https://www.wpshepherd.com/server/index/api_key/' . $options['api_key'], $this->wp_args);
				$response_data = json_decode($response['body'], true);
				
				// Lets verify handshake
				$hand = wp_remote_get('https://www.wpshepherd.com/server/hand/api_key/' . $options['api_key'], $this->wp_args);

				$rsa = new Crypt_RSA();
				$rsa->loadKey($response_data['public_key']);
				
				if(!$rsa->verify('shake', $hand['body'])) {
					throw new Exception(802);
				} else {
					// Handshake passed lets just carry on and cache for 24hours / (60 * 60 * 24)
					set_transient('wpshep_public', $response_data['public_key'], 60);
					set_transient('wpshep_md5', $response_data['md5_check'], 60);
					$public_key = $response_data['public_key'];
					$md5_check = $response_data['md5_check'];
				}
			}
			
			if(!$public_key) {
				throw new Exception('108');
			}
			$rsa_now = new Crypt_RSA();
			$rsa_now->loadKey($public_key);
			$rsa_now->setEncryptionMode(CRYPT_RSA_PUBLIC_FORMAT_RAW);
			
			$raw_data = $this->shep_get_bloginfo();
			$json_data = json_encode($raw_data);
			$md5_now = md5($json_data);
			
			// Check the md5
			if($md5_now == $md5_check || (isset($_GET['md5']) && $_GET['md5'] == $md5_now)) {
				throw new Exception('101');
			}
			$json_arr = array();
			$body_array = array('domain' => $this->url);
			foreach(str_split($json_data, 3000) as $key => $str) {
				$body_array['data_' . $key] = $rsa_now->encrypt($str);
			}
			
			wp_remote_post('https://www.wpshepherd.com/server/post/api_key/' . $options['api_key'], array_merge(array('body' => $body_array, 'user-agent' => 'WordPress/WPShep/' . SHEP_VERSION)));
		} catch (Exception $e) {
        	echo json_encode(array('fault' => $e->getMessage()));
		}
		exit;
	}

	public function shep_get_bloginfo(){
		
		// General wordpress data
		$aRtn = array();
		$aBlogInfo = get_bloginfo();
		$sVersion = $this->sVersion;
		$aRtn['wordpress'] = array(
			'admin_email' 			=> get_bloginfo('admin_email'), 
			'atom_url'				=> get_bloginfo('atom_url'),
			'charset'				=> get_bloginfo('charset'),
			'comments_atom_url'		=> get_bloginfo('comments_atom_url'),
			'comments_rss2_url'		=> get_bloginfo('comments_rss2_url'),
			'description'			=> get_bloginfo('description'),
			//'home'				=> get_bloginfo('home'),
			//'siteurl'				=> get_bloginfo('siteurl'),
			'html_type'				=> get_bloginfo('html_type'),
			'language'				=> get_bloginfo('language'),
			'name'					=> get_bloginfo('name'),
			'pingback_url'			=> get_bloginfo('pingback_url'),
			'rdf_url'				=> get_bloginfo('rdf_url'),
			'rss2_url'				=> get_bloginfo('rss2_url'),
			'rss_url'				=> get_bloginfo('rss_url'),
			'stylesheet_directory'	=> get_bloginfo('stylesheet_directory'),
			'template_directory'	=> get_bloginfo('template_directory'),
			'template_url'			=> get_bloginfo('template_url'),
			'text_direction'		=> get_bloginfo('text_direction'),
			'url'					=> get_bloginfo('url'),
			'version'				=> $sVersion,
			'wpurl'					=> get_bloginfo('wpurl'), 
			'cf_authentication_key' => get_option('cf_authentication_key')
		);
		
		// Theme information
		$oTheme 		= wp_get_theme();
		$aRtn['theme']	= array(
			'name' => $oTheme->Name, 
			'theme_uri' => $oTheme->ThemeURI,
			'description' => $oTheme->Description,
			'author' => $oTheme->Author,
			'author_uri' => $oTheme->AuthorUri,
			'version' => $oTheme->Version,
			'template' => $oTheme->Template,
			'status' => $oTheme->Status,
			'tags' => $oTheme->Tags,
			'text_domain' => $oTheme->TextDomain,
			'domain_path' => $oTheme->DomainPath
		);
		
		// Plugins
		$aPlugins 		= get_plugins();
		$aActivePlugins = get_option('active_plugins');
		foreach ( $aPlugins as $sSlug =>  $aPlugin ) {
			$aPlugin['Active'] = in_array($sSlug, $aActivePlugins) ? '1' : '0';
			$aRtn['plugins'][$aPlugin['Name']] = $aPlugin;
		}
		
		return $aRtn;
	}	
}

$oWpShep = new Wp_Shep();
<?php
class ShepherdSettingsPage
{
    /**
     * Holds the values to be used in the fields callbacks
     */
    private $options;

    /**
     * Start up
     */
    public function __construct()
    {
        add_action( 'admin_menu', array( $this, 'add_plugin_page' ) );
        add_action( 'admin_init', array( $this, 'page_init' ) );
		$this->warning();
    }

    /**
     * Add options page
     */
    public function add_plugin_page()
    {
        // This page will be under "Settings"
        add_options_page(
            'WP Shepherd Settings', 
            'WP Shepherd', 
            'manage_options', 
            'shepherd-setting-admin', 
            array( $this, 'create_admin_page' )
        );
    }

	public function warning() {
		$options = get_option( 'shepherd_option_name' ); 

		if ( isset($options) && !$options['api_key'] && !isset($_POST['submit']) ) {
			function shepherd_warning() {
				echo "
				<div id='shepherd-warning' class='updated fade'><p><strong>".__('Shepherd is almost ready: ')."</strong> ".sprintf(__('Please <a href="%1$s">enter your Shepherd API key</a> to start tracking.'), "admin.php?page=shepherd-setting-admin")."</p></div>
				";
			}
			add_action('admin_notices', 'shepherd_warning');
			return;
		}
	}

    /**
     * Options page callback
     */
    public function create_admin_page()
    {
        // Set class property
        $this->options = get_option( 'shepherd_option_name' );
        ?>
        <div class="wrap">
            <?php screen_icon(); ?>
            <h2>WP Shepherd</h2>           
            <form method="post" action="options.php">
            <?php
                // This prints out all hidden setting fields
                settings_fields( 'shepherd_option_group' );   
                do_settings_sections( 'shepherd-setting-admin' );
                submit_button(); 
            ?>
            </form>
        </div>
        <?php
    }

    /**
     * Register and add settings
     */
    public function page_init()
    {        
        register_setting(
            'shepherd_option_group', // Option group
            'shepherd_option_name', // Option name
            array( $this, 'sanitize' ) // Sanitize
        );

        add_settings_section(
            'setting_section_id', // ID
            'Shepherd Settings', // Title
            array( $this, 'print_section_info' ), // Callback
            'shepherd-setting-admin' // Page
        );  

        add_settings_field(
            'api_key', // ID
            'API Key', // Title 
            array( $this, 'api_key_callback' ), // Callback
            'shepherd-setting-admin', // Page
            'setting_section_id' // Section           
        );          
    }

    /**
     * Sanitize each setting field as needed
     *
     * @param array $input Contains all settings fields as array keys
     */
    public function sanitize( $input )
    {
        $new_input = array();
        if( isset( $input['api_key'] ) ) {
            $new_input['api_key'] = sanitize_text_field( $input['api_key'] );
			$url = str_replace(array('http://', 'www.', '/'), '', get_bloginfo('url'));
			$response = wp_remote_get('https://www.wpshepherd.com/server/wpsave/api_key/' . $input['api_key'] . '/url/' . $url . '/version/' . SHEP_VERSION, array('user-agent' => 'WordPress/WPShep/' . SHEP_VERSION));	
		}

        return $new_input;
    }

    /** 
     * Print the Section text
     */
    public function print_section_info()
    {
        print 'Enter your settings below:';
    }

	public function api_key_callback() {

 		printf(
            '<input type="text" id="api_key" name="shepherd_option_name[api_key]" value="%s" />',
            isset( $this->options['api_key'] ) ? esc_attr( $this->options['api_key']) : ''
        );
	}	
}

$ShepherdSettingsPage = new ShepherdSettingsPage();
<?php if ( ! defined( 'ABSPATH' ) ) exit; 
/**
 * Plugin Name:		GA Tracking Code
 * Plugin URI:		https://www.francescotaurino.com/wordpress/ga-tracking-code
 * Description:		Ga Tracking Code plugin makes Google Analytics tracking easier
 * Author:				Francesco Taurino
 * Author URI:		https://www.francescotaurino.com
 * Version:				1.2.0
 * Text Domain:		ga-tracking-code
 * Domain Path:		/languages
 * License:				GPL v3
 *
 * @package     	Ga_Tracking_Code
 * @author      	Francesco Taurino <dev.francescotaurino@gmail.com>
 * @copyright   	Copyright (c) 2017, Francesco Taurino
 * @license     	http://www.gnu.org/licenses/gpl-3.0.html
 *              
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program.  If not, see http://www.gnu.org/licenses/gpl-3.0.html.
 */
if ( !class_exists('Ga_Tracking_Code') ) :


	class Ga_Tracking_Code {


		/**
     * Minimum PHP version
     * 
     * @const string
     */
		const MIN_PHP_VERSION = '5.3';


		/**
     * Minimum WordPress version
     * 
     * @const string
     */
		const MIN_WP_VERSION = '3.1.0';


		/**
     * Instance of this class.
     * 
		 * @static
		 * @access private
     * @var      object
     */
		private static $instance = null;


		/**
		 * Plugin Name
		 * 
		 * @access private
		 * @var string
		 */
		private $plugin_name = 'GA Tracking Code';


		/**
		 * Option Name
		 * 
		 * @access private
		 * @var string
		 */
		private $option_name = 'gatc_settings';


		/**
		 * Option Group
		 *
		 * @access private
		 * @var string
		 */
		private $option_group = 'gatc_option_group';


		/**
		 * Text Domain
		 *
		 * @access private
		 * @var string
		 */
		private $text_domain = 'ga-tracking-code';


		/**
		 * Required Capability
		 *
		 * @access private
		 * @var string
		 */
		private $required_capability = 'manage_options';


		/**
		 * Options
		 *
		 * @access private
		 * @var array Holds the plugins options.
		 */
		private $options = array();


		/**
		 * Default Options
		 *
		 * @access private
		 * @var array
		 */
		private $default_options = array(
			'tracking_id'					 	=> '',
			'script_position' 			=> 'wp_head',
			'track_administrator' 	=> true
		);


		/**
		 * Use this method to get an instance of your config.
		 * Each config has its own instance of this object.
		 *
		 * @static
		 * @access public
		 * 
		 * @return Ga_Tracking_Code
		 */
		public static function get_instance() {
 
			if ( null == self::$instance ) {
					self::$instance = new self;
			}

			return self::$instance;
 
		}

	 
	 	/**
		 * The class constructor.
		 * Use the get_instance() static method to get the instance you need.
		 *
		 * @access private
		 * 
		 * @return void
		 */
		private function __construct() {
			
			if( !self::meets_requirements() ){
				
				// Add a dashboard notice.
				// all_admin_notices Prints generic admin screen notices.
				add_action( 'all_admin_notices', array( $this, 'requirements_not_met_notice' ) );
				
				// Deactivate GA Tracking Code plugin.
				// admin_init Fires as an admin screen or script is being initialized.
				add_action( 'admin_init', array( $this, 'deactivate_plugin' ) );
				
				// Didn't meet the requirements.
				return false;

			}

			$this->options = $this->get_options();

			// This hook is called once any activated plugins have been loaded.
			add_action('plugins_loaded', array( $this, 'plugins_loaded') );

			// admin_init is triggered before any other hook when a user accesses the admin area.
			add_action('admin_init', array( $this, 'admin_init'));
			
			// This action is used to add extra submenus and menu options to the admin panel's menu structure.
			add_action('admin_menu', array( $this, 'admin_menu'));

			// The script_position value can be `wp_head` or` wp_footer`
			// The `wp_head` action hook is triggered within the <head></head> section of the user's template by the wp_head() function. 
			// The `wp_footer` action is triggered near the </body> tag of the user's template by the wp_footer() function. 
			// According to the WordPress documentation both functions are theme-dependent 
			add_action( $this->options['script_position'], array( $this, 'script' ) );

		}


		/**
		 * Check that all plugin requirements are met.
		 *
		 * Note:
		 * 
		 * since WP 3.1.0
		 * function: 	submit_button
		 * action: 		all_admin_notices
		 * 
		 * since WP 2.8.0
		 * function:	esc_html
		 * function:	esc_attr
		 * 
		 * since WP 2.7.0
		 * function:	register_setting >=WP 4.7.0 `$args` can be passed to set flags on the setting.
		 * function: 	settings_fields
		 * function: 	add_settings_field >=WP 4.2.0 The `$class` argument was added.
		 * function: 	add_settings_section
		 * function: 	do_settings_sections
		 * 
		 * since WP 2.5.0
		 * action: 		admin_init
		 * function: 	deactivate_plugins
		 * 
		 * since WP 2.0.0
		 * function:	current_user_can
		 *
		 * since WP 1.5.1
		 * action: 		wp_footer
		 * 
		 * since WP 1.5.0
		 * function:	load_plugin_textdomain
		 * function:	get_option
		 * function:	plugin_basename
		 * action: 		admin_menu
		 * action: 		wp_head
		 * 
		 * since WP 1.0.0
		 * function:	selected
		 * 	
		 * since WP 0.71
		 * function: 	apply_filters
		 * 
		 * @access private
		 * @static
		 * 
		 * @return bool
		 */
		private static function meets_requirements() {

			if ( version_compare( phpversion(), self::MIN_PHP_VERSION, '<' ) ) {
				return false;
			}

			if ( version_compare( $GLOBALS['wp_version'], self::MIN_WP_VERSION, '<' ) ) {
				return false;
			}

			return true;
		}


		/**
		 * Adds a notice to the dashboard if the plugin requirements are not met.
		 * 
		 * @return void
		 */
		public function requirements_not_met_notice() {

			// Compile default message.
			$message = sprintf( __( 'GA Tracking Code requires at least WordPress version %s and PHP %s. You are running version %s on PHP %s. Please upgrade and try again.', 'ga-tracking-code' ), 
				self::MIN_WP_VERSION,
				self::MIN_PHP_VERSION,
				$GLOBALS['wp_version'],
				phpversion()
			);

			// Output errors.
			printf( '<div id="message" class="error"><p>%s</p></div>', esc_html( $message ) );

		}


		/**
		 * Deactivates GA Tracking Code
		 *
		 * @return void
		 */
		public function deactivate_plugin() {
			if ( ! function_exists( 'deactivate_plugins' ) ) {
				return;
			}

			// We do a check for deactivate_plugins before calling it, to protect
			// any developers from accidentally calling it too early and breaking things.
			deactivate_plugins( plugin_basename( __FILE__ ) );
		
		}


		/**
		 * Loads a plugin's translated strings
		 * 
		 * @link https://codex.wordpress.org/Function_Reference/load_plugin_textdomain
		 * 
		 * @return void
		 */
		public function plugins_loaded() {
			
			// Set text domain
			load_plugin_textdomain( 
				$this->text_domain, 
				false, 
				basename( dirname( __FILE__ ) ) . '/languages' 
			); 

		}


		/**
		 * Register settings sections, fields, etc
		 *
		 * @return void
		 */
		public function admin_init() {
			
			// Register a setting and its data.
			register_setting( 
				$this->option_group, 
				$this->option_name,
				array($this, 'sanitize_callback')
			);
			
			// Add a new section to a settings page.
			add_settings_section(
				$this->option_name . '_section', 
				__('General Settings', 'ga-tracking-code'), 
				null, 
				$this->option_group
			);


			// Add a new section to a settings page.
			add_settings_section(
				$this->option_name . '_advanced_settings_section', 
				__('Advanced settings', 'ga-tracking-code'), 
				null, 
				$this->option_group
			);

			// Add a new field to a section of a settings page
			add_settings_field( 
				'tracking_id', 
				'Tracking ID', 
				array($this, 'tracking_id_render'), 
				$this->option_group, 
				$this->option_name . '_section'
			);

			// Add a new field to a section of a settings page
			add_settings_field( 
				'script_position', 
				__('Script Location', 'ga-tracking-code'), 
				array($this, 'script_position_render'), 
				$this->option_group, 
				$this->option_name . '_advanced_settings_section'
			);

			// Add a new field to a section of a settings page
			add_settings_field( 
				'track_administrator', 
				__('Track administrator hits?', 'ga-tracking-code'), 
				array($this, 'track_administrator_render'), 
				$this->option_group, 
				$this->option_name . '_advanced_settings_section'
			);
			
		}


		/**
		 * Create and link options page
		 * 
		 * @return void
		 */
		public function admin_menu() {
			
			// Add submenu page to the Settings main menu.
			add_options_page(
				$this->plugin_name,
				$this->plugin_name,
				$this->required_capability,
				$this->option_name,
				array($this, 'options_page')
			);

		}


		/**
		 * This injects the Google Analytics code into the footer or head of the page.
		 *
		 * @return void
		 */
		public function script() {

			if( $this->allow_tracking() ) { 

?>
<!-- Ga Tracking Code https://wordpress.org/plugins/ga-tracking-code/ -->
<script type="text/javascript">
(function(i,s,o,g,r,a,m){i['GoogleAnalyticsObject']=r;i[r]=i[r]||function(){
(i[r].q=i[r].q||[]).push(arguments)},i[r].l=1*new Date();a=s.createElement(o),
m=s.getElementsByTagName(o)[0];a.async=1;a.src=g;m.parentNode.insertBefore(a,m)
})(window,document,'script','//www.google-analytics.com/analytics.js','ga');
ga('create', '<?php echo esc_js($this->options['tracking_id']); ?>', 'auto');
ga('send', 'pageview');
</script>
<!-- / Ga Tracking Code https://wordpress.org/plugins/ga-tracking-code/ --> 
<?php
			
			}   // <= allow_tracking

		}  // <= script


		/**
		 * Check if google analytics tracking is allowed
		 *
		 * @access private 
		 * 
		 * @return bool True if google analytics tracking is allowed
		 */
		private function allow_tracking() {

			// Bail out if tracking_id is empty
			if( empty( $this->options['tracking_id'] ) ) 
				return false;

			// Bail out if user is an admin and track_administrator is false
			if ( current_user_can( $this->required_capability ) && ! $this->options['track_administrator'] ) 
				return false;
						
			/**
			 * Filters: Check if tracking is allowed
			 * 
			 * @param bool
			 */
			return (bool) apply_filters('gatc_allow_tracking', true );

		}


		/**
		 * Returns the set options
		 *
		 * @access private
		 * 
		 * @return array
		 */
		private function get_options() {

			$options = get_option( $this->option_name, $this->default_options );
			
			if( !is_array( $options ) ){
				
				$options = $this->default_options;
			
			}

			return $this->sanitize_callback( $options );

		}
		

		/**
		 * Sanitize script position
		 *
		 * @access private
		 * 
		 * @return string
		 */
		private function sanitize_script_position( $val = '' ) {
			return ( $val == 'wp_footer' ) ? 'wp_footer' : 'wp_head';
		}


		/**
		 * Sanitize Tracking ID
		 * 
		 * @link https://developers.google.com/analytics/devguides/collection/analyticsjs/field-reference#trackingId
		 * @access private
		 * 
		 * @return string
		 */
		private function sanitize_tracking_id( $val = '' ) {
			return preg_replace("/[^A-Za-z0-9-]/","", $val );
		}


		/**
		 * Validate Boolean
		 *  
		 * @param  mixed  $val
		 * @access private 
		 * 
		 * @return bool  $val
		 */
		private static function sanitize_track_administrator( $val = null ) {
			return filter_var( $val, FILTER_VALIDATE_BOOLEAN );
		}


		/**
		 * A custom sanitize callback that will be used to properly save the values.
		 *
		 * @param $input array
		 * 
		 * @access private
		 * @return array $sanitized
		 */
		private function sanitize_callback( $input = array() ) {
			
			$sanitized = array();

			$sanitized[ 'tracking_id' ] = $this->sanitize_tracking_id( 
				isset( $input[ 'tracking_id' ] ) ? $input[ 'tracking_id' ] : $this->default_options[ 'tracking_id' ] 
			);

			$sanitized[ 'script_position' ] = $this->sanitize_script_position( 
				isset( $input[ 'script_position' ] ) ? $input[ 'script_position' ] : $this->default_options[ 'script_position' ]
			);

			$sanitized[ 'track_administrator' ] = $this->sanitize_track_administrator( 
				isset( $input[ 'track_administrator' ] ) ? $input[ 'track_administrator' ] : $this->default_options[ 'track_administrator' ]
			);

			return $sanitized;

		}


		/**
     * Builds out the options panel.
     * 
     * Output nonce, action, and option_page fields
     * Prints out all settings sections
		 *
     * @return void
     */
		public function options_page() { ?>           
			
			<div class="wrap">               
			<h2><?php echo esc_html($this->plugin_name); ?></h2> 
			<p><em><?php echo esc_html__( 'Google Analytics Tracking Code' , 'ga-tracking-code') ?></em></p> 
			<hr />
			<form action='options.php' method='post'>
			<?php settings_fields( $this->option_group ); ?>
			<?php do_settings_sections( $this->option_group ); ?>
			<?php submit_button(); ?>
			</form>
			<br />
			<hr />
			<?php $this->donations(); ?>
			</div> <?php 

		}


		/**
		 * HTML Field: Tracking ID
		 *  
		 * @return void
		 */
		public function tracking_id_render() { ?>
			
			<input placeholder="UA-XXXXXXXX-X" type='text' name='<?php echo esc_attr($this->option_name) ; ?>[tracking_id]' value='<?php echo esc_attr($this->options['tracking_id']) ; ?>'>
			<p class="description">
				<?php 
					printf(
						esc_html__('Enter your Google Analytics Tracking ID for this website (e.g: %s).',  'ga-tracking-code'), 'UA-XXXXXXXX-X' );
					?>
					<br />
					<?php 
					printf(
						esc_html__('Tip: If you do not know your Tracking ID, you can use the %s to find it.',  'ga-tracking-code'), 
						'<a href="https://ga-dev-tools.appspot.com/account-explorer/" target="_blank"> Account Explorer</a>	'
					);
				?>
			</p> <?php

		}


		/**
		 * HTML Field: Script Location
		 * 
		 * @return void
		 */
		public function script_position_render() { ?>
			
			<select name='<?php echo esc_attr( $this->option_name ) ; ?>[script_position]' > 
				<option value="wp_head" <?php selected( $this->options['script_position'], "wp_head" ); ?>>Header</option>
				<option value="wp_footer" <?php selected( $this->options['script_position'], "wp_footer" ); ?>>Footer</option>
			</select> <?php		

		}


		/**
		 * HTML Field: Track Administrator
		 * 
		 * @return void
		 */
		public function track_administrator_render() { ?>
			
			<select name='<?php echo esc_attr( $this->option_name ) ; ?>[track_administrator]' > 
				<option value="1" <?php selected( $this->options['track_administrator'], true ); ?>><?php echo esc_html__( 'Yes', 'ga-tracking-code' ); ?></option>
				<option value="0" <?php selected( $this->options['track_administrator'], false ); ?>><?php echo esc_html__( 'No', 'ga-tracking-code' ); ?></option>
			</select> <?php	

		}

		
		/**
		 * 
		 * @access private 
		 * @return void
		 * 
		 */
		private function donations() {			
			
			$link =  sprintf( 
				'<strong><a target="_blank" href="%s">%s</a></strong>',
				esc_url('https://www.paypal.me/francescotaurino'),
				esc_html__( 'donation', 'ga-tracking-code' )
			);

			echo '<em>';
			echo sprintf(
				__( 'If you find this plugin useful, please consider making a %s.' , 'ga-tracking-code'),$link);
			echo ' ';
			echo esc_html__( 'Your donation will help encourage and support the plugin\'s continued development and better user support.' , 'ga-tracking-code');
			echo '</em>';

		}
		

	} // <= Class


	Ga_Tracking_Code::get_instance();


endif;

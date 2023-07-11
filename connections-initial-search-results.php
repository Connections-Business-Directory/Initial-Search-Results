<?php
/**
 * An extension for the Connections Business Directory plugin which adds the option to suppress the display of the initial results.
 *
 * @package   Connections Business Directory Extension - Initial Search Results
 * @category  Extension
 * @author    Steven A. Zahm
 * @license   GPL-2.0+
 * @link      https://connections-pro.com
 * @copyright 2021 Steven A. Zahm
 *
 * @wordpress-plugin
 * Plugin Name:       Connections Business Directory Extension - Initial Search Results
 * Plugin URI:        https://connections-pro.com/documentation/
 * Description:       An extension for the Connections Business Directory plugin which adds the option to suppress the display of the initial results.
 * Version:           1.0.2
 * Author:            Steven A. Zahm
 * Author URI:        https://connections-pro.com
 * License:           GPL-2.0+
 * License URI:       https://www.gnu.org/licenses/gpl-2.0.txt
 * Text Domain:       connections_initial_search_results
 * Domain Path:       /languages
 */

// If this file is called directly, abort.
if ( ! defined( 'WPINC' ) ) {
	die;
}

use Connections_Directory\Utility\_format;

if ( ! class_exists( 'Connections_Initial_Search_Results' ) ) {

	final class Connections_Initial_Search_Results {

		const VERSION = '1.0.2';

		/**
		 * @var string The absolute path this file.
		 *
		 * @access private
		 * @since 1.0
		 */
		private static $file = '';

		/**
		 * @var string The URL to the plugin's folder.
		 *
		 * @access private
		 * @since 1.0
		 */
		private static $url = '';

		/**
		 * @var string The absolute path to this plugin's folder.
		 *
		 * @access private
		 * @since 1.0
		 */
		private static $path = '';

		/**
		 * @var string The basename of the plugin.
		 *
		 * @access private
		 * @since 1.0
		 */
		private static $basename = '';

		public function __construct() {

			self::$file       = __FILE__;
			self::$url        = plugin_dir_url( self::$file );
			self::$path       = plugin_dir_path( self::$file );
			self::$basename   = plugin_basename( self::$file );

			// This should run on the `plugins_loaded` action hook. Since the extension loads on the
			// `plugins_loaded action hook, call immediately.
			self::loadTextdomain();

			/*
			 * Register the settings tabs shown on the Settings admin page tabs, sections and fields.
			 */
			//add_filter( 'cn_register_settings_tabs', array( __CLASS__, 'registerSettingsTab' ) );
			//add_filter( 'cn_register_settings_sections', array( __CLASS__, 'registerSettingsSections' ) );
			add_filter( 'cn_register_settings_fields', array( __CLASS__, 'registerSettingsFields' ) );

			add_filter( 'the_posts', array( __CLASS__, 'cn_initial_search_filter' ), 1000, 2 );
		}

		/**
		 * Load the plugin translation.
		 *
		 * Credit: Adapted from Ninja Forms / Easy Digital Downloads.
		 *
		 * @access private
		 * @since  1.0
		 * @static
		 *
		 * @return void
		 */
		public static function loadTextdomain() {

			// Plugin textdomain. This should match the one set in the plugin header.
			$domain = 'connections_initial_search_results';

			// Set filter for plugin's languages directory
			$languagesDirectory = apply_filters( "cn_{$domain}_languages_directory", dirname( self::$file ) . '/languages/' );

			// Traditional WordPress plugin locale filter
			$locale   = apply_filters( 'plugin_locale', get_locale(), $domain );
			$fileName = sprintf( '%1$s-%2$s.mo', $domain, $locale );

			// Setup paths to current locale file
			$local  = $languagesDirectory . $fileName;
			$global = WP_LANG_DIR . "/{$domain}/" . $fileName;

			if ( file_exists( $global ) ) {

				// Look in global `../wp-content/languages/{$domain}/` folder.
				load_textdomain( $domain, $global );

			} elseif ( file_exists( $local ) ) {

				// Look in local `../wp-content/plugins/{plugin-directory}/languages/` folder.
				load_textdomain( $domain, $local );

			} else {

				// Load the default language files
				load_plugin_textdomain( $domain, FALSE, $languagesDirectory );
			}
		}

		/**
		 * Register the settings fields.
		 *
		 * @access private
		 * @since  1.0
		 * @static
		 *
		 * @param array $fields
		 *
		 * @return array The settings fields options array.
		 */
		public static function registerSettingsFields( $fields ) {

			$settings = 'connections_page_connections_settings';

			$fields[] = array(
				'plugin_id' => 'connections',
				'id'        => 'suppress_results',
				'position'  => 35,
				'page_hook' => $settings,
				'tab'       => 'search',
				'section'   => 'connections_search',
				'title'     => __( 'Suppress Results', 'connections_initial_search_results' ),
				'desc'      => __(
					'Suppress results until user performs a search.',
					'connections_initial_search_results'
				),
				'help'      => '',
				'type'      => 'checkbox',
				'default'   => 0
			);

			return $fields;
		}

		public static function cn_initial_search_filter( $posts, $WP_Query ) {
			global $wp_query;

			if ( ! class_exists('cnTemplatePart') ) return $posts;

			$shortcode = 'connections';
			//$pattern   = get_shortcode_regex();

			// Grab the array containing all query vars registered by Connections.
			$registeredQueryVars = cnRewrite::queryVars( array() );

			foreach ( $posts as $post ) {

				// If we're in the main query, proceed!
				if ( isset( $WP_Query->queried_object_id ) && $WP_Query->queried_object_id == $post->ID ) {

					/*
					 * $matches[0] == An array of all shortcode that were found with its options.
					 * $matches[1] == Unknown.
					 * $matches[2] == An array of all shortcode tags that were found.
					 * $matches[3] == An array of the shortcode options that were found.
					 * $matches[4] == Unknown.
					 * $matches[5] == Unknown.
					 * $matches[6] == Unknown.
					 */
					$matches = cnShortcode::find( 'connections', $post->post_content, 'matches' );

					if ( $matches ) {

						// Parse the shortcode atts.
						//$atts = shortcode_parse_atts( $matches[3][ array_search( $shortcode, $matches[2] ) ] );
						$atts = shortcode_parse_atts( $matches[0][3] );

						if ( ! is_array( $atts ) ) {

							$atts = array();
						}

						// Remove the cn-image query vars.
						$wp_query->query_vars = array_diff_key( (array) $wp_query->query_vars, array_flip( array( 'src', 'w', 'h', 'q', 'a', 'zc', 'f', 's', 'o', 'cc', 'ct' ) ) );

						// Show just the search form w/o showing the initial results?
						// If a Connections query var is set, show the results instead.
						if ( '1' === cnSettingsAPI::get( 'connections', 'search', 'suppress_results' ) &&
						     FALSE == (bool) array_intersect( $registeredQueryVars, array_keys( (array) $wp_query->query_vars ) )
						) {

							// Add return = true to $atts so template parts are returned instead of echoed.
							$atts['return'] = TRUE;

							// Enqueue the CSS and JS. We need to use the action because we're hooked
							// way to early in WP to use wp_enqueue_style() and wp_enqueue_script().
							add_action( 'wp_enqueue_scripts', array( 'cnScript', 'enqueueStyles' ) );

							// Add action to enqueue Chosen.
							add_action( 'wp_enqueue_scripts', array( __CLASS__, 'cn_enqueue_chosen' ) );
							add_action( 'wp_print_footer_scripts', array( __CLASS__, 'cn_init_chosen' ), 999 );

							$categoryProperties = array( 'return' => true );
							$formProperties     = array( 'return' => true );

							if ( array_key_exists( 'str_select', $atts ) ) {

								$categoryProperties['default'] = $atts['str_select'];
							}

							if ( array_key_exists( 'str_select_all', $atts ) ) {

								$categoryProperties['select_all'] = $atts['str_select_all'];
							}

							if ( array_key_exists( 'enable_category_multi_select', $atts ) ) {

								_format::toBoolean( $atts['enable_category_multi_select'] );
								$categoryProperties['type'] = $atts['enable_category_multi_select'] ? 'multiselect' : 'select';
							}

							if ( array_key_exists( 'enable_category_group_by_parent', $atts ) ) {

								$categoryProperties['group'] = $atts['enable_category_group_by_parent'];
							}

							if ( array_key_exists( 'show_category_count', $atts ) ) {

								$categoryProperties['show_count'] = $atts['show_category_count'];
							}

							if ( array_key_exists( 'show_empty_categories', $atts ) ) {

								$categoryProperties['show_empty'] = $atts['show_empty_categories'];
							}

							if ( array_key_exists( 'enable_category_by_root_parent', $atts ) ) {

								_format::toBoolean( $atts['enable_category_by_root_parent'] );
								$categoryProperties['parent_id'] = $atts['enable_category_by_root_parent'] ? $atts['category'] : array();
							}

							if ( array_key_exists( 'exclude_category', $atts ) ) {

								$categoryProperties['exclude'] = $atts['exclude_category'];
							}

							if ( array_key_exists( 'force_home', $atts ) ) {

								_format::toBoolean( $atts['force_home'] );
								$formProperties['force_home'] = $atts['force_home'];

							} else {

								$formProperties['force_home'] = false;
							}

							if ( array_key_exists( 'home_id', $atts ) ) {

								$formProperties['home_id'] = $atts['home_id'];

							} else {

								$formProperties['home_id'] = cnShortcode::getHomeID();
							}

							// Passing the shortcode $atts in case there are any template part specific shortcode options set.
							$category  = cnTemplatePart::category( $categoryProperties );
							$search    = cnTemplatePart::search( $atts );
							$formOpen  = cnTemplatePart::formOpen( $formProperties );
							$formClose = cnTemplatePart::formClose( $atts );

							// Create the final output.
							$replace = $formOpen . $category . $search . $formClose;

							// All returns and tabs should be removed so wpautop() doesn't insert <p> and <br> tags in the form output.
							//$replace = str_replace( array( "rn", "r", "n", "t" ), array( ' ', ' ', ' ', ' ' ), $replace );

							// This needs to be wrapped in a div with an id of #cn-list so the CSS styles will be applied.
							$replace = '<div id="cn-list" style="min-height: 250px;">' . $replace . '</div>';

						} else {

							// Rewrite the $atts array to prep it to be imploded.
							array_walk( $atts, function( &$i, $k ) { $i = "{$k}=\"{$i}\""; } );

							$replace = '[' . $shortcode . ' ' . implode( ' ', $atts ) . ']';
						}

						// Replace the shortcode in the post with something a new one based on you changes to $atts.
						$post->post_content = str_replace( $matches[0][0], $replace, $post->post_content );
					}

				}

			}

			return $posts;
		}

		public static function cn_enqueue_chosen() {

			// If SCRIPT_DEBUG is set and TRUE load the non-minified JS files, otherwise, load the minified files.
			$min = defined('SCRIPT_DEBUG') && SCRIPT_DEBUG ? '' : '.min';

			wp_enqueue_script( 'jquery-chosen', CN_URL . "vendor/chosen/chosen.jquery$min.js", array( 'jquery' ), '1.1.0', TRUE );
		}

		public static function cn_init_chosen() {

			?>

			<script type="text/javascript">/* <![CDATA[ */
				jQuery('select[name^=cn-cat]').chosen();
			/* ]]> */</script>

			<?php

		}
	}

	/**
	 * Start up the extension.
	 *
	 * @access public
	 * @since 1.0
	 *
	 * @return mixed object | bool
	 */
	function Connections_Initial_Search_Results() {

		if ( class_exists('connectionsLoad') ) {

			return new Connections_Initial_Search_Results();

		} else {

			add_action(
				'admin_notices',
				static function() {
					echo '<div id="message" class="error"><p><strong>ERROR:</strong> Connections must be installed and active in order use this Connections addon.</p></div>';
				}
			);

			return FALSE;
		}
	}

	/**
	 * Since Connections loads at default priority 10, and this extension is dependent on Connections,
	 * we'll load with priority 11, so we know Connections will be loaded and ready first.
	 */
	add_action( 'plugins_loaded', 'Connections_Initial_Search_Results', 11 );

}


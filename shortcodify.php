<?php
/*
Copyright: © 2015 art@tec
<mailto:al@art-at-tec.de> <http://www.art-at-tec.de/>

Released under the terms of the GNU General Public License.
You should have received a copy of the GNU General Public License,
along with this software. In the main directory, see: /licensing/
If not, see: <http://www.gnu.org/licenses/>.
*/
/*
Version: 1.3.1

Author URI: http://www.art-at-tec.de/
Author: Arne Lorenz

Plugin Name: Shortcodify
Plugin URI: http://www.arnelorenz.de/shortcodify/
Description: Plugin to add shortcodes
*/

if ( ! class_exists( 'shortcodify' ) ) {
	class shortcodify {
		/**
		 * @var string The plugin version
		 */
		var $version = '1.3.1';
		/**
		 * @var string The options string name for this plugin
		 */
		var $optionsName = 'wp_shortcodify_options';
		/**
		 * @var string $pluginurl The url to this plugin
		 */
		var $pluginurl = '';
		/**
		 * @var string $pluginpath The path to this plugin
		 */
		var $pluginpath = '';


		var $activeFunctions = array();
		var $bookmark_args = array();

		/**
		 * @var array $options Stores the options for this plugin
		 */
		var $options = array();


		/**
		 * PHP 5 Constructor
		 */
		function __construct() {
			$name                     = dirname( plugin_basename( __FILE__ ) );
			$this->localizationDomain = $name;
			//Language Setup
			load_plugin_textdomain( $this->localizationDomain, FALSE, $name . '/lang/' );

			//"Constants" setup
			$this->pluginurl  = plugins_url( $name ) . "/";
			$this->pluginpath = WP_PLUGIN_DIR . "/$name/";

			$this->bookmark_args = array(
				'orderby'          => 'name',
				'order'            => 'ASC',
				'limit'            => - 1,
				'category'         => '',
				'exclude_category' => '',
				'category_name'    => '',
				'hide_invisible'   => 1,
				'show_updated'     => 0,
				'echo'             => 0,
				'categorize'       => 1,
				'title_li'         => __( 'Bookmarks' ),
				'title_before'     => '<h2>',
				'title_after'      => '</h2>',
				'category_orderby' => 'name',
				'category_order'   => 'ASC',
				'class'            => 'linkcat',
				'category_before'  => '<li id=%id class=%class>',
				'category_after'   => '</li>'
			);

			//Initialize the options
			$this->get_options();

			// which shortcodes are active?
			$this->check_active_functions();

			//Actions
			$this->create_Actions();

			// ADD the shortcodes
			$this->create_shortcode();

		}

// ----  -----------

		private function check_active_functions() {
			$this->activeFunctions = array(
				'wsc',
				'unshortcode',
				'br',
				'hr',
				'date',
				'time',
				'links',
				'random'
			);
			/* todo:
			- analytics opt out
			- post content
			- meta options
			*/
		}

		private function create_Actions() {
			// WP Admin Menu entry
			add_action( 'admin_menu', array( &$this, 'admin_menu_link' ) );
		}

		private function create_shortcode() {
			// SHORTCODES

			foreach ( $this->activeFunctions as $name ) {
				add_shortcode( $name, array( &$this, $name ) );
			}

			// To use a widget-area in a shortcode
			if ( $this->options['sc_widget'] ) {
				$this->useWidgets();
			}

			// To use a menu in a widget
			if ( $this->options['sc_menu'] ) {
				$this->useMenus();
			}

			// To use a accordion
			if ( $this->options['sc_accordion'] ) {
				$this->useAccordion();
			}
		}

		/*
			Function for small shortcode handling
			instead of: function wrap_shortcode_func($atts, $content= NULL)
		*/
		public function __call( $name, $argumente ) {
			// for calling the widget functions
			if ( substr( $name, 0, 12 ) == 'createWidget' ) {
				$parts  = explode( '-', $name );
				$name   = $parts[0];
				$number = $parts[1];
			}

			$atts    = $argumente[0];
			$content = $argumente[1];
			switch ( $name ) {
				case 'hr':
					$rtn = '<hr class="clear" style="clear: both">';
					break;
				case 'date':
					$rtn = '<span class="sc_date">' . date_i18n( get_option( 'date_format' ) ) . '</span>';
					break;
				case 'time':
					$rtn = '<span class="sc_time">' . date_i18n( get_option( 'time_format' ) ) . '</span>';
					break;
				case 'br':
					$rtn = '<br class="clear" style="clear: both">';
					break;
				case 'unshortcode':
					$rtn = $content;
					break;
				case 'wsc':
					$rtn = '<span class="wrap_shortcode ' . $atts['link'] . '">' . do_shortcode( $content ) . '</span>';
					break;
				case 'links':
					$html = wp_list_bookmarks( $this->bookmark_args );
					$rtn  = '<ul class="shortcodify_links">' . $html . '</ul>';
					break;
				case 'random':
					if ( isset( $atts['trennzeichen'] ) ) {
						$trenner = $atts['trennzeichen'];
					} elseif ( isset( $atts['separator'] ) ) {
						$trenner = $atts['separator'];
					} else {
						$trenner = PHP_EOL;
					}

					$content = explode( $trenner, $content );
					$rnd     = rand( 0, count( $content ) - 1 );
					$rtn     = '<span class="wrap_shortcode random">' . do_shortcode( $content[ $rnd ] ) . '</span>';
					break;
				case 'createWidget':
					$html = $this->getWidgetContent( 'Shortcodify ' . $number );

					return '<div class="shortcodify_widget sc_w' . $number . '">' . $html . '</div>';
					break;
				default:
					$rtn = $content;
			}

			return $rtn;
		}

		function useWidgets() {
			add_action( 'widgets_init', array( &$this, 'shortcodify_widgets_init' ) );
			add_shortcode( 'widget', array( &$this, 'createWidget' ) );
			for ( $i = 1; $i <= $this->options['sc_widget_number'] + 1; $i ++ ) {
				// i dont know how to give this function-call an parameter :(
				// so i use this _call method
				add_shortcode( 'widget' . $i, array( &$this, 'createWidget-' . $i ) );
			}
		}

		function useAccordion() {
			//Register with hook 'wp_enqueue_scripts', which can be used for front end CSS and JavaScript
			add_action( 'wp_enqueue_scripts', array( &$this, 'loadCss' ) );
			add_action( 'wp_enqueue_scripts', array( &$this, 'loadJs' ) );
			add_shortcode( 'accordion', array( &$this, 'createAccordion' ) );
			add_shortcode( 'section', array( &$this, 'createAccordionSection' ) );
		}

		function loadJs() {
			wp_enqueue_script( 'jquery' );
			wp_enqueue_script( 'jquery-ui-accordion' );
			wp_enqueue_script(
				'shortcodify',
				plugins_url( '/js/shortcodify.js', __FILE__ ),
				array( 'jquery' ),
				FALSE,
				TRUE
			);
		}

		/**
		 * Enqueue plugin style-file
		 */
		function loadCss() {
			// Respects SSL, Style.css is relative to the current file
			// this css changes some font-sizes etc. :(
			//wp_register_style( 'jquery-ui-default', 'http://code.jquery.com/ui/1.10.1/themes/base/jquery-ui.css' );
			//wp_enqueue_style( 'jquery-ui-default' );
			// we use this
			wp_register_style( 'shortcodify', plugins_url( 'css/shortcodify.css', __FILE__ ) );
			wp_enqueue_style( 'shortcodify' );
		}

		function useMenus() {
			add_action( 'init', array( &$this, 'register_my_menus' ) );
			add_shortcode( 'menu', array( &$this, 'createMenu' ) );
		}

		function register_my_menus() {
			register_nav_menus( array(
				'Shortcodify' => __( 'Shortcodify Menu, for sitemaps' )
			) );
		}

		static function getWidgetContent( $area = 'Shortcodify' ) {
			ob_start();
			dynamic_sidebar( $area );
			$html = ob_get_contents();
			ob_end_clean();

			return $html;
		}

		function createWidget( $number ) {
			$html = $this->getWidgetContent( 'Shortcodify ' . $number );

			return '<div class="shortcodify_widget sc_w' . $number . '">' . $html . '</div>';
		}

		// menu shortcode output
		function createMenu() {
			ob_start();
			wp_nav_menu( array( 'menu' => 'Shortcodify' ) );
			$html = ob_get_contents();
			ob_end_clean();

			return '<div class="shortcodify_menu">' . $html . '</div>';
		}

		function createAccordion( $atts, $content = NULL ) {
			//$atts['link']
			if ( isset( $atts['type'] ) ) {
				$name = $atts['type'];
			} else {
				$name = 'no-auto';
			}

			$ausgabe = trim( $content );
			//preg_match_all('/\[section\].*\[\/section\]/', $content, $ausgabe);

			// remove whitespace at beginning
			$ausgabe = preg_replace( '/\A.*?\[section/s', '[section', $ausgabe );
			// remove whitespace at end of sting
			$ausgabe = preg_replace( '/\A.*?\]noitces\/\[/s', ']noitces/[', strrev( $ausgabe ) );
			$ausgabe = strrev( $ausgabe );
			// remove whitespace between tags
			$ausgabe = preg_replace( '/\[\/section\].*?\[section/s', '[/section][section', $ausgabe );

			$rtn = '<div class="sc_accordion ' . $name . '">' . do_shortcode( $ausgabe ) . '</div>';

			return $rtn;
		}

		function createAccordionSection( $atts, $content = NULL ) {
			$rtn = '<h3>' . $atts['name'] . '</h3><div>' . do_shortcode( $content ) . '</div>';

			return $rtn;
		}

		/**
		 * Adds a widget Area
		 *
		 */
		function shortcodify_widgets_init() {
			$conf = array(
				'name'          => 'Shortcodify',
				'id'            => 'shortcodify',
				'description'   => __( 'Add this widget to an other textfield with [widget]', $this->localizationDomain ),
				'before_widget' => '<p class="shortcodify_widget">',
				'after_widget'  => '</p>',
				'before_title'  => '<h2>',
				'after_title'   => '</h2>',
			);
			register_sidebar( $conf );

			for ( $i = 1; $i <= $this->options['sc_widget_number'] + 1; $i ++ ) {
				$conf = array(
					'name'          => 'Shortcodify ' . $i,
					'id'            => 'shortcodify' . $i,
					'description'   => __( 'Add this widget to an other textfield with [widget' . $i . ']', $this->localizationDomain ),
					'before_widget' => '<p class="shortcodify_widget sc_w' . $i . '">',
					'after_widget'  => '</p>',
					'before_title'  => '<h2>',
					'after_title'   => '</h2>',
				);
				register_sidebar( $conf );
			}
		}

		/**
		 * Retrieves the plugin options from the database.
		 * @return array
		 */
		function get_options() {
			if ( ! $options = get_option( $this->optionsName ) ) {
				$options = array(
					'sc_widget'        => TRUE,
					'sc_widget_number' => 0,
					'sc_menu'          => TRUE,
					'sc_accordion'     => TRUE
					/*
					'title' => 'Pages:',
					'nextpage' => '&raquo;',
					'previouspage' => '&laquo;',
					'css' => true,
					'before' => '<div class="navigation">',
					'after' => '</div>',
					
					'range' => 3,
					'anchor' => 1,
					'gap' => 3
					*/
				);
				update_option( $this->optionsName, $options );
			}
			$this->options = $options;
		}

		/**
		 * Saves the admin options to the database.
		 */
		function save_admin_options() {
			return update_option( $this->optionsName, $this->options );
		}

		/**
		 * @desc Adds the options subpanel
		 */
		function admin_menu_link() {
			add_options_page( 'Shortcodify', 'Shortcodify', 'manage_options', basename( __FILE__ ), array(
				&$this,
				'admin_options_page'
			) );
			add_filter( 'plugin_action_links_' . plugin_basename( __FILE__ ), array(
				&$this,
				'filter_plugin_actions'
			), 10, 2 );
		}

		/**
		 * @desc Adds the Settings link to the plugin activate/deactivate page
		 */
		function filter_plugin_actions( $links, $file ) {
			$settings_link = '<a href="options-general.php?page=' . basename( __FILE__ ) . '">' . __( 'Settings', $this->localizationDomain ) . '</a>';
			array_unshift( $links, $settings_link ); // before other links

			return $links;
		}

		/**
		 * Adds settings/options page
		 */
		function admin_options_page() {
			if ( isset( $_POST['wp_test_save'] ) ) {
				if ( wp_verify_nonce( $_POST['_wpnonce'], 'wp-test-update-options' ) ) {
					$this->options['sc_widget']        = ( isset( $_POST['sc_widget'] ) && $_POST['sc_widget'] === 'on' ) ? TRUE : FALSE;
					$this->options['sc_widget_number'] = (int) $_POST['sc_widget_number'];

					$this->options['sc_menu']      = ( isset( $_POST['sc_menu'] ) && $_POST['sc_menu'] === 'on' ) ? TRUE : FALSE;
					$this->options['sc_accordion'] = ( isset( $_POST['sc_accordion'] ) && $_POST['sc_accordion'] === 'on' ) ? TRUE : FALSE;
					$this->save_admin_options();

					echo '<div class="updated"><p>' . __( 'Success! Your changes were successfully saved!', $this->localizationDomain ) . '</p></div>';
				} else {
					echo '<div class="error"><p>' . __( 'Whoops! There was a problem with the data you posted. Please try again.', $this->localizationDomain ) . '</p></div>';
				}
			}
			?>

			<div class="wrap">
				<div class="icon32" id="icon-options-general"><br/></div>
				<h2><?php _e( 'Shortcodify settings', $this->localizationDomain ); ?></h2>

				<form method="post" id="wp_test_options">
					<?php wp_nonce_field( 'wp-test-update-options' ); ?>
					<h3><?php _e( 'Settings', $this->localizationDomain ); ?></h3>
					<h5><?php _e( 'See all possible shortcodes <a href="http://www.arnelorenz.de/shortcodify/">here</a>', $this->localizationDomain ); ?></h5>
					<table class="form-table">

						<tr valign="top">
							<th scope="row"><?php _e( 'Create widget-shortcode', $this->localizationDomain ); ?></th>
							<td><label for="empty">
									<input type="checkbox" id="sc_widget"
									       name="sc_widget" <?php echo ( $this->options['sc_widget'] === TRUE ) ? "checked='checked'" : ""; ?>/> <?php
									_e( 'Creates a widget-area for a shortcode. Use it with [widget]', $this->localizationDomain ); ?>
								</label></td>
						</tr>
						<tr valign="top">
							<th scope="row"><?php _e( 'Number of widgets to create', $this->localizationDomain ); ?></th>
							<td>
								<select name="sc_widget_number" id="sc_widget_number">
									<?php for ( $i = 0; $i <= 20; $i ++ ) : ?>
										<option
											value="<?php echo $i; ?>" <?php echo ( $i == $this->options['sc_widget_number'] ) ? "selected='selected'" : ""; ?>><?php echo $i; ?></option>
									<?php endfor; ?>
								</select>
								<span
									class="description"><?php _e( 'Select the number of widgets you want to create.', $this->localizationDomain ); ?></span>
							</td>
						</tr>
						<tr valign="top">
							<th scope="row"><?php _e( 'Create menu-shortcode', $this->localizationDomain ); ?></th>
							<td><label for="empty">
									<input type="checkbox" id="sc_menu"
									       name="sc_menu" <?php echo ( $this->options['sc_menu'] === TRUE ) ? "checked='checked'" : ""; ?>/> <?php
									_e( 'Creates a menu-area for a shortcode. Create a new menu e.g. for a sitemap. Use shortcode with [menu] ', $this->localizationDomain ); ?>
								</label></td>
						</tr>
						<tr valign="top">
							<th scope="row"><?php _e( 'Create accordion-shortcode', $this->localizationDomain ); ?></th>
							<td><label for="empty">
									<input type="checkbox" id="sc_accordion"
									       name="sc_accordion" <?php echo ( $this->options['sc_accordion'] === TRUE ) ? "checked='checked'" : ""; ?>/> <?php
									_e( 'Creates a <a href="http://jqueryui.com/accordion/" target="_blank">accordion</a> shortcode. ', $this->localizationDomain ); ?>
								</label></td>
						</tr>

					</table>
					<p class="submit">
						<input type="submit" value="<?php _e( 'Save Changes', $this->localizationDomain ); ?>"
						       name="wp_test_save" class="button-primary"/>
					</p>
				</form>
			</div>

		<?php
		}
	}
}

//Adds a widget

class ShortcodifyWidget extends WP_Widget {

	function ShortcodifyWidget() {
		// Instantiate the parent object
		parent::__construct( FALSE, 'The Widget Title' );
	}

	function widget( $args, $instance ) {
		// Widget output
	}

	function update( $new_instance, $old_instance ) {
		// Save widget options
	}

	function form( $instance ) {
		// Output admin widget options form
	}
}

//instantiate the class
if ( class_exists( 'shortcodify' ) ) {
	$shortcodify = new shortcodify();
}
?>

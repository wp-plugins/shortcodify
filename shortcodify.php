<?php
/*
Copyright: © 2012 LRNZ ( coded in germany )
<mailto:me@arnelorenz.de> <http://www.arnelorenz.de/>

Released under the terms of the GNU General Public License.
You should have received a copy of the GNU General Public License,
along with this software. In the main directory, see: /licensing/
If not, see: <http://www.gnu.org/licenses/>.
*/
/*
Version: 1.0.0

Author URI: http://www.arnelorenz.de/
Author: Arne Lorenz

Plugin Name: Shortcodify
Plugin URI: http://www.arnelorenz.de/
Description: Plugin to add shortcodes
*/

if (!class_exists('shortcodify')) {
	class shortcodify {
		/**
		 * @var string The plugin version
		 */
		var $version = '1.0.0';

		/**
		 * @var string The options string name for this plugin
		 */
		var $optionsName = 'wp_test_options';
		
		/**
		 * @var string $pluginurl The url to this plugin
		 */
		var $pluginurl = '';
		/**
		 * @var string $pluginpath The path to this plugin
		 */
		var $pluginpath = '';

		/**
		 * @var array $options Stores the options for this plugin
		 */
		var $options = array();

		/**
		 * PHP 4 Compatible Constructor
		 */
		function test() {$this->__construct();}

		/**
		 * PHP 5 Constructor
		 */
		function __construct() {
			$name = dirname(plugin_basename(__FILE__));

			//Language Setup
			// load_plugin_textdomain($this->localizationDomain, false, "$name/I18n/");

			//"Constants" setup
			$this->pluginurl = plugins_url($name) . "/";
			$this->pluginpath = WP_PLUGIN_DIR . "/$name/";

			//Initialize the options
			$this->get_options();
			
			
			//Actions
			add_action('admin_menu', array(&$this, 'admin_menu_link'));
			
			// SHORTCODES
			add_shortcode( 'wsc', array(&$this, 'wrap_shortcode_func') );
			add_shortcode( 'unshortcode', array(&$this, 'wrap_unshortcode_func') );
			add_shortcode( 'br', array(&$this, 'createlinebreak') );
			add_shortcode( 'hr', array(&$this, 'createline') );
			add_shortcode( 'date', array(&$this, 'createDate') );
			add_shortcode( 'time', array(&$this, 'createTime') );
			add_shortcode( 'links', array(&$this, 'createLinks') );
			
			
			// To use a widget-area in a shortcode
			if($this->options['sc_widget'])
			{
				$this->useWidgets();
			}
			
			// To use a menu in a widget
			if($this->options['sc_menu'])
			{
				$this->useMenus();
			}

		}
		
		function useWidgets(){
			add_action( 'widgets_init', array(&$this, 'shortcodify_widgets_init') );
			
			add_shortcode( 'widget', array(&$this, 'createWidget') );
		}
		function useMenus(){
			add_action( 'init', array(&$this, 'register_my_menus') );
			add_shortcode( 'menu', array(&$this, 'createMenu') );
		}
		
		function register_my_menus() {
		  register_nav_menus(
		    array( 'shortcodify' => __( 'Shortcodify Menu' ) )
		  );
		}
		
		
		function wrap_shortcode_func_hm_KP($atts, $content= NULL) {
			 return '<span class="wrap_shortcode '.$atts['class'].'">'.apply_filters('the_content', $content).'</span>';
		}
		
		function wrap_unshortcode_func($atts, $content= NULL) {
			 return $content;
		}
		
		function wrap_shortcode_func($atts, $content= NULL) {
			 return '<span class="wrap_shortcode '.$atts['link'].'">'.do_shortcode($content).'</span>';
		}
		
		function createlinebreak() {
			 return '<br class="clear" style="clear: both">';
		}
		
		function createDate() {
			 return '<span class="sc_date">'.date_i18n( get_option('date_format') ).'</span>';
		}
		
		function createTime() {
			 return '<span class="sc_time">'.date_i18n( get_option('time_format') ).'</span>';
		}
		
		function createline() {
			 return '<hr class="clear" style="clear: both">';
		}
		
		function createWidget() {
			ob_start();
			dynamic_sidebar('Shortcodify');
			$html = ob_get_contents();
			ob_end_clean();
			
			return '<div class="shortcodify_widget">'.$html.'</div>';
		}	
		
		function createMenu() {
			ob_start();
			wp_nav_menu( array('menu' => 'Shortcodify' ));
			$html = ob_get_contents();
			ob_end_clean();
			
			return '<div class="shortcodify_menu">'.$html.'</div>';
		}
		
		function createLinks($atts, $content= NULL) {
			$args = array(
			    'orderby'          => 'name',
			    'order'            => 'ASC',
			    'limit'            => -1,
			    'category'         => '',
			    'exclude_category' => '',
			    'category_name'    => '',
			    'hide_invisible'   => 1,
			    'show_updated'     => 0,
			    'echo'             => 0,
			    'categorize'       => 1,
			    'title_li'         => __('Bookmarks'),
			    'title_before'     => '<h2>',
			    'title_after'      => '</h2>',
			    'category_orderby' => 'name',
			    'category_order'   => 'ASC',
			    'class'            => 'linkcat',
			    'category_before'  => '<li id=%id class=%class>',
			    'category_after'   => '</li>' );
			    
			$html = wp_list_bookmarks( $args );
			
			return '<ul class="shortcodify_links">'.$html.'</ul>';
		}	

		//adds a widget Area
		/**
		 * Register our sidebars and widgetized areas. 
		 *
		 */
		function shortcodify_widgets_init() 
		{
			register_sidebar( array(
				'name' => 'Shortcodify',
				'id' => 'shortcodify',
				'description' => __( 'Add this widget to an other textfield with [widget]' ),
				'before_widget' => '<p class="shortcodify_widget">',
				'after_widget' => '</p>',
				'before_title' => '<h2>',
				'after_title' => '</h2>',
			) );
			/*
			register_sidebar( array(
				'name' => 'disclaimer',
				'id' => 'disclaimer',
				'before_widget' => '<aside id="%1$s" class="widget %2$s">',
				'after_widget' => "</aside>",
				'before_title' => '<h3 class="widget-title">',
				'after_title' => '</h3>',
			) );
			*/
		/*	// add this to tmeplate
			<?php if ( ! dynamic_sidebar( 'name' ) ) :?><?php endif;?>
		*/
		}

		/**
		 * Retrieves the plugin options from the database.
		 * @return array
		 */
		function get_options() {
			if (!$options = get_option($this->optionsName)) {
				$options = array(
					'sc_widget' => true
					//'sc_menu' => true
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
				update_option($this->optionsName, $options);
			}
			$this->options = $options;
		}
		/**
		 * Saves the admin options to the database.
		 */
		function save_admin_options(){
			return update_option($this->optionsName, $this->options);
		}

		/**
		 * @desc Adds the options subpanel
		 */
		function admin_menu_link() {
			add_options_page('Shortcodify', 'Shortcodify', 'manage_options', basename(__FILE__), array(&$this, 'admin_options_page'));
			add_filter('plugin_action_links_' . plugin_basename(__FILE__), array(&$this, 'filter_plugin_actions'), 10, 2 );
		}

		/**
		 * @desc Adds the Settings link to the plugin activate/deactivate page
		 */
		function filter_plugin_actions($links, $file) {
			$settings_link = '<a href="options-general.php?page=' . basename(__FILE__) . '">' . __('Settings', $this->localizationDomain) . '</a>';
			array_unshift($links, $settings_link); // before other links

			return $links;
		}

		/**
		 * Adds settings/options page
		 */
		function admin_options_page() {
			if (isset($_POST['wp_test_save'])) {
				if (wp_verify_nonce($_POST['_wpnonce'], 'wp-test-update-options')) {
					$this->options['sc_widget'] = (isset($_POST['sc_widget']) && $_POST['sc_widget'] === 'on') ? true : false;
					
					//$this->options['sc_menu'] = (isset($_POST['sc_menu']) && $_POST['sc_menu'] === 'on') ? true : false;
					/*
					$this->options['title'] = $_POST['title'];
					$this->options['previouspage'] = $_POST['previouspage'];
					$this->options['nextpage'] = $_POST['nextpage'];
					$this->options['before'] = $_POST['before'];
					$this->options['after'] = $_POST['after'];
					$this->options['css'] = (isset($_POST['css']) && $_POST['css'] === 'on') ? true : false;
					$this->options['range'] = intval($_POST['range']);
					$this->options['anchor'] = intval($_POST['anchor']);
					$this->options['gap'] = intval($_POST['gap']);
					*/
					$this->save_admin_options();

					echo '<div class="updated"><p>' . __('Success! Your changes were successfully saved!', $this->localizationDomain) . '</p></div>';
				}
				else {
					echo '<div class="error"><p>' . __('Whoops! There was a problem with the data you posted. Please try again.', $this->localizationDomain) . '</p></div>';
				}
			}
?>

<div class="wrap">
<div class="icon32" id="icon-options-general"><br/></div>
<h2><?php _e('Shortcodify settings', $this->localizationDomain); ?></h2>
<form method="post" id="wp_test_options">
<?php wp_nonce_field('wp-test-update-options'); ?>
	<h3><?php _e('Settings', $this->localizationDomain); ?></h3>
	<table class="form-table">
	
		<tr valign="top">
			<th scope="row"><?php _e('Create widget-shortcode', $this->localizationDomain); ?></th>
			<td><label for="empty">
				<input type="checkbox" id="sc_widget" name="sc_widget" <?php echo ($this->options['sc_widget'] === true) ? "checked='checked'" : ""; ?>/> <?php 
				_e('Creates a widget-area for a shortcode. Use it with [widget] ', $this->localizationDomain); ?></label></td>
		</tr>
		<tr valign="top" style="display: none">
			<th scope="row"><?php _e('Create menu-shortcode', $this->localizationDomain); ?></th>
			<td><label for="empty">
				<input type="checkbox" id="sc_widget" name="sc_widget" <?php echo ($this->options['sc_widget'] === true) ? "checked='checked'" : ""; ?>/> <?php 
				_e('Creates a menu-area for a shortcode. Create a new menu e.g. for a sitemap. Use shortcode with [menu] ', $this->localizationDomain); ?></label></td>
		</tr>
	
		<tr valign="top" style="display: none">
			<th scope="row"><?php _e('Before Markup:', $this->localizationDomain); ?></th>
			<td><input name="before" type="text" id="before" size="40" value="<?php echo stripslashes(htmlspecialchars($this->options['before'])); ?>"/>
			<span class="description"><?php _e('The HTML markup to display before the pagination code.', $this->localizationDomain); ?></span></td>
		</tr>
		<tr valign="top" style="display: none">
			<th scope="row"><?php _e('After Markup:', $this->localizationDomain); ?></th>
			<td><input name="after" type="text" id="after" size="40" value="<?php echo stripslashes(htmlspecialchars($this->options['after'])); ?>"/>
			<span class="description"><?php _e('The HTML markup to display after the pagination code.', $this->localizationDomain); ?></span></td>
		</tr>
		<tr valign="top" style="display: none">
			<th scope="row"><?php _e('WP-Paginate CSS File:', $this->localizationDomain); ?></th>
			<td><label for="css">
				<input type="checkbox" id="css" name="css" <?php echo ($this->options['css'] === true) ? "checked='checked'" : ""; ?>/> <?php printf(__('Include the default stylesheet wp-paginate.css? WP-Paginate will first look for <code>wp-paginate.css</code> in your theme directory (<code>themes/%s</code>).', $this->localizationDomain), get_template()); ?></label></td>
		</tr>
		<tr valign="top" style="display: none">
			<th scope="row"><?php _e('Page Range:', $this->localizationDomain); ?></th>
			<td>
				<select name="range" id="range">
				<?php for ($i=1; $i<=10; $i++) : ?>
					<option value="<?php echo $i; ?>" <?php echo ($i == $this->options['range']) ? "selected='selected'" : ""; ?>><?php echo $i; ?></option>
				<?php endfor; ?>
				</select>
				<span class="description"><?php _e('The number of page links to show before and after the current page. Recommended value: 3', $this->localizationDomain); ?></span></td>
		</tr>
		<tr valign="top" style="display: none">
			<th scope="row"><?php _e('Page Anchors:', $this->localizationDomain); ?></th>
			<td>
				<select name="anchor" id="anchor">
				<?php for ($i=0; $i<=10; $i++) : ?>
					<option value="<?php echo $i; ?>" <?php echo ($i == $this->options['anchor']) ? "selected='selected'" : ""; ?>><?php echo $i; ?></option>
				<?php endfor; ?>
				</select>
				<span class="description"><?php _e('The number of links to always show at beginning and end of pagination. Recommended value: 1', $this->localizationDomain); ?></span></td>
		</tr>
		<tr valign="top" style="display: none">
			<th scope="row"><?php _e('Page Gap:', $this->localizationDomain); ?></th>
			<td>
				<select name="gap" id="gap">
				<?php for ($i=1; $i<=10; $i++) : ?>
					<option value="<?php echo $i; ?>" <?php echo ($i == $this->options['gap']) ? "selected='selected'" : ""; ?>><?php echo $i; ?></option>
				<?php endfor; ?>
				</select>
				<span class="description"><?php _e('The minimum number of pages in a gap before an ellipsis (...) is added. Recommended value: 3', $this->localizationDomain); ?></span></td>
		</tr>
	</table>
	<p class="submit">
		<input type="submit" value="Save Changes" name="wp_test_save" class="button-primary" />
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
		parent::__construct( false, 'My New Widget Title' );
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
if (class_exists('shortcodify')) {
	$shortcodify = new shortcodify();
}
?>

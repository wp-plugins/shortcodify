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
		 * PHP 4 Compatible Constructor
		 */
		function test() {$this->__construct();}

		/**
		 * PHP 5 Constructor
		 */
		function __construct() {
			$name = dirname(plugin_basename(__FILE__));
			$this->localizationDomain = $name;
			//Language Setup
			load_plugin_textdomain($this->localizationDomain, false, $name.'/lang/');

			//"Constants" setup
			$this->pluginurl = plugins_url($name) . "/";
			$this->pluginpath = WP_PLUGIN_DIR . "/$name/";
			
			$this->bookmark_args = array(
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

		private function check_active_functions(){
			$this->activeFunctions = array(
				'wsc',
				'unshortcode',
				'br',
				'hr',
				'date',
				'time',
				'links',
				'random'
			) ;
		}
		
		private function create_Actions(){
			// WP Admin Menu entry
			add_action('admin_menu', array(&$this, 'admin_menu_link'));	
		}
		
		private function create_shortcode(){
			// SHORTCODES
			
			foreach($this->activeFunctions as $name)
			{
				add_shortcode( $name, array(&$this, $name) );	
			}
			
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
		
		//function wrap_shortcode_func($atts, $content= NULL)

		public function __call($name, $argumente) {
			$atts = $argumente[0];
			$content = $argumente[1];
			switch($name){
				case 'hr':
					$rtn = '<hr class="clear" style="clear: both">';
				break;
				case 'date':
					$rtn = '<span class="sc_date">'.date_i18n( get_option('date_format') ).'</span>';
				break;
				case 'time':
					$rtn = '<span class="sc_time">'.date_i18n( get_option('time_format') ).'</span>';
				break;
				case 'br':
					$rtn = '<br class="clear" style="clear: both">';
				break;
				case 'unshortcode':
					$rtn = $content;
				break;
				case 'wsc':
					//return '<span class="wrap_shortcode '.$atts['class'].'">'.apply_filters('the_content', $content).'</span>';
					$rtn = '<span class="wrap_shortcode '.$atts['link'].'">'.do_shortcode($content).'</span>';
				break;
				case 'links':					  
					$html = wp_list_bookmarks( $this->bookmark_args );
					$rtn = '<ul class="shortcodify_links">'.$html.'</ul>';
				break;
				case 'random':
					if( isset( $atts['trennzeichen'] ) ) $trenner = $atts['trennzeichen'];
					else {$trenner = PHP_EOL;}
					
					$content = explode($trenner, $content);
					$rnd = rand(0, count($content) - 1);
					$rtn = '<span class="wrap_shortcode random">'.do_shortcode( $content[ $rnd ] ).'</span>';
				break;
				default:
					$rtn = $content;
			}
			return $rtn;
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
		    register_nav_menus( array(
					'Shortcodify' => __('Shortcodify Menu, for sitemaps')
			) );
		}

		static function getWidgetContent($area = 'Shortcodify'){
			ob_start();
			dynamic_sidebar($area);
			$html = ob_get_contents();
			ob_end_clean();
			
			return $html;			
		}		
		
		function createWidget() {
			$html = $this->getWidgetContent('Shortcodify');
			return '<div class="shortcodify_widget">'.$html.'</div>';
		}	
		
		// menu shortcode output
		function createMenu() {
			ob_start();
			wp_nav_menu( array('menu' => 'Shortcodify' ));
			$html = ob_get_contents();
			ob_end_clean();
			
			return '<div class="shortcodify_menu">'.$html.'</div>';
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
					$this->options['sc_menu'] = (isset($_POST['sc_menu']) && $_POST['sc_menu'] === 'on') ? true : false;
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
	<h5><?php _e('See all possible shortcodes <a href="http://www.arnelorenz.de/shortcodify/">here</a>', $this->localizationDomain); ?></h5>
	<table class="form-table">
	
		<tr valign="top">
			<th scope="row"><?php _e('Create widget-shortcode', $this->localizationDomain); ?></th>
			<td><label for="empty">
				<input type="checkbox" id="sc_widget" name="sc_widget" <?php echo ($this->options['sc_widget'] === true) ? "checked='checked'" : ""; ?>/> <?php 
				_e('Creates a widget-area for a shortcode. Use it with [widget]', $this->localizationDomain); ?></label></td>
		</tr>
		<tr valign="top" style="display: ">
			<th scope="row"><?php _e('Create menu-shortcode', $this->localizationDomain); ?></th>
			<td><label for="empty">
				<input type="checkbox" id="sc_menu" name="sc_menu" <?php echo ($this->options['sc_menu'] === true) ? "checked='checked'" : ""; ?>/> <?php 
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
		<input type="submit" value="<?php _e('Save Changes'); ?>" name="wp_test_save" class="button-primary" />
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

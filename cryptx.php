<?php
/*
Plugin Name: CryptX
Plugin URI: http://weber-nrw.de/wordpress/cryptx/
Description: No more SPAM by spiders scanning you site for email adresses. With CryptX you can hide all your email adresses, with and without a mailto-link, by converting them using javascript or UNICODE. Although you can choose to add a mailto-link to all unlinked email adresses with only one klick at the settings. That's great, isn't it?
Version: 2.6.3
Author: Ralf Weber
Author URI: http://weber-nrw.de/
Donate link: https://www.paypal.com/cgi-bin/webscr?cmd=_s-xclick&hosted_button_id=4026696
*/

/////////////////////////////////////////////////////////////////////////////

/**
* @internal prevent from direct calls
*/
if (!defined('ABSPATH')) {
	return ;
	}

/**
* @internal prevent from second inclusion
*/
if (!class_exists('cryptX')) {

/////////////////////////////////////////////////////////////////////////////
// plugin definitions
define( 'CRYPTX_BASENAME', plugin_basename( __FILE__ ) );
define( 'CRYPTX_BASEFOLDER', plugin_basename( dirname( __FILE__ ) ) );
define( 'CRYPTX_FILENAME', str_replace( CRYPTX_BASEFOLDER.'/', '', plugin_basename(__FILE__) ) );

load_plugin_textdomain('cryptx', sprintf('%s/cryptx/languages', PLUGINDIR ));

$cryptX_var = (array) get_option('cryptX');

/**
* "CryptX" WordPress Plugin
*
* @author Ralf Weber <ralf@weber-nrw.de>
* @license http://opensource.org/licenses/gpl-license.php GNU Public License
*/
Class cryptX {

	// -- -- -- -- -- -- -- -- -- -- -- -- -- -- -- -- -- -- -- -- -- --

	/**
	* Constructor
	*
	* This constructor attaches the needer plugin hook callbacks
	*/
	function cryptX() {

		global $cryptX_var, $wp_version;

		// attach the converstion handlers
		//
		if (@$cryptX_var[theContent]) {
			$this->filter('the_content');
		}
		if (@$cryptX_var[theExcerpt]) {
			$this->filter('the_excerpt');
		}
		if (@$cryptX_var[commentText]) {
			$this->filter('comment_text');
		}
		if (@$cryptX_var[widgetText]) {
			$this->filter('widget_text');
		}

		// attach to admin menu
		//
		if (is_admin()) {
			add_action('admin_menu',
				array(&$this, 'menu')
				);
			}

		// attach to plugin installation
		//
		add_action(
			'activate_' . str_replace(
				DIRECTORY_SEPARATOR, '/',
				str_replace(
					realpath(ABSPATH . PLUGINDIR) . DIRECTORY_SEPARATOR,
						'', __FILE__
					)
				),
			array(&$this, 'install')
			);

		// attach javascript to Header
		//
		if (@$cryptX_var[java]) {
			if (@$cryptX_var[load_java]) {
				add_action(
					'wp_footer',
					array(&$this, 'header'),
					9
					);
			} else {
				add_action(
					'wp_head',
					array(&$this, 'header'),
					9
					);
			}
		}

		if (@$cryptX_var[metaBox]) {
			add_action('admin_menu',
					array(&$this, 'cryptx_meta_box')
					); 
	
			add_action('wp_insert_post',
					array(&$this, 'cryptx_insert_post')
					);
			add_action('wp_update_post',
					array(&$this, 'cryptx_insert_post')
					); 
		}
		
		// Add FAQ and support information
		if ( version_compare( $wp_version, '2.8', '>' ) ) {
			add_filter( 'plugin_row_meta', array($this, 'init_row_meta'), 10, 2 ); // only 2.8 and higher
		} else {
			add_filter( 'plugin_action_links', array($this, 'init_row_meta'), 10, 2 );
		}
		
		// Add tinyurl for image action
		add_filter( 'init', array($this, 'init_tinyurl'));
	} // End function

	// -- -- -- -- -- -- -- -- -- -- -- -- -- -- -- -- -- -- -- -- -- --

	function init_tinyurl() {
		global $cryptX_var;
		// check TinyURl
		$url = $_SERVER["REQUEST_URI"];
		$params = explode( '/', $url );
		if ( count( $params ) > 1 ) {
			$tiny_url = $params[count( $params ) -2];
			if ( $tiny_url == md5( get_bloginfo('url') ) ) {
				// define vars
				$font = $cryptX_var[c2i_font]; 
				$msg = $params[count( $params ) -1];
				$size = $cryptX_var[c2i_fontSize]; 
				$pad = 1;
				$transparent = 1;
				$red = hexdec(substr($cryptX_var[c2i_fontRGB],0,2)); 
				$grn = hexdec(substr($cryptX_var[c2i_fontRGB],2,2));
				$blu = hexdec(substr($cryptX_var[c2i_fontRGB],4,2));
				$bg_red = 255 - $red;
				$bg_grn = 255 - $grn;
				$bg_blu = 255 - $blu;
				$width = 0;
				$height = 0;
				$offset_x = 0;
				$offset_y = 0;
				$bounds = array();
				$image = "";
				// get the font height.
				$bounds = ImageTTFBBox($size, 0, $font, "W");
				if ($rot < 0) 
				{
					$font_height = abs($bounds[7]-$bounds[1]);		
				} 
				else if ($rot > 0) 
				{
					$font_height = abs($bounds[1]-$bounds[7]);
				} 
				else 
				{
					$font_height = abs($bounds[7]-$bounds[1]);
				}
				// determine bounding box.
				$bounds = ImageTTFBBox($size, 0, $font, $msg);
				$width = abs($bounds[4]-$bounds[6]);
				$height = abs($bounds[7]-$bounds[1]);
				$offset_y = $font_height+abs(($height - $font_height)/2)-1;
				$offset_x = 0;
				$image = imagecreatetruecolor($width+($pad*2),$height+($pad*2));
				imagesavealpha($image, true);
				//$background = ImageColorAllocate($image, $bg_red, $bg_grn, $bg_blu);
				$foreground = ImageColorAllocate($image, $red, $grn, $blu);
				$background = imagecolorallocatealpha($image, 0, 0, 0, 127);
				imagefill($image, 0, 0, $background);
				// render the image
				ImageTTFText($image, $size, 0, $offset_x+$pad, $offset_y+$pad, $foreground, $font, $msg);
				// output PNG object.
				Header("Content-type: image/png");
				imagePNG($image);
				die;
			}
		}
	}

	function init_row_meta($links, $file) {
		if (CRYPTX_BASENAME == $file) {
			return array_merge(
				$links,
				array(
					sprintf(
						'<a href="options-general.php?page=%s">%s</a>',
						CRYPTX_BASENAME,
						__('Settings')
					)
				),
				array(
					sprintf(
						'<a href="https://www.paypal.com/cgi-bin/webscr?cmd=_s-xclick&hosted_button_id=4026696">%s</a>',
						__('Donate', 'cryptx')
					)
				)
			);
		}
		return $links;
	}

	function filter($apply)
	{
		global $cryptX_var, $post, $shortcode_tags;

		if (@$cryptX_var[autolink]) {
			add_filter($apply, array(&$this, 'autolink'), 5);
			if (!empty($shortcode_tags) || is_array($shortcode_tags)) {
				add_filter($apply, array(&$this, 'autolink'), 11);
			}		
		}
		add_filter($apply, array($this, 'encryptx'), 12);
		add_filter($apply, array($this, 'linktext'), 13);
	}

	// -- -- -- -- -- -- -- -- -- -- -- -- -- -- -- -- -- -- -- -- -- --

	function _excluded($ID) {
		global $cryptX_var;
		$return = false;
		$exIDs = explode(",", $cryptX_var[excludedIDs]);
		if(in_array($ID, $exIDs) > 0 ) $return = true;
		return $return;
	}
	
	function linktext($content)
	{
		global $post;
		if (!$this->_excluded($post->ID)) {
			$content = preg_replace_callback("/([_a-zA-Z0-9-]+(\.[_a-zA-Z0-9-]+)*@[a-zA-Z0-9-]+(\.[a-zA-Z0-9-]+)*(\.[a-zA-Z]{2,}))/i", array(get_class($this), '_Linktext'), $content );
		}
		return $content;	
	}

	function _linktext($Match)
	{
		global $cryptX_var;
		switch ($cryptX_var[opt_linktext]) {

			case 1: // alternative text for mail link
				$linktext = $cryptX_var[alt_linktext];
				break;

			case 2: // alternative image for mail link
				$linktext = "<img src=\"" . $cryptX_var[alt_linkimage] . "\" class=\"cryptxImage\" alt=\"" . $cryptX_var[alt_linkimage_title] . "\" title=\"" . $cryptX_var[alt_linkimage_title] . "\">";
				break;

			case 3: // uploaded image for mail link
				$imgurl = $cryptX_var[alt_uploadedimage];
				$linktext = "<img src=\"" . $imgurl . "\" class=\"cryptxImage\" alt=\"" . $cryptX_var[http_linkimage_title] . "\" title=\"" . $cryptX_var[http_linkimage_title] . "\">";
				break;

			case 4: // text scrambled by antispambot
				$linktext = antispambot($Match[1]);
				break;

			case 5: // convert to image
				$linktext = "<img src=\"" . get_bloginfo('url') . "/" . md5( get_bloginfo('url') ) . "/" . antispambot($Match[1]) . "\" style=\"vertical-align:text-bottom\" alt=\"" . antispambot($Match[1]) . "\" title=\"" . antispambot($Match[1]) . "\">";
				break;

			default:
				$linktext = str_replace( "@", $cryptX_var[at], $Match[1]);
				$linktext = str_replace( ".", $cryptX_var[dot], $linktext);

		}
		return $linktext;
	}

	// -- -- -- -- -- -- -- -- -- -- -- -- -- -- -- -- -- -- -- -- -- --

	function _dirImages()
	{
		$dir = plugin_dir_path( __FILE__ ).'images'; 
		$fh = opendir($dir);
		$verzeichnisinhalt = array();
		while (true == ($file = readdir($fh)))
		{
			if ((substr(strtolower($file), -3)=="jpg") or (substr(strtolower($file), -3)=="gif")) 
				{
				$verzeichnisinhalt[] = $file;
				}
		}
		return $verzeichnisinhalt;
	}

	function _dirFonts()
	{
		$dir = plugin_dir_path( __FILE__ ).'fonts'; 
		$fh = opendir($dir);
		$verzeichnisinhalt = array();
		while (true == ($file = readdir($fh)))
		{
			if ((substr(strtolower($file), -3)=="ttf") or (substr(strtolower($file), -3)=="ttf")) 
				{
				$verzeichnisinhalt[] = $file;
				}
		}
		return $verzeichnisinhalt;
	}

	// -- -- -- -- -- -- -- -- -- -- -- -- -- -- -- -- -- -- -- -- -- --

	function encryptx($content)
	{
		global $post;
		
		if (!$this->_excluded($post->ID)) {
			$content = preg_replace_callback('/<a (.*?)(href=("|\')mailto:(.*?)("|\')(.*?)|)>(.*?)<\/a>/i', array(get_class($this), 'mailtocrypt'), $content );
		}
		return $content;
	}

	function mailtocrypt($Match)
	{
		global $cryptX_var;
		$return = $Match[0];
		$mailto = "mailto:" . $Match[4];
		//* If mailto contains no email adress, like a link from "Sociable" do nothing *//
		if (substr($Match[4], 0, 9) =="?subject=") return $return;
		if (@$cryptX_var[java]) {
			$crypt = '';
			$ascii = 0;
			for ($i = 0; $i < strlen( $Match[4] ); $i++) {
				$ascii = ord ( substr ( $Match[4], $i ) );
				if (8364 <= $char) {
					$ascii = 128;
				}
				$crypt .= chr($ascii + 1);
			}
			$javascript="javascript:DeCryptX('" . $crypt . "')";
			$return = str_replace( "mailto:".$Match[4], $javascript, $return);
		} else {				
				$return = str_replace( $mailto, antispambot($mailto), $return);
		}	
		return $return;
	}


	// -- -- -- -- -- -- -- -- -- -- -- -- -- -- -- -- -- -- -- -- -- --

	function autolink($content) {
		global $post;
		if ($this->_excluded($post->ID)) return $content;
		$src[]="/([\s])([_a-zA-Z0-9-]+(\.[_a-zA-Z0-9-]+)*@[a-zA-Z0-9-]+(\.[a-zA-Z0-9-]+)*(\.[a-zA-Z]{2,}))/si";
		$src[]="/(>)([_a-zA-Z0-9-]+(\.[_a-zA-Z0-9-]+)*@[a-zA-Z0-9-]+(\.[a-zA-Z0-9-]+)*(\.[a-zA-Z]{2,}))(<)/si";
		$src[]="/(\()([_a-zA-Z0-9-]+(\.[_a-zA-Z0-9-]+)*@[a-zA-Z0-9-]+(\.[a-zA-Z0-9-]+)*(\.[a-zA-Z]{2,}))(\))/si";
		$src[]="/(>)([_a-zA-Z0-9-]+(\.[_a-zA-Z0-9-]+)*@[a-zA-Z0-9-]+(\.[a-zA-Z0-9-]+)*(\.[a-zA-Z]{2,}))([\s])/si";
		$src[]="/([\s])([_a-zA-Z0-9-]+(\.[_a-zA-Z0-9-]+)*@[a-zA-Z0-9-]+(\.[a-zA-Z0-9-]+)*(\.[a-zA-Z]{2,}))(<)/si";
		$src[]="/^([_a-zA-Z0-9-]+(\.[_a-zA-Z0-9-]+)*@[a-zA-Z0-9-]+(\.[a-zA-Z0-9-]+)*(\.[a-zA-Z]{2,}))/si";
		$src[]="/(<a[^>]*>)<a[^>]*>/";
		$src[]="/(<\/A>)<\/A>/i";
		$tar[]="\\1<a href=\"mailto:\\2\">\\2</a>";
		$tar[]="\\1<a href=\"mailto:\\2\">\\2</a>\\6";
		$tar[]="\\1<a href=\"mailto:\\2\">\\2</a>\\6";
		$tar[]="\\1<a href=\"mailto:\\2\">\\2</a>\\6";
		$tar[]="\\1<a href=\"mailto:\\2\">\\2</a>\\6";
		$tar[]="<a href=\"mailto:\\0\">\\0</a>";
		$tar[]="\\1";
		$tar[]="\\1";
		$content = preg_replace($src,$tar,$content);
		return $content;
	}

	// -- -- -- -- -- -- -- -- -- -- -- -- -- -- -- -- -- -- -- -- -- --


	function install() {
		global $cryptX_var, $wpdb;
		$firstImage = $this->_dirImages();
		$firstFont = $this->_dirFonts();
		add_option(
			'cryptX',
				array(
					'at' => ' [at] ',
					'dot' => ' [dot] ',
					'theContent' => 1,
					'theExcerpt' => 0,
					'commentText' => 1,
					'widgetText' => 0,
					'java' => 1,
					'opt_linktext' => 0,
					'autolink' => 1,
					'excludedIDs' => '',
					'metaBox' => 1,
					'alt_uploadedimage' => plugins_url('cryptx/images/').$firstImage[0],
					'c2i_font' => plugin_dir_path( __FILE__ ).'fonts/'.$firstFont[0],
					'c2i_fontSize' => 10,
					'c2i_fontRGB' => '000000',
				)
			);
		$cryptX_var = (array) get_option('cryptX'); // reread Options
		if ($cryptX_var[excludedIDs] == "") {
			$tmp = array();
			$excludes = $wpdb->get_results("SELECT post_id FROM $wpdb->postmeta WHERE meta_key = 'cryptxoff' AND meta_value = 'true'");
			if(count($excludes) > 0) {
				foreach ($excludes as $exclude) {
					$tmp[] = $exclude->post_id;
				}
				sort($tmp);
				$cryptX_var[excludedIDs] = implode(",", $tmp);
				update_option( 'cryptX', $cryptX_var);
				$cryptX_var = (array) get_option('cryptX'); // reread Options			
				$wpdb->query("DELETE FROM $wpdb->postmeta WHERE meta_key = 'cryptxoff'");
			}
		}
		if (empty($cryptX_var[c2i_font])) {
			$cryptX_var[c2i_font] = plugin_dir_path( __FILE__ ).'fonts/'.$firstFont[0];
		}
		if (empty($cryptX_var[c2i_fontSize])) {
			$cryptX_var[c2i_fontSize] = 10;
		}
		if (empty($cryptX_var[c2i_fontRGB])) {
			$cryptX_var[c2i_fontRGB] = '000000';
		}
		update_option( 'cryptX', $cryptX_var);
		$cryptX_var = (array) get_option('cryptX'); // reread Options			
	}

	// -- -- -- -- -- -- -- -- -- -- -- -- -- -- -- -- -- -- -- -- -- --

	/**
	* Attach the menu page to the `Options` tab
	*/
	function header()
	{
		$cryptX_script.= "<script type=\"text/javascript\" src=\"" . site_url() . '/' . PLUGINDIR . '/' . dirname(plugin_basename (__FILE__)) . "/js/cryptx.js\"></script>\n";
		print($cryptX_script);
	}

	// -- -- -- -- -- -- -- -- -- -- -- -- -- -- -- -- -- -- -- -- -- --


	function cryptx_meta() {
		global $post;
		?>
		<input type="checkbox" name="cryptxoff" <?php if ($this->_excluded($post->ID)) { echo 'checked="checked"'; } ?>/> Disable CryptX for this post/page
		<?php
	}
	
	function cryptx_option() {
		global $post;
		if ( current_user_can('edit_posts') ) { ?>
		<fieldset id="cryptxoption" class="dbx-box">
		<h3 class="dbx-handle">CryptX</h3>
		<div class="dbx-content">
			<input type="checkbox" name="cryptxoff" <?php if ($this->_excluded($post->ID)) { echo 'checked="checked"'; } ?>/> Disable CryptX for this post/page
		</div>
		</fieldset>
		<?php 
		}
	}
	
	function cryptx_meta_box() {
		// Check whether the 2.5 function add_meta_box exists, and if it doesn't use 2.3 functions.
		if ( function_exists('add_meta_box') ) {
			add_meta_box('cryptx','CryptX', array(&$this, 'cryptx_meta'),'post');
			add_meta_box('cryptx','CryptX', array(&$this, 'cryptx_meta'),'page');
		} else {
			add_action('dbx_post_sidebar', array(&$this, 'cryptx_option'));
			add_action('dbx_page_sidebar', array(&$this, 'cryptx_option'));
		}
	}
	
	//add_action('admin_menu', 'cryptx_meta_box');
	
	function cryptx_insert_post($pID) {
		global $cryptX_var, $post;
		
		$rev = wp_is_post_revision($pID);
		if($rev) $pID = $rev;
		$b = explode(",", $cryptX_var[excludedIDs]);
		if($b[0] == '') unset($b[0]);
		foreach($b as $x=>$y) {
			if($y == $pID) {
				unset($b[$x]);
				break;
			}
		}
		if (isset($_POST['cryptxoff'])) $b[] = $pID;
		$b = array_unique($b, SORT_NUMERIC);
		sort($b);
		$cryptX_var[excludedIDs] = implode(",", $b);
		update_option( 'cryptX', $cryptX_var);
		$cryptX_var = (array) get_option('cryptX'); // reread Options
	}

	/**
	* Attach the menu page to the `Options` tab
	*/
	function menu() {
		add_options_page(
			'CryptX',
			(version_compare($GLOBALS['wp_version'], '2.6.999', '>') ? '<img src="' .@plugins_url('cryptx/icon.png'). '" width="10" height="10" alt="CryptX Icon" />' : ''). 'CryptX',
			9,
			__FILE__,
			array(
			$this,
			'_submenu'
			)
		);
	}
	

	/**
	* Handles and renders the menu page
	*/
	function _submenu() {
		global $cryptX_var;
		
		if (isset($_POST) && !empty($_POST)) {
			if (function_exists('current_user_can') === true && (current_user_can('manage_options') === false || current_user_can('edit_plugins') === false)) {
				wp_die("You don't have permission to access!");
			}
			check_admin_referer('cryptX');
			update_option( 'cryptX', $_POST['cryptX_var']);
			$cryptX_var = (array) get_option('cryptX'); // reread Options
			?>
			<div id="message" class="updated fade">
				<p><strong><?php _e('Settings saved.') ?></strong></p>
			</div>
		<?php } ?>
		
		<div class="wrap">
		<?php if (version_compare($GLOBALS['wp_version'], '2.6.999', '>')) { ?>
		<div class="icon32" style="background: url(<?php echo @plugins_url('cryptx/icon32.png') ?>) no-repeat"><br /></div>
		<?php } ?>
		<h2>CryptX</h2>
		
		<form method="post" action="">
		
		<?php wp_nonce_field('cryptX') ?>
		
		<div id="poststuff" class="ui-sortable">
		<div class="postbox">
		
		<h3><?php _e("Presentation",'cryptx'); ?></h3>
		
		<div class="inside">
	    <table class="form-table">
			<tr valign="top">
				<td><input name="cryptX_var[opt_linktext]" type="radio" id="opt_linktext" value="0" <?php echo ($cryptX_var[opt_linktext] == 0) ? 'checked="checked"' : ''; ?> /></td>
				<th scope="row"><label for="cryptX_var[at]"><?php _e("Replacement for '@'",'cryptx'); ?></label></th>
				<td><input name="cryptX_var[at]" value="<?php echo $cryptX_var[at]; ?>" type="text" class="regular-text" /></td>
			</tr>
			<tr valign="top">
				<td>&nbsp;</td>
				<th scope="row"><label for="cryptX_var[dot]"><?php _e("Replacement for '.'",'cryptx'); ?></label></th>
				<td><input name="cryptX_var[dot]" value="<?php echo $cryptX_var[dot]; ?>" type="text" class="regular-text" /></td>
			</tr>
        	<tr valign="top">
            	<td scope="row"><input type="radio" name="cryptX_var[opt_linktext]" id="opt_linktext2" value="1" <?php echo ($cryptX_var[opt_linktext] == 1) ? 'checked="checked"' : ''; ?>/></td>
            	<th><label for="cryptX_var[alt_linktext]"><?php _e("Text for link",'cryptx'); ?></label></th>
            	<td><input name="cryptX_var[alt_linktext]" value="<?php echo $cryptX_var[alt_linktext]; ?>" type="text" class="regular-text" /></td>
          	</tr>
          	<tr valign="top">
            	<td scope="row"><input type="radio" name="cryptX_var[opt_linktext]" id="opt_linktext3" value="2" <?php echo ($cryptX_var[opt_linktext] == 2) ? 'checked="checked"' : ''; ?>/></td>
            	<th><label for="cryptX_var[alt_linkimage]"><?php _e("Image-URL",'cryptx'); ?></label></th>
            	<td><input name="cryptX_var[alt_linkimage]" value="<?php echo $cryptX_var[alt_linkimage]; ?>" type="text" class="regular-text" /></td>
          	</tr>
         	<tr valign="top">
            	<td scope="row">&nbsp;</td>
            	<th><label for="cryptX_var[http_linkimage_title]"><?php _e("Title-Tag for the Image",'cryptx'); ?></label></th>
            	<td><input name="cryptX_var[http_linkimage_title]" value="<?php echo $cryptX_var[http_linkimage_title]; ?>" type="text" class="regular-text" /></td>
          	</tr>
          	<tr valign="top">
            	<td scope="row"><input type="radio" name="cryptX_var[opt_linktext]" id="opt_linktext4" value="3" <?php echo ($cryptX_var[opt_linktext] == 3) ? 'checked="checked"' : ''; ?>/></td>
            	<th><label for="cryptX_var[alt_uploadedimage]"><?php _e("Select image from folder",'cryptx'); ?></label></th>
            	<td>            	<select name="cryptX_var[alt_uploadedimage]" onchange="cryptX_bild_wechsel(this)">
				<?php foreach($this->_dirImages() as $image) { 
					?>
					<option value="<?php echo plugins_url('cryptx/images/').$image; ?>" <?php echo ($cryptX_var[alt_uploadedimage] == plugins_url('cryptx/images/').$image) ? 'selected' : ''; ?> ><?php echo $image; ?></option>
				<?php } ?>
				</select>
				<br/><?php _e("the selected image: ",'cryptx'); ?><img src="<?php echo $cryptX_var[alt_uploadedimage]; ?>" id="cryptXmailTo" align="top" style="padding: 3px;"><br/>
				<span class="setting-description"><?php echo sprintf( __("Upload your favorite email-image to '%s'. Only .jpg and .gif Supported!",'cryptx'), plugin_dir_path( __FILE__ ).'images/' ); ?></span></td>
			</tr>
			<tr valign="top">
				<td>&nbsp;</td>
            	<th><label for="cryptX_var[alt_linkimage_title]"><?php _e("Title-Tag for the Image",'cryptx'); ?></label></th>
				<td><input name="cryptX_var[alt_linkimage_title]" value="<?php echo $cryptX_var[alt_linkimage_title]; ?>" type="text" class="regular-text" /></td>
          	</tr>
         	<tr valign="top">
            	<td scope="row"><input type="radio" name="cryptX_var[opt_linktext]" id="opt_linktext4" value="4" <?php echo ($cryptX_var[opt_linktext] == 4) ? 'checked="checked"' : ''; ?>/></td>
            	<th colspan="2"><?php _e("Text scrambled by AntiSpamBot (<small>Try it and look at your site and check the html source!</small>)",'cryptx'); ?></th>
          	</tr>
        	<tr valign="top">
            	<td scope="row"><input type="radio" name="cryptX_var[opt_linktext]" id="opt_linktext5" value="5" <?php echo ($cryptX_var[opt_linktext] == 5) ? 'checked="checked"' : ''; ?>/></td>
            	<th><?php _e("Convert Email to PNG-image",'cryptx'); ?></th>
            	<td><?php _e("Example with the saved options: ",'cryptx'); ?><img src="<?php echo get_bloginfo('url'); ?>/<?php echo md5( get_bloginfo('url') ); ?>/<?php echo antispambot("test@example.com"); ?>" align="absmiddle" alt="<?php echo antispambot("test@example.com"); ?>" title="<?php echo antispambot("test@example.com"); ?>"></td>
          	</tr>
			<tr valign="top">
				<td>&nbsp;</td>
            	<th><label for="cryptX_var[c2i_font]"><?php _e("Choose a Font",'cryptx'); ?></label></th>
            	<td><select name="cryptX_var[c2i_font]">
				<?php foreach($this->_dirFonts() as $font) { ?>
					<option value="<?php echo plugin_dir_path( __FILE__ ).'fonts/'.$font; ?>" <?php echo ($cryptX_var[c2i_font] == plugin_dir_path( __FILE__ ).'fonts/'.$font) ? 'selected' : ''; ?> ><?php echo $font; ?></option>
				<?php } ?>
				</select><br/>
				<span class="setting-description"><?php echo sprintf( __("Upload your favorite font to '%s'. Only .ttf is Supported!",'cryptx'), plugin_dir_path( __FILE__ ).'fonts/' ); ?></span>
				</td>
          	</tr>
			<tr valign="top">
				<td>&nbsp;</td>
            	<th><label for="cryptX_var[c2i_fontSize]"><?php _e("Font size (pixel)",'cryptx'); ?></label></th>
				<td><input name="cryptX_var[c2i_fontSize]" value="<?php echo $cryptX_var[c2i_fontSize]; ?>" type="text" class="regular-text" /></td>
          	</tr>
			<tr valign="top">
				<td>&nbsp;</td>
            	<th><label for="cryptX_var[c2i_fontRGB]"><?php _e("Font color (RGB)",'cryptx'); ?></label></th>
				<td><input name="cryptX_var[c2i_fontRGB]" value="<?php echo $cryptX_var[c2i_fontRGB]; ?>" type="text" class="regular-text" /></td>
          	</tr>
        </table>
		</div>
		</div>

			<p><input type="submit" name="cryptX" class="button-primary" value="<?php _e('Save Changes') ?>" /></p>

		<div class="postbox">
		
		<h3><?php _e("General",'cryptx'); ?></h3>
		
		<div class="inside">
		<table class="form-table">
			<tr valign="top">
				<th scope="row"><?php _e("Apply CryptX to...",'cryptx'); ?></th>
				<td><input name="cryptX_var[theContent]" <?php echo ($cryptX_var[theContent]) ? 'checked="checked"' : ''; ?> type="checkbox" />&nbsp;&nbsp;<?php _e("Content",'cryptx'); ?> <?php _e("(<i>this can be disabled per Post by an Option</i>)",'cryptx'); ?><br/>
				    <input name="cryptX_var[theExcerpt]" <?php echo ($cryptX_var[theExcerpt]) ? 'checked="checked"' : ''; ?> type="checkbox" />&nbsp;&nbsp;<?php _e("Excerpt",'cryptx'); ?><br/>
				    <input name="cryptX_var[commentText]" <?php echo ($cryptX_var[commentText]) ? 'checked="checked"' : ''; ?> type="checkbox" />&nbsp;&nbsp;<?php _e("Comments",'cryptx'); ?><br/>
				    <input name="cryptX_var[widgetText]" <?php echo ($cryptX_var[widgetText]) ? 'checked="checked"' : ''; ?> type="checkbox" />&nbsp;&nbsp;<?php _e("Widgets",'cryptx'); ?> <?php _e("(<i>works only on all widgets, not on a single widget</i>!)",'cryptx'); ?></td>
			</tr>
			<tr valign="top">
				<th scope="row"><?php _e("Excluded ID's...",'cryptx'); ?></th>
				<td><input name="cryptX_var[excludedIDs]" value="<?php echo $cryptX_var[excludedIDs]; ?>" type="text" class="regular-text" />
				<br/><span class="setting-description"><?php _e("Enter all Page/Post ID's to exclude from CryptX as comma seperated list.",'cryptx'); ?></span>
				<br/><input name="cryptX_var[metaBox]" <?php echo ($cryptX_var[metaBox]) ? 'checked="checked"' : ''; ?> type="checkbox" />&nbsp;&nbsp;<?php _e("Enable the CryptX Widget on editing a post or page.",'cryptx'); ?></td>
			</tr>
			<tr valign="top">
				<th scope="row"><?php _e("Type of decryption",'cryptx'); ?></th>
				<td><input name="cryptX_var[java]" <?php echo ($cryptX_var[java]) ? 'checked="checked"' : ''; ?> type="radio" value="1" />&nbsp;&nbsp;<?php _e("Use javascript to hide the Email-Link.",'cryptx'); ?><br/>
				    <input name="cryptX_var[java]" <?php echo (!$cryptX_var[java]) ? 'checked="checked"' : ''; ?> type="radio" value="0" />&nbsp;&nbsp;<?php _e("Use Unicode to hide the Email-Link.",'cryptx'); ?></td>
			</tr>
			<tr valign="top">
				<th scope="row"><?php _e("Where to load the needed javascript...",'cryptx'); ?></th>
				<td><input name="cryptX_var[load_java]" <?php echo (!$cryptX_var[load_java]) ? 'checked="checked"' : ''; ?> type="radio" value="0" />&nbsp;&nbsp;<?php _e("Load the javascript in the <b>header</b> of the page.",'cryptx'); ?><br/>
				    <input name="cryptX_var[load_java]" <?php echo ($cryptX_var[load_java]) ? 'checked="checked"' : ''; ?> type="radio" value="1" />&nbsp;&nbsp;<?php _e("Load the javascript in the <b>footer</b> of the page.",'cryptx'); ?></td>
			</tr>
			<tr valign="top">
				<th scope="row" colspan="2"><input name="cryptX_var[autolink]" <?php echo ($cryptX_var[autolink]) ? 'checked="checked"' : ''; ?> type="checkbox" />&nbsp;&nbsp;<?php _e("Add mailto to all unlinked email addresses",'cryptx'); ?></th>
			</tr>
		</table>

		</div>
		</div>
			<p><input type="submit" name="cryptX" class="button-primary" value="<?php _e('Save Changes') ?>" /></p>

		<div class="postbox">
		
		<h3><?php _e("How to use CryptX in your Template",'cryptx'); ?></h3>
		
		<div class="inside">
		<table class="form-table">
			<tr>
				<td>In your Template you can use the following function to encrypt a email address:
				<p style="border:1px solid #000; background-color: #e9e9e9;padding: 10px;">
				<i>
				&lt;?php <br/>
				&nbsp;&nbsp;&nbsp;&nbsp;    $mail="name@example.com"; <br/>
				&nbsp;&nbsp;&nbsp;&nbsp;    $text="Contact"; <br/>
				&nbsp;&nbsp;&nbsp;&nbsp;    $css ="email"; <br/>
				&nbsp;&nbsp;&nbsp;&nbsp;    if (function_exists('cryptx')) { <br/>
				&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;        cryptx($mail, $text, $css, 1); <br/>
				&nbsp;&nbsp;&nbsp;&nbsp;    } else { <br/>
				&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;        echo sprintf('&lt;a href="mailto:%s" class="%s"&gt;%s&lt;/a&gt;', $mail, $css, ($text != "" ? $text : $mail)); <br/>
				&nbsp;&nbsp;&nbsp;&nbsp;    } <br/>
				?&gt;
				</i></p>
				<ol><li>parameter is the email address.</li>
				<li>parameter is the linktext. If none given the email address is used.</li>
				<li>parameter is a css class added to the link.</li>
				<li>parameter is 1 for echo the encrypted email address or 0 to return the redult to a variable.</li></ol></td>
			</tr>
		</table>

		</div>
		</div>

		<div class="postbox">
		
		<h3><?php _e("Information",'cryptx'); ?></h3>
		
		<div class="inside">
		<table class="form-table">
			<tr>
				<td><?php
				$data = get_plugin_data(__FILE__);
				echo sprintf(
					'%1$s: %2$s | %3$s: %4$s | %5$s: <a href="http://weber-nrw.de" target="_blank">Ralf Weber</a> | <a href="http://twitter.com/Weber_NRW" target="_blank">%6$s</a> | <a href="https://www.paypal.com/cgi-bin/webscr?cmd=_s-xclick&hosted_button_id=4026696">%7$s</a><br />',
					__('Plugin'),
					'CryptX',
					__('Version'),
					$data['Version'],
					__('Author'),
					__('Follow on Twitter', 'cryptx'),
					__('Donate', 'cryptx')
				);
				?>
				Please support me by translating CryptX into other languages. You can download the cryptx.pot file from my <a href="http://weber-nrw.de/wordpress/cryptx/downloads/">site</a> and mail me the zipped language files. Thanks for it.
				</td>
			</tr>
		</table>

		</div>
		</div>

		</div>
		
		</form>
	
		<script type="text/javascript">
		<!--
		function cryptX_bild_wechsel(select){ 
		 document.getElementById("cryptXmailTo").src = select.options[select.options.selectedIndex].value; 
		 return true; 
		 }		//-->
		</script>

		</div>
<?php
		}

	// -- -- -- -- -- -- -- -- -- -- -- -- -- -- -- -- -- -- -- -- -- --


	//--end-of-class
	}

}

/////////////////////////////////////////////////////////////////////////////

/**
* Initiating the plugin...
* @see cryptX
*/
new cryptX;

/**
* Create Template functions...
* $content = string to convert
* $text    = string to replace linktext
* $css     = assign a css class to the link
* $echo    = 0: keep result in a variable, 1: show result
*/
function cryptx( $content, $text="", $css="", $echo=1 )
{
	global $cryptX_var;
	$cryptX = new cryptX;
	$content = $cryptX->autolink( $content );
	$content = $cryptX->encryptx( $content );
	if("" != $text) {
		$content = preg_replace( "/(<a[^>]*>)(.*)(<\/a>)/i", '$1'.$text.'$3', $content );
	}
	if("" != $css) {
		$content = preg_replace( "/(<a[^>]*)(>)/i", '$1 class="'.$css.'"$2', $content );
	}
	if(1 == $echo) echo $content;
	return $content;
}
?>
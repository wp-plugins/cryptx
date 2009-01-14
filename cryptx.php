<?php
/*
Plugin Name: CryptX
Plugin URI: http://weber-nrw.de/wordpress/cryptx/
Description: No more SPAM by spiders scanning you site for email adresses. With CryptX you can hide all your email adresses, with and without a mailto-link, by converting them using javascript or UNICODE. Although you can choose to add a mailto-link to all unlinked email adresses with only one klick at the settings. That's great, isn't it?
Version: 2.1
Author: Ralf Weber
Author URI: http://weber-nrw.de/
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

load_plugin_textdomain('cryptx', PLUGINDIR . '/' . dirname(plugin_basename (__FILE__)) . '/languages');

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

		global $cryptX_var;

		// attach the converstion handlers
		//
		if (@$cryptX_var[theContent]) {
			$this->_filter('the_content');
		}
		if (@$cryptX_var[theExcerpt]) {
			$this->_filter('the_excerpt');
		}
		if (@$cryptX_var[commentText]) {
			$this->_filter('comment_text');
		}

		// attach to admin menu
		//
		if (is_admin()) {
			add_action('admin_menu',
				array(&$this, '_menu')
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
			array(&$this, '_install')
			);

		// attach javascript to Header
		//
		if (@$cryptX_var[java]) {
			add_action(
				'wp_head',
				array(&$this, '_header')
				);
			}

		} // End function

	// -- -- -- -- -- -- -- -- -- -- -- -- -- -- -- -- -- -- -- -- -- --

	function _filter($apply)
	{
		global $cryptX_var;

		if (@$cryptX_var[autolink]) {
			add_filter($apply,
			array(&$this, '_autolink'),
			9);
		}
		add_filter( $apply,
					array(&$this, '_findMatches'),
					11);
	}

	// -- -- -- -- -- -- -- -- -- -- -- -- -- -- -- -- -- -- -- -- -- --

	function _linktext($txt)
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
				$imgurl = "/" . PLUGINDIR . "/" . dirname(plugin_basename (__FILE__)) . "/images/" . $cryptX_var[alt_uploadedimage];
				$linktext = "<img src=\"" . $imgurl . "\" class=\"cryptxImage\" alt=\"" . $cryptX_var[http_linkimage_title] . "\" title=\"" . $cryptX_var[http_linkimage_title] . "\">";
				break;

			case 4: // text scrambled by antispambot
				$linktext = antispambot($txt);
				break;

			default:
				$linktext = str_replace( "@", $cryptX_var[at], $txt);
				$linktext = str_replace( ".", $cryptX_var[dot], $linktext);

		}

		return $linktext;
	}

	// -- -- -- -- -- -- -- -- -- -- -- -- -- -- -- -- -- -- -- -- -- --

	function _dirImages()
	{
		$dir = $_SERVER["DOCUMENT_ROOT"].'/'.PLUGINDIR.'/'.dirname(plugin_basename (__FILE__)).'/images';
		$fh = opendir($dir); //Verzeichnis
		$verzeichnisinhalt = array();
		while (true == ($file = readdir($fh)))
		{
			if ((substr(strtolower($file), -3)=="jpg") or (substr(strtolower($file), -3)=="gif")) //Abfrage nach g�ltigen Datenformat
				{
				$verzeichnisinhalt[] = $file;
				}
		}
		return $verzeichnisinhalt;
	}

	// -- -- -- -- -- -- -- -- -- -- -- -- -- -- -- -- -- -- -- -- -- --

	function _findMatches($content)
	{
		global $cryptX_var;

		$pattern = '/<a (.*?)(href=("|\')(.*?)("|\')(.*?)|)>(.*?)<\/a>/i'; // Thx to Michael Woehrer (http://sw-guide.de) for this pattern out of his plugin 'Link Indication'
		$result = preg_replace_callback($pattern,array(get_class($this), '_encrypt'),$content);

		return $result;
		
	}
	
	function _encrypt($matches)
	{
		global $cryptX_var;

		if(preg_match('/mailto:(.*)/', $matches[4], $mail)) { 
		
			if (@$cryptX_var[java]) {
	
					$crypt = '';
					$ascii = 0;
					for ($i = 0; $i < strlen( $mail[1] ); $i++) { 
						$ascii = ord ( substr ( $mail[1], $i ) ); 
						if (8364 <= $char) {
							$ascii = 128;
						}
						$crypt .= chr($ascii + 1);
					}
					$matches[4] = "javascript:DeCryptX('" . $crypt . "')";
	
			} else {
	
					$matches[4] = antispambot($matches[4]);
	
			}
		}
	
		$matches[7] = $this->_linktext($matches[7]); 
		$others = $matches[1] . ' ' . $matches[6];
		$others = eregi_replace('[[:space:]]+', ' ', $others);
		$others = trim($others);	

		return '<a href="' . $matches[4] . '" ' . $others . '>' . $matches[7] . '</a>';
	}

	// -- -- -- -- -- -- -- -- -- -- -- -- -- -- -- -- -- -- -- -- -- --

	function _autolink($content) {

		$src[]="/([\s])([_a-zA-Z0-9-]+(\.[_a-zA-Z0-9-]+)*@[a-zA-Z0-9-]+(\.[a-zA-Z0-9-]+)*(\.[a-zA-Z]{2,}))/si";
		$src[]="/(>)([_a-zA-Z0-9-]+(\.[_a-zA-Z0-9-]+)*@[a-zA-Z0-9-]+(\.[a-zA-Z0-9-]+)*(\.[a-zA-Z]{2,}))(<)/si";
		$src[]="/^([_a-zA-Z0-9-]+(\.[_a-zA-Z0-9-]+)*@[a-zA-Z0-9-]+(\.[a-zA-Z0-9-]+)*(\.[a-zA-Z]{2,}))/si";
		$src[]="/(<a[^>]*>)<a[^>]*>/";
		$src[]="/(<\/A>)<\/A>/i";

		$tar[]="\\1<a href=\"mailto:\\2\">\\2</a>";
		$tar[]="\\1<a href=\"mailto:\\2\">\\2</a>\\6";
		$tar[]="<a href=\"mailto:\\0\">\\0</a>";
		$tar[]="\\1";
		$tar[]="\\1";

		$content = preg_replace($src,$tar,$content);

		return $content;
	}

	// -- -- -- -- -- -- -- -- -- -- -- -- -- -- -- -- -- -- -- -- -- --


	function _install() {
		add_option(
			'cryptX',
				array(
					'at' => ' [at] ',
					'dot' => ' [dot] ',
					'theContent' => 1,
					'theExcerpt' => 0,
					'commentText' => 1,
					'java' => 1,
					'opt_linktext' => 0,
				)
			);
		}

	// -- -- -- -- -- -- -- -- -- -- -- -- -- -- -- -- -- -- -- -- -- --

	/**
	* Attach the menu page to the `Options` tab
	*/
	function _header()
	{
		$cryptX_script.= "<script type=\"text/javascript\" src=\"" . get_option('siteurl') . '/' . PLUGINDIR . '/' . dirname(plugin_basename (__FILE__)) . "/js/cryptx.js\"></script>\n";
		print($cryptX_script);
	}

	// -- -- -- -- -- -- -- -- -- -- -- -- -- -- -- -- -- -- -- -- -- --

	/**
	* Attach the menu page to the `Options` tab
	*/
	function _menu() {
		add_options_page(
			'CryptX',
			(version_compare($GLOBALS['wp_version'], '2.6.999', '>') ? '<img src="' .@plugins_url('cryptx/icon.png'). '" width="12" height="10" alt="CryptX Icon" />' : ''). 'CryptX',
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
		<div id="wp_seo_about_wpseo" class="postbox">
		
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
            	<td><select name="cryptX_var[alt_uploadedimage]" onchange="cryptX_bild_wechsel(this)">
				<?php foreach($this->_dirImages() as $image) { 
					$FirstIMG = (!isset($FirstIMG))? plugins_url('cryptx/images/').$image : ($cryptX_var[alt_uploadedimage] == plugins_url('cryptx/images/').$image) ? plugins_url('cryptx/images/').$image : $FirstIMG;
					?>
					<option value="<?php echo plugins_url('cryptx/images/').$image; ?>" <?php echo ($cryptX_var[alt_uploadedimage] == plugins_url('cryptx/images/').$image) ? 'selected' : ''; ?> ><?php echo $image; ?></option>
				<?php } ?>
				</select>&nbsp;&nbsp;<img src="<?php echo $FirstIMG; ?>" id="cryptXmailTo"></td>
			</tr>
			<tr valign="top">
				<td>&nbsp;</td>
            	<th><label for="cryptX_var[alt_linkimage_title]"><?php _e("Title-Tag for the Image",'cryptx'); ?></label></th>
				<td><input name="cryptX_var[alt_linkimage_title]" value="<?php echo $cryptX_var[alt_linkimage_title]; ?>" type="text" class="regular-text" />
            	<span class="setting-description"><?php _e("Upload your favorite email-image to ../plugins/cryptx/images. Only .jpg and .gif Supported!",'cryptx'); ?></span></td>
          	</tr>
         	<tr valign="top">
            	<td scope="row"><input type="radio" name="cryptX_var[opt_linktext]" id="opt_linktext" value="4" <?php echo ($cryptX_var[opt_linktext] == 4) ? 'checked="checked"' : ''; ?>/></td>
            	<th colspan="2"><?php _e("Text scrambled by AntiSpamBot (<small>Try it and look at your site and check the html source!</small>)",'cryptx'); ?></th>
          	</tr>
        </table>
		</div>
		</div>

		<div id="wp_seo_about_wpseo" class="postbox">
		
		<h3><?php _e("General",'cryptx'); ?></h3>
		
		<div class="inside">
		<table class="form-table">
			<tr valign="top">
				<th scope="row"><?php _e("Apply CryptX to...",'cryptx'); ?></th>
				<td><input name="cryptX_var[theContent]" <?php echo ($cryptX_var[theContent]) ? 'checked="checked"' : ''; ?> type="checkbox" />&nbsp;&nbsp;<?php _e("Content",'cryptx'); ?><br/>
				    <input name="cryptX_var[theExcerpt]" <?php echo ($cryptX_var[theExcerpt]) ? 'checked="checked"' : ''; ?> type="checkbox" />&nbsp;&nbsp;<?php _e("Excerpt",'cryptx'); ?><br/>
				    <input name="cryptX_var[commentText]" <?php echo ($cryptX_var[commentText]) ? 'checked="checked"' : ''; ?> type="checkbox" />&nbsp;&nbsp;<?php _e("Comments",'cryptx'); ?></td>
			</tr>
			<tr valign="top">
				<th scope="row"><?php _e("Type of decryption",'cryptx'); ?></th>
				<td><input name="cryptX_var[java]" <?php echo ($cryptX_var[java]) ? 'checked="checked"' : ''; ?> type="radio" value="1" />&nbsp;&nbsp;<?php _e("Use javascript to hide the Email-Link.",'cryptx'); ?><br/>
				    <input name="cryptX_var[java]" <?php echo (!$cryptX_var[java]) ? 'checked="checked"' : ''; ?> type="radio" value="0" />&nbsp;&nbsp;<?php _e("Use Unicode to hide the Email-Link.",'cryptx'); ?></td>
			</tr>
			<tr valign="top">
				<th scope="row" colspan="2"><input name="cryptX_var[autolink]" <?php echo ($cryptX_var[autolink]) ? 'checked="checked"' : ''; ?> type="checkbox" />&nbsp;&nbsp;<?php _e("Add mailto to all unlinked email addresses",'cryptx'); ?></th>
			</tr>
		</table>

		</div>
		</div>
			<p><input type="submit" name="cryptX" class="button-primary" value="<?php _e('Save Changes') ?>" /></p>
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
* $echo    = 0: keep result in a variable, 1: show result
*/
function cryptx( $content, $text="", $css="", $echo=1 )
{
	global $cryptX_var;

	$cryptX = new cryptX;

	$content = $cryptX->_autolink( $content );

	if(@$cryptX_var[java]) {
		$content = $cryptX->_encrypt( $content );
	} else {
		$content = $cryptX->_unicode( $content );
	}

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

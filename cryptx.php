<?php
/*
Plugin Name: CryptX
Plugin URI: http://weber-nrw.de/wordpress/cryptx/
Description: No more SPAM by spiders scanning you site for email adresses. With CryptX you can hide all your email adresses, with and without a mailto-link, by converting them using javascript or UNICODE. Although you can choose to add a mailto-link to all unlinked email adresses with only one klick at the settings. That's great, isn't it?
Version: 1.8
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
			array(&$this, '_autolink'));
		}
		if (@$cryptX_var[java]) {
			add_filter($apply,
				array(&$this, '_encrypt'));
		} else {
			add_filter($apply,
				array(&$this, '_unicode'));
		}

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
				$linktext = "<img src=\"" . $imgurl . "\" class=\"cryptxImage\" alt=\"" . $cryptX_var[alt_uploadedimage] . "\">";
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
			if ((substr(strtolower($file), -3)=="jpg") or (substr(strtolower($file), -3)=="gif")) //Abfrage nach gültigen Datenformat
				{
				$verzeichnisinhalt[] = $file;
				}
		}
		return $verzeichnisinhalt;
	}

	// -- -- -- -- -- -- -- -- -- -- -- -- -- -- -- -- -- -- -- -- -- --

	function _encrypt($content)
	{
		global $cryptX_var;

		preg_match_all('/<a[^>]*href=["|\'](.*)["|\'].*>(.*)<\/a>/iUs', $content, $links, PREG_SET_ORDER);

		foreach( $links as $link ) {

			if(preg_match('/mailto:(.*)/', $link[1], $mail)) {
				$crypt = '';
				$ascii = 0;
				for ($i = 0; $i < strlen( $mail[1] ); $i++) {
					$ascii = ord ( substr ( $mail[1], $i ) );
					if (8364 <= $char) {
						$ascii = 128;
					}
					$crypt .= chr($ascii + 1);
				}
				$temp = str_replace( $link[1], "javascript:DeCryptX('" . $crypt . "')", $link[0]);
				$temp = str_replace( $link[2], $this->_linktext($link[2]), $temp);
				$content = str_replace( $link[0], $temp, $content);
			}

		} // foreach


		return $content;
	}

	// -- -- -- -- -- -- -- -- -- -- -- -- -- -- -- -- -- -- -- -- -- --

	function _unicode($content)
	{
		global $cryptX_var;

		preg_match_all('/<a[^>]*href=["|\'](.*)["|\'].*>(.*)<\/a>/iUs', $content, $links, PREG_SET_ORDER);

		foreach( $links as $link ) {

			if(preg_match('/mailto:(.*)/', $link[1], $mail)) {
				$mailto = "mailto:" . $mail[1];
/*
				$crypt = '';
				for ($i = 0; $i < strlen( $mailto ); $i++) {
					$crypt .= "&#" . ord ( substr ( $mailto, $i ) ) . ";";
				}
				$content = str_replace( $mailto, $crypt, $content);
*/
				$content = str_replace( $mailto, antispambot($mailto), $content);
				$content = str_replace( $link[2], $this->_linktext($link[2]), $content);
			}

		} // foreach
		return $content;
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
		add_submenu_page('options-general.php',
			 'CryptX',
			 'CryptX', 9,
			 __FILE__,
			 array($this, '_submenu')
			);
		}

	/**
	* Handles and renders the menu page
	*/
	function _submenu() {
		global $cryptX_var;

		// sanitize referrer
		//
		$_SERVER['HTTP_REFERER'] = preg_replace(
			'~&saved=.*$~Uis','', $_SERVER['HTTP_REFERER']
			);

		// information updated ?
		//
		if ($_POST['submit']) {

			// save
			//
			update_option(
				'cryptX',
				$_POST['cryptX_var']
				);

			die("<script>document.location.href = '{$_SERVER['HTTP_REFERER']}&saved=settings:" . time() . "';</script>");
			}

		// operation report detected
		//
		if (@$_GET['saved']) {

			list($saved, $ts) = explode(':', $_GET['saved']);
			if (time() - $ts < 10) {
				echo '<div id="message" class="updated fade"><p><strong>';

				switch ($saved) {
					case 'settings' :
						echo 'Settings saved.';
						break;
					}

				echo '</strong></p></div>';
				}
			}

		// read the settings
		//
		//$cryptX = (array) get_option('cryptX');

?>
<!-- Start Optionen im Adminbereich (xhtml, außerhalb PHP) -->
<div class="wrap">
	<h2><?php _e("CryptX Options...",'cryptx'); ?></h2>
	<form method="post">
	  <blockquote>
	    <fieldset>
	    <legend><?php _e("Presentation",'cryptx'); ?></legend>
	    <table>
          <tr>
            <td><input name="cryptX_var[opt_linktext]" type="radio" id="opt_linktext" value="0" <?php echo ($cryptX_var[opt_linktext] == 0) ? 'checked="checked"' : ''; ?> />&nbsp;&nbsp;</td>
            <td nowrap><?php _e("Replacement for '@'",'cryptx'); ?>&nbsp;&nbsp;</td>
			<td ><input name="cryptX_var[at]" value="<?php echo $cryptX_var[at]; ?>" type="text" /></td>
          </tr>
           <tr>
            <td>&nbsp;</td>
            <td nowrap><?php _e("Replacement for '.'",'cryptx'); ?>&nbsp;&nbsp;</td>
			<td><input name="cryptX_var[dot]" value="<?php echo $cryptX_var[dot]; ?>" type="text" /></td>
          </tr>
         <tr>
            <td><input type="radio" name="cryptX_var[opt_linktext]" id="opt_linktext2" value="1" <?php echo ($cryptX_var[opt_linktext] == 1) ? 'checked="checked"' : ''; ?>/></td>
            <td nowrap><?php _e("Text for link",'cryptx'); ?>&nbsp;&nbsp;</td>
			<td><input name="cryptX_var[alt_linktext]" value="<?php echo $cryptX_var[alt_linktext]; ?>" type="text" /></td>
          </tr>
          <tr>
            <td><input type="radio" name="cryptX_var[opt_linktext]" id="opt_linktext3" value="2" <?php echo ($cryptX_var[opt_linktext] == 2) ? 'checked="checked"' : ''; ?>/></td>
            <td nowrap><?php _e("Image-URL",'cryptx'); ?>&nbsp;&nbsp;</td>
			<td><input name="cryptX_var[alt_linkimage]" value="<?php echo $cryptX_var[alt_linkimage]; ?>" type="text" /></td>
          </tr>
          <tr>
            <td><input type="radio" name="cryptX_var[opt_linktext]" id="opt_linktext4" value="3" <?php echo ($cryptX_var[opt_linktext] == 3) ? 'checked="checked"' : ''; ?>/></td>
            <td nowrap><?php _e("Select image from folder",'cryptx'); ?>&nbsp;&nbsp;</td>
			<td><select name="cryptX_var[alt_uploadedimage]">
			<?php foreach($this->_dirImages() as $image) { ?>
				<option <?php echo ($cryptX_var[alt_uploadedimage] == $image) ? 'selected' : ''; ?> ><?php echo $image; ?></option>
			<?php } ?>
			</select></td>
          </tr>
         <tr>
            <td>&nbsp;</td>
            <td nowrap><?php _e("Title-Tag for the Image",'cryptx'); ?>&nbsp;&nbsp;</td>
			<td><input name="cryptX_var[alt_linkimage_title]" value="<?php echo $cryptX_var[alt_linkimage_title]; ?>" type="text" /></td>
          </tr>
          <tr>
            <td>&nbsp;</td>
            <td nowrap colspan="2"><?php _e("Upload your favorite email-image to ../plugins/cryptx/images. Only .jpg and .gif Supported!",'cryptx'); ?></td>
          </tr>
         <tr>
            <td><input type="radio" name="cryptX_var[opt_linktext]" id="opt_linktext2" value="4" <?php echo ($cryptX_var[opt_linktext] == 4) ? 'checked="checked"' : ''; ?>/></td>
            <td nowrap colspan="2"><?php _e("Text scrambled by AntiSpamBot (<small>Try it and look at your site and check the html source!</small>)",'cryptx'); ?>&nbsp;&nbsp;</td>
          </tr>
        </table>
	    </fieldset><br />
	    <fieldset>
	    <legend><?php _e("General",'cryptx'); ?></legend>
		<table>
			<tr>
				<td valign="top"><input name="cryptX_var[theContent]" <?php echo ($cryptX_var[theContent]) ? 'checked="checked"' : ''; ?> type="checkbox" />&nbsp;&nbsp;</td>
				<td nowrap><?php _e("Apply CryptX to the Content",'cryptx'); ?></td>
			</tr>
			<tr>
				<td valign="top"><input name="cryptX_var[theExcerpt]" <?php echo ($cryptX_var[theExcerpt]) ? 'checked="checked"' : ''; ?> type="checkbox" /></td>
				<td nowrap><?php _e("Apply CryptX to the Excerpt",'cryptx'); ?></td>
			</tr>
			<tr>
				<td valign="top"><input name="cryptX_var[commentText]" <?php echo ($cryptX_var[commentText]) ? 'checked="checked"' : ''; ?> type="checkbox" /></td>
				<td nowrap><?php _e("Apply CryptX to the Comments",'cryptx'); ?></td>
			</tr>
		</table><br />
		<table>
			<tr>
				<td valign="top">
					<input name="cryptX_var[java]" <?php echo ($cryptX_var[java]) ? 'checked="checked"' : ''; ?> type="radio" value="1" /></td>
				<td nowrap><?php _e("Use javascript to hide the Email-Link.",'cryptx'); ?></td>
			</tr>
			<tr>
				<td valign="top"><input name="cryptX_var[java]" <?php echo (!$cryptX_var[java]) ? 'checked="checked"' : ''; ?> type="radio" value="0" /></td>
				 <td nowrap><?php _e("Use Unicode to hide the Email-Link.",'cryptx'); ?>
				</td>
			</tr>
		</table><br />
		<table>
			<tr>
				<td valign="top"><input name="cryptX_var[autolink]" <?php echo ($cryptX_var[autolink]) ? 'checked="checked"' : ''; ?> type="checkbox" /></td>
				 <td nowrap><?php _e("Add mailto to all unlinked email addresses",'cryptx'); ?></td>
			</tr>
		</table>
	    </fieldset>
	    <input type="submit" name="submit" value="<?php _e("Update &raquo;",'cryptx'); ?>" />
	    </blockquote>
	</form>
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

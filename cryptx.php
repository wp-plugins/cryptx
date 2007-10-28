<?php
/*
Plugin Name: CryptX
Plugin URI: http://weber-nrw/wordpress/cryptx/
Description: No more SPAM by spiders scanning you site for email adresses. With CryptX you can hide all your email adresses, with and without a mailto-link, by converting them using javascript or UNICODE. Although you can choose to add a mailto-link to all unlinked email adresses with only one klick at the settings. That's great, isn't it?
<<<<<<< .mine
Version: 1.4
=======
Version: 1.2
>>>>>>> .r23456
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

load_plugin_textdomain('cryptx', PLUGINDIR . '/' . dirname(plugin_basename (__FILE__)) );

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

	/**
	* Wrap _filter
	* @param string $content
	* @return string
	*/
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

	/**
	* Wrap _encrypt
	* @param string $content
	* @return string
	*/
	function _encrypt($content)
	{
		global $cryptX_var;

		preg_match_all('/<a[^>]*href=["|\'](.*)["|\']*>(.*)<\/a>/iUs', $content, $links, PREG_SET_ORDER);

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
				$new_mail = str_replace( "@", $cryptX_var[at], $link[2]);
				$new_mail = str_replace( ".", $cryptX_var[dot], $new_mail);
				$temp = str_replace( $link[2], $new_mail, $temp);
				$content = str_replace( $link[0], $temp, $content);
			}

		} // foreach


		return $content;
	}

	// -- -- -- -- -- -- -- -- -- -- -- -- -- -- -- -- -- -- -- -- -- --

	/**
	* Wrap _unicode
	* @param string $content
	* @return string
	*/
	function _unicode($content)
	{
		global $cryptX_var;

		preg_match_all('/<a[^>]*href=["|\'](.*)["|\']*>(.*)<\/a>/iUs', $content, $links, PREG_SET_ORDER);

		foreach( $links as $link ) {

			if(preg_match('/mailto:(.*)/', $link[1], $mail)) {
				$mailto = "mailto:" . $mail[1];
				$crypt = '';
				for ($i = 0; $i < strlen( $mailto ); $i++) {
					$crypt .= "&#" . ord ( substr ( $mailto, $i ) ) . ";";
				}
				$content = str_replace( $mailto, $crypt, $content);
				$new_mail = str_replace( "@", $cryptX_var[at], $link[2]);
				$new_mail = str_replace( ".", $cryptX_var[dot], $new_mail);
				$content = str_replace( $link[2], $new_mail, $content);
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

	/**
	* Performs the routines required at plugin installation:
	* in general introducing the settings array
	*/
	function _install() {
		add_option(
			'cryptX',
				array(
					'at' => ' [at] ',
					'dot' => ' [dot] ',
					'theContent' => 1,
					'theExcerpt' => 0,
					'java' => 1,
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
			<table>
				<tr>
					<td valign="top"><?php _e("Replacement for '@'",'cryptx'); ?>
						<input name="cryptX_var[at]" value="<?php echo $cryptX_var[at]; ?>" type="text" /></td>
				</tr>
				<tr>
					<td valign="top"><?php _e("Replacement for '.'",'cryptx'); ?>
						<input name="cryptX_var[dot]" value="<?php echo $cryptX_var[dot]; ?>" type="text" /></td>
				</tr>
				<tr>
					<td valign="top"><input name="cryptX_var[theContent]" <?php echo ($cryptX_var[theContent]) ? 'checked="checked"' : ''; ?> type="checkbox" />
						<?php _e("Apply CryptX to the Content",'cryptx'); ?></td>
				</tr>
				<tr>
					<td valign="top"><input name="cryptX_var[theExcerpt]" <?php echo ($cryptX_var[theExcerpt]) ? 'checked="checked"' : ''; ?> type="checkbox" />
						<?php _e("Apply CryptX to the Excerpt",'cryptx'); ?></td>
				</tr>
				<tr>
					<td valign="top">
						<input name="cryptX_var[java]" <?php echo ($cryptX_var[java]) ? 'checked="checked"' : ''; ?> type="radio" value="1" />
						<?php _e("Use javascript to hide the Email-Link.",'cryptx'); ?><br />
						<input name="cryptX_var[java]" <?php echo (!$cryptX_var[java]) ? 'checked="checked"' : ''; ?> type="radio" value="0" />
						<?php _e("Use Unicode to hide the Email-Link.",'cryptx'); ?>
					</td>
				</tr>
				<tr>
					<td valign="top"><input name="cryptX_var[autolink]" <?php echo ($cryptX_var[autolink]) ? 'checked="checked"' : ''; ?> type="checkbox" />
						<?php _e("Add mailto to all unlinked email addresses",'cryptx'); ?></td>
				</tr>
				<tr>
					<td><input type="submit" name="submit" value="Update &raquo;" /></td>
				</tr>
			</table>
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

?>

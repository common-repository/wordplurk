<?php
/*
Plugin Name: WordPlurk
Plugin URI: http://blog.bluefur.com/wordplurk
Description: Generates Plurk Updates when a new Post is Published.
Author: <a href="http://blog.bluefur.com/">Gary Jones</a> and <a href="http://mattsblog.ca/">Matt Freedman</a>
Version: 1.0.2
*/

add_action('transition_post_status', 'wordplurk_post_now_published', 10, 3);

function plurk_update_status($username, $password, $new_status)
{

        $curl = curl_init();
        curl_setopt($curl, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($curl, CURLOPT_COOKIEJAR, 'cookie.txt');
        curl_setopt($curl, CURLOPT_COOKIEFILE, 'cookie.txt');
        curl_setopt($curl, CURLOPT_URL, 'http://www.plurk.com/Users/login');
        curl_setopt($curl, CURLOPT_POSTFIELDS, 'nick_name='. $username .'&password='. $password .'');
        curl_exec($curl);

        $profile = file_get_contents('http://www.plurk.com/user/' . $username);
        $uid = explode('var GLOBAL', $profile);
        $uid = explode('"uid": ', $uid[1]);
        $uid = explode(',', $uid[1]);

        curl_setopt($curl, CURLOPT_URL, 'http://www.plurk.com/TimeLine/addPlurk');
        curl_setopt($curl, CURLOPT_POSTFIELDS, 'qualifier=shares&content='. $new_status .'&lang=en&no_comments=0&uid=' . $uid[0]);
        $post = curl_exec($curl);
        curl_close($curl);

}

function wordplurk_post_now_published($new_status, $old_status, $post)
{

$post_id = $post->ID;

	if ($new_status == 'publish' && $old_status != 'publish') {
		$has_been_plurked = get_post_meta($post_id, 'has_been_plurked', true);
		if (!($has_been_plurked == 'yes')) {
			query_posts('p=' . $post_id);
			if (have_posts()) {
				the_post();
				$post_url = file_get_contents('http://tinyurl.com/api-create.php?url=' . get_permalink());
				$title = get_the_title();
				if (strlen($title) > 110) {
					$title = substr_replace($title, '...', 107);
				}
				$i = '\'' . $title . '\' - ' . $post_url;
			
				$plurk_username = get_option('wordplurk_username', 0);
				$plurk_password = get_option('wordplurk_password', 0);
			
				plurk_update_status($plurk_username, $plurk_password, $i);
	
				add_post_meta($post_id, 'has_been_plurked', 'yes');
			}
		}
	}
}

function wordplurk_options_subpanel()
{
	?>
	<div class="wrap">
	<h2>WordPlurk <?php _e('Settings'); ?></h2>
	<p><?php _e('Please enter your Plurk username and password below. All fields are required.'); ?></p>
	<form method="post" action="options.php">
	<?php wp_nonce_field('update-options'); ?>
	<table class="form-table">
	<tr valign="top">
	<th scope="row"><label for="wordplurk_username">Plurk <?php _e('Username'); ?></label></th>
	<td><input type="text" name="wordplurk_username" value="<?php echo get_option('wordplurk_username'); ?>">
	<br />
	Enter the username of the Plurk account you want to post updates to.</td>
	</tr>
	<tr valign="top">
	<th scope="row"><label for="wordplurk_password">Plurk <?php _e('Password'); ?></label></th>
	<td><input type="password" name="wordplurk_password" value="<?php echo get_option('wordplurk_password'); ?>">
	<br />
	Enter the password of the Plurk account entered above.</td>
	</tr>
	</table>
	<input type="hidden" name="action" value="update" />
	<input type="hidden" name="page_options" value="wordplurk_username, wordplurk_password" />
	<p class="submit"><input type="submit" name="submit" value="<?php _e('Save Changes') ?>" /></p>
	</form>
	</div>
	<?php 
}

function wordplurk_add_plugin_option()
{
	if (function_exists('add_options_page')) 
	{
		add_options_page('WordPlurk', 'WordPlurk', 10, 'wordplurk', 'wordplurk_options_subpanel');
    }	
}

add_action('admin_menu', 'wordplurk_add_plugin_option');

function wordplurk_notice()
{
	if (!get_option('wordplurk_username') || !get_option('wordplurk_password'))
	{
		echo '<div class="updated fade" style="padding: 5px;">' . sprintf(__('Please enter your Plurk username and password on the <a href="%1$s" title="WordPlurk Settings">WordPlurk Settings page</a>.'), "options-general.php?page=wordplurk") . '</div>';
	}
}

add_action('admin_notices', 'wordplurk_notice');

?>

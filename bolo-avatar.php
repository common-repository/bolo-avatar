<?php
/* 
Plugin Name: Bolo Avatar
Plugin URI: http://www.codecto.com/
Version: 1.0
Author: Bolo
Description: Allow users upload a avatar
Author URI: http://www.codecto.com/
Donate link: http://www.codecto.com/
*/

add_action('load-profile.php', array('bolo_avatar', 'load_profile_php'));
add_action('show_user_profile', array('bolo_avatar', 'show_user_profile'));
add_filter('get_avatar', array('bolo_avatar', 'get_avatar'), 1, 5);
class bolo_avatar {
	function str_replace_once($needle, $replace, $haystack) {
		$pos = strpos($haystack, $needle);
		if($pos === false) {
			return $haystack;
		}
		return substr_replace($haystack, $replace, $pos, strlen($needle));
	}

	function avatar_dir() {
		$siteurl = get_option('siteurl');
		$uploads['path'] = WP_CONTENT_DIR.DIRECTORY_SEPARATOR.'avatar';
		$uploads['url'] = content_url().'/avatar';
		$uploads['subdir'] = '';
		$uploads['basedir'] = $uploads['path'];
		$uploads['baseurl'] = $uploads['url'];
		$uploads['error'] = false;
		return $uploads;
	}

	function load_profile_php() {
		if($_SERVER['REQUEST_METHOD'] == 'POST' && $_FILES['avatar']['name'] && check_admin_referer('avatar-upload', '_wpnonce-avatar-upload')) {
			add_filter('upload_dir', array(__CLASS__, 'avatar_dir'));
			$user_id = (int)$_POST['user_id'];
			$overrides = array('test_form' => false);
			$uploaded_file = $_FILES['avatar'];
			$wp_filetype = wp_check_filetype_and_ext( $uploaded_file['tmp_name'], $uploaded_file['name'], false );
			$path_parts = pathinfo($uploaded_file['name']);
			$uploaded_file['name'] = $user_id.'_avatar.'.$path_parts['extension'];
			if ( ! wp_match_mime_types( 'image', $wp_filetype['type'] ) )
				wp_die( __( 'The uploaded file is not a valid image. Please try again.' ) );
	
			$file = wp_handle_upload($uploaded_file, $overrides);
			if ( isset($file['error']) )
				wp_die( $file['error'],  __( 'Image Upload Error' ) );

			$avatar_dir = self::avatar_dir();
			@unlink(str_replace(content_url(), WP_CONTENT_DIR, get_user_meta($user_id, 'avatar', true)));
			$image = wp_get_image_editor($file['file']);
			$image->resize(96, 96);
			$image->save($file['file']);
			update_user_meta($user_id, 'avatar', $file['url']);
		}
		ob_start();
	}

	function show_user_profile($user) {
		$html = ob_get_clean();
		$html = str_replace('<form id="your-profile"', '<form id="your-profile" enctype="multipart/form-data"', $html);
		ob_start();
		?>
		<tr>
			<th scope="row">头像</th>
			<td valign="middle">
				<?php echo get_avatar($user->ID); ?><br />
				<label for="upload"><?php _e( 'Choose an image from your computer:' ); ?></label><br />
				<input type="file" id="upload" name="avatar" />
				<?php wp_nonce_field('avatar-upload', '_wpnonce-avatar-upload'); ?>
			</td>
		</tr>
		<?php
		$avatar = ob_get_clean();
		$html = self::str_replace_once('<tr', $avatar.'<tr', $html);
		echo $html;
	}

	function get_avatar($avatar, $id_or_email, $size, $default, $alt) {
		if(!is_numeric($id_or_email)) {
			return $avatar;
		}
		$avatar_img = get_user_meta($id_or_email, 'avatar', true);
		return $avatar_img?'<img alt="'.$alt.'" src="'.$avatar_img.'" class="avatar avatar-'.$size.' photo" height="'.$size.'" width="'.$size.'">':$avatar;
	}
}
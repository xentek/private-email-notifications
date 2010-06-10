<?php
/*
Plugin Name: Private Email Notifications
Plugin URI: http://xentek.net/
Description: Remove Email and IP address information from Email Notifications to protect the privacy of folks commenting on your blog.
Version: 0.4
Author: Eric Marden
Author URI: http://xentek.net/ 
*/

if ( !function_exists('wp_new_user_notification') ):
function wp_new_user_notification($user_id, $plaintext_pass = '') {
		$user = new WP_User($user_id);

		$user_login = stripslashes($user->user_login);
		$user_email = stripslashes($user->user_email);

		// The blogname option is escaped with esc_html on the way into the database in sanitize_option
		// we want to reverse this for the plain text arena of emails.
		$blogname = wp_specialchars_decode(get_option('blogname'), ENT_QUOTES);

		$message  = sprintf(__('New user registration on your blog %s:'), $blogname) . "\r\n\r\n";
		$message .= sprintf(__('Username: %s'), $user_login) . "\r\n\r\n";
		//$message .= sprintf(__('E-mail: %s'), $user_email) . "\r\n";

		@wp_mail(get_option('admin_email'), sprintf(__('[%s] New User Registration'), $blogname), $message);

		if ( empty($plaintext_pass) )
				return;

		$message  = sprintf(__('Username: %s'), $user_login) . "\r\n";
		$message .= sprintf(__('Password: %s'), $plaintext_pass) . "\r\n";
		$message .= wp_login_url() . "\r\n";

		wp_mail($user_email, sprintf(__('[%s] Your username and password'), $blogname), $message);

}
endif;

if ( !function_exists('wp_notify_moderator') ):
function wp_notify_moderator($comment_id) {
		global $wpdb;

		if( get_option( "moderation_notify" ) == 0 )
				return true;

		$comment = $wpdb->get_row($wpdb->prepare("SELECT * FROM $wpdb->comments WHERE comment_ID=%d LIMIT 1", $comment_id));
		$post = $wpdb->get_row($wpdb->prepare("SELECT * FROM $wpdb->posts WHERE ID=%d LIMIT 1", $comment->comment_post_ID));

		$comment_author_domain = @gethostbyaddr($comment->comment_author_IP);
		$comments_waiting = $wpdb->get_var("SELECT count(comment_ID) FROM $wpdb->comments WHERE comment_approved = '0'");

		// The blogname option is escaped with esc_html on the way into the database in sanitize_option
		// we want to reverse this for the plain text arena of emails.
		$blogname = wp_specialchars_decode(get_option('blogname'), ENT_QUOTES);

		switch ($comment->comment_type)
		{
				case 'trackback':
						$notify_message	 = sprintf( __('A new trackback on the post #%1$s "%2$s" is waiting for your approval'), $post->ID, $post->post_title ) . "\r\n";
						$notify_message .= get_permalink($comment->comment_post_ID) . "\r\n\r\n";
						$notify_message .= sprintf( __('Website : %1$s (IP: %2$s , %3$s)'), $comment->comment_author, $comment->comment_author_IP, $comment_author_domain ) . "\r\n";
						$notify_message .= sprintf( __('URL	   : %s'), $comment->comment_author_url ) . "\r\n";
						$notify_message .= __('Trackback excerpt: ') . "\r\n" . $comment->comment_content . "\r\n\r\n";
						break;
				case 'pingback':
						$notify_message	 = sprintf( __('A new pingback on the post #%1$s "%2$s" is waiting for your approval'), $post->ID, $post->post_title ) . "\r\n";
						$notify_message .= get_permalink($comment->comment_post_ID) . "\r\n\r\n";
						$notify_message .= sprintf( __('Website : %1$s (IP: %2$s , %3$s)'), $comment->comment_author, $comment->comment_author_IP, $comment_author_domain ) . "\r\n";
						$notify_message .= sprintf( __('URL	   : %s'), $comment->comment_author_url ) . "\r\n";
						$notify_message .= __('Pingback excerpt: ') . "\r\n" . $comment->comment_content . "\r\n\r\n";
						break;
				default: //Comments
						$notify_message	 = sprintf( __('A new comment on the post #%1$s "%2$s" is waiting for your approval'), $post->ID, $post->post_title ) . "\r\n";
						$notify_message .= get_permalink($comment->comment_post_ID) . "\r\n\r\n";
						$notify_message .= sprintf( __('Author : %1$s'), $comment->comment_author) . "\r\n";
						//$notify_message .= sprintf( __('E-mail : %s'), $comment->comment_author_email ) . "\r\n";
						$notify_message .= sprintf( __('URL	   : %s'), $comment->comment_author_url ) . "\r\n";
						//$notify_message .= sprintf( __('Whois	 : http://ws.arin.net/cgi-bin/whois.pl?queryinput=%s'), $comment->comment_author_IP ) . "\r\n";
						$notify_message .= __('Comment: ') . "\r\n" . $comment->comment_content . "\r\n\r\n";
						break;
		}

		$notify_message .= sprintf( __('Approve it: %s'),  admin_url("comment.php?action=approve&c=$comment_id") ) . "\r\n";
		if ( EMPTY_TRASH_DAYS )
				$notify_message .= sprintf( __('Trash it: %s'), admin_url("comment.php?action=trash&c=$comment_id") ) . "\r\n";
		else
				$notify_message .= sprintf( __('Delete it: %s'), admin_url("comment.php?action=delete&c=$comment_id") ) . "\r\n";
		$notify_message .= sprintf( __('Spam it: %s'), admin_url("comment.php?action=spam&c=$comment_id") ) . "\r\n";

		$notify_message .= sprintf( _n('Currently %s comment is waiting for approval. Please visit the moderation panel:',
				'Currently %s comments are waiting for approval. Please visit the moderation panel:', $comments_waiting), number_format_i18n($comments_waiting) ) . "\r\n";
		$notify_message .= admin_url("edit-comments.php?comment_status=moderated") . "\r\n";

		$subject = sprintf( __('[%1$s] Please moderate: "%2$s"'), $blogname, $post->post_title );
		$admin_email = get_option('admin_email');
		$message_headers = '';

		$notify_message = apply_filters('comment_moderation_text', $notify_message, $comment_id);
		$subject = apply_filters('comment_moderation_subject', $subject, $comment_id);
		$message_headers = apply_filters('comment_moderation_headers', $message_headers);

		@wp_mail($admin_email, $subject, $notify_message, $message_headers);

		return true;
}
endif;

if ( !function_exists('wp_notify_postauthor') ):
function wp_notify_postauthor($comment_id, $comment_type='') {
		$comment = get_comment($comment_id);
		$post	 = get_post($comment->comment_post_ID);
		$user	 = get_userdata( $post->post_author );
		$current_user = wp_get_current_user();

		if ( $comment->user_id == $post->post_author ) return false; // The author moderated a comment on his own post

		if ('' == $user->user_email) return false; // If there's no email to send the comment to

		$comment_author_domain = @gethostbyaddr($comment->comment_author_IP);

		// The blogname option is escaped with esc_html on the way into the database in sanitize_option
		// we want to reverse this for the plain text arena of emails.
		$blogname = wp_specialchars_decode(get_option('blogname'), ENT_QUOTES);

		if ( empty( $comment_type ) ) $comment_type = 'comment';
	if ('comment' == $comment_type) {
					/* translators: 1: post id, 2: post title */
					$notify_message	 = sprintf( __('New comment on your post #%1$s "%2$s"'), $comment->comment_post_ID, $post->post_title ) . "\r\n";
					/* translators: 1: comment author, 2: author IP, 3: author domain */
					$notify_message .= sprintf( __('Author : %1$s (IP: %2$s , %3$s)'), $comment->comment_author, $comment->comment_author_IP, $comment_author_domain ) . "\r\n";
					//$notify_message .= sprintf( __('E-mail : %s'), $comment->comment_author_email ) . "\r\n";
					$notify_message .= sprintf( __('URL	   : %s'), $comment->comment_author_url ) . "\r\n";
					//$notify_message .= sprintf( __('Whois	 : http://ws.arin.net/cgi-bin/whois.pl?queryinput=%s'), $comment->comment_author_IP ) . "\r\n";
					$notify_message .= __('Comment: ') . "\r\n" . $comment->comment_content . "\r\n\r\n";
					$notify_message .= __('You can see all comments on this post here: ') . "\r\n";
					/* translators: 1: blog name, 2: post title */
					$subject = sprintf( __('[%1$s] Comment: "%2$s"'), $blogname, $post->post_title );
			} elseif ('trackback' == $comment_type) {
					/* translators: 1: post id, 2: post title */
					$notify_message	 = sprintf( __('New trackback on your post #%1$s "%2$s"'), $comment->comment_post_ID, $post->post_title ) . "\r\n";
					/* translators: 1: website name, 2: author IP, 3: author domain */
					$notify_message .= sprintf( __('Website: %1$s (IP: %2$s , %3$s)'), $comment->comment_author, $comment->comment_author_IP, $comment_author_domain ) . "\r\n";
					$notify_message .= sprintf( __('URL	   : %s'), $comment->comment_author_url ) . "\r\n";
					$notify_message .= __('Excerpt: ') . "\r\n" . $comment->comment_content . "\r\n\r\n";
					$notify_message .= __('You can see all trackbacks on this post here: ') . "\r\n";
					/* translators: 1: blog name, 2: post title */
					$subject = sprintf( __('[%1$s] Trackback: "%2$s"'), $blogname, $post->post_title );
			} elseif ('pingback' == $comment_type) {
			   /* translators: 1: post id, 2: post title */
					$notify_message	 = sprintf( __('New pingback on your post #%1$s "%2$s"'), $comment->comment_post_ID, $post->post_title ) . "\r\n";
					/* translators: 1: comment author, 2: author IP, 3: author domain */
					$notify_message .= sprintf( __('Website: %1$s (IP: %2$s , %3$s)'), $comment->comment_author, $comment->comment_author_IP, $comment_author_domain ) . "\r\n";
					$notify_message .= sprintf( __('URL	   : %s'), $comment->comment_author_url ) . "\r\n";
					$notify_message .= __('Excerpt: ') . "\r\n" . sprintf('[...] %s [...]', $comment->comment_content ) . "\r\n\r\n";
					$notify_message .= __('You can see all pingbacks on this post here: ') . "\r\n";
					/* translators: 1: blog name, 2: post title */
					$subject = sprintf( __('[%1$s] Pingback: "%2$s"'), $blogname, $post->post_title );
			}
			$notify_message .= get_permalink($comment->comment_post_ID) . "#comments\r\n\r\n";
			
			if ( EMPTY_TRASH_DAYS )
					$notify_message .= sprintf( __('Trash it: %s'), admin_url("comment.php?action=trash&c=$comment_id") ) . "\r\n";
			else
					$notify_message .= sprintf( __('Delete it: %s'), admin_url("comment.php?action=delete&c=$comment_id") ) . "\r\n";
			$notify_message .= sprintf( __('Spam it: %s'), admin_url("comment.php?action=spam&c=$comment_id") ) . "\r\n";

			$wp_email = 'wordpress@' . preg_replace('#^www\.#', '', strtolower($_SERVER['SERVER_NAME']));

			$from = "From: \"$comment->comment_author\" <$wp_email>";
			$reply_to = "Reply-To: $wp_email";

			$message_headers = "$from\n"
					. "Content-Type: text/plain; charset=\"" . get_option('blog_charset') . "\"\n";

			if ( isset($reply_to) )
					$message_headers .= $reply_to . "\n";

			$notify_message = apply_filters('comment_notification_text', $notify_message, $comment_id);
			$subject = apply_filters('comment_notification_subject', $subject, $comment_id);
			$message_headers = apply_filters('comment_notification_headers', $message_headers, $comment_id);

			@wp_mail($user->user_email, $subject, $notify_message, $message_headers);

			return true;
}
endif;
?>
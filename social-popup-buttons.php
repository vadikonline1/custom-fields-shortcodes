<?php
	if(defined('TRIPGO_URL') 	== false) 	define('TRIPGO_URL', get_template_directory());
	if(defined('TRIPGO_URI') 	== false) 	define('TRIPGO_URI', get_template_directory_uri());

	load_theme_textdomain( 'tripgo', TRIPGO_URL . '/languages' );

	// Main Feature
	require_once( TRIPGO_URL.'/inc/class-main.php' );

	// Functions
	require_once( TRIPGO_URL.'/inc/functions.php' );

	// Hooks
	require_once( TRIPGO_URL.'/inc/class-hook.php' );

	// Widget
	require_once (TRIPGO_URL.'/inc/class-widgets.php');
	

	// Elementor
	if (defined('ELEMENTOR_VERSION')) {
		require_once (TRIPGO_URL.'/inc/class-elementor.php');
	}
	
	// WooCommerce
	if (class_exists('WooCommerce')) {
		require_once (TRIPGO_URL.'/inc/class-woo.php');
		require_once (TRIPGO_URL.'/inc/class-woo-template-functions.php');
		require_once (TRIPGO_URL.'/inc/class-woo-template-hooks.php');
	}
	
	
	/* Customize */
	if( current_user_can('customize') ){
	    require_once TRIPGO_URL.'/customize/custom-control/google-font.php';
	    require_once TRIPGO_URL.'/customize/custom-control/heading.php';
	    require_once TRIPGO_URL.'/inc/class-customize.php';
	}
    
   
	require_once ( TRIPGO_URL.'/install-resource/active-plugins.php' );
	

	
// 1. Adaugă avatarul în lista de opțiuni
function custom_avatar_option($avatar_defaults) {
$custom_avatar_url = '/wp-content/uploads/2025/10/cropped-site-icon-1.png';
$avatar_defaults[$custom_avatar_url] = 'Custom Avatar';
return $avatar_defaults;
}
add_filter('avatar_defaults', 'custom_avatar_option');

// 2. Înlocuiește afișarea avatarului pentru utilizatorii fără avatar propriu
function custom_default_avatar($avatar, $id_or_email, $args) {
$custom_avatar_url = '/wp-content/uploads/2025/10/cropped-site-icon-1.png';
$alt = esc_attr($args['alt']);
$class = esc_attr($args['class']);

// Folosim dimensiunea fixă: 35x35 px
$size = 35;

$user = false;

if (is_numeric($id_or_email)) {
$user = get_user_by('id', (int)$id_or_email);
} elseif (is_object($id_or_email) && !empty($id_or_email->user_id)) {
$user = get_user_by('id', (int)$id_or_email->user_id);
} elseif (is_string($id_or_email)) {
$user = get_user_by('email', $id_or_email);
}

// Verifică dacă userul are Gravatar
$has_gravatar = false;
if ($user) {
$email = $user->user_email;
$hash = md5(strtolower(trim($email)));
$gravatar_check = wp_remote_head("https://www.gravatar.com/avatar/$hash?d=404");

if (is_array($gravatar_check) && $gravatar_check['response']['code'] === 200) {
$has_gravatar = true;
}
}

// Dacă NU are Gravatar, returnăm avatarul nostru
if (!$has_gravatar) {
$avatar = "<img alt='{$alt}' src='{$custom_avatar_url}' class='{$class}' width='35' height='35' />";
}

return $avatar;
}
add_filter('get_avatar', 'custom_default_avatar', 10, 3);
//show only posts in search results
if (!is_admin()) {
function wpb_search_filter($query) {
if ($query->is_search) {
$query->set('post_type', 'post');
}
return $query;
}
add_filter('pre_get_posts','wpb_search_filter');
}

<?php 

require_once('weblamas_functions.php');

require_once('weblamas_options.php');

require_once('query.php');

require_once('fields.php');
require_once('custom_post.php');

add_theme_support( 'post-thumbnails' ); 
//отменить  srcset
add_filter( 'wp_calculate_image_srcset_meta', '__return_null' );


add_action('init', function(){
    if (!is_admin()&&!($GLOBALS['pagenow'] === 'wp-login.php')) {
        wp_deregister_script('jquery');
    }
});


function rus_plural($number,$after){
	$cases = array (2, 0, 1, 1, 1, 2);
	return sprintf($after[ ($number%100>4 && $number%100<20)? 2: $cases[min($number%10, 5)] ],$number);
	}
add_action( 'wpcf7_init', function(){
	wpcf7_add_form_tag( 'submitwl', 'wpcf7_submitwl_form_tag_handler' );
	wpcf7_add_form_tag( 'wlsubmit', 'wpcf7_submitwl_form_tag_handler' );
} );


function wpcf7_submitwl_form_tag_handler( $tag ) {
	$class = wpcf7_form_controls_class( $tag->type );

	$atts = array();

	$atts['class'] = $tag->get_class_option( $class ) .' wpcf7-submit' ;
	
	$atts['class']=str_replace('wpcf7-form-control','',$atts['class']);
	$atts['id'] = $tag->get_id_option();
	$atts['tabindex'] = $tag->get_option( 'tabindex', 'signed_int', true );

	$value = isset( $tag->values[0] ) ? $tag->values[0] : '';

	if ( empty( $value ) ) {
		$value = __( 'Send', 'contact-form-7' );
	}

	$atts['type'] = 'submit';
	//$atts['value'] = $value;

	$atts = wpcf7_format_atts( $atts );
	$html='<span class="wpcf7-form-control-wrap wpcf7-form-control-wrap_button">'.sprintf( '<button %1$s >', $atts ).htmlspecialchars_decode($value).'</button></span>';
	//$html = sprintf( '<input %1$s />', $atts );

	return $html;
}



//---------------------  добавление мелких изменений ----------------------- 
remove_action('welcome_panel', 'wp_welcome_panel');
//------------------------------------------------------------------------------- 
function remove_footer_admin() {echo '<p>&copy; <a href="http://weblamas.com/" target="_blank">WebLamas</a> '.date('Y').'.</p>';}
add_filter('admin_footer_text', 'remove_footer_admin');

function disable_emojis() {
	remove_action( 'wp_head', 'print_emoji_detection_script', 7 );
	remove_action( 'admin_print_scripts', 'print_emoji_detection_script' );
	remove_action( 'wp_print_styles', 'print_emoji_styles' );
	remove_action( 'admin_print_styles', 'print_emoji_styles' );	
	remove_filter( 'the_content_feed', 'wp_staticize_emoji' );
	remove_filter( 'comment_text_rss', 'wp_staticize_emoji' );	
	remove_filter( 'wp_mail', 'wp_staticize_emoji_for_email' );
	
	// Remove from TinyMCE
	//add_filter( 'tiny_mce_plugins', 'disable_emojis_tinymce' );
}
add_action( 'init', 'disable_emojis' );
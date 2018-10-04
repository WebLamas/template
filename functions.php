<?php 
include('weblamas_functions.php');
require_once('query.php');
require_once('fields.php');
require_once('custom_post.php');
add_theme_support( 'post-thumbnails' ); 
//отменить  srcset
add_filter( 'wp_calculate_image_srcset_meta', '__return_null' );


add_action('init', function(){
    if (!is_admin()) {
        wp_deregister_script('jquery');
//        wp_register_script('jquery',get_template_directory_uri() . '/js/jquery-3.1.1.min.js', false, '3.1.1',true);
//        wp_enqueue_script('jquery');
    }
});

function rus_plural($number,$after){
	$cases = array (2, 0, 1, 1, 1, 2);
	return sprintf($after[ ($number%100>4 && $number%100<20)? 2: $cases[min($number%10, 5)] ],$number);
	}
add_action( 'wpcf7_init', function(){
	wpcf7_add_form_tag( 'submitwl', 'wpcf7_submitwl_form_tag_handler' );
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
	$html=sprintf( '<button %1$s >', $atts ).htmlspecialchars_decode($value).'</button>';
	//$html = sprintf( '<input %1$s />', $atts );

	return $html;
}

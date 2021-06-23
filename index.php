<?php
return;
$templates=WeblamasTemplate::get_subtemplates();
?><!DOCTYPE html>
<html <?php language_attributes(); ?> class="no-js">
<head>
	<meta charset="<?php bloginfo( 'charset' ); ?>">
	<script>var ajax_url='<?php echo admin_url( 'admin-ajax.php' )?>'</script>
	<?php wp_head(); ?>
	<meta name="viewport" content="width=device-width, initial-scale=1">
	<link rel="stylesheet" href="<?php echo get_template_directory_uri();?>/css/main.css">
	<script src="<?php echo get_template_directory_uri();?>/js/main.js"></script>
</head> 
<body <?php body_class(); ?>> 
<?php wp_nav_menu(array('menu'=>'header-menu','container'=>'nav','container_class'=>'main_menu','menu_id'=>''));?>
<?php WeblamasTemplate::loadTemplate($templates);?>
	
<?php //WeblamasTemplate::showTemplates($templates);?>

</body> 
</html>
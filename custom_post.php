<?php

abstract class WeblamasCustomPost{
	//abstract public $name;
	public static $has_cats=false;
	public static $has_archive=false;
	abstract static function getArgs();
	public static function query(){
		$q=new Query();
		$q->post_type(static::$name);
		$q->post_fields=static::customFields(true); //get all fields
		return $q;
	}
	public static function init(){
		add_action( 'init', array(get_called_class(),'createposttype') );
		add_filter('manage_'.static::$name.'_posts_columns' , array(get_called_class(),'add_columns'));
		add_action( 'manage_posts_custom_column' , array(get_called_class(),'custom_columns'), 10, 2 );
		if(static::$has_archive){
			add_action( 'admin_menu', array(get_called_class(),'addarchivedescription') );
			add_action('init',array(get_called_class(),'archivedescription_save'));

			}
		if(static::$has_cats){
			add_filter( 'rewrite_rules_array', array(get_called_class(),'add_rewrite_rules') );
			add_filter( 'post_type_link', array(get_called_class(),'filter_post_type_link'), 10, 2 );
			add_filter('post_type_archive_link',array(get_called_class(),'filter_post_type_archive_link'),10,2);
		}
		$cf=static::customFields();
		if(!empty($cf)){
			add_action( 'add_meta_boxes', array( get_called_class(), 'add_meta_box' ) );
			add_action( 'save_post', array( get_called_class(), 'save' ) );
		}
		
	}
	public static function custom_columns($column,$post_id){return '';}
	public static function add_columns($columns){return $columns;}
	public static function customFields(){return array();}
	public static function multipleDescriptions(){
			return apply_filters('wlcp_multiple_descriptions',['default'=>'']);//wlcp=WebLamasCustomPost
		}
	public static function archivedescription_save(){
		if(empty($_REQUEST['page'])||empty($_POST['action']))return;
		if($_REQUEST['page']!=static::$name . '-description' or  $_POST['action']!='save')return;
		$post=array_map('stripslashes_deep',$_POST);
		foreach(static::multipleDescriptions() as $variant=>$desc_title){
			$post['desc'][$variant]['description']=$post['description__'.$variant];
		}
		$post=$post['desc'];
		update_option('archivedesc_'.static::$name,base64_encode(serialize($post)));
		}
	public static function addarchivedescription(){
		add_submenu_page('edit.php?post_type=' . static::$name,"Описание для архива","Описание для архива",'edit_posts',static::$name . '-description',array(get_called_class(),'archivedescription'));	
	}
	public static function get_archive_description($variant=''){
		$value=get_option('archivedesc_'.static::$name);
		$value=unserialize(base64_decode($value));
		if(!empty($variant)){
			$value=$value[$variant];
		}
		return $value;
	}
	public static function archivedescriptionFields($variant='',$title=''){
		$value=get_option('archivedesc_'.static::$name);
		$value=unserialize(base64_decode($value));
		$value=$value[$variant]??[];
		$editor_name='description__'.$variant;
		echo '<h1 class="wp-heading-inline">Описание для архива '.($title ?? '').'</h1>';
		echo '<div><label>Тайтл '.$title.'</label></div>';
		echo '<div><input type="text" name="desc['.$variant.'][title]" value="'.htmlspecialchars($value['title']??'').'"></div>';
		echo '<div><label>Заголовок (h1) '.$title.'</label></div>';
		echo '<div><input type="text" name="desc['.$variant.'][h1]" value="'.htmlspecialchars($value['h1']??'').'"></div>';
		echo '<div><label>Мета описание '.$title.'</label></div>';
		echo '<div><textarea name="desc['.$variant.'][meta_desc]">'.($value['meta_desc']??'').'</textarea></div>';
		echo '<div><label>Описание '.$title.'</label></div>';
		wp_editor($value['description']??'','description__'.$variant);
		}
	public static function archivedescription(){
		echo '<div class="wrap">';
		echo '<form method="post">';
		foreach(static::multipleDescriptions() as $variant=>$desc_title){
			static::archivedescriptionFields($variant,$desc_title);
		}
		echo '<button name="action" value="save">Сохранить</button>';
		echo '</form>';
		echo '</div>';
	}
	public function add_rewrite_rules($rules){
		$new = array();
		$new[static::$name.'/([^/]+)/(.+)/?$'] = 'index.php?'.static::$name.'=$matches[2]';
		$new[static::$name.'/(.+)/?$'] = 'index.php?'.static::$name.'_cat=$matches[1]';
		$new[static::$name.'$'] = 'index.php?post_type='.static::$name;
		return array_merge( $new, $rules ); // Ensure our rules come first
		}
	public function filter_post_type_archive_link($link,$post_type){
		$link=str_replace('%'.static::$name.'_cat%','',$link);
		$link=str_replace(static::$name.'//',static::$name.'/',$link);
		if(function_exists('pll_current_language')&&pll_default_language()!=pll_current_language()){
			$link=str_replace(static::$name,pll_current_language().'/'.static::$name,$link);
		}
		return $link;
	}
	public function filter_post_type_link( $link, $post ) {
		if ( $post->post_type == static::$name ) {
			if ( $cats = get_the_terms( $post->ID, static::$name.'_cat' ) ) {
				$link = str_replace( '%'.static::$name.'_cat%', current( $cats )->slug, $link );
				}
			}
		return $link;
		}

	public static function populateArgs($args){
		$q=array(      
			'public' => true,
			'show_ui'  => true,
			'has_archive' => true,
			'menu_position'=>5,
			'query_var'     => true,
			'rewrite'       => array('slug' => static::$name),
			);
		foreach($q as $k=>$v){
			if(!isset($args[$k])){
				$args[$k]=$v;
			}
		}
		return $args;
		
	}
	public static function catArgs(){
		return array();
	}

	public static function createposttype(){
		register_post_type( static::$name,static::populateArgs(static::getArgs()));
		$cargs=static::catArgs();
		if(!empty($cargs)){
			register_taxonomy( static::$name.'_cat', static::$name, $cargs );
		}
	}
	public static function add_meta_box(){
		$where='side';	
		if(!empty(static::$position)){	
			$where=static::$position;	
		}
			add_meta_box(
				static::$name.'_sectionid',
				"Дополнительные настройки",
				array(get_called_class(),'meta_box_callback'),
				static::$name,
				$where
				);
	}
	public static function meta_box_callback( $post ) {
		wp_nonce_field( static::$name.'_save_meta_box_data', static::$name.'_meta_box_nonce' );
		echo '<div class="weblamas_fields">';
		foreach(static::customFields() as $fieldm){
			$field_value=get_post_meta( $post->ID, '_'.$fieldm['name'], true );
			if($fieldm['type']=='json'){
				if(!empty($field_value)){
					$json_field_value=maybe_unserialize(base64_decode($field_value));
				}else{
					$json_field_value=array();
					}
				foreach($fieldm['fields'] as $field){
					echo '<div><label for="'.$fieldm['name'].'['.$field['name'].']'.'">'.$field['label'].'</label></div><div>';
					FieldRenderer::render($field,$fieldm['name'].'['.$field['name'].']',$json_field_value[$field['name']]??'');
					echo '</div>';
				}
				
			}elseif($fieldm['type']=='multiple'){
				//$field_value="";
				if(!empty($field_value)){
					$multiple_field_value=maybe_unserialize(base64_decode($field_value));
				}else{
					$multiple_field_value=array(array());
					}
				echo '<div class="multiple multiple_'.$fieldm['name'].'">';
				foreach($multiple_field_value as $k=>$row){
					echo '<div class="item" data-counter="'.$k.'">';
					foreach($fieldm['fields'] as $field){
						if(!empty($field['label'])){
							echo '<div><label for="'.$field['name'].'['.$k.']['.$field['name'].']">'.$field['label'].'</label></div>';
						}
						echo '<div>';
						FieldRenderer::render($field,$fieldm['name'].'['.$k.']['.$field['name'].']',$row[$field['name']]);
						echo '</div>';
					}
					echo '<button type="button" class="remove_item">-</button>';
					echo '</div>';
				}
				echo '<button type="button" class="add_item">+</button>';
				echo '</div>';
				?>
				<style>
				.item{
					border:1px solid gray;
				}
				</style>
				<script>
				$(document).ready(function(){
					$('.multiple_<?php echo $fieldm['name'];?>').on('click','.remove_item',function(){
						$(this).closest('.item').remove();
					});
					$('.multiple_<?php echo $fieldm['name'];?> .add_item').click(function(){
						var item=$(this).closest('.multiple').find('.item').last();
						var find='<?php echo $fieldm['name'];?>\['+item.data('counter')+'\]';
						var newitem=(item.html().replace(new RegExp(find.replace(/[-\/\\^$*+?.()|[\]{}]/g, '\\$&'),'g'),'<?php echo $fieldm['name'];?>['+(1+item.data('counter'))+']'));
						console.log(newitem);
						newitem=newitem.replace(/ value=".+?"/ig,'');
						console.log(newitem);
						newitem='<div class="item" data-counter="'+(1+item.data('counter'))+'">'+newitem+'</div>';
						$(newitem).insertBefore($('.multiple_<?php echo $fieldm['name'];?> .add_item'));
						
						
						//console.log(s.match('/*name="<?php echo $fieldm['name'];?>"*/'));
					});
				});
				</script>
				<?php
			}else{
				if($fieldm['type']=='unused'){
					continue;
				}
				if(!empty($fieldm['label'])){
					echo '<div><label for="'.$fieldm['name'].'">'.$fieldm['label'].'</label></div>';
				}
				echo '<div>';
				FieldRenderer::render($fieldm,$fieldm['name'],$field_value);
				echo '</div>';
			}
		}
		echo '</div>';
		?>
		<style>
			.weblamas_fields input,.weblamas_fields textarea{
				width:100%;
			}
			
			.weblamas_fields input[type="checkbox"]{
				width:auto;
			}
			.select2{
					width:100%!important;
					
				}
				.weblamas_fields select{
					width:100%;
				}
		</style>
		<link href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css" rel="stylesheet" />
<script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>
<script>
jQuery(document).ready(function(){
	jQuery(".select2").select2();
});
</script>
		<?php
	}
	public function modify_field($field,$field_value){
		if($field['type']=='json'){
			if(is_array($field_value)){
				$field_value=array_map(function($item){
					return str_replace('\"','"',$item);
				},array_filter($field_value));
				$field_value=base64_encode(serialize($field_value));
			}else{
				$field_value=base64_encode(serialize(array()));
			}
		}
		if($field['type']=='multiple'){
			if(is_array($field_value)){
				foreach($field_value as $k=>$v){
					$field_value[$k]=array_filter($v);
				}
				$field_value=array_values(array_filter($field_value));
				$field_value=base64_encode(serialize($field_value));
			}else{
				$field_value=base64_encode(serialize(array(array())));
			}
		}
		if($field['type']=='date'){
			$q=new DateTime($field_value);
			return $q->format('Y-m-d');
		}
		if($field['type']=='checkbox'){
			return !empty($field_value)?1:0;
		}
		return $field_value;
	}
	public static function save( $post_id ) {
		if(empty($_POST['post_type']))return;
		if($_POST['post_type']!=static::$name) return;
		if ( ! isset( $_POST[static::$name.'_meta_box_nonce'] ) )return;
		if ( ! wp_verify_nonce( $_POST[static::$name.'_meta_box_nonce'], static::$name.'_save_meta_box_data' ) ) return;
		if ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE )return;
		if ( ! current_user_can( 'edit_post', $post_id ) ) 	return;
		$cf=static::customFields();
		foreach($cf as $field){
			if($field['type']=='unused') continue;
			if ( ! isset( $_POST[$field['name']] ) ){
				update_post_meta( $post_id, '_'.$field['name'], '' );
				continue;
			}
			$my_data=static::modify_field($field,$_POST[$field['name']]);
			update_post_meta( $post_id, '_'.$field['name'], $my_data );
		}
		//die();
	}	
	
}
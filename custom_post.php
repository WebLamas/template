<?php

abstract class WeblamasCustomPost{
	//abstract public $name;
	public $has_cats=false;
	abstract function getArgs();
	public function query(){
		$q=new Query();
		//var_dump(get_class(static));
		//var_dump(static::getName());
		$q->post_type(static::$name);
		$q->post_fields=static::customFields();
		return $q;
	}
	public function __construct(){
		add_action( 'init', array($this,'createposttype') );
		add_filter('manage_'.static::$name.'_posts_columns' , array($this,'add_columns'));
		add_action( 'manage_posts_custom_column' , array($this,'custom_columns'), 10, 2 );
		if($this->has_archive){
			add_action( 'admin_menu', array($this,'addarchivedescription') );
			add_action('init',array($this,'archivedescription_save'));

			}
		if($this->has_cats){
			add_filter( 'rewrite_rules_array', array($this,'add_rewrite_rules') );
			add_filter( 'post_type_link', array($this,'filter_post_type_link'), 10, 2 );
			add_filter('post_type_archive_link',array($this,'filter_post_type_archive_link'),10,2);
		}
		$cf=$this->customFields();
		if(!empty($cf)){
			add_action( 'add_meta_boxes', array( $this, 'add_meta_box' ) );
			add_action( 'save_post', array( $this, 'save' ) );
		}
		
	}
	public function custom_columns($column,$post_id){return '';}
	public function add_columns($columns){return $columns;}
	public function customFields(){return array();}
	public function archivedescription_save(){
		if($_REQUEST['page']!=static::$name . '-description' or  $_POST['action']!='save')return;
		$post=$_POST;
		if(function_exists('pll_languages_list')){
			foreach(pll_languages_list() as $lang){
				$post['desc'][$lang]['description']=$post['description'][$lang];
			}
		}
		else{
			$post['desc']['description']=$post['description'];
		}
		$post=$post['desc'];
		update_option('archivedesc_'.static::$name,serialize($post));
		}
	public function addarchivedescription(){
		add_submenu_page('edit.php?post_type=' . static::$name,"Описание для архива","Описание для архива",'edit_posts',static::$name . '-description',array($this,'archivedescription'));	
	}
	public function archivedescription_fields($lang=''){
		$value=get_option('archivedesc_'.static::$name);
		$value=unserialize($value);
		if(!empty($lang)){
			$value=$value[$lang];
			$lang='['.$lang.']';
		}
		
		echo '<h1 class="wp-heading-inline">Описание для архива '.$lang.'</h1>';
		echo '<div><label>Тайтл '.$lang.'</label></div>';
		echo '<div><input type="text" name="desc'.$lang.'[title]" value="'.$value['title'].'"></div>';
		echo '<div><label>Заголовок '.$lang.'</label></div>';
		echo '<div><input type="text" name="desc'.$lang.'[h1]" value="'.$value['h1'].'"></div>';
		echo '<div><label>Мета ключи '.$lang.'</label></div>';
		echo '<div><input type="text" name="desc'.$lang.'[meta_keys]" value="'.$value['meta_keys'].'"></div>';
		echo '<div><label>Мета описание '.$lang.'</label></div>';
		echo '<div><textarea name="desc'.$lang.'[meta_desc]">'.$value['meta_desc'].'</textarea></div>';
		echo '<div><label>Описание '.$lang.'</label></div>';
		wp_editor($value['description'],'description'.$lang);
		}
	public function archivedescription(){
		echo '<div class="wrap">';
		echo '<form method="post">';
		if(function_exists('pll_languages_list')){
		foreach(pll_languages_list() as $lang){
			$this->archivedescription_fields($lang);
			}
		}else{
			$this->archivedescription_fields($lang);
		}
		echo '<button name="action" value="save">Сохранить</button>';
		echo '</form>';
		echo '</div>';
	}
	public function add_rewrite_rules($rules){
		//var_dump($rules);
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

	public function populateArgs($args){
		$q=array(      
			'public' => true,
			'show_ui'  => true,
			'has_archive' => true,
			'menu_position'=>5,
			'query_var'             => true,
			);
		foreach($q as $k=>$v){
			if(!isset($args[$k])){
				$args[$k]=$v;
			}
		}
		return $args;
		
	}
	public function catArgs(){
		return array();
	}

	public function createposttype(){
		register_post_type( static::$name,$this->populateArgs($this->getArgs()));
		$cargs=$this->catArgs();
		if(!empty($cargs)){
			register_taxonomy( static::$name.'_cat', static::$name, $cargs );
		}
	}
	public function add_meta_box(){
			add_meta_box(
				static::$name.'_sectionid',
				"Дополнительные настройки",
				array($this,'meta_box_callback'),
				static::$name,
				'side'
				);
	}
	function meta_box_callback( $post ) {
		wp_nonce_field( static::$name.'_save_meta_box_data', static::$name.'_meta_box_nonce' );
		foreach($this->customFields() as $fieldm){
			$field_value=get_post_meta( $post->ID, '_'.$fieldm['name'], true );
			
			if($fieldm['type']=='json'){
				$fields=$fieldm['fields'];
				if(!empty($field_value)){
					$json_field_value=unserialize($field_value);
				}else{
					$json_field_value=array();
					}
					
				
			}else{
				$fields=array($fieldm);
			}
			foreach($fields as $field){
				if($fieldm['type']=='json'){
					$field_value=$json_field_value[$field['name']];
					$field['name']=$fieldm['name'].'['.$field['name'].']';
				}
				echo '<div><label for="'.$field['name'].'">'.$field['label'].'</label></div><div>';
				if($field['type']=='text'){
					echo '<input type="text" name="'.$field['name'].'" value="'.$field_value.'">';
				}elseif($field['type']=='date'){
					echo '<input type="date" name="'.$field['name'].'" value="'.$field_value.'">';
				}elseif($field['type']=='textarea'){
					echo '<textarea name="'.$field['name'].'">'.$field_value.'</textarea>';
				}elseif($field['type']=='select'){
					echo '<select name="'.$field['name'].'">';
					foreach($field['options'] as $k=>$v){
						echo '<option value="'.$k.'"'.($k==$field_value?' selected':'').'>'.$v.'</option>';
					}
					echo '</select>';
				}elseif($field['type']=='info'){
					if(!empty($field['callback'])){
						echo call_user_func($field['callback'],$post);
					}elseif(!empty($field['html'])){
						echo $field['html'];
					}
				}elseif($field['type']=='mappoint'){
					if(empty($field_value)){
						$field_value=base64_encode('{"lat": 55.75583, "lng": 37.61778}');
					}
					echo '<script>
					function base64_encode( data ) {    // Encodes data with MIME base64
						var b64 = "ABCDEFGHIJKLMNOPQRSTUVWXYZabcdefghijklmnopqrstuvwxyz0123456789+/=";
						var o1, o2, o3, h1, h2, h3, h4, bits, i=0, enc="";
						do { // pack three octets into four hexets
							o1 = data.charCodeAt(i++);
							o2 = data.charCodeAt(i++);
							o3 = data.charCodeAt(i++);
							bits = o1<<16 | o2<<8 | o3;
							h1 = bits>>18 & 0x3f;
							h2 = bits>>12 & 0x3f;
							h3 = bits>>6 & 0x3f;
							h4 = bits & 0x3f;
							enc += b64.charAt(h1) + b64.charAt(h2) + b64.charAt(h3) + b64.charAt(h4);
						} while (i < data.length);
						switch( data.length % 3 ){
							case 1:
								enc = enc.slice(0, -2) + "==";
							break;
							case 2:
								enc = enc.slice(0, -1) + "=";
							break;
						}
						return enc;
					}

										function initMap() {
					
					var location=JSON.parse(\''.base64_decode($field_value).'\');
					  var map = new google.maps.Map(document.getElementById("map"), {
						zoom: 4,
						center: location
					  });
					  console.log(document.getElementById("coords").value);
					  
					  
					  var marker= new google.maps.Marker({position: location, map: map});
					  google.maps.event.addListener(map, "click", function(event) {
						 console.log("click");
						startLocation = event.latLng;
						placeMarker(startLocation);
						});


						function placeMarker(location,map) {
							marker.setPosition(location);
							document.getElementById("coords").value=base64_encode(JSON.stringify(location));
						}
						
					}</script>';
					echo '<div id="map"></div><style>#map{height:300px}</style>';
					//echo '<script src="'.get_template_directory_uri().'/admin.js"></script>';

					echo '<script src="https://maps.googleapis.com/maps/api/js?key=AIzaSyDkYtMcjg1cfV3aBe87ROSV3udADCpu-ZM&signed_in=true&callback=initMap" async defer></script>';
					echo '<input type="text" id="coords" name="'.$field['name'].'" value=\''.$field_value.'\'size="25" /></div>';
				}else{
					echo 'нужно запрограммировать новый тип поля';
				}
				echo '</div>';
			}
		}
	}
	public function modify_field($field,$field_value){
		if($field['type']=='json'){
			var_dump('need_some changes');
			if(is_array($field_value)){
				$field_value=serialize(array_filter($field_value));
			}else{
				$field_value=serialize(array());
			}
			die();
		}
		if($field['type']=='date'){
			$q=new DateTime($field_value);
			return $q->format('Y-m-d');
		}
		return $field_value;
	}
	public function save( $post_id ) {
		if($_POST['post_type']!=static::$name) return;
		if ( ! isset( $_POST[static::$name.'_meta_box_nonce'] ) )return;
		if ( ! wp_verify_nonce( $_POST[static::$name.'_meta_box_nonce'], static::$name.'_save_meta_box_data' ) ) return;
		if ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE )return;
		if ( ! current_user_can( 'edit_post', $post_id ) ) 	return;
		
		foreach($this->customFields() as $field){
			if ( ! isset( $_POST[$field['name']] ) ) continue;
			$my_data=$this->modify_field($field,$_POST[$field['name']]);
			update_post_meta( $post_id, '_'.$field['name'], $my_data );
		}
		die();
	}	
	
}
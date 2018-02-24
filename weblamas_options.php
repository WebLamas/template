<?php 
class WeblamasOptions_parent{
	public static $options=null;
	public function getParams(){
		return [];
	}
	public function __call($name,$arguments){
		$function=explode('_',$name,2);
		if($function[0]=='shortcode'){
			$function=$function[1];
		}else{
			return;
		}
//		var_dump($arguments);
		list($atts,$content,$tag)=$arguments;
		$content=do_shortcode($content);
		$f=get_stylesheet_directory().'/html/mod_'.$function.'.php';
		if(file_exists($f)){
			ob_start();
			include($f);
			$q=ob_get_clean();
//			ob_end(
//			var_dump(ob_get_level());
			return $q;
			}
		else{
			return 'нет файла"'.get_stylesheet_directory().'/html/mod_'.$function.'.php"';
		}
	}
	public function shortcodes(){
		return [];
	}
	public function __construct(){
		add_action('admin_menu', array($this,'menupage'));
		add_action('init',array($this,'save_options'));
		add_action('add_meta_boxes', array($this,'meta_metabox')  );
		add_action('save_post', array($this,'save_meta'));
		add_action('wp_head', array($this,'add_meta_tags'));
		$shortcodes=$this->shortcodes();
		foreach($shortcodes as $code){
			add_shortcode( $code, array($this,'shortcode_'.$code ));
			}
	}
	public function add_meta_tags(){
		if('https://'.$_SERVER['SERVER_NAME']!==get_option('home')&&'http://'.$_SERVER['SERVER_NAME']!==get_option('home')){
			return ;
		}
		if(is_single()||is_page()){
			$field_value=get_post_meta( get_the_ID(), '_meta_tags', true );
			if(empty($field_value)){
				return ;
			}
			$field_value=unserialize($field_value);

			if(!empty($field_value['description'])){
				echo '<meta name="description" content="'.$field_value['description'].'">'.PHP_EOL;
			}
			if(!empty($field_value['keywords'])){
				echo '<meta name="keywords" content="'.$field_value['keywords'].'">'.PHP_EOL;
			}
			if(!empty($field_value['title'])){
				echo '<title>'.$field_value['title'].'</title>';
			}else{
				echo '<title>'.get_the_title().'</title>';
				}
			return true;
		}elseif(is_tax()){
			$field_value=get_term_meta(get_queried_object()->term_id,'_meta_tags',true);
			if(empty($field_value)){
				return;
			}
			$field_value=unserialize($field_value);

			if(!empty($field_value['description'])){
				echo '<meta name="description" content="'.$field_value['description'].'">'.PHP_EOL;
			}
			if(!empty($field_value['keywords'])){
				echo '<meta name="keywords" content="'.$field_value['keywords'].'">'.PHP_EOL;
			}
			if(!empty($field_value['title'])){
				echo '<title>'.$field_value['title'].'</title>';
			}else{
				echo '<title>'.get_the_title().'</title>';
				}
			return true;
		}elseif(is_post_type_archive()){ 
			$field_value=get_option('archivedesc_'.get_queried_object()->name);
			if(empty($field_value))return;
			$field_value=unserialize($field_value);
			if(function_exists('pll_current_language')){
				$field_value=$field_value[pll_current_language()];
			}
			if(!empty($field_value['meta_desc'])){
				echo '<meta name="description" content="'.$field_value['meta_desc'].'">'.PHP_EOL;
			}
			if(!empty($field_value['meta_keys'])){
				echo '<meta name="keywords" content="'.$field_value['meta_keys'].'">'.PHP_EOL;
			}
			if(!empty($field_value['title'])){
				echo '<title>'.$field_value['title'].'</title>';
			}elseif(!empty($field_value['h1'])){
				echo '<title>'.$field_value['h1'].'</title>';
			}else{
				echo '<title>'.get_queried_object()->label.'</title>';
				}
			}
		return false;
		
	}
	public function save_meta($post_id){
		if(!empty($_POST['meta_tags'])&&is_array($_POST['meta_tags']))
		$my_data=serialize($_POST['meta_tags']);
		update_post_meta( $post_id, '_meta_tags', $my_data );
	}
	
	public function meta_metabox(){
		add_meta_box('meta_sectionid',"Настройки",array($this,'meta_metabox_callback'));
	}
	public function meta_metabox_callback($post){
		$field_value=get_post_meta( $post->ID, '_meta_tags', true );

		if(is_array($field_value)){
			$field_value=$field_value;
		}elseif(!empty($field_value)){
			$field_value=unserialize($field_value);
		}else{
			$field_value=array();
			
			
		}
		echo '<div><label>title</label></div><div><input type="text" name="meta_tags[title]" value="'.$field_value['title'].'" style="width:100%"></div>';
		echo '<div><label>meta keywords</label></div><div><input type="text" name="meta_tags[keywords]" value="'.$field_value['keywords'].'" style="width:100%"></div>';
		echo '<div><label>meta description</label></div><div><input type="text" name="meta_tags[description]" value="'.htmlspecialchars($field_value['description']).'" style="width:100%"></div>';
	}

	public function menupage(){
		if(!empty($this->getParams)){
			add_menu_page('Настройки', 'Настройки', 8, 'weblamasoptions', array($this,'options_page'),'',7);
			}
	}
	public static function getValue($name){
		if(empty(self::$options)){
			self::$options=json_decode(get_option('weblamas_options'),true);
		}

		return self::$options[$name];
	}
	public function echoValue($name){
		$v=self::getValue($name);
		if(!empty($v))
			echo $v;
	}
	public function options_page(){
		?>
		<div class="wrap">
		<h2><?php _e('Добавить данные'); ?></h2>
			<form method="post">
				<table class="form-table">
					<?php foreach($this->getParams() as $par):?>
					<tr valign="top">
						<th scope="row"><?php echo $par['title']; ?></th>
						<td>
						<?php if(empty($par['type'])||$par['type']=='text'):?>
						<input class="regular-text" type="text" name="<?php echo $par['name']?>" value="<?php echo self::getValue($par['name'])?>"/>
						<?php elseif($par['type']=='textarea'):?>
								<textarea class="regular-text" name="<?php echo $par['name']?>"><?php echo self::getValue($par['name'])?></textarea>
						<?php else:?>
							неизвестный тип
						<?php endif;?>
						
						
						</td>
					</tr>
					<?php endforeach;?>
					<input type="hidden" name="action" value="save" />
				</table>
				<?php submit_button(); ?>
			</form>

</div>
<?
	}
	public function save_options(){
		if($_POST['action']=='save'&& $_REQUEST['page']=='weblamasoptions'){
			foreach($this->getParams() as $par){
				self::$options[$par['name']]=$_POST[$par['name']];
			}
			update_option('weblamas_options',json_encode(self::$options));
		}
	}
	
}






// ADD FIELD TO CATEGORY TERM PAGE
add_action( 'courses_cat_add_form_fields', '___add_form_field_term_meta_text' );
function ___add_form_field_term_meta_text() { ?>
	<div class="form-field term-meta-text-wrap">
        <label for="term-meta-text">Заголовок страницы в браузере</label>
        <input type="text" name="meta_tags[title]" id="term-meta-text" value="" class="term-meta-text-field" />
    </div>
    <div class="form-field term-meta-text-wrap">
        <label for="term-meta-text">Мета ключи</label>
        <input type="text" name="meta_tags[keywords]" id="term-meta-text" value="" class="term-meta-text-field" />
    </div>
    <div class="form-field term-meta-text-wrap">
        <label for="term-meta-text">Мета описание</label>
       <textarea name="meta_tags[description]" id="description" rows="5" cols="50" class="large-text"></textarea>
    </div>
	<div class="form-field">
		<label for="term-meta-text">Описание на главной</label>
       <textarea name="meta_tags[main_desc]" id="description" rows="5" cols="50" class="large-text"></textarea>
    </div>

<?php }
// ADD FIELD TO CATEGORY EDIT PAGE
add_action( 'courses_cat_edit_form_fields', '___edit_form_field_term_meta_text' );
function ___edit_form_field_term_meta_text( $term ) {
	$value = get_term_meta( $term->term_id, '_meta_tags', true );
	$value1 = get_term_meta( $term->term_id, 'main_desc', true );
	$value=unserialize($value);
 ?>

    <tr class="form-field term-meta-text-wrap">
        <th scope="row"><label for="term-meta-text">Заголовок таблицы в браузере</label></th>
        <td>
            <input type="text" name="meta_tags[title]" id="term-meta-text" value="<?php echo esc_attr( $value['title']); ?>" class="term-meta-text-field"  />
        </td>
    </tr>
	<tr class="form-field term-meta-text-wrap">
        <th scope="row"><label for="term-meta-text">Мета ключи</label></th>
        <td>
            <input type="text" name="meta_tags[keywords]" id="term-meta-text" value="<?php echo esc_attr( $value['keywords']); ?>" class="term-meta-text-field"  />
        </td>
    </tr>
	<tr class="form-field term-meta-text-wrap">
        <th scope="row"><label for="term-meta-text">Мета описание</label></th>
        <td>
            <input type="text" name="meta_tags[description]" id="term-meta-text" value="<?php echo esc_attr( $value['description']); ?>" class="term-meta-text-field"  />
        </td>
    </tr>
	<tr class="form-field term-meta-text-wrap">
        <th scope="row"><label for="term-meta-text">Описание на главной</label></th>
        <td>
			<?php wp_editor(( $value1),'main_desc')?>
        </td>
    </tr>

<?php }
// SAVE TERM META (on term edit & create)
add_action( 'edit_courses_cat',   '___save_term_meta_text' );
add_action( 'create_courses_cat', '___save_term_meta_text' );
function ___save_term_meta_text( $term_id ) {
	if(!empty($_POST['meta_tags'])&&is_array($_POST['meta_tags'])){
	$my_data=serialize($_POST['meta_tags']);
	update_term_meta( $term_id, '_meta_tags', $my_data );
	}
	if(!empty($_POST['main_desc'])){
	update_term_meta( $term_id, 'main_desc', $_POST['main_desc'] );
	}
}


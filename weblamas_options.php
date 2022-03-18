<?php 
class WeblamasOptions_parent{
	public static $options=null;
	public function getParams(){
		return array();
	}
	public function __call($name,$arguments){
		$function=explode('_',$name,2);
		if($function[0]=='shortcode'){
			$function=$function[1];
		}else{
			return;
		}
		list($atts,$content,$tag)=$arguments;
		$content=do_shortcode($content);
		$f=get_stylesheet_directory().'/html/mod_'.$function.'.php';
		if(file_exists($f)){
			ob_start();
			WeblamasTemplate::loadTemplate(['mod_'.$function.'.php']);
			$q=ob_get_clean();
			return $q;
			}
		else{
			return 'нет файла"'.get_stylesheet_directory().'/html/mod_'.$function.'.php"';
		}
	}
	public function shortcodes(){return array();}
	public function __construct(){
		add_filter( 'shortcode_atts_wpcf7',array($this,'title_shortcode'), 10, 3 );
 
		add_action('admin_menu', array($this,'menupage'));
		add_action('init',array($this,'save_options'));
		add_action('add_meta_boxes', array($this,'meta_metabox')  );
		add_action('save_post', array($this,'save_meta'));
		add_action('wp_head', array($this,'add_meta_tags'));
		add_action('wp_head', array($this,'add_jquery_footer'),1000);
		add_action('wp_footer', array($this,'add_jquery_footerf'),1000);
		$shortcodes=$this->shortcodes();
		foreach($shortcodes as $code){
			add_shortcode( $code, array($this,'shortcode_'.$code ));
			}
	}
	public function add_jquery_footer(){
		//http://writing.colin-gourlay.com/safely-using-ready-before-including-jquery/
		echo '<script>(function(w,d,u){w.readyQ=[];w.bindReadyQ=[];function p(x,y){if(x=="ready"){w.bindReadyQ.push(y);}else{w.readyQ.push(x);}};var a={ready:p,bind:p};w.$=w.jQuery=function(f){if(f===d||f===u){return a}else{p(f)}}})(window,document)</script>';
	}
	public function add_jquery_footerf(){
		echo '<script>(function($,d){$.each(readyQ,function(i,f){$(f)});$.each(bindReadyQ,function(i,f){$(d).bind("ready",f)})})(jQuery,document)</script>';
	}
	public function title_shortcode( $out, $pairs, $atts ) {
		$meta=$this->get_meta();
		$out['page_title']=$meta['title'];
		return $out;
	}
	public function get_meta(){
		if(is_home()){
			$field_value=get_post_meta( get_option( 'page_for_posts' ), '_meta_tags', true );
			if(empty($field_value)){
				$field_value=array();
			}else{
				$field_value=unserialize($field_value);
			}
			if(empty($field_value['title'])){
				$field_value['title']==get_the_title();
			}
		}elseif(is_single()||is_page()){
			$field_value=get_post_meta( get_the_ID(), '_meta_tags', true );
			if(empty($field_value)){
				$field_value=array();
			}else{
				$field_value=unserialize(base64_decode($field_value));
			}

			if(empty($field_value['title'])){
				$field_value['title']=get_queried_object()->post_title;
			}
		}elseif(is_author()){
			$field_value['title']=get_queried_object()->display_name;
		}elseif(is_tax()||is_category()||is_tag()){
			//$page=absint( get_query_var( 'paged' ) );
			
			$field_value=get_term_meta(get_queried_object()->term_id,'_meta_tags',true);
			if(empty($field_value)){
				$field_value=array();
			}else{
				$field_value=unserialize(base64_decode($field_value));
			}

			if(empty($field_value['title'])){
				$field_value['title']=get_queried_object()->name;
				}
		}elseif(is_post_type_archive()){ 
			$field_value=get_option('archivedesc_'.get_queried_object()->name);
			$field_value=unserialize(base64_decode($field_value));
			if(empty($field_value))$field_value=[];
			if(function_exists('pll_current_language')){
				$field_value=$field_value[pll_current_language()]??[];
			}else{
				$field_value=$field_value['default'];
			}
			if(empty($field_value['title'])){
				if(!empty($field_value['h1'])){
					$field_value['title']=$field_value['h1'];
				}else{
					$field_value['title']=get_queried_object()->label;
				}
			}
			if(!empty($field_value['meta_desc'])){
				$field_value['description']=$field_value['meta_desc'];
			}
			
			
		}elseif(is_404()){
			$field_value['title']='404';
			}
		$field_value=apply_filters('wl_meta_fields',$field_value);
		if(empty($field_value['title']) || empty($field_value['description'])){
			
			$hfv=get_post_meta( get_option( 'page_on_front' ), '_meta_tags', true );
			if(empty($field_value)){
				$hfv=array();
			}else{
				$hfv=unserialize($hfv);
			}
			
			if(empty($hfv['title'])){
				$hfv['title']=get_the_title(get_option( 'page_on_front' ));
			}
			if(empty($field_value['title'])){
				$field_value['title']=$hfv['title'];
			}
			if(empty($field_value['description'])&&!empty($hfv['description'])){
				$field_value['description']=$hfv['description'];
			}
		}
		return $field_value;
	}


	public function add_meta_tags(){
		echo PHP_EOL.'<!--created by Weblamas-->'.PHP_EOL.'<meta charset="UTF-8"><meta name="viewport" content="width=device-width">';
		$field_value=$this->get_meta();
			if(!empty($field_value['title'])){
				echo '<title>'.strip_tags($field_value['title']).'</title>';
				}
		if(!empty($field_value['description'])){
			echo '<meta name="description" content="'.strip_tags($field_value['description']).'">'.PHP_EOL;
			}
	
		return true;
	}
	public function save_meta($post_id){
		if(!empty($_POST['meta_tags'])&&is_array($_POST['meta_tags'])){
			$post=array_map('stripslashes_deep',$_POST);
			$my_data=base64_encode(serialize($post['meta_tags']));
			update_post_meta( $post_id, '_meta_tags', $my_data );
		}
	
	}
	
	public function meta_metabox(){
		add_meta_box('meta_sectionid',"Настройки",array($this,'meta_metabox_callback'),null,'side');
	}
	public function meta_metabox_callback($post){
		$field_value=get_post_meta( $post->ID, '_meta_tags', true );
		if(is_array($field_value)){
			$field_value=$field_value;
		}elseif(!empty($field_value)){
			$field_value=unserialize(base64_decode($field_value));
		}else{
			$field_value=array();
			
			
		}
		echo '<div><label>Заголовок страницы в браузере</label></div><div><input type="text" name="meta_tags[title]" value="'.htmlspecialchars($field_value['title']).'" style="width:100%"></div>';
		echo '<div><label>Мeta Description</label></div><div><textarea rows=3 name="meta_tags[description]" style="width:100%">'.htmlspecialchars($field_value['description']).'</textarea></div>';
	}

	public function menupage(){
		$par=$this->getParams();
		if(!empty($par)){
			add_menu_page('Настройки', 'Настройки', 'edit_pages', 'weblamasoptions', array($this,'options_page'),'',7);
			}
	}
	public static function getValue($name){
		$l=get_option('frontval_'.$name);
		if(!empty($l)){
			return $l;
		}
		if(empty(self::$options)){
			self::$options=json_decode(get_option('weblamas_options'),true);
		}
		if(empty(self::$options[$name])){
			return '';
		}
		return self::$options[$name];
	}
	public function echoValue($name){
		$v=self::getValue($name);
		if(!empty($v))
			echo $v;
	}
	public static function formatValue($name,$format=''){
		$val=self::getValue($name);
		if(in_array($format,['phone','tel'])){
			$arr = array('(',')',' ','-');
			$val=str_replace($arr,'',$val);
		}elseif(!empty($format)){
			var_dump('unknown formatting');
			die();
		}
		return $val;
	}
	
	public function options_page(){
		?>
		<div class="wrap">
		<h2><?php _e('Добавить данные'); ?></h2>
			<form method="post" class="weblamasoptions">
					<?php foreach($this->getParams() as $par):?>
					<?php if($par['type']=='group'):?>
						<fieldset style="border:1px solid black;padding:10px">
						<legend><?php echo $par['title'];?></legend>
						<?php foreach($par['fields']as $field):?>
							<div>
								<label><?php echo $field['title']; ?></label>
								<div>
								<?php FieldRenderer::render($field,$field['name'],self::getValue($field['name']));?>
								</div>
							</div>
						<?php endforeach;?>
						</fieldset>
						<?php else:?>
					<div>
						<label><?php echo $par['title']; ?></label>
						<div>
						<?php FieldRenderer::render($par,$par['name'],self::getValue($par['name']));?>
						
						</div>
					</div>
					<?php endif;?>
					<?php endforeach;?>
					<input type="hidden" name="action" value="save" />
				</table>
				<?php submit_button(); ?>
			</form>
			<style>
			.weblamasoptions input{
				width:100%;
			}
			.weblamasoptions input[type="checkbox"]{
				width: auto;
			}
			.weblamasoptions input[type="submit"]{
				width: auto;
				font-size: 17px;
				padding: 10px 30px;
			}
			.weblamasoptions textarea{
				width:100%;
				height:50px;
			}
			</style>

</div>
<?php
	}
	public function modify_field($field,$value){
		if(!empty($field['frontedit'])){
			update_option('frontval_'.$field['name'],$value);
			$files = glob(get_stylesheet_directory().'/html_cached/*'); // get all file names
			foreach($files as $file){ // iterate files
			  if(is_file($file)) {
				unlink($file); // delete file
			  }
			}
		}
		return $value;
	}
	public function save_options(){
		if(empty($_POST['action'])||empty($_REQUEST['page']))return;
		if($_POST['action']=='save'&& $_REQUEST['page']=='weblamasoptions'){
			foreach($this->getParams() as $par){
				if($par['type']=='group'){
					foreach($par['fields'] as $fld){
						self::$options[$fld['name']]=$this->modify_field($fld,$_POST[$fld['name']]);
					}
				}else{
					self::$options[$par['name']]=$this->modify_field($fld,$_POST[$par['name']]);
				}
			}
			update_option('weblamas_options',json_encode(self::$options));
		}
	}
	
}

function get_archive_desc(){
	if(is_category()){
		$field['h1']=get_queried_object()->name;
		$field['title']=get_queried_object()->name;
		return $field;
	}
	//var_dump(get_queried_object()->name);
	$field_value=get_option('archivedesc_'.get_queried_object()->name);
	$field_value=unserialize(base64_decode($field_value));
	if(function_exists('pll_current_language')){
		$field_value=$field_value[pll_current_language()]??[];
	}else{
		$field_value=$field_value['default'];
	}
	return $field_value;
}
if(function_exists('pll_current_language')){
	add_filter('wlcp_multiple_descriptions',function($multiple){
		return wp_list_pluck(pll_the_languages(['raw'=>true]),'name','slug');
	});
}

function ___save_term_meta_text( $term_id ) {
	if(!empty($_POST['meta_tags'])&&is_array($_POST['meta_tags'])){
		$post=array_map('stripslashes_deep',$_POST);
		$my_data=base64_encode(serialize($post['meta_tags']));
		update_term_meta( $term_id, '_meta_tags', $my_data );
	}
}
foreach(['category','post_tag'] as $category){

	// ADD FIELD TO CATEGORY TERM PAGE
	add_action( $category.'_add_form_fields', function(){
		?>
		<div class="form-field term-meta-text-wrap">
			<label for="term-meta-text">Заголовок страницы в браузере</label>
			<input type="text" name="meta_tags[title]" id="term-meta-text" value="" class="term-meta-text-field" />
		</div>
		<div class="form-field term-meta-text-wrap">
			<label for="term-meta-text">Мета описание</label>
		   <textarea name="meta_tags[description]" id="description" rows="5" cols="50" class="large-text"></textarea>
		</div>
		<?php
	});
	// ADD FIELD TO CATEGORY EDIT PAGE
	add_action( $category.'_edit_form_fields', function($term){
		$value = get_term_meta( $term->term_id, '_meta_tags', true );
		$value1 = get_term_meta( $term->term_id, 'main_desc', true );
		$value=unserialize(base64_decode($value));
		?>

		<tr class="form-field term-meta-text-wrap">
			<th scope="row"><label for="term-meta-text">Заголовок категории в браузере</label></th>
			<td>
				<input type="text" name="meta_tags[title]" id="term-meta-text" value="<?php echo esc_attr( $value['title']??''); ?>" class="term-meta-text-field"  />
			</td>
		</tr>
		<tr class="form-field term-meta-text-wrap">
			<th scope="row"><label for="term-meta-text">Мета описание</label></th>
			<td>
				<input type="text" name="meta_tags[description]" id="term-meta-text" value="<?php echo esc_attr( $value['description']??''); ?>" class="term-meta-text-field"  />
			</td>
		</tr>

	<?php
	} );
	// SAVE TERM META (on term edit & create)
	add_action( 'edit_'.$category,   '___save_term_meta_text' );
	add_action( 'create_'.$category, '___save_term_meta_text' );

}

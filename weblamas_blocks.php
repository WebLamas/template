<?php 
class WeblamasBlocks{
	static $blocks=[];
	public static function init(){
		add_action('admin_head', array(get_called_class(),'adminStyles'));
		add_filter('render_block',array(get_called_class(),'render_block'),10,2);
		
		
	}
	public static function registerStyle($params){
		if(empty($params['filename'])){
			$params['filename']=$params['name'];
		}
		register_block_style( $params['block'], [ 'name' => $params['name'], 'label' => $params['title'], ] );
		static::$blocks[]=$params;
	}
	public static function adminStyles(){
		echo '<style>';
		$s=implode(',',array_map(function($item){return '.wp-block-group.is-style-'.$item;},wp_list_pluck(static::$blocks,'name')));
		echo $s.'{border: 2px solid #0073aa; position:relative;}';
		$s=implode(',',array_map(function($item){return '.wp-block-group.is-style-'.$item.':before';},wp_list_pluck(static::$blocks,'name')));
		echo $s.'{top:-20px;content:"",height:20px;position:absolute;background:#0073aa;font-size:10px;padding:5px;color:white;font-family:sans-serif;}';
		foreach(static::$blocks as $bl){
			echo '.wp-block-group.is-style-'.$bl['name'].':before{content:"'.htmlspecialchars($bl['title']).'"}';
		}
		echo'</style>';
	}
	public static function render_block($block_content,$block){
		if(empty($block['attrs']['className']))return $block_content;
		$classes=explode(' ',$block['attrs']['className']);
		foreach(static::$blocks as $sblock){
			if($sblock['block']==$block['blockName']&&in_array('is-style-'.$sblock['name'],$classes)){
				$f=get_stylesheet_directory().'/html/block_'.$sblock['filename'].'.php';
				if(file_exists($f)){
					ob_start();
					include($f);
					$q=ob_get_clean();
					return $q;
				}else{
					return 'нет файла.'.$f;
				}
			}
		}
		return $block_content;
	}

		
}

WeblamasBlocks::init();


/*

add_filter('render_block',function($block_content, $block){
	if($block['blockName']!='core/group')return $block_content;
	if(empty($block['attrs']['className']))return $block_content;
	if(in_array($block['attrs']['className'],['is-style-mainscreen-152','is-style-mainscreen-144'])){
		$f=get_stylesheet_directory().'/html/block_mainscreen.php';
		if(file_exists($f)){
			ob_start();
			include($f);
			$q=ob_get_clean();
			return $q;
		
		}
	}
	//var_dump($block['attrs']['className']) ;
	//if($block)
	//var_dump($block);
	return $block_content;
},10,2);
*/
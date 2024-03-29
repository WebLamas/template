<?php 

class WeblamasTemplate{
	public static $templates=array();
	public static function addTemplate($template){
		$templates=self::get_subtemplates();
		$templates[]=$template.'.php';
		self::$templates=$templates;
	}
	public static function cacheTemplate($template,$f_cached){
		$s=self::getFrontEditable($template,'user');
		if(!is_dir(get_stylesheet_directory().'/html_cached/')){
			mkdir(get_stylesheet_directory().'/html_cached/');
		}
		file_put_contents($f_cached,$s);
	}
	public static function setInnerHTML($element, $html){
		$fragment = $element->ownerDocument->createDocumentFragment();
		$html=str_replace('<br>','<br/>',$html);
		$fragment->appendXML($html);
		while ($element->hasChildNodes())
			$element->removeChild($element->firstChild);
		$element->appendChild($fragment);
	}
	public static function getFrontEditable($template,$type){
		$s=file_get_contents(get_stylesheet_directory().'/html/'.$template);
		preg_match_all('/\<\?php (?<code>[\S \r\n\t]+?)\?>/',$s,$codes);
		$codes=(array_unique($codes[0]));
		foreach($codes as $id=>$code){
			$s=str_replace($code,'<php>'.$id.'</php>',$s);
		}
		$dom = new DOMDocument;
		$s=mb_convert_encoding($s, 'HTML-ENTITIES', 'UTF-8');
		$dom->loadHTML('<body>'.$s.'</body>',LIBXML_NOERROR|LIBXML_NOWARNING);
		$xpath = new DOMXPath($dom);
		$nodes = $xpath->query("//*[@data-editable]");
		foreach($nodes as $node) {
			$s=$node->getAttribute('data-editable');
			
			if(is_string($s)){
				if(function_exists('pll_current_language')&&pll_current_language()!=pll_default_language()){
					$s.='_'.pll_current_language();
				}
				$l=get_option('frontval_'.$s);
				if($l!==false){
					self::setInnerHtml($node,stripslashes($l));
				}
			}
			if($type=='admin'){
				$node->setAttribute('contenteditable', 'true');
			}else{
				$node->removeAttribute('data-editable');
			}
		}
		$s=substr($dom->saveHTML($dom->getElementsByTagName('body')->item(0)),6,-7);
		preg_match_all('/&lt;php&gt;(?<id>\d+)&lt;\/php&gt;/',$s,$matches);
		foreach(array_unique($matches['id']) as $id){
			$s=str_replace('&lt;php&gt;'.$id.'&lt;/php&gt;','<php>'.$id.'</php>',$s);
		}
		foreach($codes as $id=>$code){
			$s=str_replace('<php>'.$id.'</php>',$code,$s);
		}
		return $s;
	}

	public static function adminTemplate($template){
		$s=self::getFrontEditable($template,'admin');
		eval('?>'.$s);
	}
	public static function loadTemplate($templates=array()){
		if(empty($templates)){
			$need_frontedit_button=true;
			$templates=self::get_subtemplates();
		}
		foreach(array_reverse($templates) as $t){
			$f=get_stylesheet_directory().'/html/'.$t;
			$f_cached=get_stylesheet_directory().'/html_cached/'.$t;
			if(function_exists('pll_current_language')&&pll_current_language()!=pll_default_language()){
				$f_cached=str_replace('.php','_'.pll_current_language().'.php',$f_cached);
			}
			if(file_exists($f)){
				if( current_user_can('editor') || current_user_can('administrator') ){
					if($need_frontedit_button){
						echo '<div class="frontedit"><span class="frontedit__save">зберегти</span></div>';
					}
					self::adminTemplate($t);
					return;
				}else{
					if(!file_exists($f_cached)||true){
						self::cacheTemplate($t,$f_cached);
					}
					//include($f_fac);
					include($f_cached);
				}
				return;
			}
		}
	}
	public static function get_subtemplates(){
		if(empty(self::$templates)){
		$templates = array();
		if ( is_embed()          && $templates = self::get_embed_template($templates)          ) ;
		if ( is_404()            && $templates = self::get_404_template($templates)            ) ;
		if ( is_search()         && $templates = self::get_search_template($templates)         ) ;
		if ( is_front_page()     && $templates = self::get_front_page_template($templates)     ) ;
		if ( is_home()           && $templates = self::get_home_template($templates)           ) ;
		if ( is_post_type_archive() && $templates = self::get_post_type_archive_template($templates) ) ;
		if ( is_tax()            && $templates = self::get_taxonomy_template($templates)       ) ;
		if ( is_attachment()     && $templates = self::get_attachment_template($templates)     ) ;
		if ( is_single()         && $templates = self::get_single_template($templates)         ) ;
		if ( is_page()           && $templates = self::get_page_template($templates)           ) ;
		if ( is_singular()       && $templates = self::get_singular_template($templates)       ) ;
		if ( is_category()       && $templates = self::get_category_template($templates)       ) ;
		if ( is_tag()            && $templates = self::get_tag_template($templates)            ) ;
		if ( is_author()         && $templates = self::get_author_template($templates)         ) ;
		if ( is_date()           && $templates = self::get_date_template($templates)           ) ;
		if ( is_archive()        && $templates = self::get_archive_template($templates)        ) ;
		$templates = self::get_index_template($templates);
			self::$templates=$templates;
			}
		return self::$templates;
	}

	public static function get_index_template($templates) {
		array_unshift($templates,'index.php');
		return $templates;
	}
	public static function get_404_template($templates) {
		array_unshift($templates,'404.php');
		return $templates;
	}
	public static function get_archive_template($templates) {
		$post_types = array_filter( (array) get_query_var( 'post_type' ) );

		if ( count( $post_types ) == 1 ) {
			$post_type = reset( $post_types );
			array_unshift($templates,"archive-{$post_type}.php");
		}
		array_unshift($templates,'archive.php');
		return $templates;
	}
	public static function get_post_type_archive_template($templates) {
		$post_type = get_query_var( 'post_type' );
		if ( is_array( $post_type ) )
			$post_type = reset( $post_type );

		$obj = get_post_type_object( $post_type );
		if ( ! $obj->has_archive )
			return $templates;
		return self::get_archive_template($templates);
	}
	public static function get_author_template($templates) {
		$author = get_queried_object();

		if ( $author instanceof WP_User ) {
			array_unshift($templates,"author-{$author->user_nicename}.php");
			array_unshift($templates,"author-{$author->ID}.php");
		}
		array_unshift($templates,'author.php');

		return $templates;
	}

	public static function get_category_template($templates) {
		$category = get_queried_object();

		$templates = array();

		if ( ! empty( $category->slug ) ) {

			$slug_decoded = urldecode( $category->slug );
			if ( $slug_decoded !== $category->slug ) {
				array_unshift($templates,"category-{$slug_decoded}.php");
			}

			array_unshift($templates,"category-{$category->slug}.php");
			array_unshift($templates,"category-{$category->term_id}.php");
		}
		array_unshift($templates,'category.php');
		

		return $templates;
	}

	public static function get_tag_template($templates) {
		$tag = get_queried_object();


		if ( ! empty( $tag->slug ) ) {

			$slug_decoded = urldecode( $tag->slug );
			if ( $slug_decoded !== $tag->slug ) {
				array_unshift($templates,"tag-{$slug_decoded}.php");
			}

			array_unshift($templates,"tag-{$tag->slug}.php");
			array_unshift($templates,"tag-{$tag->term_id}.php");
		}
		array_unshift($templates,'tag.php');

		return $templates;
	}

	public static function get_taxonomy_template($templates) {
		$term = get_queried_object();

		if ( ! empty( $term->slug ) ) {
			$taxonomy = $term->taxonomy;

			$slug_decoded = urldecode( $term->slug );
			if ( $slug_decoded !== $term->slug ) {
				array_unshift($templates,"taxonomy-$taxonomy-{$slug_decoded}.php");
			}

			array_unshift($templates,"taxonomy-$taxonomy-{$term->slug}.php");
			array_unshift($templates,"taxonomy-$taxonomy.php");
		}
		array_unshift($templates,'taxonomy.php');

		return $templates;
	}

	public static function get_date_template($templates) {
		array_unshift($templates,'date.php');
		return $templates;
	}

	public static function get_home_template($templates) {
		array_unshift($templates, 'index.php','home.php' );
		
		return $templates ;
	}

	public static function get_front_page_template($templates) {
		array_unshift($templates,'front-page.php');

		return $templates;
	}

	public static function get_page_template($templates) {
		$id = get_queried_object_id();
		$template = get_page_template_slug();
		$pagename = get_query_var('pagename');

		if ( ! $pagename && $id ) {
			// If a static page is set as the front page, $pagename will not be set. Retrieve it from the queried object
			$post = get_queried_object();
			if ( $post )
				$pagename = $post->post_name;
		}

		if ( $template && 0 === validate_file( $template ) )
			array_unshift($templates,$template);
		if ( $pagename ) {
			$pagename_decoded = urldecode( $pagename );
			if ( $pagename_decoded !== $pagename ) {
				array_unshift($templates,"page-{$pagename_decoded}.php");
			}
			array_unshift($templates,"page-$pagename.php");
		}
		if ( $id )
			array_unshift($templates, "page-$id.php");
		array_unshift($templates,'page.php');

		return $templates;
	}
	public static function get_search_template($templates) {
		array_unshift($templates,'search.php');
		return $templates;
	}
	public static function get_single_template($templates) {
		$object = get_queried_object();


		if ( ! empty( $object->post_type ) ) {
			$template = get_page_template_slug( $object );
			if ( $template && 0 === validate_file( $template ) ) {
				array_unshift($templates,$template);
			}

			$name_decoded = urldecode( $object->post_name );
			if ( $name_decoded !== $object->post_name ) {
				array_unshift($templates,"single-{$object->post_type}-{$name_decoded}.php");
			}

			array_unshift($templates,"single-{$object->post_type}-{$object->post_name}.php");
			array_unshift($templates,"single-{$object->post_type}.php");
		}

		array_unshift($templates,"single.php");

		return $templates;
	}

	public static function get_embed_template($templates) {
		$object = get_queried_object();


		if ( ! empty( $object->post_type ) ) {
			$post_format = get_post_format( $object );
			if ( $post_format ) {
				array_unshift($templates,"embed-{$object->post_type}-{$post_format}.php");
			}
			array_unshift($templates,"embed-{$object->post_type}.php");
		}

		array_unshift($templates,"embed.php");

		return $templates;
	}
	public static function get_singular_template($templates) {
		array_unshift($templates,'singular.php');
		return $templates;
	}
	public static function get_attachment_template($templates) {
		$attachment = get_queried_object();

		if ( $attachment ) {
			if ( false !== strpos( $attachment->post_mime_type, '/' ) ) {
				list( $type, $subtype ) = explode( '/', $attachment->post_mime_type );
			} else {
				list( $type, $subtype ) = array( $attachment->post_mime_type, '' );
			}

			if ( ! empty( $subtype ) ) {
				array_unshift($templates,"{$type}-{$subtype}.php");
				array_unshift($templates,"{$subtype}.php");
			}
			array_unshift($templates, "{$type}.php");
		}
		array_unshift($templates,'attachment.php');

		return $templates;
	}

}
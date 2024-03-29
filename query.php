<?php 
class Query{
	public $where=array();
	public $joins=array();
	public $orderby='';
	public $apply_filters=true;
	public $add_files=false;
	public $post_type='';
	public $thumbnail_sizes=array();
	public $include_imagemeta=false;
	public $images_sizes=array();
	public function post_type($post_type){
		$this->where[]='(post_type="'.$post_type.'")';
		$this->post_type=$post_type;
		return $this;
	}
	public function applyFilters($r){
		$this->apply_filters=$r;
		return $this;
	}
	public function fields($params,$can_fake=false){
		if(!is_array($params)){
			$params=array($params);
		}
		foreach($params as $k=>$field){
			if(in_array($field,array('link'))){
				unset($params[$k]);
				$params[]='post_name';
				$params[]='ID';
				$this->fields[]='1 as link';
			}
		}
		foreach($params as $k=>$field){
			if(mb_substr($field,0,6)=='images'){
				$this->images_sizes[]=mb_substr($field,7);
				$params[]='ID';
				unset($params[$k]);
				continue;
			}
		}
		foreach($params as $k=>$field){
			if($field=='files'){
				$this->add_files=true;
				unset($params[$k]);
				continue;
			}
		}
		$params=array_unique($params);
		foreach($params as $k=>$field){
			if(in_array($field,array('post_content','post_title','ID','post_status','post_name','post_excerpt','post_parent','menu_order','post_date'))){
				$this->fields[]=$field;
				unset($params[$k]);
				continue;
			}
		}
		if(empty($params)) return $this;
		foreach($params as $k=>$field){
			foreach($this->post_fields as $pf){
				if($pf['name']==$field){
					$this->fields[]='`pm_'.$field.'`.`meta_value` as `'.$field.'`';
					$this->joins[]='left join `wp_postmeta` `pm_'.$field.'` on (`pm_'.$field.'`.`post_id`=`wp_posts`.`ID` AND `pm_'.$field.'`.`meta_key`="_'.$field.'")';
					unset($params[$k]);
					continue;
				}
			}
		}
		if(empty($params)) return $this;
		foreach($params as $k=>$field){
			if($field=='thumbnail'||mb_substr($field,0,9)=='thumbnail'){
				$this->thumbnail_sizes[]=mb_substr($field,10);
				$field='thumbnail_id';
				$this->fields[]='pm_'.$field.'.meta_value as '.$field;
				$this->joins[]='left join `wp_postmeta` `pm_'.$field.'` on (`pm_'.$field.'`.`post_id`=`wp_posts`.`ID` AND `pm_'.$field.'`.`meta_key`="_'.$field.'")';
				unset($params[$k]);
				continue;
			}
		}
		if(empty($params)) return $this;
		
		if(!empty($params)&&!$can_fake){
			var_dump('error field'.json_encode($params));
			var_dump($this->post_fields);
			return $this;
		}
		return $this;		
	}
	public function orderByRaw($text){
		$this->orderby=$text;
		return $this;
	}
	public function orderBy($param,$order="desc"){
		$this->fields($param,true);
		$this->orderby.=$param.' '.$order;
		return $this;
	}
	public function includeImagemeta(){
		$this->include_imagemeta=true;
		return $this;
	}
	public function whereLang($lang=''){
		if(empty($lang)){
			$lang=pll_current_language();
		}
		$langid=pll_the_languages(['raw'=>1])[$lang]['id'];
		return $this->where('term',$langid);
	}
	public function where($item, $value, $action = '='){ 
		if(in_array($item,array('post_content','post_title','ID','post_status','post_date','post_parent'))){
			$this->where[]='('.$item.$action.'"'.$value.'")';
			return $this;
		}
		foreach($this->post_fields as $pf){
			if($pf['name']==$item){
				$this->fields[]='`pm_'.$item.'`.`meta_value` as `'.$item.'`';
				$this->joins[]='left join `wp_postmeta` `pm_'.$item.'` on (`pm_'.$item.'`.`post_id`=`wp_posts`.`ID` AND `pm_'.$item.'`.`meta_key`="_'.$item.'")';
				$this->where[]='(pm_'.$item.'.meta_value'.$action.'"'.$value.'")';
				return $this;
			}
		}
		if($item=='term'){
			$this->joins[]='left join `wp_term_relationships` `wtr'.((int)$value).'` on `wtr'.((int)$value).'`.object_id=`wp_posts`.`ID` and `wtr'.((int)$value).'`.`term_taxonomy_id`="'.$value.'"';
			$this->where[]='(`wtr'.((int)$value).'`.object_id is not null)';
			return $this;
		}
		var_dump('unknown where');
		var_dump($item);
		var_dump($this->post_fields);
		}
	public function whereIn($item,$vals){
		if(in_array($item,array('ID'))){
			$this->where[]='('.$item.' in ('.implode(',',$vals).'))';
			return $this;
		}
		var_dump('unknown wherin');
		var_dump($item);
		var_dump($this->post_fields);
	}
	public function published(){
		return $this->where('post_status','publish');
	}
	public function limit($limit,$offset=0){
		$this->limit=$offset.','.$limit;
		return $this;
	}
	public function get(){
		global $wpdb;
		$query='select '.implode(',',$this->fields).' from '.$wpdb->posts;	
		if(!empty($this->joins)){
			$query.=' '.implode(' ',array_unique($this->joins));
		}
		if(!empty($this->where)){
			$query.=' where '.implode('AND',$this->where);
		}
		if(!empty($this->orderby)){
			$query.=' order by ' . $this->orderby;
		}
		if(!empty($this->limit)){
			$query.=' limit '.$this->limit;
		}
		//var_dump($query);
		$r= $wpdb->get_results($query);
		$result=array();
		$thumbnails_ids=array();
		foreach($r as $rq){
			$rq=(array)$rq;
			if(!empty($rq['post_content'])&&$this->apply_filters){
				remove_filter('the_content','wpautop');// ������� ������ wpautop
				$rq['post_content'] = apply_filters( 'the_content', $rq['post_content'] );
				add_filter('the_content','wpautop');// ������� ������ wpautop
				$rq['post_content'] = str_replace( ']]>', ']]&gt;', $rq['post_content'] );
	
			}
			//var_dump($rq);
			foreach($this->post_fields as $pf){ //������ ��� json&multiple
				if($pf['type']!='json'&&$pf['type']!='multiple')continue;
				if(empty($rq[$pf['name']])) continue;
				$rq[$pf['name']]=base64_decode($rq[$pf['name']]);
				//var_dump($rq[$pf['name']]);
				$rq[$pf['name']]=maybe_unserialize($rq[$pf['name']]);
				//?????????????
				$rq[$pf['name']]=maybe_unserialize($rq[$pf['name']]);
				//var_dump($rq[$pf['name']]);
				
			}
			if(isset($rq['link'])){
				if(has_filter('post_type_link')||$this->post_type=='post'){ //����� ������� ��� ���������
					$rq['link']=get_permalink($rq['ID']);
				}else{
					global $wp_rewrite;
					$post_link = $wp_rewrite->get_extra_permastruct($this->post_type);
					$post_link = str_replace("%$this->post_type%", $rq['post_name'], $post_link);
					$post_link = home_url( user_trailingslashit($post_link) );
					$rq['link']=$post_link;
				}
				
			}
			if(!empty($rq['thumbnail_id'])){
				$thumbnails_ids[]=$rq['thumbnail_id'];
			}
			$result[]=$rq;
		}
		
		$image_files=['image/jpeg','image/png'];
		if(!empty($this->images_sizes)){
			if($this->include_imagemeta){
				$fields=',post_title,post_content,post_excerpt';
			}else{
				$fields='';
			}
			$sql='select post_parent,menu_order,ID'.$fields.','.$wpdb->postmeta.'.meta_value 
			from wp_posts 
			right join '.$wpdb->postmeta.' on (wp_posts.ID='.$wpdb->postmeta.'.post_id and '.$wpdb->postmeta.'.meta_key="_wp_attachment_metadata") 
			where post_mime_type in("'.implode('","',$image_files).'")and post_parent in ('.implode(',',wp_list_pluck($result,'ID')).') 
			order by menu_order';
			$img_r=$wpdb->get_results($sql,ARRAY_A);
			$images=[];
			foreach($img_r as $img){
				$image=$this->metadata_to_images($img['meta_value'],$this->images_sizes,$img['ID'],'image');
				if(isset($img['post_title'])){
					$image['title']=$img['post_title'];
					$image['description']=$img['post_content'];
					$image['caption']=$img['post_excerpt'];
				}
				$images[$img['post_parent']][]=$image;
			}
			foreach($result as $k=>$v){
				if(!empty($images[$v['ID']])){
					$v['images']=$images[$v['ID']];
					$result[$k]=$v;
				}
			}
		}
		if(!empty($thumbnails_ids)){
			$sql='select post_id,meta_value from '.$wpdb->postmeta.' where meta_key="_wp_attachment_metadata" and post_id in ('.implode(',',$thumbnails_ids).')';
			$thumbnails=$wpdb->get_results($sql);
			$thumbnails=wp_list_pluck($thumbnails,'meta_value','post_id');
			foreach($result as $k=>$v){
				if(!empty($thumbnails[$v['thumbnail_id']])){
					$image=$this->metadata_to_images($thumbnails[$v['thumbnail_id']],$this->thumbnail_sizes,$v['thumbnail_id'],'thumbnail');
					foreach($image as $ik=>$iv){
						$v[$ik]=$iv;
					}
					$result[$k]=$v;
				}
			}
		}
		if($this->add_files){
			$sql='select *,post_title,guid,post_parent,menu_order,ID from wp_posts where post_type="attachment" and post_parent in ('.implode(',',wp_list_pluck($result,'ID')).') and post_mime_type not in("'.implode('","',$image_files).'") order by menu_order';
			$files=$wpdb->get_results($sql,ARRAY_A);
			//var_dump($files);
			foreach($files as $file){
				$files_r[$file['post_parent']][]=['title'=>$file['post_title'],'link'=>$file['guid']];
			}
			foreach($result as $k=>$v){
				if(!empty($files_r[$v['ID']])){
					$v['files']=$files_r[$v['ID']];
					$result[$k]=$v;
				}
			}
		}
		return $result;
	}
	private function metadata_to_images($metadata,$sizes,$ID,$prefix='thumbnail'){
		global $wpdb;
		$result=[];
		$th=maybe_unserialize($metadata);
		$upload_dir=wp_upload_dir();
		if(empty($th['file'])){
			$th['file']=$wpdb->get_var('select meta_value from '.$wpdb->postmeta.' where meta_key="_wp_attached_file" and post_id='.$ID);
		}
		foreach($sizes as $size){
			if(!empty($th['sizes'][$size])){
				// ��������� ��������, ����� ��������� �������
				$image=[$upload_dir['baseurl'].'/'.pathinfo($th['file'],PATHINFO_DIRNAME).'/'.$th['sizes'][$size]['file'],$th['sizes'][$size]['width'],$th['sizes'][$size]['height']];
				$image=apply_filters('wp_get_attachment_image_src',$image,$ID,$size,false);
				$result[$prefix.'_'.$size]=$image[0];
				$result[$prefix.'_'.$size.'_obj']=$image;
			}else{
				$global_sizes=wp_get_additional_image_sizes();
				if($global_sizes[$size]['width']>$th['width']&&$global_sizes[$size]['height']>$th['height']){
					$image=[$upload_dir['baseurl'].'/'.$th['file'],$th['width'],$th['height']];
					$image=apply_filters('wp_get_attachment_image_src',$image,$ID,$size,false);
					$result[$prefix.'_'.$size]=$image[0];
					$result[$prefix.'_'.$size.'_obj']=$image;
				}else{
					$r=image_downsize($ID,$size);
					if(!empty($r)){
						$image=apply_filters('wp_get_attachment_image_src',$r,$ID,$size,false);
						$result[$prefix.'_'.$size]=$image[0];
						$result[$prefix.'_'.$size.'_obj']=$image;
					}
				}
			}
		}
		$image=[$upload_dir['baseurl'].'/'.$th['file'],$th['width'],$th['height']];
		$image=apply_filters('wp_get_attachment_image_src',$image,$ID,'full',false);
		$result[$prefix]=$image[0];
		$result['id']=$ID;
		return $result;
	}
	public function first(){
		$items=$this->limit(1)->get();
		return reset($items);
	}
	public function count(){
		global $wpdb;
		$query='select count(*)as cnt from '.$wpdb->posts;	
		if(!empty($this->joins)){
			$query.=' '.implode(' ',$this->joins);
		}
		if(!empty($this->where)){
			$query.=' where '.implode('AND',$this->where);
		}
		if(!empty($this->orderby)){
			$query.=' order by ' . $this->orderby;
		}
		if(!empty($this->limit)){
			$query.=' limit '.$this->limit;
		}
		
		$r= $wpdb->get_results($query);
		
		//var_dump($query);
		return $r[0]->cnt;
		
	}
}
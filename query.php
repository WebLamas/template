<?php 
class Query{
	public $where=[];
	public $joins=[];
	public $orderby='';
	public function post_type($post_type){
		$this->where[]='(post_type="'.$post_type.'")';
		return $this;
	}
	public function fields($params){
		foreach($params as $k=>$field){
			if(in_array($field,['post_content','post_title','ID','post_status'])){
				$this->fields[]=$field;
				unset($params[$k]);
				continue;
			}
		}
		if(empty($params)) return $this;
		foreach($params as $k=>$field){
			foreach($this->post_fields as $pf){
				if($pf['name']==$field){
					$this->fields[]='pm_'.$field.'.meta_value as '.$field;
					$this->joins[]='left join wp_postmeta pm_'.$field.' on pm_'.$field.'.post_id=wp_posts.ID';
					$this->where[]='(pm_'.$field.'.meta_key="_'.$field.'")';
					unset($params[$k]);
					continue;
				}
			}
		}
		if(empty($params)) return $this;
		foreach($params as $k=>$field){
			if($field=='thumbnail'){
				$field='thumbnail_id';
				$this->fields[]='pm_'.$field.'.meta_value as '.$field;
				$this->joins[]='left join wp_postmeta pm_'.$field.' on pm_'.$field.'.post_id=wp_posts.ID';
				$this->where[]='(pm_'.$field.'.meta_key="_'.$field.'")';
				unset($params[$k]);
				continue;
			}
		}
		if(empty($params)) return $this;
		
		if(!empty($params)){
			var_dump('error field');
			return $this;
		}
		if(empty($params)) return $this;
		
	}
	public function orderBy($param,$order="desc"){
		$this->orderby.=$param.' '.$order;
		return $this;
	}
	public function where($item, $value, $action = '='){ 
		if(in_array($item,['post_content','post_title','ID','post_status'])){
			$this->where[]='('.$item.$action.'"'.$value.'")';
			return $this;
		}
		foreach($this->post_fields as $pf){
			if($pf['name']==$item){
				$this->fields[]='pm_'.$item.'.meta_value as '.$item;
				$this->joins[]='left join wp_postmeta pm_'.$item.' on pm_'.$item.'.post_id=wp_posts.ID';
				$this->where[]='(pm_'.$item.'.meta_key="_'.$item.'" AND pm_'.$item.'.meta_value'.$action.'"'.$value.'")';
				return $this;
			}
		}
		var_dump('unknown where');
		var_dump($item);
		var_dump($this->post_fields);
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
		//var_dump($this);
		//var_dump($query);
		$r= $wpdb->get_results($query);
		$result=[];
		$thumbnails_ids=[];
		foreach($r as $rq){
			$rq=(array)$rq;
			if(!empty($rq['post_content'])){
				$rq['post_content'] = apply_filters( 'the_content', $rq['post_content'] );
				$rq['post_content'] = str_replace( ']]>', ']]&gt;', $rq['post_content'] );
	
			}
			foreach($this->post_fields as $pf){
				if($pf['type']!='json')continue;
				if(empty($rq[$pf['name']])) continue;
				//var_dump($rq[$pf['name']]);
				$rq[$pf['name']]=unserialize($rq[$pf['name']]);
				//?????????????
				$rq[$pf['name']]=unserialize($rq[$pf['name']]);
				//var_dump($rq[$pf['name']]);
				
			}
			if(!empty($rq['thumbnail_id'])){
				$thumbnails_ids[]=$rq['thumbnail_id'];
			}
			$result[]=$rq;
		}
		if(!empty($thumbnails_ids)){
			$sql='select ID,guid from '.$wpdb->posts.' where ID in ('.implode(',',$thumbnails_ids).')';
			$thumbnails=$wpdb->get_results($sql);
			$thumbnails=wp_list_pluck($thumbnails,'guid','ID');
			//$rq['thumbnail']=$q[0]->guid;
			foreach($result as $k=>$v){
				if(!empty($thumbnails[$v['thumbnail_id']])){
					$v['thumbnail']=$thumbnails[$v['thumbnail_id']];
					$result[$k]=$v;
				}
			}
		}
		
		//var_dump($result);
		return $result;
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
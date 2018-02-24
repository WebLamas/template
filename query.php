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
	public function where($item,$value){
		if(in_array($item,['post_content','post_title','ID','post_status'])){
			$this->where[]='('.$item.'="'.$value.'")';
			return $this;
		}
		foreach($this->post_fields as $pf){
			if($pf['name']==$item){
				$this->fields[]='pm_'.$item.'.meta_value as '.$item;
				$this->joins[]='left join wp_postmeta pm_'.$item.' on pm_'.$item.'.post_id=wp_posts.ID';
				$this->where[]='(pm_'.$item.'.meta_key="_'.$item.'" AND pm_'.$item.'.meta_value="'.$value.'")';
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
		//var_dump($query);
		$r= $wpdb->get_results($query);
		$result=[];
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
				$sql='select guid from '.$wpdb->posts.' where ID='.$rq['thumbnail_id'];
				$q=$wpdb->get_results($sql);
				$rq['thumbnail']=$q[0]->guid;
			}
			$result[]=$rq;
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
		//var_dump($query);
		$r= $wpdb->get_results($query);
		
		//var_dump($query);
		return $r[0]->cnt;
		
	}
}
<?php

class CustomTableQuery{
	public $tablefields=array();
	public $fields=array();
	public $where=array();
	public $joins=array();
	public $orderby='';
	public $table='';
	public function orderBy($param,$order="desc"){
		$this->orderby.=$param.' '.$order;
		return $this;
	}
	public function orderByRaw($orderby){
		$this->orderby=$orderby;
		return $this;
	}
	public function fields($fld){ 
		if (!empty($fld)){
			$this->fields = array_merge($this->fields, $fld);
			}
		return $this;
	}
	public function where($item, $value, $action = '='){ 
		$this->where[]='('.$item.' '.$action.' "'.$value.'")';
		return $this;
	}
	public function limit($limit,$offset=0){
		$this->limit=$offset.','.$limit;
		return $this;
	}
	public function whereRaw($where){
		$this->where[]='('.$where.')';
		return $this;
	}
	public function get(){
		global $wpdb;
		if (empty($this->fields)){
			$fld = "*";
		}else{
			$fld = implode(',',$this->fields);
		}
		$sql="select $fld from ".$this->table;	
		if(!empty($this->joins)){
			$sql.=' '.implode(' ',$this->joins);
		}
		if(!empty($this->where)){ 
			$sql.=' where '.implode('AND',$this->where);
			}
		if(!empty($this->orderby))	{ $sql.=' order by ' . $this->orderby; }
		if(!empty($this->limit))	{ $sql.=' limit '.$this->limit; }
		return $wpdb->get_results($sql, 'ARRAY_A' );
	}
	public function first(){
		$items=$this->limit(1)->get();
		return reset($items);
	}
	public function join($join){
		$this->joins[]=$join;
		return $this;
	}
	public function count(){
		global $wpdb;
		$query='select count(*)as cnt from '.$this->table;	
		if(!empty($this->joins)){
			$query.=' '.implode(' ',$this->joins);
		}
		if(!empty($this->where)){
			$query.=' where '.implode('AND',$this->where);
		}
		//var_dump($query);
		$r= $wpdb->get_results($query);
		
		//var_dump($query);
		return $r[0]->cnt;
		
	}
}
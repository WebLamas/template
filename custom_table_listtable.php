<?php 
if( ! class_exists( 'WP_List_Table' ) ) {
    require_once( ABSPATH . 'wp-admin/includes/class-wp-list-table.php' );
}


class wlListTable extends WP_List_Table{

	public $cTable;
	
	function __construct($cTbl) {
		parent::__construct( array(
			'singular'	=> 	__( 'Данные', 'analytics' ), 	//Singular label
			'plural' 	=> 	__( 'Данные', 'analytics' ), 	//plural label, also this well be one of the table css class
			'ajax'   	=> 	false 							//We won't support Ajax for this table
		) );
	$this->cTable = $cTbl;  
	}
	protected function get_views() { 
		$tc=$this->cTable; 
		$status_links = array(
			"all"       => __("<a href='".add_query_arg('status','all')."'>Все (".$tc::count(['status'=>'all']).")</a>",'my-plugin-slug'),
		);
		return $status_links;
	}
		
	function prepare_items() {
	  $Tc = $this->cTable; 
	  $columns = $this->get_columns();
	  $hidden = array();
	  $sortable = array();
//	  $sortable = $this->get_sortable_columns();
	  $this->_column_headers = array($columns, $hidden, $sortable);
	  $page=$_GET['paged'];
	  if(empty($page)){
		  $page=1;
	  }
	  $per_page=100;
	  $this->items = $Tc::get_list_table($per_page,$page,$_GET);
	  $this->set_pagination_args( array(
		'total_items' => $Tc::count($_GET),
		'per_page'    => $per_page 
	  ) );
	}
	public function get_columns() {
		$Tc = $this->cTable; 
		$columns['cb'] = '<input type="checkbox" />';
		foreach($Tc::$tablefields as $f){ 
			if($f['type']=='editor')continue;
			$columns[$f['name']] = $f['label'];
			}
		return $columns;
	}
	public function column_default( $item, $column_name ) {
		$Tc = $this->cTable; 
		if( $column_name === $Tc::$tablefields[0]['name'] ) {
			$actions = array(
				'edit' => sprintf('<a href="?page=%s&action=%s&id=%s">Редактировать</a>',$_REQUEST['page'],'edit',$item['id']),
				'delete' => sprintf('<a href="?page=%s&action=%s&id=%s">Удалить</a>',$_REQUEST['page'],'delete',$item['id']),
				);
			return esc_html( $item[$column_name] ) . $this->row_actions( $actions ); 
		}else{
			return esc_html( $item[$column_name] );
		}
//		return sprintf('%1$s %2$s', $item[$column_name], $this->row_actions($actions) );
//		return $item[$column_name];
	}
	public function column_cb( $item ) {
		return sprintf(
			'<input type="checkbox" name="bulk-delete[]" value="%s" />', $item['id']
		);
	}
	
}
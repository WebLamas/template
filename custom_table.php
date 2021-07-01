<?php
/**
 *
 * custom_table classes
 *
*/ 
 
if( ! class_exists( 'WP_List_Table' ) ) {
    require_once( ABSPATH . 'wp-admin/includes/class-wp-list-table.php' );
}
//================================================================================================
class CustomTableQuery{
	public $tablefields=array();
	public $fields=array();
	public $where=array();
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
//================================================================================================
abstract class wlCustomTable {
	public static $timercheck = 0;
	public static $version = '1.0';
	public static $tablename = array('label'=>'Заголовок','add'=>'Добавить данные','name'=>'TableName');
	public static $tablefields = array( 		
			array('label'=>'Поле1','type'=>'text','name'=>'Name1','db_type'=>'varchar(255)',),
			array('label'=>'Поле1','type'=>'text','name'=>'Name1','db_type'=>'varchar(255)',),
			array('label'=>'ПолеN','type'=>'text','name'=>'NameN','db_type'=>'varchar(255)',),
			);
	public function __construct() {  static::wl_db_check();  }	
	public static function init() {
		static::wl_db_check();  
	}	
	public static function wl_db_check() {
		global $wpdb;
		static::wl_db_check_version();
	}
	public static function wl_db_check_version() {
		return;
		$v_name = 'wl_db_tables';
		$my_versions = json_decode(get_option( $v_name ), true); 
		$cur_class = get_called_class();
		if( static::$version == $my_versions[$cur_class] ) return;
		static::createTable();
		$my_versions[$cur_class] = static::$version;
		update_option($v_name, json_encode($my_versions)); 
	}
	public static function createTable(){
		global $wpdb;
		$table_name = $wpdb->prefix . static::$tablename['name'];
		$charset_collate = $wpdb->get_charset_collate();
		$sql = "CREATE TABLE $table_name (".PHP_EOL ."id bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT,".PHP_EOL;
		foreach(static::$tablefields as $f){ 
			$fn = $f['name'];
			$ft = $f['db_type'];
			if(!empty($f['nullable'])&&$f['nullable']==true){
				$null='';
			}else{
				$null='NOT NULL';
			}
			$sql .= " $fn $ft $null,".PHP_EOL;
			}
		$sql .= " PRIMARY KEY  (id)".PHP_EOL .") $charset_collate;";
		require_once( ABSPATH . 'wp-admin/includes/upgrade.php' );
		var_dump($sql);
//		var_dump($sql);var_dump(dbDelta ($sql));
		dbDelta ($sql);
	}
//-------------------------------------------------------------------------------------	
	public static function compare_fields(){
		global $wpdb;
		$table = $wpdb->prefix . static::$tablename['name'];
		$dbfields = $wpdb->get_col("DESC {$table}", 0);
		$myfields = wp_list_pluck( static::$tablefields, 'name' );
		$myfields[] = "id";
		$diff = array_diff( $myfields, $dbfields );
		if( !empty($diff) ) { 
			echo 'Not Enough Fields in : ' . $table ." -> "; var_dump($diff); wp_die(); }
		$column = array_diff( $dbfields, $myfields );
		return;
	}
//-------------------------------------------------------------------------------------	
	public static function query(){
		global $wpdb;
		$q = new CustomTableQuery();
		$q->table = $wpdb->prefix . static::$tablename['table'];
		$q->tablefields = wp_list_pluck( static::$tablefields, 'name' );
		return $q;
	}
	public static function get_single($id){
		$result = static::query()->where( 'id', $id )->get();
		return reset($result);
	}
	public static function get_single_by_field($field, $data){
		$result = static::query()->where( $field, $data )->get();
		return reset($result);
	}
	private static function get_query($get=''){
		if(empty($get)){
			$get=$_GET;
		}
		$result = static::query();
			if(!empty($get['s'])){
				$result=$result->where('address','%'.$get['s'].'%','like');
			}
			if(!empty($get['status'])){
				if($get['status']=='error'){
					$result=$result->where('status','1');
				}elseif($get['status']=='queue'){
					$result=$result->whereRaw('status in(0,2)');
				}elseif($get['status']=='deleted'){
					$result=$result->where('deleted',1);
				}elseif($get['status']=='bad_coords'){
					$result=$result->where('status',3);
				}elseif($get['status']=='good_coords'){
					$result=$result->where('status',4);
				}elseif($get['status']=='far'){
					$result=$result->where('status',5);
				}
				
			}
		return $result;
	}
	public static function get_analytics( $per_page = 10, $page_number = 1 ) {
		if($per_page){
			$result = static::get_query();
			
			$result = $result->limit( $per_page, ($page_number-1)*$per_page )->get();
		}else{
			$result = static::query()->get();
		}
		return $result;
	}
	public static function rec_count($get=''){
		return  static::get_query($get)->count();
	}
	public static function table_check( $data ) {
		global $wpdb;
//		$sql = $wpdb->prepare( 'query' [ parameter1, parameter2 ... ] );		
		foreach($data as $i => $d) $data[$i] = esc_sql( $d );
		return $data; }
	/**
	 * Delete a customer record.
	 * Update a customer record.
	 * Insert a customer record.
	 * @param int $id customer ID
	 */
	public static function update( $data, $id ) {
		global $wpdb;
		return $wpdb->update( $wpdb->prefix . static::$tablename['table'], $data, array( 'id' => $id )); }
	public static function insert( $data ) {
		global $wpdb;
		$wpdb->insert( $wpdb->prefix . static::$tablename['name'], $data ); 
		return $wpdb->insert_id; }
	public static function delete( $id ) {
		global $wpdb;
		return $wpdb->delete( $wpdb->prefix . static::$tablename['name'], array( 'id' => $id ), array( '%d' ) ); }
//-------------------------------------------------------------------------------------	
	public static function get_label($name){ 
		foreach (static::$tablefields as $tf) {
			if ($tf['name'] == $name) return $tf['label'];
		}
		return "Непонятное поле";
	}
	public static function get_options($name){ 
		foreach (static::$tablefields as $tf) {
			if ($tf['name'] == $name) return $tf['options'];
		}
		return "Непонятное поле";
	}
	public static function in_table($name){ 
		foreach (static::$tablefields as $tf) {
			if ($tf['name'] == $name) return true;
		}
		return false;
	}
	public static function get_option_label($name1,$name2){ 
		foreach (static::$tablefields as $tf) {
			if ($tf['name'] == $name1) {
				foreach ($tf['options'] as $name => $label) {
					if ($name == $name2) return $label;
				}
			}	
		}
		return "Непонятное поле";
	}
	public static function save_row_by_post_id( $post_id, $data ) {
		$ids = static::query()->fields(array('id'))->where('post_id',$post_id)->get();	
		$data['post_id'] = $post_id;
		if( count($ids) == 1 ) static::update( $data, $ids[0]['id'] );
		else static::insert( $data );
		return;
	}
}
//================================================================================================
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
			"all"       => __("<a href='".add_query_arg('status','all')."'>Все (".$tc::rec_count(['status'=>'all']).")</a>",'my-plugin-slug'),
			"good_coords"   => __("<a href='".add_query_arg('status','good_coords')."'>Точная координата (".$tc::rec_count(['status'=>'good_coords']).")</a>",'my-plugin-slug'),
			"bad_coords"   => __("<a href='".add_query_arg('status','bad_coords')."'>Неточная координата (".$tc::rec_count(['status'=>'bad_coords']).")</a>",'my-plugin-slug'),
			"no_coords" => __("<a href='".add_query_arg('status','error')."'>Ошибка (".$tc::rec_count(['status'=>'error']).")</a>",'my-plugin-slug'),
			"in_queue"   => __("<a href='".add_query_arg('status','queue')."'>В очереди (".$tc::rec_count(['status'=>'queue']).")</a>",'my-plugin-slug'),
			"far"   => __("<a href='".add_query_arg('status','far')."'>Определились далеко (".$tc::rec_count(['status'=>'far']).")</a>",'my-plugin-slug'),
			"deleted"   => __("<a href='".add_query_arg('status','deleted')."'>Для теста (".$tc::rec_count(['status'=>'deleted']).")</a>",'my-plugin-slug'),
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
	  $this->items = $Tc::get_analytics(10,$page);
	  $this->set_pagination_args( array(
		'total_items' => $Tc::rec_count(),
		'per_page'    => 10  
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
			return esc_html( $item[$column_name] ) . $this->row_actions( $actions ); }
		else return esc_html( $item[$column_name] );
//		return sprintf('%1$s %2$s', $item[$column_name], $this->row_actions($actions) );
//		return $item[$column_name];
	}
	public function column_cb( $item ) {
		return sprintf(
			'<input type="checkbox" name="bulk-delete[]" value="%s" />', $item['id']
		);
	}
	
}
//================================================================================================
class wlOutputTable {

	public $cTable;

	public function __construct($cTbl) {
		add_action( 'admin_menu', array( $this, 'plugin_menu' ) );
		add_action('init',array($this,'add_edit'));
		$this->cTable = $cTbl; 
	}

	public function add_edit(){
		$Tc = $this->cTable; 
		if($_POST['action']=='save'&& $_GET['page']==$Tc::$tablename['name']&&($_GET['action']=='edit'||$_GET['action']=='add')){
			$_POST = stripslashes_deep( $_POST );
			foreach($Tc::$tablefields as $f){ 
// !!!!!!! ====== строка ниже применяется когда поле checkbox пишется в int !!!!  если в bit - то коментируется
				if(($f['type']=='checkbox')&&($_POST[$f['name']] == 'on')) $data[$f['name']] = '1'; else
				$data[$f['name']] = (string)$_POST[$f['name']];
				}
			if($_GET['action']=='add'){
				$insert_id = $Tc::insert( $data );
				wp_redirect(sprintf('?page=%s&action=%s&id=%s',$_REQUEST['page'],'edit',$insert_id));
				}
			elseif($_GET['action']=='edit'){
				$Tc::update( $data, $_GET['id'] );
				}
		}
		if($_GET['action']=='delete'){
			$Tc::delete( $_GET['id'] );
			}

	}

	public function plugin_menu() {
		$Tc = $this->cTable;  
		add_menu_page($Tc::$tablename['label'], 
					$Tc::$tablename['label'], 8, 
					$Tc::$tablename['name'], 
					array($this,'render_page'),'dashicons-list-view',"16.3");
	}
	/**
	 * Plugin settings page
	 */
	public function render_page(){
		if($_GET['action']=='add'||$_GET['action']=='edit'){
			return $this->edit_page();
		}
		return $this->options_page();
		
	}

	public function edit_page(){
		$Tc = $this->cTable; 
		$element=array();
		if($_GET['action']=='add'){
			foreach( $Tc::$tablefields as $fld ){
				$element[$fld['name']]='';
			}
		}else{
			$element=$Tc::get_single($_GET['id']);
		}
		?>
		<div class="wrap">
		<h1><?php echo $Tc::$tablename['label'] ?></h1>
		<h2><?php _e($Tc::$tablename['add']); ?></h2>
				<form method="post">
					<table class="form-table">
						<?php foreach($Tc::$tablefields as $fld):?>
						<tr valign="top">
							<th scope="row"><?php echo $fld['label'] ?></th>
							<td><?php 
							$value = htmlspecialchars($element[$fld['name']]);
							FieldRenderer::render($fld,$fld['name'],$value);?>
							
							</td>
						</tr>
						<?php endforeach;?>
					</table>
						<input type="hidden" name="action" value="save" />
					<?php submit_button(); ?>
				</form>
		</div>
		<?php
	}
	public function options_page() {
		$Tc = $this->cTable; 
		?>
		<div class="wrap">
			<h2>
				<?php echo $Tc::$tablename['label'] ?>&nbsp;
				<?php echo sprintf( '<a href="'.get_admin_url().'options.php?page='.$Tc::$tablename['name'].'&action=add" class="page-title-action" >'.$Tc::$tablename['add'].'</a>&nbsp;'); ?>
				<form method="post" style="display:inline" class="upload_adresses" enctype="multipart/form-data">
					<label style="cursor:pointer" class="page-title-action"><input type="file" name="file" style="display:none">Загрузить файл</label>
					<input type="hidden" name="action" value="upload_adresses">
				</form>
				<script>
					jQuery(document).ready(function(){
						jQuery('.upload_adresses input').change(function(){
							jQuery(this).closest('form').submit();
						});
					});
				</script>
			</h2>
			<?php $myListTable = new wlListTable($this->cTable);
			$myListTable->views();
			?>
			<form method="get">
			
			 <?php
			 $myListTable->prepare_items(); 
			 $myListTable->search_box('поиск','wl_search'); 
			$myListTable->display(); ?>
				<?php //$this->analytics_obj->display(); ?>
				<input type="hidden" name="page" value="<?php echo $_GET['page'] ?>"/>
			</form>
		</div>
	<?php
	}
}

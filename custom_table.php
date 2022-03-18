<?php
/**
 *
 * custom_table classes
 *
 */ 
include('custom_table_query.php');
include('custom_table_listtable.php');

abstract class wlCustomTable {
	public static $version = '1.0';
	public static $tablename = array('label'=>'Заголовок','add'=>'Добавить данные','name'=>'TableName');
	public static $tablefields = array( 		
			array('label'=>'Поле1','type'=>'text','name'=>'Name1','db_type'=>'varchar(255)',),
			);
	public static function init() {
		if(!is_admin())return;
		$v_name = 'wl_db_tables';
		$my_versions = json_decode(get_option( $v_name ), true); 
		$cur_class = get_called_class();
		if( static::$version == $my_versions[$cur_class] ) return;
		static::createTable();
		$my_versions[$cur_class] = static::$version;
		update_option($v_name, json_encode($my_versions)); 
	}
	public static function createTable(){
		$sql=static::createTableQuery();
		require_once( ABSPATH . 'wp-admin/includes/upgrade.php' );
		dbDelta ($sql);
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
		return $result;
	}
	public static function get_list_table( $per_page = 10, $page_number = 1, $params = []) {
		$result = static::get_query($params);
		$result_r = $result->limit( $per_page, ($page_number-1)*$per_page )->get();
		$result=[];
		foreach($result_r as $row){
			$new_row=['id'=>$row['id']];
				
			foreach(static::$tablefields as $field){
				$new_row[$field['name']]=static::prepare_field($row[$field['name']],$field);
			}
			$result[]=$new_row;
		}
		return $result;
	}
	public static function prepare_field($value,$field){
		if(empty($value))return '';
		if(in_array($field['type'],['text']))return $value;
		global $wpdb;
		if($field['type']=='custom_table'){
			return $wpdb->get_var('select '.$field['title'].' as title from '.$wpdb->prefix.$field['table'].' where id='.$value);
		}
		if($field['type']=='post_type'){
			return $wpdb->get_var('select concat(post_title,if(post_status="draft","(Черновик)","")) as post_title from wp_posts where id='.$value);
		}
		return '';
	}
	public static function count($get=''){
		return  static::get_query($get)->count();
	}
	public static function update( $data, $id ) {
		global $wpdb;
		return $wpdb->update( $wpdb->prefix . static::$tablename['table'], $data, array( 'id' => $id )); 
	}
	public static function insert( $data ) {
		global $wpdb;
		$wpdb->insert( $wpdb->prefix . static::$tablename['table'], $data ); 
		return $wpdb->insert_id; 
	}
	public static function delete( $id ) {
		global $wpdb;
		return $wpdb->delete( $wpdb->prefix . static::$tablename['table'], array( 'id' => $id ), array( '%d' ) ); 
	}
	public function main_links(){
		return sprintf( '<a href="'.get_admin_url().'options.php?page='.static::$tablename['name'].'&action=add" class="page-title-action" >'.static::$tablename['add'].'</a>&nbsp;');
	}
	public function render_page(){
		return false;
	}
}
//================================================================
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
		if($_GET['action']=='delete'&&$_GET['page']==$Tc::$tablename['name']){
			if(is_array($_GET['bulk-delete'])){
				foreach($_GET['bulk-delete'] as $id){
					$Tc::delete( $id );
				}
			}elseif(!empty($_GET['id'])){
				$Tc::delete( $_GET['id'] );
			}
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
		$Tc = $this->cTable; 
		if($Tc::render_page()){
			return;
		}
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
							FieldRenderer::render($fld,$fld['name'],$element[$fld['name']]);?>
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
				<?php echo $Tc::main_links();?>
			</h2>
			<?php $myListTable = new wlListTable($this->cTable);
			$myListTable->views();
			?>
			<form method="get">
			
			 <?php
			 $myListTable->prepare_items(); 
			 $myListTable->search_box('поиск','wl_search'); 
			 $myListTable->display(); ?>
				<input type="hidden" name="page" value="<?php echo $_GET['page'] ?>"/>
			</form>
		</div>
	<?php
	}
}

<?php

Class FieldRenderer{
	public static function render($field,$field_name,$field_value){
		if($field['type']=='taxonomy'){	
			$cats=get_terms($field['taxonomy'],array('hide_empty'=>false));	
			$field['type']='select';	
			$field['options']=array();	
			if($field['canempty']){
				$field['options'][0]='Без категории';
			}
			foreach($cats as $cat){	
				$field['options'][$cat->term_id]=$cat->name;	
			}	
		}elseif($field['type']=='post_type'){
			global $wpdb;
			$posts=$wpdb->get_results('select ID,concat(post_title,if(post_status="draft","(Черновик)","")) as post_title from wp_posts where post_type="'.$field['post_type'].'"');
			$field['options']=array();	
			if($field['canempty']){
				$field['options'][0]='Не связяно';
			}
			foreach($posts as $post){
				$field['options'][$post->ID]=$post->post_title;
			}
			$field['type']='select';	
		}elseif($field['type']=='custom_table'){
			global $wpdb;
			$posts=$wpdb->get_results('select id,'.$field['title'].' as title from '.$wpdb->prefix.$field['table']);
			$field['options']=array();	
			if($field['canempty']){
				$field['options'][0]='Не связяно';
			}
			foreach($posts as $post){
				$field['options'][$post->id]=$post->title;
			}
			
			$field['type']='select';	
		}elseif($field['type']=='post_types'){
			global $wpdb;
			$posts=$wpdb->get_results('select ID,concat(post_title,if(post_status="draft","(Черновик)","")) as post_title from wp_posts where post_type in ("'.implode('","',$field['post_types']).'")');
			$field['options']=array();	
			if($field['canempty']){
				$field['options'][0]='Не связяно';
			}
			foreach($posts as $post){
				$field['options'][$post->ID]=$post->post_title;
			}
			$field['type']='select';	
		}
		
		if($field['type']=='checkbox'){
			echo '<input type="checkbox" name="'.$field_name.'" '.(!empty($field_value)?'checked':'').'>';
		}elseif($field['type']=='radio'){
			foreach($field['items'] as $val=>$label){
				echo '<label><input type="radio" name="'.$field_name.'"  value="'.$val.'"'.(($field_value==$val)?'checked':'').'>'.$label.'</label>';
			}
		}elseif($field['type']=='simple_checkbox'){
			echo '<label><input type="checkbox" name="'.$field_name.'" '.(!empty($field_value)?'checked':'').'>'.$field['simple_label'].'</label>';
		}elseif($field['type']=='editor'){
			$name=str_replace('[','_',$field_name);
			$name=str_replace(']','_',$name);
			wp_editor($field_value, $name, $settings = array('textarea_name'=>$field_name,'quicktags'=>true) );
		}elseif($field['type']=='text'){
			echo '<input type="text" name="'.$field_name.'" value="'.htmlspecialchars($field_value).'" list="data_'.$field_name.'">';
			if(!empty($field['options'])){
				echo '<datalist id="data_'.$field_name.'">';
				foreach($field['options'] as $fo){
					echo '<option value="'.$fo.'"></option>';
				}
				echo '</datalist>'; 
			}
		}elseif($field['type']=='date'){
			echo '<input type="date" name="'.$field_name.'" value="'.$field_value.'">';
		}elseif($field['type']=='color'){
			echo '<input type="color" name="'.$field_name.'" value="'.$field_value.'">';
		}elseif($field['type']=='textarea'){
			echo '<textarea name="'.$field_name.'" style="width:100%" class="textarea_autosize">'.$field_value.'</textarea>';
		}elseif($field['type']=='select'){
			$multiple=$field['multiple'];
			if(!is_array($field_value)){
				$field_value=[$field_value];
			}
			echo '<select class="select2"name="'.$field_name.($multiple?'[]':'').'"'.($multiple?' multiple':'').'>';
			foreach($field['options'] as $k=>$v){
				echo '<option value="'.$k.'"'.(in_array($k,$field_value)?' selected':'').'>'.$v.'</option>';
			}
			echo '</select>';
		}elseif($field['type']=='info'){
			if(!empty($field['callback'])){
				echo call_user_func($field['callback'],$field_value);
			}elseif(!empty($field['html'])){
				echo $field['html'];
			}
		}elseif($field['type']=='image'){
			$imageurl=wp_get_attachment_image_url($field_value,'full');
			echo '<div class="wlfields_image" style="'.(!empty($imageurl)?'background:url('.$imageurl.')no-repeat center;background-size:contain;':'').'" data-media-uploader-target="#image_'.$field_name.'">'.(empty($imageurl)?'выберите картинку':'').'</div><input id="image_'.$field_name.'" type="hidden" name="'.$field_name.'" value="'.htmlspecialchars($field_value).'">';//<input type="text" name="'.$field_name.'" value="'.htmlspecialchars($field_value).'" list="data_'.$field_name.'">';
			?>
			<style>
				.wlfields_image{
				border: 1px dashed #afafaf;
				border-radius: 4px;
				padding: 10px;
				box-sizing: border-box;
				width: 100%; 
				height: 90px;
				line-height: 70px;
				cursor:pointer;
				}
			</style>
			<?php
		}elseif($field['type']=='mappoint'){
			if(empty($field_value)){
				$field_value=base64_encode('{"lat": 55.75583, "lng": 37.61778}');
			}
			echo '<div><script>
			function base64_encode( data ) {    // Encodes data with MIME base64
				var b64 = "ABCDEFGHIJKLMNOPQRSTUVWXYZabcdefghijklmnopqrstuvwxyz0123456789+/=";
				var o1, o2, o3, h1, h2, h3, h4, bits, i=0, enc="";
				do { // pack three octets into four hexets
					o1 = data.charCodeAt(i++);
					o2 = data.charCodeAt(i++);
					o3 = data.charCodeAt(i++);
					bits = o1<<16 | o2<<8 | o3;
					h1 = bits>>18 & 0x3f;
					h2 = bits>>12 & 0x3f;
					h3 = bits>>6 & 0x3f;
					h4 = bits & 0x3f;
					enc += b64.charAt(h1) + b64.charAt(h2) + b64.charAt(h3) + b64.charAt(h4);
				} while (i < data.length);
				switch( data.length % 3 ){
					case 1:
						enc = enc.slice(0, -2) + "==";
					break;
					case 2:
						enc = enc.slice(0, -1) + "=";
					break;
				}
				return enc;
			}

								function initMap() {
			
			var location=JSON.parse(\''.base64_decode($field_value).'\');
			  var map = new google.maps.Map(document.getElementById("map"), {
				zoom: 12,
				center: location
			  });
			  console.log(document.getElementById("coords").value);
			  
			  
			  var marker= new google.maps.Marker({position: location, map: map});
			  google.maps.event.addListener(map, "click", function(event) {
				 console.log("click");
				startLocation = event.latLng;
				placeMarker(startLocation);
				});


				function placeMarker(location,map) {
					marker.setPosition(location);
					document.getElementById("coords").value=base64_encode(JSON.stringify(location));
				}
				
			}</script>';
			echo '<div id="map"></div><style>#map{height:300px}</style>';
			//echo '<script src="'.get_template_directory_uri().'/admin.js"></script>';

			echo '<script src="https://maps.googleapis.com/maps/api/js?key='.$key.'&signed_in=true&callback=initMap" async defer></script>';
			echo '<input type="hidden" id="coords" name="'.$field_name.'" value=\''.$field_value.'\'size="25" /></div>';
		}elseif($field['type']=='curcalendar'){
			echo '<div class="curcalendar">';
			for($i=1;$i<=12;$i++){
				echo_curcalendar($i,date('Y'),$field_name,$field_value);
			}
			echo '</div>';
			?>
			<style>
			.curcalendar{
				display: flex;
				flex-wrap: wrap;
			}
			.curcalendar_month{
				margin: 0 25px 25px 0;
				width: 20%;
				border-collapse: collapse;
				background: white;
			}
			.curcalendar input{
				display:none;
			}
			.curcalendar input:checked + span{
				background:#fd5446;
				color:#fff;
			}
			.curcalendar_month td{
				width:30px;
				height:30px;
			}
			.curcalendar_month label, .curcalendar_month span{
				display: block;
				height: 100%;
				line-height: 30px;
				text-align: center;
			} 
			</style>
			<?php
		}else{
			var_dump($field);
			echo 'нужно запрограммировать новый тип поля('.$field['type'].')';
		}
	}
}

function echo_curcalendar($month,$year,$name,$values){
	if($month>12){
		$year+=1;
		$month-=12;
	}
		$months = array(
			1  => 'Январь',
			2  => 'Февраль',
			3  => 'Март',
			4  => 'Апрель',
			5  => 'Май',
			6  => 'Июнь',
			7  => 'Июль',
			8  => 'Август',
			9  => 'Сентябрь',
			10 => 'Октябрь',
			11 => 'Ноябрь',
			12 => 'Декабрь'
		);
		$day_week = date('N', mktime(0, 0, 0, $month, 1, $year));
		$day_week--;
	?>
	<table class="curcalendar_month" border=1>
		<tr><td colspan=7><?php echo $months[(int)$month];?></td></tr>
		<tr>
			<th>ПН</th>
			<th>ВТ</th>
			<th>СР</th>
			<th>ЧТ</th>
			<th>ПТ</th>
			<th>СБ</th>
			<th>ВС</th>
		</tr>
		<tr>
		<?php for ($x = 0; $x < $day_week; $x++) {
			echo '<td></td>';
		}?>
		<?php
		$days_month = date('t', mktime(0, 0, 0, $month, 1, $year));
	
		for ($day = 1; $day <= $days_month; $day++):?>
		
		<td><label><input type="checkbox" name="<?php echo $name;?>[<?php echo $year.'-'.$month.'-'.$day;?>]" <?php echo !empty($values[$year.'-'.$month.'-'.$day])?' checked':'';?>><span><?php echo $day;?><span></label></td>
		<?php 
		if ($day_week == 6) {
				echo '</tr>';
			if (($days_counter + 1) != $days_month) {
				echo '<tr>';
			}
			$day_week = -1;
		}

		$day_week++; 

		$days_counter++;
		?>
		<?php endfor;?>
		
	</table>
	<?
}
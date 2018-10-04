<?php

Class FieldRenderer{
	public function render($field,$field_name,$field_value){
		if($field['type']=='taxonomy'){	
			$cats=get_terms($field['taxonomy'],array('hide_empty'=>false));	
			$field['type']='select';	
			$field['options']=array();	
			foreach($cats as $cat){	
				$field['options'][$cat->term_id]=$cat->name;	
			}	
		}elseif($field['type']=='post_type'){
			global $wpdb;
			$posts=$wpdb->get_results('select ID,post_title from wp_posts where post_type="'.$field['post_type'].'"');
			$field['options']=wp_list_pluck($posts,'post_title','ID');
			$field['type']='select';	
		}
		
		if($field['type']=='checkbox'){
			echo '<input type="checkbox" name="'.$field_name.'" '.(!empty($field_value)?'checked':'').'>';
		}elseif($field['type']=='editor'){
			$name=str_replace('[','_',$field_name);
			$name=str_replace(']','_',$name);
			wp_editor($field_value, $name, $settings = array('textarea_name'=>$field_name,'quicktags'=>true) );
		}elseif($field['type']=='text'){
			echo '<input type="text" name="'.$field_name.'" value="'.$field_value.'">';
		}elseif($field['type']=='date'){
			echo '<input type="date" name="'.$field_name.'" value="'.$field_value.'">';
		}elseif($field['type']=='textarea'){
			echo '<textarea name="'.$field_name.'">'.$field_value.'</textarea>';
		}elseif($field['type']=='select'){
			echo '<select name="'.$field_name.'">';
			foreach($field['options'] as $k=>$v){
				echo '<option value="'.$k.'"'.($k==$field_value?' selected':'').'>'.$v.'</option>';
			}
			echo '</select>';
		}elseif($field['type']=='info'){
			if(!empty($field['callback'])){
				echo call_user_func($field['callback'],$post);
			}elseif(!empty($field['html'])){
				echo $field['html'];
			}
		}elseif($field['type']=='mappoint'){
			if(empty($field_value)){
				$field_value=base64_encode('{"lat": 55.75583, "lng": 37.61778}');
			}
			echo '<script>
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
				zoom: 4,
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

			echo '<script src="https://maps.googleapis.com/maps/api/js?key=AIzaSyDkYtMcjg1cfV3aBe87ROSV3udADCpu-ZM&signed_in=true&callback=initMap" async defer></script>';
			echo '<input type="hidden" id="coords" name="'.$field_name.'" value=\''.$field_value.'\'size="25" /></div>';
		}else{
			var_dump($field);
			echo 'нужно запрограммировать новый тип поля('.$field['type'].')';
		}
	}
}
<?php 
$breadcrumbs=[];
$breadcrumbs[]=['url'=>'/','title'=>'Главная'];
if(!empty($atts['links'])){
	$crumbs=explode('|',$atts['links']);
	for($i=0;$i<count($crumbs);$i+=2){
		$breadcrumbs[]=['url'=>$crumbs[$i],'title'=>$crumbs[$i+1]];
	}
	//var_dump($atts['links']);
}elseif(is_search()){
	$breadcrumbs[]=['url'=>'','title'=>'Результаты поиска'];
}elseif(get_post_type()=='post'){
	$cat=(get_the_category(get_the_ID()));
	$cat=reset($cat);
	$breadcrumbs[]=['url'=>get_term_link($cat),'title'=>$cat->name];
}elseif(get_post_type()=='page'){
	//nothing)
}else{
	$object=get_post_type_object(get_post_type());
	$s=get_archive_desc(get_post_type());
	if(!empty($s['h1'])){
		$title=$s['h1'];
	}else{
		$title=get_post_type_object(get_post_type())->label;
	}
	$breadcrumbs[]=['url'=>get_post_type_archive_link(get_post_type()),'title'=>$title];
}


if(is_singular()){
	$meta=WeblamasOptions::get_meta();
	if(!empty($meta['breadcrumbs'])){
		$breadcrumbs[]=['url'=>'','title'=>$meta['breadcrumbs']];
	}else{
		$breadcrumbs[]=['url'=>'','title'=>get_the_title()];
	}
	
	
}
$title=array_pop($breadcrumbs)['title'];
?>


<ol itemscope="" itemtype="http://schema.org/BreadcrumbList" class="breadcrumbs">
	<?php foreach($breadcrumbs as $k=>$crumb):?>
		<li itemscope="" itemprop="itemListElement" itemtype="http://schema.org/ListItem">
			<a itemprop="item" href="<?php echo $crumb['url'];?>">
				<span itemprop="name"><?php echo $crumb['title'];?></span>
			</a>
			<meta itemprop="position" content="<?php echo $k;?>" />
		</li> 
		<li><span>/</span></li>
	<?php endforeach;?>
	<li class="unactive">
		<span><?php echo $title;?></span>
	</li>
</ol>

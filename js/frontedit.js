$(document).ready(function(){
	var cureditable;
	$('body').on('focus', '[contenteditable]', function() {
		const $this = $(this);
		$this.data('before', $this.html());
		console.log($this.offset());
		$(".frontedit").css({
			top: $this.offset().top - $(".frontedit").height() - 5 + "px",
			left: $this.offset().left + "px",
			width: "320px",
			display:'block'
		}).data('editableid',$this.data('editable'));
		cureditable=this;
		console.log($this.data('editable'));
		/*
		if ($(".frontedit").position().top < 0){
			$(".frontedit").css({ top: 0 });
		}*/
	}).on('blur keyup paste input', '[contenteditable]', function() {
		const $this = $(this);
		if ($this.data('before') !== $this.html()) {
			$this.data('before', $this.html());
			$this.trigger('change');
		}
	}).on('blur','[contenteditable]',function(){
		//$('.frontedit').hide();
	});
	$('[data-editable]').change(function(){
		console.log($(this).html());
		console.log($(this).data('editable'));
	});
	$('.frontedit__save').click(function(){
		let editableid=$(this).closest('.frontedit').data('editableid');
		let data={action:'update_frontval',id:editableid,value:$('[data-editable="'+editableid+'"]').html()};
		let lng=$('body').data('lang');
		if(lng){
			data.lang=lng;
		}
		jQuery.post('/wp-admin/admin-ajax.php', data, function(response) {
			console.log(response);
		});
	})
	
});
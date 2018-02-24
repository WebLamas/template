jQuery(document).ready(function($){
	
//mobile_menu	
$('.mobile_menu').click(function(){
		$(this).find('#nav-icon').toggleClass('open');
		$('header').toggleClass('active');
	});
// slider	
	
	var slide=0;
	var slides=jQuery('.review_container').children().children().length;
	
	function nextslide(){
		slide++;
		var count=Math.round(parseFloat(jQuery('.review_wrapper').css('width'))/parseFloat(jQuery('.review').css('width')))-1;
		if(slide>=slides-count){
			slide=0;
		}
		$('.review_group').css('margin-left','-'+(slide*33.33333)+'%');
	}
	
	function prevslide(){
		slide--;
		var count=Math.round(parseFloat(jQuery('.review_wrapper').css('width'))/parseFloat(jQuery('.review').css('width')))-1;
		 
		if(slide<0){
			slide=slides-count-1;
		}
		$('.review_group').css('margin-left','-'+(slide*33.33333)+'%');
	}

	$('.reviews_wr .arrow.next').click(function(){
		nextslide();
	})
	
	$('.reviews_wr .arrow.prev').click(function(){
		prevslide();
	})
//popup after form submit
	jQuery(".wpcf7-submit").click(function(event) {//IF THE SUBMIT IS PRESSED
			var self=this;
			jQuery( document ).ajaxComplete(function() {//AJAX RESPONSES
				jQuery('div.wpcf7-response-output').wrap("<div class='response-wrap'></div>");	
				var responseOutput = jQuery(self).closest(".wpcf7-form").find('div.response-wrap').html();
                    
				jQuery.fancybox({
					'overlayColor'		: '#000',
					'padding'			: 15,
					'centerOnScroll'	: true,
					'content'			: responseOutput,
					'minHeight'			: 0
				});
			});
		});	
// review form submit
	jQuery('.jreview_form').submit(function(e){
		e.preventDefault();
		var self=this;
		data=$(self).serialize();
		data+='&action=post_review';
		jQuery.post(ajax_url, data, function(response) {
			$.fancybox.open({ content : 'Отзыв отправлен. Он появится на сайте после модерации',minHeight:0});
		});
	})
// bottom blocks
		$(document).on('click',function (e) {
			$('.bottom').removeClass('active');
		});
		$('.bottom .bheading').click(function(e){
			 e.stopPropagation();
			$(this).closest('.bottom').toggleClass('active');
			
		});
		$('.bottom .openform').click(function(e){
			 e.stopPropagation();
			$('.bottom.breviews .content.form').show();
		})
		$('.bottom.breviews a.down').click(function(e){
			e.preventDefault();
			if($(this).closest('.bottom').find('.reviews_wrapper').find('.r_single:visible').length>2)
				$(this).closest('.bottom').find('.reviews_wrapper').find('.r_single:visible').first().hide();
			if($(this).closest('.bottom').find('.reviews_wrapper').find('.r_single:visible').length>2)
				$(this).closest('.bottom').find('.reviews_wrapper').find('.r_single:visible').first().hide();
		})
		$('.bottom.breviews a.up').click(function(e){
			e.preventDefault();
			$(this).closest('.bottom').find('.reviews_wrapper').find('.r_single:hidden').last().show();
			$(this).closest('.bottom').find('.reviews_wrapper').find('.r_single:hidden').last().show();
		})
		$('.bottom.breviews a.close_form').click(function(e){
			e.preventDefault();
			$('.bottom.breviews .content.form').hide();
			
		})
		$('.bottom').click(function(e){
			e.stopPropagation();
		})
// to top button
		$(window).scroll(function() {
			if($(window).width()>1000){
				if($(this).scrollTop() > 600) {
					$(".totop").fadeIn();
					}
				else {
					$(".totop").fadeOut();
					}
			}else{
				$(".totop").fadeOut();
			}
		});
		$('.totop').click(function(){
			$("body,html").animate({scrollTop:0},800);
		});
// open form for order
	$('.service_page .service_order,body.home .service a').click(function(e){
		e.preventDefault();
		e.stopPropagation();
		if($('.bottom.callback').is(':visible')){
			$('.bottom.callback').addClass('active');
			$('.bottom.callback textarea').val('Я хочу заказать услугу "'+$(this).data('sn')+'"');
		}else{
			$('footer .orderForm textarea').val('Я хочу заказать услугу "'+$(this).data('sn')+'"');
			var top = jQuery('footer .orderForm').offset().top;
			jQuery('body,html').animate({scrollTop: top}, 400);
		}
		
	});
//add_review click
	$('.reviews_wr .review_button a').click(function(e){
		if($('.bottom.breviews').is(':visible')){
			e.preventDefault();
			e.stopPropagation();
		}
		$('.bottom.breviews').addClass('active');
		$('.bottom.breviews .content.form').show();
		
	})
		
})

var nextUrl='';
var photoCarousel;
var interval;
var resendText='';
$(document).ready(function(){
	$(document).on('submit','.sms-form',function(){
		$this=$(this);
		$this.next('.result.form-result').find('.result-content').html("");
		$this.next('.result.form-result').removeClass('active');
		$this.find('.form-group').removeClass('has-errors').addClass('success-validation');
		$.ajax({
			url: $this.attr('action'),
			type: 'POST',
			dataType: 'json',
			data: $this.serialize(),
			success: function (response) {
				if (response.result==false) {
					$.each(response.errors,function(key,error){
						$this.find('input[name="'+key+'"]').next('.field-errors').html(error);
						$this.find('input[name="'+key+'"]').parents('.form-group').addClass('has-errors').removeClass('success-validation');
					});
					if(response.leftTime>0){
						resendText=response.resendText;
						display=document.querySelector('#left-time');
						clearInterval(interval);
						startTimer(display);						
					}
				}else{
					if(response.redirect){
						document.location.href=response.redirect;
					}
					nextUrl=response.nextStep;
					$('#modal-password-change').modal('show');
				}
			},
			error: function () {
			}
		});
		return false;
	});
	$(document).on('click','.photo-link-btn',function(e){
		e.preventDefault();
		$this=$(this);
		$.ajax({
			url: $this.attr('href'),
			type: 'GET',
			dataType: 'json',
			success: function (response) {
				if (response.result==true) {
					$this.parents('.modal-content-wrapper').html(response.message);
					display=document.querySelector('#left-time');
					clearInterval(interval);
					startTimer(display);					
				}
			},
			error: function () {
			}
		});	
	});
	$(document).on('click','.resend-link',function(e){
		e.preventDefault();
		$this=$(this);
		$this.find('.form-group').removeClass('has-errors').addClass('success-validation');
		$.ajax({
			url: $this.attr('href'),
			type: 'POST',
			dataType: 'json',
			success: function (response) {
				if (response.result==true) {
					$this.parents('.code-errors').html(response.message);
				}else{
					$.each(response.errors,function(key,error){
						$this.parents('.field-errors').html(error);
						$this.find('input[name="'+key+'"]').parents('.form-group').addClass('has-errors').removeClass('success-validation');
					});					
				}
			},
			error: function () {
			}
		});		
	});
	$(document).on('submit','.confirmation-form',function(){
		$this=$(this);
		$this.next('.result.form-result').find('.result-content').html("");
		$this.next('.result.form-result').removeClass('active');
		$this.find('.form-group').removeClass('has-errors');
		$('.loading-wrapper').addClass('active');
		$.ajax({
			url: $this.attr('action'),
			type: 'POST',
			dataType: 'json',
			data: $this.serialize(),
			success: function (response) {
				if (response.result==false) {
					$.each(response.errors,function(key,error){
						$this.find('input[name="'+key+'"]').next('.field-errors').html(error);
						$this.find('input[name="'+key+'"]').parents('.form-group').addClass('has-errors');
						$this.find('select[name="'+key+'"]').next('.field-errors').html(error);
						$this.find('select[name="'+key+'"]').parents('.form-group').addClass('has-errors');
					});
					if(response.leftTime>0){
						resendText=response.resendText;
						display=document.querySelector('#left-time');
						clearInterval(interval);
						startTimer(display);						
					}					
				}else{
					if(response.redirect){
						document.location.href=response.redirect;
					}
					nextUrl=response.nextStep;
					$('#modal-sms-confirm').modal('show');
				}
				$('.loading-wrapper').removeClass('active');
			},
			error: function () {
			}
		});
		return false;
	});
	$(document).on('show.bs.modal','#modal-password-forgot',function(e){
		$(this).removeData('bs.modal');
	});
	$(document).on('submit','.login-form,.identification-form,.password-forgot-form',function(){
		$this=$(this);
		$this.next('.result.form-result').find('.result-content').html("");
		$this.next('.result.form-result').removeClass('active');
		$this.find('.form-group').removeClass('has-errors');
		$('.loading-wrapper').addClass('active');
		$.ajax({
			url: $this.attr('action'),
			type: 'POST',
			dataType: 'json',
			data: $this.serialize(),
			success: function (response) {
				if (response.result==false) {
					if(response.errors){
						$.each(response.errors,function(key,error){
							$this.find('input[name="'+key+'"]').next('.field-errors').html(error);
							$this.find('input[name="'+key+'"]').parents('.form-group').addClass('has-errors');
						});
					}
					$this.find('input[name="loginToken"]').val(response.form_token);
					$this.find('input[name="token"]').val(response.form_token);
					if(response.error){
						$this.next('.result.form-result').find('.result-content').html('<p>'+response.error+'</p>');
					}
					if(response.leftTime>0){
						resendText=response.resendText;
						display=document.querySelector('#left-time');
						clearInterval(interval);
						startTimer(display);						
					}					
				}else{
					if(response.redirect){
						document.location.href=response.redirect;
					}
					if(response.messages){
						$.each(response.messages,function(key,message){
							$this.find('input[name="'+key+'"]').parents('.form-group').addClass('has-success');
							$this.find('input[name="'+key+'"]').next('.field-errors').html(message);
						});
						setTimeout(function(){$('.modal').trigger('click')},5000);
					}
				}
				$('.loading-wrapper').removeClass('active');
			},
			error: function () {
			}
		});
		return false;
	});
	$(document).on('submit','.photo-form',function(){
		$this=$(this);
		$this.next('.result.form-result').find('.result-content').html("");
		$this.next('.result.form-result').removeClass('active');
		$this.find('.form-group').removeClass('has-errors');
		var formData=new FormData($('.photo-form')[0]);
		$('.loading-wrapper').addClass('active');
		$.ajax({
			url: $this.attr('action'),
			type: 'POST',
			data: formData,
			cache: false,
			contentType: false,
			processData: false,			
			success: function (response) {
				if (response.result==false) {
					if(response.errors){
						$.each(response.errors,function(key,error){
							$this.find('input[name="'+key+'"]').parents('.form-group').find('.photo-step-errors').html(error);
							$this.find('input[name="'+key+'"]').parents('.form-group').addClass('has-errors');
							photoCarousel.trigger('to.owl.carousel',$('.photo-carousel-wrapper .owl-stage').find('input[name="'+key+'"]').parents('.owl-item').index());
						});
					}
					$this.find('input[name="loginToken"]').val(response.form_token);
					$this.find('input[name="token"]').val(response.form_token);
					if(response.error){
						$this.next('.result.form-result').find('.result-content').html('<p>'+response.error+'</p>');
					}					
				}else{
					if(response.redirect){
						document.location.href=response.redirect;
					}
				}
				$('.loading-wrapper').removeClass('active');
			},
			error: function () {
			}
		});
		return false;
	});
	$(document).on('submit','.password-form',function(){
		$this=$(this);
		$this.next('.result.form-result').find('.result-content').html("");
		$this.next('.result.form-result').removeClass('active');
		$this.find('.form-group').removeClass('has-errors');
		$.ajax({
			url: $this.attr('action'),
			type: 'POST',
			dataType: 'json',
			data: $this.serialize(),
			success: function (response) {
				if (response.result==false){
					$.each(response.errorsArray,function(key,error){
						$this.find('input[name="'+key+'"]').next('.field-errors').html(error);
						$this.find('input[name="'+key+'"]').parents('.form-group').addClass('has-errors');
					});					
					$this.next('.result.form-result').find('.result-content').html(response.errors);
					$this.next('.result.form-result').addClass('active');
				}else{
					if(response.redirect){
						document.location.href=response.redirect;
					}
				}
			},
			error: function () {
			}
		});
		return false;
	});
	$(document).on('keyup','#input-registration-address',function(){
        $this=$(this);
		$this.siblings('.variants-list').find('ul').html("");
		$this.siblings('.variants-list').removeClass('active');
        $.ajax({
            url: $this.data('action'),
            type: 'POST',
            dataType: 'json',
            data: {text:$this.val()},
            success: function (response) {
                if(response.result){
					$this.siblings('.variants-list').addClass('active');
					$this.siblings('.variants-list').html(response.variants);
				}else{
					$this.siblings('.variants-list').removeClass('active');
				}
            },
            error: function () {}
        });	
	});
	$(document).on('click','.variants-list ul li a',function(e){
		e.preventDefault();
		$(this).parents('.variants-list').removeClass('active');
		$('#input-registration-address').val($(this).html());
		$('input[name="suggestion_address_id"]').val($(this).data('id'));
	});
	$(document).on('click','.form-password-btn',function(){
		var input=$(this).parents('.form-password-wrapper').find('input');
		$(this).toggleClass('password-shown');
		if(input.attr('type')=='password'){
			input.attr('type','text');
		}else{
			input.attr('type','password');
		}
	});
	$(document).on('show.bs.modal','#modal-password-change',function(e){
		$(this).removeData('bs.modal');
		var link = $(e.relatedTarget);
		$(this).find('.modal-content').load(nextUrl);	
	});
	$(document).on('hide.bs.modal','#modal-sms-confirm,#modal-password-change',function(e){
		return false;
	});		
	$('#input-code').mask('0000');
	$('#input-phone').mask('+7 (000) 000-00-00').on('focus', function(e) {
		var p = $(this);
		p.data('orig-placeholder', p.attr('placeholder'));
		p.attr('placeholder', '+7 (');
	})
	.on('blur', function(e) {
		var p = $(this);
		p.attr('placeholder', p.data('orig-placeholder'));
	});
	$('#input-issue-date').mask('0000-00-00').on('focus', function(e) {
		var p = $(this);
		p.data('orig-placeholder', p.attr('placeholder'));
		p.attr('placeholder', '1900-01-01');
	})
	.on('blur', function(e) {
		var p = $(this);
		p.attr('placeholder', p.data('orig-placeholder'));
	});
	$('#input-subdivision-code').mask('000-0000').on('focus', function(e) {
		var p = $(this);
		p.data('orig-placeholder', p.attr('placeholder'));
		p.attr('placeholder', '000-000');
	})
	.on('blur', function(e) {
		var p = $(this);
		p.attr('placeholder', p.data('orig-placeholder'));
	});
	$('#input-id').mask('000000000000').on('focus', function(e) {
		var p = $(this);
		p.data('orig-placeholder', p.attr('placeholder'));
		p.attr('placeholder', '111222333444');
	})
	.on('blur', function(e) {
		var p = $(this);
		p.attr('placeholder', p.data('orig-placeholder'));
	});	
	$('.identification-form input[type="radio"]').change(function(){
		if($(this).val()==1){
			$('#input-id').mask('000000000000').on('focus', function(e) {
				var p = $(this);
				p.data('orig-placeholder', p.attr('placeholder'));
				p.attr('placeholder', '111222333444');
			})
			.on('blur', function(e) {
				var p = $(this);
				p.attr('placeholder', p.data('orig-placeholder'));
			});
		}else{
			$('#input-id').mask('00000000000').on('focus', function(e) {
				var p = $(this);
				p.data('orig-placeholder', p.attr('placeholder'));
				p.attr('placeholder', '11122233344');
			})
			.on('blur', function(e) {
				var p = $(this);
				p.attr('placeholder', p.data('orig-placeholder'));
			});			
		}
	});
	$(document).on('click','.remove-photo-btn',function(e){
		$(this).parents('.form-group').find('.photo-wrapper.has-photo').removeClass('has-photo').css({backgroundImage:'url(/assets/images/camera.svg)'});
		$(this).find('span').html('Загрузить');
		$(this).removeClass('remove-photo-btn');
	});
	var inputs=document.querySelectorAll('.inputfile');
	Array.prototype.forEach.call(inputs,function(input){
		var label=input.nextElementSibling,
			labelVal=label.innerHTML;
		input.addEventListener('change',function(e){
			var fileName='';
			if(this.files&&this.files.length>1){
				fileName=(this.getAttribute('data-multiple-caption')||'').replace('{count}',this.files.length);
			}else{
				fileName=e.target.value.split('\\').pop();
			}
			if(fileName){
				label.querySelector('span').innerHTML=this.getAttribute('data-remove-text');
				label.classList.add('remove-photo-btn');
			}else{
				label.innerHTML=labelVal;
			}
			readURL(this);
			setTimeout(function(){photoCarousel.trigger('next.owl.carousel')},1000);
		});
	});	
	photoCarousel=$('.owl-carousel.fadeOut').owlCarousel({
		items: 1,
		loop: false,
		dots:false,
		margin: 30,
		stagePadding:0,
		responsive:{
			768:{
				stagePadding:30,
			},
			1024:{
				stagePadding:80,
			},
			1200:{
				stagePadding:130,
			}
		}
	});	
	if($('*').is('#camera-input-file')){
		document.getElementById('camera-input-file').onchange=function(e){
			$(this).parents('.photo-selfie-wrapper').find('.form-submit-btn').attr('disabled','disabled').addClass('hidden');
			$(this).parents('.photo-selfie-wrapper').find('.photo-wrapper').removeClass('has-photo').css({backgroundImage:'none'});
			readFile(this,e.srcElement.files[0]);
		};	
	}
});
function readURL(input) {
    if (input.files && input.files[0]) {
        var reader = new FileReader();
        reader.onload = function (e) {
            $(input).parents('.form-group').find('.photo-wrapper img').attr('src', e.target.result).show();
			$(input).parents('.form-group').find('.photo-wrapper').addClass('has-photo').css({backgroundImage:'url('+e.target.result+')'});
        };
        reader.readAsDataURL(input.files[0]);
    }
}
function startTimer(display){
	time=display.textContent;
	interval=setInterval(function (){
		time--;
		display.textContent = time;
		if(time<0){
			clearInterval(interval);
			$('.code-errors').html(resendText);
		}
	}, 1000);
}
function readFile(el,file){
	var $el=$(el);
	var reader=new FileReader();
	reader.onload=readSuccess;                                            
	function readSuccess(evt){     
		$el.parents('.photo-selfie-wrapper').find('.photo-wrapper').addClass('has-photo').css({backgroundImage:'url('+evt.target.result+')'});
		$el.parents('.photo-selfie-wrapper').find('.form-submit-btn').attr('disabled',false).removeClass('hidden');
	};
	reader.readAsDataURL(file);                                              
}
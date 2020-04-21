<?php
defined('BASEPATH') OR exit('No direct script access allowed');
if(!isset($on_hold_message)){
?>
<div class="col-md-6 col-md-offset-3 col-sm-6 col-sm-offset-3 col-xs-12">
	<div class="bordered-block">
		<?php echo form_open('login',['class' => 'mt-60 login-form']);?>
			<div class="form-group">
				<?php echo form_label($this->lang->line('Phone'),'input-phone');?>
				<?php echo form_input(['name' => 'login_string','value' => '','placeholder' => '+7 (','id' => 'input-phone','class' => 'form-control']);?>
				<p class="field-errors"></p>
			</div>
			<div class="form-group">
				<?php echo form_label($this->lang->line('Password'),'input-password');?>
				<?php //echo form_password(['name' => 'login_pass','class' => 'form-control','id' => 'input-password','placeholder' => '']);?>
				<p class="field-errors"></p>
				<div class="form-password-wrapper">
					<div class="row">
						<div class="col-xs-9">
							<?php echo form_password(['name' => 'login_pass','value' => '','id' => 'input-password','class' => 'form-control']);?>
							<p class="field-errors col-sm-12"></p>
						</div>
						<div class="col-xs-3 text-right">
							<?php echo form_button(['type' => 'button', 'content' => '<img src="'.asset_url().'images/eye-slash.svg">'.'<img src="'.asset_url().'images/eye.svg">','class' => 'form-password-btn','tabindex' => -1]);?>
						</div>
					</div>
				</div>				
			</div>
			
			<div class="form-group">
				<?php echo form_label($this->lang->line('Sms code'),'input-code');?>
				<?php echo form_input(['name' => 'code','class' => 'form-control','id' => 'input-code','placeholder' => '0000']);?>
				<p class="field-errors code-errors"></p>
			</div>			
			<div class="text-center">
				<?php echo form_button(['type' => 'submit', 'content' => $this->lang->line('Enter'),'class' => 'form-submit-btn mt-20']);?>
			</div>
		<?php echo form_close();?>
		<div class="result form-result"><div class="result-content"></div></div>
	</div>
</div>
<?php }else{?>
	<div class="text-center"><h2><?php echo $on_hold_message;?></h2></div>
<?php }?>
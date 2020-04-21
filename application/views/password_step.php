<div class="col-sm-6 col-sm-offset-3">
	<h3 class="text-center title-small"><?php echo $this->lang->line('Change your password');?></h3>
	<?php echo form_open($form_url,['class' => 'mt-20 password-form']);?>
		<div class="form-group">
			<?php echo form_label($this->lang->line('Enter new password'),'input-password');?>
			<div class="form-password-wrapper">
				<div class="row">
					<div class="col-xs-9">
						<?php echo form_password(['name' => 'password','value' => '','id' => 'input-password','class' => 'form-control']);?>
					</div>
					<div class="col-xs-3 text-right">
						<?php echo form_button(['type' => 'button', 'content' => '<img src="'.asset_url().'images/eye-slash.svg">'.'<img src="'.asset_url().'images/eye.svg">','class' => 'form-password-btn','tabindex' => -1]);?>
					</div>
				</div>
			</div>
		</div>
		<div class="form-group">
			<?php echo form_label($this->lang->line('Repeat password'),'input-password-repeat');?>
			<div class="form-password-wrapper">
				<div class="row">
					<div class="col-xs-9">
						<?php echo form_password(['name' => 'cpassword','value' => '','id' => 'input-password-repeat','class' => 'form-control']);?>
					</div>
					<div class="col-xs-3 text-right">
						<?php echo form_button(['type' => 'button', 'content' => '<img src="'.asset_url().'images/eye-slash.svg">'.'<img src="'.asset_url().'images/eye.svg">','class' => 'form-password-btn','tabindex' => -1]);?>
					</div>
				</div>
			</div>
		</div>
		<div class="text-center">
			<?php echo form_button(['type' => 'submit', 'content' => $this->lang->line('Change password'),'class' => 'form-submit-btn mt-20']);?>
		</div>
	<?php echo form_close();?>
	<div class="result form-result"><div class="result-content"></div></div>
</div>
<div class="clearfix"></div>
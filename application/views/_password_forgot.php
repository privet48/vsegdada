<div class="col-sm-12">
	<h3 class="text-center title-small"><?php echo $this->lang->line('Restore password');?></h3>
	<?php echo form_open($form_url,['class' => 'mt-20 password-forgot-form']);?>
		<div class="form-group">
			<?php echo form_label($this->lang->line('Enter your email'),'input-email');?>
			<div class="row">
				<div class="col-xs-12">
					<?php echo form_input(['name' => 'email','value' => '','id' => 'input-email','class' => 'form-control']);?>
					<p class="field-errors col-sm-12"></p>
				</div>
			</div>
		</div>
		<div class="text-center">
			<?php echo form_button(['type' => 'submit', 'content' => $this->lang->line('Send'),'class' => 'form-submit-btn mt-20']);?>
		</div>
	<?php echo form_close();?>
	<div class="result form-result"><div class="result-content"></div></div>
</div>
<div class="clearfix"></div>
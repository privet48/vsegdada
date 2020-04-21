<div class="content">
	<h3 class="title-small"><?php echo $this->lang->line('Hello');?>, <?php echo $user->username;?> <?php echo $user->userlastname;?></h3>
	<div class="col-sm-6 col-sm-offset-3">
		<div class="bordered-block">
			<?php echo form_open(base_url('registration/'.$user->user_id.'/'.$user->passport_id.'/sms'),['class' => 'mt-30 sms-form']);?>
				<div class="form-group">
					<?php echo form_label($this->lang->line('Your phone number'),'input-phone');?>
					<?php echo form_input(['name' => 'phone','value' => $user->phone,'id' => 'input-phone','class' => 'form-control']);?>
					<p class="field-errors"></p>
				</div>
				<div class="form-group">
					<?php echo form_label($this->lang->line('Sms code'),'input-code');?>
					<?php echo form_input(['name' => 'code','class' => 'form-control','id' => 'input-code','placeholder' => '0000']);?>
					<p class="field-errors code-errors"></p>
				</div>
				<div class="text-center">
					<?php echo form_button(['type' => 'submit', 'content' => $this->lang->line('Enter'),'class' => 'form-submit-btn mt-30']);?>
				</div>
			<?php echo form_close();?>
			<div class="result form-result"><div class="result-content"></div></div>
		</div>
	</div>
</div>
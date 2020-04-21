<div class="content">
	<h3 class="step-title"><?php echo $this->lang->line('ID confirmation');?></h3>
	<?php $this->load->view('_steps');?>
	<div class="col-sm-6 col-sm-offset-3">
		<div class="bordered-block">
			<h4><?php echo $this->lang->line('Confirm the accuracy of the data sent');?></h4>
			<?php echo form_open($formUrl,['class' => 'mt-20 confirmation-form']);?>
				<div class="form-group mt-20">
					<?php echo form_label($this->lang->line('Enter sms code'),'input-code');?>
					<?php echo form_input(['name' => 'code','id' => 'input-code','class' => 'form-control']);?>
					<p class="field-errors code-errors"></p>
				</div>				
				<div class="text-center">
					<?php echo form_button(['type' => 'submit', 'content' => $this->lang->line('Confirm'),'class' => 'form-submit-btn mt-20']);?>
				</div>
			<?php echo form_close();?>
			<div class="result form-result"><div class="result-content"></div></div>
		</div>
	</div>
</div>
<div class="content">
	<h3 class="step-title"><?php echo $this->lang->line('ID confirmation');?></h3>
	<?php $this->load->view('_steps');?>
	<div class="col-sm-6 col-sm-offset-3">
		<div class="bordered-block">
			<h4 class="text-center"><?php echo $this->lang->line('Enter second document');?></h4>
			<?php echo form_open($formUrl,['class' => 'mt-40 identification-form']);?>
				<div class="row">
					<div class="col-xs-3">
						<div class="form-group input-radio-wrapper">
							<?php echo form_radio(['name' => 'document','value' => 1,'id' => 'input-tin','checked' => true]);?>
							<?php echo form_label($this->lang->line('TIN'),'input-tin');?>
							<p class="field-errors"></p>
						</div>
					</div>
					<div class="col-xs-4">
						<div class="form-group input-radio-wrapper">
							<?php echo form_radio(['name' => 'document','value' => 2,'id' => 'input-snils']);?>
							<?php echo form_label($this->lang->line('SNILS'),'input-snils');?>
							<p class="field-errors"></p>
						</div>
					</div>
				</div>
				<div class="form-group mt-0">
					<?php echo form_input(['name' => 'documentId','id' => 'input-id','class' => 'form-control']);?>
					<p class="field-errors"></p>
				</div>
				<div class="form-button-wrapper-align-right">
					<?php echo form_button(['type' => 'submit', 'content' => $this->lang->line('Further'),'class' => 'form-submit-btn mt-40 btn-next-step']);?>
				</div>
			<?php echo form_close();?>
			<div class="result form-result"><div class="result-content"></div></div>
		</div>
	</div>
</div>
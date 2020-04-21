<div class="content">
	<h3 class="step-title"><?php echo $this->lang->line('ID confirmation');?></h3>
	<?php $this->load->view('_steps');?>
	<div class="col-sm-10 col-sm-offset-1">
		<div class="bordered-block">
			<h4 class="text-center mt-30"><?php echo $this->lang->line('Please, fill additional fields');?></h4>
			<?php echo form_open($formUrl,['class' => 'mt-20 confirmation-form',' autocomplete' => 'off']);?>
				<div class="row">
					<div class="col-sm-6">
						<div class="form-group mt-20">
							<?php echo form_label($this->lang->line('Place of birth (as in passport)'),'input-birth');?>
							<?php echo form_input(['name' => 'birth_place','value' => $this->auth_user_birth_place,'id' => 'input-birth','class' => 'form-control']);?>
							<p class="field-errors"></p>
						</div>
						<div class="form-group mt-20">
							<?php echo form_label($this->lang->line('Passport Issue Date'),'input-issue-date');?>
							<?php echo form_input(['name' => 'passport_issue_date','value' => $this->auth_user_passport_issue_date,'id' => 'input-issue-date','class' => 'form-control']);?>
							<p class="field-errors"></p>
						</div>
						<div class="form-group mt-20">
							<?php echo form_label($this->lang->line('Unit Code'),'input-subdivision-code');?>
							<?php echo form_input(['name' => 'passport_subdivision_code','value' => $this->auth_user_passport_subdivision_code,'id' => 'input-subdivision-code','class' => 'form-control']);?>
							<p class="field-errors"></p>
						</div>
					</div>
					<div class="col-sm-6">
						<div class="form-group mt-20">
							<?php echo form_label($this->lang->line('Issued by (as in passport)'),'input-issuing-authority');?>
							<?php echo form_input(['name' => 'passport_issuing_authority','value' => $this->auth_user_passport_issuing_authority,'id' => 'input-issuing-authority','class' => 'form-control']);?>
							<p class="field-errors"></p>
						</div>
						<div class="form-group mt-20">
							<?php echo form_hidden('suggestion_address_id');?>
							<div class="variants-list"></div>
							<?php echo form_label($this->lang->line('Registration address'),'input-registration-address');?>
							<?php echo form_input(['name' => 'registration_address','id' => 'input-registration-address','class' => 'form-control','data-action' => base_url('/main/address')]);?>
							<p class="field-errors"></p>
						</div>
						<div class="form-group mt-20">
							<?php echo form_label($this->lang->line('Secret word'),'input-secret');?>
							<?php echo form_input(['name' => 'secret','id' => 'input-secret','class' => 'form-control']);?>
							<p class="field-errors"></p>
						</div>
					</div>
					<div class="col-sm-6">	
						<div class="form-group">
							<?php echo form_label($this->lang->line('Occupation'),'input-occupation');?>
							<?php echo form_dropdown('occupation',array_merge(['' => $this->lang->line('-- Select --')],array_map(function($data){return $data['transcription'];},config_item('occupationList'))),'',['id' => 'input-occupation','class' => 'form-control']);?>
							<p class="field-errors"></p>						
						</div>					
					</div>					
					<div class="col-sm-6">
						<?php echo form_label($this->lang->line('Your sex'),'',['class' => 'mb-20']);?>
						<div class="row">
							<div class="col-xs-6">
								<div class="form-group input-radio-wrapper">
									<?php echo form_radio(['name' => 'sex','value' => 1,'id' => 'input-sex-male','checked' => $this->auth_user_sex==1]);?>
									<?php echo form_label($this->lang->line('Male'),'input-sex-male');?>
									<p class="field-errors"></p>
								</div>
							</div>
							<div class="col-xs-6">
								<div class="form-group input-radio-wrapper">
									<?php echo form_radio(['name' => 'sex','value' => 0,'id' => 'input-sex-female','checked' => $this->auth_user_sex==0]);?>
									<?php echo form_label($this->lang->line('Female'),'input-sex-female');?>
									<p class="field-errors"></p>
								</div>
							</div>					
						</div>	
					</div>					
					<div class="col-sm-12"><hr></div>
					<div class="col-sm-6">
						<div class="form-group input-radio-wrapper input-checkbox-wrapper">
							<?php echo form_checkbox(['name' => 'agree_documents','id' => 'input-agree-documents','value' => 1]);?>
							<?php echo form_label(sprintf($this->lang->line('I have read and %s to the documents'),anchor(asset_url().'documents/agreeDocument.pdf',$this->lang->line('agree'),['class' => 'agree-link','target' => '_blank'])),'input-agree-documents');?>
							<p class="field-errors"></p>
						</div>					
					</div>
					<div class="col-sm-6">
						<div class="form-group input-radio-wrapper input-checkbox-wrapper">
							<?php echo form_checkbox(['name' => 'agree_pass_data','id' => 'input-agree-pass','value' => 1]);?>
							<?php echo form_label(sprintf($this->lang->line('I %s to the transfer of data to third parties'),anchor(asset_url().'documents/dataPassDocument.pdf',$this->lang->line('agree'),['class' => 'agree-link','target' => '_blank'])),'input-agree-pass');?>
							<p class="field-errors"></p>
						</div>					
					</div>
					<div class="text-center">
						<?php echo form_button(['type' => 'submit', 'content' => $this->lang->line('Send'),'class' => 'form-submit-btn mt-20']);?>
					</div>
				</div>
			<?php echo form_close();?>
			<div class="result form-result"><div class="result-content"></div></div>
		</div>
	</div>
	<div class="modal" tabindex="-1" role="dialog" id="modal-sms-confirm">
		<div class="modal-dialog modal-dialog-centered modal-sm" role="document">
			<div class="modal-content">
				<div class="modal-body">
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
	</div>
</div>
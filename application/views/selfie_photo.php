<?php echo form_open_multipart($formUrl,['class' => 'photo-form selfie-photo-form']);?>
	<div class="photo-selfie-wrapper">
		<h4 class="text-center"><?php echo $this->lang->line('Selfie photo with passport in hand');?></h4>
		<div class="form-group">
			<div class="col-sm-12">
				<div class="photo-wrapper mt-30">
					<?php echo form_label('','camera-input-file');?>
				</div>
			</div>
			<div class="col-sm-12">
				<div class="photo-requirements row mt-30">
					<p class="mb-20"><?php echo $this->lang->line('Photo requirements');?>:</p>
					<p><?php echo $this->lang->line('Photos must be clear');?></p>
					<p><?php echo $this->lang->line('Valid format: jpeg,png');?></p>
					<p><?php echo $this->lang->line('Size: min: 200 Kb.');?></p>
					<p><?php echo $this->lang->line('Max: 4Mb.');?></p>
				</div>
				<div class="photo-step-errors"></div>
			</div>
			<div class="col-sm-12">
				<?php echo form_upload(['name' => 'file-selfie','id' => 'camera-input-file','class' => 'inputfile_','data-multiple-caption' => '{count} files selected','data-remove-text' => $this->lang->line('Remove'),'capture' => 'user','accept' => 'image/*']);?>
				<?php echo form_label('<span>'.$this->lang->line('Take snapshot').'</span>','camera-input-file');?>
				<div class="text-center">
					<?php echo form_button(['type' => 'submit', 'content' => $this->lang->line('Further'),'class' => 'form-submit-btn mt-20 btn-next-step hidden','disabled' => 'disabled']);?>
				</div>
			</div>
		</div>
	</div>
<?php echo form_close();?>
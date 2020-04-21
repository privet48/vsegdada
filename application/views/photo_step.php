<div class="content photo-step-wrapper">
	<h3 class="step-title"><?php echo $this->lang->line('ID confirmation');?></h3>
	<?php $this->load->view('_steps');?>
	<div class="clearfix"></div>	
	<?php echo form_open_multipart($formUrl,['class' => 'photo-form']);?>
		<div class="fadeOut photo-carousel-wrapper owl-carousel owl-theme">
			<div class="item">
				<?php 
					$class='photo-wrapper';
					$style='';	
					$errors='';
					if(!empty($images)&&in_array('PHOTO_SELFIE_WITH_PASSPORT',array_keys($images['documents']))){
						$class.=' has-photo';
						if(isset($images['documents']['PHOTO_SELFIE_WITH_PASSPORT']['url'])){
							$path=$images['documents']['PHOTO_SELFIE_WITH_PASSPORT']['url'];
							$path=str_replace($_SERVER['DOCUMENT_ROOT'],'',$path);
							$style='style="background-image:url('.$path.')"';
						}
						$errors=$images['documents']['PHOTO_SELFIE_WITH_PASSPORT']['error'];
					}
				?>
				<h4 class="text-center"><?php echo $this->lang->line('Selfie photo with passport in hand');?></h4>
				<div class="form-group flex-container <?php if($errors!==''){?> has-errors<?php }?>">
					<div class="col-sm-4">
						<div class="row">
							<div class="<?php echo $class;?>" <?php echo $style;?>>
								<?php if(!$isMobile){?>
									<?php echo anchor('#modal-load-selfie',' ',['data-toggle' => 'modal','class' => 'label-modal-selfie']);?>
								<?php }else{?>								
									<?php echo form_label('','input-file-self');?>
								<?php }?>
							</div>
						</div>
					</div>
					<div class="col-sm-5">
						<?php if($errors==''){?>
							<div class="photo-requirements row">
								<p class="mb-20"><?php echo $this->lang->line('Photo requirements');?>:</p>
								<p><?php echo $this->lang->line('Photos must be clear');?></p>
								<p><?php echo $this->lang->line('Valid format: jpeg,png');?></p>
								<p><?php echo $this->lang->line('Size: min: 200 Kb.');?></p>
								<p><?php echo $this->lang->line('Max: 4Mb.');?></p>
							</div>
						<?php }else{?>
							<div class="photo-step-errors">
								<?php echo $errors;?>
							</div>
						<?php }?>
						<div class="photo-step-errors"></div>
					</div>
					<div class="col-sm-3">
						<?php if(!$isMobile){?>
							<?php echo form_hidden('file-self');?>
							<?php echo anchor('#modal-load-selfie','<span>'.$this->lang->line('Upload').'</span>',['data-toggle' => 'modal','class' => 'btn-modal-selfie']);?>
						<?php }else{?>
							<?php echo form_upload(['name' => 'file-self','id' => 'input-file-self','class' => 'inputfile','data-multiple-caption' => '{count} files selected','data-remove-text' => $this->lang->line('Remove'),'capture' => 'user','accept' => 'image/*']);?>
							<?php echo form_label('<span>'.$this->lang->line('Upload').'</span>','input-file-self',['tabindex' => -1]);?>
						<?php }?>
					</div>
				</div>					
			</div>
			<div class="item">
				<?php 
					$class='photo-wrapper';
					$style='';
					$errors='';
					if(!empty($images)&&in_array('PASSPORT_2_3P',array_keys($images['documents']))){
						if(isset($images['documents']['PASSPORT_2_3P']['url'])){
							$class.=' has-photo';
							$path=$images['documents']['PASSPORT_2_3P']['url'];
							$path=str_replace($_SERVER['DOCUMENT_ROOT'],'',$path);
							$style='style="background-image:url('.$path.')"';
						}
						$errors=$images['documents']['PASSPORT_2_3P']['error'];
					}
				?>		
				<h4 class="text-center"><?php echo $this->lang->line('First U-Turn Photo');?></h4>
				<div class="form-group flex-container <?php if($errors!==''){?> has-errors<?php }?>">
					<div class="col-sm-4">
						<div class="row">
							<div class="<?php echo $class;?>" <?php echo $style;?>>
								<?php echo form_label('','input-file-first-u-turn');?>
							</div>
						</div>
					</div>
					<div class="col-sm-5">
						<?php if($errors==''){?>
							<div class="photo-requirements row">
								<p class="mb-20"><?php echo $this->lang->line('Photo requirements');?>:</p>
								<p><?php echo $this->lang->line('Photos must be clear');?></p>
								<p><?php echo $this->lang->line('Valid format: jpeg,png');?></p>
								<p><?php echo $this->lang->line('Size: min: 200 Kb.');?></p>
								<p><?php echo $this->lang->line('Max: 4Mb.');?></p>
							</div>
						<?php }else{?>
							<div class="photo-step-errors">
								<?php echo $errors;?>
							</div>
						<?php }?>
						<div class="photo-step-errors"></div>
					</div>
					<div class="col-sm-3">
						<?php echo form_upload(['name' => 'file-turn','id' => 'input-file-first-u-turn','class' => 'inputfile','data-multiple-caption' => '{count} files selected','data-remove-text' => $this->lang->line('Remove'),'accept' => 'image/*']);?>
						<?php echo form_label('<span>'.$this->lang->line('Upload').'</span>','input-file-first-u-turn');?>
					</div>
				</div>
			</div>
			<div class="item">
				<?php 
					$class='photo-wrapper';
					$style='';	
					$errors='';					
					if(!empty($images)&&in_array('PASSPORT_REG',array_keys($images['documents']))){
						if(isset($images['documents']['PASSPORT_REG']['url'])){
							$class.=' has-photo';
							$path=$images['documents']['PASSPORT_REG']['url'];
							$path=str_replace($_SERVER['DOCUMENT_ROOT'],'',$path);
							$style='style="background-image:url('.$path.')"';
						}
					}
				?>			
				<h4 class="text-center"><?php echo $this->lang->line('Photo of the registration page');?></h4>
				<div class="form-group flex-container">
					<div class="col-sm-4">
						<div class="row">
							<div class="<?php echo $class;?>" <?php echo $style;?>>
								<?php echo form_label('','input-file-registration');?>
							</div>
						</div>
					</div>
					<div class="col-sm-5">
						<div class="photo-requirements row">
							<p class="mb-20"><?php echo $this->lang->line('Photo requirements');?>:</p>
							<p><?php echo $this->lang->line('Photos must be clear');?></p>
							<p><?php echo $this->lang->line('Valid format: jpeg,png');?></p>
							<p><?php echo $this->lang->line('Size: min: 200 Kb.');?></p>
							<p><?php echo $this->lang->line('Max: 4Mb.');?></p>
						</div>
						<div class="photo-step-errors"></div>
					</div>
					<div class="col-sm-3">
						<?php echo form_upload(['name' => 'file-registration','id' => 'input-file-registration','class' => 'inputfile','data-multiple-caption' => '{count} files selected','data-remove-text' => $this->lang->line('Remove'),'accept' => 'image/*']);?>
						<?php echo form_label('<span>'.$this->lang->line('Upload').'</span>','input-file-registration');?>
					</div>
				</div>
			</div>					
		</div>
		<div class="col-sm-8 col-sm-offset-2">
			<div class="row">
				<div class="form-button-wrapper-align-right">
					<?php echo form_button(['type' => 'submit', 'content' => $this->lang->line('Further'),'class' => 'form-submit-btn mt-20 btn-next-step']);?>
				</div>
			</div>
		</div>
	<?php echo form_close();?>
	<div class="result form-result"><div class="result-content"></div></div>
</div>
<?php if(!$isMobile){?>
	<div class="modal" tabindex="-1" role="dialog" id="modal-load-selfie">
		<div class="modal-dialog modal-dialog-centered modal-sm" role="document">
			<div class="modal-content">
				<div class="col-sm-12">
					<div class="modal-content-wrapper">
						<p class="mt-20"><?php echo $this->lang->line('To continue, you need to take a picture from the camera of your phone.');?></p>
						<p><?php echo $this->lang->line('A message will be sent to your phone number with a link by which you must go.');?></p>
						<div class="text-center">
							<?php echo anchor(base_url('main/photo'),$this->lang->line('Send'),['class' => 'form-submit-btn photo-link-btn mt-20 mb-20 btn-next-step btn-block']);?>
						</div>
					</div>
				</div>
				<div class="clearfix"></div>
			</div>
		</div>
	</div>
<?php }?>
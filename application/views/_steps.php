<div class="steps-wrapper mt-10 mb-20 flex-container">
	<?php foreach(config_item('steps') as $key => $step){?>
		<?php 
			if(!$step['is_front']){
				continue;
			}
			$class='';
			if($this->auth_current_step-1==$key){
				$class=' current-step';
			}elseif($this->auth_current_step-1>$key){
				$class=' previous-step';
			}
		?>
			<p class="step-item<?php echo $class;?>"><?php echo sprintf($this->lang->line('Step %s'),$key);?></p>
	<?php }?>
</div>
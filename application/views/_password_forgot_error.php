<div class="col-sm-12">
	<h3 class="text-center title-small"><?php echo $this->lang->line('Restore password');?></h3>
	<p><?php echo sprintf($this->lang->line('Password\'s already sent to your email. Next send\'ll be available at %s at %s'),date('Y-m-d',strtotime($leftTime)),date('H:i',strtotime($leftTime)));?></p>
</div>
<div class="clearfix"></div>
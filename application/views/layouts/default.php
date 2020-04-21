<!DOCTYPE html>
<html lang="ru">
	<head>
		<base href="<?php echo base_url();?>">
		<meta name="viewport" content="width=device-width, initial-scale=1" />
		<link rel="stylesheet" type="text/css" href="<?php echo asset_url();?>css/bootstrap/css/bootstrap.min.css" />
		<link rel="stylesheet" type="text/css" href="<?php echo asset_url();?>css/owlcarousel/owl.carousel.min.css" />
		<link rel="stylesheet" type="text/css" href="<?php echo asset_url();?>css/owlcarousel/owl.theme.default.min.css" />
		<link rel="stylesheet" type="text/css" href="<?php echo asset_url();?>css/main.css" />
		<script type="text/javascript" src="<?php echo asset_url();?>js/jquery/jquery.js"></script>
		<script type="text/javascript" src="<?php echo asset_url();?>js/jquery/jquery.mask.min.js"></script>
		<script type="text/javascript" src="<?php echo asset_url();?>js/bootstrap/bootstrap.min.js"></script>
		<script type="text/javascript" src="<?php echo asset_url();?>js/owlcarousel/owl.carousel.min.js"></script>
		<script type="text/javascript" src="<?php echo asset_url();?>js/main.js"></script>
	</head>
	<body>
		<div class="header-wrapper">
			<div class="container">
				<div class="row">
					<div class="col-sm-8 col-sm-offset-2">
						<div class="row">
							<div class="header<?php if(empty($this->auth_role)){?> registration-step text-center<?php }?>">
								<div class="header-block-item">
									<?php echo anchor('/',img(['src' => asset_url().'images/logo.svg']),['class' => 'header-logo']);?>
								</div>
								<?php if(!empty($this->auth_role)){?>
									<div class="header-block-item">
										<div class="auth-information-wrapper">
											<p class="d-inline"><?php echo $this->auth_userlastname;?> <?php echo $this->auth_username;?> <?php echo $this->auth_usermiddlename;?></p>
											<div class="logout-btn-wrapper d-inline">
												<?php echo anchor('/logout',$this->lang->line('Logout'),['class' => 'header-auth-link']);?>
											</div>
										</div>
									</div>
								<?php }?>
							</div>
						</div>
					</div>
				</div>
			</div>
		</div>
		<div class="container">
			<div class="row">
				<div class="row desctop-row">
					<div class="row desctop-row">
						<div class="col-sm-8 col-sm-offset-2">
							<div class="content-wrapper">
								<?php $this->load->view($view,$data);?>
								<div class="clearfix"></div>
								<div class="loading-wrapper">
									<?php echo img(['src' => asset_url().'images/loading.gif','class' => 'loading']); ?>
								</div>
							</div>
						</div>
					</div>
				</div>
			</div>
		</div>
		<?php $this->load->view('_footer');?>
	</body>
</html>
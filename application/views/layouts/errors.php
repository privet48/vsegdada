<!DOCTYPE html>
<html lang="ru">
	<head>
		<link rel="stylesheet" type="text/css" href="<?php echo asset_url();?>css/bootstrap/css/bootstrap.min.css" />
		<link rel="stylesheet" type="text/css" href="<?php echo asset_url();?>css/main.css" />
	</head>
	<body>
		<div class="header-wrapper">
			<div class="container">
				<div class="row">
					<div class="col-sm-10 col-sm-offset-1">
						<div class="header<?php if(empty($this->auth_role)){?> registration-step text-center<?php }?>">
							<div class="header-block-item">
								<?php echo anchor('/','<img src="'.asset_url().'images/logo.svg">',['class' => 'header-logo']);?>
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
		<div class="container">
			<div class="row">
				<div class="col-sm-10 col-sm-offset-1">
					<div class="content-wrapper">
						<?php $this->load->view($view,$data);?>
						<div class="clearfix"></div>
					</div>
				</div>
			</div>
		</div>
	</body>
</html>
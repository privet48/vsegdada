<ul>
	<?php foreach($results as $count=>$item):?>
		<li><?php echo anchor('#',$item->value,['data-id' => $count]);?></li>
	<?php endforeach;?>
</ul>
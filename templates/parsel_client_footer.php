<?php

$image     = $data['image'];
$url       = $data['url'];
$button_bg = $data['button_bg'];

?>

<style type="text/css" media="all">
    .parsel-store-button {position:fixed; top:50%; right:0; background:<?php echo $button_bg ?>; z-index:+9999; padding:7px 1px 7px 3px;}
    .parsel-store-button a { display:block !important; }
</style>


<div class='parsel-store-button'>
	<a href='<?php echo $url ?>'><img src="<?php echo $image ?>" alt="Parsel Store"/></a>
</div>




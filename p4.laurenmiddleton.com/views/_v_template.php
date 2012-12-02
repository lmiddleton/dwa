<!DOCTYPE html>
<html>
<head>
	<title><?=@$title; ?></title>

	<meta http-equiv="Content-Type" content="text/html; charset=UTF-8" />	
	
	<!-- CSS -->
	<link rel="stylesheet" type="text/css" href="/css/reset.css">
	<link rel="stylesheet" type="text/css" href="http://code.jquery.com/ui/1.9.2/themes/base/jquery-ui.css">
	<link rel="stylesheet" type="text/css" href="/css/lkm-styles.css">
	
	<!-- JS -->
	<script src="http://code.jquery.com/jquery-1.8.3.js"></script>
	<script src="http://code.jquery.com/ui/1.9.2/jquery-ui.js"></script>
				
	<!-- Controller Specific JS/CSS -->
	<?php echo @$client_files; ?>
	
</head>

<body>	

	<?=$content;?> 

</body>
</html>
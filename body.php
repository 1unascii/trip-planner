<body class="ui-widget-overlay">

<!--STYLESHEET-->
<link href="css/body.css" rel="stylesheet" type="text/css" />

<script type="text/javascript">
$( document ).ready(function(
	$( ".ui-icon-action" ).on( "click", function() {
	  $( "#open-left" ).trigger( "click" );
	});
	
));
</script>

<!--OPEN SIDEBAR BUTTON -->
<div id="floating-panel">
	<a href="#" id="open-left"><div class="ui-icon ui-icon-action"></div></a>
	<a href="#" id="open-right"><div class="ui-icon ui-icon-gear"></div></a>
</div> 

<!--MAP CONTAINER-->
<iframe id="map" 
		style="margin:-7px;border:0" 
		marginwidth="0" 
		width="100%" 
		height="100%" 
		src="map/index.php?center=Madison+WI&addresses=0">
</iframe> 				<!--MAP CENTER AND ADDRESS ARRAY-->

<?php include 'sidebar.php'; ?>

</body>

<body class="ui-widget-overlay">

<!--STYLESHEET-->
<link href="css/body.css" rel="stylesheet" type="text/css" />

<!--OPEN SIDEBAR BUTTON -->
<div id="floating-panel">
	<a href="#" id="open-left" title="Stops">&#9776;</a>
	<a href="#" id="open-right" title="Settings">&#9881;</a>
</div>

<!--MAP CONTAINER-->
<iframe id="map"
		style="margin:-7px;border:0"
		marginwidth="0"
		width="100%"
		height="100%"
		src="map/index.php?center=Madison+WI&addresses=0">
</iframe>

<?php include 'sidebar.php'; ?>

</body>

<?php
session_start();
?>
<html>
	<head>
		<meta charset="utf-8">
		<meta name="viewport" content="width=device-width">
		<title>pmms</title>
		<script src="index.js"></script>
		<link rel="stylesheet" href="fontawesome-free-6.2.0-web/css/solid.css">
		<link rel="stylesheet" href="index.css">
	</head>
	<body>
		<div id="main">
			<div id="title">
				<div>🐩</div>
				<div>PMMS</div>
				<div id="subtitle">Poodle's Multimedia System</div>
			</div>
			<button id="catalog">
				<i class="fas fa-grip-horizontal"></i> Browse
			</button>
			<hr>
			<form action="create.php" id="create-room">
				<input type="text" name="url" id="url" placeholder="Enter media URL...">
				<button type="submit">
					<i class="fas fa-circle-right"></i>
				</button>
			</form>
		</div>
	</body>
</html>

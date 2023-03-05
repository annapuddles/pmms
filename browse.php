<?php
include "pmms.php";

session_start();
?>
<html>
	<head>
		<meta charset="utf-8">
		<meta name="viewport" content="width=device-width">
		<title>pmms - Browsing the catalog</title>
		<script src="browse.js"></script>
		<link rel="stylesheet" href="browse.css">
	</head>
	<body>
		<div id="header">
			<div id="navigation">
				<button id="home">
					<i class="fa-solid fa-home"></i>
				</button>
			</div>
			<div id="filters">
				<div id="categories">
					<button id="back">
						<i class="fa-solid fa-caret-left"></i>
					</button>
					<button class="category-button">
						<i class="fas fa-grip-horizontal"></i>
						<span>All</span>
					</button>
					<button class="category-button" data-category="movie">
						<i class="fas fa-film"></i>
						<span>Movies</span>
					</button>
					<button class="category-button" data-category="tv">
						<i class="fas fa-tv"></i>
						<span>TV</span>
					</button>
					<button class="category-button" data-category="music">
						<i class="fas fa-music"></i>
						<span>Music</span>
					</button>
					<button id="forward">
						<i class="fa-solid fa-caret-right"></i>
					</button>
				</div>
				<div id="search">
					<select id="genre">
						<option value="">All genres</option>
					</select>
					<input type="text" id="query" placeholder="Enter search terms...">
					<button id="clear-search"><i class="fa-solid fa-xmark"></i></button>
					<button id="search-button"><i class="fas fa-search"></i> Search</button>
				</div>
			</div>
		</div>
		<div id="catalog"></div>
		<div id="custom-popup" style="display: none">
			<button id="close-custom-popup">
				<i class="fas fa-xmark"></i>
			</button>
			<input type="text" id="custom-url" name="url" placeholder="Enter media URL...">
			<button id="create-custom-room">
				<i class="fas fa-circle-right"></i>
			</button>
		</div>
	</body>
</html>

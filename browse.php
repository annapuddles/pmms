<?php
include "pmms.php";

session_start();
?>
<html>
	<head>
		<meta charset="utf-8">
		<meta name="viewport" content="width=device-width">
		<title>pmms</title>
		<script src="browse.js"></script>
		<link rel="stylesheet" href="browse.css">
	</head>
	<body>
		<button id="home">
			<i class="fas fa-house"></i>
		</button>
		<div id="categories">
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
		</div>
		<div id="search">
			<select id="genre">
				<option value="">All genres</option>
			</select>
			<input type="text" id="query">
			<button id="clear-search"><i class="fa-solid fa-xmark"></i></button>
			<button id="search-button"><i class="fas fa-search"></i> Search</button>
		</div>
		<div id="catalog"></div>
	</body>
</html>

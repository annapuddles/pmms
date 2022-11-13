window.addEventListener('load', function() {
	let url = new URL(window.location);
	let roomKey = url.searchParams.get('room');
	let series = url.searchParams.get('series');
	let category = url.searchParams.get('category');
	let genre = url.searchParams.get('genre');

	let homeButton = document.getElementById('home');
	let catalogDiv = document.getElementById('catalog');

	let allButton = document.getElementById('category-all');
	let movieButton = document.getElementById('category-movie');
	let tvButton = document.getElementById('category-tv');
	let musicButton = document.getElementById('category-music');

	let genreSelect = document.getElementById('genre');

	let searchQuery = document.getElementById('query');
	let clearSearchButton = document.getElementById('clear-search');
	let searchButton = document.getElementById('search-button');

	homeButton.addEventListener('click', function() {
		window.location = '.';
	});

	document.querySelectorAll('.category-button').forEach(button => {
		let buttonCategory = button.getAttribute('data-category');

		button.addEventListener('click', function() {

			if (buttonCategory) {
				url.searchParams.set('category', buttonCategory);
			} else {
				url.searchParams.delete('category');
			}

			url.searchParams.delete('genre');
			url.searchParams.delete('series');
			url.searchParams.delete('query');
			window.location = url.toString();
		});

		if (series || buttonCategory != category) {
			button.style.color = 'grey';
		} else {
			button.style.color = 'black';
		}
	});

	if (series) {
		genreSelect.style.display = 'none';
	} else {
		let genresUrl = 'genres.php';

		if (category) {
			genresUrl += `?category=${category}`;
		}

		fetch(genresUrl).then(resp => resp.json()).then(data => {
			genreSelect.innerHTML = '<option value="">All genres</option>';

			data.forEach(genre => {
				let option = document.createElement('option');
				option.value = genre;
				option.innerHTML = genre;
				genreSelect.appendChild(option);
			});

			if (genre) {
				genreSelect.value = genre;
			}
		});

		genreSelect.addEventListener('change', function() {
			if (this.value == '') {
				url.searchParams.delete('genre');
			} else {
				url.searchParams.set('genre', this.value);
			}

			window.location = url.toString();
		});
	}

	clearSearchButton.addEventListener('click', function() {
		if (url.searchParams.get('query')) {
			url.searchParams.delete('query');
			window.location = url.toString();
		} else {
			searchQuery.value = '';
			this.style.display = 'none';
		}
	});

	searchButton.addEventListener('click', function() {
		if (searchQuery.value == '') {
			url.searchParams.delete('query');
		} else {
			url.searchParams.set('query', searchQuery.value);
		}
		window.location = url.toString();
	});

	searchQuery.addEventListener('keyup', function(e) {
		if (e.code == 'Enter') {
			if (this.value == '') {
				url.searchParams.delete('query');
			} else {
				url.searchParams.set('query', this.value);
			}
			window.location = url.toString();
		} else if (searchQuery.value == '') {
			clearSearchButton.style.display = 'none';
		} else {
			clearSearchButton.style.display = null;
		}
	});

	searchQuery.value = url.searchParams.get('query');

	if (searchQuery.value == '') {
		clearSearchButton.style.display = 'none';
	}

	function addCatalogEntryClickListener(div, entry) {
		div.addEventListener('click', () => {
			if (entry.url == null) {
				url.searchParams.set('series', entry.id);
				url.searchParams.delete('category');
				url.searchParams.delete('genre');
				url.searchParams.delete('query');
				window.location = url.toString();
			} else {
				if (roomKey == null) {
					window.location = `create.php?url=${entry.url}&title=${entry.title}`;
				} else {
					fetch(`enqueue.php?room=${roomKey}&url=${encodeURI(entry.url)}&title=${entry.title}`).then(resp => {
						window.location = `join.php?room=${roomKey}`;
					});
				}
			}
		});
	}

	let catalogUrl = 'catalog.php?' + url.searchParams.toString();

	fetch(catalogUrl).then(resp => resp.json()).then(data => {
		if (data.length == 0) {
			catalogDiv.innerHTML = '<div id="no-results">No results found.</div>';
		} else {
			if (series && searchQuery.value == '') {
				let playAllDiv = document.createElement('div');

				playAllDiv.className = 'catalog-entry';

				playAllDiv.innerHTML = '<div class="cover"><button><i class="fas fa-play"></i></button></div><div class="title">Play All</div>';

				addCatalogEntryClickListener(playAllDiv, {url: encodeURI("series=" + series)}, roomKey);

				catalogDiv.appendChild(playAllDiv);
			}

			data.forEach(entry => {
				let div = document.createElement('div');

				div.className = 'catalog-entry';

				addCatalogEntryClickListener(div, entry, roomKey);

				let coverDiv = document.createElement('div');
				coverDiv.className = 'cover';
				let coverImg = document.createElement('img');
				coverImg.src = entry.cover;
				coverDiv.appendChild(coverImg);
				div.appendChild(coverDiv);

				let titleDiv = document.createElement('div');
				titleDiv.className = 'title';
				titleDiv.innerHTML = entry.title;
				div.appendChild(titleDiv);

				catalogDiv.appendChild(div);
			});
		}
	});
});

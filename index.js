window.addEventListener('load', function() {
	let url = new URL(window.location);
	let familyMode = url.searchParams.get('family');

	document.getElementById('catalog').addEventListener('click', function() {
		if (familyMode) {
			window.location = 'browse.php?family=' + familyMode;
		} else {
			window.location = 'browse.php';
		}
	});
});

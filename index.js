window.addEventListener('load', function() {
	let url = new URL(window.location);
	let familyMode = url.searchParams.get('family');
	let joinRoom = url.searchParams.get('join');

	document.getElementById('catalog').addEventListener('click', function() {
		let params = new URLSearchParams();

		if (familyMode) {
			params.set('family', familyMode);
		}

		if (joinRoom) {
			params.set('join', joinRoom);
		}

		window.location = 'browse.php?' + params.toString();
	});
});

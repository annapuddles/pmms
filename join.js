const syncInterval = 1000;
const queueUpdateInterval = 2000;
const maxRoomSyncAttempts = 3;
const mediaReadyTimeout = 5000;

let syncTolerance = 2;
let media = null;
let currentUrl = null;
let roomSyncAttempts = maxRoomSyncAttempts;

function timeToString(time) {
	if (time == null || time <= 0) {
		return '--:--:--';
	}

	var h = Math.floor(time / 60 / 60);
	var m = Math.floor(time / 60) % 60;
	var s = Math.floor(time) % 60;

	if (isNaN(h)) h = 0;
	if (isNaN(m)) m = 0;
	if (isNaN(s)) s = 0;

	return String(h).padStart(2, '0') + ':' + String(m).padStart(2, '0') + ':' + String(s).padStart(2, '0');
}

function setCaptions(name) {
	let video = document.querySelector('video');

	for (let i = 0; i < video.textTracks.length; ++i) {
		if (video.textTracks[i].label == name) {
			video.textTracks[i].mode = 'showing';
		} else {
			video.textTracks[i].mode = 'hidden';
		}
	}

	localStorage.captions = name;
}

window.addEventListener('load', () => {
	let url = new URL(window.location);
	let roomKey = url.searchParams.get("room");
	let videoContainer = document.getElementById('video-container');
	let playButton = document.getElementById('play');
	let progressBar = document.getElementById('progress');
	let currentTimecode = document.getElementById('current-timecode');
	let durationTimecode = document.getElementById('duration-timecode');
	let exitButton = document.getElementById('exit');
	let nextButton = document.getElementById('next');
	let queueList = document.getElementById('queue-list');
	let loopButton = document.getElementById('loop');
	let fullscreenButton = document.getElementById('fullscreen');
	let volumeSlider = document.getElementById('volume');
	let volumeStatus = document.getElementById('volume-status');
	let lockButton = document.getElementById('lock');
	let addMediaButton = document.getElementById('add-media');
	let queueButton = document.getElementById('queue-button');
	let roomSettingsButton = document.getElementById('room-settings-button');
	let queueContainer = document.getElementById('queue-container');
	let roomSettingsContainer = document.getElementById('room-settings-container');
	let clearQueueButton = document.getElementById('clear-queue');
	let sourceSelect = document.getElementById('source');
	let seekBackwardButton = document.getElementById('seek-backward');
	let seekForwardButton = document.getElementById('seek-forward');
	let currentVideoTitle = document.getElementById('current-video-title');
	let pinnedButton = document.getElementById('pinned');
	let shuffleButton = document.getElementById('shuffle');
	let connectionLostNotice = document.getElementById('connection-lost-notice');
	let captionsSelect = document.getElementById('captions');
	let catalogViewer = document.getElementById('catalog-viewer');
	let catalogContainer = document.getElementById('catalog-container');
	let closeCatalogButton = document.getElementById('close-catalog');

	playButton.setPauseIcon = function() {
		this.innerHTML = '<i class="fas fa-pause"></i>';
		this.icon = "pause";
	}

	playButton.setPlayIcon = function() {
		this.innerHTML = '<i class="fas fa-play"></i>';
		this.icon = "play";
	}

	playButton.icon = "pause";

	loopButton.setLoopOneIcon = function() {
		this.style.color = 'black';
		this.icon = "loop-one";
		this.innerHTML = '<i class="fas fa-repeat"></i>';
	}

	loopButton.setLoopAllIcon = function() {
		this.style.color = 'black';
		this.icon = 'loop-all';
		this.innerHTML = '<i class="fas fa-repeat"></i><i class="fas fa-list"></i>';
	}

	loopButton.setContinueIcon = function() {
		this.style.color = 'grey';
		this.icon = "continue";
		this.innerHTML = '<i class="fas fa-repeat"></i>';
	}

	loopButton.mode = 0;

	lockButton.setLockedIcon = function() {
		this.innerHTML = '<i class="fas fa-lock"></i>';
		this.icon = "locked";
	}

	lockButton.setUnlockedIcon = function() {
		this.innerHTML = '<i class="fas fa-lock-open"></i>';
		this.icon = "unlocked";
	}

	lockButton.icon = "unlocked";

	volumeStatus.updateIcon = function() {
		if (media != null && media.muted) {
			this.innerHTML = '<i class="fas fa-volume-mute"></i>';
		} else if (volumeSlider.value == 0) {
			this.innerHTML = '<i class="fas fa-volume-off"></i>';
		} else if (volumeSlider.value < 50) {
			this.innerHTML = '<i class="fas fa-volume-down"></i>';
		} else {
			this.innerHTML = '<i class="fas fa-volume-up"></i>';
		}
	}

	volumeStatus.updateIcon();

	function disableControls(disabled) {
		lockButton.disabled = disabled;
		playButton.disabled = disabled;
		progressBar.disabled = disabled;
		nextButton.disabled = disabled;
		loopButton.disabled = disabled;
		catalogButton.disabled = disabled;
		clearQueueButton.disabled = disabled;
		shuffleButton.disabled = disabled;
		seekForwardButton.disabled = disabled;
		seekBackwardButton.disabled = disabled;
	}

	setInterval(() => {
		let url;

		if (sourceSelect.value == 'default') {
			url = `sync.php?room=${roomKey}`;
		} else {
			url = `sync.php?room=${roomKey}&source=${sourceSelect.value}`;
		}

		fetch(url)
		.then(resp => {
			if (resp.ok) {
				return resp.json();
			} else {
				reject();
			}
		})
		.then(resp => {
			roomSyncAttempts = maxRoomSyncAttempts;
			if (connectionLostNotice.style.display != "none") {
				connectionLostNotice.style.display = "none";
			}

			if (resp.locked) {
				if (lockButton.icon == "unlocked") {
					lockButton.setLockedIcon();

					if (resp.is_owner) {
						disableControls(false);
					} else {
						disableControls(true);
					}
				}
			} else {
				if (lockButton.icon == "locked") {
					lockButton.setUnlockedIcon();
					disableControls(false);
				}
			}

			lockButton.disabled = !resp.is_owner;

			if (media == null || currentUrl != resp.url) {
				currentUrl = resp.url;

				if (media != null) {
					media.remove();
					media = null;
				}

				videoContainer.innerHTML = '';

				let video = document.createElement('video');
				video.id = 'video';
				video.src = resp.url;
				video.style.display = 'none';
				videoContainer.appendChild(video);

				fetch(`captions.php?room=${roomKey}`).then(resp => resp.json()).then(captions => {
					captions.forEach(captions => {
						let track = document.createElement('track');
						track.kind = 'subtitles';
						track.label = captions.name;
						track.src = captions.url;
						video.appendChild(track);
					});

					if (localStorage.captions) {
						setCaptions(localStorage.captions);
					}

					let selectedCaptions = captionsSelect.value;

					captionsSelect.innerHTML = '<option>off</option>';

					captions.forEach(captions => {
						let option = document.createElement('option');

						option.value = captions.name;
						option.innerHTML = captions.name;

						if (captions.name == localStorage.captions) {
							option.selected = true;
						}

						captionsSelect.appendChild(option);
					});

					media = new MediaElement('video');

					media.style.display = 'none';

					media.isLive = () => false;

					let failedToLoad = setTimeout(() => {
						if (media) {
							media.remove();
							media = null;
						}
					}, mediaReadyTimeout);

					media.addEventListener('canplay', () => {
						clearTimeout(failedToLoad);

						media.style.display = null;

						progressBar.max = media.duration;
						durationTimecode.innerHTML = timeToString(media.duration);

						media.volume = volumeSlider.value / 100;

						if (localStorage.muted) {
							media.muted = localStorage.muted == 'true';
						}

						media.isLive = () =>
							media.duration == 0 || media.duration == Infinity ||
							(media.youTubeApi && media.youTubeApi.getVideoData().isLive);

						media.play();

						media.isReady = true;
					});

					media.addEventListener('volumechange', () => {
						volumeStatus.updateIcon();
					});

					fetch(`sources.php?room=${roomKey}`).then(resp => resp.json()).then(resp => {
						let selected = sourceSelect.value;

						sourceSelect.innerHTML = '<option>default</option>';

						resp.forEach(source => {
							let option = document.createElement('option');

							option.value = source;
							option.innerHTML = source;

							if (selected == source) {
								option.selected = true;
							}

							sourceSelect.appendChild(option);
						});
					});
				});
			}

			if (media == null) {
				return;
			}

			let title = resp.title || resp.url;
			if (currentVideoTitle.innerHTML != title) {
				currentVideoTitle.innerHTML = title;
				document.title = 'pmms - Watching: ' + title;
			}

			nextButton.queueId = resp.next;

			if (loopButton.mode != resp.loop) {
				if (resp.loop == 0) {
					loopButton.setContinueIcon();
				} else if (resp.loop == 1) {
					loopButton.setLoopOneIcon();
				} else if (resp.loop == 2) {
					loopButton.setLoopAllIcon();
				}

				loopButton.mode = resp.loop;
			}

			if (resp.paused == null) {
				let currentTime;
				let duration;

				if (media.isLive()) {
					playButton.disabled = true;
					progressBar.disabled = true;
					seekForwardButton.disabled = true;
					seekBackwardButton.disabled = true;

					currentTime = -1;
					duration = -1;
				} else {
					if (!resp.locked) {
						playButton.disabled = false;
						progressBar.disabled = false;
						seekForwardButton.disabled = false;
						seekBackwardButton.disabled = false;
					}

					currentTime = resp.time;
					duration = media.duration;

					if (resp.loop == 1) {
						currentTime %= media.duration;
					} else {
						if (media.isReady && currentTime >= media.duration) {
							if (nextButton.queueId != null) {
								fetch(`dequeue.php?room=${roomKey}&id=${nextButton.queueId}`);
							}

							currentTime = media.duration;

							progressBar.value = currentTime;
							currentTimecode.innerHTML = timeToString(currentTime);

							media.pause();

							return;
						}
					}

					if (Math.abs(media.currentTime - currentTime) > syncTolerance) {
						media.currentTime = currentTime;
					}
				}

				progressBar.value = currentTime;
				currentTimecode.innerHTML = timeToString(currentTime);

				progressBar.max = duration;
				durationTimecode.innerHTML = timeToString(duration);

				if (media.paused) {
					media.play();
				}

				if (playButton.icon == "play") {
					playButton.setPauseIcon();
				}
			} else {
				let currentTime = resp.paused - resp.start_time;

				progressBar.value = currentTime;
				currentTimecode.innerHTML = timeToString(currentTime);

				if (Math.abs(media.currentTime - currentTime) > syncTolerance) {
					media.currentTime = currentTime;
				}

				if (!media.paused) {
					media.pause();
				}

				if (playButton.icon == "pause") {
					playButton.setPlayIcon();
				}
			}
		})
		.catch(err => {
			if (roomSyncAttempts == 0) {
				if (connectionLostNotice.style.display != 'block') {
					connectionLostNotice.style.display = 'block';
				}
			} else {
				--roomSyncAttempts;
			}
		});
	}, syncInterval);

	setInterval(() => {
		fetch(`queue.php?room=${roomKey}`).then(resp => resp.json()).then(resp => {
			queueList.innerHTML = '';

			resp.forEach(item => {
				let queueItem = document.createElement('div');
				queueItem.className = "queue-item";
				queueItem.innerHTML = item.title;
				queueItem.addEventListener('click', () => {
					fetch(`dequeue.php?room=${roomKey}&id=${item.id}`);
				});
				queueList.appendChild(queueItem);
			});
		});
	}, queueUpdateInterval);

	playButton.addEventListener('click', function() {
		if (media.paused) {
			fetch(`resume.php?room=${roomKey}`);
		} else {
			fetch(`pause.php?room=${roomKey}`);
		}
	});

	progressBar.addEventListener('input', function() {
		fetch(`seek.php?room=${roomKey}&time=${this.value}`);
	});

	exitButton.addEventListener('click', function() {
		if (history.length > 1) {
			history.back();
		} else {
			window.location = 'browse.php';
		}
	});

	nextButton.addEventListener('click', function() {
		if (this.queueId != null) {
			fetch(`dequeue.php?room=${roomKey}&id=${this.queueId}`);
		}
	});

	loopButton.addEventListener('click', function() {
		fetch(`loop.php?room=${roomKey}&loop=${(this.mode + 1) % 3}&time=${media.currentTime}`);
	});

	fullscreenButton.addEventListener('click', function() {
		if (document.fullscreenElement) {
			document.exitFullscreen();
		} else {
			document.documentElement.requestFullscreen();
		}
	});

	document.documentElement.addEventListener('fullscreenchange', () => {
		if (document.fullscreenElement) {
			fullscreenButton.className = 'active';
		} else {
			fullscreenButton.className = 'inactive';
		}
	});

	if (localStorage.volume) {
		volumeSlider.value = localStorage.volume;
	}

	volumeSlider.addEventListener('input', function() {
		if (media != null) {
			media.volume = this.value / 100;
			localStorage.volume = this.value;
		}
	});

	volumeStatus.addEventListener('click', function() {
		if (media != null) {
			localStorage.muted = !media.muted;
			media.muted = !media.muted;
		}
	});

	lockButton.addEventListener('click', function() {
		fetch(`lock.php?room=${roomKey}`);
	});

	addMediaButton.addEventListener('click', function() {
		let iframe = document.createElement('iframe');

		iframe.addEventListener('load', function() {
			iframe.contentWindow.history.pushState = new Proxy(iframe.contentWindow.history.pushState, {
				  apply: () => {},
			});
			iframe.contentWindow.history.replaceState = new Proxy(iframe.contentWindow.history.replaceState, {
				  apply: () => {},
			});
		});

		iframe.src = `browse.php?room=${roomKey}`;

		catalogContainer.appendChild(iframe);
		catalogViewer.style.display = null;
	});

	closeCatalogButton.addEventListener('click', function() {
		catalogViewer.style.display = 'none';
		catalogContainer.innerHTML = '';
	});

	document.querySelectorAll('.pop-up-menu-button').forEach(button => button.addEventListener('click', function() {
		let menu = this.getAttribute('data-menu');

		document.querySelectorAll('.pop-up-menu').forEach(menu => menu.style.visibility = 'hidden');

		document.querySelectorAll('.pop-up-menu-button.active').forEach(button => {
			if (button != this) {
				button.className = 'pop-up-menu-button inactive';
			}
		});

		if (pinnedButton.className != 'active') {
			document.getElementById('controls').style.visibility = null;
		}
		pinnedButton.disabled = false;

		if (this.className == 'pop-up-menu-button inactive') {
			document.getElementById(menu).style.visibility = 'visible';
			this.className = 'pop-up-menu-button active';

			document.getElementById('controls').style.visibility = 'visible';
			pinnedButton.disabled = true;
		} else {
			this.className = 'pop-up-menu-button inactive';
		}
	}));

	clearQueueButton.addEventListener('click', function() {
		fetch(`clear-queue.php?room=${roomKey}`);
	});

	seekBackwardButton.addEventListener('click', function() {
		fetch(`seek-backward.php?room=${roomKey}`);
	});

	seekForwardButton.addEventListener('click', function() {
		fetch(`seek-forward.php?room=${roomKey}`);
	});

	pinnedButton.addEventListener('click', function() {
		if (this.className == 'active') {
			document.getElementById('controls').style.visibility = null;
			this.className = null;
		} else {
			document.getElementById('controls').style.visibility = 'visible';
			this.className = 'active';
		}
	});

	shuffleButton.addEventListener('click', function() {
		fetch(`shuffle.php?room=${roomKey}`);
	});

	captionsSelect.addEventListener('input', function() {
		setCaptions(this.value);
	});
});

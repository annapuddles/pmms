<?php
session_start();
?>
<html>
	<head>
		<meta charset="utf-8">
		<meta name="viewport" content="width=device-width, initial-scale=1.0">
		<title>pmms</title>
		<script src="mediaelement/mediaelement.min.js"></script>
		<script src="mediaelement/dailymotion.min.js"></script>
		<script src="mediaelement/facebook.min.js"></script>
		<script src="mediaelement/soundcloud.min.js"></script>
		<script src="mediaelement/twitch.min.js"></script>
		<script src="mediaelement/vimeo.min.js"></script>
		<script src="join.js"></script>
		<link rel="stylesheet" href="join.css">
	</head>
	<body>
		<div id="video-container"></div>
		<div id="controls-container" class="hover-menu-container">
			<div id="controls" class="hover-menu">
				<button id="play">
					<i class="fas fa-pause"></i>
				</button>
				<input type="range" id="progress" min="0" max="0" value="0">
				<div id="seek-controls">
					<button id="seek-backward">
						<i class="fas fa-backward"></i>
					</button>
					<button id="seek-forward">
						<i class="fas fa-forward"></i>
					</button>
				</div>
				<div id="timecodes">
					<span id="current-timecode">00:00:00</span>/<span id="duration-timecode">--:--:--</span>
				</div>
				<div id="volume-control">
					<div id="volume-status">
						<i class="fas fa-volume-up"></i>
					</div>
					<input type="range" id="volume" min="0" max="100" value="100">
				</div>
				<button id="fullscreen">
					<i class="fas fa-expand"></i>
				</button>
				<button id="queue-button" class="pop-up-menu-button inactive" data-menu="queue">
					<i class="fas fa-list"></i>
				</button>
				<button id="settings-button" class="pop-up-menu-button inactive" data-menu="settings">
					<i class="fas fa-cog"></i>
				</button>
				<button id="pinned">
					<i class="Fas fa-thumbtack"></i>
				</button>
				<button id="exit">
					<i class="fa-solid fa-door-open"></i>
				</button>
			</div>
		</div>
		<div id="queue" class="pop-up-menu">
			<div id="queue-title"><i class="fas fa-list"></i> Queue</div>
			<div id="current-video"><i class="fas fa-play"></i> <span id="current-video-title"></span></div>
			<div id="add-media">
				<button id="catalog"><i class="fas fa-grip-horizontal"></i></button><input id="url" placeholder="Enter media URL..."><button id="queue-video"><i class="fas fa-plus"></i></button>
			</div>
			<div id="queue-list"></div>
			<div id="queue-controls">
				<button id="loop" style="color: grey;">
					<i class="fas fa-repeat"></i>
				</button>
				<button id="shuffle">
					<i class="fas fa-shuffle"></i>
				</button>
				<button id="next">
					<i class="fas fa-step-forward"></i>
				</button>
				<button id="clear-queue">
					<i class="fas fa-trash"></i>
				</button>
			</div>
		</div>
		<div id="settings" class="pop-up-menu">
			<div class="settings-group">
				<div class="settings-group-title"><i class="fas fa-user-cog"></i> User settings</div>
				<div class="settings-group-main">
					<div class="setting">
						<div class="setting-label"><i class="fas fa-server"></i> / <i class="fas fa-language"></i></div>
						<select id="source" class="setting-input">
							<option>default</option>
						</select>
					</div>
					<div class="setting">
						<div class="setting-label"><i class="fa-solid fa-closed-captioning"></i></div>
						<select id="captions" class="setting-input">
							<option>off</option>
						</select>
					</div>
				</div>
			</div>
			<div class="settings-group">
				<div class="settings-group-title"><i class="fas fa-users-cog"></i> Room settings</div>
				<div class="settings-group-main">
					<div class="setting no-label">
						<button id="lock" class="setting-input">
							<i class="fas fa-lock-open"></i>
						</button>
					</div>
				</div>
			</div>
		</div>
		<div id="connection-lost-notice">
			<div id="icon"><i class="fa-solid fa-triangle-exclamation"></i></div>
			<p><strong>Connection to the server has been lost.</strong></p>
			<p>This room may have expired or the server may have gone offline temporarily.</p>
			<p>If this persists, click the <i class="fa-solid fa-door-open"></i> button on the controls below and try creating a new room.</p>
		</div>
	</body>
</html>

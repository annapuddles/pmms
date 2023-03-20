<?php
$Config = parse_ini_file("config.ini", true);

function get_param($name) {
	return isset($_GET[$name]) ? $_GET[$name] : null;
}

function create_db_connection() {
	global $Config;

	return new mysqli($Config["database"]["host"], $Config["database"]["user"], $Config["database"]["password"], $Config["database"]["name"], $Config["database"]["port"]);
}

function prune_rooms($conn) {
	global $Config;

	$stmt = $conn->prepare("DELETE FROM room WHERE expires IS NOT NULL AND expires < NOW()");
	$stmt->execute();
	$stmt->close();
}

function is_url_in_catalog($conn, $url) {
	$stmt = $conn->prepare("SELECT id FROM catalog WHERE url = ?");
	$stmt->bind_param("s", $url);
	$stmt->bind_result($id);
	$stmt->execute();
	$stmt->fetch();
	$stmt->close();

	return $id != null || preg_match("/^series=[0-9]+$/", $url);
}

function is_url_allowed($conn, $url) {
	global $Config;

	if ($Config["general"]["allow_custom_urls"]) {
		if (is_url_in_catalog($conn, $url)) {
			return true;
		}
	} else {
		if (!is_url_in_catalog($conn, $url)) {
			return false;
		}
	}

	if (isset($Config["general"]["allowed_url_patterns"]) && gettype($Config["general"]["allowed_url_patterns"]) == "array") {
		foreach ($Config["general"]["allowed_url_patterns"] as $index => $pattern) {
			if (preg_match($pattern, $url)) {
				error_log($pattern);
				return true;
			}
		}
		return false;
	} else {
		return true;
	}
}

function create_room($conn, $url, $title = null, $locked = null, $owner = null) {
	global $Config;

	prune_rooms($conn);

	if (!is_url_allowed($conn, $url)) {
		return null;
	}

	$stmt = $conn->prepare("SELECT UUID()");
	$stmt->bind_result($room);
	$stmt->execute();
	$stmt->fetch();
	$stmt->close();

	if ($locked == null) {
		$locked = $Config["rooms"]["lock_by_default"];
	}

	if ($owner == null) {
		$owner = session_id();
	}

	$stmt = $conn->prepare("INSERT INTO room (room_key, start_time, expires, owner, locked) VALUES (?, UNIX_TIMESTAMP() + 2, DATE_ADD(NOW(), INTERVAL ? SECOND), ?, ?)");
	$stmt->bind_param("sisi", $room, $Config["rooms"]["prune_after"], $owner, $locked);
	$result = $stmt->execute();
	$stmt->close();

	$room_id = get_room_id($conn, $room);
	$queue_id = enqueue_video($conn, $room_id, $url, $title);
	dequeue_video($conn, $room, $queue_id);

	return $room;
}

function get_room_id($conn, $room) {
	$stmt = $conn->prepare("SELECT id FROM room WHERE room_key = ?");
	$stmt->bind_param("s", $room);
	$stmt->bind_result($room_id);
	$stmt->execute();
	$stmt->fetch();
	$stmt->close();

	return $room_id;
}

function get_series_id($url, &$id) {
	if (preg_match("/^series=([0-9]+)$/", $url, $match)) {
		$id = $match[1];
		return true;
	} else {
		return false;
	}
}

function get_youtube_playlist_id($url, &$id) {
	if (preg_match("/^(?:(?:https?:)?\/\/)?(?:(?:www|m)\.)?(?:youtube\.com|youtu.be)\/playlist\?list=([a-z0-9_\-]+)$/i", $url, $match)) {
		$id = $match[1];
		return true;
	} else {
		return false;
	}
}

function enqueue_video($conn, $room_id, $url, $title = null) {
	if (get_series_id($url, $series_id)) {
		return enqueue_series($conn, $room_id, $series_id);
	} else if (get_youtube_playlist_id($url, $playlist_id)) {
		return enqueue_youtube_playlist($conn, $room_id, $playlist_id);
	} else if ($title == null && get_youtube_video_id($url, $video_id)) {
		return enqueue_youtube_video($conn, $room_id, $video_id);
	} else {
		if ($title == null) {
			$title = $url;
		}

		$stmt = $conn->prepare("INSERT INTO queue (room_id, url, title) VALUES (?, ?, ?)");
		$stmt->bind_param("iss", $room_id, $url, $title);
		$stmt->execute();
		$queue_id = $stmt->insert_id;
		$stmt->close();
		return $queue_id;
	}
}

function get_youtube_playlist_videos($playlist_id) {
	global $Config;

	if (!array_key_exists("api_key", $Config["youtube"])) {
		error_log("No YouTube API key specified in config.ini!");
		return [];
	}

	$videos = [];
	$next_page_token = false;

	while ($next_page_token !== null) {
		$url = "https://www.googleapis.com/youtube/v3/playlistItems?playlistId=" . $playlist_id . "&part=snippet&maxResults=50&key=" . $Config["youtube"]["api_key"];

		if ($next_page_token) {
			$url = $url . "&pageToken=" . $next_page_token;
		}

		$playlist = json_decode(file_get_contents($url));

		foreach ($playlist->items as $item) {
			$videos[] = [
				"id" => $item->snippet->resourceId->videoId,
				"title" => $item->snippet->title
			];
		}

		if (property_exists($playlist, "nextPageToken")) {
			$next_page_token = $playlist->nextPageToken;
		} else {
			$next_page_token = null;
		}
	}

	return $videos;
}

function enqueue_series($conn, $room_id, $series) {
	$stmt = $conn->prepare("SELECT id, url, title FROM catalog WHERE series = ? ORDER BY sort_title");
	$stmt->bind_param("i", $series);
	$stmt->execute();

	$result = $stmt->get_result();

	$queue_id = null;

	while ($row = $result->fetch_assoc()) {
		if ($row["url"] == null) {
			$id = enqueue_series($conn, $room_id, $row["id"]);
		} else {
			$id = enqueue_video($conn, $room_id, $row["url"], $row["title"]);
		}

		if ($queue_id == null) {
			$queue_id = $id;
		}
	}

	$stmt->close();

	return $queue_id;
}

function enqueue_youtube_playlist($conn, $room_id, $playlist_id) {
	$videos = get_youtube_playlist_videos($playlist_id);

	$queue_id = null;

	foreach ($videos as $video) {
		$id = enqueue_video($conn, $room_id, "https://youtube.com/watch?v=" . $video["id"], $video["title"]);

		if ($queue_id == null) {
			$queue_id = $id;
		}
	}

	return $queue_id;
}

function dequeue_video($conn, $room, $queue_id) {
	$stmt = $conn->prepare("SELECT room_id, url, title FROM queue WHERE id = ?");
	$stmt->bind_param("i", $queue_id);
	$stmt->bind_result($room_id, $url, $title);
	$stmt->execute();
	$stmt->fetch();
	$stmt->close();

	$stmt = $conn->prepare("SELECT url, title, loop_media FROM room WHERE room_key = ?");
	$stmt->bind_param("s", $room);
	$stmt->bind_result($current_url, $current_title, $loop);
	$stmt->execute();
	$stmt->fetch();
	$stmt->close();

	if (isset($url)) {
		// Add the current video to the end of the queue
		if ($loop == 2 && $current_url) {
			enqueue_video($conn, $room_id, $current_url, $current_title);
		}

		$stmt = $conn->prepare("UPDATE room SET url = ?, title = ?, start_time = UNIX_TIMESTAMP() + 2, paused = null WHERE room_key = ?");
		$stmt->bind_param("sss", $url, $title, $room);
		$stmt->execute();
		$stmt->close();

		// Add all videos before the selected queue item to the end of the queue
		if ($loop == 2) {
			$stmt = $conn->prepare("INSERT INTO queue (room_id, url, title) SELECT room_id, url, title FROM queue WHERE room_id = ? and id < ?");
			$stmt->bind_param("ii", $room_id, $queue_id);
			$stmt->execute();
			$stmt->close();
		}

		$stmt = $conn->prepare("DELETE FROM queue WHERE room_id = ? and id <= ?");
		$stmt->bind_param("ii", $room_id, $queue_id);
		$stmt->execute();
		$stmt->close();
	}
}

function get_owner($conn, $room) {
	$stmt = $conn->prepare("SELECT owner FROM room WHERE room_key = ?");
	$stmt->bind_param("s", $room);
	$stmt->bind_result($owner);
	$stmt->execute();
	$stmt->fetch();
	$stmt->close();

	return $owner;
}

function can_control_room($conn, $session_id, $room) {
	$stmt = $conn->prepare("SELECT owner, locked FROM room WHERE room_key = ?");
	$stmt->bind_param("s", $room);
	$stmt->bind_result($owner, $locked);
	$stmt->execute();
	$stmt->fetch();
	$stmt->close();

	return !$locked || $session_id == $owner;
}

function clear_queue($conn, $room) {
	$room_id = get_room_id($conn, $room);

	$stmt = $conn->prepare("DELETE FROM queue WHERE room_id = ?");
	$stmt->bind_param("i", $room_id);
	$stmt->execute();
	$stmt->close();
}

function get_youtube_video_id($url, &$id) {
	if (preg_match('%(?:youtube(?:-nocookie)?\.com/(?:[^/]+/.+/|(?:v|e(?:mbed)?)/|.*[?&]v=)|youtu\.be/)([^"&?/\s]{11})%i', $url, $match)) {
		$id = $match[1];
		return true;
	} else {
		return false;
	}
}

function get_youtube_video_info($video_id) {
	global $Config;

	if (!array_key_exists("api_key", $Config["youtube"])) {
		return null;
	}

	$url = "https://www.googleapis.com/youtube/v3/videos?part=snippet&id=" . $video_id . "&key=" . $Config["youtube"]["api_key"];

	$response = json_decode(file_get_contents($url));

	return $response->items[0];
}

function enqueue_youtube_video($conn, $room_id, $video_id) {
	$video = get_youtube_video_info($video_id);

	$url = "https://youtube.com/watch?v=" . $video_id;

	if ($video) {
		$title = $video->snippet->title;
	} else {
		$title = $url;
	}

	return enqueue_video($conn, $room_id, $url, $title);
}

function shuffle_queue($conn, $room) {
	$room_id = get_room_id($conn, $room);

	$stmt = $conn->prepare("SELECT url, title FROM queue WHERE room_id = ?");
	$stmt->bind_param("i", $room_id);
	$stmt->execute();

	$result = $stmt->get_result();
	$queue = [];

	while ($row = $result->fetch_assoc()) {
		$queue[] = [
			"url" => $row["url"],
			"title" => $row["title"]
		];
	}

	$stmt->close();

	$stmt = $conn->prepare("SELECT url, title FROM room WHERE id = ?");
	$stmt->bind_param("i", $room_id);
	$stmt->bind_result($url, $title);
	$stmt->execute();
	$stmt->fetch();
	$stmt->close();

	$queue[] = [
		"url" => $url,
		"title" => $title
	];

	clear_queue($conn, $room);

	shuffle($queue);

	$queue_id = null;

	foreach ($queue as $item) {
		$stmt = $conn->prepare("INSERT INTO queue (room_id, url, title) VALUES (?, ?, ?)");
		$stmt->bind_param("iss", $room_id, $item["url"], $item["title"]);
		$stmt->execute();

		if ($queue_id == null) {
			$queue_id = $stmt->insert_id;
		}

		$stmt->close();
	}

	return $queue_id;
}

function bump_room($conn, $room) {
	global $Config;

	$stmt = $conn->prepare("UPDATE room SET expires = DATE_ADD(NOW(), INTERVAL ? SECOND) WHERE room_key = ? AND expires IS NOT NULL");
	$stmt->bind_param("is", $Config["rooms"]["prune_after"], $room);
	$stmt->execute();
	$stmt->close();
}
?>

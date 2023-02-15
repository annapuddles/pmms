<?php
include "pmms.php";

session_start();

$room = $_GET["room"];

$conn = create_db_connection();

bump_room($conn, $room);

if (isset($_GET["source"])) {
	$source = $_GET["source"];

	$stmt = $conn->prepare("SELECT room.id AS id, source.source_url AS url, title, start_time, UNIX_TIMESTAMP() - start_time AS time, paused, loop_media, owner, locked FROM room JOIN source ON room.url = source.url WHERE room_key = ? AND source_name = ?");

	$stmt->bind_param("ss", $room, $source);
} else {
	$stmt = $conn->prepare("SELECT id, url, title, start_time, UNIX_TIMESTAMP() - start_time AS time, paused, loop_media, owner, locked FROM room WHERE room_key = ?");

	$stmt->bind_param("s", $room);
}

$stmt->bind_result($room_id, $url, $title, $start_time, $time, $paused, $loop_media, $owner, $locked);
$stmt->execute();
$stmt->fetch();
$stmt->close();

header("Content-type: application/json");

if ($room_id == null) {
	http_response_code(404);
	echo json_encode(["error" => "Room not found"]);
} else {
	$stmt = $conn->prepare("SELECT id FROM queue WHERE room_id = ? ORDER BY id LIMIT 1");
	$stmt->bind_param("i", $room_id);
	$stmt->bind_result($queue_id);
	$stmt->execute();
	$stmt->fetch();
	$stmt->close();

	$data = [
		"url" => $url,
		"title" => $title,
		"start_time" => $start_time,
		"time" => $time,
		"paused" => $paused,
		"loop" => $loop_media,
		"next" => $queue_id,
		"locked" => $locked,
		"is_owner" => session_id() == $owner
	];

	echo json_encode($data);
}

$conn->close();

?>

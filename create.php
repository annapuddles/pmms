<?php
include "pmms.php";

session_start();

$family = get_param("family");
$join = get_param("join");
$url = get_param("url");
$title = get_param("title");
$lock = get_param("lock");

$conn = create_db_connection();

if ($Config["rooms"]["lock_by_default"]) {
	$locked = $lock == "no" ? 0 : 1;
} else {
	$locked = $lock == "yes" ? 1 : 0;
}

if ($join == null) {
	$room = create_room($conn, $url, $title, $locked);
} else {
	$stmt = $conn->prepare('SELECT room_key FROM room where room_key = ?');
	$stmt->bind_param('s', $join);
	$stmt->bind_result($room);
	$stmt->execute();
	$stmt->fetch();
	$stmt->close();

	if ($room == null) {
		$room = create_room($conn, $url, $title, $locked, null, $join);
	} else {
		enqueue_video($conn, get_room_id($conn, $room), $url, $title);
	}
}

$conn->close();

http_response_code(302);

if ($room == null) {
	header("Location: browse.php");
} else {
	$params = [];

	if ($family) {
		$params['family'] = $family;
	}

	if ($join) {
		$params['join'] = $join;
	}

	$params['room'] = $room;

	header("Location: join.php?" . http_build_query($params));
}
?>

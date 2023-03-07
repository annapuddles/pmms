<?php
include "pmms.php";

session_start();

$room = $_GET["room"];
$url = $_GET["url"];
$title = isset($_GET["title"]) ? $_GET["title"] : null;

$conn = create_db_connection();

if (is_url_allowed($conn, $url)) {
	if (can_control_room($conn, session_id(), $room)) {
		$room_id = get_room_id($conn, $room);

		enqueue_video($conn, $room_id, $url, $title);
	}
} else {
	http_response_code(400);
}

$conn->close();
?>

<?php
include "pmms.php";

session_start();

$family = get_param("family");
$url = get_param("url");
$title = get_param("title");
$lock = get_param("lock");

$conn = create_db_connection();

if ($Config["rooms"]["lock_by_default"]) {
	$locked = $lock == "no" ? 0 : 1;
} else {
	$locked = $lock == "yes" ? 1 : 0;
}

$room = create_room($conn, $url, $title, $locked);

$conn->close();

http_response_code(302);

if ($room == null) {
	header("Location: browse.php");
} else {
	if ($family) {
		header("Location: join.php?family=" . $family . "&room=" . $room);
	} else {
		header("Location: join.php?room=" . $room);
	}
}
?>

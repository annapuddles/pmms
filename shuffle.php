<?php
include "pmms.php";

session_start();

$room = $_GET["room"];

$conn = create_db_connection();

if (can_control_room($conn, session_id(), $room)) {
	$queue_id = shuffle_queue($conn, $room);
	dequeue_video($conn, $room, $queue_id);
}

$conn->close();
?>

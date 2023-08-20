<?php
include "pmms.php";

session_start();

$room = $_GET["room"];
$queue_id = $_GET["id"];

$conn = create_db_connection();

$owner = get_owner($conn, $room);

if ($owner == null || can_control_room($conn, session_id(), $room)) {
	dequeue_video($conn, $room, $queue_id);
}

$conn->close();

?>

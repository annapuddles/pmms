<?php
include "pmms.php";

session_start();

$room = $_GET["room"];

$conn = create_db_connection();

if (can_control_room($conn, session_id(), $room)) {
	clear_queue($conn, $room);
}

$conn->close();

?>

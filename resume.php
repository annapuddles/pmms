<?php
include "pmms.php";

session_start();

$room = $_GET["room"];

$conn = create_db_connection();

if (can_control_room($conn, session_id(), $room)) {
	$stmt = $conn->prepare("UPDATE room SET start_time = start_time + (UNIX_TIMESTAMP() - paused), paused = null WHERE room_key = ?");
	$stmt->bind_param("s", $room);
	$stmt->execute();
	$stmt->close();
}

$conn->close();
?>

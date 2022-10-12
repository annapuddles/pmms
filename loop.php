<?php
include "pmms.php";

session_start();

$room = $_GET["room"];
$loop = $_GET["loop"];
$time = $_GET["time"];

$conn = create_db_connection();

if (can_control_room($conn, session_id(), $room)) {
	if ($loop == 0) {
		$stmt = $conn->prepare("UPDATE room SET loop_media = 0, start_time = UNIX_TIMESTAMP() - ? WHERE room_key = ?");
		$stmt->bind_param("is", $time, $room);
		$stmt->execute();
		$stmt->close();
	} else {
		$stmt = $conn->prepare("UPDATE room SET loop_media = ? WHERE room_key = ?");
		$stmt->bind_param("is", $loop, $room);
		$stmt->execute();
		$stmt->close();
	}
}

$conn->close();
?>
